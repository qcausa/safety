<?php

namespace Jet_WC_Product_Table;

/**
 * Public assets manager
 */
class Assets {

	protected $assets_registered = false;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_public_assets' ] );
	}

	/**
	 * Enqueue previously registered script by handle
	 *
	 * @param  string $handle Script name to enqueue.
	 * @return void
	 */
	public function enqueue_script( $handle ) {

		if ( ! $this->assets_registered ) {
			$this->register_public_assets();
		}

		wp_enqueue_script( $handle );
	}

	/**
	 * Register public assests for the tables
	 *
	 * @return void
	 */
	public function register_public_assets() {

		wp_register_script(
			'jet-wc-product-snackbar',
			JET_WC_PT_URL . 'assets/js/public/snackbar-notices.js',
			[ 'jquery' ],
			JET_WC_PT_VERSION,
			true
		);

		wp_register_script(
			'jet-wc-product-actions',
			JET_WC_PT_URL . 'assets/js/public/product-actions.js',
			[ 'jet-wc-product-snackbar' ],
			JET_WC_PT_VERSION,
			true
		);

		wp_register_script(
			'jet-wc-product-filters',
			JET_WC_PT_URL . 'assets/js/public/filters.js',
			[ 'jet-wc-product-snackbar' ],
			JET_WC_PT_VERSION,
			true
		);

		$this->assets_registered = true;
	}

	/**
	 * Enqueue inline script
	 *
	 * @param  string $file File name without extension.
	 * @return void
	 */
	public function enqueue_inline_script( $file = '' ) {

		$file = sanitize_file_name( $file );
		$path = JET_WC_PT_PATH . 'assets/js/inline/' . $file . '.js';

		if ( ! is_readable( $path ) ) {
			return;
		}

		ob_start();
		include $path;
		$content = ob_get_clean();

		// phpcs:ignore
		printf( '<script>%s</script>', $content );
	}
}
