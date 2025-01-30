<?php
use Elementor\Controls_Manager;
use Elementor\Settings;
use Elementor\Control_Repeater;
use Elementor\Plugin;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Controls\Fields_Map;
use ElementorPro\Modules\Forms\Classes\Integration_Base;
use ElementorPro\Classes\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WPF_Elementor_Forms extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.21.1
	 * @var string $slug
	 */

	public $slug = 'elementor-forms';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.21.1
	 * @var string $name
	 */
	public $name = 'Elementor Forms';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.21.1
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/lead-generation/elementor-forms/';


	/**
	 * Gets things started.
	 *
	 * @since   3.21.1
	 */

	public function init() {

		add_action( 'elementor_pro/init', array( $this, 'add_form_actions' ) );
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_elementor_forms_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_elementor_forms', array( $this, 'batch_step' ) );
	}


	/**
	 * Registers the form actions.
	 *
	 * @since 3.41.24
	 */
	public function add_form_actions() {

		if ( version_compare( ELEMENTOR_PRO_VERSION, '3.5.0', '>=' ) ) {
			\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->actions_registrar->register( new WPF_Elementor_Forms_Integration(), 'wpfusion' );
		} else {
			\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( 'wpfusion', new WPF_Elementor_Forms_Integration() );
		}
	}

	public function register_controls( $controls ) {
		$controls->register( new WPF_Elementor_Field_Mapping() );
	}

	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Get sent data from submissions table.
	 *
	 * @since 3.43.0
	 *
	 * @param integer $submission_id
	 * @return array
	 */
	private function get_sent_data( $submission_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `key`,`value` FROM {$wpdb->prefix}e_submissions_values WHERE `submission_id` = %d",
				$submission_id
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return;
		}

		return array_column( $results, 'value', 'key' );
	}

	/**
	 * Adds Elementor forms checkbox to available export options.
	 *
	 * @since 3.43.0
	 *
	 * @param array $options The options.
	 * @return array The options.
	 */

	public function export_options( $options ) {

		$options['elementor_forms'] = array(
			'label'         => 'Elementor Forms submissions',
			'process_again' => true,
			'title'         => 'Entries',
			// translators: %s CRM name.
			'tooltip'       => sprintf( __( 'Finds Elementor Forms entries that have not been successfully processed by WP Fusion and syncs them to %s based on their configured feeds.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;
	}

	/**
	 * Gets total list of entries to be processed
	 *
	 * @since 3.43.0
	 *
	 * @param array $args The args.
	 * @return array Submission IDs.
	 */

	public function batch_init( $args ) {

		global $wpdb;
		$query = "SELECT DISTINCT `submission_id` FROM {$wpdb->prefix}e_submissions_values";
		if ( ! empty( $args['skip_processed'] ) ) {
			$query .= " WHERE NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}e_submissions_values as t2 WHERE t2.submission_id = {$wpdb->prefix}e_submissions_values.submission_id AND t2.key = 'wpf_complete')";
		}

		$submissions_ids = $wpdb->get_results( $query, ARRAY_A );
		if ( ! empty( $submissions_ids ) ) {
			$submissions_ids = wp_list_pluck( $submissions_ids, 'submission_id' );
		}

		return $submissions_ids;
	}

	/**
	 * Processes submission.
	 *
	 * @since 3.43.0
	 *
	 * @param int $submission_id The submission ID.
	 */

	public function batch_step( $submission_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `post_id`,`element_id` FROM {$wpdb->prefix}e_submissions WHERE `type` = 'submission' AND `id` = %d",
				$submission_id
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return;
		}

		$el_data = json_decode( get_post_meta( $results[0]['post_id'], '_elementor_data', true ), true );
		if ( empty( $el_data ) ) {
			return;
		}

		$form = '';
		foreach ( $el_data as $value ) {
			foreach ( $value['elements'] as $element ) {
				if ( $element['widgetType'] === 'form' && $element['id'] === $results[0]['element_id'] ) {
					$form = $element;
				}
			}
		}

		if ( $form == '' ) {
			return;
		}

		$sent_data          = $this->get_sent_data( $submission_id );
		$record             = new Form_Record( $sent_data, $form );
		$wpf_el_forms_class = new WPF_Elementor_Forms_Integration();
		$wpf_el_forms_class->run( $record );
	}
}

new WPF_Elementor_Forms();


/**
 * Form field mapping.
 *
 * @since 3.41.24
 */
class WPF_Elementor_Field_Mapping extends Control_Repeater {

	const CONTROL_TYPE = 'wpf_fields_map';

	/**
	 * Get control type.
	 *
	 * Retrieve the control type, in this case `wpf_fields_map`.
	 *
	 * @since 3.41.24
	 *
	 * @return string Control type.
	 */
	public function get_type() {
		return self::CONTROL_TYPE;
	}

	/**
	 * Gets the default settings.
	 *
	 * @since 3.41.24
	 */
	protected function get_default_settings() {
		return array_merge(
			parent::get_default_settings(),
			array(
				'render_type' => 'none',
				'fields'      => array(
					array(
						'name' => 'local_id',
						'type' => Controls_Manager::HIDDEN,
					),
					array(
						'name' => 'remote_id',
						'type' => Controls_Manager::SELECT,
					),
				),
			)
		);
	}

	/**
	 * Enqueue the admin scripts.
	 *
	 * @since 3.41.24
	 */
	public function enqueue() {
		wp_enqueue_script( 'wpf-elementor-forms-script', WPF_DIR_URL . 'assets/js/wpf-elementor-forms.js', array( 'jquery' ), WP_FUSION_VERSION, true );

		wp_localize_script(
			'wpf-elementor-forms-script',
			'wpfElementorObject',
			array(
				'fields' => ( new WPF_Elementor_Forms_Integration() )->get_fields(),
			)
		);
	}
}


class WPF_Elementor_Forms_Integration extends Integration_Base {

	/**
	 * Get action ID.
	 *
	 * @since 3.41.24
	 * @return string ID
	 */
	public function get_name() {
		return 'wpfusion';
	}

	/**
	 * Get action label.
	 *
	 * @since 3.41.24
	 * @return string Label
	 */
	public function get_label() {
		return __( 'WP Fusion', 'wp-fusion' );
	}

	/**
	 * Get CRM fields.
	 *
	 * @since 3.41.24
	 * @return array fields
	 */

	public function get_fields() {

		$fields = array();

		$available_fields = wp_fusion()->settings->get_crm_fields_flat();

		foreach ( $available_fields as $field_id => $field_label ) {

			$remote_required = false;

			if ( 'Email' === $field_label ) {
				$remote_required = true;
			}

			$fields[] = array(
				'remote_label'    => $field_label,
				'remote_type'     => 'text',
				'remote_id'       => $field_id,
				'remote_required' => $remote_required,
			);

		}

		// Add as tag
		if ( wp_fusion()->crm->supports( 'add_tags' ) ) {

			$fields[] = array(
				'remote_label'    => '+ Create tag(s) from',
				'remote_type'     => 'text',
				'remote_id'       => 'add_tag_e',
				'remote_required' => false,
			);

		}

		return $fields;
	}


	/**
	 * Registers settings.
	 *
	 * @since 3.41.24
	 */
	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_wpfusion',
			array(
				'label'     => 'WP Fusion',
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		$widget->add_control(
			'wpf_apply_tags',
			array(
				'label'       => __( 'Apply Tags', 'wp-fusion' ),
				'description' => sprintf( __( 'The selected tags will be applied in %s when the form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => wp_fusion()->settings->get_available_tags_flat( false ),
				'multiple'    => true,
				'label_block' => true,
				'show_label'  => true,
			)
		);

		// if the CRM supports lists:

		if ( wp_fusion()->crm->supports( 'lists' ) ) {

			$widget->add_control(
				'wpf_apply_lists',
				array(
					'label'       => __( 'Apply Lists', 'wp-fusion' ),
					'description' => sprintf( __( 'The selected lists will be applied in %s when the form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ),
					'type'        => Controls_Manager::SELECT2,
					'options'     => wpf_get_option( 'available_lists', array() ),
					'multiple'    => true,
					'label_block' => true,
					'show_label'  => true,
				)
			);
		}

		$widget->add_control(
			'wpf_add_only',
			array(
				'label'       => __( 'Add Only', 'wp-fusion' ),
				'description' => __( 'Only add new contacts, don\'t update existing ones.', 'wp-fusion' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_block' => false,
				'show_label'  => true,
			)
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'local_id',
			array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			)
		);

		$repeater->add_control(
			'remote_id',
			array(
				'type'    => Controls_Manager::SELECT,
				'default' => '',
			)
		);

		$widget->add_control(
			'wpfusion_fields_map',
			array(
				'label'       => __( 'Field Mapping', 'elementor-pro' ),
				'type'        => WPF_Elementor_Field_Mapping::CONTROL_TYPE,
				'separator'   => 'before',
				'render_type' => 'none',
				'fields'      => $repeater->get_controls(),
			)
		);

		$widget->end_controls_section();
	}


	/**
	 * Unsets WPF settings on export
	 *
	 * @access  public
	 * @return  object Element
	 */

	public function on_export( $element ) {

		unset(
			$element['settings']['wpfusion_fields_map'],
			$element['settings']['wpf_apply_tags'],
			$element['settings']['wpf_apply_lists']
		);

		return $element;
	}

	/**
	 * Run
	 * Process form submission.
	 *
	 * @since 3.21.1
	 * @since 3.43.3 Switched from "sent_data" to "fields" for submitted data.
	 *
	 * @param object      $record       Elementor form record.
	 * @param object|bool $ajax_handler Ajax handler or false.
	 */
	public function run( $record, $ajax_handler = false ) {

		$sent_data     = $record->get( 'fields' );
		$form_settings = $record->get( 'form_settings' );
		$update_data   = array();
		$email_address = false;

		if ( ! empty( $form_settings['wpfusion_fields_map'] ) ) {

			foreach ( $form_settings['wpfusion_fields_map'] as $i => $field ) {

				if ( ! empty( $field['local_id'] ) && ! empty( $sent_data[ $field['local_id'] ] ) && ! empty( $field['remote_id'] ) ) {

					$value = $sent_data[ $field['local_id'] ]['value'];

					if ( false !== strpos( $field['remote_id'], 'add_tag_' ) ) {

						// Don't run the filter on dynamic tagging inputs.
						$update_data[ $field['remote_id'] ] = $value;
						continue;

					}

					// Let's get the type.

					$type = 'text';

					if ( isset( $form_settings['form_fields'][ $i ]['field_type'] ) ) {
						$type = $form_settings['form_fields'][ $i ]['field_type'];

						if ( 'acceptance' === $type ) {
							$type = 'checkbox';
						} elseif ( 'time' === $type ) {
							$type = 'date';
						} elseif ( 'textarea' === $type || 'hidden' === $type ) {
							$type = 'text';
						} elseif ( true === boolval( $form_settings['form_fields'][ $i ]['allow_multiple'] ) || ( 'checkbox' === $type && false !== strpos( strval( $form_settings['form_fields'][ $i ]['field_options'] ), PHP_EOL ) ) ) {
							$type = 'multiselect';
						}
					}

					if ( ! empty( $sent_data[ $field['local_id'] ]['raw_value'] ) && is_array( $sent_data[ $field['local_id'] ]['raw_value'] ) ) {
						$value = $sent_data[ $field['local_id'] ]['raw_value'];
						$type  = 'multiselect';
					}

					$update_data[ $field['remote_id'] ] = apply_filters( 'wpf_format_field_value', $value, $type, $field['remote_id'] );

					// For determining the email address, we'll try to find a field
					// mapped to the main lookup field in the CRM, but if not we'll take
					// the first email address on the form.

					if ( is_string( $value ) && is_email( $value ) && wpf_get_lookup_field() === $field['remote_id'] ) {
						$email_address = $value;
					} elseif ( false === $email_address && is_string( $value ) && is_email( $value ) ) {
						$email_address = $value;
					}
				}
			}
		}

		if ( false === $email_address ) {

			// Try to find any email address, in case it wasn't mapped.
			foreach ( $sent_data as $field ) {

				if ( is_string( $field['value'] ) && is_email( $field['value'] ) ) {
					$email_address = $field['value'];
					break;
				}
			}
		}

		if ( isset( $form_settings['wpf_add_only'] ) && 'yes' == $form_settings['wpf_add_only'] ) {
			$add_only = true;
		} else {
			$add_only = false;
		}

		if ( empty( $form_settings['wpf_apply_tags'] ) ) {
			$form_settings['wpf_apply_tags'] = array();
		}

		$form_settings['wpf_apply_tags'] = array_map( 'htmlspecialchars_decode', $form_settings['wpf_apply_tags'] );

		if ( empty( $update_data ) && empty( $form_settings['wpf_apply_tags'] ) ) {
			return;
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $form_settings['wpf_apply_tags'],
			'apply_lists'      => wp_fusion()->crm->supports( 'lists' ) && isset( $form_settings['wpf_apply_lists'] ) ? $form_settings['wpf_apply_lists'] : array(),
			'add_only'         => $add_only,
			'integration_slug' => 'elementor_forms',
			'integration_name' => 'Elementor Forms',
			'form_id'          => null,
			'form_title'       => null,
			'form_edit_link'   => null,
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		// Get submission id from table using user email.
		global $wpdb;
		$submission_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `submission_id` FROM {$wpdb->prefix}e_submissions_values WHERE `value` = %s AND `key` = 'email'",
				$email_address
			)
		);

		if ( intval( $submission_id ) !== 0 ) {
			$wpdb->insert(
				$wpdb->prefix . 'e_submissions_values',
				array(
					'submission_id' => $submission_id,
					'key'           => 'wpf_complete',
					'value'         => current_time( 'Y-m-d H:i:s' ),
				),
				array( '%d', '%s', '%s' )
			);

			$wpdb->insert(
				$wpdb->prefix . 'e_submissions_values',
				array(
					'submission_id' => $submission_id,
					'key'           => 'wpf_contact_id',
					'value'         => $contact_id,
				),
				array( '%d', '%s', '%s' )
			);

		}

		// Return after login + auto login.

		if ( isset( $_COOKIE['wpf_return_to'] ) && doing_wpf_auto_login() && $ajax_handler ) {

			$post_id = absint( $_COOKIE['wpf_return_to'] );
			$url     = get_permalink( $post_id );

			setcookie( 'wpf_return_to', '', time() - ( 15 * 60 ) );

			if ( ! empty( $url ) && wpf_user_can_access( $post_id ) ) {

				$ajax_handler->add_response_data( 'redirect_url', $url );

			}
		}
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */

	public function handle_panel_request( array $data ) { }

	/**
	 * Get field map control options.
	 *
	 * @since  3.37.13
	 *
	 * @return array The fields map control options.
	 */
	protected function get_fields_map_control_options() {
		return array(
			'default'   => $this->get_fields(),
			'condition' => array(),
		);
	}
}
