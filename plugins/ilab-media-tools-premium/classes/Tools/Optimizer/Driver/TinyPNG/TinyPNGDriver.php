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

namespace MediaCloud\Plugin\Tools\Optimizer\Driver\TinyPNG;

use MediaCloud\Plugin\Tools\Optimizer\OptimizerInterface;
use MediaCloud\Plugin\Utilities\Logging\Logger;

class TinyPNGDriver implements OptimizerInterface {
	/** @var TinyPNGSettings  */
	protected $settings = null;

	public function __construct() {
		$this->settings = TinyPNGSettings::instance();
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

	private function processUpload($tinyPNG, $filepath, $cloudInfo, $sizeName) {
		if ($this->settings->preserveGeotag || $this->settings->preserveDate || $this->settings->preserveCopyright) {
			$preserve = [];

			if ($this->settings->preserveCopyright) {
				$preserve[] = 'copyright';
			}

			if ($this->settings->preserveDate) {
				$preserve[] = 'creation';
			}

			if ($this->settings->preserveGeotag) {
				$preserve[] = 'location';
			}

			$tinyPNG = $tinyPNG->preserve($preserve);
		}


		$filedir = pathinfo($filepath, PATHINFO_DIRNAME);
		$filename = pathinfo($filepath, PATHINFO_BASENAME);

		$results = [
			'originalSize' => filesize($filepath)
		];

		try {
			$result = $tinyPNG->toFile(trailingslashit($filedir).'optim-'.$filename);

			if (!empty($result)) {
				$results['optimizedSize'] = $result;

				@unlink($filepath);
				@rename(trailingslashit($filedir).'optim-'.$filename, $filepath);
			} else {
				$results['error'] = 'Unknown error';
			}
		} catch (\Exception $exception) {
			$results['error'] = $exception->getMessage();
		}

		return new TinyPNGResults($results, $filepath);
	}

	/**
	 * @inheritDoc
	 */
	public function optimizeFile($filepath, $cloudInfo = null, $sizeName = null) {
		Logger::info("Optimizing URL: $filepath", [], __METHOD__, __LINE__);

		\MediaCloud\Vendor\Tinify\setKey($this->settings->apiKey);
		$tinyPNG = \MediaCloud\Vendor\Tinify\fromFile($filepath);

		return $this->processUpload($tinyPNG, $filepath, $cloudInfo, $sizeName);
	}

	/**
	 * @inheritDoc
	 */
	public function optimizeUrl($url, $filepath, $cloudInfo = null, $sizeName = null) {
		Logger::info("Optimizing URL: $url", [], __METHOD__, __LINE__);

		\MediaCloud\Vendor\Tinify\setKey($this->settings->apiKey);
		$tinyPNG = \MediaCloud\Vendor\Tinify\fromUrl($url);

		return $this->processUpload($tinyPNG, $filepath, $cloudInfo, $sizeName);
	}

	/**
	 * @inheritDoc
	 */
	public function handleWebhook($data) {
	}

	public function accountStats() {
		return null;
	}
}