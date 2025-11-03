<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wptravelengine.com/
 * @since             1.0.0
 * @package           WP_Travel_Engine
 *
 * @wordpress-plugin
 * Plugin Name:       WP Travel Engine - HBL Gateway
 * Plugin URI:        https://wptravelengine.com/
 * Description:       An extension of WP Travel Engine plugin to accept payment through Himalayan Bank 2C2P payment gateway.
 * Version:           2.2.2
 * Author:            WP Travel Engine
 * Author URI:        https://wptravelengine.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wte-hbl
 * Domain Path:       /languages
 * WTE: 			  20311:wte_hbl_payments_license_key
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WP_TRAVEL_ENGINE_HBL_FILE_PATH', __FILE__ );
define( 'WP_TRAVEL_ENGINE_HBL_BASE_PATH', dirname( __FILE__ ) );
define( 'WP_TRAVEL_ENGINE_HBL_VERSION', '2.2.2' );

require WP_TRAVEL_ENGINE_HBL_BASE_PATH . '/vendor/autoload.php';


add_action( 'admin_notices', 'wte_hbl_gateway_maybe_disable_plugin' );

/**
 * Output error message and disable plugin if requirements are not met.
 *
 * This fires on admin_notices.
 *
 * @since 1.0.0
 */
function wte_hbl_gateway_maybe_disable_plugin() {

	if ( ! wte_hbl_gateway_meets_requirements() ) {

		// Display our error
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( '<strong>WP Travel Engine - HBL Gateway</strong> addon requires the WP Travel Engine plugin to work. Please install and activate the latest WP Travel Engine plugin first. <strong>WP Travel Engine - HBL Gateway will be deactivated now.</strong>', 'wte-hbl' ), admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';

		// Deactivate our plugin
		deactivate_plugins( __FILE__ );
	}
}

/**
 * Check if all plugin requirements are met.
 *
 * @since 1.0.0
 *
 * @return bool True if requirements are met, otherwise false.
 */
function wte_hbl_gateway_meets_requirements() {
	return ( class_exists( 'WP_Travel_Engine' ) && defined( 'WP_TRAVEL_ENGINE_VERSION' ) && version_compare( WP_TRAVEL_ENGINE_VERSION, '4.3.4', '>=' ) );
}

add_action( 'plugins_loaded', function() {
	wptravelengine_pro_config( __FILE__, array(
		'id'           => 20311,
		'slug'         => 'wp-travel-engine-hbl-payment-gateway',
		'plugin_name'  => 'HBL Gateway',
		'file_path'    => __FILE__,
		'version'      => WP_TRAVEL_ENGINE_HBL_VERSION,
		'dependencies' => [
			'requires' => [
				'/class-wp-travel-engine-hbl-gateway',
				'/includes/class-wte-hbl-handle-payment',
				'/includes/integration/autoload',
			],
		],
		'execute'      => 'Wte_HBL_Payments_Handler',
	) );
});
