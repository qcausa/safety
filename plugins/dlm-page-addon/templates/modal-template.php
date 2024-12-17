<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>
<div id="dlm-no-access-modal" >
	<div class="dlm-no-access-modal-overlay">

	</div>
	<div class="dlm-no-access-modal-window">
		<div class="dlm-no-access-modal__header">
			<span class="dlm-no-access-modal__title"><?php echo esc_html__( 'Download Information', 'dlm_page_addon' ); ?></span>
			<span class="dlm-no-access-modal-close" title="<?php echo esc_attr__( 'Close Modal', 'dlm_page_addon' ); ?>"> <span class="dashicons dashicons-no"></span>
		</div>
		<div class="dlm-no-access-modal__body">
			<div id="download-page"><?php WP_DLM_Page_Addon::instance()->download_info( $download_slug, array() ); ?></div>
		</div>
		<div class="dlm-no-access-modal__footer">
			<button class="dlm-no-access-modal-close"><?php echo esc_html__( 'Close', 'dlm_page_addon' ); ?></button>
		</div>
</div>
</div>
