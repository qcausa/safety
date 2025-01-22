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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress\Tasks;

use MediaCloud\Plugin\Tasks\Task;
use MediaCloud\Plugin\Utilities\Logging\Logger;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined('ABSPATH')) { header('Location: /'); die; }

class BuddyPressMigrateTask extends Task {
	//region Static Task Properties

	/**
	 * The identifier for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function identifier() {
		return 'buddypress-migrate';
	}

	/**
	 * The title for the task.  Must be overridden.  Default implementation throws exception.
	 * @return string
	 * @throws \Exception
	 */
	public static function title() {
		return 'Migrate BuddyPress Uploads';
	}

	/**
	 * View containing instructions for the task
	 * @return string|null
	 */
	public static function instructionView() {
		return 'tasks.batch.instructions.buddypress-migrate';
	}

	/**
	 * The menu title for the task.
	 * @return string
	 * @throws \Exception
	 */
	public static function menuTitle() {
		return 'Migrate BuddyPress';
	}

	/**
	 * Controls if this task stops on an error.
	 *
	 * @return bool
	 */
	public static function stopOnError() {
		return false;
	}

	/**
	 * Bulk action title.
	 *
	 * @return string|null
	 */
	public static function bulkActionTitle() {
		return null;
	}

	/**
	 * Determines if a task is a user facing task.
	 * @return bool|false
	 */
	public static function userTask() {
		return true;
	}

	/**
	 * The identifier for analytics
	 * @return string
	 */
	public static function analyticsId() {
		return '/batch/buddypress-migrate';
	}

	/**
	 * The available options when running a task.
	 * @return array
	 */
	public static function taskOptions() {
		return [
		];
	}

	//endregion



	//region Execution
	public function willStart() {
		parent::willStart();

		add_filter('media-cloud/integrations/buddypress-realtime', '__return_true');
	}

	public function prepare($options = [], $selectedItems = []) {
		$groups = \BP_Groups_Group::get([
			'hidden' => true,
			'fields' => 'ids',
		]);

		$groupIds = arrayPath($groups, 'groups', []);
		foreach($groupIds as $groupId) {
			$this->addItem(['id' => $groupId, 'type' => 'group']);
		}

		$userQuery = new \WP_User_Query([
			'fields' => 'ids',
			'paged' => -1,

		]);
		$userIds = $userQuery->get_results();
		foreach($userIds as $userId) {
			$this->addItem(['id' => $userId, 'type' => 'user']);
		}


		return true;
	}

	private function migrateCoverImage($type, $id) {
		$cover_image_object = array(
			'component' => $type,
			'object'    => $id,
		);
		$params = bp_attachments_get_cover_image_settings( $cover_image_object['component'] );
		if (empty($params)) {
			return false;
		}

		$object_dir = $cover_image_object['component'];

		if ( 'xprofile' === $object_dir ) {
			$object_dir = 'members';
		}

		$cover_image = bp_attachments_get_attachment(
			'url',
			array(
				'object_dir' => $object_dir,
				'item_id'    => $cover_image_object['object'],
			)
		);

		if ( empty( $cover_image ) ) {
			if ( ! empty( $params['default_cover'] ) ) {
				$cover_image = $params['default_cover'];
			}
		}

		$cover = call_user_func_array(
			$params['callback'],
			array(
				array(
					'cover_image' => esc_url_raw( $cover_image ),
					'component'   => sanitize_key( $cover_image_object['component'] ),
					'object_id'   => (int) $cover_image_object['object'],
					'width'       => (int) $params['width'],
					'height'      => (int) $params['height'],
				),
			)
		);

		return $cover;
	}

	protected function updateCurrentPost($post_id, $title, $thumb) {
		$this->currentItemID = $post_id;
		$this->currentFile = $title;
		$this->currentTitle = $title;
		$this->currentThumb = $thumb;

		$this->save();
	}

	/**
	 * Performs the actual task
	 *
	 * @param $item
	 *
	 * @return bool|void
	 * @throws \Exception
	 */
	public function performTask($item) {
		global $bp;
		$isBuddyBoss = isset($bp->buddyboss) && !empty($bp->buddyboss);

		$itemId = $item['id'];
		$type = $item['type'];

		if ($type === 'group') {
			$bp->current_component = 'groups';

			$thumbImg = bp_core_fetch_avatar(
				array(
					'item_id'    => $itemId,
					'avatar_dir' => 'group-avatars',
					'object'     => 'group',
					'type'       => 'full',
				)
			);

			if (preg_match('/src=\"([^\"]+)\"/m', $thumbImg, $matches)) {
				$image = $matches[1];
				$group = groups_get_group($itemId);

				$this->updateCurrentPost($itemId, $group->name, $image);
			}

			$this->migrateCoverImage('groups', $itemId);
		} else {
			$bp->current_component = 'front';

			$userAvatar = bp_get_displayed_user_avatar( "item_id=$itemId&type=full" );
			if (preg_match('/src=\"([^\"]+)\"/m', $userAvatar, $matches)) {
				$image = $matches[1];
				$user = get_user_by('id', $itemId);

				$this->updateCurrentPost($itemId, $user->display_name, $image);
			}

			bp_get_displayed_user_avatar( "item_id=$itemId&type=thumb&width=45&height=45" );
			$this->migrateCoverImage(($isBuddyBoss) ? 'xprofile' : 'members', $itemId);
		}

		return true;
	}

	//endregion
}