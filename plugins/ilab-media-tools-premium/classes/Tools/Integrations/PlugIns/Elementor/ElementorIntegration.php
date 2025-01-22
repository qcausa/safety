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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Assets\AssetsTool;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\Tasks\MigrateElementorFormUploadsTask;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\Elementor\Tasks\UpdateElementorTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\Prefixer;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\arrayPathSet;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class ElementorIntegration {
	public function __construct() {
		if (is_admin()) {
			TaskManager::registerTask(UpdateElementorTask::class);
			TaskManager::registerTask(MigrateElementorFormUploadsTask::class);

			if (!empty(Environment::Option('mcloud-elementor-auto-update', null, false))) {
				add_action('media-cloud/storage/migration/complete', function() {
					$task = new UpdateElementorTask();
					$task->prepare();
					TaskManager::instance()->queueTask($task);
				});
			}

			if (!empty(Environment::Option('mcloud-elementor-auto-update', null, false))) {
				add_action('media-cloud/storage/import/complete', function() {
					$task = new UpdateElementorTask();
					$task->prepare();
					TaskManager::instance()->queueTask($task);
				});
			}

			add_action( 'wp_ajax_elementor_get_images_details', function() {
				if (ToolsManager::instance()->toolEnabled('storage')) {
					$this->getImageDetails();
				}
			}, 1, 0);
		}

		if (!empty(Environment::Option('mcloud-elementor-offload-forms', null, false))) {
			add_filter('elementor_pro/forms/upload_url', function($url, $file_name) {
				return static::handleFormUpload($url, $file_name);
			}, PHP_INT_MAX - 1, 2);
		}

		if (!empty(Environment::Option('mcloud-elementor-offload-forms', null, false))) {
			if (Environment::Option('mcloud-elementor-form-privacy', null, 'public-read') === 'private') {
				add_filter('rest_pre_echo_response', function($result, $server, $request) {
					return $this->handleRestResponse($result, $server, $request);
				}, PHP_INT_MAX, 3);
			}
		}

		static::registerWidgetDefFilters();

		add_filter('elementor/image_size/get_attachment_image_html', function($html, $settings, $image_size_key, $image_key) {
			$id = arrayPath($settings, "$image_key/id", null);
			if (!empty($id)) {
				$size = arrayPath($settings, "{$image_key}_size", null);
				if (!empty($size) && ($size === 'custom')) {
					$sizeInfo = arrayPath($settings, "{$image_key}_custom_dimension", null);
					if (!empty($sizeInfo)) {
						$sizeName = "custom_".$sizeInfo['width'].'x'.$sizeInfo['height'];
						$newUrl = ElementorUpdater::instance()->getCustomSize($id, $sizeName)[0];
						if (!empty($newUrl)) {
							$re = '/((?:http|https):\/\/(?:[^\'"]+))/m';
							preg_match($re, $html, $matches);

							if (count($matches) > 0) {
								return str_replace($matches[0], $newUrl, $html);
							}
						}
					}
				}
			}

			return $html;
		}, PHP_INT_MAX, 4);

		add_action('elementor/document/after_save', function($document, $data) {
			if (!empty(Environment::Option('mcloud-elementor-auto-update', null, false))) {
				$this->processElementorSave($document->get_main_id(), $data);
			}

			if (ToolsManager::instance()->toolEnabled('assets')) {
				if(!empty(Environment::Option('mcloud-elementor-update-build', null, false))) {
					/** @var AssetsTool $assetTool */
					$assetTool = ToolsManager::instance()->tools['assets'];
					$assetTool->updateBuildVersion(false);
				}
			}
		}, 10, 2);

		if (!empty(Environment::Option('mcloud-elementor-update-build', null, false))) {
			if (ToolsManager::instance()->toolEnabled('assets')) {
				add_action('elementor/core/files/clear_cache', function() {
						/** @var AssetsTool $assetTool */
						$assetTool = ToolsManager::instance()->tools['assets'];
						$assetTool->updateBuildVersion(false);
				}, 10);
			}
		}
	}

	private function processElementorSave($postId, $ogData) {
		global $wpdb;
		$results = $wpdb->get_results("select meta_id from {$wpdb->postmeta} where meta_key = '_elementor_data' and meta_value LIKE '[%' and post_id = {$postId}", ARRAY_A);
		if (empty($results)) {
			return;
		}

		ElementorUpdater::update(true, $postId, $results[0]['meta_id'], $ogData['elements'], null);
	}

	//region Dynamic Image Sizes
	private function getImageDetails() {
		$items = $_POST['items'];
		$urls  = [];

		foreach ( $items as $item ) {
			$urls[ $item['id'] ] = $this->getDetails($item['id'], $item['size'], $item['is_first_time']);
		}

		wp_send_json_success($urls);
	}

	private function getDetails($id, $size, $firstTime) {
		if ('true' === $firstTime) {
			$sizes = array_keys(ilab_get_image_sizes());
			$sizes[] = 'full';
		} else {
			$sizes = [];
		}

		$sizes[] = $size;
		$urls = [];
		foreach ( $sizes as $size ) {
			if ( 0 === strpos( $size, 'custom_' ) ) {
				$result = ElementorUpdater::instance()->getCustomSize($id, $size);
				if (!empty($result)) {
					$urls[$size] = $result[0];
				}
			} else {
				$urls[ $size ] = wp_get_attachment_image_src( $id, $size )[0];
			}
		}

		return $urls;
	}

	//endregion

	//region Widget Handlers
	protected static function registerWidgetDefFilters() {
		add_filter("media-cloud/elementor/def/all-defs", function($widgetInfo) {
			$elementNames = [];
			foreach($widgetInfo['elements'] as &$element) {
				$elementNames[] = $element['name'];
			}

			foreach($widgetInfo['elements'] as &$element) {
				if (strpos($element['name'], '_background_') === 0) {
					$cleaned = ltrim($element['name'], '_');
					if (!in_array($cleaned, $elementNames)) {
						$newElement = $element;
						$newElement['name'] = $cleaned;
						$widgetInfo['elements'][] = $newElement;
					}
				}
			}

			return $widgetInfo;
		});

		add_filter("media-cloud/elementor/def/eael-flip-box", function($widgetInfo) {
			foreach($widgetInfo['elements'] as &$element) {
				if (isset($element['imageSize'])) {
					if (strpos($element['name'], 'eael_flipbox_image') !== 0) {
						unset($element['imageSize']);
						unset($element['imageCustomSizeName']);
					} else if(strpos($element['name'], 'eael_flipbox_image_back') === 0) {
						$element['imageSize'] = 'thumbnail_back_size';
						$element['imageCustomSizeName'] = 'thumbnail_back_custom_dimension';
					}
				}
			}

			return $widgetInfo;
		});
	}
	//endregion

	//region Form Uploads
	public static function handleFormUpload($url, $file_name, $canDelete = true) {
		if (parse_url(home_url(), PHP_URL_HOST) !== parse_url($url, PHP_URL_HOST)) {
			return $url;
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$wp_upload_dir = wp_upload_dir();
		$file = $wp_upload_dir['basedir'] . '/elementor/forms/' . $file_name;
		if (!file_exists($file)) {
			Logger::error("File does not exist for Elementor form upload $file", [], __METHOD__, __LINE__);
			return $url;
		}

		$key = 'elementor/forms/'.$file_name;

		try {
			$randomFilename = Environment::Option('mcloud-elementor-form-random-filename', null, false);
			$prefix = Environment::Option('mcloud-elementor-form-prefix', null, null);
			if (!empty($prefix)) {
				$type = mime_content_type($file);

				if (empty($type)) {
					$type = 'application/octet-stream';
				}

				Prefixer::setType($type);
				$uniquePath = Prefixer::Parse($prefix, null);
				if ($randomFilename) {
					$ext = pathinfo($file, PATHINFO_EXTENSION);
					$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
				} else {
					$key = ltrim(trailingslashit($uniquePath), '/').$file_name;
				}
			} else if ($randomFilename) {
				$uniquePath = Prefixer::genUUIDPath();
				$ext = pathinfo($file, PATHINFO_EXTENSION);
				$key = ltrim(trailingslashit($uniquePath), '/').Prefixer::genUUID().'.'.$ext;
			}

			$url = $storageTool->client()->upload($key, $file, Environment::Option('mcloud-elementor-form-privacy', null, 'public-read'));

			if ($canDelete && !empty(Environment::Option('mcloud-elementor-form-delete-uploads', null, false))) {
				@unlink($file);
			}
		} catch (\Exception $ex) {
			Logger::error("Error uploading $key to storage.  ".$ex->getMessage(), [], __METHOD__, __LINE__);
		}

		return $url;
	}

	private function handleRestResponse($result, $server, $request) {
		if(strpos($request->get_route(), '/elementor/v1/form-submissions') !== 0) {
			return $result;
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$uploadFields = [];
		$fields = arrayPath($result, 'data/form/fields', []);
		foreach($fields as $field) {
			if($field['type'] === 'upload') {
				$uploadFields[] = $field['id'];
			}
		}

		$values = arrayPath($result, 'data/values', []);
		for($i = 0; $i < count($values); $i++) {
			if(in_array($values[$i]['key'], $uploadFields)) {
				if (empty($values[$i]['value'])) {
					continue;
				}

				if (parse_url(home_url(), PHP_URL_HOST) === parse_url($values[$i]['value'], PHP_URL_HOST)) {
					continue;
				}

				$key = ltrim(parse_url($values[$i]['value'], PHP_URL_PATH), '/');
				arrayPathSet($result, "data/values/$i/value", $storageTool->client()->presignedUrl($key, 15*60));
			}
		}

		return $result;
	}
	//endregion
}
