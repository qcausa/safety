<?php

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpIllegalPsrClassPathInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

namespace WPFormsPostSubmissions;

use Tribe__Timezones;
use WP_Post;
use WP_User_Query;
use WPForms_Field_File_Upload;
use WPForms_Updater;

/**
 * Post Submissions.
 *
 * @since 1.0.0
 */
class Plugin {

	/**
	 * Retrieve a single instance of the class.
	 *
	 * @since 1.5.0
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();

			$instance->init();
		}

		return $instance;
	}

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();

		return $this;
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.5.0
	 */
	private function hooks() {

		add_action( 'init', [ $this, 'load_template' ], 15 );
		add_action( 'wpforms_builder_enqueues', [ $this, 'admin_enqueues' ] );
		add_action( 'wpforms_builder_strings', [ $this, 'add_strings' ], 10, 2 );
		add_filter( 'wpforms_process_before_form_data', [ $this, 'override_file_uploads' ], 10, 2 );
		add_action( 'wpforms_process_complete', [ $this, 'process_post_submission' ], 10, 4 );
		add_filter( 'wpforms_builder_settings_sections', [ $this, 'settings_register' ], 20, 2 );
		add_action( 'wpforms_form_settings_panel_content', [ $this, 'settings_content' ], 20, 2 );

		add_action( 'wpforms_updater', [ $this, 'updater' ] );
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key.
	 */
	public function updater( $key ) {

		new WPForms_Updater(
			[
				'plugin_name' => 'WPForms Post Submissions',
				'plugin_slug' => 'wpforms-post-submissions',
				'plugin_path' => plugin_basename( WPFORMS_POST_SUBMISSIONS_FILE ),
				'plugin_url'  => trailingslashit( plugin_dir_url( WPFORMS_POST_SUBMISSIONS_FILE ) ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_POST_SUBMISSIONS_VERSION,
				'key'         => $key,
			]
		);
	}

	/**
	 * Load the post submission form template.
	 *
	 * @since 1.0.0
	 */
	public function load_template() {

		new Template();
	}

	/**
	 * Enqueue assets for the builder.
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueues() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-builder-post-submissions',
			WPFORMS_POST_SUBMISSIONS_URL . "assets/css/admin-builder-post-submissions{$min}.css",
			[],
			WPFORMS_POST_SUBMISSIONS_VERSION
		);

		wp_enqueue_script(
			'wpforms-builder-post-submissions',
			WPFORMS_POST_SUBMISSIONS_URL . "assets/js/admin-builder-post-submissions{$min}.js",
			[ 'jquery' ],
			WPFORMS_POST_SUBMISSIONS_VERSION,
			true
		);
	}

	/**
	 * Add i18n strings.
	 *
	 * @since 1.5.0
	 *
	 * @param array   $strings Builder strings.
	 * @param WP_Post $form    Active form.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function add_strings( $strings, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$strings['post_submissions'] = [
			'disabling_options' => esc_html__( 'Some of the Field Options for the selected field will be disabled to prevent changes as long as the field is being used as the Post Featured Image.', 'wpforms-post-submissions' ),
			'disabled_option'   => wp_kses(
				__( 'This field is being used to upload the Post Featured Image. Some Field Options cannot be changed. You can change the Post Featured Image upload field by going to <strong>Settings » Post Submissions.</strong>', 'wpforms-post-submissions' ),
				[
					'strong' => [],
				]
			),
			'image_extensions'  => 'jpg,jpeg,png,gif,webp',
		];

		return $strings;
	}

	/**
	 * Force file upload fields connected to Post Submissions to use the
	 * WordPress media library.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form data.
	 * @param array $entry     Entry.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function override_file_uploads( $form_data, $entry ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$settings = $form_data['settings'];
		$fields   = $form_data['fields'];

		// Check for featured image.
		if ( ! empty( $settings['post_submissions_featured'] ) ) {
			$fields[ $settings['post_submissions_featured'] ]['media_library'] = '1';
		}

		// Check for files defined in custom post meta.
		if ( ! empty( $settings['post_submissions_meta'] ) ) {

			foreach ( $settings['post_submissions_meta'] as $id ) {

				if ( ! empty( $fields[ $id ]['type'] ) && $fields[ $id ]['type'] === 'file-upload' ) {
					$fields[ $id ]['media_library'] = '1';
				}
			}
		}

		$form_data['fields'] = $fields;

		return $form_data;
	}

	/**
	 * Validate and process the post submission form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    The fields that have been submitted.
	 * @param array $entry     The post data submitted by the form.
	 * @param array $form_data The information for the form.
	 * @param int   $entry_id  Entry ID.
	 *
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function process_post_submission( $fields, $entry, $form_data, $entry_id = 0 ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded, WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		$settings  = $form_data['settings'];
		$post_args = [];

		// Only process if enabled.
		if ( empty( $settings['post_submissions'] ) ) {
			return;
		}

		$entry_data = wpforms()->get( 'entry' )->get( $entry_id );

		// Post Title.
		if ( ! empty( $settings['post_submissions_title'] ) || ! empty( $fields[ $settings['post_submissions_title'] ]['value'] ) ) {
			$post_args['post_title'] = $fields[ $settings['post_submissions_title'] ]['value'];
		}

		// Post Content.
		if ( ! empty( $settings['post_submissions_content'] ) || ! empty( $fields[ $settings['post_submissions_content'] ]['value'] ) ) {
			$post_args['post_content'] = $fields[ $settings['post_submissions_content'] ]['value'];
		}

		// Post Excerpt.
		if ( ! empty( $settings['post_submissions_excerpt'] ) || ! empty( $fields[ $settings['post_submissions_excerpt'] ]['value'] ) ) {
			$post_args['post_excerpt'] = $fields[ $settings['post_submissions_excerpt'] ]['value'];
		}

		// Post Type.
		if ( ! empty( $settings['post_submissions_type'] ) ) {
			$post_args['post_type'] = $settings['post_submissions_type'];
		}

		// Post Status.
		if ( ! empty( $settings['post_submissions_status'] ) ) {
			$post_args['post_status'] = $settings['post_submissions_status'];
		}

		// Post Author.
		if ( ! empty( $settings['post_submissions_author'] ) ) {
			if ( $settings['post_submissions_author'] === 'current_user' ) {
				$post_args['post_author'] = $entry_data->user_id ?? get_current_user_id();

				if ( $post_args['post_author'] === 0 ) {
					$form                     = get_post( $form_data['id'], ARRAY_A );
					$post_args['post_author'] = $form['post_author'];
				}
			} else {
				$post_args['post_author'] = absint( $settings['post_submissions_author'] );
			}
		} else {
			$post_args['post_author'] = 0;
		}

		// Don't require post title/content to create new post.
		add_filter( 'wp_insert_post_empty_content', '__return_false' );

		// Do it.
		// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
		$post_id = wp_insert_post( apply_filters( 'wpforms_post_submissions_post_args', $post_args, $form_data, $fields ) );

		// Check for errors.
		if ( is_wp_error( $post_id ) ) {

			wpforms_log(
				'Post Submission Error',
				$post_id->get_error_message(),
				[
					'type'    => [ 'error' ],
					'parent'  => $entry_id,
					'form_id' => $form_data['id'],
				]
			);

			return;
		}

		// Featured Image.
		if ( ! empty( $settings['post_submissions_featured'] ) && ! empty( $fields[ $settings['post_submissions_featured'] ] ) ) {
			$file  = $fields[ $settings['post_submissions_featured'] ];
			$style = ! empty( $file['style'] ) ? $file['style'] : WPForms_Field_File_Upload::STYLE_CLASSIC;

			// Modern or classic file uploader?
			switch ( $style ) {
				case WPForms_Field_File_Upload::STYLE_CLASSIC:
					if ( ! empty( $file['attachment_id'] ) ) {

						update_post_meta( $post_id, '_thumbnail_id', $file['attachment_id'] );

						$filetype = wp_check_filetype( $file['file'] );

						// Attach the featured image to the post.
						wp_insert_attachment(
							[
								'ID'             => $file['attachment_id'],
								'post_parent'    => $post_id,
								'post_title'     => $this->get_wp_media_file_title( $file ),
								'guid'           => $file['value'],
								'post_mime_type' => $filetype['type'],
							]
						);
					}
					break;

				case WPForms_Field_File_Upload::STYLE_MODERN:
					if (
						! empty( $file['value_raw'][0]['attachment_id'] ) &&
						! empty( $file['value_raw'][0]['type'] ) &&
						strpos( $file['value_raw'][0]['type'], 'image' ) !== false
					) {
						update_post_meta( $post_id, '_thumbnail_id', $file['value_raw'][0]['attachment_id'] );

						// Attach the featured image to the post.
						wp_insert_attachment(
							[
								'ID'             => $file['value_raw'][0]['attachment_id'],
								'post_parent'    => $post_id,
								'post_title'     => $this->get_wp_media_file_title( $file['value_raw'][0] ),
								'guid'           => $file['value_raw'][0]['value'],
								'post_mime_type' => $file['value_raw'][0]['type'],
							]
						);
					}
					break;
			}

			unset( $file );
		}

		// Post Meta.
		if ( ! empty( $settings['post_submissions_meta'] ) ) {

			foreach ( $settings['post_submissions_meta'] as $key => $id ) {

				if ( empty( $key ) || ( empty( $id ) && $id !== '0' ) ) {
					continue;
				}

				if ( $fields[ $id ]['type'] === 'file-upload' ) {

					$style = ! empty( $fields[ $id ]['style'] ) ? $fields[ $id ]['style'] : WPForms_Field_File_Upload::STYLE_CLASSIC;

					switch ( $style ) {
						case WPForms_Field_File_Upload::STYLE_CLASSIC:
							if ( ! empty( $fields[ $id ]['attachment_id'] ) ) {

								update_post_meta( $post_id, esc_html( $key ), $fields[ $id ]['attachment_id'] );

								$filetype = wp_check_filetype( $fields[ $id ]['file'] );

								// Attach file to the post.
								wp_insert_attachment(
									[
										'ID'             => $fields[ $id ]['attachment_id'],
										'post_parent'    => $post_id,
										'post_title'     => $this->get_wp_media_file_title( $fields[ $id ] ),
										'guid'           => $fields[ $id ]['value'],
										'post_mime_type' => $filetype['type'],
									]
								);
							}
							break;

						case WPForms_Field_File_Upload::STYLE_MODERN:
							$file_attachments = [];

							if ( ! is_array( $fields[ $id ]['value_raw'] ) ) {
								continue 2; // Get out of the switch and continue 'post_submissions_meta' foreach.
							}

							foreach ( $fields[ $id ]['value_raw'] as $file ) {
								if ( empty( $file['attachment_id'] ) ) {
									continue;
								}

								$file_attachments[] = $file['attachment_id'];

								// Attach file to the post.
								wp_insert_attachment(
									[
										'ID'             => $file['attachment_id'],
										'post_parent'    => $post_id,
										'post_title'     => $this->get_wp_media_file_title( $file ),
										'guid'           => $file['value'],
										'post_mime_type' => $file['type'],
									]
								);
							}

							if ( ! empty( $file_attachments ) ) {
								// For compatibility with ACF File field need save as media ID.
								$file_attachments = 1 === count( $file_attachments )
									? array_shift( $file_attachments )
									: $file_attachments;

								update_post_meta( $post_id, esc_html( $key ), $file_attachments );
							}
							break;
					}
				} elseif ( isset( $fields[ $id ]['value'] ) && ! wpforms_is_empty_string( $fields[ $id ]['value'] ) ) {

					// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
					$value = apply_filters( 'wpforms_post_submissions_process_meta', $fields[ $id ]['value'], $key, $id, $fields, $form_data );

					$value = $this->maybe_adjust_events_calendar_meta_value( $value, $key, $fields[ $id ] );

					update_post_meta( $post_id, esc_html( $key ), $value );
				}
			}
		}

		// Events Calendar.
		$this->maybe_add_timezone_to_events_calendar_post( $post_id, $settings );

		// Post Taxonomies.
		foreach ( $fields as $field ) {

			if ( ! empty( $field['dynamic_taxonomy'] ) && ! empty( $field['dynamic_items'] ) ) {

				$terms = array_map( 'absint', explode( ',', $field['dynamic_items'] ) );

				foreach ( $terms as $key => $term ) {

					$exists = term_exists( $term, $field['dynamic_taxonomy'] );

					if ( ! ( $exists !== 0 && $exists !== null ) ) {
						unset( $terms[ $key ] );
					}
				}

				wp_set_object_terms( $post_id, $terms, $field['dynamic_taxonomy'] );
			}
		}

		// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
		do_action( 'wpforms_post_submissions_process', $post_id, $fields, $form_data, $entry_id );

		// Associate post id with entry.
		if ( ! empty( $entry_id ) ) {
			wpforms()->get( 'entry' )->update( $entry_id, [ 'post_id' => $post_id ], '', '', [ 'cap' => false ] );
		}
	}

	/**
	 * Maybe adjust the Events Calendar meta.
	 *
	 * @since 1.4.0
	 *
	 * @param string $value Meta value.
	 * @param string $key   Meta key.
	 * @param array  $field Field data.
	 *
	 * @return string
	 */
	private function maybe_adjust_events_calendar_meta_value( $value, $key, $field ) {

		if ( ! in_array( $key, [ '_EventStartDate', '_EventEndDate', '_EventStartDateUTC', '_EventEndDateUTC' ], true ) ) {
			return $value;
		}

		// Date/Time field is required.
		if ( empty( $field['unix'] ) ) {
			return $value;
		}

		// Set a date value with the required format.
		$value = gmdate( 'Y-m-d H:i:s', $field['unix'] );

		if ( class_exists( 'Tribe__Timezones' ) && in_array( $key, [ '_EventStartDateUTC', '_EventEndDateUTC' ], true ) ) {
			return Tribe__Timezones::to_utc( $value, Tribe__Timezones::wp_timezone_string() );
		}

		return $value;
	}

	/**
	 * Maybe add timezone to Events Calendar post.
	 *
	 * @since 1.6.0
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $settings Form settings.
	 */
	private function maybe_add_timezone_to_events_calendar_post( int $post_id, array $settings ) {

		if (
			class_exists( 'Tribe__Timezones' ) &&
			! empty( $settings['post_submissions_meta'] ) &&
			array_diff_key( $settings['post_submissions_meta'], [ '_EventStartDate', '_EventEndDate', '_EventStartDateUTC', '_EventEndDateUTC' ] )
		) {
			update_post_meta( $post_id, '_EventTimezone', Tribe__Timezones::wp_timezone_string() );
		}
	}

	/**
	 * Post Submissions settings register section.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sections  Section.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function settings_register( $sections, $form_data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$sections['post_submissions'] = esc_html__( 'Post Submissions', 'wpforms-post-submissions' );

		return $sections;
	}

	/**
	 * Post Submissions settings content.
	 *
	 * @since 1.0.0
	 *
	 * @param object $instance Object instance.
	 *
	 * @noinspection HtmlUnknownAttribute
	 */
	public function settings_content( $instance ) { //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-post_submissions">';

		printf(
			'<div class="wpforms-panel-content-section-title">%s</div>',
			esc_html__( 'Post Submissions', 'wpforms-post-submissions' )
		);

		// Toggle.
		wpforms_panel_field(
			'toggle',
			'settings',
			'post_submissions',
			$instance->form_data,
			esc_html__( 'Enable Post Submissions', 'wpforms-post-submissions' )
		);

		echo '<div id="wpforms-post-submissions-content-block">';

		// Post Title.
		wpforms_panel_field(
			'select',
			'settings',
			'post_submissions_title',
			$instance->form_data,
			esc_html__( 'Post Title', 'wpforms-post-submissions' ),
			[
				'field_map'   => [ 'text', 'name' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'wpforms-post-submissions' ),
			]
		);

		// Post Content.
		wpforms_panel_field(
			'select',
			'settings',
			'post_submissions_content',
			$instance->form_data,
			esc_html__( 'Post Content', 'wpforms-post-submissions' ),
			[
				'field_map'   => [ 'textarea', 'richtext' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'wpforms-post-submissions' ),
			]
		);

		// Post Excerpt.
		wpforms_panel_field(
			'select',
			'settings',
			'post_submissions_excerpt',
			$instance->form_data,
			esc_html__( 'Post Excerpt', 'wpforms-post-submissions' ),
			[
				'field_map'   => [ 'textarea', 'text', 'richtext' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'wpforms-post-submissions' ),
			]
		);

		// Post Featured Image.
		wpforms_panel_field(
			'select',
			'settings',
			'post_submissions_featured',
			$instance->form_data,
			esc_html__( 'Post Featured Image', 'wpforms-post-submissions' ),
			[
				'field_map'   => [ 'file-upload' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'wpforms-post-submissions' ),
			]
		);

		// Post Type.
		// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
		$types   = get_post_types( apply_filters( 'wpforms_post_submissions_post_type_args', [ 'public' => true ], $instance->form_data ), 'objects' );
		$options = [];

		unset( $types['attachment'] );

		foreach ( $types as $key => $type ) {
			$options[ $key ] = $type->labels->name;
		}

		wpforms_panel_field(
			'select',
			'settings',
			'post_submissions_type',
			$instance->form_data,
			esc_html__( 'Post Type', 'wpforms-post-submissions' ),
			[
				'options' => $options,
				'default' => 'post',
			]
		);

		// Post Status.
		wpforms_panel_field(
			'select',
			'settings',
			'post_submissions_status',
			$instance->form_data,
			esc_html__( 'Post Status', 'wpforms-post-submissions' ),
			[
				'tooltip' => esc_html__( 'Select the default status used for new posts.', 'wpforms-post-submissions' ),
				'options' => get_post_statuses(),
				'default' => 'pending',
			]
		);

		// Post Author.
		$user_args = [
			'number'  => 999,
			'fields'  => [ 'ID', 'display_name' ],
			'orderby' => 'display_name',
		];

		// phpcs:ignore WPForms.Comments.PHPDocHooks.RequiredHookDocumentation, WPForms.PHP.ValidateHooks.InvalidHookName
		$users   = new WP_User_Query( apply_filters( 'wpforms_post_submissions_user_args', $user_args ) );
		$options = [
			'current_user' => esc_html__( 'Current User', 'wpforms-post-submissions' ),
		];

		if ( ! empty( $users->results ) ) {
			foreach ( $users->results as $user ) {
				$options[ $user->ID ] = $user->display_name;
			}
		}

		wpforms_panel_field(
			'select',
			'settings',
			'post_submissions_author',
			$instance->form_data,
			esc_html__( 'Post Author', 'wpforms-post-submissions' ),
			[
				'tooltip'     => esc_html__( 'Select the post author used for new posts. Selecting Current User will use the current WordPress user that submits the form.', 'wpforms-post-submissions' ),
				'options'     => $options,
				'placeholder' => esc_html__( '--- Select User ---', 'wpforms-post-submissions' ),
			]
		);
		?>

		<div class="wpforms-field-map-table">
			<h3 id="custom-post-meta"><?php esc_html_e( 'Custom Post Meta', 'wpforms-post-submissions' ); ?></h3>
			<table aria-describedby="custom-post-meta">
				<tbody>
				<?php
				$fields = wpforms_get_form_fields( $instance->form_data );
				$meta   = ! empty( $instance->form_data['settings']['post_submissions_meta'] ) ? $instance->form_data['settings']['post_submissions_meta'] : [ false ];

				foreach ( $meta as $meta_key => $meta_field ) :
					$key  = $meta_field !== false ? preg_replace( '/[^a-zA-Z0-9_\-]/', '', $meta_key ) : '';
					$name = ! empty( $key ) ? 'settings[post_submissions_meta][' . $key . ']' : '';
					?>
					<tr>
						<td class="key">
							<input type="text" class="key-source" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php esc_attr_e( 'Enter meta key...', 'wpforms-post-submissions' ); ?>">
						</td>
						<td class="field">
							<select data-name="settings[post_submissions_meta][{source}]" name="<?php echo esc_attr( $name ); ?>"
							        class="key-destination wpforms-field-map-select" data-field-map-allowed="all-fields">
								<option value=""><?php esc_html_e( '--- Select Field ---', 'wpforms-post-submissions' ); ?></option>
								<?php
								if ( ! empty( $fields ) ) {
									foreach ( $fields as $field_id => $field ) {
										$label = ! empty( $field['label'] )
											? $field['label']
											: sprintf( /* translators: %d - field ID. */
												__( 'Field #%d', 'wpforms-post-submissions' ),
												absint( $field_id )
											);

										printf( '<option value="%s" %s>%s</option>', esc_attr( $field['id'] ), selected( $meta_field, $field_id, false ), esc_html( $label ) );
									}
								}
								?>
							</select>
						</td>
						<td class="actions">
							<a class="add" href="#"><i class="fa fa-plus-circle"></i></a>
							<a class="remove" href="#"><i class="fa fa-minus-circle"></i></a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php
		echo '</div></div>';
	}

	/**
	 * Generate an attachment title used in WordPress Media Library for an uploaded file.
	 *
	 * @since 1.4.0
	 *
	 * @param array $field_data Field data.
	 *
	 * @return string
	 */
	private function get_wp_media_file_title( $field_data ) {

		return $field_data['file_user_name'] ?? '';
	}
}
