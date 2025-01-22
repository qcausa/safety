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

use MediaCloud\Plugin\Tools\Optimizer\OptimizerAccountStatus;
use MediaCloud\Plugin\Tools\Optimizer\OptimizerConsts;
use function MediaCloud\Plugin\Utilities\arrayPath;

class KrakenIOAccountStatus implements OptimizerAccountStatus {
	private $stats = null;

	public function __construct($stats) {
		$this->stats = $stats;
	}


	/**
	 * @inheritDoc
	 */
	public function quotaType() {
		return OptimizerConsts::QUOTA_BYTES;
	}

	/**
	 * @inheritDoc
	 */
	public function quota() {
		return (int)arrayPath($this->stats, 'quota_total', 0);
	}

	/**
	 * @inheritDoc
	 */
	public function used() {
		return (int)arrayPath($this->stats, 'quota_used', 0);
	}

	/**
	 * @inheritDoc
	 */
	public function plan() {
		return arrayPath($this->stats, 'plan_name', 0);
	}
}