<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Library\Types;

use DynamicShortcodes\Core\Library\BaseLibrary;

class ParameterPost extends BaseLibrary {

	protected static function get_contextual_fields_list() {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $_POST ? array_keys( $_POST ) : [];
	}
}
