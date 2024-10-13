<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class DLM_CI_WP_Importer
 */
class DLM_CI_WP_Importer {

	/** @var int */
	private $total_downloads_imported = 0;

	/** @var int */
	private $total_versions_importered = 0;

	/** @var array */
	private $version_count = array();

	/**
	 * Register the importer
	 */
	public function register_importer() {
		register_importer( 'dlm_csv', 'Download Monitor CSV Importer', __( 'Import downloads from a csv file.', '' ), array(
			$this,
			'setup'
		) );
	}

	/**
	 * Setup the page
	 */
	public function setup() {

		// Process actions
		$this->process();

		// Print screen
		$this->print_screen();
	}

	/**
	 * Check the nonce
	 */
	private function check_nonce() {
		$success = false;

		// Validate
		if ( isset( $_POST['dlm_ci_nonce'] ) && false != wp_verify_nonce( $_POST['dlm_ci_nonce'], 'dlm_ci_super_secret' ) ) {
			$success = true;
		}

		// Check
		if ( true !== $success ) {
			wp_die( 'Nonce check failed, please run the importer from the Tools > Import page.' );
		}
	}

	/**
	 * Process any actions
	 */
	private function process() {

		// CSV Handler
		$csv_handler = new DLM_CI_CSV_Handler();

		// Current import data
		$current_import = $csv_handler->get_current_import();

		// Check if we need to upload a file
		if ( isset( $_POST['upload_file'] ) && '1' == $_POST['upload_file'] && isset( $_FILES ) && isset( $_FILES['dlm_ci_file'] ) ) {

			// Check Nonce
			$this->check_nonce();

			// Move uploaded CSV file to media lib
			$lib_file = $csv_handler->move_file_to_media_lib( $_FILES['dlm_ci_file'] );

			// Check if the file was uploaded successfully
			if ( is_array( $lib_file ) && isset( $lib_file['file'] ) ) {
				$current_import['file'] = $lib_file;

				// Check if a delimiter is set
				if ( isset( $_POST['delimiter'] ) ) {
					// Get correct delimiter
					$delimiter = ( ( '' != $_POST['delimiter'] ) ? $_POST['delimiter'] : ',' );

					// Set delimiter
					$current_import['delimiter'] = $delimiter;
				}

			} else {
				echo '<strong>Error uploading file:</strong><br/>';
				echo '<pre>';
				print_r( $lib_file );
				echo '</pre>';
			}

		}

		// Check if data map has been submitted
		if ( isset( $_POST['mapped_data'] ) && '1' == $_POST['mapped_data'] && count( $current_import ) > 0 ) {

			// Create our CSV map
			$map = array();
			foreach ( $_POST['mapto'] as $csv_col => $dlm_col ) {
				if ( 'custom_meta' === $dlm_col ) {
					$map[ $dlm_col ][] = $csv_col;
				} else {
					$map[ $dlm_col ] = $csv_col;
				}
			}

			// Check if title is mapped
			if ( ! isset( $map['title'] ) ) {
				wp_die( 'Title must be mapped' );
			}

			// Get import data
			$import_data = $csv_handler->get_current_import();

			// Parse CSV
			$csv            = new DLM_CI_CSV_Parser();
			$csv->delimiter = $import_data['delimiter'];
			$csv->parse( $import_data['file']['file'] );

			// Create array with downloads
			$downloads = array();

			// The download.
			$download = null;

			// Check and loop CSV data.
			if ( count( $csv->data ) > 0 ) {
				foreach ( $csv->data as $data ) {

					// Check if row is a download.
					if ( ! isset( $data['type'] ) || 'download' === $data['type'] ) {

						// Check if a download is set and, if so, add it.
						if ( null !== $download ) {
							if ( ! empty( $this->version_count ) ) {
								foreach ( $this->version_count as $key => $version ) {
									if ( null !== $version->get_version() ) {
										$download->add_version( $version );
									} else {
										$version->set_version( ( count( $this->version_count ) - $key ) . '.0' );
										$download->add_version( $version );
									}
								}
								$this->version_count = array();
							}
							// Add download object.
							$downloads[] = $download;

							// Increase downloads imported count.
							$this->total_downloads_imported ++;
						}

						// Don't import rows that don't have a title.
						if ( '' == $data[ $map['title'] ] ) {
							continue;
						}

						// if no type is set, all is on one line so we require file url as well.
						if ( ! isset( $data['type'] ) && ( ! isset( $map['file_urls'] ) || '' == $data[ $map['file_urls'] ] ) ) {
							continue;
						}

						// Setup basic download
						$download = new DLM_CI_Import_Download(
							$data[ $map['title'] ],
							( isset( $map['description'] ) ? $data[ $map['description'] ] : '' ),
							( isset( $map['excerpt'] ) ? $data[ $map['excerpt'] ] : '' ),
							( isset( $map['categories'] ) ? explode( '|', $data[ $map['categories'] ] ) : array() ),
							( isset( $map['tags'] ) ? explode( '|', $data[ $map['tags'] ] ) : array() )
						);

						// check if id isset and if so, set it
						if ( isset( $map['download_id'] ) && ! empty( $data[ $map['download_id'] ] ) ) {
							$download->set_id( $data[ $map['download_id'] ] );
						}

						// Check featured
						if ( isset( $map['featured'] ) && ( 'yes' === $data[ $map['featured'] ] || 1 == $data[ $map['featured'] ] ) ) {
							$download->set_featured( true );
						}

						// Check featured
						if ( isset( $map['members_only'] ) && ( 'yes' === $data[ $map['members_only'] ] || 1 == $data[ $map['members_only'] ] ) ) {
							$download->set_members_only( true );
						}

						// Check featured
						if ( isset( $map['redirect'] ) && ( 'yes' === $data[ $map['redirect'] ] || 1 == $data[ $map['redirect'] ] ) ) {
							$download->set_redirect( true );
						}

						// add_thumbnail
						if ( isset( $map['thumbnail'] ) &&  !empty( $data[ $map['thumbnail'] ] ) ) {
							$download->set_thumbnail( absint( $this->thumbnail_upload( $data[ $map['thumbnail'] ] ) ) );
						}

						// Published date
						if ( isset( $map['published_date'] ) && ! empty( $data[ $map['published_date'] ] ) ) {
							$time = new DateTime( $data[ $map['published_date'] ] );
							$download->set_published_date( $time->format( 'Y-m-d g:i:s' ) );
						}

						if ( isset( $map['download_slug'] ) && ! empty( $data[ $map['download_slug'] ] ) ) {
							$download->set_slug( $data[ $map['download_slug'] ] );
						}

						// check if there are custom meta fields
						if ( isset( $map['custom_meta'] ) && count( $map['custom_meta'] ) > 0 ) {
							foreach ( $map['custom_meta'] as $custom_meta ) {

								// check if user set a post meta key
								if ( isset( $_POST['meta_keys'][ $custom_meta ] ) && '' != $_POST['meta_keys'][ $custom_meta ] ) {

									// allow user via filter to completly turn off exploding call on meta data value, on by default
									if ( apply_filters( 'dlm_csv_importer_meta_data_allow_multiple_values', true ) ) {

										// allow user via filter to set the delimiter we explode the meta data on, default is comma (,)
										$meta_data = explode( apply_filters( 'dlm_csv_importer_meta_data_delimiter', ',' ), $data[ $custom_meta ] );
									} else {
										$meta_data = array( $data[ $custom_meta ] );
									}

									if ( is_array( $meta_data ) && count( $meta_data ) > 0 ) {
										foreach ( $meta_data as $md ) {
											// add custom meta to download
											$download->add_meta( $_POST['meta_keys'][ $custom_meta ], $md );
										}
									}

								}
							}
						}

						// check if we need to create a version from this line
						if ( ! isset( $data['type'] ) || ( 'download' == $data['type'] && isset( $data['file_urls'] ) && '' !== $data['file_urls'] ) ) {
							// Create version
							$version = new DLM_CI_Import_Version( $data[ $map['file_urls'] ] );

							// Check version #
							if ( isset( $map['version'] ) && isset( $data[ $map['version'] ] ) && '' !== $data[ $map['version'] ] ) {
								$version->set_version( $data[ $map['version'] ] );
							} else {
								$version->set_version( null );
							}

							// Check download_count
							if ( isset( $map['download_count'] ) && isset( $data[ $map['download_count'] ] ) && '' !== $data[ $map['download_count'] ] ) {
								$version->set_download_count( $data[ $map['download_count'] ] );
							}

							// Check file_date
							if ( isset( $map['file_date'] ) && isset( $data[ $map['file_date'] ] ) && '' !== $data[ $map['file_date'] ] ) {
								$version->set_date( new DateTime( $data[ $map['file_date'] ] ) );
							}

							// Check version_order
							if ( isset( $map['version_order'] ) && isset( $data[ $map['version_order'] ] ) && '' !== $data[ $map['version_order'] ] ) {
								$version->set_order( $data[ $map['version_order'] ] );
							}

							// Add version to download
							$download->add_version( $version );

							// Increase versions imported count
							$this->total_versions_importered ++;
						}
					} else if ( 'version' === $data['type'] ) {

						// No version without a file
						if ( ! isset( $map['file_urls'] ) || '' == $data[ $map['file_urls'] ] ) {
							continue;
						}

						// Create version
						$version = new DLM_CI_Import_Version( $data[ $map['file_urls'] ] );

						// Check version #
						if ( isset( $map['version'] ) && isset( $data[ $map['version'] ] ) && '' !== $data[ $map['version'] ] ) {
							$version->set_version( $data[ $map['version'] ] );
							
						}else{
							$version->set_version( null );
						}

						// Check download_count
						if ( isset( $map['download_count'] ) && isset( $data[ $map['download_count'] ] ) && '' !== $data[ $map['download_count'] ] ) {
							$version->set_download_count( $data[ $map['download_count'] ] );
						}

						// Check file_date
						if ( isset( $map['file_date'] ) && isset( $data[ $map['file_date'] ] ) && '' !== $data[ $map['file_date'] ] ) {
							$version->set_date( new DateTime( $data[ $map['file_date'] ] ) );
						}

						// Check version_order
						if ( isset( $map['version_order'] ) && isset( $data[ $map['version_order'] ] ) && '' !== $data[ $map['version_order'] ] ) {
							$version->set_order( $data[ $map['version_order'] ] );
						}

						// Add version to download
						$this->version_count[] = $version;

						// Increase versions imported count
						$this->total_versions_importered ++;
					}
				}

				// Add final download object if it's there
				if ( null !== $download ) {

					if( !empty( $this->version_count ) ){
						foreach( $this->version_count as $key => $version ){
							if( null !== $version->get_version() ){
								$download->add_version( $version );
								
							}else{
								$version->set_version( ( count( $this->version_count ) - $key ) . '.0' );
								$download->add_version( $version );
							}
						}
						$this->version_count = array();
					}
					// Add download object
					$downloads[] = $download;

					// Increase downloads imported count
					$this->total_downloads_imported ++;
				}
			}

			// Check if there are downloads to be imported
			if ( count( $downloads ) > 0 ) {

				// Import the downloads
				$im = new DLM_CI_Import_Manager();

				// Do the import, store the result
				$import_result = $im->import_downloads( $downloads );

				// Check result
				if ( true === $import_result ) {

					// Remove the transient on success
					$csv_handler->unset_current_import();
					$current_import = array();

				} else {
					// Set total imported to 0 if import failed
					$this->total_imported = 0;
				}
			}
		}

		// Set import data if there's any
		if ( count( $current_import ) > 0 ) {
			$csv_handler->set_current_import( $current_import );
		}

	}

	/**
	 * Determine the upload step
	 */
	public function print_screen() {
		$step = isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1;

		?>
        <div class="wrap">
            <h2><?php _e( 'Download Monitor CSV Importer', 'dlm-csv-importer' ); ?></h2>
			<?php
			switch ( $step ) {
				case 1:
					$this->print_upload_screen();
					break;
				case 2:
					// mapping screen
					$this->print_map_screen();
					break;
				case 3:
					// do the actual upload
					$this->print_done_screen();
					break;
				default:
					echo __( 'Incorrect step given', 'dlm-csv-importer' );
					break;
			}
			?>
        </div>
		<?php
	}

	/**
	 * Print upload screen
	 */
	private function print_upload_screen() {

		// Get max files sizes
		$post_max_size       = intval( str_replace( 'M', '', ini_get( 'post_max_size' ) ) );
		$upload_max_filesize = intval( str_replace( 'M', '', ini_get( 'upload_max_filesize' ) ) );

		// Set max size
		$max_size = $post_max_size;

		// Check if upload_max_filesize is smaller than post_max_size
		if ( $upload_max_filesize < $max_size ) {
			$max_size = $upload_max_filesize;
		}

		?>
        <form enctype="multipart/form-data" method="post"
              action="<?php echo admin_url( 'admin.php?import=dlm_csv&step=2' ); ?>">
            <table class="form-table">
                <tbody>
                <tr>
                    <td colspan="2"><?php _e( "Select a CSV file from your computer you want to import. You can 'map' the csv 	fields to the correct download data in the next step.", 'dlm-csv-importer' ); ?></td>
                </tr>
                <tr>
                    <th>
                        <label for="upload"><?php _e( 'Upload a file:', 'dlm-csv-importer' ); ?></label>
                    </th>
                    <td>
                        <input type="file" name="dlm_ci_file" size="25"/>
                        <small><?php printf( __( 'Maximum size: %sMB', 'dlm-csv-importer' ), $max_size ); ?></small>
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e( 'Delimiter', 'dlm-csv-importer' ); ?></label><br/></th>
                    <td><input type="text" name="delimiter" value="," size="2"/></td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="hidden" name="dlm_ci_nonce"
                       value="<?php echo wp_create_nonce( 'dlm_ci_super_secret' ); ?>"/>
                <input type="hidden" name="upload_file" value="1"/>
                <input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import' ); ?>"/>
            </p>
        </form>
		<?php
	}

	/**
	 * Print map screen
	 */
	private function print_map_screen() {

		// CSV Handler
		$csv_handler = new DLM_CI_CSV_Handler();

		// Get import data
		$import_data = $csv_handler->get_current_import();

		// Import data should be set in this step
		if ( 0 == count( $import_data ) ) {
			wp_die( 'Something went wrong when uploading the file, please try again' );
		}

		// CSV Parser
		$csv            = new DLM_CI_CSV_Parser();
		$csv->delimiter = $import_data['delimiter'];
		$csv->parse( $import_data['file']['file'] );

		// CSV headers
		$headers = array();
		foreach ( $csv->titles as $title ) {
			// Skip blank headers and the 'type' header
			if ( '' !== $title && 'type' !== $title ) {
				$headers[] = $title;
			}
		}

		// Check if there's a header
		if ( 0 === count( $headers ) ) {
			wp_die( __( 'No CSV header row found', 'dlm-csv-importer' ) );
		}

		// First row data
		$first_row = array();
		foreach ( $csv->data as $data ) {
			foreach ( $headers as $header ) {
				if ( isset( $data[ $header ] ) ) {
					$first_row[ $header ] = $data[ $header ];
				}
			}
			break;
		}

		// second row data
		$second_row  = array();
		$sr_skip_row = true;
		foreach ( $csv->data as $data ) {
			if ( $sr_skip_row ) {
				$sr_skip_row = false;
				continue;
			}
			foreach ( $headers as $header ) {
				$second_row[ $header ] = $data[ $header ];
			}
			break;
		}

		// Check if there are data rows
		if ( 0 === count( $first_row ) ) {
			wp_die( __( 'No CSV download rows found', 'dlm-csv-importer' ) );
		}

		?>

        <p><?php printf( __( 'We found <strong>%d rows</strong> to import, these include downloads and versions.', 'dlm-csv-importer' ), count( $csv->data ) ); ?></p>

        <h3><?php _e( 'Map Fields', 'dlm-csv-importer' ); ?></h3>

        <p><?php _e( 'Here you can map your imported columns to download data fields.', 'dlm-csv-importer' ); ?></p>

        <form method="post" action="<?php echo admin_url( 'admin.php?import=dlm_csv&step=3' ); ?>">
            <table class="widefat">
                <thead>
                <tr>
                    <th width="25%"><?php _e( 'Map to', 'dlm-csv-importer' ); ?></th>
                    <th width="25%"><?php _e( 'Column Header', 'dlm-csv-importer' ); ?></th>
                    <th><?php _e( 'Example Column Value', 'dlm-csv-importer' ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php
				foreach ( $headers as $header ) {
					?>
                    <tr data-header="<?php echo $header; ?>">
                        <td><?php $this->print_mapping_select( $header ); ?></td>
                        <td><?php echo $header; ?></td>
                        <td>
                            <code><?php echo( ( isset( $first_row[ $header ] ) && '' != $first_row[ $header ] ) ? $first_row[ $header ] : ( ( isset( $second_row[ $header ] ) && '' != $second_row[ $header ] ) ? $second_row[ $header ] : '' ) ); ?></code>
                        </td>
                    </tr>
					<?php
				}
				?>
                </tbody>
            </table>

            <p class="submit">
                <input type="hidden" name="dlm_ci_nonce"
                       value="<?php echo wp_create_nonce( 'dlm_ci_super_secret' ); ?>"/>
                <input type="hidden" name="mapped_data" value="1"/>
                <input type="submit" class="button button-primary"
                       value="<?php _e( 'Start Import', 'dlm-csv-importer' ); ?>"/>
            </p>
        </form>

		<?php
	}

	/**
	 * Print the mapping select box
	 *
	 * @param $current
	 */
	private function print_mapping_select( $current ) {

		// The select options
		$options = array(
			'download_id'    => __( 'Download ID', 'dlm-csv-importer' ),
			'download_slug'  => __( 'Download Slug', 'dlm-csv-importer' ),
			'title'          => __( 'Title', 'dlm-csv-importer' ),
			'description'    => __( 'Description', 'dlm-csv-importer' ),
			'excerpt'        => __( 'Excerpt', 'dlm-csv-importer' ),
			'categories'     => __( 'Categories', 'dlm-csv-importer' ),
			'tags'           => __( 'Tags', 'dlm-csv-importer' ),
			'files'          => __( 'Files', 'dlm-csv-importer' ),
			'featured'       => __( 'Featured', 'dlm-csv-importer' ),
			'members_only'   => __( 'Members Only', 'dlm-csv-importer' ),
			'redirect'       => __( 'Redirect to file', 'dlm-csv-importer' ),
			'version'        => __( 'Version: Version', 'dlm-csv-importer' ),
			'download_count' => __( 'Version: Download Count', 'dlm-csv-importer' ),
			'file_date'      => __( 'Version: File Date', 'dlm-csv-importer' ),
			'file_urls'      => __( 'Version: File URL', 'dlm-csv-importer' ),
			'version_order'  => __( 'Version: Order', 'dlm-csv-importer' ),
			'custom_meta'    => __( 'Custom Meta', 'dlm-csv-importer' ),
			'thumbnail'    	 => __( 'Thumbnail', 'dlm-csv-importer' ),
			'published_date' => __( 'Published Date', 'dlm-csv-importer' )
		);

		// strtolower the current
		$lower_current = strtolower( $current );

		echo '<select name="mapto[' . $current . ']" class="dlm-ci-select-map-to">' . PHP_EOL;
		echo '<option value="0">' . __( 'Do not import', 'dlm-csv-importer' ) . '</option>' . PHP_EOL;
		foreach ( $options as $option_key => $option_title ) {
			echo '<option value="' . $option_key . '"';

			// Detect the correct column
			if ( $lower_current == $option_key ) {
				echo ' selected="selected"';
			}

			echo '>' . $option_title . '</option>' . PHP_EOL;
		}
		echo '</select>' . PHP_EOL;

		?>
		<?php
	}

	/**
	 * Print the done screen
	 */
	private function print_done_screen() {
		if ( $this->total_downloads_imported > 0 ) {
			echo '<p>' . sprintf( __( 'All done. %s downloads and %s versions have been imported!', 'dlm-csv-importer' ), $this->total_downloads_imported, $this->total_versions_importered ) . '</p>' . PHP_EOL;
			echo '<p>' . sprintf( __( '%sCheck them out here%s', 'dlm-csv-importer' ), '<a href="' . admin_url( 'edit.php?post_type=dlm_download' ) . '">', '</a>' ) . '</p>' . PHP_EOL;
		} else {
			echo '<p>' . __( 'Something went wrong while importing the downloads, no Downloads have been imported.', 'dlm-csv-importer' ) . '</p> ' . PHP_EOL;
			echo '<p>' . sprintf( __( '%sPlease try again%s', 'dlm-csv-importer' ), '<a href="' . admin_url( 'admin.php?import=dlm_csv' ) . '">', '</a>' ) . '</p> ' . PHP_EOL;
		}

	}

	/**
	 * Uploads and returns the uploaded image id.
	 *
	 * @param $image_url the thumbnail url
	 * 
	 * @return mixed false if upload failed, id on successs
	 */
	private function thumbnail_upload( $image_url ) {

		// it allows us to use download_url() and wp_handle_sideload() functions
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
	
		// download to temp dir
		$temp_file = download_url( $image_url );
	
		if( is_wp_error( $temp_file ) ) {
			return false;
		}
	
		// move the temp file into the uploads directory
		$file = array(
			'name'     => basename( $image_url ),
			'type'     => mime_content_type( $temp_file ),
			'tmp_name' => $temp_file,
			'size'     => filesize( $temp_file ),
		);
		$sideload = wp_handle_sideload(
			$file,
			array(
				'test_form'   => false // no needs to check 'action' parameter
			)
		);
	
		if( ! empty( $sideload[ 'error' ] ) ) {
			// you may return error message if you want
			return false;
		}
	
		// it is time to add our uploaded image into WordPress media library
		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $sideload[ 'url' ],
				'post_mime_type' => $sideload[ 'type' ],
				'post_title'     => basename( $sideload[ 'file' ] ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$sideload[ 'file' ]
		);
	
		if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return false;
		}
	
		// update medatata, regenerate image sizes
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
	
		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
		);
	
		return $attachment_id;
	
	}


}