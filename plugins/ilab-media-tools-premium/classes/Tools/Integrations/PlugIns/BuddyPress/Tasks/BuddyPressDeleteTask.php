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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress\Tasks;

use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress\BuddyPressMap;
use MediaCloud\Plugin\Utilities\Logging\Logger;

if (!defined('ABSPATH')) { header('Location: /'); die; }

class BuddyPressDeleteTask extends Task {
	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'buddypress-delete';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Delete BuddyPress Uploads';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.buddypress-delete';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Delete BuddyPress Uploads';
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
		return false;
	}

	/**
	 * The identifier for analytics
	 * @return string
	 */
	public static function analyticsId() {
		return '/batch/buddypress-delete';
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
	protected function cleanEmptyDirectories() {
		if (defined('UPLOADS')) {
			$root = trailingslashit(ABSPATH).UPLOADS;
		} else {
			$root = trailingslashit(WP_CONTENT_DIR).'uploads';
		}

		$root = trailingslashit($root);

		$folders = [];

		$rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
		foreach($rii as $file) {
			if ($file->isDir()) {
				$path = trailingslashit($file->getPath());
				if (!in_array($path, $folders) && ($path != $root)) {
					$folders[] = $path;
				}
			}
		}

		usort($folders, function($a, $b) {
			$countA = count(explode(DIRECTORY_SEPARATOR, $a));
			$countB = count(explode(DIRECTORY_SEPARATOR, $b));
			if ($countA > $countB) {
				return -1;
			} else if ($countA == $countB) {
				return 0;
			}

			return 1;
		});

		foreach($folders as $folder) {
			$filecount = count(scandir($folder));
			if ($filecount <= 2) {
				Logger::info("Removing directory $folder", [], __METHOD__, __LINE__);
				@rmdir($folder);
			} else if (($filecount == 3) && file_exists(trailingslashit($folder).'.DS_STORE')) {
				Logger::info("Removing .DS_STORE", [], __METHOD__, __LINE__);
				unlink(trailingslashit($folder).'.DS_STORE');

				Logger::info("Removing directory $folder", [], __METHOD__, __LINE__);
				@rmdir($folder);
			} else {
				Logger::info("NOT Removing directory $folder", [], __METHOD__, __LINE__);
			}
		}

		return true;
	}

	public function prepare($options = [], $selectedItems = []) {
		$items = BuddyPressMap::undeletedFiles();

		foreach($items as $item) {
			$this->addItem($item);
		}

		$this->addItem(['id' => -1]);

		return true;
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
		$post_id = $item['id'];

		if ($post_id == -1) {
			Logger::info("Cleaning empty directories.", [], __METHOD__, __LINE__);
			return $this->cleanEmptyDirectories();
		}

		Logger::info("Removing {$post_id} - {$item['file_path']}", [], __METHOD__, __LINE__);

		if (file_exists($item['file_path'])) {
			if (@unlink($item['file_path'])) {
				Logger::info("Deleted {$item['file_path']}", [], __METHOD__, __LINE__);
			} else {
				Logger::info("Error deleting {$item['file_path']}", [], __METHOD__, __LINE__);
			}
		} else {
			Logger::info("Skipping missing file: {$item['file_path']}", [], __METHOD__, __LINE__);
		}

		BuddyPressMap::markDeleted($post_id);

		Logger::info("Finished {$post_id}", [], __METHOD__, __LINE__);

		return true;
	}

	//endregion
}