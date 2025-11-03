<?php
/**
 * Class WTE_HBL_Payments_Handler
 *
 * @package WTE_HBL_Payments_Handler
 */
use WTEHBL\Payment\Payment\Response_Parser;
use WTEHBL\Payment\Payment;
/**
 * PayHere Admin Class
 *
 * @since 1.0.0
 */
class WTE_HBL_Payments_Handler {

	/**
	 * Clas Constructor
	 *
	 * @acces public
	 */
	public function __construct() {

		$this->init_hooks();
		$wte_settings = get_option( 'wp_travel_engine_settings', array() );

		// Setup default merchant data.
		$this->merchant_id     = isset( $wte_settings['hbl_merchant_id'] ) ? $wte_settings['hbl_merchant_id'] : '';
		$this->merchant_secret = isset( $wte_settings['hbl_secret_key'] ) ? $wte_settings['hbl_secret_key'] : '';

		$this->url = 'https://hblpgw.2c2p.com/HBLPGW/Payment/Payment/Payment';
		// Setup the test data, if in test mode.
		if ( defined( 'WP_TRAVEL_ENGINE_PAYMENT_DEBUG' ) && WP_TRAVEL_ENGINE_PAYMENT_DEBUG ) {
			$this->url = 'https://hblpgw.2c2p.com/HBLPGW/Payment/Payment/Payment';
			$this->url = 'https://uat3ds.2c2p.com/HBLPGW/Payment/Payment/Payment';
		} else {
			$this->send_debug_email = false;
		}

	}

	/**
	 * Init admin area hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		// add_action( 'wp_travel_engine_after_booking_process_completed', array( $this, 'generate_form' ) );

		/**
		 *
		 * @since 2.1.0
		 */
		add_action( 'wte_payment_gateway_hbl_enable', array( $this, 'process_booking' ), 10, 3 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'init', array( $this, 'handle_payment_callback' ), 20 );

		add_action(
			'wptravelengine_payment_notification',
			function( $default_callback, $action, $gateway ) {
				if ( 'hbl_enable' === $gateway ) {
					is_callable( array( $this, 'callback_' . $action ) ) && $this->{"callback_{$action}"}();
				}
			},
			10,
			3
		);

	}

	/**
	 * Handles Callback.
	 *
	 * @since 2.1.0
	 */
	public function handle_payment_callback() {

		$notification_version = apply_filters( 'wptravelengine_payment_notification_version', null );

		if ( is_null( $notification_version ) ) {

			if ( isset( $_REQUEST['_action'] ) && isset( $_REQUEST['_gateway'] ) && 'hbl_enable' === sanitize_text_field( wp_unslash( $_REQUEST['_gateway'] ) ) ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['_action'] ) );
				if ( in_array( $action, array( 'wtep_success', 'wtep_fail', 'wtep_cancel', 'wtep_ipn' ) ) ) {
					$_action = str_replace( 'wtep_', '', $action );
					is_callable( array( $this, 'callback_' . $_action ) ) && $this->{"callback_{$_action}"}();
				}
			}
		}
	}

	public function parse_order_number( $order_number ) {
		$data = get_transient( 'order_' . $order_number );
		if ( isset( $data['booking_id'], $data['payment_id'] ) ) {
			return array( $data['booking_id'], $data['payment_id'] );
		}
		return array( 0, 0 );
	}

	public function callback_success() {
		if ( isset( $_REQUEST['orderNo'] ) ) {
			list($booking_id, $payment_id) = $this->parse_order_number( wp_unslash( $_REQUEST['orderNo'] ) );
			$payment_key = wptravelengine_generate_key( $payment_id );
			$redirect_url = add_query_arg( array( 'payment_key' => $payment_key ), wp_travel_engine_get_booking_confirm_url() );
			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function callback_fail() {
		if ( isset( $_REQUEST['orderNo'] ) ) {

			$redirect_url = wp_travel_engine_get_checkout_url();
			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function callback_cancel() {
		if ( isset( $_REQUEST['orderNo'] ) ) {

			$redirect_url = wp_travel_engine_get_checkout_url();
			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function callback_ipn() {

		$response = file_get_contents( 'php://input' );

		$payment = new Payment();

		$decrypted_response = $payment->decrypt_token( $response );

		$response_parser = Response_Parser::json( $decrypted_response );

		list( $booking_id, $payment_id ) = $this->parse_order_number( $response_parser->get_order_number() );

		update_post_meta( $payment_id, 'gateway_response', $decrypted_response );

		$response_code = $response_parser->get_response_code();
		if ( 'PC-B050000' === $response_code ) {
			update_post_meta( $payment_id, 'payment_status', 'success' );
			$amount_data = $response_parser->get_transaction_amount_data();
			if ( $amount_data ) {
				update_post_meta(
					$payment_id,
					'payment_amount',
					array(
						'value'    => $amount_data->amount,
						'currency' => $amount_data->currencyCode,
					)
				);
				update_post_meta( $booking_id, 'wp_travel_engine_booking_status', 'booked' );
				$paid_amount = (float) get_post_meta( $booking_id, 'paid_amount', true ) + (float) $amount_data->amount;
				update_post_meta( $booking_id, 'paid_amount', $paid_amount );
				$due_amount = (float) get_post_meta( $booking_id, 'due_amount', true ) - (float) $amount_data->amount;
				update_post_meta( $booking_id, 'due_amount', $due_amount );
			}
			wptravelengine_send_booking_emails( $payment_id, 'order', 'all' );
			wptravelengine_send_booking_emails( $payment_id, 'order_confirmation', 'all' );
		} elseif ( 'PC-B050910' === $response_code ) {
			update_post_meta( $payment_id, 'payment_status', 'cancelled' );
		} else {
			update_post_meta( $payment_id, 'payment_status', 'failed' );
		}
	}

	/**
	 * Process Booking further.
	 *
	 * @since 2.1.0
	 */
	public function process_booking( $payment_id, $payment_mode, $payment_method ) {
		try {
			$booking_id = get_post_meta( $payment_id, 'booking_id', true );

			$payment = new Payment();
			$payment->setData( compact( 'payment_id', 'payment_mode', 'payment_method' ) );

			$response = $payment->execute_jose();

			$response_parser = Response_Parser::json( $response );

			if ( is_null( $response_parser ) ) {
				throw new \Exception( 'Invalid response from gateway endpoint.', -1 );
			}

			if ( $response_parser->is_success() ) {
				$payment_page = $response_parser->get_payment_page_url();

				wp_redirect( $payment_page );
				exit;
			}

			// TODO: Send to Checkout Page

		} catch ( GuzzleException $e ) {
			$this->handle_exception( $e );
		} catch ( \Exception $e ) {
			$this->handle_exception( $e );
		}
	}

	public function handle_exception( $e ) {
		if ( defined( 'WP_TRAVEL_ENGINE_PAYMENT_DEBUG' ) && WP_TRAVEL_ENGINE_PAYMENT_DEBUG ) {
			wte_log( $e->getMessage() );
			wte_log( $e->getTraceAsString() );
		}
		$session = WTE()->session;
		$errors  = $session->get( 'wp_travel_engine_errors' );
		if ( ! is_array( $errors ) ) {
			$errors = array();
		}
		$errors[] = $e->getMessage();
		$session->set( 'wp_travel_engine_errors', $errors );
	}

	/**
	 * Enabled payhere
	 *
	 * @return void
	 */
	public function hbl_enabled() {
		$wte_settings = get_option( 'wp_travel_engine_settings' );
		return isset( $wte_settings['hbl_enable'] ) && '1' == $wte_settings['hbl_enable'];
	}

	/**
	 * Scripts Enqueue.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

	}

    /**
	 * Execute Plugin.
	 *
	 * @return void
	 */
	public static function execute() {
        new WTE_HBL_Payments_Handler();
    }

}

