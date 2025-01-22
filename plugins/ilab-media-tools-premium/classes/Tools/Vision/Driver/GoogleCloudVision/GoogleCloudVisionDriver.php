<?php
// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// Uses code from:
// Persist Admin Notices Dismissal
// by Agbonghama Collins and Andy Fragen
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\Vision\Driver\GoogleCloudVision;


use MediaCloud\Vendor\Google\Cloud\Storage\StorageClient;

use MediaCloud\Vendor\Google\Cloud\Storage\StorageObject;
use MediaCloud\Vendor\Google\Cloud\Vision\V1\AnnotateImageResponse;
use MediaCloud\Vendor\Google\Cloud\Vision\V1\FaceAnnotation;
use MediaCloud\Vendor\Google\Cloud\Vision\V1\Feature\Type;
use MediaCloud\Vendor\Google\Cloud\Vision\V1\ImageAnnotatorClient;
use MediaCloud\Vendor\Google\Cloud\Vision\V1\SafeSearchAnnotation;
use MediaCloud\Vendor\Google\Protobuf\Internal\RepeatedField;

use MediaCloud\Plugin\Tools\Storage\StorageToolSettings;
use MediaCloud\Plugin\Tools\Storage\StorageTool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\Environment;
use MediaCloud\Plugin\Utilities\Logging\ErrorCollector;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use MediaCloud\Plugin\Tools\Vision\VisionDriver;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined('ABSPATH')) { header('Location: /'); die; }

class GoogleCloudVisionDriver extends VisionDriver {
    /*** @var string */
    private $credentials = null;

    /*** @var string */
    private $bucket = null;

    /** @var null|string */
    private $enabledError = null;

    public function __construct() {
        parent::__construct();

        $this->bucket = Environment::Option('mcloud-storage-s3-bucket', [
            'ILAB_AWS_S3_BUCKET',
            'ILAB_CLOUD_BUCKET'
        ]);

        $credFile = Environment::Option(null, 'ILAB_CLOUD_GOOGLE_CREDENTIALS');
        if (!empty($credFile)) {
            if (file_exists($credFile)) {
                $this->credentials = json_decode(file_get_contents($credFile), true);
            } else {
                Logger::error("Credentials file '$credFile' could not be found.", [], __METHOD__, __LINE__);
            }
        }

        if (empty($this->credentials)) {
            $creds = Environment::Option('mcloud-storage-google-credentials');
            if (!empty($creds)) {
                $this->credentials = json_decode($creds, true);
            }
        }
    }

    /**
     * Insures that all the configuration settings are valid and that the vision api is enabled.
     * @return bool
     */
    public function enabled() {
        if (empty($this->credentials)) {
            $this->enabledError = "Missing credentials for Google Cloud Vision.";
            return false;
        }

        $client = null;
        if (!empty($this->credentials) && is_array($this->credentials)) {
            $client = new ImageAnnotatorClient([
                'credentials' => $this->credentials
            ]);
        }

        if(empty($client)) {
            $this->enabledError = "Invalid Google Cloud Vision credentials.";
            return false;
        }

        $this->enabledError = null;
        return $this->minimumOptionsEnabled();
    }

	public function minimumOptionsEnabled() {
		return ($this->settings->detectLabels || $this->settings->detectFaces || $this->settings->detectExplicit);
	}

    /**
     * If the driver isn't enabled, this returns the error message as to why
     * @return string|null
     */
    public function enabledError() {
        return $this->enabledError;
    }

    /**
     * Processes the image through the driver's vision API
     * @param $postID
     * @param $meta
     * @return array
     */
    public function processImage($postID, $meta) {
    	/** @var ImageAnnotatorClient $client */
        $client = $this->getClient();
        if (!$client) {
            return $meta;
        }

        $features = [];
        if ($this->settings->detectFaces) {
            $features[] = Type::FACE_DETECTION;
        }

        if ($this->settings->detectLabels) {
            $features[] = Type::LANDMARK_DETECTION;
            $features[] = Type::LABEL_DETECTION;
            $features[] = Type::LOGO_DETECTION;
        }

        if ($this->settings->detectExplicit) {
            $features[] = Type::SAFE_SEARCH_DETECTION;
        }

        /** @var StorageTool $storageTool */
        $storageTool = ToolsManager::instance()->tools['storage'];

        $urlOrResource = null;

        $provider = arrayPath($meta, 's3/provider', null);
        $key = arrayPath($meta, 's3/key', null);

        if (!empty($provider) && !empty($key) && ($provider != 'google') && (StorageToolSettings::driver() == $provider) && $storageTool->enabled()) {
            $urlOrResource = $storageTool->client()->presignedUrl($key);
        } else if (!empty($provider) && !empty($key) && ($provider == 'google') && (StorageToolSettings::driver() == $provider)) {
            /** @var StorageClient $storageClient */
            $storageClient = $storageTool->client()->client();
            /** @var StorageObject $urlOrResource */
            $urlOrResource = $storageClient->bucket($storageTool->client()->bucket())->object($key);
            if (!empty($urlOrResource)) {
            	$urlOrResource = $urlOrResource->gcsUri();/// signedUrl(120);
            }
        } else {
            $file = get_attached_file($postID);
            if (file_exists($file)) {
                $urlOrResource = fopen($file,'r');
            }
        }

        if (!$urlOrResource) {
            $urlOrResource = wp_get_attachment_url($postID);
        }

        if (!$urlOrResource) {
            return $meta;
        }


        try {
	        /** @var AnnotateImageResponse $result */
	        $result = $client->annotateImage($urlOrResource, $features);
	        if (!empty($result)) {
		        return $this->processResults($meta, $postID, $result);
	        }
        } catch (\Exception $exception) {
        	Logger::error($exception->getMessage(), [], __METHOD__, __LINE__);
        }

        return $meta;
    }

    /**
     * @param $meta
     * @param $postID
     * @param $results AnnotateImageResponse
     *
     * @return mixed
     */
    private function processResults($meta, $postID, $results) {
    	$tagsList = [];

        if (!empty($results->getFaceAnnotations())) {
            $width = (int)arrayPath($meta, 'width');
            $height = (int)arrayPath($meta, 'height');

            if (!empty($width) && !empty($height)) {
                $faces = [];
                /** @var FaceAnnotation $faceAnnotation */
	            foreach($results->getFaceAnnotations() as $faceAnnotation) {
	            	/** @var RepeatedField $vertices */
	            	$vertices = $faceAnnotation->getBoundingPoly()->getVertices();
	            	if ($vertices->count() >= 3) {
			            $left = floatval($vertices[0]->getX()) / floatval($width);
			            $top = floatval($vertices[0]->getX()) / floatval($height);
			            $fwidth = (floatval($vertices[2]->getX()) - floatval($vertices[0]->getX())) / floatval($width);
			            $fheight = (floatval($vertices[2]->getY()) - floatval($vertices[0]->getY())) / floatval($height);

			            $faces[] = [
				            'BoundingBox' => [
					            'Left' => $left,
					            'Top' => $top,
					            'Width' => $fwidth,
					            'Height' => $fheight
				            ]
			            ];
		            }
                }

                if (!empty($faces)) {
                    $meta['faces'] = $faces;
                }
            } else {
                Logger::warning("Meta does not include size information for face detection.", [], __METHOD__, __LINE__);
            }
        }

        if (!empty($results->getLabelAnnotations())) {
            $tags = $this->getTags($results->getLabelAnnotations(), ($this->settings->detectLabelsConfidence / 100.0));

            if (!empty($tags)) {
	            if (!isset($tagsList[$this->settings->detectLabelsTax])) {
		            $tagsList[$this->settings->detectLabelsTax] = [];
	            }

                $this->processTags($tags, $this->settings->detectLabelsTax, $postID,  $tagsList[$this->settings->detectLabelsTax]);
                Logger::info( 'Detect Labels', $tags, __METHOD__, __LINE__);
            } else {
                Logger::info( 'Detect Labels: None found.', [], __METHOD__, __LINE__);
            }
        }

        if (!empty($results->getLogoAnnotations())) {
            $tags = $this->getTags($results->getLogoAnnotations(), ($this->settings->detectLabelsConfidence / 100.0));

            if (!empty($tags)) {
	            if (!isset($tagsList[$this->settings->detectLabelsTax])) {
		            $tagsList[$this->settings->detectLabelsTax] = [];
	            }

                $this->processTags($tags, $this->settings->detectLabelsTax, $postID, $tagsList[$this->settings->detectLabelsTax]);
                Logger::info( 'Detect Logos', $tags, __METHOD__, __LINE__);
            } else {
                Logger::info( 'Detect Logos: None found.', [], __METHOD__, __LINE__);
            }
        }

        if (!empty($results->getLandmarkAnnotations())) {
            $tags = $this->getTags($results->getLandmarkAnnotations(), ($this->settings->detectLabelsConfidence / 100.0));

            if (!empty($tags)) {
	            if (!isset($tagsList[$this->settings->detectLabelsTax])) {
		            $tagsList[$this->settings->detectLabelsTax] = [];
	            }

	            $this->processTags($tags, $this->settings->detectLabelsTax, $postID, $tagsList[$this->settings->detectLabelsTax]);
                Logger::info( 'Detect Landmarks', $tags, __METHOD__, __LINE__);
            } else {
                Logger::info( 'Detect Landmarks: None found.', [], __METHOD__, __LINE__);
            }
        }

        /** @var SafeSearchAnnotation|null $safeSearchAnnotation */
	    $safeSearchAnnotation = $results->getSafeSearchAnnotation();
        if (!empty($safeSearchAnnotation)) {
            $tags = [];
            $keys = ['Adult', 'Spoof', 'Medical', 'Violence', 'Racy'];

            foreach($keys as $key) {
            	$getter = "get{$key}";
                $confidence = call_user_func([$safeSearchAnnotation, $getter]);
                if (($confidence / 5.) >= ($this->settings->detectExplicitConfidence / 100.)) {
                    $tags[] = ['tag' => $key];
	            }
            }

            if (!empty($tags)) {
	            if (!isset($tagsList[$this->settings->detectExplicitTax])) {
		            $tagsList[$this->settings->detectExplicitTax] = [];
	            }

                $this->processTags($tags, $this->settings->detectExplicitTax, $postID, $tagsList[$this->settings->detectExplicitTax]);
                Logger::info( 'Detect Moderation', $tags, __METHOD__, __LINE__);
            } else {
                Logger::info( 'Detect Moderation: None found.', [], __METHOD__, __LINE__);
            }
        }

        return $meta;
    }

	/**
	 * @param RepeatedField $annotations
	 * @param $confidence
	 *
	 * @return array
	 */
    private function getTags($annotations, $confidence) {
        $tags = [];
        foreach($annotations as $annotation) {
            if ($annotation->getScore() > $confidence) {
                if (!in_array($annotation->getDescription(), $this->settings->ignoredTags)) {
                    $tags[] = [
                        'tag' => $annotation->getDescription()
                    ];
                }
            }
        }

        return $tags;
    }


    //region Client Creation
    /**
     * @param ErrorCollector|null $errorCollector
     * @return ImageAnnotatorClient|null
     */
    protected function getClient($errorCollector = null) {
        if(!$this->enabled()) {
            if ($errorCollector) {
                $errorCollector->addError("Google configuration is incorrect or missing.");
            }

            return null;
        }

        $client = null;
        if (!empty($this->credentials) && is_array($this->credentials)) {
            $client = new ImageAnnotatorClient([
                'credentials' => $this->credentials
            ]);
        }

        if(!$client) {
            if ($errorCollector) {
                $errorCollector->addError("Google configuration is incorrect or missing.");
            }

            Logger::error('Could not create Google storage client.', [], __METHOD__, __LINE__);
        }

        return $client;
    }
    //endregion
}
