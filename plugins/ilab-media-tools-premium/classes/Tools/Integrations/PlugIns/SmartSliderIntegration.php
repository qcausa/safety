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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns;

use MediaCloud\Plugin\Tools\Storage\StorageConstants;
use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\DynamicImages\DynamicImagesTool;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;
use MediaCloud\Plugin\Utilities\NoticeManager;
use MediaCloud\Plugin\Utilities\Prefixer;
use MediaCloud\Vendor\IvoPetkov\HTML5DOMDocument;
use MediaCloud\Vendor\IvoPetkov\HTML5DOMElement;
use MediaCloud\Vendor\IvoPetkov\HTML5DOMNodeList;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class SmartSliderIntegration {
	/** @var StorageTool|null $storageTool */
	private $storageTool = null;

	/** @var DynamicImagesTool|null $dynamicImagesTool */
	private $dynamicImagesTool = null;

	/** @var string|null  */
	private $pathPrefix = null;

	public function __construct() {
		if (!defined('PHP_MAJOR_VERSION') || (PHP_MAJOR_VERSION<7)) {
			if (current_user_can('manage_options')) {
				NoticeManager::instance()->displayAdminNotice('warning', 'Smart Slider integration required PHP 7.0 or better.', true, 'media-cloud-smart-slider-warning', 7);
			}

			return;
		}

		add_filter('the_content', [$this, 'filterContent'], PHP_INT_MAX - 1, 1);

		$this->storageTool = ToolsManager::instance()->tools['storage'];
		$this->dynamicImagesTool = DynamicImagesTool::currentDynamicImagesTool();
		$this->pathPrefix = Environment::Option('mcloud-smart-slider-path-prefix', null, null);
		$this->pathPrefix = trim($this->pathPrefix, '/');
	}

	public function filterContent($content) {
		$html = new HTML5DOMDocument();
		$html->loadHTML($content, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);


		/** @var HTML5DOMNodeList $nodes */
		$nodes = $html->querySelectorAll('div.n2-ss-slide-background-image');
		if ($nodes->count() == 0) {
			return $content;
		}

		$resizedImages = [];
		$scheme = parse_url(home_url(), PHP_URL_SCHEME);

		/** @var HTML5DOMElement $node */
		foreach($nodes as $node) {
			if (empty($node->getAttribute('data-desktop'))) {
				continue;
			}

			$attr = $node->getAttribute('data-desktop');
			$img = $scheme.':'.$attr;
			$resizedImages[$attr] = $img;
		}

		/** @var HTML5DOMNodeList $nodes */
		$nodes = $html->querySelectorAll('div.n2-ss-slide');

		/** @var HTML5DOMElement $node */
		foreach($nodes as $node) {
			if (empty($node->getAttribute('data-thumbnail'))) {
				continue;
			}

			$attr = $node->getAttribute('data-thumbnail');
			$resizedImages[$attr] = $attr;
		}


		$upload_dir = wp_get_upload_dir();

		foreach($resizedImages as $attr => $resizedImage) {
			if (strpos($resizedImage, $upload_dir['baseurl']) === false) {
				continue;
			}

			$path = str_replace($upload_dir['baseurl'], '', $resizedImage);
			$localFile = $upload_dir['basedir'].$path;
			if (file_exists($localFile)) {
				$key = ltrim($path, '/');
				if (!empty($this->pathPrefix)) {
					$prefix = trim(Prefixer::Parse($this->pathPrefix), '/');
					if (!empty($prefix)) {
						$key = str_replace('resized/', $prefix.'/', $key);
					}
				}

				if (!$this->storageTool->client()->exists($key)) {
					$this->storageTool->client()->upload($key, $localFile, StorageConstants::ACL_PUBLIC_READ);
				}

				if (empty($this->dynamicImagesTool)) {
					$url = $this->storageTool->client()->url($key, 'image');
					if (!empty(StorageToolSettings::cdn())) {
						$cdnScheme = parse_url(StorageToolSettings::cdn(), PHP_URL_SCHEME);
						$cdnHost = parse_url(StorageToolSettings::cdn(), PHP_URL_HOST);

						$urlScheme =  parse_url($url, PHP_URL_SCHEME);
						$urlHost = parse_url($url, PHP_URL_HOST);

						$url = str_replace("{$urlScheme}://{$urlHost}", "{$cdnScheme}://{$cdnHost}", $url);
					}
				} else {
					$url = $this->dynamicImagesTool->urlForStorageMedia($key);
				}

				$content = str_replace($attr, $url, $content);
			}
		}


		return $content;
	}
}
