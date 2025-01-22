<?php

// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentForm;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentForm\Tasks\FluentFormMigrateTask;
use MediaCloud\Plugin\Tools\Storage\StorageConstants;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;
use function MediaCloud\Plugin\Utilities\ilab_set_time_limit;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class FluentFormIntegration {
	/** @var FluentFormSettings|null  */
	protected $settings = null;

	private $formCache = [];

	public function __construct() {
		$this->settings = FluentFormSettings::instance();

		if (ToolsManager::instance()->toolEnabled('storage') && $this->settings->enabled) {
			TaskManager::registerTask(FluentFormMigrateTask::class);

			add_filter('fluentform_insert_response_data', function($formData, $formId, $inputConfigs) {
				return $this->processFormData($formData, $formId, $inputConfigs);
			}, PHP_INT_MAX, 3);

			add_action('fluentform_submission_inserted', function($insertId, $formData, $form) {
				$this->submissionInserted($insertId, $formData, $form);
			}, 0, 3);


			if ($this->settings->acl !== StorageConstants::ACL_PUBLIC_READ) {
				add_filter('fluentform_single_response_data', function($submission, $formId) {
					return $this->processSubmission($submission, $formId);
				}, PHP_INT_MAX, 2);
			}
		}
	}

	private function submissionInserted($insertId, $formData, $form) {
		global $wpdb;
		$responseData = $wpdb->get_var('select response from '.$wpdb->prefix.'fluentform_submissions where id = '.$insertId);
		$responseData = json_decode($responseData, true);
		$responseData['migrated'] = true;
		$wpdb->update($wpdb->prefix.'fluentform_submissions', ['response' => json_encode($responseData)], ['id' => $insertId]);
	}

	private function processSubmission($submission, $formId) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		$responseData = json_decode($submission->response, true);

		$migrated = arrayPath($responseData, 'migrated', false);

		if (!isset($this->formCache[$formId])) {
			global $wpdb;
			$query = $wpdb->prepare('SELECT form_fields FROM '.$wpdb->prefix.'fluentform_forms WHERE id = %d', $formId);
			$fieldsJSON = $wpdb->get_var($query);
			if (!$fieldsJSON) {
				return $submission;
			}

			$fields = json_decode($fieldsJSON, true);
			if (!is_array($fields)) {
				return $submission;
			}

			$uploadFields = [];
			foreach($fields['fields'] as $field) {
				if (!in_array($field['element'], ['input_image', 'input_file'])) {
					continue;
				}

				$uploadFields[] = $field['attributes']['name'];
			}

			$this->formCache[$formId] = $uploadFields;
		} else {
			$uploadFields = $this->formCache[$formId];
		}

		$localHost = parse_url(home_url(), PHP_URL_HOST);
		foreach($uploadFields as $uploadField) {
			if (!isset($responseData[$uploadField])) {
				continue;
			}

			$uploadData = &$responseData[$uploadField];
			if (!is_array($uploadData) || empty($uploadData)) {
				continue;
			}

			foreach($uploadData as &$url) {
				$urlHost = parse_url($url, PHP_URL_HOST);
				if (!$migrated && ($urlHost === $localHost)) {
					continue;
				}

				$key = ltrim(parse_url($url, PHP_URL_PATH), '/');
				$signedUrl = $storageTool->client()->presignedUrl($key, '+30 minutes');
				$submission->user_inputs[$uploadField] = str_replace($url, $signedUrl, $submission->user_inputs[$uploadField]);
				$url = $signedUrl;
			}
		}

		$submission->response = json_encode($responseData);
		return $submission;
	}

	private function processFormData($formData, $formId, $inputConfig) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		foreach($inputConfig as $key => $config) {
			if (!in_array($config['element'], ['input_file', 'input_image'])) {
				continue;
			}

			if (!isset($formData[$key])) {
				continue;
			}

			foreach($formData[$key] as &$url) {
				$key = trim(parse_url($url, PHP_URL_PATH), '/');
				$src = ABSPATH.'/'.$key;
				if (!file_exists($src)) {
					continue;
				}

				try {
					ilab_set_time_limit(0);

					if ($this->settings->randomFilename) {
						$uniquePath = Prefixer::genUUIDPath();
						$ext = pathinfo($src, PATHINFO_EXTENSION);
						$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
					}

					$s3Url = $storageTool->client()->upload($key, $src, $this->settings->acl);
					if ($s3Url) {
						$url = $s3Url;
						@unlink($src);
					}
				} catch (\Exception $ex) {
					Logger::error($ex->getMessage(), [], __METHOD__, __LINE__);
				}
			}
		}

		return $formData;
	}
}