<?php
/**
 * Manage Redirection settings and profile tab option.
 *
 * @package    BuddyPress Member Types Pro
 * @subpackage Core
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Redirection manager.
 */
class BPMTP_Redirection_manager {

	/**
	 * Boot.
	 */
	public static function boot() {
		$self = new self();
		$self->setup();
	}

	/**
	 * Setup hooks.
	 */
	public function setup() {
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 111, 3 );
		// compatibility with ajax login v1.x.
		add_filter( 'bpajaxr_redirect_url', array( $this, 'ajax_reg_activation_redirect' ), 1001, 2 );
		add_filter( 'bpdev_autoactivate_redirect_url', array( $this, 'ajax_reg_activation_redirect' ), 1001, 2 );
		add_filter( 'rh_custom_redirect_for_reg', array( $this, 'ajax_reg_activation_redirect' ), 1001, 2 );

		// Subway plugin compatibility.
		add_filter( 'subway_login_redirect', array( $this, 'subway_login_redirect' ), 1001, 2 );

		add_action( 'bp_core_activated_user', array( $this, 'on_account_activation' ) );

		// must be called before '5'(canonical stack setup).
		add_action( 'bp_setup_canonical_stack', array( $this, 'setup_default_component' ), 8 );

		add_filter( 'ghostpool_login_redirect', array( $this, 'gp_redirect' ), 1001, 2 );
		add_filter( 'rh_custom_redirect_for_login', array( $this, 'gp_redirect' ), 1001, 2 );
		// boombox theme compatibility.
		add_filter( 'snax_login_redirect_url', array( $this, 'gp_redirect' ), 1001, 2 );
	}


	/**
	 * Calculate the url to be redirected on login.
	 *
	 * @param string  $redirect_to_calculated calculated redirect.
	 * @param string  $redirect_url_specified specified redirect.
	 * @param WP_User $user user object.
	 *
	 * @return string
	 */
	public function login_redirect( $redirect_to_calculated, $redirect_url_specified, $user ) {

		if ( ! $user || is_wp_error( $user ) ) {
			return $redirect_to_calculated;
		}

		$redirect = $this->get_redirect_url( $user->ID, 'login' );

		if ( $redirect ) {
			$redirect_to_calculated = $redirect;
		}

		return $redirect_to_calculated;
	}

	/**
	 * Redirect for login via Subway plugin.
	 *
	 * @param string  $redirect_url where to redirect.
	 * @param WP_User $user user object.
	 *
	 * @return string
	 */
	public function subway_login_redirect( $redirect_url, $user = null ) {

		if ( ! $user || is_wp_error( $user ) ) {
			return $redirect_url;
		}

		$redirect = $this->get_redirect_url( $user->ID, 'login' );

		if ( $redirect ) {
			$redirect_url = $redirect;
		}

		return $redirect_url;
	}

	/**
	 * Filter the redirect link for activation when using ajax register plugin.
	 *
	 * @param string $where url.
	 * @param int    $user_id user id.
	 *
	 * @return string
	 */
	public function ajax_reg_activation_redirect( $where, $user_id ) {

		$redirect = $this->get_redirect_url( $user_id, 'activation' );

		if ( $redirect ) {
			$where = $redirect;
		}

		return $where;
	}

	/**
	 * Activation redirect.
	 *
	 * @param int $user_id user id.
	 */
	public function on_account_activation( $user_id ) {

		if ( defined( 'DOING_AJAX' ) ) {
			return;// don't modify ajax requests.
		}
		// if our auto activate plugin is active, do not redirect
		// let autoactivate do it and we hook to the redirect.
		if ( class_exists( 'BPDev_Account_Auto_Activator' ) ) {
			return;
		}

		$url = $this->get_redirect_url( $user_id, 'activation' );
		if ( ! $url ) {
			return;
		}

		bp_core_redirect( $url );
	}

	/**
	 * Setup default component for displayed user
	 * 1. Logged Member type has the criteria 'loggedin', It gets highest priority.
	 * 2. Displayed Member type and Criteria = displayed, It gets second priority
	 * 3. If displayed member has 'loggedin' or 'flexible' set as criteria, logged/displayed selected tab will be used.
	 */
	public function setup_default_component() {

		if ( ! bp_is_user() ) {
			return;
		}

		if ( defined( 'BP_DEFAULT_COMPONENT' ) ) {
			return;
		}

		$slug                = '';
		$logged_mtype_object = null;

		$displayed_member_type = bp_get_member_type( bp_displayed_user_id(), true );
		$logged_member_type    = is_user_logged_in() ? bp_get_member_type( bp_loggedin_user_id(), true ) : '';

		$logged_mtype_object    = $logged_member_type ? bpmtp_get_member_type_entry( $logged_member_type ) : null;
		$displayed_mtype_object = $displayed_member_type ? bpmtp_get_member_type_entry( $displayed_member_type ) : null;

		// if none of the member type exists, just return.
		if ( ! $logged_mtype_object && ! $displayed_mtype_object ) {
			return;
		}

		// if logged in member type exists and the criteria is logged in, setup it.
		if ( $logged_mtype_object && 'loggedin' == $logged_mtype_object->default_profile_tab_criteria ) {
			$slug = $logged_mtype_object->default_profile_tab_slug;
			if ( $slug ) {
				define( 'BP_DEFAULT_COMPONENT', $slug );
			}

			return; // all done.
		}

		// if we are here, either logged in mtype does not exist or the criteria is not logged.
		// in this case, the displayed type must exist and must have a criteria set.
		if ( ! $displayed_mtype_object || ! $displayed_mtype_object->default_profile_tab_criteria ) {
			return;
		}

		// if displayed member type is set as criteria, do it.
		if ( 'displayed' == $displayed_mtype_object->default_profile_tab_criteria ) {
			$slug = $displayed_mtype_object->default_profile_tab_slug;
			if ( $slug ) {
				define( 'BP_DEFAULT_COMPONENT', $slug );
			}

			return; // all done.
		}

		// if we are here, the criteria for displayed is set to loggedin or flexible.
		// in both these cases, if a user is logged in, his member type is used.
		if ( is_user_logged_in() ) {
			$slug = $logged_mtype_object ? $logged_mtype_object->default_profile_tab_slug : '';
		} elseif ( 'flexible' === $displayed_mtype_object->default_profile_tab_criteria ) {
			$slug = $displayed_mtype_object->default_profile_tab_slug;
		}

		if ( $slug ) {
			define( 'BP_DEFAULT_COMPONENT', $slug );
		}

	}

	/**
	 * Get the parsed redirect url.
	 *
	 * @param int    $user_id user id.
	 * @param string $type type.
	 *
	 * @return mixed|string
	 */
	private function get_redirect_url( $user_id, $type = 'login' ) {

		if ( ! $user_id ) {
			return '';
		}

		$member_type = bp_get_member_type( $user_id, true );

		if ( ! $member_type ) {
			return '';
		}

		$mtp_details = bpmtp_get_member_type_entry( $member_type );

		if ( ! $mtp_details ) {
			return '';
		}

		if ( 'login' == $type ) {
			$dynamic_url = $this->is_first_login( $user_id ) && $mtp_details->first_login_redirect_url ? $mtp_details->first_login_redirect_url : $mtp_details->login_redirect_url;
		} else {
			$dynamic_url = $mtp_details->activation_redirect_url;
		}

		if ( ! $dynamic_url ) {
			return '';
		}

		return BPMTP_URL_Parser::parse( $user_id, $dynamic_url );
	}

	/**
	 * Implement GhostPool's idiotic redirect.
	 *
	 * @param string  $redirect redirect.
	 * @param WP_User $user user.
	 *
	 * @return string
	 */
	public function gp_redirect( $redirect, $user ) {

		$redirect_url = $this->get_redirect_url( $user->ID, 'login' );
		// if we have a redirect setup, let us update.
		if ( $redirect_url ) {
			$redirect = $redirect_url;
		}

		return $redirect;
	}

	/**
	 * Is user new?
	 *
	 * @param int $user_id user id.
	 *
	 * @return bool
	 */
	private function is_first_login( $user_id ) {
		// check for user's last activity.
		$last_activity =  bp_get_user_last_activity( $user_id );

		if ( empty( $last_activity ) ) {
			// it is the first login
			// update redirect url
			// I am redirecting to user's profile here
			// you may change it to anything
			return true;
		}

		return false;
	}
}

// Boot.
BPMTP_Redirection_manager::boot();
