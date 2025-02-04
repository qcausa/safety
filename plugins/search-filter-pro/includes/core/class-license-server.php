<?php
/**
 * Handles license server endpoint selection and health checks.
 *
 * @link       https://searchandfilter.com
 * @since      3.0.0
 *
 * @package    Search_Filter_Pro
 * @subpackage Search_Filter_Pro/Core
 */

namespace Search_Filter_Pro\Core;

use Search_Filter\Core\Notices;
use Search_Filter_Pro\Util;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles license server availability checks and endpoint selection.
 */
class License_Server {

    /**
     * License server endpoints
     */
    const SERVER_ENDPOINTS = array(
        'license' => 'https://license.searchandfilter.com',
        'main'    => 'https://searchandfilter.com',
    );

    /**
     * Test server endpoints
     * 
     * The license server will return an error if it doesn't contain `edd_action`.
     */
    const TEST_SERVER_ENDPOINTS = array(
        'license' => 'https://searchandfilter.com/?edd_action=check_license',
        'main'    => 'https://searchandfilter.com/?edd_action=check_license',
    );

    /**
     * The cron hook name.
     */
    const CRON_HOOK = 'search-filter-pro/core/license-server/health-check';

    /**
     * The cron interval name.
     */
    const CRON_INTERVAL_NAME = 'search_filter_4days';

    /**
     * The option name for storing the preferred server.
     */
    const OPTION_NAME = 'license-server';

    /**
     * The option name for storing the server test results.
     */
    const OPTION_TEST_RESULTS = 'license-server-test';

    /**
     * Initialize the license server checks.
     */
    public static function init() {

        // Setup CRON job for checking for expired items.
        add_action( 'init', array( __CLASS__, 'validate_cron_schedule' ) );
       
        // Create the schedule
        add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
        
        // Add the cron job action
        add_action( self::CRON_HOOK, array( __CLASS__, 'schedule_check_server_health' ) );
        
        // Attach activation/deactivation hooks
        add_action( 'search-filter-pro/core/activator/activate', array( __CLASS__, 'activate' ) );
        add_action( 'search-filter-pro/core/deactivator/deactivate', array( __CLASS__, 'deactivate' ) );

        // Add notices when there are errors with connecting to the servers.
        add_action( 'init', array( __CLASS__, 'add_notices' ) );

        // Add the connection info to the admin data.
        add_action( 'search-filter/rest-api/get_admin_data', array( __CLASS__, 'get_admin_data' ) );
    }

    /**
     * Get the preferred server endpoint.
     *
     * @return string The server endpoint URL
     */
    public static function get_endpoint() {

        // There is a special condition, when S&F pro is installed but not free, then we don't have
        // access to the Options class, so just return the license server.
        if ( ! class_exists( '\Search_Filter\Options' ) ) {
            return self::SERVER_ENDPOINTS['license'];
        }

        $preferred_server = \Search_Filter\Options::get_option_value( self::OPTION_NAME );
        
        if ( empty( $preferred_server ) ) {
            $preferred_server = 'license'; // Default to license server
        }

        return self::SERVER_ENDPOINTS[ $preferred_server ];
    }

    /**
     * Setup the interval for the cron job.
     *
     * @param array $schedules The existing cron schedules.
     * @return array Modified cron schedules.
     */
    public static function schedules( $schedules ) {
        if ( ! isset( $schedules[ self::CRON_INTERVAL_NAME ] ) ) {
            $schedules[ self::CRON_INTERVAL_NAME ] = array(
                'interval' => DAY_IN_SECONDS * 4,
                'display'  => __( 'Once every 4 days', 'search-filter-pro' ),
            );
        }
        return $schedules;
    }

    /**
     * Activate the cron job.
     */
    public static function activate() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
        }
    }

    /**
     * Deactivate the cron job.
     */
    public static function deactivate() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    
    /**
     * Hook the task into shutdown so we don't affect the request.
     */
    public static function schedule_check_server_health() {
		// Hook the task into shutdown so we don't affect the request.
		add_action( 'shutdown', array( __CLASS__, 'check_server_health' ) );
    }

    /**
     * Check the health of both servers and update the preferred endpoint.
     */
    public static function check_server_health() {
        $license_server_healthy = self::test_endpoint( self::TEST_SERVER_ENDPOINTS['license'] );
        $main_server_healthy = self::test_endpoint( self::TEST_SERVER_ENDPOINTS['main'] );

        if ( $license_server_healthy ) {
            \Search_Filter\Options::update_option_value( self::OPTION_NAME, 'license' );
        } elseif ( $main_server_healthy ) {
            \Search_Filter\Options::update_option_value( self::OPTION_NAME, 'main' );
        } else {
            // Fallback to the license server.
            \Search_Filter\Options::update_option_value( self::OPTION_NAME, 'license' );
        }

        $result = array(
            'license' => $license_server_healthy,
            'main'    => $main_server_healthy,
        );
        // Store the results in the options table.
        \Search_Filter\Options::update_option_value( self::OPTION_TEST_RESULTS, $result );

        return $result;
    }

    /**
     * Test if an endpoint is responding.
     *
     * @param string $endpoint The endpoint URL to test.
     * @return bool Whether the endpoint is healthy.
     */
    private static function test_endpoint( $endpoint ) {
        $response = wp_remote_get( 
            $endpoint,
            array(
                'timeout'   => 5,
                'sslverify' => true,
            )
        );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
    }

  
    /**
	 * Validate the cron job.
	 *
	 * @since 3.0.0
	 */
	public static function validate_cron_schedule() {
		$next_event = wp_get_scheduled_event( self::CRON_HOOK );
		if ( ! $next_event ) {
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
			return;
		}

		$time_diff      = $next_event->timestamp - time();
		$time_5_minutes = 5 * MINUTE_IN_SECONDS;

		if ( $time_diff < 0 && -$time_diff > $time_5_minutes ) {
			// This means our scheduled event has been missed by more then 5 minutes.
			// So lets run manually and reschedule.
			self::schedule_check_server_health();
			Util::error_log( 'Expired license server cron job found, re-running and rescheduling.', 'error' );
			wp_clear_scheduled_hook( self::CRON_HOOK );
			wp_schedule_event( time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK );
		}
	}

    /**
	 * Add error notices if the license server cannot be reached.
	 */
	public static function add_notices() {

        if ( ! class_exists( '\Search_Filter\Options' ) ) {
            return;
        }
        
        // Show a notice to the user if there are errors with both servers.
        $test_result = \Search_Filter\Options::get_option_value( self::OPTION_TEST_RESULTS );

        // If the options are empty, then we don't have any test results yet.
        if ( empty( $test_result ) ) {
            return;
        }

        // If the license server is healthy, then we don't need to show a notice.
        if ( $test_result['license'] === false && $test_result['main'] === false ) {
            // Add WP notice, not S&F notice:
            add_action( 'admin_notices', array( __CLASS__, 'display_wp_admin_connection_error_notice' ) );
        }
	}

    /**
     * Display a notice if the connection to the license server fails
     * in the S&F dashboard.
     */
    public static function display_search_filter_notices() {
        
        $test_result = \Search_Filter\Options::get_option_value( self::OPTION_TEST_RESULTS );

        // If the options are empty, then we don't have any test results yet.
        if ( empty( $test_result ) ) {
            return;
        }

        // If the license server is healthy, then we don't need to show a notice.
        if ( $test_result['license'] === true ) {
            return;
        }

        // If only the main is working, show a generic notice to the user.
        if ( $test_result['main'] === true ) {
            $notice_string = sprintf(
                // translators: %s: Support URL.
                __( 'Issues connecting to the Search & Filter license server. <a href="%s" target="_blank">Contact support for help</a>.', 'search-filter-pro' ),
                'https://searchandfilter.com/account/support/'
            );
            Notices::add_notice( $notice_string, 'error', 'search-filter-pro-license-server-error' );
            return;
        }

        // Then both servers failed.
        $notice_string = sprintf(
            // translators: %s: Support URL.
            __( 'Unable to connect to Search & Filter update servers. Please check your internet connection or firewall settings. <a href="%s" target="_blank">Contact support for help</a>.', 'search-filter-pro' ),
            'https://searchandfilter.com/account/support/'
        );
        $actions = array(
            'test_connection'  => array(
                'label'         => esc_html__( 'Test connection', 'search-filter-pro' ),
               'type'     => 'navigate',
                'location' => '?page=search-filter&section=test-connection',
                'variant'  => 'secondary',
            ),
            // 'dismiss' => true,
        );
        Notices::add_notice( $notice_string, 'error', 'search-filter-pro-license-server-error', $actions );
    }

    public static function display_wp_admin_connection_error_notice() {
        if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

        $notice_string = sprintf(
            // translators: %s: Support URL.
            __( 'Unable to connect to Search & Filter update servers. Please check your internet connection or firewall settings. <a href="%s">Test your connection settings</a> or <a href="%s" target="_blank">contact support for help</a>.', 'search-filter-pro' ),
            admin_url( 'admin.php?page=search-filter' ),
            'https://searchandfilter.com/account/support/'
        );

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', wp_kses_post( $notice_string ) );

    }


    public static function get_admin_data( $admin_data ) {
        $test_result = \Search_Filter\Options::get_option_value( self::OPTION_TEST_RESULTS );
        // If the options are empty, then we don't have any test results yet.
        if ( empty( $test_result ) ) {
            return $admin_data;
        }
        $admin_data['connection'] = array(
            'license' => $test_result['license'],
            'main'    => $test_result['main'],
        );
        return $admin_data;
    }
}
