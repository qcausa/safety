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

use MediaCloud\Plugin\Utilities\Logging\Logger;

if (!defined( 'ABSPATH')) { header( 'Location: /'); die; }

require_once 'LearnDash/functions.php';


class LearnDashIntegration {
	public function __construct() {
		if (!defined('K_PATH_FONTS')) {
			$reflector = new \ReflectionClass(\SFWD_LMS::class);
			$class = $reflector->getFileName();

			if (strpos($class, WP_PLUGIN_DIR) !== false) {
				$class = str_replace(WP_PLUGIN_DIR, '', $class);
				$parts = explode(DIRECTORY_SEPARATOR, trim($class, DIRECTORY_SEPARATOR));
				$pluginDir = trailingslashit(WP_PLUGIN_DIR).array_shift($parts);

				$fontsDir = trailingslashit($pluginDir).'includes/vendor/tcpdf/fonts/';
				if (file_exists($fontsDir)) {
					define('K_PATH_FONTS', $fontsDir);
					return;
				}

				$fontsDir = trailingslashit($pluginDir).'includes/lib/tcpdf/fonts/';
				if (file_exists($fontsDir)) {
					define('K_PATH_FONTS', $fontsDir);
					return;
				}

				Logger::warning("Cannot find fonts directory in '$pluginDir'.", [], __METHOD__, __LINE__);
			}
		}
	}
}


