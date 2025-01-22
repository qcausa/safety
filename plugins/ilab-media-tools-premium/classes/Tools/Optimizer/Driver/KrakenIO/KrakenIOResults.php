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

use MediaCloud\Plugin\Tools\Optimizer\Models\OptimizerResultsInterface;
use function MediaCloud\Plugin\Utilities\arrayPath;

class KrakenIOResults implements OptimizerResultsInterface {
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

	public function __construct($data, $file = null, $url = null, $waiting = false) {
		$this->id = arrayPath($data, 'id', null);

		$this->error = (isset($data['success']) && empty($data['success']));
		if ($this->error) {
			if (isset($data['error']))  {
				$this->errorMessage = $data['error'];
			} else {
				$this->errorMessage = $data['message'];
			}
		}

		$this->localFile = $file;
		$this->remoteUrl = $url;
		$this->success = arrayPath($data, 'success', false);
		$this->optimizedUrl = arrayPath($data, 'kraked_url', null);
		$this->waiting = $waiting;

		$this->originalSize = arrayPath($data, 'original_size', (int)0);
		$this->optimizedSize = arrayPath($data, 'kraked_size', (int)0);
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