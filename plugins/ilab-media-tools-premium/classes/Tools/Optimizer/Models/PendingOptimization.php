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

namespace MediaCloud\Plugin\Tools\Optimizer\Models;
use MediaCloud\Plugin\Model\Model;
use MediaCloud\Plugin\Tools\Storage\StorageGlobals;
use MediaCloud\Plugin\Tools\Optimizer\OptimizerTool;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;

/**
 * Class MuxAsset
 * @package MediaCloud\Plugin\Tools\Video\Driver\Mux\Models
 *
 * @property string $optimizerId
 * @property int $createdAt
 * @property int $postId
 * @property string $localFile
 * @property string $remoteUrl
 * @property string $wordpressSize
 * @property int $savedToCloud
 * @property array $s3Data [
 *   'bucket' => string,
 *   'privacy' => string,
 *   'key' => string,
 *   'provider' => string,
 *   'v' => string
 * ]
 */
class PendingOptimization extends Model {
	//region Fields

	/**
	 * Optimizer Identifier
	 * @var null
	 */
	protected $optimizerId = null;

	/**
	 * Epoch time created
	 * @var int
	 */
	protected $createdAt = 0;

	/**
	 * The post ID, if any
	 * @var int
	 */
	protected $postId = 0;

	/**
	 * Local file this represents
	 * @var string
	 */
	protected $localFile = null;

	/**
	 * Remote URL this represents
	 * @var string
	 */
	protected $remoteUrl = null;

	/**
	 * WordPress size name
	 * @var string
	 */
	protected $wordpressSize = null;

	/**
	 * Saved to cloud?
	 * @var bool
	 */
	protected $savedToCloud = false;

	/**
	 * S3 Data
	 * @var array
	 */
	protected $s3Data = null;

	protected $modelProperties = [
		'optimizerId' => '%s',
		'createdAt' => '%d',
		'localFile' => '%s',
		'remoteUrl' => '%s',
		'wordpressSize' => '%s',
		's3Data' => '%s',
		'savedToCloud' => '%d',
		'postId' => '%d',
	];

	protected $columnMap = [
		'optimizerId' => 'optimizer_id',
		'createdAt' => 'created_at',
		'localFile' => 'local_file',
		'remoteUrl' => 'remote_url',
		'wordpressSize' => 'wordpress_size',
		's3Data' => 's3_data',
		'savedToCloud' => 'saved_to_cloud',
		'postId' => 'post_id',
	];

	protected $serializedProperties = [
		's3Data'
	];

	//endregion


	//region Static

	public static function table() {
		global $wpdb;
		return "{$wpdb->base_prefix}mcloud_pending_optimizations";
	}

	//endregion


	//region Constructor

	public function __construct($data = null) {
		parent::__construct($data);
	}

	//endregion

	//region Queries

	/**
	 * Returns a task with the given ID
	 *
	 * @param $id
	 *
	 * @return PendingOptimization|null
	 * @throws \Exception
	 */
	public static function pending($optimizationId) {
		global $wpdb;

		$table = static::table();
		$rows = $wpdb->get_results($wpdb->prepare("select * from {$table} where optimizer_id = %s", $optimizationId));

		foreach($rows as $row) {
			return new static($row);
		}

		return null;
	}

	/**
	 * @param OptimizerResultsInterface $result
	 * @return PendingOptimization|null
	 * @throws \Exception
	 */
	public static function fromResult($result) {
		$pending = new static();

		$pending->optimizerId = $result->id();
		$pending->localFile = $result->localFile();
		$pending->remoteUrl = $result->remoteUrl();
		$pending->createdAt = time();

		return $pending;
	}

	//endregion

	//region Upload Handling
	public function handleOptimizedUrl($optimizedUrl) {
		Logger::info("Handling optimized URL: $optimizedUrl", [], __METHOD__, __LINE__);

		/** @var OptimizerTool $optimizeTool */
		$optimizeTool = ToolsManager::instance()->tools['optimizer'];

		if (empty($this->savedToCloud)) {
			Logger::info("Not saved to cloud.", [], __METHOD__, __LINE__);

			if (empty($optimizedUrl)) {
				Logger::info("Optimized ", [], __METHOD__, __LINE__);
				return;
			}

			$tries = 1;
			while($tries <= 5) {
				Logger::info("Downloading $optimizedUrl try $tries of 5", [], __METHOD__, __LINE__);
				$tmpName = tempnam('/tmp', 'optimized-');
				$downloadResult = wp_remote_get($optimizedUrl, [
					'stream' => true,
					'filename' => $tmpName,
					'timeout' => 60 * $tries,
				]);

				if (is_wp_error($downloadResult)) {
					Logger::info("Error downloading $optimizedUrl to $tmpName: ".$downloadResult->get_error_message(), [], __METHOD__, __LINE__);
				} else {
					Logger::error("Finished downloading $optimizedUrl", [], __METHOD__, __LINE__);
					break;
				}

				$tries++;
				sleep(1 + ($tries - 1));
			}


			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];

			Logger::info("Uploading $optimizedUrl to cloud storage", [], __METHOD__, __LINE__);
			$mimeType = arrayPath($this->s3Data, 'mimeType', null);
			$optimizedUrl = $storageTool->client()->upload($this->s3Data['key'], $tmpName, $this->s3Data['privacy'], StorageGlobals::cacheControl(), StorageGlobals::cacheControl(), $mimeType);
			Logger::info("Finished uploading $optimizedUrl to cloud storage", [], __METHOD__, __LINE__);

			@unlink($tmpName);
		} else {
			Logger::info("Already saved to cloud: ".$this->remoteUrl, [], __METHOD__, __LINE__);
		}


		if (!empty($this->postId)) {
			$optimizeTool->processCloudMetadata($optimizedUrl, $this->postId, $this->wordpressSize, $this->s3Data);
		} else {
			Logger::error("Missing post id for pending.  Size {$this->wordpressSize}.", [], __METHOD__, __LINE__);
		}
	}
	//endregion
}