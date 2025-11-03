<?php
// phpcs:ignoreFile
namespace WTEHBL\Payment;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Client;

class Payment extends ActionRequest {

	protected $settings = null;

	public function __construct() {

		SecurityData::$EncryptionKeyId = $this->get_settings( 'key_id', '' );
		SecurityData::$AccessToken = $this->get_settings( 'api_key', '' );

		parent::__construct();

		$handler = HandlerStack::create();

		$handler->push(
			Middleware::mapRequest(
				function ( RequestInterface $request ) {
					return $request->withoutHeader( 'User-Agent' );
				}
			)
		);

		$args = array(
			'base_uri' => defined( 'WP_TRAVEL_ENGINE_PAYMENT_DEBUG' ) && WP_TRAVEL_ENGINE_PAYMENT_DEBUG ? 'https://core.demo-paco.2c2p.com/' : 'https://core.paco.2c2p.com/',
			'handler'  => $handler,
		);

		$this->client = new Client( $args );
	}

	public function setData( $args ) {
		$this->payment_id     = $args['payment_id'];
		$this->payment_mode   = $args['payment_mode'];
		$this->payment_method = $args['payment_method'];

		$booking_id = get_post_meta( $this->payment_id, 'booking_id', true );

		$this->booking = get_post( $booking_id );
	}

	public function get_order_number( $now ) {
		return round( (float) $now->format('Uu') / pow(10, 3) );
	}

	public function get_description() {
		$orders = $this->booking->order_trips;
		foreach ( $orders as $order ) {
			if ( strlen( $order['title'] ) > 30 ) {
				$order['title'] = substr( $order['title'], 0, 25 ) . '...';
			}
			return 'Trip : ' . $order['title'];
		}
	}

	public function get_transaction_data() {
		$payable = get_post_meta( $this->payment_id, 'payable', true );

		$amount_text = '000000000000';

		$amount      = $payable['amount'];
		$amount      = explode( '.', $amount );
		$amount_text = substr_replace( $amount_text, $amount[0], ( 12 - strlen( $amount[0] ) - 2 ), strlen( $amount[0] ) );
		if ( isset( $amount[1] ) ) {
			$amount_text = substr_replace( $amount_text, $amount[1], -2, strlen( $amount[1] ) );
		}

		return array(
			'amountText'    => $amount_text,
			'currencyCode'  => $payable['currency'],
			'decimalPlaces' => 2,
			'amount'        => $payable['amount'],
		);
	}

	public function get_notification_urls() {
		$notification_url_base = home_url();

		return array(
			'confirmationURL' => $this->get_settings( 'confirmation_url', $notification_url_base . '?_action=wtep_success&_gateway=hbl_enable' ),
			'failedURL'       => $this->get_settings( 'failed_url', $notification_url_base . '?_action=wtep_fail&_gateway=hbl_enable' ),
			'cancellationURL' => $this->get_settings( 'cancel_url', $notification_url_base . '?_action=wtep_cancel&_gateway=hbl_enable' ),
			'backendURL'      => $this->get_settings( 'backend_url', $notification_url_base . '?_action=wtep_ipn&_gateway=hbl_enable' ),
		);
	}

	public function get_settings( $field, $default = false ) {
		if ( is_null( $this->settings ) ) {
			$this->settings = get_option( 'wp_travel_engine_settings', array() );
		}
		if ( isset( $this->settings['hbl_settings'] ) && isset( $this->settings['hbl_settings'][ $field ] ) ) {
			return $this->settings['hbl_settings'][ $field ];
		}
		return $default;
	}

	public function get_office_id() {
		return $this->get_settings( 'office_id', 'DEMOOFFICE' );
	}

	public function get_request_data( $data = array() ) {
		$now = new \DateTime();
		$now->setTimezone( new \DateTimeZone("UTC") );

		$order_number = $this->get_order_number( $now );

		$session    = \WP_Session::get_instance();
		$session_id = $session->session_id;

		$session->set('waiting_response', [
			'order_number' => $order_number,
			'booking_id'   => $this->booking->ID,
			'payment_id'   => $this->payment_id,
		]);

		set_transient(
			'order_' . $order_number,
			array(
				'customer_id' => $session_id
			),
			30 * 60
		);

		$request = array(
			'apiRequest'         => array(
				'requestMessageID' => $this->Guid(),
				'requestDateTime'  => $now->format( 'Y-m-d\TH:i:s.v\Z' ),
				'language'         => 'en-US',
			),
			'officeId'           => $this->get_office_id(),
			'orderNo'            => $order_number,
			'productDescription' => $this->get_description(),
			'paymentType'        => 'CC',
			'paymentCategory'    => 'ECOM',
			'mcpFlag'            => 'N',
			'request3dsFlag'     => 'N',
			'transactionAmount'  => $this->get_transaction_data(),
			'notificationURLs'   => $this->get_notification_urls(),
			'customFieldList'    => array(
				array(
					'fieldName'  => 'cart_key',
					'fieldValue' => 'This is test',
				),
			),
		);

		return json_encode( $request );
	}

	public function get_access_token() {
		return $this->get_settings( 'api_key', '' );
	}

	/**
	 * @throws GuzzleException
	 */
	public function Execute(): string {

		$request_body = $this->get_request_data();
		// third-party http client https://github.com/guzzle/guzzle
		$response = $this->client->post(
			'api/1.0/Payment/prePaymentUI',
			array(
				'headers' => array(
					'Accept'       => 'application/json',
					'apiKey'       => $this->get_access_token(),
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => $request_body,
			)
		);

		return $response->getBody()->getContents();
	}

	/**
	 *
	 */
	public function is_success( $response ) {
		return is_object( $response ) && isset( $response->apiResponse->responseCode ) && 'PC-B050001' === $response->apiResponse->responseCode;
	}

	public function execute_jose() {
		$now = new \DateTime();
		$now->setTimezone( new \DateTimeZone("UTC") );
		$order_number = $this->get_order_number( $now );
		$session    = WTE()->session;
		$session->set('waiting_response', [
			'order_number' => $order_number,
			'booking_id'   => $this->booking->ID,
			'payment_id'   => $this->payment_id,
		]);

		set_transient(
			'order_' . $order_number,
			array(
				'booking_id'   => $this->booking->ID,
				'payment_id'   => $this->payment_id,
				'customer_id'  => \WP_Session::get_instance()->session_id,
			),
			30 * 60
		);

		$request = array(
			'apiRequest'         => array(
				'requestMessageID' => $this->Guid(),
				'requestDateTime'  => $now->format( 'Y-m-d\TH:i:s.v\Z' ),
				'language'         => 'en-US',
			),
			'officeId'           => $this->get_office_id(),
			'orderNo'            => $order_number,
			'productDescription' => $this->get_description(),
			'paymentType'        => 'CC',
			'paymentCategory'    => 'ECOM',
			'mcpFlag'                   => 'N',
			'request3dsFlag'            => 'Y',
			'transactionAmount'  => $this->get_transaction_data(),
			'notificationURLs'   => $this->get_notification_urls(),
		);

		$access_token = $this->get_settings( 'api_key', '' );

		$payload = array(
			'request'       => $request,
			'iss'           => $access_token,
			'aud'           => 'PacoAudience',
			'CompanyApiKey' => $access_token,
			'iat'           => $now->getTimestamp(),
			'nbf'           => $now->getTimestamp(),
			'exp'           => $now->getTimestamp() + 3600,
		);

		$stringPayload = json_encode( $payload );
		$signingKey    = $this->GetPrivateKey( $this->get_settings( 'merchant_signing_private_key', '' ) );
		$encryptingKey = $this->GetPublicKey( $this->get_settings( 'paco_encryption_public_key', '' ) );

		$body = $this->EncryptPayload( $stringPayload, $signingKey, $encryptingKey );

		// third-party http client https://github.com/guzzle/guzzle
		$response = $this->client->post(
			'api/1.0/Payment/prePaymentUI',
			array(
				'headers' => array(
					'Accept'        => 'application/jose',
					'CompanyApiKey' => $access_token,
					'Content-Type'  => 'application/jose; charset=utf-8',
				),
				'body'    => $body,
			)
		);

		$token                    = $response->getBody()->getContents();

		return $this->decrypt_token( $token );

	}

	public function decrypt_token( $token ) {
		$decryptingKey            = $this->GetPrivateKey( $this->get_settings( 'merchant_decryption_private_key', '' ) );
		$signatureVerificationKey = $this->GetPublicKey( $this->get_settings( 'paco_signing_public_key', '' ) );
		return $this->DecryptToken( $token, $decryptingKey, $signatureVerificationKey );
	}
}
