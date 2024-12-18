<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Settings\AdminPages;

class Manager {
	/**
	 * @var License;
	 */
	public $license;

	/**
	 * @var Notices;
	 */
	public $notices;

	/**
	 * @var Bar;
	 */
	public $bar;

	/**
	 * @var Collection;
	 */
	public $collection;

	public function __construct() {
		$this->notices    = new Notices();
		$this->license    = new License( $this );
		$this->bar        = new Bar();
		$this->collection = new Collection();
	}
}
