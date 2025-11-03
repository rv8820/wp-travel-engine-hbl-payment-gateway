<?php
/**
 * Response Class.
 */
namespace WTEHBL\Payment\Payment;

class Response_Parser {
	const SUCCESS_CODE = 'PC-B050001';
	/**
	 * Constructor.
	 */
	public function __construct( $response ) {
		$this->response         = $response;
		$this->is_jose_response = isset( $this->response->response );
	}

	public static function json( $response ) {
		$response = json_decode( $response );
		if ( is_null( $response ) ) {
			return $response;
		}
		return new self( $response );
	}

	public function get_response_code() {
		if ( $this->is_jose_response && isset( $this->response->response->ApiResponse->ResponseCode ) ) {
			return $this->response->response->ApiResponse->ResponseCode;
		}
		if ( isset( $this->response->apiResponse->responseCode ) ) {
			return $this->response->apiResponse->responseCode;
		}
		return false;
	}

	public function get_transaction_amount_data() {
		if ( $this->is_jose_response ) {
			if ( isset( $this->response->response->Data->paymentIncompleteResult->transactionAmount ) ) {
				return (object) array(
					'amount'       => $this->response->response->Data->paymentIncompleteResult->transactionAmount->Amount,
					'currencyCode' => $this->response->response->Data->paymentIncompleteResult->transactionAmount->CurrencyCode,
				);
			}
			if ( isset( $this->response->response->Data->paymentResult->transactionAmount ) ) {
				return (object) array(
					'amount'       => $this->response->response->Data->paymentResult->transactionAmount->Amount,
					'currencyCode' => $this->response->response->Data->paymentResult->transactionAmount->CurrencyCode,
				);
			}
		}
		if ( isset( $this->response->data->paymentResult->transactionAmount ) ) {
			return (object) array(
				'amount'       => $this->response->data->paymentResult->transactionAmount->amount,
				'currencyCode' => $this->response->data->paymentResult->transactionAmount->currencyCode,
			);
		}
		return false;
	}

	public function get_order_number() {
		if ( $this->is_jose_response ) {
			if ( isset( $this->response->response->Data->paymentIncompleteResult->orderNo ) ) {
				return $this->response->response->Data->paymentIncompleteResult->orderNo;
			}
			if ( isset( $this->response->response->Data->paymentResult->orderNo ) ) {
				return $this->response->response->Data->paymentResult->orderNo;
			}
		}
		if ( isset( $this->response->data->paymentResult->orderNo ) ) {
			return $this->response->data->paymentResult->orderNo;
		}
		return false;
	}

	public function is_success() {
		$code = $this->get_response_code();
		return self::SUCCESS_CODE === $code;
	}

	public function get_payment_page_url() {
		if ( $this->is_jose_response ) {
			if ( isset( $this->response->response->Data->paymentPage->paymentPageURL ) ) {
				return $this->response->response->Data->paymentPage->paymentPageURL;
			}
		}
		return $this->response->data->paymentPage->paymentPageURL;
	}
}
