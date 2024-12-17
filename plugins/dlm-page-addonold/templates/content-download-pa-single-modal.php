<?php
/**
 * Default output for a download via the [download] shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

global $dlm_page_addon;

$versions          = $dlm_download->get_versions();
$previous_versions = '';

// Check if we got files in the download
if ( count( $versions ) <= 0 ) {
	?>
	<section class="download-information">
		<p><?php _e( 'No files found for this download.', 'dlm-page-addon' ); ?></p>
	</section>
	<?php
	return;
}

?>

<div class="dlm-mx-auto dlm-max-w-3xl">
	<div
		class="dlm-dlp-article lg:dlm-flex lg:dlm-justify-center dlm-px-4 sm:dlm-px-6 lg:dlm-px-8">
		<div class="lg:dlm-w-1/3 lg:dlm-flex-none lg:dlm-py-0 dlm-m-2">
			<div class="dlm-mx-auto">

				<?php do_action( 'dlm_page_addon_aside_start' ); ?>

				<?php echo $dlm_download->get_image( 'full' ); ?>

				<?php do_action( 'dlm_page_addon_aside_end' ); ?>
			</div>
			<?php do_action( 'dlm_page_addon_single_article_start' ); ?>
		</div>
		<div class='lg:dlm-w-2/3 lg:dlm-flex-none lg:dlm-py-0 dlm-m-2'>
			<div class="dlm-px-4 dlm-ring-1 dlm-ring-gray-300 sm:dlm-mx-0 sm:dlm-rounded-lg">
				<table class="download-meta dlm-divide-y dlm-divide-gray-300 dlm-w-full">
					<?php
					// Get formatted list of tags
					$terms = wp_get_post_terms( $dlm_download->get_id(), 'dlm_download_tag' );
					$tags  = array();
					foreach ( $terms as $term ) {
						$tags[] = '<a href="' . $dlm_page_addon->get_tag_link( $term ) . '">' . $term->name . '</a>';
					}

					// Get formatted list of categories
					$terms = wp_get_post_terms( $dlm_download->get_id(), 'dlm_download_category' );
					$cats  = array();
					foreach ( $terms as $term ) {
						$cats[] = '<a href="' . $dlm_page_addon->get_category_link( $term ) . '">' . $term->name . '</a>';
					}

					// Get previous versions
					if ( sizeof( $versions ) > 1 ) {
						$o_version = array_shift( $versions );

						$previous_versions = ' <a href="#" class="toggle-previous-versions">' . __( 'Previous versions', 'dlm-page-addon' ) . '</a><ul class="previous-versions" style="display: none">';

						foreach ( $versions as $version ) {
							$dlm_download->set_version( $version );
							$version_post = get_post( $version->get_id() );

							$previous_versions .= '<li><a href="' . $dlm_download->get_the_download_link() . '">' . sprintf( __( 'Version %s', 'dlm-page-addon' ), $dlm_download->get_version()->get_version_number() ) . '</a> - ' . date_i18n( get_option( 'date_format' ), strtotime( $version_post->post_date ) ) . '</li>';
						}

						$dlm_download->set_version( $o_version );

						$previous_versions .= '</ul>';
					}

					$download_meta = array(
						'filename'   => array(
							'name'     => __( 'Filename', 'dlm-page-addon' ),
							'value'    => $dlm_download->get_version()->get_filename(),
							'priority' => 1
						),
						'filesize'   => array(
							'name'     => __( 'Filesize', 'dlm-page-addon' ),
							'value'    => $dlm_download->get_version()->get_filesize_formatted(),
							'priority' => 2
						),
						'version'    => array(
							'name'     => __( 'Version', 'dlm-page-addon' ),
							'value'    => $dlm_download->get_version()->get_version_number() . $previous_versions,
							'priority' => 3
						),
						'date'       => array(
							'name'     => __( 'Date added', 'dlm-page-addon' ),
							'value'    => date_i18n( get_option( 'date_format' ), $dlm_download->get_version()->get_date()->getTimestamp() ),
							'priority' => 4
						),
						'downloaded' => array(
							'name'     => __( 'Downloaded', 'dlm-page-addon' ),
							'value'    => sprintf( _n( '1 time', '%d times', $dlm_download->get_download_count(), 'dlm_page_addon' ), $dlm_download->get_download_count() ),
							'priority' => 5
						),
						'categories' => array(
							'name'     => __( 'Category', 'dlm-page-addon' ),
							'value'    => implode( ', ', $cats ),
							'priority' => 6
						),
						'tags'       => array(
							'name'     => __( 'Tags', 'dlm-page-addon' ),
							'value'    => implode( ', ', $tags ),
							'priority' => 7
						)
					);

					$priority = sizeof( $download_meta );

					foreach ( get_post_custom( $dlm_download->get_id() ) as $key => $meta ) {
						if ( strpos( $key, '_' ) === 0 ) {
							continue;
						}

						$download_meta[ $key ] = array(
							'name'     => $key,
							'value'    => do_shortcode( make_clickable( $meta[0] ) ),
							'priority' => $priority
						);

						$priority ++;
					}

					$download_meta = apply_filters( 'dlm_page_addon_download_meta', $download_meta );

					foreach ( $download_meta as $meta ) :
						if ( empty( $meta['value'] ) ) {
							continue;
						}
						?>
						<tr>
							<td class="dlm-relative dlm-py-4 dlm-pr-3 dlm-text-sm dlm-border-t <?php echo esc_attr( 1 == $meta['priority'] ? 'dlm-border-transparent' : 'dlm-border-gray-200' ); ?>">
								<div class="dlm-font-medium dlm-text-gray-900"><?php echo $meta['name']; ?></div>
							</td>
							<td class="dlm-pl-3 dlm-py-3.5 dlm-text-sm dlm-text-gray-500 dlm-border-t dlm-border-gray-200 dlm-text-right"><?php echo $meta['value']; ?></td>
						</tr>
					<?php endforeach;
					?>
				</table>
				<?php do_action( 'dlm_page_addon_single_article_end' ); ?>
			</div>
		</div>
		<script type="text/javascript">
			jQuery('.toggle-previous-versions').click(function () {
				jQuery('ul.previous-versions').slideToggle();
			});
		</script>
	</div>
	<div class='dlm-mx-auto dlm-pb-5'>
			<p class="dlm-mt-2 dlm-text-sm dlm-text-gray-700"><?php echo wptexturize( do_shortcode( $dlm_download->get_description() ) ); ?></p>
	</div>
	<div class='dlm-mx-auto dlm-py-5'>

		<?php
		$download_button = apply_filters( 'dlm_page_addon_download_button', '<a class="aligncenter download-button dlm-rounded dlm-bg-indigo-600 dlm-px-2 dlm-py-1 dlm-text-sm dlm-font-semibold dlm-text-white dlm-shadow-sm hover:dlm-bg-indigo-500 focus-visible:dlm-outline focus-visible:dlm-outline-2 focus-visible:dlm-outline-offset-2 focus-visible:dlm-outline-indigo-600 dlm-w-auto" href="' . $dlm_download->get_the_download_link() . '" rel="nofollow">' . __( 'Download', 'dlm-page-addon' ) . '</a>', $dlm_download->get_id() );
		if ( '' !== $download_button ) {
			echo $download_button;
		}
		?>

	</div>
</div>
