<?php
/**
 * Easy Digital Downloads Plugin Updater
 *
 * @package WP_Travel_Engine_HBL
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Includes the files needed for the plugin updater.
 *
 * @since 1.0.0
 */
if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {

	include( dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php' );
}

/**
 * Download ID for the product in Easy Digital Downloads.
 *
 * @since 1.0.0
 */
define( 'WP_TRAVEL_ENGINE_HBL_ITEM_ID', 20311 ); 

/**
 * Setup the updater for the WTE Payfast Payment Gateway Add-on.
 *
 * @since 1.0.0
 */
function wte_hbl_payment_plugin_updater() {

	// retrieve our license key from the DB
	$settings = get_option( 'wp_travel_engine_license' );
	$license_key = isset( $settings['wte_hbl_payment_license_key'] ) ? esc_attr( $settings['wte_hbl_payment_license_key'] ):'';

	// setup the updater
	$edd_updater = new EDD_SL_Plugin_Updater( WP_TRAVEL_ENGINE_STORE_URL, WP_TRAVEL_ENGINE_HBL_FILE_PATH,
		array(
			'version' => WP_TRAVEL_ENGINE_HBL_VERSION,        // current version number
			'license' => $license_key,             // license key (used get_option above to retrieve from DB)
			'item_id' => WP_TRAVEL_ENGINE_HBL_ITEM_ID,       // ID of the product
			'author'  => 'WP Travel Engine', // author of this plugin
			'beta'    => false,
		)
	);

}
add_action( 'admin_init', 'wte_hbl_payment_plugin_updater', 0 );

/**
 * Add-ons name for plugin license page.
 *
 * @since 1.0.0
 */
function wte_hbl_payment_name($array)
{
	$array['WP Travel Engine - HBL Gateway'] =  'wte_hbl_payment';
	return $array;
}
add_filter( 'wp_travel_engine_addons', 'wte_hbl_payment_name' );

/**
 * Add-ons Item ID for plugin license page.
 *
 * @since 1.0.0
 */
function wte_hbl_payment_id($array)
{
	$array['wte_hbl_payment'] = WP_TRAVEL_ENGINE_HBL_ITEM_ID;
	return $array;
}
add_filter( 'wp_travel_engine_addons_id', 'wte_hbl_payment_id' );

/**
 * Add-ons License details for showing updates in plugin license page.
 *
 * @since 1.0.0
 */
function wte_hbl_payment_license($array)
{
	$settings = get_option( 'wp_travel_engine_license' );
	$license_key = isset( $settings['wte_hbl_payment_license_key'] ) ? esc_attr( $settings['wte_hbl_payment_license_key'] ):'';// setup the updater

	$array[] = array( 
		'version' => WP_TRAVEL_ENGINE_HBL_VERSION,     // current version number
		'license' => $license_key,   // license key (used get_option above to retrieve from DB)
		'item_id' => WP_TRAVEL_ENGINE_HBL_ITEM_ID,   // id of this product in EDD
		'author'  => 'WP Travel Engine',  // author of this plugin
		'url'     => home_url()
	);
	return $array;
}

$settings = get_option( 'wp_travel_engine_license' );
$license_status  = isset( $settings['wte_hbl_payment_license_status'] ) ? esc_attr( $settings['wte_hbl_payment_license_status'] ): '';

if( isset( $license_status ) && $license_status == 'valid' )
{
	add_filter( 'wp_travel_engine_licenses', 'wte_hbl_payment_license' );
}
