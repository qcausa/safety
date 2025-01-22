<?php

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\WPForms;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\WPForms\Tasks\WPFormsMigrateTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;
use function MediaCloud\Plugin\Utilities\arrayPath;

class WPFormsIntegration {
	/** @var WPFormsSettings|null  */
	protected $settings = null;


	public function __construct() {
		$this->settings = WPFormsSettings::instance();

		if (ToolsManager::instance()->toolEnabled('storage') && $this->settings->enabled) {
			TaskManager::registerTask(WPFormsMigrateTask::class);

			add_filter('wpforms_process_after_filter', function($fields, $entry, $form_data) {
				return self::processFormSubmission($this->settings, $fields, $form_data, true);
			}, PHP_INT_MAX, 3);

			add_filter('wpforms_entry_single_data', function($fieldData, $entry, $formData) {
				return $this->overrideSingleData($fieldData, $entry, $formData);
			}, PHP_INT_MAX, 4);

			add_filter('wpforms_entry_table_column_value', function($value, $entry, $column_name, $field_type) {
				return $this->overrideFileUploadColumn($value, $entry, $column_name, $field_type);

			}, PHP_INT_MAX, 4);
		}
	}

	private function overrideSingleData($fieldData, $entry, $formData) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		foreach($fieldData as &$fieldDatum) {
			if ($fieldDatum['type'] === 'file-upload') {
				foreach($fieldDatum['value_raw'] as &$rawValue) {
					$key = arrayPath($rawValue, 's3/key', null);
					if (!empty($key)) {
						$rawValue['value'] = $storageTool->client()->presignedUrl($key, 60 * 15);
					}
				}
			}
		}

		return $fieldData;
	}

	private function overrideFileUploadColumn($value, $entry, $column_name, $field_type) {
		if ($field_type !== 'file-upload') {
			return $value;
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$re = '/href\s*=\s*["\']{1}([^"\']+)/m';
		preg_match_all($re, $value, $matches, PREG_SET_ORDER, 0);

		foreach($matches as $match) {
			$url = $match[1];
			if (parse_url(home_url(), PHP_URL_HOST) === parse_url($url, PHP_URL_HOST)) {
				return $value;
			}

			$key = ltrim(parse_url($url, PHP_URL_PATH), '/');
			$newUrl = $storageTool->client()->presignedUrl($key, 60 * 15);
			$value = str_replace($url, $newUrl, $value);
		}

		return $value;
	}

	/**
	 * @param WPFormsSettings $settings
	 * @param $fields
	 * @param $entry
	 * @param $formData
	 *
	 * @return mixed
	 */
	public static function processFormSubmission($settings, $fields, $formData, $canDelete = true) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		foreach($fields as &$field) {
			if (arrayPath($field, 'type', null) !== 'file-upload') {
				continue;
			}

			$fieldId = arrayPath($field, 'id', null);
			$fieldDef = arrayPath($formData, "fields/$fieldId", null);
			if (empty($fieldDef)) {
				continue;
			}

			if (arrayPath($fieldDef, 'media_library', false) == 1) {
				continue;
			}

			$newUrls = [];
			foreach($field['value_raw'] as &$rawValue) {
				if (arrayPath($rawValue, 's3', null) !== null) {
					$newUrls[] = $rawValue['value'];
					continue;
				}

				$key = ltrim(parse_url($rawValue['value'], PHP_URL_PATH), '/');
				$filePath = trailingslashit(ABSPATH).$key;
				if (!file_exists($filePath)) {
					$newUrls[] = $rawValue['value'];
					continue;
				}

				try {
					if (!empty($settings->prefix)) {
						Prefixer::setType($rawValue['type']);
						$uniquePath = Prefixer::Parse($settings->prefix, null);
						if ($settings->randomFilename) {
							$ext = pathinfo($filePath, PATHINFO_EXTENSION);
							$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
						} else {
							$key = ltrim(trailingslashit($uniquePath), '/').$rawValue['file'];
						}
					} else if ($settings->randomFilename) {
						$uniquePath = Prefixer::genUUIDPath();
						$ext = pathinfo($filePath, PATHINFO_EXTENSION);
						$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
					}

					$newUrl = $storageTool->client()->upload($key, $filePath, $settings->acl);
					$newUrls[] = $newUrl;
					$rawValue['value'] = $newUrl;
					$rawValue['s3'] = [
						'url' => $newUrl,
						'bucket' => $storageTool->client()->bucket(),
						'privacy' => $settings->acl,
						'key' => $key,
						'provider' =>  StorageToolSettings::driver(),
						'v' => MEDIA_CLOUD_INFO_VERSION,
						'optimized' => false,
						'options' => [],
						'formats' => [],
					];

					if ($canDelete && $settings->deleteUploads) {
						@unlink($filePath);
					}
				} catch (\Exception $ex) {
					Logger::error("Error uploading $key to storage.  ".$ex->getMessage(), [], __METHOD__, __LINE__);
					$newUrls[] = $rawValue['value'];
				}
			}

			$field['value'] = implode('\n', $newUrls);
		}

		return $fields;
	}
}