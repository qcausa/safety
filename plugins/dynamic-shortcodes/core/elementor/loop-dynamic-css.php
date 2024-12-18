<?php

/*
 * @copyright Elementor
 * @license GPLv3
 * This code is integrated due to external constraints and to achieve interoperability
 */


namespace DynamicShortcodes\Core\Elementor;

use \Elementor\Core\DynamicTags\Dynamic_CSS;
use \Elementor\Core\Files\CSS\Post;

class Loop_Dynamic_CSS extends Dynamic_CSS {

	private $post_id_for_data;

	public function __construct( $post_id, $post_id_for_data ) {

		$this->post_id_for_data = $post_id_for_data;

		$post_css_file = Post::create( $post_id_for_data );

		parent::__construct( $post_id, $post_css_file );
	}

	public function get_post_id_for_data() {
		return $this->post_id_for_data;
	}

	public static function print_dynamic_css( $post_id, $post_id_for_data ) {
		$document = \Elementor\Plugin::instance()->documents->get_doc_for_frontend( $post_id_for_data );
		if ( ! $document ) {
			return;
		}
		\Elementor\Plugin::instance()->documents->switch_to_document( $document );
		$css_file = Loop_Dynamic_CSS::create( $post_id, $post_id_for_data );
		$post_css = $css_file->get_content();
		if ( empty( $post_css ) ) {
			return;
		}
		$css = '';
		$css = str_replace( '.elementor-' . $post_id, '.e-loop-item-' . $post_id, $post_css );
		$css = sprintf( '<style id="%s">%s</style>', 'loop-dynamic-' . $post_id_for_data, $css );
		echo $css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		\Elementor\Plugin::instance()->documents->restore_document();
	}
}
