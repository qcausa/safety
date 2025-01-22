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

namespace MediaCloud\Plugin\Tools\Optimizer\Driver\KrakenIO;

use MediaCloud\Plugin\Tools\Optimizer\Models\OptimizationStats;
use MediaCloud\Plugin\Tools\Optimizer\Models\PendingOptimization;
use MediaCloud\Plugin\Tools\Optimizer\OptimizerInterface;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Vendor\Kraken\Kraken;
use function MediaCloud\Plugin\Utilities\anyEmpty;
use function MediaCloud\Plugin\Utilities\arrayPath;

class KrakenIODriver implements OptimizerInterface {
	/** @var KrakenIOSettings  */
	protected $settings = null;

	public function __construct() {
		$this->settings = KrakenIOSettings::instance();
	}

	/**
	 * @inheritDoc
	 */
	public function enabled() {
		return (!anyEmpty($this->settings->apiKey, $this->settings->apiSecret));
	}

	/**
	 * @inheritDoc
	 */
	public function supportsWebhooks() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsUploads() {
		return true;
	}

	public function shouldUpload() {
		return ($this->supportsUploads() && !empty($this->settings->uploadImage));
	}

	public function shouldUseWebhook() {
		return $this->supportsWebhooks() && !empty($this->settings->useWebhook) && !empty($this->settings->webhookUrl);
	}

	/**
	 * @inheritDoc
	 */
	public function supportsCloudStorage($provider) {
		return false;//($provider === 's3');  too buggy
	}

	/**
	 * Generates parameters for the optimization
	 *
	 * @param array|null $cloudInfo
	 * @param string|null $sizeName
	 *
	 * @return array
	 */
	private function generateParams($cloudInfo = null, $sizeName = null) {
		$params = [
			'wait' => empty($this->shouldUseWebhook())
		];

		if ($this->shouldUseWebhook()) {
			$params['callback_url'] = $this->settings->webhookUrl;
		}

		if (!empty($cloudInfo)) {
			if (isset($cloudInfo['acl'])) {
				$cloudInfo['acl'] = ($cloudInfo['acl'] === 'public-read') ? 'public_read' : 'private';
			}

			$params['s3_store'] = $cloudInfo;
		}

		if (!empty($this->settings->lossy)) {
			$params['lossy'] = true;
			if (!empty($this->settings->lossyQuality)) {
				$params['quality'] = (int)$this->settings->lossyQuality;
			}
		}

		$preserve = [];

		if ($this->settings->preserveCopyright) {
			$preserve[] = 'copyright';
		}

		if ($this->settings->preserveDate) {
			$preserve[] = 'date';
		}

		if ($this->settings->preserveGeotag) {
			$preserve[] = 'geotag';
		}

		if ($this->settings->preserveOrientation) {
			$preserve[] = 'orientation';
		}

		if ($this->settings->preserveProfile) {
			$preserve[] = 'profile';
		}

		if (!empty($preserve)) {
			$params['preserve_meta'] = $preserve;
		}

		if ($this->settings->subsampling != '420') {
			$params['sampling_scheme'] = ($this->settings->subsampling == '422') ? '4:2:2' : '4:4:4';
		}

		if (!empty($sizeName)) {
			$params = apply_filters('media-cloud/optimizer/params/kraken', $params, $sizeName);
		}

		return $params;
	}

	/**
	 * @inheritDoc
	 */
	public function optimizeFile($filepath, $cloudInfo = null, $sizeName = null) {
		$params = $this->generateParams($cloudInfo, $sizeName);
		$params['file'] = $filepath;

		$tries = 1;

		while($tries <= 5) {
			Logger::info("Optimizing file $filepath for size $sizeName - try $tries of 5", [], __METHOD__, __LINE__);
			$kraken = new Kraken($this->settings->apiKey, $this->settings->apiSecret);
			$result = $kraken->upload($params);
			Logger::info("Finished optimizing file $filepath for size $sizeName", [], __METHOD__, __LINE__);

			if (isset($result['success']) && empty($result['success'])) {
				if (isset($result['error'])) {
					Logger::error("Error uploading file to Kraken: ".$result['error'], [], __METHOD__, __LINE__);
				} else {
					Logger::error("Error uploading file to Kraken: ".$result['message'], [], __METHOD__, __LINE__);
				}
			} else {
				break;
			}

			$tries++;

			sleep(1 + ($tries - 1));
		}

		return new KrakenIOResults($result, $filepath, null, $this->settings->useWebhook);
	}

	/**
	 * @inheritDoc
	 */
	public function optimizeUrl($url, $filepath, $cloudInfo = null, $sizeName = null) {
		$params = $this->generateParams($cloudInfo);
		$params['url'] = $url;

		Logger::info("Optimizing url $url for size $sizeName", [], __METHOD__, __LINE__);
		$kraken = new Kraken($this->settings->apiKey, $this->settings->apiSecret);

		$tries = 1;

		while ($tries <= 5) {
			Logger::info("Sending url $url to kraken for size $sizeName", [], __METHOD__, __LINE__);

			$result = $kraken->url($params);

			if (isset($result['success']) && empty($result['success'])) {
				if (isset($result['error'])) {
					Logger::error("Error posting URL to Kraken: ".$result['error'], [], __METHOD__, __LINE__);
				} else {
					Logger::error("Error posting URL to Kraken: ".$result['message'], [], __METHOD__, __LINE__);
				}
			} else {
				Logger::info("Finished optimizing url $url for size $sizeName", [], __METHOD__, __LINE__);
				break;
			}

			$tries++;
			sleep(1 + ($tries - 1));
		}

		return new KrakenIOResults($result, $filepath, $url, $this->settings->useWebhook);
	}

	public function handleWebhook($data) {
		$id = arrayPath($data, 'id', null);
		if (empty($id)) {
			Logger::error("Web hook data is missing id", [], __METHOD__, __LINE__);
			return;
		}

		/** @var PendingOptimization $pending */
		$pending = PendingOptimization::pending($id);
		if (empty($pending)) {
			Logger::info("Pending optmization $id could not be found.", [], __METHOD__, __LINE__);
			return;
		}

		$optimizedUrl = arrayPath($data, 'kraked_url', null);
		if (!empty($optimizedUrl)) {
			Logger::info("Processing pending $optimizedUrl for {$pending->id()}", [], __METHOD__, __LINE__);
			$pending->handleOptimizedUrl($optimizedUrl);

			if (!empty($pending->postId)) {
				$stats = new OptimizationStats($pending->postId);
				$stats->saveResult($pending->wordpressSize, arrayPath($data, 'original_size', 0), arrayPath($data, 'kraked_size', 0));
			}
		} else {
			Logger::error("Missing optimized url for {$pending->id()}", [], __METHOD__, __LINE__);
		}

		$pending->delete();
	}


	public function accountStats() {
		$kraken = new Kraken($this->settings->apiKey, $this->settings->apiSecret);
		$result = $kraken->status();

		return new KrakenIOAccountStatus($result);
	}


}