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

use MediaCloud\Plugin\Tools\Optimizer\OptimizerAccountStatus;
use MediaCloud\Plugin\Tools\Optimizer\OptimizerConsts;

class TinyPNGAccountStatus implements OptimizerAccountStatus {
	private $stats = null;

	public function __construct($stats) {
		$this->stats = $stats;
	}


	/**
	 * @inheritDoc
	 */
	public function quotaType() {
		return OptimizerConsts::QUOTA_API_CALLS;
	}

	/**
	 * @inheritDoc
	 */
	public function quota() {
		return (int)$this->stats['remaining'];
	}

	/**
	 * @inheritDoc
	 */
	public function used() {
		return (int)$this->stats['count'];
	}

	/**
	 * @inheritDoc
	 */
	public function plan() {
		return null;
	}
}