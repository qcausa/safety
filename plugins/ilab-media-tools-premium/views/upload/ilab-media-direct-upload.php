<script type="text/html" id="tmpl-media-cloud-direct-upload">
    <# var messageClass = data.message ? 'has-upload-message' : 'no-upload-message'; #>
    <# if ( data.canClose ) { #>
    <button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close uploader' ); ?></span></button>
    <# } #>
    <div class="uploader-inline-content {{ messageClass }}">
        <# if ( data.message ) { #>
        <img class="media-cloud-upload-logo" style="margin: 0 auto 20px auto;" src="<?php echo ILAB_PUB_IMG_URL.'/icon-cloud.svg'?>">
        <h2 class="upload-message">{{ data.message }}</h2>
        <# } #>
		<?php if ( ! _device_can_upload() ) : ?>
            <h2 class="upload-instructions"><?php printf( __( 'The web browser on your device cannot be used to upload files. You may be able to use the <a href="%s">native app for your device</a> instead.' ), 'https://apps.wordpress.org/' ); ?></h2>
		<?php elseif ( is_multisite() && ! is_upload_space_available() ) : ?>
            <h2 class="upload-instructions"><?php _e( 'Upload Limit Exceeded' ); ?></h2>
			<?php
			/** This action is documented in wp-admin/includes/media.php */
			do_action( 'upload_ui_over_quota' );
			?>

		<?php else : ?>
            <div class="upload-ui">
                <img class="media-cloud-upload-logo" style="margin: 0 auto 20px auto;" src="<?php echo ILAB_PUB_IMG_URL.'/icon-cloud.svg'?>">
                <h2 class="upload-instructions drop-instructions"><?php _e( 'Drop files anywhere to upload directly to '.\MediaCloud\Plugin\Tools\Storage\StorageToolSettings::driver() ); ?></h2>
                <p class="upload-instructions drop-instructions"><?php _ex( 'or', 'Uploader: Drop files here - or - Select Files' ); ?></p>
                <button type="button" class="browser button button-hero"><?php _e( 'Select Files' ); ?></button>
            </div>

            <div class="upload-inline-status"></div>

            <div class="post-upload-ui">
				<?php
				/** This action is documented in wp-admin/includes/media.php */
				do_action( 'pre-upload-ui' );
				/** This action is documented in wp-admin/includes/media.php */
				do_action( 'pre-plupload-upload-ui' );

				if ( 10 === remove_action( 'post-plupload-upload-ui', 'media_upload_flash_bypass' ) ) {
					/** This action is documented in wp-admin/includes/media.php */
					do_action( 'post-plupload-upload-ui' );
					add_action( 'post-plupload-upload-ui', 'media_upload_flash_bypass' );
				} else {
					/** This action is documented in wp-admin/includes/media.php */
					do_action( 'post-plupload-upload-ui' );
				}

				$max_upload_size = wp_max_upload_size();
				if ( ! $max_upload_size ) {
					$max_upload_size = 0;
				}
				?>

                <p class="max-upload-size">
					<?php
					printf( __( 'Maximum upload file size: %s.' ), esc_html( size_format( $max_upload_size ) ) );
					?>
                </p>

                <# if ( data.suggestedWidth && data.suggestedHeight ) { #>
                <p class="suggested-dimensions">
					<?php
					/* translators: 1: suggested width number, 2: suggested height number. */
					printf( __( 'Suggested image dimensions: %1$s by %2$s pixels.' ), '{{data.suggestedWidth}}', '{{data.suggestedHeight}}' );
					?>
                </p>
                <# } #>

				<?php
				/** This action is documented in wp-admin/includes/media.php */
				do_action( 'post-upload-ui' );
				?>
            </div>
		<?php endif; ?>
    </div>
</script>
