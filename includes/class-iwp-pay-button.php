<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tbz_WC_IWP_Pay_Button_Gateway extends WC_Payment_Gateway {

    /**
     * Constructor
     */
    public function __construct() {

        $this->id = 'iwp-pay-button';
        $this->method_title = 'Interswitch Payment Gateway';
        $this->has_fields = false;

        // Load the form fields
        $this->init_form_fields();

        // Load the settings
        $this->init_settings();

        // Get setting values
        $this->title = 'Interswitch Payment Gateway';
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->client_id = $this->get_option('client_id');
        $this->domain = $this->get_option('domain');
        $this->terminal_id = $this->get_option('terminal_id');
        $this->merchant_code = $this->get_option('merchant_code');
        $this->icon_url = $this->get_option('icon_url');
        $this->client_secret = $this->get_option('client_secret');
        $this->gateway_script = "https://testmerchant.interswitch-ke.com/webpay/button/functions.js";
        $this->currency = $this->get_option('currency');
        $this->fee = $this->get_option('fee');

        $this->merchant_code = $this->get_option('iwp_merchant_code');

        $this->query_url = 'https://testmerchant.interswitch-ke.com/merchant/transaction/query?';





        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        add_action('woocommerce_thankyou', array($this, 'verifyTrxn'), 10, 1);


//add_action('woocommerce_checkout_process', 'process_custom_payment');
// Payment listener/API hook
        //add_action('woocommerce_payment_complete', array($this, 'verify_transaction'), 10, 1);
        //add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );
        // Payment listener/API hook
        // add_action('woocommerce_payment_complete', 'wh_test_2', 10, 1);
        // Check if the gateway can be used
        if (!$this->is_valid_for_use()) {
            $this->enabled = false;
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     */
    public function is_valid_for_use() {



        return true;
    }

    function wp9838c_timeout_extend($time) {
        // Default timeout is 5
        return 15;
    }

    /**
     * Display Interswitch payment icon
     */
    public function get_icon() {

        $icon = '<br/>
				<img style="height: 32px;width: 360px; " src="' . WC_HTTPS::force_https_url(plugins_url('assets/images/interswitch-payment-gateway.png', WC_IWP_PB_MAIN_FILE)) . '"/>
				
				';

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {

        if ($this->enabled == "yes") {

            if (!($this->merchant_code)) {

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        ?>

        <h3>Interswitch Pay Button</h3>

        <?php
        if ($this->is_valid_for_use()) {

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        } else {
            ?>
            <div class="inline error">
                <p><strong>Interswitch Pay Button Payment Gateway Disabled</strong>: <?php echo $this->msg ?></p>
            </div>

            <?php
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Interswitch Payment Gateway',
                'type' => 'checkbox',
                'description' => 'Enable Interswitch Payment Gateway as a payment option on the checkout page.',
                'default' => 'no',
                'desc_tip' => true
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
     * Outputs scripts used for Interswitch payment
     */
    public function payment_scripts() {

        if (!is_checkout_pay_page()) {
            return;
        }

        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_enqueue_script('wc_iwp_pb', plugins_url('assets/js/iwp' . $suffix . '.js', WC_IWP_PB_MAIN_FILE), array('jquery'), WC_IWP_PB_VERSION, true);
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {

        $order = wc_get_order($order_id);

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function verifyTrxn($order_id) {

        //create an order instance
        $order = wc_get_order($order_id);
        $txn_details = $this->get_transaction_details($order_id);

        if (isset($txn_details['transactionResponseCode']) && $txn_details['transactionResponseCode'] == '0') {
            if ('pending' == $order->status) {
                $order->update_status('processing', 'order_note');
            }
        } 
        exit;
    }

    /**
     * Validate a payment
     */
    public function get_transaction_details($txn_ref) {

        $merchantid = $this->merchant_code;
        $domain = $this->domain;
        $query_url = $this->query_url . $txn_ref;
        $clientID = $this->client_id;
        $clientSecret = $this->client_secret;
        $contentType = 'application/json; charset=utf-8';
        $authorization = 'InterswitchAuth ' . base64_encode($clientID);
        $timestamp = time();
        $nonce = $this->genNonce(20) . $timestamp;
        $httpMethod = 'GET';
        $url = $this->query_url . "transactionRef=$txn_ref&merchantId=$merchantid&provider=prv";
        $signatureText = ($httpMethod . "&" . rawurlencode($url) . "&" . $timestamp . "&" . $nonce . "&" . $clientID . "&" . $clientSecret);
        $signature = base64_encode(hash('SHA1', $signatureText, true));
        $signatureMethod = 'SHA1';




        $request = wp_remote_get($url);

        if (!is_wp_error($request) && 200 == wp_remote_retrieve_response_code($request)) {

            $response = json_decode(wp_remote_retrieve_body($request));
        }



        if (!is_wp_error($request)) {

            $response = json_decode(wp_remote_retrieve_body($request));
        } else {
            $response['ResponseCode'] = '400';
            $response['ResponseDescription'] = 'Cant verify payment. Contact us for more details about the order and payment status.';
        }

        return (array) $response;
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

        $url = WC()->api_request_url('Tbz_WC_IWP_Pay_Button_Gateway');

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
                <script type='text/javascript' src='<?php echo $this->gateway_script; ?>' data-isw-transactionReference='<?php echo $refnz; ?>' data-isw-merchantCode='<?php echo $this->merchant_code; ?>' data-isw-currencyCode='<?php echo $this->currency; ?>' data-isw-amount='<?php echo $total; ?>' data-isw-dateOfPayment=<?php echo date('Y-m-d\TH:i:s'); ?> data-isw-orderId='<?php echo $order_id; ?>' data-isw-terminalId='<?php echo $this->terminal_id; ?>' data-isw-customerInfor='<?php echo $customerInfo; ?>' data-isw-redirectUrl='<?php echo $order->get_checkout_payment_url($on_checkout = true) . '&paid=' . base64_encode(true) ?>'  data-isw-domain='<?php echo $this->domain; ?>' data-isw-narration='<?php echo $narration; ?>' data-isw-fee='<?php echo $this->fee; ?>' data-isw-preauth='1' data_isw_icon_url='<?php echo $this->icon_url; ?>'>
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

        // $order = wc_get_order($order_id);
        // $order->payment_complete();
        // wc_empty_cart();
        // wp_redirect($this->get_return_url($order));
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
