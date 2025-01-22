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

namespace MediaCloud\Plugin\Tools\Network;

use MediaCloud\Plugin\Tools\Tool;
use MediaCloud\Plugin\Tools\ToolsManager;
use MediaCloud\Plugin\Utilities\View;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

/**
 * Class NetworkTool
 *
 * Tool for managing WordPress multisite network settings
 */
class NetworkTool extends Tool {

	/**
	 * NetworkTool constructor.
	 *
	 * @param $toolName
	 * @param $toolInfo
	 * @param ToolsManager $toolManager
	 */
	public function __construct( $toolName, $toolInfo, $toolManager ) {
		foreach($toolManager->tools as $tool) {
			$networkGroups = arrayPath($tool->toolInfo, 'network_settings/groups', []);
			if (!empty($networkGroups)) {
				$toolInfo['settings']['groups'] = array_merge($toolInfo['settings']['groups'], $networkGroups);
			}
		}

		parent::__construct( $toolName, $toolInfo, $toolManager );
	}

	public function registerMenu($top_menu_slug, $networkMode = false, $networkAdminMenu = false, $tool_menu_slug = null) {
		parent::registerMenu($top_menu_slug);

		if ($networkMode && $networkAdminMenu) {
			add_submenu_page($top_menu_slug, 'Media Cloud Network Settings', 'Network Settings', 'manage_network_options', 'media-tools-network-settings', [
				$this,
				'renderNetworkSettings'
			]);
		}
	}

	public function enabled() {
		return true;
	}

	public function renderNetworkSettings() {
		global $wp_settings_sections, $wp_settings_fields;

		$page = $this->optionsPage();
		$group = $this->optionsGroup();

		$sections = [];

		foreach((array)$wp_settings_sections[$page] as $section) {
			if (!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']])) {
				continue;
			}

			$sections[] = [
				'title' => $section['title'],
				'id' => $section['id'],
				'description' => arrayPath($this->toolInfo, "settings/groups/{$section['id']}/description", null)
			];
		}

		echo View::render_view( 'base/simple-settings', [
			'title' => 'Network Settings',
			'group' => $group,
			'page' => $page,
			'manager' => $this,
			'sections' => $sections
		]);
	}
}