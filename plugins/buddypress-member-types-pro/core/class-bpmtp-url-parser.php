<?php
/**
 * Url parser to parse tokens and generate absolute url.
 *
 * @package    BuddyPress Member Types Pro
 * @subpackage Core
 * @copyright  Copyright (c) 2018, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

// Do not show directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper for parsing our urls.
 */

class BPMTP_URL_Parser {

	/**
	 * Parse a url and return the parsed url.
	 *
	 * @param int    $user_id user id
	 * @param string $url url to be parsed.
	 *
	 * @return mixed
	 */
	public static function parse( $user_id, $url ) {

		if ( empty( $url ) ) {
			return $url;
		}

		$map = self::get_tokens_map( $user_id );

		$tokens    = array_keys( $map );
		$replacers = array_values( $map );

		$url = str_replace( $tokens, $replacers, $url );

		return $url;

	}

	/**
	 * Get the map to be replaced.
	 *
	 * @param int $user_id user id.
	 *
	 * @return array
	 */
	public static function get_tokens_map( $user_id ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return array();
		}

		$member_type = bp_get_member_type( $user_id, true );

		$map = array(
			'[user_id]'          => $user->ID,
			'[user_login]'       => $user->user_login,
			'[user_nicename]'    => $user->user_nicename,
			'[username]'         => bpmtp_get_user_slug( $user_id, $user->user_nicename, $user->user_login ),
			'[user_profile_url]' => bpmtp_get_user_url( $user_id ),
			'[site_url]'         => site_url( '/' ),
			'[network_url]'      => network_home_url( '/' ),
			'[member_type]'      => $member_type ? $member_type : '',
		);

		return apply_filters( 'bpmtp_url_tokens_map', $map, $user );
	}
}
