<?php
// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Member Type shortcodes.
 */
class BPMTP_Shortcode_Helper {

	/**
	 * Temporary store for the args to currently executing shortcode
	 *
	 * @var array
	 */
	private $args;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup shortcodes
	 */
	private function setup() {
		add_shortcode( 'bpmtp-members-list', array( $this, 'members_list' ) );
		add_shortcode( 'bpmtp-show-if-member-type', array( $this, 'show_if' ) );
		add_shortcode( 'bpmtp-show-if-not-member-type', array( $this, 'show_if_not' ) );
		add_shortcode( 'bpmtp-hide-if-member-type', array( $this, 'hide_if' ) );
		add_shortcode( 'bpmtp-hide-if-not-member-type', array( $this, 'hide_if_not' ) );
	}

	/**
	 * Shortcode callback
	 *
	 * @param array  $args shortcode atts.
	 * @param string $content nothing.
	 *
	 * @return string
	 */
	public function members_list( $args, $content = '' ) {

		$atts = shortcode_atts( array(
			'type'     => 'active',
			'page'     => 1,
			'per_page' => 20,
			'max'      => false,

			'include' => false,
			// Pass a user_id or a list (comma-separated or array) of user_ids to only show these users.
			'exclude' => false,
			// Pass a user_id or a list (comma-separated or array) of user_ids to exclude these users.
			'user_id'             => false,
			// Pass a user_id to only show friends of this user.
			'member_type'         => '',
			'member_type__in'     => '',
			'member_type__not_in' => '',
			'search_terms'        => '',

			'meta_key'   => false,
			// Only return users with this usermeta.
			'meta_value' => false,
			// Only return users where the usermeta value matches. Requires meta_key.
			'populate_extras' => true,
			// Fetch usermeta? Friend count, last active etc.
			'compat'          => 1,

		), $args, 'bpmtp-members-list' );

		$compat = $atts['compat'];

		unset( $atts['compat'] );

		$this->args = $atts;

		add_filter( 'bp_after_has_members_parse_args', array( $this, 'filter' ) );

		ob_start();

		if ( $compat ) {
			echo "<div id='buddypress'>";
		}

		echo '<div class="members bpmtp-shortcode-members">';

		bp_get_template_part( 'members/members-loop' );

		echo '</div>'; // end of .members.
		echo '<div class="bpmtp-shortcode-clear"></div>';
		if ( $compat ) {
			echo '</div>'; // end of #buddypress.
		}

		echo '<style type="text/css">.bpmtp-shortcode-clear{clear:both;}</style>';

		$content = ob_get_clean();

		remove_filter( 'bp_after_has_members_parse_args', array( $this, 'filter' ) );

		return $content;
	}

	/**
	 * Filter bp_has_members() args to make it work for our member type
	 *
	 * @param array $args parsed bp_has_members() args.
	 *
	 * @return array
	 */
	public function filter( $args ) {

		$args = wp_parse_args( $this->args, $args );

		if ( ! $this->args['user_id'] ) {
			$args['user_id'] = false; // workaround for user page.
		}
		// remove temp var.
		unset( $this->args );

		return $args;
	}

	/**
	 * [bpmtp-show-if-member-type in="type1,type2,..."]
	 *
	 * Show/Hide contents based on member type.
	 *
	 * @param array  $atts atts.
	 * @param string $content content to hide.
	 *
	 * @return string
	 */
	public function show_if( $atts = array(), $content = '' ) {

		if ( is_super_admin() ) {
			return do_shortcode( $content );
		}


		$atts = shortcode_atts( array( 'in' => '' ), $atts, 'bpmtp-show-if-member-type' );


		if ( empty( $atts['in'] ) || ! is_user_logged_in() || ! function_exists( 'buddypress' ) ) {
			return '';
		}

		$in = $atts['in'];

		$in = explode( ',', $in );
		$in = $this->validate_member_types( $in );

		if ( empty( $in ) ) {
			return '';
		}

		$member_types = bp_get_member_type( bp_loggedin_user_id(), false );

		$common_types = array_intersect( $member_types, $in );
		// On of the member type of user is.
		if ( ! empty( $common_types ) ) {
			return do_shortcode( $content );
		}

		return '';
	}

	/**
	 * [bpmtp-show-if-not-member-type in="type1,type2,..."]
	 *
	 * Show/Hide contents based on member type.
	 *
	 * @param array  $atts atts.
	 * @param string $content content to hide.
	 *
	 * @return string
	 */
	public function show_if_not( $atts = array(), $content = '' ) {
		$atts = shortcode_atts( array( 'in' => '' ), $atts, 'bpmtp-show-if-not-member-type' );


		if ( empty( $atts['in'] ) || ! is_user_logged_in() || is_super_admin() || ! function_exists( 'buddypress' ) ) {
			return do_shortcode( $content );
		}

		$in = $atts['in'];

		$in = explode( ',', $in );
		$in = $this->validate_member_types( $in );

		if ( empty( $in ) ) {
			return do_shortcode( $content );
		}

		$member_types = bp_get_member_type( bp_loggedin_user_id(), false );

		$common_types = array_intersect( $member_types, $in );
		// On of the member type of user is.
		if ( ! empty( $common_types ) ) {
			return '';
		}

		return do_shortcode( $content );

	}

	/**
	 * [bpmtp-hide-if-member-type in="type1,type2,..."]
	 *
	 * Show/Hide contents based on member type.
	 *
	 * @param array  $atts atts.
	 * @param string $content content to hide.
	 *
	 * @return string
	 */
	public function hide_if( $atts = array(), $content = '' ) {
		return $this->show_if_not( $atts, $content );
	}

	/**
	 * [bpmtp-hide-if-not-member-type in="type1,type2,..."]
	 *
	 * Show/Hide contents based on member type.
	 *
	 * @param array  $atts atts.
	 * @param string $content content to hide.
	 *
	 * @return string
	 */
	public function hide_if_not( $atts = array(), $content = '' ) {
		return $this->show_if( $atts, $content );
	}

	/**
	 * Validate the member types.
	 *
	 * @param array $list_types array of member type names.
	 *
	 * @return array valid array of member type names.
	 */
	private function validate_member_types( $list_types ) {

		$valid_list   = array();
		$member_types = bp_get_member_types( array() );

		foreach ( $list_types as $type ) {
			$type = trim( $type );
			if ( in_array( $type, $member_types ) ) {
				$valid_list[] = $type;
			}
		}

		return $valid_list;
	}


}

// instantiate.
new BPMTP_Shortcode_Helper();
