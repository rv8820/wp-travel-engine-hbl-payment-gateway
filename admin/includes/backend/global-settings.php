<?php
/**
 * Get Global Settings
 */
$office_id                       = 'DEMOOFFICE';
$api_key                         = '';
$merchant_signing_private_key    = '';
$merchant_decryption_private_key = '';
$paco_encryption_public_key      = '';
$paco_signing_public_key         = '';
$key_id                             = '';

$notification_url_base = home_url();
$query_arg             = array( '_gateway' => 'hbl_enable' );

$query_arg['_action'] = 'wtep_success';
$confirmation_url     = add_query_arg( $query_arg, $notification_url_base );
$query_arg['_action'] = 'wtep_fail';
$failed_url           = add_query_arg( $query_arg, $notification_url_base );
$query_arg['_action'] = 'wtep_cancel';
$cancel_url           = add_query_arg( $query_arg, $notification_url_base );
$query_arg['_action'] = 'wtep_ipn';
$backend_url          = add_query_arg( $query_arg, $notification_url_base );

$wte_settings = get_option( 'wp_travel_engine_settings', array() );
$hbl_settings = isset( $wte_settings['hbl_settings'] ) ? $wte_settings['hbl_settings'] : compact( 'office_id', 'confirmation_url', 'failed_url', 'cancel_url', 'backend_url' );
extract( $hbl_settings );
?>
<div class="wpte-field wpte-text wpte-floated">
	<label for="wp_travel_engine_settings[hbl_office_id]" class="wpte-field-label"><?php _e( 'Office ID', 'wte-hbl' ); ?></label>
	<input type="text" id="wp_travel_engine_settings[hbl_office_id]" name="wp_travel_engine_settings[hbl_settings][office_id]" value="<?php echo esc_attr( $office_id ); ?>">
	<span class="wpte-tooltip"><?php esc_html_e( 'Get office ID from the Bank.', 'wte-hbl' ); ?></span>
</div>
<!-- API KEY -->
<div class="wpte-field wpte-text wpte-floated">
	<label for="wp_travel_engine_settings[api_key]" class="wpte-field-label"><?php _e( 'API Key', 'wte-hbl' ); ?></label>
	<input type="text" id="wp_travel_engine_settings[api_key]" name="wp_travel_engine_settings[hbl_settings][api_key]" value="<?php echo esc_attr( $api_key ); ?>">
	<span class="wpte-tooltip"><?php esc_html_e( 'Get office API Key from the bank.', 'wte-hbl' ); ?></span>
</div>
<!-- Encryption Key ID - kid -->
<div class="wpte-field wpte-text wpte-floated">
	<label for="wp_travel_engine_settings[key_id]" class="wpte-field-label"><?php _e( 'Encryption Key ID', 'wte-hbl' ); ?></label>
	<input type="text" id="wp_travel_engine_settings[key_id]" name="wp_travel_engine_settings[hbl_settings][key_id]" value="<?php echo esc_attr( $key_id ); ?>">
	<span class="wpte-tooltip"><?php esc_html_e( 'Get Encryption Key ID from the bank.', 'wte-hbl' ); ?></span>
</div>

<!-- Encrypt Decrypt Keys -->
<div class="wpte-field wpte-multi-checkbox">
	<div class="wpte-title-wrap">
		<h3 class="wpte-title"><?php esc_html_e( 'Encryption Keys', 'wte-hbl' ); ?></h3>
	</div>
	<!-- Merchant Signing Private Key -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[merchant_signing_private_key]" class="wpte-field-label"><?php _e( 'Merchant Signing Private Key', 'wte-hbl' ); ?></label>
		<textarea id="wp_travel_engine_settings[merchant_signing_private_key]" name="wp_travel_engine_settings[hbl_settings][merchant_signing_private_key]"><?php echo esc_attr( $merchant_signing_private_key ); ?></textarea>
		<span class="wpte-tooltip"><?php esc_html_e( 'Merchant Signing Private Key is used to cryptographically sign and create the request JWS.', 'wte-hbl' ); ?></span>
	</div>
	<!-- Merchant Decryption Private Key -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[merchant_decryption_private_key]" class="wpte-field-label"><?php _e( 'Merchant Decryption Private Key', 'wte-hbl' ); ?></label>
		<textarea id="wp_travel_engine_settings[merchant_decryption_private_key]" name="wp_travel_engine_settings[hbl_settings][merchant_decryption_private_key]"><?php echo esc_attr( $merchant_decryption_private_key ); ?></textarea>
		<span class="wpte-tooltip"><?php esc_html_e( 'Merchant Decryption Private Key used to cryptographically decrypt the response JWE.', 'wte-hbl' ); ?></span>
	</div>
	<!-- PACO Signing Public Key is used to cryptographically verify the response JWS signature. -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[paco_signing_public_key]" class="wpte-field-label"><?php _e( 'PACO Signing Public Key', 'wte-hbl' ); ?></label>
		<textarea id="wp_travel_engine_settings[paco_signing_public_key]" name="wp_travel_engine_settings[hbl_settings][paco_signing_public_key]"><?php echo esc_attr( $paco_signing_public_key ); ?></textarea>
		<span class="wpte-tooltip"><?php esc_html_e( 'PACO Encryption Public Key is used to cryptographically encrypt and create the request JWE.', 'wte-hbl' ); ?></span>
	</div>
	<!-- Paco Encryption public key -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[paco_encryption_public_key]" class="wpte-field-label"><?php _e( 'PACO Encryption Public Key', 'wte-hbl' ); ?></label>
		<textarea id="wp_travel_engine_settings[paco_encryption_public_key]" name="wp_travel_engine_settings[hbl_settings][paco_encryption_public_key]"><?php echo esc_attr( $paco_encryption_public_key ); ?></textarea>
		<span class="wpte-tooltip"><?php esc_html_e( 'PACO Encryption Public Key is used to cryptographically encrypt and create the request JWE.', 'wte-hbl' ); ?></span>
	</div>
</div>

<!-- Notification URLs -->
<div class="wpte-field wpte-multi-checkbox">
	<div class="wpte-title-wrap">
		<h3 class="wpte-title"><?php esc_html_e( 'Notification URLs', 'wte-hbl' ); ?></h3>
	</div>
	<!-- Success URL -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[hbl_success_url]" class="wpte-field-label"><?php _e( 'Confirmation URL', 'wte-hbl' ); ?></label>
		<input type="text" id="wp_travel_engine_settings[hbl_success_url]" name="wp_travel_engine_settings[hbl_settings][confirmation_url]" value="<?php echo esc_attr( $confirmation_url ); ?>">
		<span class="wpte-tooltip"><?php esc_html_e( 'URL to redirect customer back to Merchant website after success.', 'wte-hbl' ); ?></span>
	</div>
	<!-- Cancel URL -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[hbl_cancel_url]" class="wpte-field-label"><?php _e( 'Cancellation URL', 'wte-hbl' ); ?></label>
		<input type="text" id="wp_travel_engine_settings[hbl_cancel_url]" name="wp_travel_engine_settings[hbl_settings][cancel_url]" value="<?php echo esc_attr( $cancel_url ); ?>">
		<span class="wpte-tooltip"><?php esc_html_e( 'Redirect URL if customer cancel the transaction.', 'wte-hbl' ); ?></span>
	</div>
	<!-- Failed URL -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[hbl_failed_url]" class="wpte-field-label"><?php _e( 'Failed URL', 'wte-hbl' ); ?></label>
		<input type="text" id="wp_travel_engine_settings[hbl_failed_url]" name="wp_travel_engine_settings[hbl_settings][failed_url]" value="<?php echo esc_attr( $failed_url ); ?>">
		<span class="wpte-tooltip"><?php esc_html_e( 'Redirect URL if transaction is failed.', 'wte-hbl' ); ?></span>
	</div>
	<!-- Backend URL -->
	<div class="wpte-field wpte-text wpte-floated">
		<label for="wp_travel_engine_settings[hbl_backend_url]" class="wpte-field-label"><?php _e( 'Notification URL', 'wte-hbl' ); ?></label>
		<input type="text" id="wp_travel_engine_settings[hbl_backend_url]" name="wp_travel_engine_settings[hbl_settings][backend_url]" value="<?php echo esc_attr( $backend_url ); ?>">
		<span class="wpte-tooltip"><?php esc_html_e( 'Payment Gateway Notification URL.', 'wte-hbl' ); ?></span>
	</div>
</div>
