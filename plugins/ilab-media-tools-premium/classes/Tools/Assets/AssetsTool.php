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

namespace MediaCloud\Plugin\Tools\Assets;

use MediaCloud\Plugin\Tasks\TaskManager;
use MediaCloud\Plugin\Tools\Assets\Tasks\UploadAssetsTask;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Tool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\NoticeManager;
use MediaCloud\Vendor\Illuminate\Support\Facades\Storage;
use MediaCloud\Vendor\Mimey\MimeTypes;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\json_response;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class AssetsTool extends Tool {
	/** @var AssetsToolSettings  */
	protected $settings;

	/** @var UploadAssetsTask */
	protected static $uploadCssTask = null;

	/** @var UploadAssetsTask */
	protected static $uploadJSTask = null;

	public function __construct($toolName, $toolInfo, $toolManager) {
		parent::__construct($toolName, $toolInfo, $toolManager);

		$this->settings = AssetsToolSettings::instance();

		$this->testForBadPlugins();
		$this->testForUselessPlugins();
	}

	public function setup() {
		parent::setup();

		if (get_transient('ilab-assets-display-cache-cleared')) {
			NoticeManager::instance()->displayAdminNotice('info', "Assets cache has been cleared.");
			delete_transient('ilab-assets-display-cache-cleared');
		}

		if (get_transient('ilab-assets-build-version-updated')) {
			NoticeManager::instance()->displayAdminNotice('info', "Assets build version has been update.");
			delete_transient('ilab-assets-build-version-updated');
		}


		if ($this->enabled()) {
			TaskManager::registerTask(UploadAssetsTask::class);

			if ($this->settings->gzip && !extension_loaded('zlib') && current_user_can('manage_options')) {
				NoticeManager::instance()->displayAdminNotice('warning', 'You have CSS/Javascript gzip compression enabled but the missing php zlib extension is not installed.  You will need to install this extension before using this feature.', true, 'mcloud-assets-zlib-missing', 1);
				$this->settings->gzip = false;
			}

			$elementorActive = (arrayPath($_REQUEST, 'action') == 'elementor');

			if (!is_admin() && !is_feed() && empty($elementorActive)) {
				add_action('wp_print_styles', function() {
					$this->processStyles();
				}, PHP_INT_MAX);

				add_action('wp_print_scripts', function() {
					$this->processScripts();
				}, PHP_INT_MAX);

				add_action('elementor/frontend/after_enqueue_styles', function() {
					$this->processStyles();
				}, PHP_INT_MAX);

				add_action('elementor/frontend/after_enqueue_global', function() {
					$this->processStyles();
				}, PHP_INT_MAX);

				add_action('elementor/frontend/after_enqueue_scripts', function() {
					$this->processScripts();
				}, PHP_INT_MAX);

				if ($this->settings->processContent) {
					add_action('wp', function() {
						ob_start(function($html) {
							return $this->processContent($html);
						});
					}, PHP_INT_MAX);
				}
			} else if (is_admin() && ($this->settings->storeCSS || $this->settings->storeJS)) {
				NoticeManager::instance()->displayAdminNotice('warning', 'You should not use the assets feature in PUSH mode except for in limited edge cases.  It will be removed in future versions of Media Cloud.  PULL mode is considerably faster and more reliable.  Please watch <a href="https://www.youtube.com/watch?v=lYHHjO27tng" target="_blank">this video</a> for more information.', true, 'mcloud-assets-no-push', 7);
			}
		}
	}

	public function enabled() {
		$enabled = parent::enabled();
		if (!$enabled) {
			return false;
		}

		if (empty($this->settings->storeCSS) && empty($this->settings->storeJS) && !empty($this->settings->cdnBase)) {
			return true;
		}

		$enabled = (!empty($this->settings->storeCSS) || !empty($this->settings->storeJS));
		if (!$enabled && current_user_can('manage_options')) {
			NoticeManager::instance()->displayAdminNotice('error', "You have assets enabled but have disabled uploading of CSS and Javascript.  If you want to serve assets using origin pull (the CDN will pull the assets from your server), please specify a CDN host in the settings.  Otherwise, turn on <strong>Store CSS Assets</strong> and/or <strong>Store Javascript Assets</strong>.");
		}

		return $enabled;
	}

	public function hasAdminBarMenu() {
		return $this->enabled() && is_admin();
	}

	public function hasSettings() {
		return true;
	}

	public function addAdminMenuBarItems($adminBar) {
		if (!is_admin()) {
			return;
		}

		$updateAction = 'update_assets_build_version';
		$updateNonce = wp_create_nonce($updateAction);

		$clearAction = 'reset_assets_cache';
		$clearNonce = wp_create_nonce($clearAction);

		$adminBar->add_node([
			'id' => 'media-cloud-assets-update-build',
			'parent' => 'media-cloud-admin-bar',
			'href' => '#',
			'title' => "Update Asset Build Version",
			'meta' => [
				'class' => 'ilab_ajax_admin_item',
				'html' => "<input type='hidden' name='nonce' value='{$updateNonce}'><input type='hidden' name='action' value='{$updateAction}'>"
			]
		]);

		$adminBar->add_node([
			'id' => 'media-cloud-assets-clear-cache',
			'parent' => 'media-cloud-admin-bar',
			'href' => '#',
			'title' => 'Clear Asset Cache',
			'meta' => [
				'class' => 'ilab_ajax_admin_item',
				'html' => "<input type='hidden' name='nonce' value='{$clearNonce}'><input type='hidden' name='action' value='{$clearAction}'>"
			]
		]);
	}

	//region Processing
	private function processStyles() {
		if ($this->settings->storeCSS) {
			$time = microtime(true);
			$this->handlePushAssets('css');
			$total = microtime(true) - $time;
			Logger::info("Asset styles processed in $total seconds.", [], __METHOD__, __LINE__);
		} else if(!empty($this->settings->cdnBase)) {
			$this->handlePullStyles();
		}
	}

	private function processScripts() {
		if ($this->settings->storeJS) {
			$time = microtime(true);
			$this->handlePushAssets('js');
			$total = microtime(true) - $time;
			Logger::info("Asset scripts processed in $total seconds.", [], __METHOD__, __LINE__);
		} else if(!empty($this->settings->cdnBase)) {
			$this->handlePullScripts();
		}
	}
	//endregion

	//region Asset Pull Handling
	private function handlePullStyles() {
		/** @var \WP_Styles $wp_styles */
		$wp_styles = wp_styles();
		$this->handlePullDeps($wp_styles);
	}

	private function handlePullScripts() {
		/** @var \WP_Scripts $wp_scripts */
		$wp_scripts = wp_scripts();
		$this->handlePullDeps($wp_scripts);
	}

	private function handlePullDeps(\WP_Dependencies $deps) {
		$baseUrl = home_url();
		$wpVer = get_bloginfo('version');

		/** @var \_WP_Dependency $registered */
		foreach($deps->registered as $key => $registered) {
			if (($registered->src === true) || empty($registered->src)) {
				continue;
			}

			$ver = $registered->ver;

			if (empty($ver)) {
				if (strpos($registered->src, '/wp-includes/') !== false) {
					$ver = $wpVer;
				} else {
					if ($this->settings->useVersion && !empty($this->settings->version)) {
						$ver = $this->settings->version;
					} else {
						$this->logWarning("CSS/JS file '{$registered->src}' registered without version, skipping.");
						continue;
					}
				}
			}

			$isUrl = filter_var($registered->src, FILTER_VALIDATE_URL);
			if (empty($isUrl)) {
				$src = parse_url(site_url($registered->src), PHP_URL_PATH);
			} else {
				if (strpos($registered->src, $baseUrl) !== 0) {
					continue;
				}

				$src = parse_url($registered->src, PHP_URL_PATH);
			}

			$deps->registered[$key]->ver = $ver;
			$deps->registered[$key]->src = $this->settings->cdnBase . '/' . ltrim($src, '/');
		}
	}

	//endregion

	//region Asset Upload Handling

	private function rootPath() {
		$home    = set_url_scheme( get_option( 'home' ), 'http' );
		$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl );
			$pos = strpos(ABSPATH, $wp_path_rel_to_home);
			if ($pos > 0) {
				$home_path = substr(ABSPATH, 0, $pos);
			}
		} else {
			$home_path = ABSPATH;
		}

		$rootPath = str_replace( '\\', '/', $home_path);

		// Fix for bedrock
		return str_replace('/wp/', '', $rootPath);
	}

	private function logWarning($message) {
		if (!$this->settings->logWarnings) {
			return;
		}

		Logger::warning($message, [], __METHOD__, __LINE__);
	}

	private function handlePushAssets($assetType) {
		$storageTool = ToolsManager::instance()->tools['storage'];

		if($assetType == 'js') {
			$processed = get_option('mcloud-assets-scripts', []);

			/** @var \WP_Dependencies $assets */
			$assets = wp_scripts();
		} else {
			$processed = get_option('mcloud-assets-styles', []);

			/** @var \WP_Dependencies $assets */
			$assets = wp_styles();
		}

		$baseUrls = [];
		$baseUrls[] = rtrim(home_url(), '/');
		$baseUrls[] = (strpos($baseUrls[0], 'https://') === 0) ? str_replace('https://', 'http://', $baseUrls[0]) : str_replace('http://', 'https://', $baseUrls[0]);

		if(is_multisite()) {
			$baseUrls[] = rtrim(network_home_url(), '/');
			$baseUrls[] = (strpos($baseUrls[2], 'https://') === 0) ? str_replace('https://', 'http://', $baseUrls[2]) : str_replace('http://', 'https://', $baseUrls[2]);
		}

		$basePath = rtrim($this->rootPath(), '/');
		$wpVer = get_bloginfo('version');

		$fileList = [];

		/** @var \_WP_Dependency $registered */
		foreach($assets->registered as $key => $registered) {
			if(strpos('style.css', $registered->src) !== false) {
				Logger::info("Skipping style.css", [], __METHOD__, __LINE__);
			}

			if(strpos('bundle.min.js', $registered->src) !== false) {
				Logger::info("Skipping style.css", [], __METHOD__, __LINE__);
			}

			Logger::info("Processing {$assetType} '{$registered->handle}'", [], __METHOD__, __LINE__);

			if(($registered->src === true) || ($registered->src === false)) {
				Logger::info("Skipping {$assetType} '{$registered->handle}' because no src is defined.", [], __METHOD__, __LINE__);
				continue;
			}

			$isWp = ((strpos($registered->src, '/wp-includes/') !== false) || (strpos($registered->src, '/wp-admin/') !== false));
			$ver = $registered->ver;

			if(empty($ver)) {
				if ($isWp) {
					$ver = $wpVer;
				} else {
					if($this->settings->useVersion && !empty($this->settings->version)) {
						$ver = $this->settings->version;
					} else {
						$this->logWarning("{$assetType} file '{$registered->src}' registered without version, skipping.");
						continue;
					}
				}
			}

			$hash = sha1($registered->src . '?' . $ver);
			if(!empty($processed[$hash])) {
				$entry = $processed[$hash];
				$key = null;

				if (isset($entry['key'])) {
					$key = $entry['key'];
				} else if (isset($entry['cssKey'])) {
					$key = $entry['cssKey'];
				} else if (isset($entry['jsKey'])) {
					$key = $entry['jsKey'];
				}

				if (!empty($key)) {
					if (!isset($entry['url'])) {
						Logger::warning("Missing url in entry", $entry, __METHOD__, __LINE__);
					} else {
						if(!empty($this->settings->cdnBase)) {
							$parsedUrl = parse_url($entry['url']);
							$path = ltrim($parsedUrl['path']);
							if (!empty($this->settings->stripBucket) && $storageTool->enabled() && str_starts_with($path, '/'.$storageTool->client()->bucket())) {
								$path = substr($path, strlen('/'.$storageTool->client()->bucket()));
							}

							$assets->registered[$key]->src = $this->settings->cdnBase . $path;
						} else {
							$assets->registered[$key]->src = $entry['url'];
						}

						$assets->registered[$key]->ver = $entry['ver'];
					}
				}



				continue;
			}

			if(!$this->settings->processLive) {
				continue;
			}

			$scheme = parse_url($registered->src, PHP_URL_SCHEME);
			$relativeFile = null;
			if(empty($scheme)) {
				if($isWp) {
					$srcPath = rtrim(ABSPATH, '/') . $registered->src;
				} else {
					$srcPath = $basePath . $registered->src;
				}
			} else {
				$found = false;
				if(strpos($registered->src, $baseUrls[0]) === 0) {
					$found = true;
					$relativeFile = str_replace($baseUrls[0], '', $registered->src);
					$srcPath = $basePath . $relativeFile;
				} else {
					foreach($baseUrls as $baseUrl) {
						if(strpos($registered->src, $baseUrl) === 0) {
							$found = true;
							$registered->src = str_replace($baseUrl, $baseUrls[0], $registered->src);
							$relativeFile = str_replace($baseUrls[0], '', $registered->src);
							$srcPath = $basePath . $relativeFile;
							break;
						}
					}
				}

				if(empty($found)) {
					Logger::warning("Unable to find base URL for {$assetType} '{$registered->handle}'", ['src' => $registered->src, 'baseUrls' => $baseUrls], __METHOD__, __LINE__);
					continue;
				}
			}

			if(!file_exists($srcPath)) {
				$queryString = strpos($srcPath, '?');
				if($queryString !== false) {
					$queryVars = substr($srcPath, $queryString + 1);
					if(preg_match_all('/(?:v[ersion]*)\s*\=\s*([aA-zZ0-9_-]+)/m', $queryVars, $versionMatches, PREG_SET_ORDER, 0)) {
						$ver = $versionMatches[0][1];
					}

					$srcPath = substr($srcPath, 0, $queryString);

					if(!file_exists($srcPath)) {
						Logger::info("File for {$assetType} '{$registered->handle}' with query string does not exist: $srcPath", [], __METHOD__, __LINE__);
						continue;
					}
				} else {
					if (!empty($relativeFile)) {
						$newSrcPath = rtrim(ABSPATH, '/') . $relativeFile;
						if (!file_exists($newSrcPath)) {
							Logger::info("File for {$assetType} '{$registered->handle}' does not exist: $newSrcPath $srcPath", [], __METHOD__, __LINE__);
							continue;
						}

						$srcPath = $newSrcPath;
					} else {
						Logger::info("File for {$assetType} '{$registered->handle}' does not exist: $srcPath", [], __METHOD__, __LINE__);
						continue;
					}
				}
			}

			$entry = ['key' => $key, 'src' => $srcPath, 'ver' => $ver];

			if($assetType == 'css') {
				$imgFiles = [];
				$this->parseCSSUrls($srcPath, $isWp, $ver, $imgFiles);
				if(!empty($imgFiles)) {
					$entry['img'] = $imgFiles;
				}
			}


			$fileList[$hash] = $entry;
		}

		if($this->settings->queuePush) {
			if(count($fileList) > 0) {
				/** @var UploadAssetsTask $task */
				$task = null;
				$transientName = 'mcloud-assets-' . $assetType . '-task-' . $this->settings->version;

				if ($assetType == 'js') {
					$task = static::$uploadJSTask;
				} else {
					$task = static::$uploadCssTask;
				}

				$transientExists = (get_transient($transientName) == $this->settings->version);
				if(!empty($task) || empty($transientExists)) {
					set_transient($transientName, $this->settings->version);

					if (empty($task)) {
						if($assetType == 'js') {
							static::$uploadJSTask = new UploadAssetsTask();
							$task = static::$uploadJSTask;
						} else {
							static::$uploadCssTask = new UploadAssetsTask();
							$task = static::$uploadCssTask;
						}
					}

					$taskOptions = [
						'type' => $assetType
					];

					if (is_multisite()) {
						$taskOptions['site'] = get_current_blog_id();
					}

					$task->prepare($taskOptions, $fileList);
					$task->wait();
				}
			}
		} else {
			if($assetType == 'js') {
				$this->uploadJSFiles($fileList);
			} else {
				$this->uploadCSSFiles($fileList);
			}

			foreach($fileList as $hash => $entry) {
				$key = $entry['key'];
				$ver = $entry['ver'];

				if(!empty($this->settings->cdnBase)) {
					$parsedUrl = parse_url($entry['url']);
					$assets->registered[$key]->src = $this->settings->cdnBase . ltrim($parsedUrl['path']);
				} else {
					$assets->registered[$key]->src = $entry['url'];
				}

				$assets->registered[$key]->ver = $ver;
			}

			$processed = array_merge($processed, $fileList);

			if($assetType == 'js') {
				update_option('mcloud-assets-scripts', $processed);
			} else {
				update_option('mcloud-assets-styles', $processed);
			}

			if(function_exists('rocket_clean_domain')) {
				rocket_clean_domain();
			}
		}
	}

	private function parseCSSUrls($cssFile, $isWp, $version, &$fileList) {
		$re = '/[:,\s]\s*url\s*\(\s*(?:\'(\S*?)\'|"(\S*?)"|((?:\\\\\s|\\\\\)|\\\\\"|\\\\\'|\S)*?))\s*\)/m';

		$basePath = pathinfo($cssFile, PATHINFO_DIRNAME);
		$css = file_get_contents($cssFile);
		preg_match_all($re, $css, $matches, PREG_SET_ORDER, 0);

		if (!empty($matches)) {
			foreach($matches as $match) {
				$imgFile = array_pop($match);
				if (empty($imgFile) || (strpos($imgFile, 'data:') === 0)) {
					continue;
				}

				if (!empty(parse_url($imgFile, PHP_URL_SCHEME))) {
					continue;
				}

				if (($queryParam = strpos($imgFile, '?')) !== false) {
					$imgFile = substr($imgFile, 0, $queryParam);
				}

				$hashIndex = strpos($imgFile, '#');
				if ($hashIndex !== false) {
					$imgFile = substr($imgFile, 0, $hashIndex);
				}

				$imgFilePath = realpath(trailingslashit($basePath).$imgFile);
				if (file_exists($imgFilePath)) {
					$hash = sha1($imgFilePath.'?'.$version);
					$fileList[$hash] = $imgFilePath;
				} else {
					$this->logWarning("CSS file '{$cssFile}' contains a missing image '{$imgFile}' => '{$imgFilePath}'");
				}
			}
		}
	}

	private function prepareGzippedFile($srcFile, $proposedContentType, &$contentType, &$contentEncoding, &$contentLength) {
		if (is_dir($srcFile)) {
			Logger::warning("srcFile is directory: $srcFile", [], __METHOD__, __LINE__);
			return $srcFile;
		}

		if (!function_exists('wp_tempnam')) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		}

		$tmpFile = wp_tempnam();

		$contents = file_get_contents($srcFile);
		$compressedContents = gzcompress($contents, 6, ZLIB_ENCODING_GZIP);

		if ($contents != $compressedContents) {
			file_put_contents($tmpFile, $compressedContents);

			$contentType = $proposedContentType;
			$contentEncoding = 'gzip';
			$contentLength = strlen($compressedContents);

			return $tmpFile;
		}

		return $srcFile;
	}

	public function uploadCSSItem($cssEntry) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$basePath = rtrim($this->rootPath(), '/');

		$key = str_replace($basePath, '', $cssEntry['src']);
		$key = ltrim($key, '/');

		$srcFile = $cssEntry['src'];

		$rtlSrcPath = trailingslashit(pathinfo($srcFile, PATHINFO_DIRNAME));
		$rtlSrcFileName = pathinfo($srcFile, PATHINFO_FILENAME);
		if (strpos($rtlSrcFileName, '.min') !== false) {
			$rtlSrcFileName = str_replace('.min', '', $rtlSrcFileName);
		}

		$rtlSrcExtension = pathinfo($srcFile, PATHINFO_EXTENSION);

		foreach([$rtlSrcExtension, 'min.'.$rtlSrcExtension] as $ext) {
			$rtlFile = $rtlSrcPath.$rtlSrcFileName.'-rtl.'.$ext;

			if (file_exists($rtlFile)) {
				$rtlkey = str_replace($basePath, '', $rtlFile);
				$rtlkey = ltrim($rtlkey, '/');

				$contentType = null;
				$contentLength = null;
				$contentEncoding = null;

				if ($this->settings->gzip && extension_loaded('zlib')) {
					Logger::info("Gzipping RTL: $rtlFile", [], __METHOD__, __LINE__);
					$rtlFile = $this->prepareGzippedFile($rtlFile, 'text/css', $contentType, $contentEncoding, $contentLength);
				}

				$storageTool->client()->upload($rtlkey, $rtlFile, 'public-read', $this->settings->cacheControl, $this->settings->expires, $contentType, $contentEncoding, $contentLength);
			} else {
				Logger::warning("Missing RTL CSS file: $rtlFile", [], __METHOD__, __LINE__);
			}
		}

		$contentType = null;
		$contentLength = null;
		$contentEncoding = null;

		if ($this->settings->gzip && extension_loaded('zlib')) {
			Logger::info("Gzipping CSS File: $rtlFile", [], __METHOD__, __LINE__);
			$srcFile = $this->prepareGzippedFile($srcFile, 'text/css', $contentType, $contentEncoding, $contentLength);
		}

		$url = $storageTool->client()->upload($key, $srcFile, 'public-read', $this->settings->cacheControl, $this->settings->expires, $contentType, $contentEncoding, $contentLength);
		if (!empty($url)) {
			$cssEntry['url'] = $url;

			if (!empty($cssEntry['img'])) {
				$mimey = new MimeTypes();

				foreach($cssEntry['img'] as $imageHash => $image) {
					$imgKey = str_replace($basePath, '', $image);
					$imgKey = ltrim($imgKey, '/');

					$contentType = null;
					$contentLength = null;
					$contentEncoding = null;

					$extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
					if (in_array($extension, ['ttf', 'otf', 'woff', 'woff2', 'eot', 'svg']) && $this->settings->gzip && extension_loaded('zlib')) {
						$mimeType = $mimey->getMimeType($extension);
						Logger::info("Gzipping CSS reference: $image", [], __METHOD__, __LINE__);
						$image = $this->prepareGzippedFile($image, $mimeType, $contentType, $contentEncoding, $contentLength);
					}

					$storageTool->client()->upload($imgKey, $image, 'public-read', $this->settings->cacheControl, $this->settings->expires, $contentType, $contentEncoding, $contentLength);
				}
			}
		}

		return $cssEntry;
	}

	private function uploadCSSFiles(&$files) {
		foreach($files as $hash => $cssEntry) {
			$files[$hash] = $this->uploadCSSItem($cssEntry);
		}
	}

	public function uploadJSItem($jsEntry) {
		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];

		$basePath = rtrim($this->rootPath(), '/');

		$key = str_replace($basePath, '', $jsEntry['src']);
		$key = ltrim($key, '/');

		$srcFile = $jsEntry['src'];
		if (is_dir($srcFile)) {
			Logger::warning("Invalid srcFile: $srcFile", $jsEntry, __METHOD__, __LINE__);
			return $jsEntry;
		}

		$contentType = null;
		$contentLength = null;
		$contentEncoding = null;
		if ($this->settings->gzip && extension_loaded('zlib')) {
			Logger::info("Gzipping JS File: $srcFile", [], __METHOD__, __LINE__);
			$srcFile = $this->prepareGzippedFile($srcFile, 'application/javascript', $contentType, $contentEncoding, $contentLength);
		}

		$url = $storageTool->client()->upload($key, $srcFile, 'public-read', $this->settings->cacheControl, $this->settings->expires, $contentType, $contentEncoding, $contentLength);
		if (!empty($url)) {
			$jsEntry['url'] = $url;
		}

		return $jsEntry;
	}

	private function uploadJSFiles(&$files) {
		foreach($files as $hash => $jsEntry) {
			$files[$hash] = $this->uploadJSItem($jsEntry);
		}
	}

	private function processContent($content) {

		$cssRe = '#\<link(?:.*)rel\s*=\s*["\']{1}stylesheet["\']{1}(?:[^>]+)\>#m';
		$url = untrailingslashit(get_site_url());

		preg_match_all($cssRe, $content, $matches, PREG_SET_ORDER, 0);
		foreach($matches as $match) {
			if (strpos($match[0], $url) !== false) {
				$fixed = str_replace($url, untrailingslashit($this->settings->cdnBase), $match[0]);
				$content = str_replace($match[0], $fixed, $content);
			}
		}

		$jsRe = '#\<script\s+(?:[^>]+)\>#m';

		preg_match_all($jsRe, $content, $matches, PREG_SET_ORDER, 0);
		foreach($matches as $match) {
			if (strpos($match[0], $url) !== false) {
				$fixed = str_replace($url, untrailingslashit($this->settings->cdnBase), $match[0]);
				$content = str_replace($match[0], $fixed, $content);
			}
		}

		$escapedUrl = str_replace('/', '\/', $url);
		$escapedCDNUrl = str_replace('/', '\/', untrailingslashit($this->settings->cdnBase));
		$content = str_replace("$escapedUrl\/wp-includes\/js\/wp-emoji-release.min.js", "$escapedCDNUrl/wp-includes/js/wp-emoji-release.min.js", $content);

		$content = str_replace("url('/wp-content/", "url('".untrailingslashit($this->settings->cdnBase)."/wp-content/", $content);
		return $content;
	}

	//endregion

	//region Actions

	public function resetCache($json = true) {
		delete_option('mcloud-assets-scripts');
		delete_option('mcloud-assets-styles');
		set_transient('ilab-assets-display-cache-cleared', true);

		if (!empty($json)) {
			json_response(['status' => 'ok', 'message' => 'Assets cache reset.']);
		}
	}

	public function updateBuildVersion($json = true) {
		$this->resetCache(false);

		$this->settings->version = time();
		set_transient('ilab-assets-build-version-updated', true);

		if (!empty($json)) {
			json_response(['status' => 'ok', 'message' => 'Assets build version updated.']);
		}
	}

	//endregion

}
