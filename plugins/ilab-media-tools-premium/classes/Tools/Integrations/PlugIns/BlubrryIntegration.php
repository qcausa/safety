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

use MediaCloud\Plugin\Utilities\View;
use function MediaCloud\Plugin\Utilities\arrayPath;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

class BlubrryIntegration {
	public function __construct() {
		add_action('current_screen', function(\WP_Screen $screen) {
			if (!empty($screen)) {
				$action = empty($screen->action) ? arrayPath($_REQUEST, 'action', null) : $screen->action;
				if ((in_array($action, ['add', 'edit'])) && ($screen->base == 'post')) {
					$this->hookUI();
				}
			}
		});
	}

	private function hookUI() {
		add_action('wp_enqueue_media', function() {
			add_action('admin_footer', function() {
				echo View::render_view('integrations/blubrry', []);
			});
		});
	}
}


