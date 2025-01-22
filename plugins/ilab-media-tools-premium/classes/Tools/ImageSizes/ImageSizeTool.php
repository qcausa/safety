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

namespace MediaCloud\Plugin\Tools\ImageSizes;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\ImageSizes\Tasks\UpdateImagePrivacyTask;
use MediaCloud\Plugin\Tools\Tool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\NoticeManager;
use MediaCloud\Plugin\Utilities\Tracker;
use MediaCloud\Plugin\Utilities\View;
use function MediaCloud\Plugin\Utilities\gen_uuid;
use function MediaCloud\Plugin\Utilities\json_response;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class ImageSizeTool
 *
 * Tool for managing image sizes
 */
class ImageSizeTool extends Tool {
	private $customSizes = [];

	public function __construct( $toolName, $toolInfo, $toolManager ) {
		parent::__construct( $toolName, $toolInfo, $toolManager );

		$this->customSizes = get_option('ilab-custom-image-sizes', []);

		foreach($this->customSizes as $key => $size) {
			add_image_size($key, $size['width'], $size['height'], $size['crop']);
		}

		if (count($this->customSizes) > 0) {
			add_filter('image_size_names_choose', function ($sizes) {
				foreach ($this->customSizes as $key => $size) {
					$sizes[$key] = ucwords(str_replace('_', ' ', str_replace('-', ' ', $key)));;
				}

				return $sizes;
			});
		}

		add_action('wp_ajax_ilab_new_image_size_page', [$this, 'displayAddImageSizePage']);
		add_action('wp_ajax_ilab_update_image_size', [$this, 'updateImageSize']);
		add_action('wp_ajax_ilab_update_image_privacy', [$this, 'updateImagePrivacy']);
		add_action('wp_ajax_ilab_delete_image_size', [$this, 'deleteImageSize']);
		add_action('admin_enqueue_scripts', function(){
			wp_enqueue_media();

			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_script ( 'ilab-modal-js', ILAB_PUB_JS_URL. '/ilab-modal.js', ['jquery'], MEDIA_CLOUD_VERSION, true );
			wp_enqueue_script ( 'ilab-media-tools-js', ILAB_PUB_JS_URL. '/ilab-media-tools.js', ['jquery'], MEDIA_CLOUD_VERSION, true );
			wp_enqueue_script ( 'ilab-image-sizes-js', ILAB_PUB_JS_URL. '/ilab-image-sizes.js', ['jquery'], MEDIA_CLOUD_VERSION, true );
		});

		add_action('admin_init', function() {
			if (!empty($_POST['nonce']) && !wp_doing_ajax()) {
				$this->processNewImageSize();
			}
		});
	}

	public function registerMenu($top_menu_slug, $networkMode = false, $networkAdminMenu = false, $tool_menu_slug = null) {
		parent::registerMenu($top_menu_slug, $networkMode, $networkAdminMenu);

		if (!$networkAdminMenu) {
			ToolsManager::instance()->insertToolSeparator();
			ToolsManager::instance()->addMultisiteTool($this);
			$this->options_page = 'media-tools-image-sizes';
			add_submenu_page(!empty($tool_menu_slug) ? $tool_menu_slug : $top_menu_slug, 'Media Cloud Image Size Manager', 'Image Sizes', 'manage_options', 'media-tools-image-sizes', [
				$this,
				'renderImageSizes'
			]);
		}
	}

	public function enabled() {
		return true;
	}

	public function setup() {
		TaskManager::registerTask(UpdateImagePrivacyTask::class);

		parent::setup();
	}

	public function renderImageSizes() {
		global $_wp_additional_image_sizes;

		$themeSizes = [];
		$wpSizes = [];
		$sizes = [];

		$get_intermediate_image_sizes = get_intermediate_image_sizes();

		$preflightSizes = get_option('preflight-image-sizes', []);

		// Create the full array with sizes and crop info
		foreach($get_intermediate_image_sizes as $_size) {
			if(in_array($_size, ['thumbnail', 'medium', 'medium_large', 'large'])) {
				$crop = get_option($_size.'_crop');
				$wpSizes[] = [
					'type' => 'WordPress',
					'size' => $_size,
					'title' => ucwords(preg_replace('/[-_]/',' ', $_size)),
					'width' => get_option($_size.'_size_w'),
					'height' => get_option($_size.'_size_h'),
					'crop' => !empty($crop),
					'privacy' => ImageSizePrivacy::privacyForSize($_size),
					'x-axis' => (!empty($crop) && is_array($crop) && (count($crop) == 2)) ? $crop[0] : null,
					'y-axis' => (!empty($crop) && is_array($crop) && (count($crop) == 2)) ? $crop[1] : null,
				];
			} else if (isset($this->customSizes[$_size])) {
				$crop = $this->customSizes[$_size]['crop'];
				$sizes[] = [
					'type' => 'Custom',
					'size' => $_size,
					'title' => ucwords(preg_replace('/[-_]/',' ', $_size)),
					'width' => $this->customSizes[$_size]['width'],
					'height' => $this->customSizes[$_size]['height'],
					'crop' => !empty($crop),
					'privacy' => ImageSizePrivacy::privacyForSize($_size),
					'x-axis' => (!empty($crop) && is_array($crop) && (count($crop) == 2)) ? $crop[0] : null,
					'y-axis' => (!empty($crop) && is_array($crop) && (count($crop) == 2)) ? $crop[1] : null,
				];
			} else if(isset($_wp_additional_image_sizes[$_size])) {
				$crop = $_wp_additional_image_sizes[$_size]['crop'];
				$themeSizes[] = [
					'type' => isset($preflightSizes[$_size]) ? 'Preflight' : 'Theme',
					'size' => $_size,
					'title' => ucwords(preg_replace('/[-_]/',' ', $_size)),
					'width' => $_wp_additional_image_sizes[$_size]['width'],
					'height' => $_wp_additional_image_sizes[$_size]['height'],
					'crop' => !empty($crop),
					'privacy' => ImageSizePrivacy::privacyForSize($_size),
					'x-axis' => (!empty($crop) && is_array($crop) && (count($crop) == 2)) ? $crop[0] : null,
					'y-axis' => (!empty($crop) && is_array($crop) && (count($crop) == 2)) ? $crop[1] : null,
				];
			}
		}

		$hasDynamic = ToolsManager::instance()->toolEnabled('imgix');

		Tracker::trackView("Image Sizes", "/image-sizes");

		echo View::render_view('image-sizes/image-sizes', [
			'title' => 'Image Size Manager',
			'hasDynamic' => $hasDynamic,
			'sizes' => $sizes,
			'wpSizes' => array_merge($wpSizes, $themeSizes)
		]);
	}

	public function displayAddImageSizePage() {
		Tracker::trackView("Image Sizes - Add New Size", "/image-sizes/new");

		echo View::render_view( 'image-sizes/new-image-size', ['modal_id'=>gen_uuid(8)]);
	}

	protected function processNewImageSize() {
		$nonce = (!empty($_POST['nonce'])) ? $_POST['nonce'] : null;

		if (empty($nonce)) {
			return;
		}

		if (!wp_verify_nonce($nonce, 'add-image-size')) {
			return;
		}

		$name = (!empty($_POST['name'])) ? $_POST['name'] : null;
		if (!empty($name)) {
			$name = sanitize_title($name);
		}

		$width = (!empty($_POST['width'])) ? intval($_POST['width']) : 0;
		$height = (!empty($_POST['height'])) ? intval($_POST['height']) : 0;

		$crop = (!empty($_POST['_crop'])) ? true : false;
		$cropX = (!empty($_POST['x-axis'])) ? $_POST['x-axis'] : null;
		$cropY = (!empty($_POST['y-axis'])) ? $_POST['y-axis'] : null;
		$privacy = (!empty($_POST['privacy'])) ? $_POST['privacy'] : 'inherit';
		$privacy = empty($privacy) ? 'inherit' : $privacy;

		if (empty($name) || (($width == 0) && ($height == 0))) {
			NoticeManager::instance()->displayAdminNotice('error', 'Invalid attributes for new image size.');
			return;
		}

		if (!empty($crop) && (!empty($cropX)) && !empty($cropY)) {
			$crop = [$cropX, $cropY];
		}

		$this->customSizes[$name] = [
			'width' => $width,
			'height' => $height,
			'crop' => $crop
		];

		update_option('ilab-custom-image-sizes', $this->customSizes);

		ImageSizePrivacy::updatePrivacyForSize($name, $privacy);

		Tracker::trackView("Image Sizes - Create New Size", "/image-sizes/create");

		wp_redirect(admin_url('admin.php?page=media-tools-image-sizes'));
	}

	public function updateImagePrivacy() {
		$nonce = (!empty($_POST['nonce'])) ? $_POST['nonce'] : null;

		if (empty($nonce)) {
			json_response(['status' => 'error', 'error' => 'Missing nonce.']);
		}

		if (!wp_verify_nonce($nonce, 'custom-size')) {
			json_response(['status' => 'error', 'error' => 'Invalid nonce.']);
		}

		if (empty($_POST['size']) || empty($_POST['privacy'])) {
			json_response(['status' => 'error', 'error' => 'Missing required parameter.']);
		}

		$size = $_POST['size'];
		$privacy = $_POST['privacy'];

		ImageSizePrivacy::updatePrivacyForSize($size, $privacy);

		Tracker::trackView("Image Sizes - Update Privacy", "/image-sizes/update-privacy");

		json_response(['status' => 'ok']);
	}

	public function updateImageSize() {
		$nonce = (!empty($_POST['nonce'])) ? $_POST['nonce'] : null;

		if (empty($nonce)) {
			json_response(['status' => 'error', 'error' => 'Missing nonce.']);
		}

		if (!wp_verify_nonce($nonce, 'custom-size')) {
			json_response(['status' => 'error', 'error' => 'Invalid nonce.']);
		}

		if (empty($_POST['size'])) {
			json_response(['status' => 'error', 'error' => 'Missing size.']);
		}

		$size = $_POST['size'];

		if (!isset($this->customSizes[$size])) {
			json_response(['status' => 'error', 'error' => 'Invalid size.']);
		}

		$width = (!empty($_POST['width'])) ? intval($_POST['width']) : 0;
		$height = (!empty($_POST['height'])) ? intval($_POST['height']) : 0;

		$crop = ($_POST['crop'] == "true");
		$cropX = (!empty($_POST['xAxis'])) ? $_POST['xAxis'] : null;
		$cropY = (!empty($_POST['yAxis'])) ? $_POST['yAxis'] : null;
		$privacy = (!empty($_POST['privacy'])) ? $_POST['privacy'] : null;

		if (!empty($crop) && (!empty($cropX)) && !empty($cropY)) {
			$crop = [$cropX, $cropY];
		}

		$this->customSizes[$size] = [
			'width' => $width,
			'height' => $height,
			'crop' => $crop
		];

		update_option('ilab-custom-image-sizes', $this->customSizes);

		ImageSizePrivacy::updatePrivacyForSize($size, $privacy);

		Tracker::trackView("Image Sizes - Update Size", "/image-sizes/update");

		json_response(['status' => 'ok', 'size' => $this->customSizes[$size]]);
	}

	public function deleteImageSize() {
		$nonce = (!empty($_POST['nonce'])) ? $_POST['nonce'] : null;

		if (empty($nonce)) {
			json_response(['status' => 'error', 'error' => 'Missing nonce.']);
		}

		if (!wp_verify_nonce($nonce, 'custom-size')) {
			json_response(['status' => 'error', 'error' => 'Invalid nonce.']);
		}

		if (empty($_POST['size'])) {
			json_response(['status' => 'error', 'error' => 'Missing size.']);
		}

		$size = $_POST['size'];

		if (!isset($this->customSizes[$size])) {
			json_response(['status' => 'error', 'error' => 'Invalid size.']);
		}

		unset($this->customSizes[$size]);

		update_option('ilab-custom-image-sizes', $this->customSizes);

		Tracker::trackView("Image Sizes - Delete Size", "/image-sizes/delete");

		json_response(['status' => 'ok']);
	}
}