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

namespace MediaCloud\Plugin\Tools\Browser;

use MediaCloud\Plugin\Tools\Storage\StorageFile;
use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Browser\Tasks\ImportFromStorageTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Tool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Tracker;
use MediaCloud\Plugin\Utilities\View;
use function MediaCloud\Plugin\Utilities\json_response;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class ImageSizeTool
 *
 * Tool for managing image sizes
 */
class BrowserTool extends Tool {
	/** @var BrowserToolSettings */
	private $settings;

	public function __construct( $toolName, $toolInfo, $toolManager ) {
		parent::__construct( $toolName, $toolInfo, $toolManager );

		$this->settings = BrowserToolSettings::instance();

		add_action('wp_ajax_ilab_browser_select_directory', [$this, 'selectDirectory']);
		add_action('wp_ajax_ilab_browser_browser_select_directory', [$this, 'browserSelectDirectory']);
		add_action('wp_ajax_ilab_browser_create_directory', [$this, 'createDirectory']);
		add_action('wp_ajax_ilab_browser_delete', [$this, 'deleteItems']);
		add_action('wp_ajax_ilab_browser_file_list', [$this, 'listFiles']);
		add_action('wp_ajax_ilab_browser_file_list_chunk', [$this, 'listFilesChunk']);
		add_action('wp_ajax_ilab_browser_import_file', [$this, 'importFile']);
		add_action('wp_ajax_ilab_browser_track', [$this, 'trackAction']);

		add_action('admin_menu', function() {
			if ($this->enabled()) {
				add_media_page('Media Cloud Storage Browser', 'Storage Browser', 'manage_options', 'media-tools-storage-browser', [
					$this,
					'renderBrowser'
				]);
			}
		});

	}

	public function registerMenu($top_menu_slug, $networkMode = false, $networkAdminMenu = false, $tool_menu_slug = null) {
		parent::registerMenu($top_menu_slug);

		if ($this->enabled()) {
			ToolsManager::instance()->addMultisiteTool($this);

			if (is_multisite() && !empty($this->settings->multisiteHide)) {
				return;
			}

			ToolsManager::instance()->insertToolSeparator();
			$this->options_page = 'media-tools-storage-browser';
			add_submenu_page(!empty($tool_menu_slug) ? $tool_menu_slug : $top_menu_slug, 'Media Cloud Storage Browser', 'Storage Browser', 'manage_options', 'media-tools-storage-browser', [
				$this,
				'renderBrowser'
			]);


		}
	}

	public function enabled() {
		if (media_cloud_licensing()->is_plan__premium_only('pro')) {
			$enabled = ToolsManager::instance()->toolEnabled('storage');

			if ($enabled && is_multisite() && !is_network_admin()) {
				$enabled = empty($this->settings->multisiteHide);
			}

			return $enabled;
		} else {
			return false;
		}
	}

	public function setup() {
		if ($this->enabled()) {
			if (media_cloud_licensing()->is_plan__premium_only('pro')) {
				TaskManager::registerTask(ImportFromStorageTask::class);
			}
		}
	}

	public function renderBrowser() {
		$currentPath = (empty($_REQUEST['path'])) ? '' : $_REQUEST['path'];

		if (is_multisite() && !empty($this->settings->multisiteRoot)) {
			if (strpos($currentPath, $this->settings->multisiteRoot) === false) {
				$currentPath = $this->settings->multisiteRoot;
			}
		}

		if ($currentPath == '/') {
			$currentPath = '';
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
//		$files = $storageTool->client()->dir($currentPath)['files'];

		$directUploads = ToolsManager::instance()->toolEnabled('media-upload');

//		if (!empty($currentPath)) {
//			$pathParts = explode('/', $currentPath);
//			array_pop($pathParts);
//			array_pop($pathParts);
//			$parentPath = implode('/', $pathParts);
//			if (!empty($parentPath)) {
//				$parentPath .= '/';
//			}
//
//			$parentDirectory = new StorageFile('DIR', $parentPath, '..');
//			array_unshift($files, $parentDirectory);
//		}

		$mtypes = array_values(get_allowed_mime_types(get_current_user_id()));
		$mtypes[] = 'image/psd';

		Tracker::trackView('Storage Browser', '/browser');

		echo View::render_view('storage/storage-browser', [
			'title' => 'Cloud Storage Browser',
			'bucketName' => $storageTool->client()->bucket(),
			"path" => $currentPath,
			"directUploads" => $directUploads,
//			'files' => $files,
			'allowUploads' => $this->settings->multisiteAllowUploads,
			'allowDeleting' => $this->settings->multisiteAllowDeleting,
			'allowedMimes' => $mtypes
		]);

	}

	public function selectDirectory() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		$currentPath = (empty($_POST['key'])) ? '' : $_POST['key'];
		$this->renderDirectory($currentPath);
	}

	public function browserSelectDirectory() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		$currentPath = (empty($_POST['key'])) ? '' : $_POST['key'];
		$this->renderDirectory($currentPath, true, true, false, false);
	}

	protected function renderDirectory($currentPath, $tableOnly = false, $hideCheckBoxes = false, $hideActions = false, $allowDeleting = true) {
		if (is_multisite() && !empty($this->settings->multisiteRoot)) {
			if (strpos($currentPath, $this->settings->multisiteRoot) === false) {
				$currentPath = $this->settings->multisiteRoot;
			}
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		$files = $storageTool->client()->dir($currentPath)['files'];

		if (!empty($currentPath)) {
			$pathParts = explode('/', $currentPath);
			array_pop($pathParts);
			array_pop($pathParts);
			$parentPath = implode('/', $pathParts);
			if (!empty($parentPath)) {
				$parentPath .= '/';
			}

			$parentDirectory = new StorageFile('DIR', $parentPath, '..');
			array_unshift($files, $parentDirectory);
		}

		$table = View::render_view('storage/browser-table', [
			'hideCheckBoxes' => $hideCheckBoxes,
			'hideActions' => $hideActions,
			'files' => $files,
			'allowUploads' => $this->settings->multisiteAllowUploads,
			'allowDeleting' => ($allowDeleting && $this->settings->multisiteAllowDeleting)
		]);

		$data = [
			'status' => 'ok',
			'table' => $table,
			'nextNonce' => wp_create_nonce('storage-browser')
		];

		if (empty($tableOnly)) {
			$header = View::render_view('storage/browser-header', [
				'bucketName' => $storageTool->client()->bucket(),
				'path' => $currentPath
			]);

			$data['header'] = $header;
		}

		json_response($data);
	}

	public function createDirectory() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		$currentPath = (empty($_POST['key'])) ? '' : $_POST['key'];
		if (!empty($currentPath)) {
			$currentPath = rtrim($currentPath, '/').'/';
		}

		$newDirectory = sanitize_title((empty($_POST['directory'])) ? '' : $_POST['directory']);

		Tracker::trackView('Storage Browser - Create Directory', '/browser/create');

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		if (!$storageTool->client()->createDirectory($currentPath.$newDirectory)) {
			json_response([
				'status' => 'error',
				'nextNonce' => wp_create_nonce('storage-browser')
			]);
		} else {
			$this->renderDirectory($currentPath);
		}
	}

	public function deleteItems() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		if (empty($_POST['keys']) || !is_array($_POST['keys'])) {
			json_response([
				'status' => 'error',
				'message' => 'Missing keys'
			]);
		}

		Tracker::trackView('Storage Browser - Delete', '/browser/delete');

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		foreach($_REQUEST['keys'] as $key) {
			if (is_multisite() && !empty($this->settings->multisiteRoot)) {
				if (strpos($key, $this->settings->multisiteRoot) === false) {
					continue;
				}
			}

			if (strpos(strrev($key), '/') === 0) {
				$storageTool->client()->deleteDirectory($key);
			} else {
				$storageTool->client()->delete($key);
			}
		}

		wp_send_json([
			'status' => 'ok'
		], 200);
	}

	public function listFiles() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		if (empty($_POST['keys']) || !is_array($_POST['keys'])) {
			json_response([
				'status' => 'error',
				'message' => 'Missing keys'
			]);
		}


		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$fileList = $storageTool->getFileList($_REQUEST['keys'],  (isset($_POST['skipThumbnails']) && ($_POST['skipThumbnails'] != "false")));

		json_response([
			'status' => 'ok',
			'files' => $fileList['files'],
			'nextNonce' => wp_create_nonce('storage-browser')
		]);
	}

	public function listFilesChunk() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		$next = isset($_REQUEST['next']) ? $_REQUEST['next'] : null;
		$path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '';


		if (is_multisite() && !empty($this->settings->multisiteRoot)) {
			if (strpos($path, $this->settings->multisiteRoot) === false) {
				$path = $this->settings->multisiteRoot;
			}
		}

		if ($path == '/') {
			$path = '';
		}

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$fileList = $storageTool->client()->dir($path, '/', 30, $next);

		json_response([
			'status' => 'ok',
			'path' => $path,
			'files' => $fileList['files'],
			'next' => $fileList['next'],
			'nextNonce' => wp_create_nonce('storage-browser')
		]);
	}

	public function importFile() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		$key = (empty($_POST['key'])) ? '' : $_POST['key'];
		if (is_multisite() && !empty($this->settings->multisiteRoot)) {
			if (strpos($key, $this->settings->multisiteRoot) === false) {
				json_response([
					'status' => 'error',
					'message' => 'Invalid path'
				]);
			}
		}

		if (empty($key)) {
			json_response([
				'status' => 'error',
				'message' => 'Missing key'
			]);
		}

		$thumbs = (isset($_POST['thumbs'])) ? $_POST['thumbs'] : [];
		$importOnly = (isset($_POST['importOnly']) && ($_POST['importOnly'] == 'true'));
		$preservePaths = (isset($_POST['preservePaths']) && ($_POST['preservePaths'] == 'true'));

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		$success = $storageTool->importFileFromStorage($key, $thumbs, $importOnly, $preservePaths);

		json_response([
			'status' => ($success) ? 'ok' : 'error',
			'nextNonce' => wp_create_nonce('storage-browser')
		]);
	}

	public function trackAction() {
		if (!check_ajax_referer('storage-browser', 'nonce')) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid nonce'
			]);
		}

		if (!isset($_POST['track'])) {
			json_response([
				'status' => 'error',
				'message' => 'Missing track variable'
			]);
		}

		$track = $_POST['track'];
		if (!in_array($track, ['upload', 'import'])) {
			json_response([
				'status' => 'error',
				'message' => 'Invalid track'
			]);
		}

		$title = ($track == 'upload') ? 'Storage Browser - Upload' : 'Storage Browser - Import';
		Tracker::trackView($title, "/browser/{$track}");

		json_response([
			'status' => 'ok'
		]);
	}



}
