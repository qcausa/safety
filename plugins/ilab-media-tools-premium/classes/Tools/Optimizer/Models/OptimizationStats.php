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

use MediaCloud\Plugin\Tools\Optimizer\OptimizerToolSettings;
use MediaCloud\Plugin\Utilities\Environment;

/**
 * Class OptimizationStats
 * @package MediaCloud\Plugin\Tools\Optimizer\Models
 *
 * @property-read int $totalBytes
 * @property-read int $optimizedBytes
 * @property-read int $savedBytes
 * @property-read int $averageBytes
 * @property-read int $savedPercent
 * @property-read int $averagePercent
 * @property-read array $sizes
 */
class OptimizationStats {
	private $postId = null;
	private $data = [];

	// region Init

	public function __construct($postId) {
		$this->setDefaults();;
		$this->postId = $postId;
		if (!empty($postId)) {
			$this->data = get_post_meta($postId, '_mcloud_optimize_stats', true);
			if (empty($this->data)) {
				$this->setDefaults();
			}
		}
	}

	private function setDefaults() {
		$this->data = [
			'totalBytes' => 0,
			'optimizedBytes' => 0,
			'savedBytes' => 0,
			'averageBytes' => 0,
			'sizes' => [

			]
		];
	}

	//endregion

	// region Magic

	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		}

		if ($name === 'savedPercent') {
			if ($this->totalBytes === 0) {
				return 0;
			}

			return (int)round((1.0 - ($this->optimizedBytes / $this->totalBytes)) * 100.0);
		}


		if ($name === 'averagePercent') {
			if ($this->totalBytes === 0) {
				return 0;
			}

			return (int)round((1.0 - ($this->averageBytes / $this->totalBytes)) * 100.0);
		}
	}

	public function __isset($name) {
		if (in_array($name, array_keys($this->data))) {
			return true;
		}

		return in_array($name, ['savedPercent', 'averagePercent']);
	}

	//endregion


	//region Updating stats

	private function saveAllStats($originalSize, $optimizedSize) {
		$allStats = Environment::Option('mcloud-optimize-stats', null, null);

		if (empty($allStats)) {
			$allStats = [
				'totalOptimized' => 0,
				'totalBytes' => 0,
				'optimizedBytes' => 0,
				'savedBytes' => 0,
				'providers' => [
				]
			];
		}

		$provider = OptimizerToolSettings::instance()->provider;
		if (!isset($allStats['providers'][$provider])) {
			$allStats['providers'][$provider] = [
				'totalOptimized' => 0,
				'totalBytes' => 0,
				'optimizedBytes' => 0,
				'savedBytes' => 0,
			];
		}

		$allStats['totalOptimized'] += 1;
		$allStats['totalBytes'] += $originalSize;
		$allStats['optimizedBytes'] += $optimizedSize;
		$allStats['savedBytes'] += ($originalSize - $optimizedSize);

		$allStats['providers'][$provider]['totalOptimized'] += 1;
		$allStats['providers'][$provider]['totalBytes'] += $originalSize;
		$allStats['providers'][$provider]['optimizedBytes'] += $optimizedSize;
		$allStats['providers'][$provider]['savedBytes'] += ($originalSize - $optimizedSize);

		Environment::UpdateOption('mcloud-optimize-stats', $allStats);
	}

	/**
	 * Saves the result of an optimization
	 *
	 * @param string $size
	 * @param int $originalSize
	 * @param int $optimizedSize
	 */
	public function saveResult($size, $originalSize, $optimizedSize) {
		$this->saveAllStats($originalSize, $optimizedSize);

		$this->data['sizes'][$size] = [
			'totalBytes' => $originalSize,
			'optimizedBytes' => $optimizedSize,
			'savedBytes' => ($originalSize - $optimizedSize)
		];

		$this->data['totalBytes'] = 0;
		$this->data['optimizedBytes'] = 0;
		$this->data['savedBytes'] = 0;
		$this->data['averageBytes'] = 0;

		foreach($this->data['sizes'] as $size => $sizeData) {
			$this->data['totalBytes'] += $sizeData['totalBytes'];
			$this->data['optimizedBytes'] += $sizeData['optimizedBytes'];
			$this->data['savedBytes'] += $sizeData['savedBytes'];
		}

		if (count($this->data['sizes']) > 0) {
			$this->data['averageBytes'] = $this->data['savedBytes'] / count($this->data['sizes']);
		}

		if (!empty($this->postId)) {
			update_post_meta($this->postId, '_mcloud_optimize_stats', $this->data);
		}
	}

	// endregion
}