<?php
// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// Uses code from:
// Persist Admin Notices Dismissal
// by Agbonghama Collins and Andy Fragen
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentSupport\Tasks;

use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentForm\FluentFormSettings;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentSupport\FluentSupportSettings;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\ilab_set_time_limit;

if (!defined('ABSPATH')) { header('Location: /'); die; }

class FluentSupportMigrateTask extends Task {
	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'fluent-support-migrate';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Migrate Fluent Support Uploads';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.fluent-support-migrate';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Migrate Fluent Support';
	}

	/**
	 * Controls if this task stops on an error.
	 *
	 * @return bool
	 */
	public static function stopOnError() {
		return false;
	}

	/**
	 * Bulk action title.
	 *
	 * @return string|null
	 */
	public static function bulkActionTitle() {
		return null;
	}

	/**
	 * Determines if a task is a user facing task.
	 * @return bool|false
	 */
	public static function userTask() {
		return true;
	}

	/**
	 * The identifier for analytics
	 * @return string
	 */
	public static function analyticsId() {
		return '/batch/fluent-support-migrate';
	}

	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [
		];
	}

	//endregion



	//region Execution

	public function prepare($options = [], $selectedItems = []) {
		global $wpdb;

		$localHost = parse_url(get_site_url(), PHP_URL_HOST);

		$attachments = $wpdb->get_results("select id, full_url from {$wpdb->prefix}fs_attachments", ARRAY_A);
		foreach($attachments as $attachment) {
			if ($localHost !== parse_url($attachment['full_url'], PHP_URL_HOST)) {
				continue;
			}

			$this->addItem([
				'id' => $attachment['id']
			]);
		}


		return count($attachments) > 0;
	}

	protected function updateCurrentPost($attachment_id, $title) {
		$this->currentItemID = $attachment_id;
		$this->currentFile = $title;
		$this->currentTitle = $title;
		$this->currentThumb = wp_mime_type_icon('image/jpeg');

		$this->save();
	}

	/**
	 * Performs the actual task
	 *
	 * @param $item
	 *
	 * @return bool|void
	 * @throws \Exception
	 */
	public function performTask($item) {
		global $wpdb;

		$settings = FluentSupportSettings::instance();

		$attachmentId = $item['id'];
		$attachment = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}fs_attachments WHERE id = {$attachmentId}", OBJECT);
		if (!$attachment || !file_exists($attachment->file_path)) {
			return true;
		}

		$this->updateCurrentPost($attachment->id, basename($attachment->file_path));

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$key = trim(parse_url($attachment->full_url, PHP_URL_PATH), '/');

		try {
			ilab_set_time_limit(0);

			if ($settings->randomFilename) {
				$uniquePath = Prefixer::genUUIDPath();
				$ext = pathinfo($attachment->file_path, PATHINFO_EXTENSION);
				$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
			}

			$s3Url = $storageTool->client()->upload($key, $attachment->file_path, $settings->acl);
			if ($s3Url) {
				$wpdb->update($wpdb->prefix.'fs_attachments', [ 'full_url' => $s3Url ], ['id' => $attachment->id]);
			}
		} catch (\Exception $ex) {
			Logger::error($ex->getMessage(), [], __METHOD__, __LINE__);
		}

		return true;
	}

	//endregion
}