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

namespace MediaCloud\Plugin\Tools\MediaUpload;

use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\Tool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Utilities\NoticeManager;
use MediaCloud\Plugin\Utilities\Prefixer;
use MediaCloud\Plugin\Utilities\VideoProbe;
use MediaCloud\Plugin\Utilities\View;
use function MediaCloud\Plugin\Utilities\arrayPath;
use function MediaCloud\Plugin\Utilities\gen_uuid;
use function MediaCloud\Plugin\Utilities\json_response;

if(!defined('ABSPATH')) {
	header('Location: /');
	die;
}

/**
 * Class ILabMediaUploadTool
 *
 * Video Tool.
 */
class UploadTool extends Tool {
	//region Class Variables
	/** @var StorageTool */
	private $storageTool;
	
	/** @var UploadToolSettings */
	private $settings = null;

	//endregion

    public function __construct($toolName, $toolInfo, $toolManager) {
        parent::__construct($toolName, $toolInfo, $toolManager);

        $this->testForBadPlugins();
        $this->testForUselessPlugins();

        $this->settings = UploadToolSettings::instance();

	    if (!function_exists('curl_multi_init') && current_user_can('manage_options')) {
		    NoticeManager::instance()->displayAdminNotice('error', "You are missing <a href='http://php.net/manual/en/curl.installation.php' target='_blank'>cURL support</a> which can cause issues with various Media Cloud features such as direct upload.  You should install cURL support before using this plugin.", true, 'media-cloud-curl-warning');
	    }
    }

    //region Tool Overrides
	public function setup() {
		parent::setup();

		if ($this->enabled()) {
			$this->storageTool = ToolsManager::instance()->tools['storage'];

            add_action('init', function() {
                if (current_user_can('upload_files')) {
	                $duUrl = admin_url('admin.php?page=media-cloud-settings&tab=media-upload');
	                if ($this->settings->useFFProbe) {
	                    if (!VideoProbe::instance()->enabled() && current_user_can('manage_options')) {
		                    NoticeManager::instance()->displayAdminNotice('warning', "You have <a href='{$duUrl}'>Use FFProbe</a> enabled in Direct Upload settings, but FFProbe is not installed or configured properly.  The error message is: ".VideoProbe::instance()->error(), true, 'media-cloud-direct-uploads-images-no-imgix-warning', 1);
                        }
                    }


	                $this->setupAdmin();
                    $this->setupAdminAjax();
                    $this->hookupUploadUI();

                    add_filter('plupload_default_settings', function($defaults) {
                        if($this->settings->uploadImages || $this->settings->uploadAudio || $this->settings->uploadVideo || $this->settings->uploadDocuments) {
	                        if($this->settings->maxUploadSize > 0) {
		                        $defaults['filters']['max_file_size'] = ($this->settings->maxUploadSize * 1024 * 1024) . 'b';
	                        }
                        }

                        return $defaults;
                    });
                }
            });
        }
	}

	public function enabled() {
		if(!parent::enabled()) {
			return false;
		}

		if (!function_exists('curl_multi_init')) {
			return false;
		}

		$client = StorageToolSettings::storageInstance();
		if (!$client->supportsDirectUploads() && current_user_can('manage_options')) {
			NoticeManager::instance()->displayAdminNotice('error', "Your cloud storage provider does not support direct uploads.", true, 'media-cloud-'.StorageToolSettings::driver().'-no-direct-uploads', 7);
		    return false;
        }

		return true;
	}

	public function hasSettings() {
		return true;
	}
	//endregion

	//region Admin Setup

    /**
     * Register Menus
     * @param $top_menu_slug
     */
    public function registerMenu($top_menu_slug, $networkMode = false, $networkAdminMenu = false, $tool_menu_slug = null) {
        parent::registerMenu($top_menu_slug);

        if($this->enabled() && ((!$networkMode && !$networkAdminMenu) || ($networkMode && !$networkAdminMenu)) && (!$this->settings->integrationMode)) {
            ToolsManager::instance()->insertToolSeparator();
            ToolsManager::instance()->addMultisiteTool($this);
            $this->options_page = 'media-cloud-upload-admin';
            add_submenu_page(!empty($tool_menu_slug) ? $tool_menu_slug : $top_menu_slug, 'Media Cloud Upload', 'Cloud Upload', 'upload_files', 'media-cloud-upload-admin', [
                $this,
                'renderSettings'
            ]);
        }
    }

	protected function prepareFaceDetection() {
		$imgixDetectFaces = apply_filters('media-cloud/imgix/detect-faces', false);
		$visionDetectFaces =  apply_filters('media-cloud/vision/detect-faces', false);

		if(current_user_can('upload_files') && $this->enabled()) {
			if (($this->settings->detectFaces) && (!$imgixDetectFaces && !$visionDetectFaces)) {
				?>
                <script>
                    var LocalVisionMediaURL = '<?php echo ILAB_PUB_URL.'/models/'; ?>';
                </script>
				<?php
			}
		}
	}

	protected function enqueueUploadScripts() {
		$imgixDetectFaces = apply_filters('media-cloud/imgix/detect-faces', false);
		$visionDetectFaces =  apply_filters('media-cloud/vision/detect-faces', false);
		if(current_user_can('upload_files') && $this->enabled()) {
			wp_enqueue_script('wp-util');

			if (($this->settings->detectFaces) && (!$imgixDetectFaces && !$visionDetectFaces)) {
				wp_enqueue_script('face-api-js', ILAB_PUB_JS_URL . '/face-api.js', ['jquery', 'wp-util'], MEDIA_CLOUD_VERSION, true);
				wp_enqueue_script('ilab-face-detect-js', ILAB_PUB_JS_URL . '/ilab-face-detect.js', ['face-api-js'], MEDIA_CLOUD_VERSION, true);
			}

			if ($this->settings->integrationMode) {
				if (!is_admin()) {
					wp_enqueue_media();
					wp_enqueue_script('underscore', "/wp-includes/js/underscore.min.js", [], '1.8.3', true);
					wp_enqueue_script('backbone', "/wp-includes/js/backboe.min.js", ['underscore', 'jquery'], '1.4.0', true);
					wp_enqueue_script('ilab-media-direct-upload-js', ILAB_PUB_JS_URL . '/ilab-media-direct-upload.js', ['backbone', 'jquery', 'wp-util'], MEDIA_CLOUD_VERSION, true);
				} else {
					wp_enqueue_script('ilab-media-direct-upload-js', ILAB_PUB_JS_URL . '/ilab-media-direct-upload.js', ['jquery', 'wp-util'], MEDIA_CLOUD_VERSION, true);
				}
			}

			wp_enqueue_script('ilab-media-upload-js', ILAB_PUB_JS_URL . '/ilab-media-upload.js', ['jquery', 'wp-util'], MEDIA_CLOUD_VERSION, true);

			$this->storageTool->client()->enqueueUploaderScripts();
		}
	}

	/**
	 * Setup upload UI
	 */
	public function setupAdmin() {
		add_action('admin_footer', function() {
			$this->prepareFaceDetection();
		});

		add_action('admin_enqueue_scripts', function() {
			$this->enqueueUploadScripts();
		});

		if (!is_admin() && !empty($this->settings->enabledOnFrontend)) {
			add_action('wp_head', function() {
				echo '<script type="text/javascript">var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
			});

			add_action('wp_footer', function() {
				$this->prepareFaceDetection();
			});

			add_action('wp_enqueue_scripts', function() {
				$this->enqueueUploadScripts();
			});
		}

		add_action('admin_menu', function() {
			if(current_user_can('upload_files')) {
				if($this->enabled()) {
					if ($this->settings->integrationMode) {
						remove_submenu_page('upload.php', 'media-new.php');

						add_media_page('Cloud Upload', 'Add New', 'upload_files', 'media-cloud-upload', [
							$this,
							'renderSettings'
						]);
					} else {
						add_media_page('Cloud Upload', 'Cloud Upload', 'upload_files', 'media-cloud-upload', [
							$this,
							'renderSettings'
						]);
					}
				}
			}
		});
	}

	/**
	 * Install Ajax Endpoints
	 */
	public function setupAdminAjax() {
		add_action('wp_ajax_ilab_upload_prepare', function() {
			$this->prepareUpload();
		});

		add_action('wp_ajax_ilab_upload_prepare_next', function() {
			$this->prepareNextUpload();
		});

		add_action('wp_ajax_ilab_upload_import_cloud_file', function() {
			$this->importUploadedFile();
		});

		add_action('wp_ajax_ilab_add_face_data', function() {
			$this->addFaceData();
		});

		add_action('wp_ajax_ilab_upload_process_batch', function() {
			$this->processUploadBatch();
		});

		add_action('wp_ajax_ilab_upload_attachment_info', function() {
			$postId = $_POST['postId'];

			json_response(wp_prepare_attachment_for_js($postId));
		});
	}

	public function hookupUploadUI() {
		add_action('admin_footer', function() {
			if(current_user_can('upload_files')) {
				$this->doRenderDirectUploadSettings();
				include ILAB_VIEW_DIR . '/upload/ilab-media-direct-upload.php';
			}
		});

		if ($this->settings->enabledOnFrontend) {
			add_action('wp_footer', function() {
				if(current_user_can('upload_files')) {
					$this->doRenderDirectUploadSettings();
					include ILAB_VIEW_DIR . '/upload/ilab-media-direct-upload.php';
				}
			});
		}

		add_action('customize_controls_enqueue_scripts', function() {
			if(current_user_can('upload_files')) {
				$this->doRenderDirectUploadSettings();
				include ILAB_VIEW_DIR . '/upload/ilab-media-direct-upload.php';
			}
		});
	}
	//endregion

	//region Ajax Endpoints
    private function processUploadBatch() {
	    if(!current_user_can('upload_files')) {
		    json_response(["status" => "error", "message" => "Current user can't upload."]);
	    }

	    if (!isset($_POST['batch'])) {
		    json_response(["status" => "error", "message" => "Invalid batch."]);
        }

	    if (!is_array($_POST['batch'])) {
		    json_response(["status" => "error", "message" => "Invalid batch."]);
        }

	    do_action('media-cloud/direct-uploads/process-batch', $_POST['batch']);

	    json_response(["status" => "ok"]);
    }

    private function addFaceData() {
	    if(!current_user_can('upload_files')) {
		    json_response(["status" => "error", "message" => "Current user can't upload."]);
	    }

	    if(empty($_POST['post_id'])) {
		    json_response(['status' => 'error', 'message' => 'Missing post.']);
	    }

	    if(empty($_POST['faces'])) {
		    json_response(['status' => 'error', 'message' => 'Missing faces data.']);
	    }

	    $postId = $_POST['post_id'];
	    $faces = arrayPath($_POST, 'faces', null);

	    /** @var StorageTool $storageTool */
	    $storageTool = ToolsManager::instance()->tools['storage'];
	    if (!$storageTool->enabled()) {
		    json_response(['status' => 'error', 'message' => 'Storage not enabled.']);
	    }

        if (!empty($faces)) {
            $meta = wp_get_attachment_metadata($postId);

            if (empty($meta)) {
	            json_response(['status' => 'error', 'message' => 'Invalid post ID.']);
            }

            $meta['faces'] = $faces;
            update_post_meta($postId, '_wp_attachment_metadata', $meta);

            json_response(['status' => 'success']);
	    } else {
		    json_response(['status' => 'error', 'message' => 'Faces is empty.']);
	    }
    }

	/**
	 * Called after a file has been uploaded and needs to be imported into the system.
	 */
	private function importUploadedFile() {
		if(!current_user_can('upload_files')) {
			json_response(["status" => "error", "message" => "Current user can't upload."]);
		}

		if(empty($_POST['key'])) {
			json_response(['status' => 'error', 'message' => 'Missing key.']);
		}

		$key = $_POST['key'];
		$faces = arrayPath($_POST, 'faces', null);

		/** @var StorageTool $storageTool */
		$storageTool = ToolsManager::instance()->tools['storage'];
		if (!$storageTool->enabled()) {
			json_response(['status' => 'error', 'message' => 'Storage not enabled.']);
        }

		$info = $this->storageTool->client()->info($key);

		if (empty($info->mimeType())) {
			json_response(['status' => 'error', 'message' => 'Unknown type.', 'type' => $info->mimeType()]);
			return;
		}

		$scrutinizedMimes = [
			'application/octet-stream',
			'application/binary',
			'unknown/unknown'
		];

		if (in_array($info->mimeType(), $scrutinizedMimes)) {
			$ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
			$allowedMimes = get_allowed_mime_types();

			$isAllowed = false;
			if (!isset($allowedMimes[$ext])) {
			    $allowedExts = array_keys($allowedMimes);
			    foreach($allowedExts as $allowedExt) {
			        if (strpos(strtolower($allowedExt), $ext) !== -1) {
			            $isAllowed = $allowedMimes[$allowedExt] === $info->mimeType();

			            if ($isAllowed) {
				            break;
			            }
			        }
			    }
		    } else {
				$isAllowed = $allowedMimes[$ext] === $info->mimeType();
			}

			if (!$isAllowed) {
				json_response(['status' => 'error', 'message' => 'Upload not allowed.', 'type' => $info->mimeType()]);
				return;
			}
		}

		$result = $storageTool->importAttachmentFromStorage($info);
		if($result) {
			$doUpdateMeta = false;
			$meta = wp_get_attachment_metadata($result['id']);

			if (isset($_POST['metadata'])) {
				$uploadMeta = arrayPath($_POST, 'metadata', []);

				if (!empty($uploadMeta)) {
					$thumbMeta = arrayPath($uploadMeta, 'thumbnail', null);
					if (!empty($thumbMeta)) {
						unset($uploadMeta['thumbnail']);

						$mime = strtolower($thumbMeta['mimeType']);
						$mimeParts = explode('/', $mime);

						if (in_array($mimeParts[1], ['jpeg', 'jpg', 'png'])) {
							$basename = basename($meta['file']);
							$filename = pathinfo($basename, PATHINFO_FILENAME);
							$ext = in_array($mimeParts[1], ['jpeg', 'jpg']) ? 'jpg' : 'png';
							$subdir = str_replace($basename, '', $meta['file']);

							$uploadDir = trailingslashit(WP_CONTENT_DIR).'uploads/'.trailingslashit($subdir);
							@mkdir($uploadDir, 0777, true);

							$filePath = $uploadDir.$filename.'.'.$ext;
							file_put_contents($filePath, base64_decode($thumbMeta['data']));

							$thumbId = wp_insert_attachment([
								'post_mime_type' => $thumbMeta['mimeType'],
								'post_title' => $filename. ' Thumb',
								'post_content' => '',
								'post_status' => 'inherit'
							]);

							require_once( ABSPATH . 'wp-admin/includes/image.php' );
							$thumbAttachmentMeta = wp_generate_attachment_metadata($thumbId, $filePath);
							wp_update_attachment_metadata($thumbId, $thumbAttachmentMeta);

							update_post_meta($result['id'], '_thumbnail_id', $thumbId);
						}
					}

					$meta = array_merge($meta, $uploadMeta);
					$doUpdateMeta = true;
				}
			}

			if (strpos($info->mimeType(), 'video') === 0) {
				if ($this->settings->useFFProbe && VideoProbe::instance()->enabled()) {
					$probeResult = VideoProbe::instance()->probe($info->signedUrl());
					if (!empty($result)) {
						$meta = array_merge($meta, $probeResult);

						$doUpdateMeta = true;
					}
				}
			} else if (!empty($faces)) {
				$meta['faces'] = $faces;

				$doUpdateMeta = true;
			}

			$otherSizes = arrayPath($_POST, 'other_sizes', []);
			if (isset($meta['s3']) && is_array($otherSizes) && (count($otherSizes) > 0)) {
				$doUpdateMeta = true;

				$s3 = $meta['s3'];

				$encKey = str_replace('%2F', '/', urlencode($s3['key']));
				$urlBase = trailingslashit(str_replace($encKey, '', $s3['url']));

				foreach($otherSizes as $otherSize) {
					$sizeS3 = $s3;
					$sizeS3['url'] = $urlBase.$otherSize['key'];
					$sizeS3['key'] = $otherSize['key'];
					$sizeS3['mime-type'] = $otherSize['mime'];

					$meta['sizes'][$otherSize['size']] = [
						'width' => $otherSize['width'],
						'height' => $otherSize['width'],
						'mime-type' => $otherSize['mime'],
						'file' => pathinfo($otherSize['key'], PATHINFO_BASENAME),
						's3' => $sizeS3
					];

					$this->storageTool->client()->insureACL($otherSize['key'], StorageToolSettings::privacy($otherSize['mime']));
				}
			}

			if ($doUpdateMeta) {
				add_filter('media-cloud/storage/ignore-metadata-update', [$this, 'ignoreMetadataUpdate'], 10, 2);
				wp_update_attachment_metadata($result['id'], $meta);
				remove_filter('media-cloud/storage/ignore-metadata-update', [$this, 'ignoreMetadataUpdate'], 10);
			}

			do_action('media-cloud/storage/direct-uploaded-attachment', $result['id'], $meta);

			json_response(['status' => 'success', 'data' => $result, 'attachment' => wp_prepare_attachment_for_js($result['id'])]);
		} else {
			json_response(['status' => 'error', 'message' => 'Error importing S3 file into WordPress.']);
		}
	}

	public function ignoreMetadataUpdate($shouldIgnore, $id) {
		return true;
	}

	/**
	 * Called when ready to upload to the storage service
	 */
	private function prepareUpload() {
		if(!current_user_can('upload_files')) {
			json_response(["status" => "error", "message" => "Current user can't upload."]);
		}

		if (!$this->storageTool->client()->enabled()) {
			json_response(["status" => "error", "message" => "Storage settings are invalid."]);
		}

		$filename = $_POST['filename'];
		$filename = sanitize_file_name($filename);
		$type = $_POST['type'];
		$prefix = (!isset($_POST['directory'])) ? null : $_POST['directory'];


		if (empty($filename) || empty($type)) {
			json_response(["status" => "error", "message" => "Invalid file name or missing type."]);
		}

		Prefixer::setType($type);
		Prefixer::nextVersion();

		if ($prefix === null) {
			$sitePrefix = '';

			if (is_multisite() && !is_main_site()) {
				$root = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads';
				$uploadDir = wp_get_upload_dir();
				$sitePrefix = ltrim(str_replace($root, '', $uploadDir['basedir']), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			}

			$prefix = $sitePrefix.StorageToolSettings::prefix(null);
		} else if (!empty($prefix)) {
			$prefix = trailingslashit($prefix);
		}

		$dynamicEnabled = apply_filters('media-cloud/dynamic-images/enabled', false);

		$parts = explode('/', $filename);
		$bucketFilename = array_pop($parts);

		if ($this->storageTool->client()->exists($prefix.$bucketFilename)) {
		    $bucketFilename = gen_uuid(8).'-'.$bucketFilename;
        }

		$uploadSizes = [
			'full' => []
		];

		if (!empty($this->settings->generateThumbnails) && (empty($dynamicEnabled) || !empty($this->settings->alwaysGenerateThumbnails))) {
			$sizes = ilab_get_image_sizes();
			foreach($sizes as $size => $sizeData) {
				$uploadSizes[$size] = [
				    'width' => (int)arrayPath($sizeData, 'width', 99999),
				    'height' => (int)arrayPath($sizeData, 'height', 99999),
				    'crop' => (bool)arrayPath($sizeData, 'crop', false),
                ];
            }
        }

		$uploadInfo = $this->storageTool->client()->uploadUrl($prefix.$bucketFilename, StorageToolSettings::privacy($type), $type,StorageToolSettings::cacheControl(), StorageToolSettings::expires());
		$res = $uploadInfo->toArray();
		$res['status'] = 'ready';
		$res['sizes'] = $uploadSizes;

		json_response($res);
	}

	/**
	 * Called when ready to upload to the storage service
	 */
	private function prepareNextUpload() {
		if(!current_user_can('upload_files')) {
			json_response(["status" => "error", "message" => "Current user can't upload."]);
		}

		if (!$this->storageTool->client()->enabled()) {
			json_response(["status" => "error", "message" => "Storage settings are invalid."]);
		}


		$key = $_POST['key'];
		$type = $_POST['type'];

		if (empty($key) || empty($type)) {
			json_response(["status" => "error", "message" => "Invalid file name or missing type."]);
		}

//		if ($this->storageTool->client()->exists($key)) {
//			$parts = explode('/', $key);
//			$bucketFilename = array_pop($parts);
//			$prefix = str_replace($bucketFilename, '', $key);
//
//			$key = trailingslashit($prefix).gen_uuid(8).'-'.$bucketFilename;
//		}

        Logger::startTiming("Create upload URL", [], __METHOD__, __LINE__);
		$uploadInfo = $this->storageTool->client()->uploadUrl($key, StorageToolSettings::privacy($type), $type, StorageToolSettings::cacheControl(), StorageToolSettings::expires());
		$res = $uploadInfo->toArray();
		Logger::endTiming("Create upload URL", [], __METHOD__, __LINE__);

		json_response($res);
	}

	protected function allMimeTypes() {
		$altFormatsEnabled = apply_filters('media-cloud/imgix/alternative-formats/enabled', false);
		$mtypes = array_values(get_allowed_mime_types(get_current_user_id()));

		if ($altFormatsEnabled) {
			$mtypes = array_merge($mtypes, StorageToolSettings::alternativeFormatTypes());
		}

		return $mtypes;
    }

	protected function allowedMimeTypes() {
	    $additionalIgnored = [];

	    if (!$this->settings->uploadImages || !StorageToolSettings::uploadImages()) {
	        $additionalIgnored[] = "image/*";
        }

		if (!$this->settings->uploadAudio || !StorageToolSettings::uploadAudio()) {
			$additionalIgnored[] = "audio/*";
		}

		if (!$this->settings->uploadVideo || !StorageToolSettings::uploadVideo()) {
			$additionalIgnored[] = "video/*";
		}

		if (!$this->settings->uploadDocuments || !StorageToolSettings::uploadDocuments()) {
			$additionalIgnored[] = "application/*";
			$additionalIgnored[] = "text/*";
		}


		$mtypes = $this->allMimeTypes();

		$allowed = [];
		foreach($mtypes as $mtype) {
		    if (StorageToolSettings::mimeTypeIsIgnored($mtype, $additionalIgnored)) {
		        continue;
            }

		    $allowed[] = $mtype;
        }

		return $allowed;
	}

	/**
	 * Render settings.
	 *
	 * @param bool $insertMode
	 */
	protected function doRenderSettings($insertMode) {
	    $mtypes = $this->allowedMimeTypes();

		$maxUploads = apply_filters('media-cloud/direct-uploads/max-uploads', $this->settings->maxUploads);

		$result = View::render_view('upload/ilab-media-upload.php', [
			'title' => $this->toolInfo['name'],
			'driver' => StorageToolSettings::driver(),
			'maxUploads' => $maxUploads,
			'group' => $this->options_group,
			'page' => $this->options_page,
			'insertMode' => $insertMode,
			'allowedMimes' => $mtypes,
			"sendDirectory" => true
		]);

		echo $result;
	}

	/**
	 * Render settings.
	 */
	protected function doRenderDirectUploadSettings() {
		$mtypes = $this->allowedMimeTypes();

		$imgixEnabled = apply_filters('media-cloud/dynamic-images/enabled', false);
		$maxUploads = apply_filters('media-cloud/direct-uploads/max-uploads', $this->settings->maxUploads);

		$generateThumbnails = !empty($this->settings->generateThumbnails);
		if ($generateThumbnails) {
			if (!empty($imgixEnabled) && empty($this->settings->alwaysGenerateThumbnails)) {
				$generateThumbnails = false;
			}
		}

		$result = View::render_view('upload/direct-upload-settings', [
			'driver' => StorageToolSettings::driver(),
			'maxUploads' => ($maxUploads > 0) ? $maxUploads : 4,
			'allowedMimes' => $mtypes,
			'wildcardUploads' => $this->storageTool->client()->supportsWildcardDirectUploads(),
            'imageQuality' => $this->settings->imageQuality,
            'generateThumbnails' => $generateThumbnails,
		]);

		echo $result;
	}

	/**
	 * Render settings.
	 */
	public function renderSettings() {
		$this->doRenderSettings(false);
	}

	/**
	 * Render settings.
	 */
	public function renderInsertSettings() {
		$this->doRenderSettings(true);
	}
	//endregion
}
