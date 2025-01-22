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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\GravityForms\Tasks;

use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\FluentForm\FluentFormSettings;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\GravityForms\GravityFormSettings;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\GravityForms\GravityFormsIntegration;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\ilab_set_time_limit;

if (!defined('ABSPATH')) { header('Location: /'); die; }

//$form = GFAPI::get_form( $form_id );

class GravityFormsMigrateTask extends Task {
	//region Private Properties
	private $formCache = [];

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'gravity-forms-migrate';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Migrate Gravity Forms Uploads';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.gravity-forms-migrate';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Migrate Gravity Forms';
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
		return '/batch/gravity-forms-migrate';
	}

	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [
		];
	}

	public static function warnOption() {
		return 'migrate-gravity-forms-warning-seen';
	}

	public static function warnConfirmationAnswer() {
		return 'I UNDERSTAND';
	}

	public static function warnConfirmationText() {
		return "It is important that you backup your database prior to running this import task.  To continue, please type 'I UNDERSTAND' to confirm that you have backed up your database.";
	}

	//endregion



	//region Execution

	public function prepare($options = [], $selectedItems = []) {
		global $wpdb;

		$submissions = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}gf_entry where id not in (select distinct entry_id from {$wpdb->prefix}gf_entry_meta where meta_key='s3')", ARRAY_A);
		$index = 1;
		foreach($submissions as $submission) {
			$this->addItem([
				'index' => $index,
				'id' => $submission['id']
			]);

			$index++;
		}


		return count($submissions) > 0;
	}

	protected function updateCurrentPost($entryId, $title) {
		$this->currentItemID = $entryId;
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
		$entryId = $item['id'];
		$entryIndex = $item['index'];

		$this->updateCurrentPost($entryId, "Entry #{$entryIndex}");
		if (!\GFAPI::entry_exists($entryId)) {
			return true;
		}

		$entry = \GFAPI::get_entry($entryId);
		$form = \GFAPI::get_form($entry['form_id']);

		GravityFormsIntegration::processFormSubmission(GravityFormSettings::instance(), $entry, $form, false);

		return true;
	}

	//endregion
}