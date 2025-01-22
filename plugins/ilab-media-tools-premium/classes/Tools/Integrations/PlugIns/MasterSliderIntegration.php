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

use MediaCloud\Plugin\Tools\DynamicImages\DynamicImagesTool;
use MediaCloud\Plugin\Utilities\Environment;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class MasterSliderIntegration {
	private $cropThumb;
	private $resizeImage;
	private $imageWidth;
	private $imageHeight;
	private $thumbWidth;
	private $thumbHeight;

	public function __construct() {
		$this->resizeImage = Environment::Option('mcloud-master-slider-image-resize', null, 80);
		$this->cropThumb = Environment::Option('ilab-mc-master-slider-thumb-crop', null, true);

		$this->imageWidth = Environment::Option('ilab-mc-master-slider-image-width', null, 0);
		$this->imageHeight = Environment::Option('mcloud-master-slider-image-height', null, 0);

		$this->thumbWidth = Environment::Option('mcloud-master-slider-thumb-width', null, 0);
		$this->thumbHeight = Environment::Option('mcloud-master-slider-thumb-height', null, 0);

		add_filter('masterslider_slider_content', [$this, 'filterMasterSlider'], 1000, 2);
	}

	public function filterMasterSlider($sliderContent, $sliderId) {
		$uploadDir = wp_get_upload_dir();

		/** @var DynamicImagesTool|null $dymamicImageTool */
		$dymamicImageTool = DynamicImagesTool::currentDynamicImagesTool();

		$ms_slider_shortcode = msp_get_ms_slider_shortcode_by_slider_id( $sliderId );

		if (!preg_match('/\[ms_slider([^\]]+)\]/m', $ms_slider_shortcode, $shortcodeMatches)) {
			return $sliderContent;
		}

		$attrText = $shortcodeMatches[1];
		preg_match_all('/([aA-zZ0-9_-]+)\s*=\s*["\']{1}([^"\']*)["\']{1}\s*/m', $attrText, $matches, PREG_SET_ORDER);

		$attrs = [];
		foreach($matches as $match) {
			$attrs[$match[1]] = $match[2];
		}

		$width = $this->imageWidth ?: arrayPath($attrs, 'width', 0);
		$height = $this->imageHeight ?: arrayPath($attrs, 'height', 0);
		$canResizeImage = ($width != 0) && ($height != 0);

		$thumbWidth = $this->thumbWidth ?: arrayPath($attrs, 'thumbs_width', 0);
		$thumbHeight = $this->thumbHeight ?: arrayPath($attrs, 'thumbs_height', 0);
		$canResizeThumb = ($thumbWidth != 0) && ($thumbHeight != 0);

		if (!preg_match_all('/\[ms_slide\s*(?:.*)src_full\s*=\s*["\']{1}([^\'"]*)(?:.*)\s+thumb\s*=\s*["\']{1}([^\'"]*)/m', $ms_slider_shortcode, $srcMatches, PREG_SET_ORDER)) {
			return;
		}

		$imageSources = [];
		$thumbSources = [];
		foreach($srcMatches as $srcMatch) {
			$ogImageSrc = $imageSrc = $srcMatch[1];
			$ogThumbSrc = $thumbSrc = $srcMatch[2];

			if (empty(parse_url($ogImageSrc, PHP_URL_SCHEME))) {
				$ogImageSrc = $uploadDir['baseurl'].$ogImageSrc;
				$imageId = attachment_url_to_postid($ogImageSrc);
			} else {
				$imageId = attachment_url_to_postid($ogImageSrc);
			}

			if ($ogThumbSrc == $ogImageSrc) {
				$thumbId = $imageId;
			} else {
				$attachThumbSrc = $ogThumbSrc;
				if (preg_match('/(-[0-9x]+)\.(?:jpg|gif|png)/m', $attachThumbSrc, $thumbSizeMatches)) {
					$attachThumbSrc = str_replace($thumbSizeMatches[1], '', $attachThumbSrc);
				} else {
					$attachThumbSrc = $ogThumbSrc;
				}

				if (empty(parse_url($ogThumbSrc, PHP_URL_SCHEME))) {
					$ogThumbSrc = $uploadDir['baseurl'].$ogThumbSrc;
					$thumbId = attachment_url_to_postid($uploadDir['baseurl'].$attachThumbSrc);
				} else {
					$thumbId = attachment_url_to_postid($attachThumbSrc);
				}
			}

			if (empty($imageId) || empty($thumbId)) {
				continue;
			}

			if ($canResizeImage && $this->resizeImage) {
				if (!empty($dymamicImageTool)) {
					$imageSrc = $dymamicImageTool->buildSizedImage($imageId, [$width, $height, false]);
				} else {
					$imageSrc = image_get_intermediate_size($imageId, [$width, $height, false]);
				}


				if (is_array($imageSrc)) {
					$imageSrc = (isset($imageSrc['url'])) ? $imageSrc['url'] : $imageSrc[0];
				}

				if (empty($imageSrc)) {
					$imageSrc = wp_get_attachment_url($imageId);
				}
			}

			if ($canResizeThumb) {
				if (!empty($dymamicImageTool)) {
					$thumbSrc = $dymamicImageTool->buildSizedImage($thumbId, [$thumbWidth, $thumbHeight, $this->cropThumb]);
				} else {
					$thumbSrc = image_get_intermediate_size($thumbId, [$thumbWidth, $thumbHeight, $this->cropThumb]);
				}

				if (is_array($thumbSrc)) {
					$thumbSrc = (isset($thumbSrc['url'])) ? $thumbSrc['url'] : $thumbSrc[0];
				}

				if (empty($thumbSrc)) {
					$thumbSrc = wp_get_attachment_url($thumbId);
				}
			}

			$imageSources[$ogImageSrc] = $imageSrc;
			$thumbSources[$ogThumbSrc] = $thumbSrc;
		}

		if (preg_match_all('/<img(?:.*)\s+data-src\s*=\s*["\']{1}([^\'"]*)(?:[^>]+)>/m', $sliderContent, $imageMatches, PREG_SET_ORDER)) {
			foreach($imageMatches as $imageMatch) {
				$tag = $imageMatch[0];
				$source = $imageMatch[1];
				if (isset($imageSources[$source])) {
					$newTag = str_replace($source, $imageSources[$source], $tag);
					$sliderContent = str_replace($tag, $newTag, $sliderContent);
				}
			}
		}

		if (preg_match_all('/<img(?:.*)\s+class\s*=\s*["\']{1}ms-thumb["\']{1}(?:.*)\s+src\s*=["\']{1}([^"\']*)(?:[^>]+)>/m', $sliderContent, $thumbMatches, PREG_SET_ORDER)) {
			foreach($thumbMatches as $thumbMatch) {
				$tag = $thumbMatch[0];
				$source = $thumbMatch[1];
				if (isset($thumbSources[$source])) {
					$newTag = str_replace($source, $thumbSources[$source], $tag);
					$sliderContent = str_replace($tag, $newTag, $sliderContent);
				}
			}
		}

		return $sliderContent;
	}
}