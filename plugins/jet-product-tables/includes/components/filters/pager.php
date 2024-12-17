<?php

namespace Jet_WC_Product_Table\Components\Filters;

class Pager {

	protected $args = [];

	public function __construct( $args ) {
		$this->args = wp_parse_args( $args, [
			'page' => 1,
			'pages' => 1,
			'edges' => 2,
		] );
	}

	/**
	 * Return string of link classes
	 *
	 * @param  array $classes Classes to implode.
	 * @return string
	 */
	public function get_link_class( $classes = [] ) {
		return implode( ' ', array_filter( array_merge( [ 'page-numbers' ], $classes ) ) );
	}

	/**
	 * Retrieves paginated links list of pages.
	 *
	 * @param string|array $args {
	 *     Optional. Array or string of arguments for generating paginated links for archives.
	 *
	 *     @type string $base               Base of the paginated url. Default empty.
	 *     @type string $format             Format for the pagination structure. Default empty.
	 *     @type int    $total              The total amount of pages. Default is the value WP_Query's
	 *                                      `max_num_pages` or 1.
	 *     @type int    $current            The current page number. Default is 'paged' query var or 1.
	 *     @type string $aria_current       The value for the aria-current attribute. Possible values are 'page',
	 *                                      'step', 'location', 'date', 'time', 'true', 'false'. Default is 'page'.
	 *     @type bool   $show_all           Whether to show all pages. Default false.
	 *     @type int    $end_size           How many numbers on either the start and the end list edges.
	 *                                      Default 1.
	 *     @type int    $mid_size           How many numbers to either side of the current pages. Default 2.
	 *     @type bool   $prev_next          Whether to include the previous and next links in the list. Default true.
	 *     @type string $prev_text          The previous page text. Default '&laquo; Previous'.
	 *     @type string $next_text          The next page text. Default 'Next &raquo;'.
	 *     @type string $type               Controls format of the returned value. Possible values are 'plain',
	 *                                      'array' and 'list'. Default is 'plain'.
	 *     @type string $add_fragment       A string to append to each link. Default empty.
	 *     @type string $before_page_number A string to appear before the page number. Default empty.
	 *     @type string $after_page_number  A string to append after the page number. Default empty.
	 * }
	 * @return string|string[]|void String of page links or array of page links, depending on 'type' argument.
	 *                              Void if total number of pages is less than 2.
	 */
	public function paginate_links( $args = [] ) {

		$defaults = array(
			'base'               => '%_%', // http://example.com/all_posts.php%_% : %_% is replaced by format (below).
			'format'             => '#%#%', // ?page=%#% : %#% is replaced by the page number.
			'total'              => 1,
			'current'            => 1,
			'aria_current'       => 'page',
			'show_all'           => false,
			'prev_next'          => true,
			'prev_text'          => '&laquo; Previous',
			'next_text'          => 'Next &raquo;',
			'end_size'           => 1,
			'mid_size'           => 2,
			'type'               => 'plain',
			'add_fragment'       => '',
			'before_page_number' => '',
			'after_page_number'  => '',
			'class'              => '',
			'current_class'      => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Who knows what else people pass in $args.
		$total = (int) $args['total'];
		if ( $total < 2 ) {
			return;
		}
		$current  = (int) $args['current'];
		$end_size = (int) $args['end_size']; // Out of bounds? Make it the default.
		if ( $end_size < 1 ) {
			$end_size = 1;
		}
		$mid_size = (int) $args['mid_size'];
		if ( $mid_size < 0 ) {
			$mid_size = 2;
		}

		$r          = '';
		$page_links = array();
		$dots       = false;

		if ( $args['prev_next'] && $current && 1 < $current ) {
			$link = str_replace( '%_%', 2 === $current ? '' : $args['format'], $args['base'] );
			$link = str_replace( '%#%', $current - 1, $link );
			$link .= $args['add_fragment'];

			$page_links[] = sprintf(
				'<a class="%3$s" href="%1$s">%2$s</a>',
				$link,
				$args['prev_text'],
				$this->get_link_class( [ 'prev', $args['class'] ] )
			);
		}

		for ( $n = 1; $n <= $total; $n++ ) {
			if ( $n === $current ) {
				$page_links[] = sprintf(
					'<span aria-current="%1$s" class="%3$s">%2$s</span>',
					esc_attr( $args['aria_current'] ),
					$args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number'],
					$this->get_link_class( [ 'current', $args['class'], $args['current_class'] ] )
				);

				$dots = true;
			} else { // phpcs:ignore
				if (
					$args['show_all']
					|| (
						$n <= $end_size
						|| ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size )
						|| $n > $total - $end_size
					)
				) {
					$link = str_replace( '%_%', $args['format'], $args['base'] );
					$link = str_replace( '%#%', $n, $link );
					$link .= $args['add_fragment'];

					$page_links[] = sprintf(
						'<a class="%3$s" href="%1$s">%2$s</a>',
						$link,
						$args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number'],
						$this->get_link_class( [ $args['class'] ] )
					);

					$dots = true;
				} elseif ( $dots && ! $args['show_all'] ) {
					$page_links[] = sprintf(
						'<span class="%1$s">&hellip;</span>',
						$this->get_link_class( [ 'dots', $args['class'] ] )
					);

					$dots = false;
				}
			}
		}

		if ( $args['prev_next'] && $current && $current < $total ) {

			$link = str_replace( '%_%', $args['format'], $args['base'] );
			$link = str_replace( '%#%', $current + 1, $link );
			$link .= $args['add_fragment'];

			$page_links[] = sprintf(
				'<a class="%3$s" href="%1$s">%2$s</a>',
				$link,
				$args['next_text'],
				$this->get_link_class( [ 'next', $args['class'] ] )
			);
		}

		switch ( $args['type'] ) {
			case 'array':
				return $page_links;

			case 'list':
				$r .= "<ul class='page-numbers'>\n\t<li>";
				$r .= implode( "</li>\n\t<li>", $page_links );
				$r .= "</li>\n</ul>\n";
				break;

			default:
				$r = implode( "\n", $page_links );
				break;
		}

		return $r;
	}

	/**
	 * Print pager
	 */
	public function print() {

		if ( empty( $this->args['pages'] ) || 1 === absint( $this->args['pages'] ) ) {
			return;
		}

		printf(
			'<nav class="jet-wc-product-pager woocommerce-pagination">%1$s</nav>',
			wp_kses_post( $this->paginate_links( [
				'total'         => $this->args['pages'],
				'current'       => $this->args['page'],
				'base'          => '%_%',
				'format'        => '#%#%',
				'type'          => 'list',
				'prev_next'     => false,
				'end_size'      => $this->args['edges'],
				'mid_size'      => $this->args['edges'],
				'class'         => 'jet-wc-product-pager__item',
				'current_class' => 'jet-wc-product-pager__item--current',
			] ) )
		);
	}
}
