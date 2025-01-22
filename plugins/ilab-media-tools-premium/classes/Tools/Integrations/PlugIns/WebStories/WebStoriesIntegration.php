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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\WebStories;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\WebStories\Tasks\UpdateWebStoriesTask;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\postIdExists;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class WebStoriesIntegration {
	public function __construct() {
		if (is_admin()) {
			TaskManager::registerTask(UpdateWebStoriesTask::class);
		}

		add_action('rest_insert_web-story', function(\WP_Post $post, \WP_REST_Request $request, bool $creating) {
			if ($post->post_type === 'web-story') {
				static::updatePost($post->ID, $post);
			}
		}, PHP_INT_MAX, 3);
	}

	private static function replaceUrl($content, $oldUrl, $newUrl, $jsonEncoded) {
		$oldUrl = (!empty($jsonEncoded)) ? str_replace('/', '\/', $oldUrl) : $oldUrl;
		$newUrl = (!empty($jsonEncoded)) ? str_replace('/', '\/', $newUrl) : $newUrl;

		return str_replace($oldUrl, $newUrl, $content);
	}

	private static function replaceMediaInContent($cache, $content, $jsonEncoded = false) {
		$sizes = ilab_get_image_sizes();
		foreach($cache['images'] as $image) {
			$src = wp_get_attachment_image_url($image['id'], 'full');
			if (!empty($src) && ($src != $image['src'])) {
				$content = static::replaceUrl($content, $image['src'], $src, $jsonEncoded);
			}

			foreach($sizes as $size => $sizeData) {
				add_filter('media-cloud/storage/ignore-cdn', '__return_true', PHP_INT_MAX);
				add_filter('media-cloud/dynamic-images/skip-url-generation', '__return_true', PHP_INT_MAX);
				add_filter('media-cloud/storage/override-url', '__return_false', PHP_INT_MAX);
				$ogSrc = wp_get_attachment_image_url($image['id'], $size);
				remove_filter('media-cloud/storage/override-url', '__return_false', PHP_INT_MAX);
				remove_filter('media-cloud/storage/ignore-cdn', '__return_true', PHP_INT_MAX);
				remove_filter('media-cloud/dynamic-images/skip-url-generation', '__return_true', PHP_INT_MAX);

				$src = wp_get_attachment_image_url($image['id'], $size);

				if (!empty($ogSrc) && !empty($src) && ($ogSrc !== $src)) {
					$content = static::replaceUrl($content, $ogSrc, $src, $jsonEncoded);
				}
			}
		}

		foreach($cache['videos'] as $video) {
			$src = wp_get_attachment_url($video['id']);
			if (!empty($src) && ($src != $video['src'])) {
				$content = static::replaceUrl($content, $video['src'], $src, $jsonEncoded);
			}
		}

		return $content;
	}

	private static function generateCache(\WP_Post $post) {
		$storyJson = $post->post_content_filtered;
		if (empty($storyJson)) {
			return null;
		}

		$storyData = json_decode($storyJson, true);
		if (empty($storyData)) {
			return null;
		}

		$cache = [
			'images' => [],
			'videos' => []
		];

		$keys = [];
		$pages = arrayPath($storyData, 'pages', []);
		foreach($pages as $page) {
			$elements = arrayPath($page, 'elements', []);
			foreach($elements as $element) {
				$resource = arrayPath($element, 'resource', null);
				if (empty($resource)) {
					continue;
				}

				$id = arrayPath($resource, 'id', null);
				if (empty($id) || !postIdExists($id)) {
					continue;
				}

				$mimeType = get_post_mime_type($id);
				if ((strpos($mimeType, 'image') !== 0) && (strpos($mimeType, 'video') !== 0)) {
					continue;
				}

				$type = arrayPath($resource, 'type', null);
				$src = arrayPath($resource, 'src', null);
				$title = arrayPath($resource, 'title', null);
				$alt = arrayPath($resource, 'alt', $title);
				$posterId = arrayPath($resource, 'posterId', null);

				if (empty($alt) || empty($id)) {
					continue;
				}

				$key = "$src,$alt,$id";
				if (isset($keys[$key])) {
					continue;
				}

				$keys[$key] = true;

				if ($type === 'image') {
					$cache['images'][] = [
						'src' => $src,
						'alt' => $alt,
						'id' => $id
					];
				} else if ($type === 'video') {
					$cache['videos'][] = [
						'src' => $src,
						'alt' => $alt,
						'id' => $id,
						'posterId' => $posterId
					];
				}
			}
		}

		$storyJson = static::replaceMediaInContent($cache, $storyJson, true);

		if ($storyJson !== $post->post_content_filtered) {
			$storyData = json_decode($storyJson, true);
			if (!empty($storyData)) {
				$post->post_content_filtered = $storyJson;
			}
		}

		return $cache;
	}

	public static function updatePost(int $post_id, \WP_Post $post) {
		$updatedPost = clone $post;
		static::filterWebStory($updatedPost);
		$needsUpdate = false;
		if ($updatedPost->post_content != $post->post_content) {
			$needsUpdate = true;
		}

		if ($updatedPost->post_content_filtered != $post->post_content_filtered) {
			$needsUpdate = true;
		}

		if ($needsUpdate) {
			wp_update_post($updatedPost);
		}
	}

	public static function filterWebStory(\WP_Post $post) {
		$content = $post->post_content;

		$cache = static::generateCache($post);
		if (empty($cache)) {
			return $post;
		}

		$posterRegex = '/<link(?:.*)href=\"([^"]+)\"(?:.*)as=\"image\"(?:[^>]+)>/m';
		preg_match_all($posterRegex, $content, $posterMatches, PREG_SET_ORDER, 0);
		foreach($posterMatches as $match) {
			$imgTag = $match[0];
			$attrStr = trim($match[1]);
			if (!empty($attrStr)) {
				foreach($cache['images'] as $img) {
					if ($img['src'] == $attrStr) {
						$newUrl = wp_get_attachment_image_url($img['id'], 'full');
						if (!empty($newUrl)) {
							$newImgTag = str_replace($attrStr, $newUrl, $imgTag);
							$content = str_replace($imgTag, $newImgTag, $content);
						}

						break;
					}
				}
			}
		}

		$posterRegex = '/<amp-story\s+(?:.*)poster-portrait-src\s*="(.[^"]+)"\s*>/m';
		preg_match_all($posterRegex, $content, $posterMatches, PREG_SET_ORDER, 0);
		foreach($posterMatches as $match) {
			$attrStr = trim($match[1]);
			if (!empty($attrStr)) {
				foreach($cache['images'] as $img) {
					if ($img['src'] == $attrStr) {
						$newUrl = wp_get_attachment_image_url($img['id'], 'full');
						if (!empty($newUrl)) {
							$content = str_replace($attrStr, $newUrl, $content);
						}

						break;
					}
				}
			}
		}

		$content = static::replaceMediaInContent($cache, $content, false);

		$post->post_content = $content;
		return $post;
	}
}