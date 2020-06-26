<?php
/*
 * Plugin Name: Interswitch Payment Gateway
 * Plugin URI: https://interswitch.co.ke
 * Description: Take credit card payments on your store.
 * Author: Antony Thumbi
 * Version: 1.0.1
 *
 /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'interswitch_add_gateway_class' );
define( 'WC_IWP_PB_MAIN_FILE', __FILE__ );
define( 'WC_IWP_PB_VERSION', '1.1.0' );
function interswitch_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Interswitch_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'interswitch_init_gateway_class' );
function interswitch_init_gateway_class() {
 
	class WC_Interswitch_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
 
	$this->id = 'interswitch'; // payment gateway plugin ID
	$this->icon = $this->get_option('icon_url');; // URL of the icon that will be displayed on checkout page near your gateway name
	$this->has_fields = false; // in case you need a custom credit card form
	$this->method_title = 'Interswitch Payment Gateway';
	$this->method_description = 'Description of Interswitch payment gateway'; // will be displayed on the options page
 
	// gateways can support subscriptions, refunds, saved payment methods,
	// but in this tutorial we begin with simple payments
	$this->supports = array(
		'products'
	);
 
	// Method with all the options fields
	$this->init_form_fields();
 
	// Load the settings.
	$this->init_settings();
	$this->title = $this->get_option( 'title' );
	$this->description = $this->get_option( 'description' );
	$this->enabled = $this->get_option( 'enabled' );
	//$this->testmode = 'yes' === $this->get_option( 'testmode' );
  $this->client_secret = $this->get_option('client_secret');
        $this->gateway_script = "https://testmerchant.interswitch-ke.com/webpay/button/functions.js";
        $this->currency = $this->get_option('currency');
        $this->fee = $this->get_option('fee');

        $this->merchant_code = $this->get_option('merchant_code');

        $this->query_url = 'https://testmerchant.interswitch-ke.com/merchant/transaction/query?';
 
	// This action hook saves the settings
	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
 
	// We need custom JavaScript to obtain a token
	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	
	   add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
 

 
 		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 
		$this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Interswitch Payment Gateway',
                'type' => 'checkbox',
                'description' => 'Enable Interswitch Payment Gateway as a payment option on the checkout page.',
                'default' => 'no',
                'desc_tip' => true
            ),
			'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'Make payment using your debit and credit cards',
			'default'     => 'Interswitch Payment Gateway',
			'desc_tip'    => true,
		),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the payment method description which the user sees during checkout.',
                'desc_tip' => true,
                'default' => 'Make payment using your debit and credit cards'
            ),
            'currency' => array(
                'title' => 'Currency',
                'type' => 'text',
                'description' => 'Enter your preferred currency',
                'default' => 'KES',
            ),
            'client_secret' => array(
                'title' => 'Client Secret',
                'type' => 'text',
                'description' => 'Enter your client secret provided by Interswitch',
                'default' => '',
            ),
            'client_id' => array(
                'title' => 'Client ID',
                'type' => 'text',
                'description' => 'Enter your client ID provided by Interswitch',
                'default' => '',
            ),
            'domain' => array(
                'title' => 'Domain',
                'type' => 'text',
                'description' => 'Enter your domain provided by Interswitch',
                'default' => 'ISWKE',
            ),
            'fee' => array(
                'title' => 'Fee',
                'type' => 'text',
                'description' => 'Enter your fee ',
                'default' => '0',
            ),
            'terminal_id' => array(
                'title' => 'Terminal ID',
                'type' => 'text',
                'description' => 'Enter your Terminal ID provided by Interswitch',
                'default' => '',
            ),
            'merchant_code' => array(
                'title' => 'Merchant Code',
                'type' => 'text',
                'description' => 'Enter your Merchant Code provided by Interswitch',
                'default' => '',
            ),
            'icon_url' => array(
                'title' => 'Icon  URL',
                'type' => 'text',
                'description' => 'Enter the icon URL of the logo',
                'default' => '',
            ),
        );
 
	 	}
 
 /**
     * Display Interswitch payment icon
     */
    public function get_icon() {

        $icon = '
				<img style="height:100%;width=100%;" src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/interswitch-payment-gateway.png', WC_IWP_PB_MAIN_FILE)) . '"/>
				
				';

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }
		
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {
 
		// we need JavaScript to process a token only on cart/checkout pages, right?
	if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
		return;
	}
 
	// if our payment gateway is disabled, we do not have to enqueue JS too
	if ( 'no' === $this->enabled ) {
		return;
	}
 
	/*// no reason to enqueue JavaScript if API keys are not set
	if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
		return;
	}
 
	// do not work with card detailes without SSL unless your website is in a test mode
	if ( ! $this->testmode && ! is_ssl() ) {
		return;
	}*/
 
    $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_enqueue_script('wc_iwp_pb', plugins_url('assets/js/iwp' . $suffix . '.js', WC_IWP_PB_MAIN_FILE), array('jquery'), WC_IWP_PB_VERSION, true);
 
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
		
 
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
 
		global $woocommerce;
 
	// we need it to get any order detailes
	$order = wc_get_order( $order_id );
 
 
	/*
 	 * Array with parameters for API interaction
	 */
	return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
 
	
 
	 	}
		
		 /**
     * Displays the payment page
     */
    public function receipt_page($order_id) {


        $order = wc_get_order($order_id);


        $refnz = $order_id; // . date('His');



        if (isset($_GET['paid']) && isset($_GET['response'])) {
            $response = json_decode(base64_decode($_GET['response']));
            if (empty($response))
                wp_redirect($order->get_view_order_url());
            else
                wp_redirect($order->get_checkout_order_received_url());
            // if (isset($response->responseCode) && $response->responseCode == '00') {
            //wp_redirect($order->get_checkout_order_received_url());
            exit;
            /* } else {
              $order->update_status('failed');
              wp_redirect($order->get_view_order_url());
              exit;
              } */
        }

        $total = $order->get_total() * 100;
        $order_data = $order->get_data();


        $txn_ref = uniqid() . '|' . $order_id;

        $body = array(
            'iwp_paycode_txnref' => $order_id,
            'iwp_paycode_amount' => $total,
        );

        $url = WC()->api_request_url('WC_Interswitch_Gateway');

        $response = wp_remote_post(
                $url, array(
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'method' => 'POST',
            'body' => json_encode($body),
                )
        );
        $customerInfo = $order_id . '|' . $order_data['billing']['first_name'] . '|' . $order_data['billing']['last_name'] . '|' . $order_data['billing']['email'] . '|' . $order_data['billing']['phone'] . '|' . $order_data['billing']['city'] . '|' . $order_data['billing']['country'] . '|' . $order_data['billing']['postcode'] . '|' . $order_data['billing']['address_1'] . '|' . $order_data['billing']['city'];
        $narration = $order_data['billing']['first_name'] . '|' . $order_data['billing']['last_name'];



        echo '<p>Please click on the "PAY NOW" button below to proceed with payment.</p>';

        ob_start();
        ?>
        <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous">

        </script>

        <div style="text-align: center">
            <a data-isw-payment-button data-isw-ref='<?php echo $this->merchant_code; ?>'>
                <script type='text/javascript' src='<?php echo $this->gateway_script; ?>' data-isw-transactionReference='<?php echo $refnz; ?>' data-isw-merchantCode='<?php echo $this->merchant_code; ?>' data-isw-currencyCode='<?php echo $this->currency; ?>' data-isw-amount='<?php echo $total; ?>' data-isw-dateOfPayment=<?php echo date('Y-m-d\TH:i:s'); ?> data-isw-orderId='<?php echo $order_id; ?>' data-isw-terminalId='<?php echo $this->terminal_id; ?>' data-isw-customerInfor='<?php echo $customerInfo; ?>' data-isw-redirectUrl='<?php echo $order->get_checkout_payment_url($on_checkout = true) . '&paid=' . base64_encode(true) ?>'  data-isw-domain='<?php echo $this->domain; ?>' data-isw-narration='<?php echo $narration; ?>' data-isw-fee='<?php echo $this->fee; ?>' data-isw-preauth='1' data_isw_icon_url='<?php echo $this->icon; ?>'>
                </script>
            </a>
        </div>
        </div>
        <script>


        </script>
        <?php
        echo ob_get_clean();

        echo '<div id="iwp_paybutton_form"><form id="order_review" method="post" action="' . $order->get_checkout_payment_url($on_checkout = true) . '"></form>
			<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">Cancel order &amp; restore cart</a></div>
				';

        return;
    }

    /**
     * Verify Interswitch payment
     */
    public function genNonce($length) {

        $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        $strlength = strlen($characters);

        $random = '';

        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[rand(0, $strlength - 1)];
        }

        return $random;
    }

    public function verify_transaction($order_id) {


        @ob_clean();
        $order_ref = $order_id;
        // get query transaction details
        $txn_details = $this->get_transaction_details($order_ref);

        $paid_amount = (int) $txn_details['transactionAmount'];
        $paymentRef = $txn_details['orderId'];
        // check if transaction was successful
        echo $txn_details['transactionResponseCode'];
        exit;
        if ('0' == $txn_details['transactionResponseCode']) {
            $order_id = (int) $order_ref;
            $order = wc_get_order($order_id);
            //check order status												
            // if (in_array($order->get_status(), array('processing', 'completed', 'on-hold'))) {
            // 	wp_redirect($this->get_return_url($order));
            // 	return;
            // }
            $order_total = $order->get_total() * 100;


            // check if the amount paid is equal to the order amount.
            if ($paid_amount != $order_total) {
                // put order on hold
                $order->update_status('on-hold', '');
                add_post_meta($order_id, '_transaction_id', $paymentRef, true);
                // Add customer order note
                $notice = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
                $notice_type = 'notice';
                $order->add_order_note($notice, 1);
                // Add admin order note
                $order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is not the same as the total order amount.<br />Amount Paid was <strong>&#8358;' . $paid_amount . '</strong> while the total order amount is <strong>&#8358;' . $order_total . '</strong><br />Interswitch Transaction Reference: ' . $paymentRef);
                wc_add_notice($notice, $notice_type);
            } else {
                $order->payment_complete();
                $order->add_order_note(sprintf('Payment via Interswitch Successful (Transaction Reference: %s)', $paymentRef));
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            }
            // reduce stock
            $order->reduce_order_stock();
            // empty cart
            wc_empty_cart();
            wp_redirect($this->get_return_url($order));
            return;
        } else {
            $error_message = 'Transaction failed';
            wc_add_notice(__('Payment error:', 'woothemes') . $error_message . $paymentRef, 'error');
            wp_redirect(wc_get_page_permalink('cart'));
            return;
        }
    }
 
		
 	}
}