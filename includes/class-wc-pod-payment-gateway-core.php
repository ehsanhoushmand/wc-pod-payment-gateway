<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if (!defined('ALL')){
	define('ALL', 4);
}
if(!defined('POD_ONLY')){
	define('POD_ONLY', 2);
}

add_action('plugins_loaded', 'load_pod_gateway', 0);


function load_pod_gateway() {

	if (class_exists('WC_Payment_Gateway') && !class_exists('WC_PodWallet') && !function_exists('Woocommerce_Add_Pod_Gateway')) {

		if( !session_id() )
			session_start();

		$pod_only = 4;
		$login_by_pod = false;
		if( get_user_meta(get_current_user_id(),'pod_user_id') ){
			$login_by_pod = true;
		}
		if($pod_only == ALL || ($pod_only == POD_ONLY && $login_by_pod)) {
			add_filter('woocommerce_payment_gateways', 'pod_add_gateway_class');
		}

		function pod_add_gateway_class( $gateways ) {
			$gateways[] = 'WC_PodWallet';
			return $gateways;
		}

		add_filter('woocommerce_currencies', 'add_pod_IR_currency');

		function add_pod_IR_currency($currencies) {
			$currencies['IRR'] = __('ریال', 'wc-pod-payment-gateway');
			$currencies['IRT'] = __('تومان', 'wc-pod-payment-gateway');
			$currencies['IRHR'] = __('هزار ریال', 'wc-pod-payment-gateway');
			$currencies['IRHT'] = __('هزار تومان', 'wc-pod-payment-gateway');

			return $currencies;
		}

		add_filter('woocommerce_currency_symbol', 'add_pod_IR_currency_symbol', 10, 2);

		function add_pod_IR_currency_symbol($currency_symbol, $currency) {
			switch ($currency) {
				case 'IRR': $currency_symbol = 'ریال';
					break;
				case 'IRT': $currency_symbol = 'تومان';
					break;
				case 'IRHR': $currency_symbol = 'هزار ریال';
					break;
				case 'IRHT': $currency_symbol = 'هزار تومان';
					break;
			}
			return $currency_symbol;
		}

		class WC_PodWallet extends WC_Payment_Gateway {

			public function __construct() {

				$this->id = 'WC_PodWallet';
				$this->method_title = __('پرداخت با پیپاد', 'wc-pod-payment-gateway');
				$this->method_description = __('تنظیمات درگاه پرداخت پاد برای افزونه فروشگاه ساز ووکامرس', 'wc-pod-payment-gateway');
				$this->icon = apply_filters('WC_PodWallet_logo', plugins_url('/assets/images/pod-logo-blue.png?1', dirname(__FILE__)));
				$this->has_fields = false;

				$this->init_form_fields();
				$this->init_settings();

				$this->title = $this->settings['title'];
				$this->description = $this->settings['description'];

				$this->success_message = $this->settings['success_message'];
				$this->failed_message = $this->settings['failed_message'];
				if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>='))
					add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
				else
					add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));

				add_action('woocommerce_api_' . sanitize_key('WC_PodWallet') , array($this, 'redirect_from_pod'));


			}

			public function init_form_fields() {
				$this->form_fields = apply_filters('WC_PodWallet_Config', array(
						'enabled' => array(
							'title' => __('فعالسازی/غیرفعالسازی', 'wc-pod-payment-gateway'),
							'type' => 'checkbox',
							'label' => __('فعالسازی درگاه پاد', 'wc-pod-payment-gateway'),
							'description' => __('برای فعالسازی درگاه پرداخت پاد باید چک باکس را تیک بزنید', 'wc-pod-payment-gateway'),
							'default' => 'yes',
							'desc_tip' => true,
						),
						'title' => array(
							'title' => __('عنوان درگاه', 'wc-pod-payment-gateway'),
							'type' => 'text',
							'description' => __('عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'wc-pod-payment-gateway'),
							'default' => __('پرداخت با پیپاد', 'wc-pod-payment-gateway'),
							'desc_tip' => true,
						),
						'description' => array(
							'title' => __('توضیحات درگاه', 'wc-pod-payment-gateway'),
							'type' => 'text',
							'desc_tip' => true,
							'description' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'wc-pod-payment-gateway'),
							'default' => __('پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه پاد', 'wc-pod-payment-gateway')
						),
						'success_message' => array(
							'title' => __('پیام پرداخت موفق', 'wc-pod-payment-gateway'),
							'type' => 'textarea',
							'description' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (توکن) پاد استفاده نمایید .', 'wc-pod-payment-gateway'),
							'default' => __('با تشکر از شما . سفارش شما با موفقیت پرداخت شد .', 'wc-pod-payment-gateway'),
						),
						'failed_message' => array(
							'title' => __('پیام پرداخت ناموفق', 'wc-pod-payment-gateway'),
							'type' => 'textarea',
							'description' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت پاد ارسال میگردد .', 'wc-pod-payment-gateway'),
							'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'wc-pod-payment-gateway'),
						),
					)
				);
			}



			public function process_payment($order_id)
			{
				global $woocommerce;

				$order = new WC_Order($order_id);

				$options = get_option( 'podsso_options' );

				$woocommerce->session->order_id_pod = $order_id;
				$currency = $order->get_currency();
				$currency = apply_filters('WC_PodWallet_Currency', $currency, $order_id);

				do_action('WC_PodWallet_Gateway_Before_Form', $order_id, $woocommerce);
				do_action('WC_PodWallet_Gateway_After_Form', $order_id, $woocommerce);

				$Amount = intval($order->get_total());
				$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
				if (strtolower($currency) == strtolower('IRT') || strtolower($currency) == strtolower('TOMAN') || strtolower($currency) == strtolower('Iran TOMAN') || strtolower($currency) == strtolower('Iranian TOMAN') || strtolower($currency) == strtolower('Iran-TOMAN') || strtolower($currency) == strtolower('Iranian-TOMAN') || strtolower($currency) == strtolower('Iran_TOMAN') || strtolower($currency) == strtolower('Iranian_TOMAN') || strtolower($currency) == strtolower('تومان') || strtolower($currency) == strtolower('تومان ایران')
				)
					$Amount = $Amount * 10;
				else if (strtolower($currency) == strtolower('IRHT'))
					$Amount = $Amount * 10000;
				else if (strtolower($currency) == strtolower('IRHR'))
					$Amount = $Amount * 1000;
				else if (strtolower($currency) == strtolower('IRR'))
					$Amount = $Amount / 1;

				$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency);
				$Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency);
				$Amount = apply_filters('woocommerce_order_amount_total_pod_gateway', $Amount, $currency);

				$CallbackUrl = add_query_arg('wc_order', $order_id, WC()->api_request_url('WC_PodWallet'));

				$products = array();
				$order_items = $order->get_items();
				foreach ((array) $order_items as $product) {
					$products[] = $product['name'] . ' (' . $product['qty'] . ') ';
				}
				$products = implode(' - ', $products);

				$Description = 'خرید به شماره سفارش : ' . $order->get_order_number() . ' | خریدار : ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ' | محصولات : ' . $products;
				$Mobile = get_post_meta($order_id, '_billing_phone', true) ? get_post_meta($order_id, '_billing_phone', true) : '-';
				$Email = $order->get_billing_email();
				$Paymenter = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
				$ResNumber = intval($order->get_order_number());

				//Hooks for iranian developer
				$Description = apply_filters('WC_PodWallet_Description', $Description, $order_id);
				$Mobile = apply_filters('WC_PodWallet_Mobile', $Mobile, $order_id);
				$Email = apply_filters('WC_PodWallet_Email', $Email, $order_id);
				$Paymenter = apply_filters('WC_PodWallet_Paymenter', $Paymenter, $order_id);
				$ResNumber = apply_filters('WC_PodWallet_ResNumber', $ResNumber, $order_id);
				do_action('WC_PodWallet_Gateway_Payment', $order_id, $Description, $Mobile);
				$Email = !filter_var($Email, FILTER_VALIDATE_EMAIL) === false ? $Email : '';
				$Mobile = preg_match('/^09[0-9]{9}/i', $Mobile) ? $Mobile : '';

				// Pod IssueInvoice
				try {

					$server_url = $options['api_url'] . '/nzh/ott';
					$requestArray = array(
						'method'      => 'POST',
						'timeout'     => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking'    => true,
						'headers'     => array(
							'_token_' => $options['api_token'],
							'_token_issuer_' => '1'
						),
						'body'        => array(),
						'cookies'     => array(),
						'sslverify'   => false
					);

					$response   = wp_remote_post( $server_url, $requestArray );
					$res_info = json_decode( $response['body'] );
					if ( isset( $res_info->error ) ) {
						wp_die( $res_info->error_description );
					}

					$ott = $res_info->ott;

					$server_url = $options['api_url'] . '/nzh/biz/issueInvoice/';

					$body = array(
						'productId[]'     => 0,
						'price[]'     => $Amount,
						'quantity[]' => 1,
						'productDescription[]' => $Description,
						'guildCode' => $options['guild_code'],
						'verificationNeeded' => 'true',
						'preferredTaxRate' => 0
					);
					if(	get_user_meta(get_current_user_id(),'pod_user_id',true)){
						$body['userId']= get_user_meta(get_current_user_id(),'pod_user_id',true);
					}
					$requestArray = array(
						'method'      => 'POST',
						'timeout'     => 45,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking'    => true,
						'headers'     => array(
							'_token_' => $options['api_token'],
							'_token_issuer_' => '1',
							'_ott_' => $ott
						),
						'body'        => $body,
						'cookies'     => array(),
						'sslverify'   => false
					);
					$response   = wp_remote_post( $server_url, $requestArray );

					$res_info = json_decode( $response['body'] );

					if ( isset( $res_info->hasError ) && $res_info->hasError ) {
						wp_die( "Error code: ". $res_info->errorCode." refrenceNumber: ". $res_info->referenceNumber." message: ". $res_info->message );
					}

					if( get_user_meta(get_current_user_id(),'pod_user_id', true) ){//logged in by pod
						$redirectUrl = $options["pay_invoice_url"] . "/?invoiceId=" . $res_info->result->id . "&redirectUri=$CallbackUrl";
					}
					else {
						$new_url = str_replace("payinvoice", "payInvoiceByUniqueNumber", $options["pay_invoice_url"]); //For backward compatibility url is changed like this

						$redirectUrl = $new_url . "/?uniqueNumber=" . $res_info->result->uniqueNumber . "&redirectUri=$CallbackUrl";
					}

					return array(
						'result' => 'success',
						'redirect' => $redirectUrl
					);

				} catch (Exception $ex) {
					wc_add_notice(  'Please try again.', 'error' );
					return;
				}


			}

			public function redirect_from_pod() {
				global $woocommerce;

				$options = get_option( 'podsso_options' );


				if (isset($_GET['wc_order'])) {
					$order_id = $_GET['wc_order'];
				} else if (isset($InvoiceNumber)) {
					$order_id = $InvoiceNumber;
				} else {
					$order_id = $woocommerce->session->order_id_pod;
					unset($woocommerce->session->order_id_pod);
				}

				if ($order_id) {
					$order = new WC_Order($order_id);
					if ($order->get_status() != 'completed') {
						if (isset($_GET['paymentBillNumber']) && $_GET['paymentBillNumber']) {

							$server_url = $options['api_url'] . '/nzh/biz/verifyInvoice';

							$requestArray = array(
								'method'      => 'POST',
								'timeout'     => 45,
								'redirection' => 5,
								'httpversion' => '1.0',
								'blocking'    => true,
								'headers'     => array(
									'_token_' => $options['api_token'],
									'_token_issuer_' => '1'
								),
								'body'        => array(
									'id'    => $_GET['invoiceId']
								),
								'cookies'     => array(),
								'sslverify'   => false
							);
							$response   = wp_remote_post( $server_url, $requestArray );
							$res_info = json_decode( $response['body'] );
							if ( isset( $res_info->error ) ) {
								$Status = 'failed';
								$Fault = '';
								$Message = 'تراکنش ناموفق بود';
							}else{
								$Status = 'completed';
								$Transaction_ID = $res_info->result->id;
								$Fault = '';
								$Message = '';
							}
						} else {
							$Status = 'failed';
							$Fault = '';
							$Message = 'تراکنش انجام نشد .';
						}

						if ($Status == 'completed' && isset($Transaction_ID) && $Transaction_ID != 0) {
							update_post_meta($order_id, '_transaction_id', $Transaction_ID);
							update_post_meta($order_id, '_invoice_state', 'open');



							$order->payment_complete($Transaction_ID);
							$woocommerce->cart->empty_cart();

							$Note = sprintf(__('پرداخت موفقیت آمیز بود .<br/> کد رهگیری : %s', 'woocommerce'), $Transaction_ID);
							$Note = apply_filters('WC_PodWallet_Return_from_Gateway_Success_Note', $Note, $order_id, $Transaction_ID);
							if ($Note)
								$order->add_order_note($Note, 1);


							$Notice = wpautop(wptexturize($this->success_message));

							$Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);

							$Notice = apply_filters('WC_PodWallet_Return_from_Gateway_Success_Notice', $Notice, $order_id, $Transaction_ID);
							if ($Notice)
								wc_add_notice($Notice, 'success');

							do_action('WC_PodWallet_Return_from_Gateway_Success', $order_id, $Transaction_ID);

							wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
							exit;
						}
						else {
							$Transaction_ID = $Transaction_ID ?? 0;
							$tr_id = ( isset($Transaction_ID) && $Transaction_ID != 0 ) ? ('<br/>توکن : ' . $Transaction_ID) : '';
							$Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s %s', 'woocommerce'), $Message, $tr_id);
							$Note = apply_filters('WC_PodWallet_Return_from_Gateway_Failed_Note', $Note, $order_id, $Transaction_ID, $Fault);
							$order->add_order_note($Note, 1);
							$Notice = wpautop(wptexturize($this->failed_message));
							$Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);
							$Notice = str_replace("{fault}", $Message, $Notice);
							$Notice = apply_filters('WC_PodWallet_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $Transaction_ID, $Fault);
							if ($Notice)
								wc_add_notice($Notice, 'error');

							do_action('WC_PodWallet_Return_from_Gateway_Failed', $order_id, $Transaction_ID, $Fault);

							wp_redirect(wc_get_checkout_url());
							exit;
						}
					}
					else {

						$Transaction_ID = get_post_meta($order_id, '_transaction_id', true);

						$Notice = wpautop(wptexturize($this->success_message));

						$Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);

						$Notice = apply_filters('WC_PodWallet_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $Transaction_ID);
						if ($Notice)
							wc_add_notice($Notice, 'success');


						do_action('WC_PodWallet_Return_from_Gateway_ReSuccess', $order_id, $Transaction_ID);

						wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
						exit;
					}
				}
				else {
					$Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
					$Notice = wpautop(wptexturize($this->failed_message));
					$Notice = str_replace("{fault}", $Fault, $Notice);
					$Notice = apply_filters('WC_PodWallet_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $Fault);
					if ($Notice)
						wc_add_notice($Notice, 'error');

					do_action('WC_PodWallet_Return_from_Gateway_No_Order_ID', $order_id, $Transaction_ID ?? 0, $Fault);

					wp_redirect(wc_get_cart_url());
					exit;
				}
			}

		}

	}
}

