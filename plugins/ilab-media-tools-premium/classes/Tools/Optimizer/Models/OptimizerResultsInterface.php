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

interface OptimizerResultsInterface {
	/**
	 * The result was a success
	 * @return bool
	 */
	public function success();
	/**
	 *
	 * The result was an error
	 * @return bool
	 */
	public function error();

	/**
	 * The error message if any
	 * @return string
	 */
	public function errorMessage();

	/**
	 * The optimization service's ID for the request
	 * @return string
	 */
	public function id();

	/**
	 * Optimization is pending
	 * @return bool
	 */
	public function waiting();

	/**
	 * The local path for the file
	 * @return string
	 */
	public function localFile();

	/**
	 * The remote URL
	 * @return string
	 */
	public function remoteUrl();

	/**
	 * The optimized URL
	 * @return string
	 */
	public function optimizedUrl();

	/**
	 * The original size of the image
	 * @return int
	 */
	public function originalSize();

	/**
	 * The optimized size
	 * @return int
	 */
	public function optimizedSize();
}