<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms
namespace DynamicShortcodes\Core\Settings\AdminPages;

use DynamicShortcodes\Plugin;

class License {
	private $admin_pages;
	public function plugin_action_links_license( $links ) {
		$links['license'] = sprintf(
			'<a style="color:brown;" title="%s" href="%s"><b>%s</b></a>',
			esc_html__( 'Activate license', 'dynamic-shortcodes' ),
			admin_url( 'admin.php?page=dynamic-shortcodes-license' ),
			esc_html__( 'License', 'dynamic-shortcodes' )
		);
		return $links;
	}

	/**
	 * Error Message on Update.
	 *
	 * @param array<mixed> $plugin_data Plugin data.
	 * @param object       $response    Response object.
	 * @return void
	 */
	//phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	public function error_message_update( $plugin_data, $response ) {
		printf( '&nbsp;<strong>%1$s</strong>', esc_html__( 'The license is not active.', 'dynamic-shortcodes' ) );
	}

	/**
	 * Display activation advisor.
	 *
	 * @return void
	 */
	public function activation_advisor() {
		$license_facade = Plugin::instance()->license_facade;
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab_license = isset( $_GET['page'] ) && 'dynamic-shortcodes-license' === $_GET['page'];
		// translators: specifiers are html tags
		$buy_msg = esc_html__( 'It seems that your copy is not activated, please %1$sactivate it%2$s or %3$sbuy a new license%4$s.', 'dynamic-content-for-elementor' );

		if ( is_admin() && ! $tab_license && current_user_can( 'manage_options' ) && ! $license_facade->get_active_license() ) {
			// translators: %1$s: Open URL. %2$s: Close URL. %3$s: Open URL. %4$s: Close URL.
			$message = sprintf(
				$buy_msg,
				'<a href="' . admin_url( 'admin.php?page=dynamic-shortcodes-license' ) . '">',
				'</a>',
				'<a href="' . DYNAMIC_SHORTCODES_PRICING_PAGE . '" target="blank">',
				'</a>'
			);
			$this->admin_pages->notices->error( $message );
			add_filter( 'plugin_action_links_' . DYNAMIC_SHORTCODES_PLUGIN_BASE, [ $this, 'plugin_action_links_license' ] );
			add_action( 'in_plugin_update_message-' . DYNAMIC_SHORTCODES_PLUGIN_BASE, [ $this, 'error_message_update' ], 10, 2 );
		}
	}

	/**
	 * Constructor.
	 *
	 * @param object $admin_pages Admin pages instance.
	 */
	public function __construct( $admin_pages ) {
		$this->admin_pages = $admin_pages;
		// Priority: run before notices manager.
		add_action( 'admin_notices', [ $this, 'activation_advisor' ], 9 );
	}

	/**
	 * Display mismatch notice.
	 *
	 * @return void
	 */
	public static function mismatch_notice() {
		Plugin::instance()->settings_manager->admin_pages->notices->warning(
			esc_html__( 'License Mismatch. Your license key doesn\'t match your current domain. This is likely due to a change in the domain URL. You can reactivate your license now. Remember to deactivate the one for the old domain from your license area on Dynamic.ooo\'s site', 'dynamic-content-for-elementor' )
		);
	}

	private static function print_status() {
		$license_facade = Plugin::instance()->license_facade;
		$dsh_license    = $license_facade->dsh_license;
		$active_license = $license_facade->get_active_license();
		$dsh_key        = $dsh_license->get_license_key();
		$dce_active     = $license_facade->is_dce_active();

		echo '<h2 class="dsh-license-status">';
		esc_html_e( 'License Status', 'dynamic-shortcodes' );
		echo ': ';
		if ( $active_license ) {
			if ( $active_license === 'dce' ) {
				echo "<span class='dsh-license-status-active'>";
				esc_html_e( 'Active via Dynamic Content for Elementor', 'dynamic-shortcodes' );
				echo '</span>';
			} else {
				echo "<span class='dsh-license-status-active'>";
				esc_html_e( 'Active', 'dynamic-shortcodes' );
				echo '</span>';
			}
		} else {
			echo "<span class='dsh-license-status-inactive'>";
			esc_html_e( 'Not Active', 'dynamic-shortcodes' );
			echo '</span>';
		}
		echo '</h2>';

		if ( $active_license === 'dsh' || ( ! $dce_active ) ) {
			static::render_form();
			return;
		}

		$dsh_key        = false;
		$active_license = false;
		static::render_licenses_tabs();
	}

	protected static function render_form() {
		$license_facade    = Plugin::instance()->license_facade;
		$dsh_license       = $license_facade->dsh_license;
		$is_license_active = $dsh_license->is_license_active();
		$license_key       = $dsh_license->get_license_key();
		$license_domain    = get_option( DYNAMIC_SHORTCODES_PREFIX . '_license_domain' );

		?>
		<form action="" method="post" class="dsh-license-key">
			<?php wp_nonce_field( 'dynamic-shortcodes-settings-page', 'dynamic-shortcodes-settings-page' ); ?>
			<input type="password" autocomplete="off" name="license_key" value="<?php echo esc_attr( $license_key ); ?>" placeholder="<?php echo esc_attr_e( 'Insert Dynamic Shortcodes License Key', 'dynamic-shortcodes' ); ?>" id="license_key">
			<input type="hidden" name="license_activated" value="<?php echo esc_attr( $is_license_active ); ?>">
			<?php
			if ( $is_license_active ) {
				submit_button( esc_html__( 'Deactivate', 'dynamic-shortcodes' ), 'cancel', 'submit', false );
			} else {
				submit_button( esc_html__( 'Save key and activate', 'dynamic-shortcodes' ), 'primary', 'submit', false );
			}
			?>
		</form>

		<?php
		if ( $is_license_active ) {
			if ( $license_domain && $license_domain !== $dsh_license->get_current_domain() ) {
				?>
				<p><strong style="color:#f0ad4e;"><?php esc_html_e( 'Your license is valid but there is something wrong: license mismatch.', 'dynamic-shortcodes' ); ?></strong></p>
				<p><?php esc_html_e( 'Your license key doesn\'t match your current domain. This is most likely due to a change in the domain URL. Please deactivate the license and reactivate it', 'dynamic-shortcodes' ); ?></p>
				<?php
			} else {
				?>
				<p><strong class='dsh-license-status-active'>
					<?php
					printf(
						// translators: part of license key
						esc_html__( 'Your license ending in "%1$s" is valid and active.', 'dynamic-shortcodes' ),
						'<strong>' . esc_html( $dsh_license->get_license_key_last_4_digits() ) . '</strong>'
					);
					?>
				</strong></p>
				<?php
			}
		} else {
			?>
			<p><strong class='dsh-license-status-inactive'>
			<?php esc_html_e( 'Deactivated', 'dynamic-shortcodes' ); ?>
			</strong></p>
			<?php
		}
	}

	protected static function render_licenses_tabs() {
		echo '<div class="dsh-content-wrapper">';

		echo '<div class="dsh-license-tab">';
		echo '<h3>' . esc_html__( 'Activation with Dynamic Shortcodes License Key', 'dynamic-shortcodes' ) . '</h3>';
		static::render_form();
		echo '<p>' . esc_html__( 'Allows full plugin functionality.', 'dynamic-shortcodes' ) . ' ' . esc_html__( 'Works with:', 'dynamic-shortcodes' ) . '<br />';
		?>
		<ul>
			<li>Dynamic Content for Elementor</li>
			<li>Elementor Free</li>
			<li>Elementor Pro</li>
			<li>Bricks</li>
			<li>Classic Editor</li>
			<li>Gutenberg (Beta)</li>
			<li>Full Site Editing (Beta)</li>
			<li>Oxygen</li>
			<li>Breakdance</li>
		</ul>

		<?php
		echo '</p>';
		echo '</div>';

		echo '<div class="dsh-license-tab">';
		echo '<h3>' . esc_html__( 'Activation via Dynamic Content for Elementor', 'dynamic-shortcodes' ) . '</h3>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=dce-license' ) ) . '" class="button button-primary">' . esc_html__( 'Go to Dynamic Content for Elementor License Page', 'dynamic-shortcodes' ) . '</a>';

		$license_facade = Plugin::instance()->license_facade;
		if ( \DynamicContentForElementor\Plugin::instance()->license_system->is_license_active() ) {
			?>
			<p><strong class='dsh-license-status-active'>
			<?php esc_html_e( 'Activated', 'dynamic-shortcodes' ); ?>
			</strong></p>
			<?php
		} else {
			?>
			<p><strong class='dsh-license-status-inactive'>
			<?php esc_html_e( 'Deactivated', 'dynamic-shortcodes' ); ?>
			</strong></p>
			<?php
		}

		echo '<p>' . esc_html__( 'Works with:', 'dynamic-shortcodes' ) . '<br />';
		?>
		<ul>
			<li>Dynamic Content for Elementor</li>
			<li>Elementor Free</li>
			<li>Elementor Pro</li>
		</ul>
		<?php
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render license page.
	 *
	 * @return void
	 */
	public static function render_license_page() {
		$license_facade = Plugin::instance()->license_facade;
		$dsh_license    = $license_facade->dsh_license;
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php
			if ( 'POST' === $_SERVER['REQUEST_METHOD'] &&
				( ! isset( $_POST['dynamic-shortcodes-settings-page'] ) || ! wp_verify_nonce( $_POST['dynamic-shortcodes-settings-page'], 'dynamic-shortcodes-settings-page' ) ) ) {
				wp_die( esc_html__( 'Nonce verification error.', 'dynamic-shortcodes' ) );
			}

			if ( isset( $_POST['license_key'] ) ) {
				if ( $_POST['license_activated'] ) {
					list( $success, $msg ) = $dsh_license->deactivate_license();
					if ( ! $success ) {
						Plugin::instance()->settings_manager->admin_pages->notices->error( $msg );
					} else {
						$msg = esc_html__( 'License key successfully deactivated for this site', 'dynamic-shortcodes' );
						Plugin::instance()->settings_manager->admin_pages->notices->success( $msg );
					}
				} else {
					$license_key           = sanitize_text_field( $_POST['license_key'] );
					list( $success, $msg ) = $dsh_license->activate_new_license_key( $license_key );
					Plugin::instance()->update_checker->purge_cache();
					if ( ! $success ) {
						Plugin::instance()->settings_manager->admin_pages->notices->error( $msg );
					} else {
						$msg = esc_html__( 'License key successfully activated for this site', 'dynamic-shortcodes' );
						Plugin::instance()->settings_manager->admin_pages->notices->success( $msg );
					}
				}
			} else {
				$dsh_license->refresh_and_repair_license_status();
			}
			if ( $dsh_license->domain_mismatch_check() ) {
				self::mismatch_notice();
			}

			$license_key       = $dsh_license->get_license_key();
			$is_license_active = $dsh_license->is_license_active();
			$license_domain    = get_option( DYNAMIC_SHORTCODES_PREFIX . '_license_domain' );
			?>
			<div class="content-wrapper">
				<div class="dynamic-shortcodes-tab">
					<?php self::print_status(); ?>

					<?php
					if ( ! $is_license_active ) {
						static::render_get_it_now();
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	protected static function render_get_it_now() {
		echo '<div class="dsh-license-get-it-now">';
		echo '<p>' . esc_html__( 'Enter your license to keep the plugin updated, obtaining new features, future compatibility, increased stability, security, and technical support.', 'dynamic-content-for-elementor' ) . '</p>';
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p>' . esc_html__( 'You still don\'t have one?', 'dynamic-content-for-elementor' ) . ' <a href="' . DYNAMIC_SHORTCODES_PRICING_PAGE . '" class="button button-small" target="_blank">' . esc_html__( 'Get it now!', 'dynamic-content-for-elementor' ) . '</a></p>';
		echo '</div>';
	}
}
