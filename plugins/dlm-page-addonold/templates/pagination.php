<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

echo paginate_links( array(
	'base'    => add_query_arg( 'dlpage', '%#%' ),
	'format'  => '?dlpage=%#%',
	'current' => max( 1, ( isset( $_GET['dlpage'] ) ? $_GET['dlpage'] : 1 ) ),
	'total'   => $pages,
	'type'    => 'list'
) );