<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $dlm_page_addon;
$template_handler = new DLM_Template_Handler();
?>
<div id="download-page-featured" class="download_group">
    <h3><?php _e( 'Featured', 'dlm_page_addon' ); ?></h3>
    <ul>
		<?php foreach ( $downloads as $download ) { ?>

            <li><?php $template_handler->get_template_part( 'content-download', $format, $dlm_page_addon->plugin_path() . 'templates/', array( 'dlm_download' => $download, 'direct_download' => $direct_download ) ); ?></li>

		<?php } ?>
    </ul>
</div>