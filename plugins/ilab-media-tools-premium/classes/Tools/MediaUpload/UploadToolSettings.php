<?php


namespace MediaCloud\Plugin\Tools\MediaUpload;

use MediaCloud\Plugin\Tools\ToolSettings;

/**
 * Class UploadToolSettings
 * @package MediaCloud\Plugin\Tools\MediaUpload
 *
 * @property bool $integrationMode
 * @property int $maxUploads
 * @property bool $detectFaces
 * @property bool $uploadImages
 * @property bool $uploadAudio
 * @property bool $uploadVideo
 * @property bool $uploadDocuments
 * @property bool $useFFProbe
 * @property int $maxUploadSize
 * @property int $enabledOnFrontend
 * @property int $imageQuality
 * @property bool $generateThumbnails
 * @property bool $alwaysGenerateThumbnails
 */
class UploadToolSettings extends ToolSettings {
	protected $settingsMap = [
		"integrationMode" => ['mcloud-direct-uploads-integration', null, true],
		"maxUploads" => ['mcloud-direct-uploads-simultaneous-uploads', null, 4],
		"detectFaces" => ['mcloud-direct-uploads-detect-faces', null, false],
		"uploadImages" => ['mcloud-direct-uploads-upload-images', null, true],
		"uploadAudio" => ['mcloud-direct-uploads-upload-audio', null, true],
		"uploadVideo" => ['mcloud-direct-uploads-upload-videos', null, true],
		"uploadDocuments" => ['mcloud-direct-uploads-upload-documents', null, true],
		"useFFProbe" => ['mcloud-direct-uploads-use-ffprobe', null, false],
		"maxUploadSize" => ['mcloud-direct-uploads-max-upload-size', null, 0],
		"enabledOnFrontend" => ['mcloud-direct-uploads-enable-frontend', null, false],
		"imageQuality" => ['mcloud-direct-uploads-image-quality', null, 85],
		"generateThumbnails" => ['mcloud-direct-uploads-generate-thumbnails', null, true],
		"alwaysGenerateThumbnails" => ['mcloud-direct-uploads-always-generate-thumbnails', null, false],
	];

}