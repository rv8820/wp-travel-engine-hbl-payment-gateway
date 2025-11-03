<?php
// admin settings
class Wte_HBL_Admin {

	public function __construct() {
		add_action( 'wte_hbl_settings', array( $this, 'wte_hbl_settings' ) );
		add_action( 'wte_hbl_enable', array( $this, 'wte_hbl_enable' ) );
		// Removed backend assets enqueue @since 2.2.2
		// add_action( 'admin_enqueue_scripts', array( $this, 'Wte_enqueue_backend_assets' ) );
		// Removed frontend assets enqueue @since 2.2.2
		// add_action( 'wp_enqueue_scripts', array( $this, 'Wte_enqueue_frontend_assets' ) );
		$wp_travel_engine_settings = get_option( 'wp_travel_engine_settings', true );
		if ( isset( $wp_travel_engine_settings['hbl_enable'] ) ) {
			add_filter( 'wte_payment_gateways_dropdown_options', array( $this, 'wte_enable_hbl_dropdown' ) );
		}
		// action called through HBL checkout form
		add_action( 'wp_ajax_hbl_checkout_form', array( $this, 'hbl_checkout_form' ) );
		add_action( 'wp_ajax_nopriv_hbl_checkout_form', array( $this, 'hbl_checkout_form' ) );

		// shortcode to add on a front-end url page, to get HBL payment response
		add_shortcode( 'WTE_HBL_PAYMENT_RESPONSE', array( $this, 'get_payment_response' ) );

		// shortcode to display custom HBL payment form
		add_shortcode( 'WTE_HBL_CUSTOM_PAYMENT', array( $this, 'hbl_custom_payment_form' ) );

		// action called through HBL custom payment form
		add_action( 'wp_ajax_hbl_custom_form_action', array( $this, 'hbl_custom_form_action' ) );
		add_action( 'wp_ajax_nopriv_hbl_custom_form_action', array( $this, 'hbl_custom_form_action' ) );

		add_action( 'add_meta_boxes', array( $this, 'wpte_hbl_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'wp_travel_engine_hbl_meta_box_data' ) );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		add_filter( 'wp_travel_engine_available_payment_gateways', array( $this, 'add_to_gateway_list' ) );

		add_filter( 'wpte_settings_get_global_tabs', array( $this, 'add_hbl_global_settings' ) );
	}

	function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wte-hbl',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Payment Details.
	 *
	 * @since 1.0
	 */
	function wpte_hbl_add_meta_boxes() {
		$screens = array( 'booking' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'hbl_id',
				__( 'HBL Payment Details', 'wte-hbl' ),
				array( $this, 'wte_hbl_metabox_callback' ),
				$screen,
				'side',
				'high'
			);
		}
	}

	// Tab for notice listing and settings
	public function wte_hbl_metabox_callback() {
		include WP_TRAVEL_ENGINE_HBL_BASE_PATH . '/admin/includes/backend/hbl.php';
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	function wp_travel_engine_hbl_meta_box_data( $post_id ) {

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */
		// Sanitize user input.
		if ( isset( $_POST['wp_travel_engine_booking_setting'] ) ) {
			$settings = $_POST['wp_travel_engine_booking_setting'];
			update_post_meta( $post_id, 'wp_travel_engine_booking_setting', $settings );
		}
	}

	// enable hbl on dropdown
	function wte_enable_hbl_dropdown( $options ) {
		$options[] = 'Himalayan-Bank';
		return $options;
	}

	function wte_hbl_settings() {
		$wp_travel_engine_settings = get_option( 'wp_travel_engine_settings', true );
		?>
	<div class="wte-hbl-form settings">
	  <h4><?php _e( 'Himalayan Bank Limited Payment Settings', 'wte-hbl' ); ?></h4>
	  <label for="wp_travel_engine_settings[hbl_merchant_id]"><?php _e( 'Merchant ID : ', 'wte-hbl' ); ?></label>
	  <input type="text" id="wp_travel_engine_settings[hbl_merchant_id]" name="wp_travel_engine_settings[hbl_merchant_id]" value="<?php echo isset( $wp_travel_engine_settings['hbl_merchant_id'] ) ? esc_attr( $wp_travel_engine_settings['hbl_merchant_id'] ) : ''; ?>">
	  <div class="settings-note"><?php _e( 'Enter a valid Client ID from HBL account. All payments will go to this account.', 'wte-hbl' ); ?></div>
	  <label for="wp_travel_engine_settings[hbl_secret_key]"><?php _e( 'Secret Key: ', 'wte-hbl' ); ?></label>
	  <input type="text" id="wp_travel_engine_settings[hbl_secret_key]" name="wp_travel_engine_settings[hbl_secret_key]" value="<?php echo isset( $wp_travel_engine_settings['hbl_secret_key'] ) ? esc_attr( $wp_travel_engine_settings['hbl_secret_key'] ) : ''; ?>">
	  <div class="settings-note"><?php _e( 'Enter a valid Secret Key from HBL account.', 'wte-hbl' ); ?></div>
	</div>
		<?php
	}

	// admin enable settings
	function wte_hbl_enable() {
		 $wp_travel_engine_settings = get_option( 'wp_travel_engine_settings', true );
		?>
	<div class="wte-hbl-form wp-travel-engine-settings">
	  <label for="wp_travel_engine_settings[hbl_enable]"><?php _e( 'HBL Payment : ', 'wte-hbl' ); ?>
		<span class="tooltip" title="<?php _e( 'Please check this to enable Himalayan Bank Limited Payment booking system for trip booking and fill the account info below.', 'wte-hbl' ); ?>"><i class="fas fa-question-circle"></i></span>
	  </label>
	  <input type="checkbox" id="wp_travel_engine_settings[hbl_enable]" class="hbl" name="wp_travel_engine_settings[hbl_enable]" value="1"
		<?php
		if ( isset( $wp_travel_engine_settings['hbl_enable'] ) && $wp_travel_engine_settings['hbl_enable'] != '' ) {
			echo 'checked';}
		?>
		>
	  <label for="wp_travel_engine_settings[hbl_enable]" class="checkbox-label"></label>
	</div>
		<?php
	}

	/**
	 * Add HBL to payment gateway list.
	 *
	 * @param mixed $list Payment gateway list.
	 * @return mixed Modified payment gateway list.
	 */
	public function add_to_gateway_list( $list ) {
		$list['hbl_enable'] = array(
			'label'       => __( 'HBL Payment', 'wte-hbl' ),
			'input_class' => 'hbl',
			'info_text'   => __( 'Please check this to enable Himalayan Bank Limited Payment booking system for trip booking and fill the account info below.', 'wte-hbl' ),
		);

		return $list;
	}


	function hbl_custom_form_action() {
		 ob_start();

		if ( $_POST['email'] == '' || $_POST['amount1'] == '' || $_POST['notravellers'] == '' || $_POST['date'] == '' ) {
			$result['type']    = 'error';
			$result['message'] = __( 'Please enter all the fields.', 'wte-hbl' );
			echo json_encode( $result );
			exit;
		}

		$wp_travel_engine_settings = get_option( 'wp_travel_engine_settings', true );
		$obj                       = new Wp_Travel_Engine_Functions();

		$invoiceNo  = str_pad( rand( 100000, (int) 99999999999999999999 ), 20, '0', STR_PAD_LEFT );
		$cost       = isset( $_POST['amount1'] ) ? $_POST['amount1'] : $_SESSION['trip-cost'];
		$amount     = str_replace( ',', '', $cost );
		$amount     = (int) $amount * 100;
		$amount     = str_pad( ( $amount ), 12, '0', STR_PAD_LEFT );
		$merchantId = $wp_travel_engine_settings['hbl_merchant_id'];

		$code            = isset( $wp_travel_engine_settings['currency_code'] ) ? $wp_travel_engine_settings['currency_code'] : 'USD';
		$currencyCode    = $obj->wp_travel_engine_currencies_symbol( $code );
		$isoCurrencyCode = 'USD' === $code ? 840 : 524;
		$secretKey       = $wp_travel_engine_settings['hbl_secret_key'];
		$nonSecure       = $_POST['notravellers'];
		$nonSecure       = '1' === $nonSecure ? 'Y' : 'N';
		$signatureString = $merchantId . $invoiceNo . $amount . $isoCurrencyCode . $nonSecure;

		$signData = hash_hmac( 'SHA256', $signatureString, $secretKey, false );
		$signData = strtoupper( $signData );

		?>
	  <div class="error"></div>
	  <div class="successful"></div>
	  <input type="hidden" id="paymentGatewayID" name="paymentGatewayID" value="<?php echo esc_attr( $merchantId ); ?>"/>
	  <input type="hidden" id="invoiceNo" name="invoiceNo" value="<?php echo esc_attr( $invoiceNo ); ?>"/>
	  <input type="hidden" id="productDesc" name="productDesc" value="<?php echo esc_attr( $_POST['message'] ); ?>"/>
	  <input type="hidden" id="amount" name="amount" value="<?php echo esc_attr( $amount ); ?>"/>
	  <input type="hidden" id="currencyCode" name="currencyCode" value="<?php echo esc_attr( $isoCurrencyCode ); ?>"/>
	  <input type="hidden" id="userDefined1" name="userDefined1" value="<?php echo $_POST['name'] . '*' . $_POST['lastname'] . '*' . $_POST['email'] . '*' . $_POST['phone'] . '*' . $_POST['city'] . '*' . $_POST['countries'] . '*' . $_POST['date'] . '*' . $_POST['amount1'] . '*' . $_POST['trip'] . '*' . $_POST['notravellers']; ?>"/>
	  <input type="hidden" id="nonSecure" name="nonSecure" value="<?php echo esc_attr( $nonSecure ); ?>"/>
	  <input type="hidden" id="hashValue" name="hashValue" value="<?php echo esc_attr( $signData ); ?>"/>

		<?php
		$data = ob_get_clean();
		wp_send_json_success( $data );
		exit();
	}

	function get_payment_response() {
		if ( $_POST['respCode'] == '00' && $_POST['fraudCode'] == '00' ) {

			if ( ! isset( $_POST['userDefined1'] ) || empty( $_POST['userDefined1'] ) ) {

				$booking_id = isset( $_POST['userDefined2'] ) && ! empty( $_POST['userDefined2'] ) ? $_POST['userDefined2'] : false;

				if ( ! $booking_id ) {
					return;
				}

				$wte_settings = get_option( 'wp_travel_engine_settings' );

				// Setup default merchant data.
				$merchantId      = isset( $wte_settings['hbl_merchant_id'] ) ? $wte_settings['hbl_merchant_id'] : '';
				$secretKey       = isset( $wte_settings['hbl_secret_key'] ) ? $wte_settings['hbl_secret_key'] : '';
				$invoiceNo       = isset( $_POST['invoiceNo'] ) ? $_POST['invoiceNo'] : '';
				$amount          = isset( $_POST['amount'] ) ? $_POST['amount'] : '';
				$currencyCode    = wp_travel_engine_get_currency_code();
				$isoCurrencyCode = 'USD' === $currencyCode ? 840 : 524;
				$nonSecure       = '';

				$signatureString = $merchantId . $invoiceNo . $amount . $isoCurrencyCode . $nonSecure;

				$signData = hash_hmac( 'SHA256', $signatureString, $secretKey, false );
				$signData = strtoupper( $signData );

				if ( $_POST['hashValue'] != $signData ) {
					return;
				}

				$payment_details = array(
					'status'  => $_POST['Status'],
					'invoice' => $_POST['invoiceNo'],
					'rescode' => $_POST['respCode'],
					'tranRef' => $_POST['tranRef'],
				);

				$payment_id = get_post_meta( $booking_id, 'payments', true );
				$pay_id     = $payment_id[0];
				$payment    = get_post( $pay_id );
				$payable    = $payment->payable;

				// Payment status update.
				update_post_meta( $pay_id, 'payment_status', 'completed' );

				$payment_meta_input = array();

				WTE_Booking::update_booking(
					$booking_id,
					array(
						'meta_input' => array(
							'paid_amount' => +$booking->paid_amount + +$payable['amount'],
							'due_amount'  => +$booking->paid_amount - +$payable['amount'],
							'wp_travel_engine_booking_status' => 'booked',
						),
					)
				);

				$payment_meta_input['payment'] = array(
					'value'    => $payable['amount'],
					'currency' => $payable['currency'],
				);

				$payment_meta_input['gateway_response'] = $payment_details;

				WTE_Booking::update_booking(
					$pay_id,
					array(
						'meta_input' => $payment_meta_input,
					)
				);

				return;

			}
			// If the transaction was valid
			$new_post = array(
				'post_status' => 'publish',
				'post_type'   => 'booking',
				'post_title'  => 'booking',
			);
			$post_id  = wp_insert_post( $new_post );

			$_SESSION['tid'] = $post_id;

			$userDefined1 = $_POST['userDefined1'];

			$res = explode( '*', $userDefined1 );

			$fname     = $res[0];
			$lname     = $res[1];
			$email     = $res[2];
			$address   = $res[3];
			$city      = $res[4];
			$country   = $res[5];
			$datetime  = $res[6];
			$cost      = $res[7];
			$tid       = $res[8];
			$travelers = $res[9];

			$post = get_post( $tid );
			$slug = $post->post_title;

			$book_post = array(
				'ID'         => $post_id,
				'post_title' => 'booking ' . $post_id,
			);
			// Update the post into the database
			$updated = wp_update_post( $book_post );

			$order_metas =
			array(
				'place_order' => array(
					'traveler' => esc_attr( $travelers ),
					'cost'     => esc_attr( $cost ),
					'due'      => '',
					'tid'      => esc_attr( $tid ),
					'tname'    => esc_attr( $slug ),
					'datetime' => esc_attr( $datetime ),
					'booking'  => array(
						'fname'   => $fname,
						'lname'   => $lname,
						'email'   => $email,
						'address' => $address,
						'city'    => $city,
						'country' => $country,
					),
					'payment'  => array(
						'status'  => $_POST['Status'],
						'invoice' => $_POST['invoiceNo'],
						'rescode' => $_POST['respCode'],
						'tranRef' => $_POST['tranRef'],
					),
				),
			);
			$bid[]       = $post_id;
			$order_metas = array_merge_recursive( $order_metas, $bid );
			update_post_meta( $post_id, 'wp_travel_engine_booking_setting', $order_metas );

			global $wpdb;
			if ( ! is_admin() ) {
				require_once ABSPATH . 'wp-admin/includes/post.php';
			}

			if ( post_exists( $order_metas['place_order']['booking']['email'], '', '' ) == 0 ) {
				$new_post = array(
					'post_status' => 'publish',
					'post_type'   => 'customer',
					'post_title'  => 'customer',
				);
				$post_id  = wp_insert_post( $new_post );

				foreach ( $order_metas['place_order'] as $key => $value ) {
					$arr[ $key ][1] = $value;
				}
				unset( $arr['booking'] );

				$booked_id[] = $order_metas[0];

				if ( ! isset( $booked_id ) && ! is_array( $booked_id ) ) {
					$booked_id = array();
				}

				update_post_meta( $post_id, 'wp_travel_engine_bookings', $booked_id );
				update_post_meta( $post_id, 'wp_travel_engine_booking_setting', $order_metas );
				update_post_meta( $post_id, 'wp_travel_engine_booked_trip_setting', $arr );

				$customer_post = array(
					'ID'         => $post_id,
					'post_title' => esc_attr( $order_metas['place_order']['booking']['email'] ),
				);
				// Update the post into the database
				$updated = wp_update_post( $customer_post );

				if ( false === $updated ) {
					_e( 'There was an error on update.', 'wte-hbl' );
				}
				require_once WP_TRAVEL_ENGINE_BASE_PATH . '/includes/class-wp-travel-engine-mail.php';
				$obj = new Wp_Travel_Engine_Mail_Template();
				$obj->mail_editor( $order_metas, $post_id );
				_e( 'Thank you for booking the trip. Please check your email for confirmation.', 'wte-hbl' );
			} else {
				$pid      = get_page_by_title( $order_metas['place_order']['booking']['email'], OBJECT, 'customer' );
				$my_array = get_post_meta( $pid->ID, 'wp_travel_engine_booked_trip_setting', true );

				if ( isset( $my_array ) && $my_array != '' ) {
					$size = sizeof( $my_array['traveler'] );
				} else {
					$my_array[] = '';
					$size       = 0;
				}
				$size++;
				foreach ( $order_metas['place_order'] as $key => $value ) {
					$arr[ $key ][ $size ] = $value;
				}
				unset( $arr['booking'] );
				$a           = array_merge_recursive( $my_array, $arr );
				$my_bookings = get_post_meta( $pid->ID, 'wp_travel_engine_bookings', true );
				$booked_id[] = $order_metas[0];
				if ( ! isset( $booked_id ) && ! is_array( $booked_id ) ) {
					$booked_id = array();
				}
				if ( isset( $my_bookings ) && $my_bookings != '' ) {
					$my_bookings = array_merge_recursive( $my_bookings, $booked_id );
				} else {
					$my_bookings[] = '';
				}
				update_post_meta( $pid->ID, 'wp_travel_engine_booked_trip_setting', $a );
				update_post_meta( $pid->ID, 'wp_travel_engine_bookings', $my_bookings );
			}
			if ( false === $updated ) {
				_e( 'There was an error on update.', 'wte-hbl' );
			}
			require_once WP_TRAVEL_ENGINE_BASE_PATH . '/includes/class-wp-travel-engine-mail.php';
			$obj = new Wp_Travel_Engine_Mail_Template();
			$obj->mail_editor( $order_metas, $post_id );
			_e( 'Thank you for booking the trip. Please check your email for confirmation.', 'wte-hbl' );
		} else {
			_e( 'Please book a Trip first in order to view the Payment Response.', 'wte-hbl' );
		}

	}


	function hbl_checkout_form() {
		ob_start();
		$wp_travel_engine_settings = get_option( 'wp_travel_engine_settings', true );
		$obj                       = new Wp_Travel_Engine_Functions();

		$invoiceNo  = str_pad( rand( 100000, (int) 99999999999999999999 ), 20, '0', STR_PAD_LEFT );
		$cost       = isset( $_POST['cost'] ) ? $_POST['cost'] : $_SESSION['trip-cost'];
		$amount     = str_replace( ',', '', $cost );
		$amount     = (int) $amount * 100;
		$amount     = str_pad( ( $amount ), 12, '0', STR_PAD_LEFT );
		$merchantId = $wp_travel_engine_settings['hbl_merchant_id'];

		$code            = isset( $wp_travel_engine_settings['currency_code'] ) ? $wp_travel_engine_settings['currency_code'] : 'USD';
		$currencyCode    = $obj->wp_travel_engine_currencies_symbol( $code );
		$isoCurrencyCode = 'USD' === $code ? 840 : 524;
		$secretKey       = $wp_travel_engine_settings['hbl_secret_key'];
		$nonSecure       = $_SESSION['travelers'];
		$nonSecure       = '1' === $nonSecure ? 'Y' : 'N';
		$signatureString = $merchantId . $invoiceNo . $amount . $isoCurrencyCode . $nonSecure;

		$signData = hash_hmac( 'SHA256', $signatureString, $secretKey, false );
		$signData = strtoupper( $signData );

		$billing_options = $obj->order_form_billing_options();

		foreach ( $billing_options as $key => $value ) {
			?>
		<div class='wp-travel-engine-billing-details-field-wrap'>
			  <?php
				switch ( $key ) {

					case 'fname':
						?>

				<label for="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]"><?php _e( $value['label'], 'wte-hbl' ); ?><span class="required">*</span></label>
				<input value="<?php echo $_POST['firstname']; ?>" type="<?php echo $value['type']; ?>" name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>"
										 <?php
											if ( $value['required'] == '1' ) {
												echo 'required';   }
											?>
				>
						<?php
						break;

					case 'lname':
						?>

				<label for="<?php echo esc_attr( $key ); ?>"><?php _e( $value['label'], 'wte-hbl' ); ?><span class="required">*</span></label>
				<input value="<?php echo $_POST['lastname']; ?>" type="<?php echo $value['type']; ?>" name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>"
										 <?php
											if ( $value['required'] == '1' ) {
												echo 'required';   }
											?>
				>
						<?php
						break;

					case 'email':
						?>

				<label for="<?php echo esc_attr( $key ); ?>"><?php _e( $value['label'], 'wte-hbl' ); ?><span class="required">*</span></label>
				<input value="<?php echo $_POST['email']; ?>" type="<?php echo $value['type']; ?>" name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>"
										 <?php
											if ( $value['required'] == '1' ) {
												echo 'required';   }
											?>
				>
						<?php
						break;

					case 'address':
						?>

				<label for="<?php echo esc_attr( $key ); ?>"><?php _e( $value['label'], 'wte-hbl' ); ?><span class="required">*</span></label>
				<input value="<?php echo $_POST['address1']; ?>" type="<?php echo $value['type']; ?>" name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>"
										 <?php
											if ( $value['required'] == '1' ) {
												echo 'required';   }
											?>
				>
						<?php
						break;

					case 'city':
						?>

				<label for="<?php echo esc_attr( $key ); ?>"><?php _e( $value['label'], 'wte-hbl' ); ?><span class="required">*</span></label>
				<input value="<?php echo $_POST['city']; ?>" type="<?php echo $value['type']; ?>" name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>"
										 <?php
											if ( $value['required'] == '1' ) {
												echo 'required';   }
											?>
				>
						<?php
						break;

					case 'postcode':
						?>

				<label for="<?php echo esc_attr( $key ); ?>"><?php _e( $value['label'], 'wte-hbl' ); ?><span class="required">*</span></label>
				<input value="<?php echo $_POST['postcode']; ?>" type="<?php echo $value['type']; ?>" name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>"
										 <?php
											if ( $value['required'] == '1' ) {
												echo 'required';   }
											?>
				>
						<?php
						break;

					case 'country':
						?>
				<label for="<?php echo esc_attr( $key ); ?>"><?php _e( $value['label'], 'wte-hbl' ); ?><span class="required">*</span></label>
				<select required id="<?php echo esc_attr( $key ); ?>" name="wp_travel_engine_booking_setting[place_order][booking][<?php echo esc_attr( $key ); ?>]" data-placeholder="<?php esc_attr_e( 'Choose a field type&hellip;', 'wte-hbl' ); ?>" class="wc-enhanced-select" >
					<option value=" "><?php _e( 'Choose country&hellip;', 'wte-hbl' ); ?></option>
						<?php
						$options = $obj->wp_travel_engine_country_list();
						foreach ( $options as $key => $val ) {
							echo '<option value="' . ( ! empty( $val ) ? esc_attr( $val ) : 'Please select' ) . '"' . selected( $_POST['country'], $val, false ) . '>' . esc_html( $val ) . '</option>';
						}
						?>
				</select>
						<?php
						break;
				}
				?>
		</div>
		<?php } ?>
	  <div class="error"></div>
	  <div class="successful"></div>
	  <input type="hidden" id="paymentGatewayID" name="paymentGatewayID" value="<?php echo esc_attr( $merchantId ); ?>"/>
	  <input type="hidden" id="invoiceNo" name="invoiceNo" value="<?php echo esc_attr( $invoiceNo ); ?>"/>
	  <input type="hidden" id="productDesc" name="productDesc" value="<?php echo esc_attr( get_the_title( $_SESSION['trip-id'] ) ); ?>"/>
	  <input type="hidden" id="amount" name="amount" value="<?php echo esc_attr( $amount ); ?>"/>
	  <input type="hidden" id="currencyCode" name="currencyCode" value="<?php echo esc_attr( $isoCurrencyCode ); ?>"/>
	  <input type="hidden" id="userDefined1" name="userDefined1" value="<?php echo $_POST['firstname'] . '*' . $_POST['lastname'] . '*' . $_POST['email'] . '*' . $_POST['address1'] . '*' . $_POST['city'] . '*' . $_POST['country'] . '*' . $_SESSION['trip-date'] . '*' . $_SESSION['trip-cost'] . '*' . $_SESSION['trip-id'] . '*' . $_SESSION['travelers']; ?>"/>
	  <input type="hidden" id="nonSecure" name="nonSecure" value="<?php echo esc_attr( $nonSecure ); ?>"/>
	  <input type="hidden" id="hashValue" name="hashValue" value="<?php echo esc_attr( $signData ); ?>"/>

		<?php
		// $_SESSION = $_POST;
		$data = ob_get_clean();
		wp_send_json_success( $data );
		exit();
	}

	// HBL integration in Custom Payment Form
	function hbl_custom_payment_form() {
		if ( isset( $_GET['id'] ) ) {
			  $pid                           = isset( $_GET['id'] ) ? esc_attr( $_GET['id'] ) : '';
			  $wp_travel_engine_settings     = get_option( 'wp_travel_engine_settings', true );
			  $wp_travel_engine_trip_setting = get_post_meta( $pid, 'wp_travel_engine_setting', true );
			if ( isset( $wp_travel_engine_trip_setting['sale'] ) && $wp_travel_engine_trip_setting['trip_price'] != '' ) {
				$flag1     = 1;
				$trip_cost = $wp_travel_engine_trip_setting['trip_price'];
			} elseif ( isset( $wp_travel_engine_trip_setting['trip_prev_price'] ) && $wp_travel_engine_trip_setting['trip_prev_price'] != '' ) {
				$flag1     = 1;
				$trip_cost = $wp_travel_engine_trip_setting['trip_prev_price'];
			} else {
				$trip_cost = 0;
			}

			$tcost = esc_attr( str_replace( ',', '', $trip_cost ) );

			if ( class_exists( 'Wte_Partial_Payment_Admin' ) && isset( $wp_travel_engine_settings['partial_payment_enable'] ) ) {

				$partial = $wp_travel_engine_settings['partial_payment_percent'];
				$partial = 100 - $partial;

				$deposit_cost = ( $tcost ) - ( $partial / 100 ) * $tcost;
				$tcost        = round( $deposit_cost );
				$flag         = 1;
			}
		}
		ob_start();
		?>
  <form id="wte-hbl-payment" method="POST">
	<div class="wte-hbl-row">
	  <div class="wte-hbl-col-half">
		  <input type="text" required class="form-control" id="fullname" name="fullname" placeholder="Enter Full name*" />
	  </div>
	  <div class="wte-hbl-col-half">
		  <input type="email" class="form-control" name="email" id="email" placeholder="Enter email*" required>
	  </div>
	  <div class="wte-hbl-col-half">
		  <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter Phone*" required>
	  </div>
	  <div class="wte-hbl-col-half">
		  <select id="countries" name="countries" required>
			<option value=" "><?php _e( 'Choose country&hellip;', 'wte-hbl' ); ?></option>
			  <?php
				$obj     = new Wp_Travel_Engine_Functions();
				$options = $obj->wp_travel_engine_country_list();
				foreach ( $options as $key => $val ) {
					echo '<option value="' . ( ! empty( $val ) ? esc_attr( $val ) : 'Please select' ) . '"' . selected( $_POST['country'], $val, false ) . '>' . esc_html( $val ) . '</option>';
				}
				?>
		</select>
	  </div>
	  <div class="wte-hbl-col-half">
		<select id="trip" name="trip"
		<?php
		if ( isset( $flag ) || isset( $flag1 ) ) {
			echo 'disabled';}
		?>
			>
		  <option>Choose your trip*</option>
		   <?php
			$args        = array(
				'post_type'      => 'trip',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);
			$posts_array = get_posts( $args );
			$pid         = isset( $_GET['id'] ) ? esc_attr( $_GET['id'] ) : '';
			if ( $posts_array ) {
				foreach ( $posts_array as $tripname ) {
					?>
				<option <?php selected( $pid, $tripname->ID ); ?> value="<?php echo $tripname->ID; ?>"><?php echo $tripname->post_title; ?></option>
					<?php
				}
			}
			?>
		</select>
	  </div>
	  <div class="wte-hbl-col-half">
		  <input type="text" class="form-control" id="notravellers" name="notravellers" placeholder="Number of travellers*" required>
	  </div>
	  <div class="wte-hbl-col-half">
		  <input type="date" class="form-control" id="date" name="date" placeholder="Enter Trip Date*">
	  </div>
	  <div class="wte-hbl-col-half">
		  <input type="number" step="1" class="form-control" id="amount1" name="amount1" placeholder="Amount*" value="
		  <?php
			if ( isset( $tcost ) && $tcost != 0 ) {
				echo esc_attr( $tcost );}
			?>
				" required
				<?php
				if ( isset( $tcost ) && $tcost != 0 ) {
								echo 'readonly';}
				?>
				><span class="note" style="opacity:0.7; font-style:italic; font-size:12px;">Deposit Amount in USD</span>
	  </div>
	  <div class="wte-hbl-col-full">
		  <textarea class="form-control" rows="5" id="message" name="message" placeholder="Your Message*" required></textarea>
	  </div>
	  <span class="response-holder"></span>
	</div>

	<div id="loader" style="display: none">
	  <div class="table">
		<div class="table-row">
		  <div class="table-cell">
			<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
		  </div>
		</div>
	  </div>
	</div>
	<button type="submit" class="btn btn-primary">Proceed to Pay</button>
  </form>
		<?php
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * Add Global Settings section.
	 *
	 * @param [type] $global_tabs
	 * @return void
	 */
	public function add_hbl_global_settings( $global_tabs ) {

		if ( isset( $global_tabs['wpte-payment'] ) ) {
			$global_tabs['wpte-payment']['sub_tabs']['wte-hbl'] = array(
				'label'        => __( 'HBL Payment', 'wte-hbl' ),
				'content_path' => plugin_dir_path( WP_TRAVEL_ENGINE_HBL_FILE_PATH ) . 'admin/includes/backend/global-settings.php',
				'current'      => false,
			);
		}

		return $global_tabs;
	}
}
new Wte_HBL_Admin();
