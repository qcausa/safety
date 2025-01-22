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

interface OptimizerAccountStatus {
	/**
	 * Quota type
	 * @return int
	 */
	public function quotaType();

	/**
	 * Current quota
	 * @return int
	 */
	public function quota();

	/**
	 * Quota used
	 * @return int
	 */
	public function used();

	/**
	 * The name of the plan
	 *
	 * @return string|null
	 */
	public function plan();

}