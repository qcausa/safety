<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $dlm_page_addon;
$template_handler = new DLM_Template_Handler();
?>
<div class="download_category download_group">
	<h3><a href="<?php echo $dlm_page_addon->get_category_link( $category ); ?>"><?php echo $category->name; ?> <?php if ( $category->count ) : ?>(<?php echo $category->count; ?>)<?php endif; ?></a></h3>
	<ol>
		<?php foreach ( $downloads as $download ) { ?>
            <li><?php $template_handler->get_template_part( 'content-download', $format, $dlm_page_addon->plugin_path() . 'templates/', array( 'dlm_download' => $download, 'direct_download' => $direct_download ) ); ?></li>

		<?php } ?>
	</ol>
</div>