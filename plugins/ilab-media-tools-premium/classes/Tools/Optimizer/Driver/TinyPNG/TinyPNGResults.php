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

use MediaCloud\Plugin\Tools\Optimizer\Models\OptimizerResultsInterface;
use function MediaCloud\Plugin\Utilities\arrayPath;

class TinyPNGResults implements OptimizerResultsInterface {
	private $error = false;
	private $errorMessage = null;
	private $success = false;
	private $id = null;
	private $waiting = false;
	private $remoteUrl = null;
	private $localFile = null;
	private $optimizedUrl = null;
	private $originalSize = 0;
	private $optimizedSize = 0;

	public function __construct($results, $file = null) {
		$this->errorMessage = arrayPath($results, 'error', null);
		$this->error = !empty($this->errorMessage);

		$this->originalSize = (int)arrayPath($results, 'originalSize', 0);
		$this->optimizedSize = (int)arrayPath($results, 'optimizedSize', 0);

		$this->localFile = $file;

		$this->success = empty($this->error);
	}

	public function error() {
		return $this->error;
	}

	public function errorMessage() {
		return $this->errorMessage;
	}

	public function success() {
		return $this->success;
	}

	public function id() {
		return $this->id;
	}

	public function waiting() {
		return $this->waiting;
	}

	public function originalSize() {
		return $this->originalSize;
	}

	public function optimizedSize() {
		return $this->optimizedSize;
	}

	public function localFile() {
		return $this->localFile;
	}

	public function remoteUrl() {
		return $this->remoteUrl;
	}

	public function optimizedUrl() {
		return $this->optimizedUrl;
	}
}