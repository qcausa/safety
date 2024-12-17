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
<a class="download-linksssssss" href="<?php echo $dlm_page_addon->get_download_info_link( $dlm_download, 0, $direct_download ); ?>" rel="nofollow">
	<?php echo $dlm_download->get_image( 'thumbnail' ); ?><?php $dlm_download->the_title(); ?> (<?php printf( _n( '1', '%d', $dlm_download->get_download_count(), 'dlm_page_addon' ), $dlm_download->get_download_count() ) ?>)
</a>