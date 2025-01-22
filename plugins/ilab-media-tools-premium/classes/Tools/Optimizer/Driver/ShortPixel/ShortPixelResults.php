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

use MediaCloud\Plugin\Tools\Optimizer\Models\OptimizerResultsInterface;

class ShortPixelResults implements OptimizerResultsInterface {
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
		if ($results->status['code'] !== 2) {
			$this->error = true;
			$this->errorMessage = ucfirst($results->status['message']);
		} else if (!property_exists($results, 'succeeded')) {
			$this->error = true;
			$this->errorMessage = 'Unknown error.';
		} else {
			$this->success = true;

			$this->originalSize = (int)$results->succeeded[0]->OriginalSize;

			if (ShortPixelSettings::instance()->lossy !== 'lossless') {
				$this->optimizedSize = (int)$results->succeeded[0]->LossySize;
			} else {
				$this->optimizedSize = (int)$results->succeeded[0]->LosslessSize;
			}
		}

		$this->localFile = $file;

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