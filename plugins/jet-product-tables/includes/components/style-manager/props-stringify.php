<?php

namespace Jet_WC_Product_Table\Components\Style_Manager;

class Props_Stringify implements \Stringable {

	protected $is_border = null;
	protected $is_dimensions = null;
	protected $has_border_keys = null;
	protected $has_dimension_keys = null;
	protected $data = [];

	public function __construct( array $data = [] ) {
		$this->data = $data;
	}

	/**
	 * Check if given data describes border properties
	 *
	 * @return boolean
	 */
	public function is_border() {

		if ( null === $this->is_border ) {

			$is_border = false;

			if ( $this->has_border_keys() ) {
				$is_border = true;
			} elseif ( $this->has_dimension_keys() ) {

				foreach ( $this->data as $item ) {

					if ( ! is_array( $item ) ) {
						// Most probaly is dimension control in this case
						$this->is_dimensions = true;
						break;
					}

					$intersect = array_intersect( array_keys( $item ), $this->borders_map() );

					if ( ! empty( $intersect ) ) {
						$is_border = true;
						break;
					}
				}
			}

			$this->is_border = $is_border;
		}

		return $this->is_border;
	}

	/**
	 * Check if given data describes dimensions properties
	 *
	 * @return boolean
	 */
	public function is_dimensions() {

		if ( null === $this->is_dimensions ) {

			$is_dimensions = false;

			if ( $this->has_dimension_keys() ) {

				$is_dimensions = true;

				foreach ( $this->data as $item ) {

					if ( is_array( $item ) ) {
						$is_dimensions = false;
						break;
					}
				}
			}

			$this->is_dimensions = $is_dimensions;
		}

		return $this->is_dimensions;
	}

	/**
	 * Check if array has dimension props.
	 * Only by this check it could be both - border and dimensions itself, so it's only first level check.
	 *
	 * @return boolean
	 */
	public function has_dimension_keys() {

		if ( null === $this->has_dimension_keys ) {
			$keys                     = array_keys( $this->data );
			$common                   = array_intersect( $keys, $this->dimensions_map() );
			$this->has_dimension_keys = ! empty( $common ) ? true : false;
		}

		return $this->has_dimension_keys;
	}

	/**
	 * Check if array has border props.
	 *
	 * @return boolean
	 */
	public function has_border_keys() {

		if ( null === $this->has_border_keys ) {
			$keys                  = array_keys( $this->data );
			$common                = array_intersect( $keys, $this->borders_map() );
			$this->has_border_keys = ! empty( $common ) ? true : false;
		}

		return $this->has_border_keys;
	}

	/**
	 * Returns map of dimension properties
	 *
	 * @return array
	 */
	public function dimensions_map() {
		return [
			'top',
			'right',
			'bottom',
			'left',
		];
	}

	/**
	 * Returns map of border properties
	 *
	 * @return array
	 */
	public function borders_map() {
		return [
			'width',
			'style',
			'color',
		];
	}

	/**
	 * Get string representation of the border
	 *
	 * @return string
	 */
	public function stringify_border() {

		$result = [];

		if ( $this->has_border_keys() ) {
			$result[] = $this->stringify_border_row( '', $this->data );
		} else {
			foreach ( $this->data as $dim => $dim_data ) {
				$result[] = $this->stringify_border_row( $dim, $dim_data );
			}
		}

		return implode( ';', $result );
	}

	/**
	 * Stringify single border row.
	 *
	 * @param  string $dimension Border dimension (top, left, right, bottom or '' for global border).
	 * @param  array  $data      Data to stringify.
	 * @return string
	 */
	public function stringify_border_row( string $dimension = '', array $data = [] ) {
		return trim( sprintf(
			'border%1$s: %2$s %3$s %4$s',
			! empty( $dimension ) ? '-' . $dimension : '',
			isset( $data['width'] ) ? $data['width'] : '1px',
			isset( $data['style'] ) ? $data['style'] : 'solid',
			isset( $data['color'] ) ? $data['color'] : '',
		) );
	}

	/**
	 * Get string representation of the dimensions.
	 *
	 * @return string
	 */
	public function stringify_dimensions() {

		$result = [];

		foreach ( $this->dimensions_map() as $prop ) {
			$result[] = isset( $this->data[ $prop ] ) ? $this->data[ $prop ] : 0;
		}

		return implode( ' ', $result );
	}

	/**
	 * Stringify given data
	 *
	 * @return string
	 */
	public function __toString() {
		if ( $this->is_border() ) {
			return $this->stringify_border();
		} elseif ( $this->is_dimensions() ) {
			return $this->stringify_dimensions();
		} else {
			return implode( ' ', $this->data );
		}
	}
}
