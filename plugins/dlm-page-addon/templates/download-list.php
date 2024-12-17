<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $dlm_page_addon;
$template_handler = new DLM_Template_Handler();

echo apply_filters( 'dlm_widget_downloads_list_start', '<ul class="dlm-downloads">' );

if ( count( $downloads ) > 0 ) {
	foreach ( $downloads as $download ) {
		echo apply_filters( 'dlm_widget_downloads_list_item_start', '<li>' );

		$template_handler->get_template_part( 'content-download', $format, $dlm_page_addon->plugin_path() . 'templates/', array( 'dlm_download' => $download, 'direct_download' => $direct_download ) );

		echo apply_filters( 'dlm_widget_downloads_list_item_end', '</li>' );
	}
}

echo apply_filters( 'dlm_widget_downloads_list_end', '</ul>' );