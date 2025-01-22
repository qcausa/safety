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

namespace MediaCloud\Plugin\Tools\Integrations;

use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Tool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\NoticeManager;
use MediaCloud\Vendor\JmesPath\Env;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class IntegrationsTool
 *
 * Tool for managing integrations
 */
class IntegrationsTool extends Tool {
	private $usePresignedURLForWoo = true;
	private $wooSignedURLExpiration = 1;

	private $usePresignedURLForEDD = true;
	private $eddSignedURLExpiration = 1;
	private $eddDownloadOriginalImage = false;

	private $integrations = [];


	public function __construct( $toolName, $toolInfo, $toolManager ) {
		parent::__construct( $toolName, $toolInfo, $toolManager );

		$this->usePresignedURLForWoo = Environment::Option('mcloud-woo-commerce-use-presigned-urls', null, true);
		$this->wooSignedURLExpiration = Environment::Option('mcloud-woo-commerce-presigned-expiration', null, 1);

		$this->usePresignedURLForEDD = Environment::Option('mcloud-edd-use-presigned-urls', null, true);
		$this->eddSignedURLExpiration = Environment::Option('mcloud-edd-presigned-expiration', null, 1);
		$this->eddDownloadOriginalImage = Environment::Option('mcloud-edd-download-original-image', null, false);
	}

	//region Tool Overrides

	public function setup() {
		parent::setup();

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		if ($storageTool->enabled()) {
			add_filter('wp_get_attachment_metadata',  [$this, 'fixWPMLDuplicateMetadata'], 1, 2 );
			add_filter('woocommerce_product_file_download_path', [$this, 'overrideWooCommerceDownloadPath'], 1000, 3);
			add_filter('edd_requested_file', [$this, 'eddRequestedFile'], 1000, 4);

			foreach($this->toolInfo['plugins'] as $plugin => $integrationClass) {
				if (is_plugin_active($plugin)) {
					if (class_exists($integrationClass)) {
						$this->integrations[] = new $integrationClass();
					} else {
						if (is_admin() && current_user_can('manage_options')) {
							NoticeManager::instance()->displayAdminNotice('error', "A required class for integration with a plugin you are using is missing.  Please contact <a href='https://support.mediacloud.press/'>Media Cloud support</a> as soon as possible.  This missing class is: <code>{$integrationClass}</code>.");
						}

						Logger::error("Integration class $integrationClass is missing.", [], __METHOD__, __LINE__);
					}
				}
			}

			foreach($this->toolInfo['classes'] as $pluginClass => $integrationClass) {
				if (class_exists($pluginClass)) {
					if (class_exists($integrationClass)) {
						$this->integrations[] = new $integrationClass();
					} else {
						if (is_admin() && current_user_can('manage_options')) {
							NoticeManager::instance()->displayAdminNotice('error', "A required class for integration with a plugin you are using is missing.  Please contact <a href='https://support.mediacloud.press/'>Media Cloud support</a> as soon as possible.  This missing class is: <code>{$integrationClass}</code>.");
						}

						Logger::error("Integration class $integrationClass is missing.", [], __METHOD__, __LINE__);
					}
				}
			}
		}
	}

	public function registerMenu($top_menu_slug, $networkMode = false, $networkAdminMenu = false, $tool_menu_slug = null) {
		parent::registerMenu($top_menu_slug);
	}

	public function enabled() {
		return true;
	}

	public function envEnabled() {
		return true;
	}

	public function alwaysEnabled() {
		return true;
	}

	//endregion


	//region WooCommerce Compatibility

	public function overrideWooCommerceDownloadPath($file_path, $product, $download_id) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		if (!$storageTool->enabled()) {
			return $file_path;
		}

		if (!filter_var($file_path, FILTER_VALIDATE_URL)) {
			return $file_path;
		}

		$postID = $storageTool->attachmentIdFromURL(null, $file_path);
		if (empty($postID)) {
			$prefixFix =  Environment::Option('mcloud-leopard-prefix-fix', null);

			$uploadDir = wp_upload_dir();
			$fixedFilePath = trailingslashit($uploadDir['baseurl']).ltrim(parse_url($file_path, PHP_URL_PATH), '/');
			if ($file_path === $fixedFilePath) {
				return $file_path;
			}

			$postID = $storageTool->attachmentIdFromURL(null, $fixedFilePath);

			if (empty($postID) && (strpos($fixedFilePath, '-scaled.') !== false)) {
				$fixedFilePath = str_replace('-scaled.', '.', $fixedFilePath);
				$postID = $storageTool->attachmentIdFromURL(null, $fixedFilePath);
			}

			if (empty($postID) && !empty($prefixFix)) {
				$fixedFilePath = str_replace($prefixFix.'/', '', $fixedFilePath);
				$postID = $storageTool->attachmentIdFromURL(null, $fixedFilePath);
			}
		}

		if (!empty($postID)) {
			if (!$this->usePresignedURLForWoo) {
				return wp_get_attachment_url($postID);
			}

			$meta = wp_get_attachment_metadata($postID);

			$isILABS3Info = false;
			if (empty($meta) || empty($meta['s3'])) {
				$meta = get_post_meta($postID, 'ilab_s3_info', true);

				$isILABS3Info = !empty($meta);
				if (isset($meta['s3'])) {
					$s3 = $meta['s3'];
				} else {
					$s3 = $meta;
				}
			} else {
				$s3 = $meta['s3'];
			}

			if (empty($s3)) {
				$url = wp_get_attachment_url($postID);
				if (!empty($url)) {
					$oldHost = parse_url($file_path, PHP_URL_HOST);
					$newHost = parse_url($url, PHP_URL_HOST);
					if ($oldHost === $newHost) {
						return $url;
					}
				}

				return $file_path;
			}

			// Fix bad ilab_s3_info
			if (!empty($isILABS3Info) && isset($meta['file']) && (!isset($meta['s3']))) {
				$mime = get_post_mime_type($postID);

				$s3 = [
					'url' => $storageTool->client()->url($meta['file']),
					'bucket' => $storageTool->client()->bucket(),
					'privacy' => StorageToolSettings::privacy($mime),
					'mime-type' => $mime,
					'key'=> $meta['file'],
					'provider' => StorageToolSettings::driver(),
					'optimized' => null,
					'options' => [],
					'v' => MEDIA_CLOUD_INFO_VERSION,
				];

				$meta = [
					'file' => $meta['file'],
					'url' => $s3['url'],
					'type' => $mime,
					's3' => $s3
				];

				update_post_meta($postID, 'ilab_s3_info', $meta);
			}

			return $storageTool->client()->presignedUrl($s3['key'], $this->wooSignedURLExpiration);
		}

		return $file_path;
	}

	//endregion


	//region EDD Compatibility

	public function eddRequestedFile($requested_file, $downloadFiles, $fileKey, $args) {
		if (empty($downloadFiles)) {
			return $requested_file;
		}

		$postID = null;
		$fileName = null;
		foreach($downloadFiles as $key => $downloadInfo) {
			if ($fileKey == $key) {
				$postID = $downloadInfo['attachment_id'];
				$fileName = pathinfo($downloadInfo['file'], PATHINFO_BASENAME);
				break;
			}
		}

		if (empty($postID)) {
			return $requested_file;
		}

		if (!$this->usePresignedURLForEDD) {
			return wp_get_attachment_url($postID);
		}

		$meta = wp_get_attachment_metadata($postID);
		if (empty($meta['s3'])) {
			$s3 = get_post_meta($postID, 'ilab_s3_info', true);
			if (isset($s3['s3'])) {
				$s3 = $s3['s3'];
			}
		} else {
			$s3 = $meta['s3'];
		}

		if ($this->eddDownloadOriginalImage && isset($meta['original_image_s3'])) {
			$s3 = $meta['original_image_s3'];
		}

		if (empty($s3) || empty($s3['provider'])) {
			return $requested_file;
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$providerClass = get_class($storageTool->client());
		$providerId = $providerClass::identifier();

		if ($s3['provider'] != $providerId) {
			return $requested_file;
		}

		return $storageTool->client()->presignedUrl($s3['key'], $this->eddSignedURLExpiration, [
			'ResponseContentDisposition' => 'attachment; filename="'.$fileName.'"'
		]);
	}

	//endregion


	//region WPML Compatibility

	/**
	 * Copy files on storage so that deleting a "translated" image doesn't physically delete it
	 *
	 * @param StorageTool $storageTool
	 * @param array $s3Data
	 * @param string $language
	 * @return array
	 */
	private function copyS3DataToNewLanguage($storageTool, $s3Data, $language) {
		$pathinfo = pathinfo($s3Data['key']);

		$path = $pathinfo['dirname'];
		$ext = $pathinfo['extension'];
		$filename = $pathinfo['filename'];

		$newKey = "{$path}/{$language}/{$filename}.{$ext}";

		try {
			$privacy = (!empty($s3Data['privacy'])) ? $s3Data['privacy'] : StorageToolSettings::privacy();
			$storageTool->client()->copy($s3Data['key'], $newKey, $privacy, $s3Data['mime-type'], StorageToolSettings::cacheControl(), StorageToolSettings::expires());
			$url = $storageTool->client()->url($newKey);

			$s3Data['key'] = $newKey;
			$s3Data['url'] = $url;
		} catch (\Exception $ex) {
			Logger::error("Error copying image to new translation: ".$ex->getMessage(), [], __METHOD__, __LINE__);
		}

		return $s3Data;
	}

	public function fixWPMLDuplicateMetadata($data, $post_id) {
		if(!isset($data['s3'])) {
			$trid = apply_filters('wpml_element_trid', null, $post_id, 'post_attachment');
			if(empty($trid)) {
				return $data;
			}

			$translations = apply_filters('wpml_get_element_translations', null, $trid, 'post_attachment');
			if(empty($translations)) {
				return $data;
			}

			$currentLang = apply_filters('wpml_current_language', NULL);
			if(empty($currentLang)) {
				return $data;
			}

			foreach($translations as $language => $translation) {
				if($translation->original) {
					remove_filter('wp_get_attachment_metadata', [$this, 'fixWPMLDuplicateMetadata'], 1);

					$original_data = wp_get_attachment_metadata($translation->element_id);

					if(empty($data) || !is_array($data)) {
						$data = $original_data;
					}

					if(isset($original_data['s3'])) {
						/** @var StorageTool $storageTool */
						$storageTool = ToolsManager::instance()->tools['storage'];

						$data['s3'] = $this->copyS3DataToNewLanguage($storageTool, $original_data['s3'], $currentLang);
						$data['file'] = $data['s3']['key'];

						$sizes = arrayPath($original_data, 'sizes', []);
						if(!empty($sizes)) {
							foreach($sizes as $size => $sizeData) {
								if(!empty($sizeData['s3'])) {
									$sizes[$size]['s3'] = $this->copyS3DataToNewLanguage($storageTool, $sizeData['s3'], $currentLang);
								}
							}

							$data['sizes'] = $sizes;
						}

						$attachedFile = get_post_meta($post_id, '_wp_attached_file');
						if(filter_var($attachedFile, FILTER_VALIDATE_URL) === false) {
							update_post_meta($post_id, 'wp_attached_file', $data['file']);
						}

						remove_filter('wp_update_attachment_metadata', [$storageTool, 'handleUpdateAttachmentMetadata'], 1000);
						wp_update_attachment_metadata($post_id, $data);
						add_filter('wp_update_attachment_metadata', [$storageTool, 'handleUpdateAttachmentMetadata'], 1000, 2);
					}

					add_filter('wp_get_attachment_metadata', [$this, 'fixWPMLDuplicateMetadata'], 1, 2);
				}
			}
		}

		return $data;
	}

	//endregion
}
