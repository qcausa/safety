<?php
/**
 * Advanced carousel template
 */
$layout     = $this->get_settings_for_display( 'item_layout' );
$layout_template   = $layout . '-items';

$this->_get_global_looped_template( $layout_template, 'items_list' );
