<?php

namespace WPFormsUserRegistration\SmartTags;

use WPForms\SmartTags\SmartTag\SmartTag;
use WPFormsUserRegistration\SmartTags\Helpers\Helper;

/**
 * Class UserRegistrationEmail.
 *
 * @since 2.0.0
 *
 * @noinspection PhpUnused
 */
class UserRegistrationEmail extends SmartTag {

	/**
	 * Get smart tag value.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $form_data Form data.
	 * @param array  $fields    List of fields.
	 * @param string $entry_id  Entry ID.
	 *
	 * @return string
	 */
	public function get_value( $form_data, $fields = [], $entry_id = '' ) {

		$user_id = Helper::get_entry_registered_user_id( $entry_id );

		if ( $user_id ) {
			Helper::set_user( $user_id );
		}

		$user = Helper::get_user();

		return $user->user_email ?? '';
	}
}
