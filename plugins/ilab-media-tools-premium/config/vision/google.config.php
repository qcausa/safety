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

if (!defined('ABSPATH')) { header('Location: /'); die; }


return [
    "mcloud-vision-provider-settings" => [
        "title" => "Vision Provider Settings",
        "watch" => true,
        "dynamic" => true,
        "description" => "Supply any required credentials.  <strong>Note, these credentials are shared with Cloud Storage.</strong>  Changing credentials here will change your cloud storage credentials.  However, you can mix and match providers, meaning you can use Google Cloud Vision with Amazon S3 or DigitalOcean or any other cloud storage provider.  Amazon Rekognition, however, must be used with Amazon S3.",
        "options" => [
            "mcloud-storage-google-credentials" => [
                "title" => "Credentials",
                "description" => "To create the appropriate credentials, <a target='_blank' href='https://cloud.google.com/video-intelligence/docs/common/auth#set_up_a_service_account'>follow this tutorial</a>.  Once you've created the credentials and downloaded the resulting JSON, copy and paste the <strong>contents</strong> of the JSON file into this text field.",
                "display-order" => 4,
                "type" => "text-area"
            ],
        ]
    ],
    "ilab-vision-options" => [
        "title" => "Vision Options",
        "dynamic" => true,
        "options" => [
	        "mcloud-vision-detect-labels" => [
		        "title" => "Detect Labels",
		        "description" => "Detects instances of real-world labels within an image (JPEG or PNG) provided as input. This includes objects like flower, tree, and table; events like wedding, graduation, and birthday party; and concepts like landscape, evening, and nature.",
		        "display-order" => 0,
		        "type" => "checkbox",
		        "default" => false
	        ],
	        "mcloud-vision-detect-labels-tax" => [
		        "title" => "Detect Labels Taxonomy",
		        "description" => "The taxonomy to apply the detected labels to.",
		        "display-order" => 1,
		        "type" => "select",
		        "default" => "post_tag",
		        "options" => 'attachmentTaxonomies'
	        ],
	        "mcloud-vision-detect-labels-confidence" => [
		        "title" => "Detect Labels Confidence",
		        "description" => "The minimum confidence (0-100) required to apply the returned label as tags.  Default is 70.",
		        "display-order" => 2,
		        "type" => "number",
		        "default" => 70
	        ],
	        "mcloud-vision-detect-moderation-labels" => [
		        "title" => "Detect Moderation Labels",
		        "description" => "Detects explicit or suggestive adult content in a specified JPEG or PNG format image. Use this to moderate images depending on your requirements. For example, you might want to filter images that contain nudity, but not images containing suggestive content.",
		        "display-order" => 3,
		        "type" => "checkbox",
		        "default" => false
	        ],
	        "mcloud-vision-detect-moderation-labels-tax" => [
		        "title" => "Detect Moderation Labels Taxonomy",
		        "description" => "The taxonomy to apply the detected moderation labels to.",
		        "display-order" => 4,
		        "type" => "select",
		        "default" => "post_tag",
		        "options" => 'attachmentTaxonomies'
	        ],
	        "mcloud-vision-detect-moderation-labels-confidence" => [
		        "title" => "Detect Moderation Labels Confidence",
		        "description" => "The minimum confidence (0-100) required to apply the returned label as tags.  Default is 70.",
		        "display-order" => 5,
		        "type" => "number",
		        "default" => 70
	        ],
	        "mcloud-vision-ignored-tags" => [
		        "title" => "Ignored Tags",
		        "description" => "Add a comma separated list of tags to ignore when parsing the results from the Rekognition API.",
		        "display-order" => 9,
		        "type" => "text-area"
	        ],
        ]
    ]
];