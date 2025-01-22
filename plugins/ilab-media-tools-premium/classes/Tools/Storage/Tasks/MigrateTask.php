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

namespace MediaCloud\Plugin\Tools\Storage\Tasks;

use MediaCloud\Plugin\Tasks\TaskReporter;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tasks\AttachmentTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Search\Searcher;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\postIdExists;

class MigrateTask extends AttachmentTask {
	/** @var Searcher|null  */
	private $searcher = null;

	/** @var array  */
	private $sizes = [];

	/** @var TaskReporter|null  */
	private $searcherReporter = null;

	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'migrate-task';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Migrate To Storage';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.migrate-task';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Migrate To Cloud';
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
		return "Migrate to Cloud Storage";
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
		return '/batch/storage';
	}

	public static function warnOption() {
		return 'migrate-task-warning-seen';
	}

	public static function warnConfirmationAnswer() {
		return 'I UNDERSTAND';
	}

	public static function warnConfirmationText() {
		return "It is important that you backup your database prior to running this import task.  To continue, please type 'I UNDERSTAND' to confirm that you have backed up your database.";
	}

	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		$options = [
			'selected-items' => [
				"title" => "Selected Media",
				"description" => "If you want to process just a small subset of items, click on 'Select Media'",
				"type" => "media-select"
			],
			'skip-imported' => [
				"title" => "Skip Imported",
				"description" => "Skip items that have already been imported.",
				"type" => "checkbox",
				"default" => true
			],
			'ignore-errors' => [
				"title" => "Ignore Errors",
				"description" => "Ignore errors when they happen.",
				"type" => "checkbox",
				"default" => false
			],
			'verify-migration' => [
				"title" => "Generate Verification Report",
				"description" => "Generates a verification report for the migrated media.  This report will be located in the <code>".TaskReporter::reporterDirectory()."</code> directory.",
				"type" => "checkbox",
				"default" => true
			],
			'allow-optimizers' => [
				"title" => "Allow Image Optimizers",
				"description" => "If you are using the Image Optimization feature, or using a third party image optimization plugin, this will enable them to run, if needed, during migration.  Generally speaking, <strong>you do not want to turn this on as an error with an optimization can derail the entire migration</strong>.  You should <strong>optimize your media before running the migration</strong> and keep this option turned off.",
				"type" => "checkbox",
				"default" => false
			]
		];

		$imgix = apply_filters('media-cloud/dynamic-images/enabled', false);
		if ($imgix) {
			$options['skip-thumbnails'] = [
				"title" => "Skip Thumbnails",
				"description" => "This will skip uploading thumbnails and other images sizes, only uploading the original master image.  This requires Imgix.",
				"type" => "checkbox",
				"default" => false
			];
		}

		if (!empty(StorageToolSettings::prefixFormat())) {
			$warning = '';
			if (strpos(StorageToolSettings::prefixFormat(), '@{date:') !== false) {
				$warning = "<p><strong>WARNING:</strong> Your custom upload prefix has a date in it, it will use today's date.  This means that all of your images will be placed in a folder for today's date.  It is recommended to remove the dynamic date from the prefix until after import.</p>";
			}

			$prefix = StorageToolSettings::prefix();

			$options['path-handling'] = [
				"title" => "Upload Paths",
				"description" => "Controls where in cloud storage imported files are placed.  <p>Current custom prefix: <code>$prefix</code>.</p>$warning",
				"type" => "select",
				"options" => [
					'preserve' => 'Keep original upload path',
					'replace' => "Replace upload path with custom prefix",
					'prepend' => "Prepend upload path with custom prefix",
				],
				"default" => 'preserve',
			];
		}

		$options['sort-order'] = [
			"title" => "Sort Order",
			"description" => "Controls the order that items from your media library are migrated to cloud storage.",
			"type" => "select",
			"options" => [
				'default' => 'Default',
				'date-asc' => "Oldest first",
				'date-desc' => "Newest first",
				'title-asc' => "Title, A-Z",
				'title-desc' => "Title, Z-A",
				'filename-asc' => "File name, A-Z",
				'filename-desc' => "File name, Z-A",
			],
			"default" => 'default',
		];


		$options['replace-urls'] = [
			"title" => "Replace URLs",
			"description" => "Perform a search and replace in the database to update your old media URLs to the new one.",
			"type" => "checkbox",
			"default" => true
		];

		return $options;
	}

	//endregion

	//region Reporter
	public function reporter() {
		if (empty($this->reportHeaders)) {
			$allSizes = ilab_get_image_sizes();
			$sizeKeys = array_keys($allSizes);
			sort($sizeKeys);

			$this->reportHeaders = array_merge(array_merge([
				'Post ID',
				'Mime Type',
				'S3 Metadata Status',
				'Attachment URL',
				'Original Source Image URL',
			], $sizeKeys), ['Notes']);
		}

		return parent::reporter();
	}
	//endregion

	//region Data

	protected function filterPostArgs($args) {
		if (isset($this->options['skip-imported'])) {
			$args['meta_query'] = [
				'relation' => 'OR',
				[
					'relation' => 'AND',
					[
						'key'     => '_wp_attachment_metadata',
						'value'   => '"s3"',
						'compare' => 'NOT LIKE',
						'type'    => 'CHAR',
					],
					[
						'key'     => 'ilab_s3_info',
						'compare' => 'NOT EXISTS',
					],
				],
				[
					'relation' => 'AND',
					[
						'key'     => '_wp_attachment_metadata',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => 'ilab_s3_info',
						'compare' => 'NOT EXISTS',
					],
				]
			];
		}

		return $args;
	}

	//endregion

	//region Execution

	/**
	 * @return array|bool
	 */
	public function prepare($options = [], $selectedItems = []) {
		if (!isset($options['path-handling'])) {
			$options['path-handling'] = 'preserve';
		}

		return parent::prepare($options, $selectedItems); // TODO: Change the autogenerated stub
	}

	public function willStart() {
		parent::willStart();

		if (empty($this->options['allow-optimizers'])) {
			add_filter('media-cloud/storage/ignore-optimizers', '__return_true');
		}

		add_filter('media-cloud/storage/delete_uploads', '__return_false');
		add_filter('media-cloud/storage/queue-deletes', '__return_false');

		if (!empty(arrayPath($this->options, 'replace-urls'))) {
			$this->sizes = ilab_get_image_sizes();
			$this->sizes['full'] = [];
			$this->searcherReporter = new TaskReporter($this, [
				'Post ID',
				'Old URL',
				'Replacement URL',
				'Changes',
			], false, 'replacements');
			$this->searcher = new Searcher(false, false, false, null, null, null, null);
		}
	}

	public function didFinish() {
		if (empty($this->options['allow-optimizers'])) {
			remove_filter('media-cloud/storage/ignore-optimizers', '__return_true');
		}

		parent::didFinish();
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
		if (!postIdExists($post_id)) {
			if ($this->options['verify-migration']) {
				$this->reporter()->add([$post_id, '', 'Post does not exist']);
			}

			return true;
		}

		$this->updateCurrentPost($post_id);

		Logger::info("Processing $post_id", [], __METHOD__, __LINE__);

		try {
			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];
			$storageTool->processImport($this->currentItem, $post_id, null, $this->options);

			if ($this->options['verify-migration']) {
				Logger::info("Verifying $post_id", [], __METHOD__, __LINE__);
				$storageTool->verifyPost($post_id, false, $this->reporter(), function ($message, $newLine = false) {});
			}

			if (!empty($this->searcher)) {
				$totalChanges = $this->searcher->replacePostId($post_id, $this->sizes, $this->searcherReporter, function() {});
				Logger::info("$totalChanges total changes to $post_id", [], __METHOD__, __LINE__);
			}

			Logger::info("Finished processing $post_id", [], __METHOD__, __LINE__);
		} catch (\Exception $ex) {
			Logger::error($ex->getMessage());
			if (empty($this->options['ignore-errors'])) {
				throw $ex;
			}
		}

		return true;
	}


	public function complete() {
		do_action('media-cloud/storage/migration/complete');

		if (function_exists('rocket_clean_domain')) {
			rocket_clean_domain();
		}
	}

	//endregion
}
