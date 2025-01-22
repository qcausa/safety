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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\NextGenGallery\Tasks;


use C_Photocrati_Transient_Manager;
use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\NextGenGallery\NextGenGalleryUtilities;
use MediaCloud\Plugin\Utilities\Logging\Logger;

class MigrateNextGenTask extends Task {
	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'migrate-ngg';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Migrate NextGen Gallery Images';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.migrate-ngg';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Migrate NextGen Galleries';
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
		return '/batch/migrate-ngg';
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

		$ids = $wpdb->get_results("select pid from {$wpdb->prefix}ngg_pictures where pid not in (select pid from {$wpdb->prefix}mcloud_ngg_data)");
		foreach($ids as $id) {
			$this->addItem(['pid' => $id->pid]);
		}

		$this->addItem(['pid' => -1]);

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
		$pid = $item['pid'];

		if ($pid == -1) {
			C_Photocrati_Transient_Manager::flush();
			return true;
		}

		global $wpdb;
		$image = $wpdb->get_row("select * from {$wpdb->prefix}ngg_pictures where pid = {$pid}");
		if (!empty($image)) {
			$image->meta_data = json_decode(base64_decode($image->meta_data), true);
		}

		$imported = NextGenGalleryUtilities::importImage($image, 'thumb');

		$this->currentFile = $image->filename;
		$this->currentItemID = $pid;
		$this->currentTitle = $image->filename;
		$this->currentThumb = $imported['thumb'];
		$this->save();

		NextGenGalleryUtilities::importImage($image, 'full');
		NextGenGalleryUtilities::importImage($image, 'thumbnail');

		Logger::info("Finished processing $pid", [], __METHOD__, __LINE__);

		return true;
	}

	//endregion
}