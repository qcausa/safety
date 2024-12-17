<?php

namespace WPFormsUserRegistration\SmartTags\Helpers;

// phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement
use WP_User;

/**
 * SmartTags Helper class.
 *
 * @since 2.0.0
 */
class Helper {

	/**
	 * Registered user.
	 *
	 * @since 2.0.0
	 *
	 * @var false|WP_User
	 */
	private static $user = false;

	/**
	 * Set user.
	 *
	 * @since 2.0.0
	 *
	 * @param int|WP_User $user User to set.
	 */
	public static function set_user( $user ) {

		self::$user = is_object( $user ) ? $user : get_user_by( 'id', $user );
	}

	/**
	 * Get User from submitted registration form.
	 *
	 * @since 2.0.0
	 *
	 * @return false|WP_User
	 */
	public static function get_user() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( self::$user ) {
			return self::$user;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['wpforms']['id'] ) ) {
			return false;
		}

		$form_obj = wpforms()->get( 'form' );

		if ( ! $form_obj ) {
			return false;
		}

		$form_data = $form_obj->get( absint( $_POST['wpforms']['id'] ), [ 'content_only' => true ] );

		if ( isset( $form_data['settings']['registration_username'] ) && ! empty( $_POST['wpforms']['fields'][ $form_data['settings']['registration_username'] ] ) && ! is_array( $_POST['wpforms']['fields'][ $form_data['settings']['registration_username'] ] ) ) {
			self::$user = get_user_by( 'login', sanitize_user( wp_unslash( $_POST['wpforms']['fields'][ $form_data['settings']['registration_username'] ] ) ) );

			return self::$user;
		}

		if ( isset( $form_data['settings']['registration_email'] ) && ! empty( $_POST['wpforms']['fields'][ $form_data['settings']['registration_email'] ] ) ) {
			$field_value        = wp_unslash( $_POST['wpforms']['fields'][ $form_data['settings']['registration_email'] ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$registration_email = isset( $field_value['primary'] ) ? $field_value['primary'] : $field_value;
			self::$user         = get_user_by( 'email', sanitize_email( $registration_email ) );

			return self::$user;
		}

		$email_field = wpforms_get_form_fields_by_meta( 'nickname', 'email', $form_data );
		$email_field = reset( $email_field );

		if ( $email_field && ! empty( $_POST['wpforms']['fields'][ $email_field['id'] ] ) ) {
			self::$user = get_user_by( 'email', sanitize_email( wp_unslash( $_POST['wpforms']['fields'][ $email_field['id'] ] ) ) );

			return self::$user;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return false;
	}

	/**
	 * Get registered user ID from entry ID.
	 *
	 * @since 2.6.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return int Registered user ID.
	 */
	public static function get_entry_registered_user_id( $entry_id ) {

		// Skip if entry ID is empty in case of login, registration or reset password form when entry is not created.
		if ( empty( $entry_id ) ) {
			return 0;
		}

		$registered_user_id = wpforms()->get( 'entry_meta' )->get_meta(
			[
				'entry_id' => $entry_id,
				'type'     => 'registered_user_id',
				'number'   => 1,
			]
		);

		return $registered_user_id[0]->data ?? 0;
	}
}
