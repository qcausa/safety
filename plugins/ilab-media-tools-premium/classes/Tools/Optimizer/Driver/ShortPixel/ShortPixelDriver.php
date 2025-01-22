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

namespace MediaCloud\Plugin\Tools\Optimizer\Driver\ShortPixel;

use MediaCloud\Plugin\Tools\Optimizer\OptimizerInterface;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Vendor\ShortPixel\ShortPixel;

class ShortPixelDriver implements OptimizerInterface {
	/** @var ShortPixelSettings  */
	protected $settings = null;

	public function __construct() {
		$this->settings = ShortPixelSettings::instance();
	}

	/**
	 * @inheritDoc
	 */
	public function enabled() {
		return (!empty($this->settings->apiKey));
	}


	/**
	 * @inheritDoc
	 */
	public function supportsWebhooks() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsUploads() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function shouldUpload() {
		return ($this->settings->uploadImage);
	}

	/**
	 * @inheritDoc
	 */
	public function shouldUseWebhook() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function supportsCloudStorage($provider) {
		return false;
	}

	/**
	 * Generates parameters
	 * @param $sizeName
	 *
	 * @return array [
	 *   'lossy' => int,
	 *   'keep_exif' => bool
	 * ]
	 */
	private function params($sizeName) {
		$params = [];

		$optimizeLevel = 1;
		if ($this->settings->lossy === 'glossy') {
			$optimizeLevel = 2;
		} else if ($this->settings->lossy === 'lossless') {
			$optimizeLevel = (int)0;
		}

		$params = [
			'lossy' => $optimizeLevel,
			'keep_exif' => !empty($this->settings->preserveExif)
		];

		if (!empty($sizeName)) {
			$params = apply_filters('media-cloud/optimizer/params/shortpixel', $params, $sizeName);
		}

		return $params;
	}

	/**
	 * @inheritDoc
	 */
	public function optimizeFile($filepath, $cloudInfo = null, $sizeName = null) {
		Logger::info("Optimizing URL: $filepath", [], __METHOD__, __LINE__);
		$params = $this->params($sizeName);

		$filedir = pathinfo($filepath, PATHINFO_DIRNAME);
		$filename = pathinfo($filepath, PATHINFO_BASENAME);

		\MediaCloud\Vendor\ShortPixel\setKey($this->settings->apiKey);

		try {
			$result = \MediaCloud\Vendor\ShortPixel\fromFile($filepath)
				->optimize($params['lossy'])
				->keepExif($params['keep_exif'])
				->toFiles($filedir, ['optim-'.$filename]);

			if ($result->status['code'] === 2) {
				@unlink($filepath);
				@rename(trailingslashit($filedir).'optim-'.$filename, $filepath);
			}

			return new ShortPixelResults($result, $filepath);
		} catch (\Exception $ex) {
			Logger::error("Error performing optimization: ".$ex->getMessage(), [], __METHOD__, __LINE__);
			return new ShortPixelResults((object)['status' => ['code' => 666, 'message' => $ex->getMessage()]], $filepath);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function optimizeUrl($url, $filepath, $cloudInfo = null, $sizeName = null) {
		Logger::info("Optimizing URL: $url", [], __METHOD__, __LINE__);
		$params = $this->params($sizeName);

		$filedir = pathinfo($filepath, PATHINFO_DIRNAME);
		$filename = pathinfo($filepath, PATHINFO_BASENAME);

		\MediaCloud\Vendor\ShortPixel\setKey($this->settings->apiKey);
		$tries = 1;
		$result = null;
		while($tries <= 5) {
			try {
				$result = \MediaCloud\Vendor\ShortPixel\fromUrls([$url])
					->optimize($params['lossy'])
					->keepExif($params['keep_exif'])
					->toFiles($filedir, ['optim-'.$filename]);

				break;
			} catch (\Exception $ex) {
				Logger::error("Error optimizing URL: ".$ex->getMessage(), [], __METHOD__, __LINE__);
			}

			$tries++;
			sleep(1 + ($tries - 1));
		}

		if (empty($result)) {
			Logger::error("Invalid result.", [], __METHOD__, __LINE__);
			return null;
		}

		Logger::info("Optimized result.", $result, __METHOD__, __LINE__);

		if ($result->status['code'] === 2) {
			@unlink($filepath);
			@rename(trailingslashit($filedir).'optim-'.$filename, $filepath);
		}

		return new ShortPixelResults($result, $filepath);
	}

	/**
	 * @inheritDoc
	 */
	public function handleWebhook($data) {
	}

	public function accountStats() {
		\MediaCloud\Vendor\ShortPixel\setKey($this->settings->apiKey);
		$result = \MediaCloud\Vendor\ShortPixel\ShortPixel::getClient()->apiStatus($this->settings->apiKey);
		if (count($result) > 0) {
			return new ShortPixelAccountStatus($result[0]);
		}

		return null;
	}
}