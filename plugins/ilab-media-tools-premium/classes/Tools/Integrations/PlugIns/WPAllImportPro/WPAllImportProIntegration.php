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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\WPAllImportPro;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tasks\TaskSchedule;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\WebStories\Tasks\UpdateWebStoriesTask;
use MediaCloud\Plugin\Tools\Storage\Tasks\MigrateTask;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\postIdExists;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class WPAllImportProIntegration {

	/** @var WPAllImportProSettings  */
	private $settings = null;

	private static $allImportRunning = false;
	private static $uploadedMedia = [];

	public function __construct() {
		$this->settings = WPAllImportProSettings::instance();

		if ($this->settings->enabled) {
			add_action('pmxi_before_post_import', function() {
				WPAllImportProIntegration::$allImportRunning = true;
			}, PHP_INT_MAX, 4);

			add_action('add_attachment', function($id) {
				if (!in_array($id, WPAllImportProIntegration::$uploadedMedia)) {
					WPAllImportProIntegration::$uploadedMedia[] = $id;
				}
			}, PHP_INT_MAX, 1);

			add_filter('media-cloud/storage/ignore-metadata-update', function($ignore, $attachmentId) {
				if (WPAllImportProIntegration::$allImportRunning) {
					if (!in_array($attachmentId, WPAllImportProIntegration::$uploadedMedia)) {
						WPAllImportProIntegration::$uploadedMedia[] = $attachmentId;
					}

					return true;
				}

				return $ignore;
			}, PHP_INT_MAX, 2);

			add_action('pmxi_after_post_import', function($importId) {
				$task = TaskSchedule::nextScheduledTaskOfType(MigrateTask::identifier());
				if (!empty($task)) {
					$task->nextRun = time() + (60 * 360);
					$task->selection = array_merge($task->selection, WPAllImportProIntegration::$uploadedMedia);
					$task->save();
				} else {
					MigrateTask::scheduleIn(360, [], WPAllImportProIntegration::$uploadedMedia);
				}
			}, PHP_INT_MAX, 1);

			add_action('pmxi_after_xml_import', function($importId, $import) {
				$selection = [];

				$task = TaskSchedule::nextScheduledTaskOfType(MigrateTask::identifier());
				if (!empty($task)) {
					$selection = $task->selection;
					$task->delete();
				}

				MigrateTask::scheduleIn(1, [], $selection);
			}, PHP_INT_MAX, 2);
		}
	}
}