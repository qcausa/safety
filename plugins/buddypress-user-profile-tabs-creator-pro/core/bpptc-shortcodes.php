<?php
// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

// Make links relative.
add_shortcode( 'bpptc_make_links_relative', function ( $atts, $content = '' ) {

	$atts = shortcode_atts( array(
		'from'     => '', // complete url.
		'to'       => '', // absolute url(relative to profile).
		'relative' => '', // optional, profile relative slug
	), $atts
	);

	if ( empty( $atts['from'] ) || ( empty( $atts['to'] ) && empty( $atts['relative'] ) ) ) {
		return $content;
	}

	$replace = '';
	if ( $atts['to'] ) {
		$replace = trim( $atts['to'] );
	} elseif ( $atts['relative'] ) {
		$replace = trailingslashit( bp_displayed_user_domain() . $atts['relative'] );
	}

	$content = do_shortcode( $content );

	return str_replace( $atts['from'], $replace, $content );
} );