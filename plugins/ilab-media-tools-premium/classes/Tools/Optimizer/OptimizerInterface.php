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

namespace MediaCloud\Plugin\Tools\Optimizer;

use MediaCloud\Plugin\Tools\Optimizer\Models\OptimizerResultsInterface;

interface OptimizerInterface {
	/**
	 * Determines if the driver is enabled
	 * @return bool
	 */
	public function enabled();

	/**
	 * Determines if the driver supports webhook callbacks
	 * @return bool
	 */
	public function supportsWebhooks();

	/**
	 * Determines if the driver supports direct uploads
	 * @return bool
	 */
	public function supportsUploads();

	/**
	 * Determines if the media should be uploaded
	 * @return bool
	 */
	public function shouldUpload();

	/**
	 * Determines if the webhook should be used
	 * @return bool
	 */
	public function shouldUseWebhook();

	/**
	 * Determines if the driver supports saving the optimized files directly to cloud storage
	 * @param string $provider The cloud storage identifier (eg s3, do, google, etc.)
	 *
	 * @return mixed
	 */
	public function supportsCloudStorage($provider);

	/**
	 * Optimizes a local file via uploading
	 *
	 * @param $filepath
	 * @param $sizeName
	 * @param array|null $cloudInfo
	 *
	 * @return OptimizerResultsInterface
	 */
	public function optimizeFile($filepath, $cloudInfo = null, $sizeName = null);

	/**
	 * Optimizes an image at a given URL
	 *
	 * @param $url
	 * @param $filepath
	 * @param $sizeName
	 * @param array|null $cloudInfo
	 *
	 * @return OptimizerResultsInterface
	 */
	public function optimizeUrl($url, $filepath, $cloudInfo = null, $sizeName = null);

	/**
	 * Processes the data from a webhook callback
	 * @param array $data
	 *
	 * @return OptimizerResultsInterface
	 */
	public function handleWebhook($data);

	/**
	 * Returns the account stats
	 * @return OptimizerAccountStatus|null
	 */
	public function accountStats();
}