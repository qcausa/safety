<?php

namespace Jet_WC_Product_Table\Traits;

/**
 * Converts array of HTML attributes to string
 */
trait Attributes_To_String {

	public function attrs_to_string( array $attrs = [] ) {

		$attrs_string = '';

		if ( ! empty( $attrs ) ) {

			$attrs_array = [];

			foreach ( $attrs as $key => $value ) {
				$attrs_array[] = sprintf( '%1$s="%2$s"', sanitize_text_field( $key ), esc_attr( $value ) );
			}

			$attrs_string = implode( ' ', $attrs_array );
		}

		return $attrs_string;
	}
}
