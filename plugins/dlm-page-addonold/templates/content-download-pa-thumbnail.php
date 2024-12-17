<?php
/**
 * Default output for a download via the [download] shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/** @var DLM_Download $dlm_download */

global $dlm_page_addon;
?>
<a class="download-link" style="display:flex;align-items:center; gap:1rem; border: 1px solid var(--bb-content-border-color); border-radius:5px;overflow:hidden;" href="<?php echo $dlm_page_addon->get_download_info_link( $dlm_download, 0, $direct_download ); ?>" rel="nofollow">
	<div style="width: 50px;"><?php echo $dlm_download->get_image( 'thumbnail' ); ?></div>
	<div><?php $dlm_download->the_title(); ?></div>
</a>