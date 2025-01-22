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

use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\Imgix\ImgixTool;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use function MediaCloud\Plugin\Utilities\gen_uuid;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class UltimateMemberIntegration {
	private $currentS3Info = null;
	private $imgixEnabled = false;

	public function __construct() {
		$this->imgixEnabled = apply_filters('media-cloud/imgix/enabled', false);

		add_filter('wp_handle_upload', [$this, 'handleUpload'], 10000, 2);
		add_filter('media-cloud/storage/add-upload-filter', function($addFilter) {
			if (isset($_POST['action']) && ($_POST['action'] == 'um_imageupload')) {
				return false;
			}

			return $addFilter;
		});

		add_action('um_upload_image_process__cover_photo', [$this, 'resizeCoverPhoto'], 1000, 7);
		add_action('um_upload_image_process__profile_photo', [$this, 'resizeProfilePhoto'], 1000, 7);

		add_filter('um_user_cover_photo_uri__filter', [$this, 'filterCoverPhotoUri'], 1000, 3);
		add_filter('um_user_avatar_url_filter', [$this, 'filterUserAvatarUrl'], 1000, 3);
	}

	private function generateUrl($umKey, $s3Info) {
		if ($this->imgixEnabled) {
			/** @var ImgixTool $imgixTool */
			$imgixTool = ToolsManager::instance()->tools['imgix'];

			$params = apply_filters('media-cloud/integrations/ultimate-member/imgix-params', [], $umKey);

			return $imgixTool->urlForStorageMedia($s3Info['key'], $params);
		} else {
			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];

			if($storageTool->client()->usesSignedURLs('image')) {
				$url = $storageTool->client()->url($s3Info['key'], 'image');
				if(!empty(StorageToolSettings::cdn())) {
					$cdnScheme = parse_url(StorageToolSettings::cdn(), PHP_URL_SCHEME);
					$cdnHost = parse_url(StorageToolSettings::cdn(), PHP_URL_HOST);

					$urlScheme = parse_url($url, PHP_URL_SCHEME);
					$urlHost = parse_url($url, PHP_URL_HOST);

					return str_replace("{$urlScheme}://{$urlHost}", "{$cdnScheme}://{$cdnHost}", $url);
				} else {
					return $url;
				}
			} else if(!empty(StorageToolSettings::cdn())) {
				return StorageToolSettings::cdn() . '/' . $s3Info['key'];
			}

			return $s3Info['url'];
		}
	}

	public function filterUserAvatarUrl($avatarUrl, $userId, $data) {
		$this->currentS3Info = get_user_meta($userId, 'um_s3_info', true);

		if (!empty($this->currentS3Info) && isset($this->currentS3Info['profile_photo'])) {
			return $this->generateUrl('profile_photo', $this->currentS3Info['profile_photo']);
		}

		return $avatarUrl;
	}

	public function filterCoverPhotoUri($coverUri, $isDefault, $attrs) {
		if (!empty($this->currentS3Info) && isset($this->currentS3Info['cover_photo'])) {
			return $this->generateUrl('cover_photo', $this->currentS3Info['cover_photo']);
		}

		return $coverUri;
	}

	public function handleUpload($upload, $sideload) {
		if (isset($_POST['action']) && ($_POST['action'] == 'um_imageupload')) {
			$umKey = $_POST['key'];

			/** @var StorageTool $storageTool */
			$storageTool = ToolsManager::instance()->tools['storage'];

			if (file_exists($upload['file'])) {
				if (preg_match('/ultimatemember\/([0-9]+)\//m', $upload['file'], $matches)) {
					$userId = intval($matches[1]);
					if (!empty($userId)) {
						$key = str_replace(trailingslashit(WP_CONTENT_DIR), '', $upload['file']);
						if (strpos($key, 'uploads'.DIRECTORY_SEPARATOR) === 0) {
							$key = str_replace('uploads'.DIRECTORY_SEPARATOR, '', $key);
						}

						$url = $storageTool->client()->upload($key, $upload['file'], StorageToolSettings::privacy('image'));

						$s3data = get_user_meta($userId, 'um_s3_info', true);
						if (empty($s3data)) {
							$s3data = [];
						}

						$s3data[$umKey] = [
							'url' => $url,
							'key' => $key,
							'driver' => StorageToolSettings::driver()
						];

						update_user_meta($userId, 'um_s3_info', $s3data);
					}

				}
			}
		}

		return $upload;
	}

	private function handleResize($umKey, $response, $image_path, $src, $key, $user_id, $coord, $crop) {
		if (preg_match('/ultimatemember\/([0-9]+)\//m', $response['image']['source_path'], $matches)) {
			$userId = intval($matches[1]);
			if (!empty($userId)) {
				$key = str_replace(trailingslashit(WP_CONTENT_DIR), '', $response['image']['source_path']);
				if (strpos($key, 'uploads'.DIRECTORY_SEPARATOR) === 0) {
					$key = str_replace('uploads'.DIRECTORY_SEPARATOR, '', $key);
				}

				$basename = basename($response['image']['source_path']);
				$rootKey = str_replace($basename, '', $key);
				$key = $rootKey.gen_uuid(8).'/'.$basename;

				/** @var StorageTool $storageTool */
				$storageTool = ToolsManager::instance()->tools['storage'];
				$url = $storageTool->client()->upload($key, $response['image']['source_path'], StorageToolSettings::privacy('image'));

				$s3data = get_user_meta($userId, 'um_s3_info', true);
				if (empty($s3data)) {
					$s3data = [];
				}

				$s3data[$umKey] = [
					'url' => $url,
					'key' => $key,
					'driver' => StorageToolSettings::driver()
				];

				$response['image']['source_url'] = $url;

				update_user_meta($userId, 'um_s3_info', $s3data);
			}
		}

		return $response;
	}

	public function resizeCoverPhoto($response, $image_path, $src, $key, $user_id, $coord, $crop) {
		return $this->handleResize('cover_photo', $response, $image_path, $src, $key, $user_id, $coord, $crop);
	}

	public function resizeProfilePhoto($response, $image_path, $src, $key, $user_id, $coord, $crop) {
		return $this->handleResize('profile_photo', $response, $image_path, $src, $key, $user_id, $coord, $crop);
	}
}
