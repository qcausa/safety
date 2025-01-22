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

namespace MediaCloud\Plugin\Tools\Video\Driver\Mux\Tasks;

use MediaCloud\Plugin\Tasks\AttachmentTask;
use MediaCloud\Plugin\Tools\Storage\StorageConstants;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Tools\Video\Driver\Mux\Models\MuxAsset;
use MediaCloud\Plugin\Tools\Video\Driver\Mux\MuxAPI;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\ilab_boolean_value;
use function MediaCloud\Plugin\Utilities\ilab_set_time_limit;
use function MediaCloud\Plugin\Utilities\ilab_stream_download;
use function MediaCloud\Plugin\Utilities\postIdExists;
use function MediaCloud\Plugin\Utilities\arrayPath;

class TransferTask extends AttachmentTask {
	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'transfer-mux-task';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Transfer Mux Videos';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.transfer-video-task';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Transfer Mux Videos';
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
		return "Transfer Mux Videos";
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
		return '/batch/transfer-video';
	}

	public static function warnOption() {
		return 'transfer-video-task-warning-seen';
	}

	public static function warnConfirmationAnswer() {
		return 'I UNDERSTAND';
	}

	public static function warnConfirmationText() {
		return "It is important that you backup your database prior to running this transfer task.  To continue, please type 'I UNDERSTAND' to confirm that you have backed up your database.";
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
			'local-only' => [
				"title" => "Local Only",
				"description" => "Transfer to local storage (your WordPress server) only.",
				"type" => "checkbox",
				"default" => false
			],
			'skip-transferred' => [
				"title" => "Skip Transferred",
				"description" => "Skips videos that have already been transferred from Mux.",
				"type" => "checkbox",
				"default" => true
			],
			'dest' => [
				"title" => "Destination Path",
				"description" => "The path in the uploads folder to transfer the Mux video to.",
				"type" => "text",
				"default" => '/video/hls/'
			],
			'delete' => [
				"title" => "Delete Mux Video",
				"description" => "Delete the Mux video after successfully transferring it.",
				"type" => "checkbox",
				"default" => false
			]
		];

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


		return $options;
	}

	//endregion

	//region Data

	protected function filterPostArgs($args) {
		$args['post_mime_type'] = 'video/*';
		return $args;
	}

	protected function addDataForPost($postId, $options):bool {
		$asset = MuxAsset::assetForAttachment($postId);
		if (empty($asset)) {
			return true;
		}

		$skipTransferred = isset($options['skip-transferred']) && ilab_boolean_value($options['skip-transferred']);
		if ($asset->isTransferred && $skipTransferred) {
			Logger::info("Skipping transferred video $postId", [], __METHOD__, __LINE__);
			return true;
		}

		$files = $asset->getFilesToTransfer($options['dest']);
		Logger::info("Adding video $postId to transfer queue with ".count($files)." files", [], __METHOD__, __LINE__);

		$localOnly = isset($options['local-only']) && ilab_boolean_value($options['local-only']);

		$relatedFiles = [];
		$transferData = [
			'source' => ($localOnly) ? 'local': 's3',
			'rendition' => []
		];

		foreach($files as $file) {
			Logger::info("Adding file {$file['key']} to transfer queue", [], __METHOD__, __LINE__);

			$relatedFiles[] = $file['key'];

			if (ilab_boolean_value(arrayPath($file, 'is-main', false))) {
				$transferData['playlist'] = $file['key'];
			}

			if (!empty(arrayPath($file, 'rendition', false))) {
				$transferData['rendition'][$file['rendition']] = $file['key'];
			}

			$this->addItem([
				'id' => $postId,
				'file' => $file,
			]);
		}

		$this->addItem([
			'id' => $postId,
			'last' => true,
			'related' => $relatedFiles,
			'transfer' => $transferData
		]);

		return true;
	}

	//endregion

	//region Execution

	public function lockExpiration() {
		return 60 * 60;
	}

	public function willStart() {
		add_filter('media-cloud/batch/lock-expiration', [$this, 'lockExpiration']);
		parent::willStart();
	}

	public function didFinish() {
		remove_filter('media-cloud/batch/lock-expiration', [$this, 'lockExpiration']);
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
			Logger::error("Post ID $post_id does not exist.", [], __METHOD__, __LINE__);
			return true;
		}

		ilab_set_time_limit(0);

		$this->updateCurrentPost($post_id);

		Logger::info("Processing $post_id", [], __METHOD__, __LINE__);
		Logger::info(json_encode($item, JSON_PRETTY_PRINT), [], __METHOD__, __LINE__);

		$asset = MuxAsset::assetForAttachment($post_id);
		if (!$asset) {
			Logger::info("Attachment $post_id is not a Mux video", [], __METHOD__, __LINE__);
			return true;
		}

		$localOnly = isset($this->options['local-only']) && ilab_boolean_value($this->options['local-only']);
		$delete = isset($this->options['delete']) && ilab_boolean_value($this->options['delete']);

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		if ((!$localOnly) && (!$storageTool || !$storageTool->enabled())) {
			Logger::error("Storage tool is not enabled", [], __METHOD__, __LINE__);
			return false;
		}

		if (isset($item['file'])) {
			$file = $item['file'];
			$dir = pathinfo($file['dest'], PATHINFO_DIRNAME);
			if (!file_exists($dir)) {
				Logger::info("Creating directory $dir", [], __METHOD__, __LINE__);
				@mkdir($dir, 0777, true);
			}

			if (isset($file['uri'])) {
				Logger::info("Processing url item {$file['key']} to {$file['dest']}", [], __METHOD__, __LINE__);
				ilab_stream_download($file['uri'], $file['dest']);
			} else if (isset($file['text'])) {
				Logger::info("Processing text item {$file['key']} to {$file['dest']}", [], __METHOD__, __LINE__);
				file_put_contents($file['dest'], $file['text']);
			}

			if(!$localOnly && isset($file['key'])) {
				Logger::info("Uploading {$file['key']} ... ", [], __METHOD__, __LINE__);
				$storageTool->client()->upload($file['key'], $file['dest'], StorageConstants::ACL_PUBLIC_READ, null, null, $file['content-type']);
				Logger::info("Done.", [], __METHOD__, __LINE__);

				if(StorageToolSettings::deleteOnUpload()) {
					Logger::info("Deleting {$file['key']} from local ... ", [], __METHOD__, __LINE__);
					@unlink($file['dest']);
					Logger::info("Done.", [], __METHOD__, __LINE__);
				}
			}
		} else if (isset($item['last'])) {
			$asset->transferData = $item['transfer'];
			$asset->relatedFiles = $item['related'];
			$asset->isTransferred = 1;
			$asset->save();

			if($delete) {
				try {
					MuxAPI::assetAPI()->deleteAsset($asset->muxId);
				} catch(\Exception $ex) {
					Logger::error('Mux: Error deleting asset from Mux: ' . $ex->getMessage(), [], __METHOD__, __LINE__);
				}
			}
		}

		Logger::info("Finished processing $post_id", [], __METHOD__, __LINE__);

		return true;
	}

	//endregion
}
