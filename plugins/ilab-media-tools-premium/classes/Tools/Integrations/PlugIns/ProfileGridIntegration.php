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

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class ProfileGridIntegration {
	private $currentUserId = null;

	public function __construct() {
		add_action('wp_ajax_pm_upload_cover_image', function() {
			add_filter('media-cloud/dynamic-images/skip-url-generation', function($skipGeneration) {
				return true;
			});
		}, 5);

		add_filter('pre_get_avatar', function($avatar, $id_or_email, $args) {
			$user_id = null;
			if (!is_numeric($id_or_email)) {
				if (is_string($id_or_email)) {
					$user = get_user_by('email', $id_or_email);
					if (!empty($user)) {
						$user_id = $user->ID;
					}
				} else if ($id_or_email instanceof \WP_User) {
					$user_id = $id_or_email->ID;
				}
			} else {
				$user_id = $id_or_email;
			}

			if (!empty($user_id)) {
				$this->currentUserId = $user_id;
				add_filter('media-cloud/dynamic-images/filter-parameters', [$this, 'profileCropRect'], 10, 4);
			}

			add_filter('media-cloud/dynamic-images/should-crop', [$this, 'shouldCrop']);
		}, 10, 3);

		add_action('pm_update_profile_image', function($user_id) {
			if (empty($_POST['attachment_id'])) {
				return;
			}

			$meta = wp_get_attachment_metadata($_POST['attachment_id']);
			if (!empty($meta)) {
				unset($meta['s3']);

				if (isset($meta['sizes'])) {
					$newSizes = [];
					foreach($meta['sizes'] as $size => $sizeData) {
						unset($sizeData['s3']);
						$newSizes[$size] = $sizeData;
					}

					$meta['sizes'] = $newSizes;
				}

				wp_update_attachment_metadata($_POST['attachment_id'], $meta);
			}

			if (!isset($_POST['x']) || !isset($_POST['y']) || !isset($_POST['w']) || !isset($_POST['h'])) {
				return;
			}

			$cx = intval($_POST['x']);
			$cy = intval($_POST['y']);
			$cw = intval($_POST['w']);
			$ch = intval($_POST['h']);

			update_user_meta($user_id, 'pm_profile_crop', [$cx, $cy, $cw, $ch]);
		});

		add_action('pm_update_cover_image', function($user_id) {
			if (empty($_POST['attachment_id'])) {
				return;
			}

			$meta = wp_get_attachment_metadata($_POST['attachment_id']);
			if (!empty($meta)) {
				unset($meta['s3']);
				unset($meta['sizes']);

				wp_update_attachment_metadata($_POST['attachment_id'], $meta);
			}

			if (!isset($_POST['x']) || !isset($_POST['y']) || !isset($_POST['w']) || !isset($_POST['h'])) {
				return;
			}

			$cx = intval($_POST['x']);
			$cy = intval($_POST['y']);
			$cw = intval($_POST['w']);
			$ch = intval($_POST['h']);

			update_user_meta($user_id, 'pm_cover_image_crop', [$cx, $cy, $cw, $ch]);
		});
	}

	public function profileCropRect($params, $size, $id, $meta) {
		remove_filter('media-cloud/dynamic-images/filter-parameters', [$this, 'profileCropRect'], 10);

		if (empty($params['fit']) || ($params['fit'] != 'crop')) {
			return $params;
		}

		if (!empty($this->currentUserId)) {
			$profileCropRect = get_user_meta($this->currentUserId, 'pm_profile_crop', true);
			if (!empty($profileCropRect)) {
				$params['rect'] = implode(',', $profileCropRect);
			}
		}

		return $params;
	}

	public function shouldCrop($shouldCrop) {
		remove_filter('media-cloud/dynamic-images/should-crop', [$this, 'shouldCrop']);

		return true;
	}
}


