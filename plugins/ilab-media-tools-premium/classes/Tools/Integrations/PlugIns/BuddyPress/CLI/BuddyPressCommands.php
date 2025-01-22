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

namespace MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress\CLI;

use MediaCloud\Plugin\CLI\Command;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress\Tasks\BuddyPressDeleteTask;
use MediaCloud\Plugin\Tools\Integrations\PlugIns\BuddyPress\Tasks\BuddyPressMigrateTask;

if (!defined('ABSPATH')) { header('Location: /'); die; }

/**
 * Commands related to the BuddyPress integration
 */
class BuddyPressCommands extends Command {
	/**
	 * Deletes any buddypress related media that has been uploaded
	 *
	 * @when after_wp_load
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \Exception
	 */
	public function deleteUploads($args, $assoc_args) {
		$task = new BuddyPressDeleteTask();
		$this->runTask($task, $assoc_args);
	}

	/**
	 * Migrates all profile and group avatars and cover images
	 *
	 * @when after_wp_load
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \Exception
	 */
	public function migrate($args, $assoc_args) {
		$task = new BuddyPressMigrateTask();
		$this->runTask($task, $assoc_args);
	}


	public static function Register() {
		\WP_CLI::add_command('mediacloud:buddypress', __CLASS__);
	}

}