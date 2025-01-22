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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentSupport;

use FluentSupport\App\Models\Attachment;
use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentSupport\Tasks\FluentSupportMigrateTask;
use MediaCloud\Plugin\Tools\Storage\StorageConstants;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;
use function MediaCloud\Plugin\Utilities\ilab_set_time_limit;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class FluentSupportIntegration {
	/** @var FluentSupportSettings|null  */
	protected $settings = null;

	private $formCache = [];

	public function __construct() {
		$this->settings = FluentSupportSettings::instance();

		if (ToolsManager::instance()->toolEnabled('storage') && $this->settings->enabled) {
			TaskManager::registerTask(FluentSupportMigrateTask::class);

			add_filter('rest_pre_echo_response', function($result, $server, $request) {
				if (($request->get_route() === '/fluent-support/v2/ticket_file_upload') || ($request->get_route() === '/fluent-support/v2/customer-portal/ticket_file_upload')) {
					$this->processTicketFileUpload($result);
					return $result;
				}

				return $result;
			}, PHP_INT_MAX, 3);
		}
	}

	private function processTicketFileUpload($attachments) {
		global $wpdb;

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		foreach($attachments as $attachmentHash) {
			$attachment = $wpdb->get_row($wpdb->prepare('select * from '.$wpdb->prefix.'fs_attachments where file_hash = %s order by id desc', $attachmentHash), ARRAY_A);

			$src = $attachment['file_path'];
			if (!file_exists($src)) {
				continue;
			}

			$key = trim(parse_url($attachment['full_url'], PHP_URL_PATH), '/');

			try {
				ilab_set_time_limit(0);

				if ($this->settings->randomFilename) {
					$uniquePath = Prefixer::genUUIDPath();
					$ext = pathinfo($src, PATHINFO_EXTENSION);
					$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
				}

				$s3Url = $storageTool->client()->upload($key, $src, $this->settings->acl);
				if ($s3Url) {
					$attachment['full_url'] = $s3Url;
					$wpdb->update($wpdb->prefix.'fs_attachments', $attachment, ['id' => $attachment['id']]);
					@unlink($src);
				}
			} catch (\Exception $ex) {
				Logger::error($ex->getMessage(), [], __METHOD__, __LINE__);
			}
		}
	}

	public static function displayAttachment($fstFile) {
		$attachmentHash = sanitize_text_field($_REQUEST['fst_file']);

		if (!empty($attachmentHash)) {
			$attachment = Attachment::where('file_hash', $attachmentHash)->first();

			if (empty($attachment)) {
				return;
			}

			// check signature hash
			$sign = md5($attachment->id . date('YmdH'));
			if($sign != $_REQUEST['secure_sign']) {
				$dieMessage = __('Sorry, Your secure sign is invalid, Please reload the previous page and get new signed url', 'fluent-support');
				die($dieMessage);
			}
		}

		$externalHost = parse_url($attachment->full_url, PHP_URL_HOST);
		$localHost = parse_url(home_url(), PHP_URL_HOST);

		if ($externalHost === $localHost) {
			return;
		}

		$redirectUrl = $attachment->full_url;

		if (FluentSupportSettings::instance()->acl === StorageConstants::ACL_PRIVATE) {
			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];

			$key = trim(parse_url($attachment->full_url, PHP_URL_PATH), '/');
			$redirectUrl = $storageTool->client()->presignedUrl($key, '+15 minutes');
		}

		wp_redirect($redirectUrl);
		die();
	}
}

if (isset($_GET['fst_file'])) {
	add_action('init', function () {
		if (FluentSupportSettings::instance()->enabled) {
			FluentSupportIntegration::displayAttachment($_GET['fst_file']);
		}
	}, 9);
}
