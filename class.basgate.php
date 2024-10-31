<?php

/**
 * Gateway class
 */
class WC_Basgate extends WC_Payment_Gateway
{

    protected $msg = array();
    /**
     * Contruction function
     */
    public function __construct()
    {
        // Go wild in here
        $this->id = BasgateConstants::ID;
        $this->method_title = BasgateConstants::METHOD_TITLE;
        $this->method_description = BasgateConstants::METHOD_DESCRIPTION;
        // $this->icon = esc_url("https://ykbsocial.com/basgate/reportlogo.png");
        $this->icon = apply_filters('woocommerce_gateway_icon', plugin_dir_url(__FILE__) . 'assets/images/bassdk-logo.svg');
        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_settings();
        $this->title = BasgateConstants::TITLE;
        $this->description = $this->getSetting('bas_description');
        $this->msg = array('message' => '', 'class' => '');

        $this->initHooks();
    }

    /**
     * InitHooks function
     */
    private function initHooks()
    {
        BasgateHelper::basgate_log('===== STARTED initHooks()');

        add_action('init', array(&$this, 'check_basgate_response'));
        //update for woocommerce >2.0
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_basgate_response'));
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        }
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
    }

    private function getSetting($key)
    {
        return $this->settings[$key];
    }

    private function getCallbackUrl()
    {
        if (!empty(BasgateConstants::CUSTOM_CALLBACK_URL)) {
            return BasgateConstants::CUSTOM_CALLBACK_URL;
        } else {
            $checkout_page_id = get_option('woocommerce_checkout_page_id');
            $checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
            $url = get_site_url() . '?page_id=' . $checkout_page_id . '&wc-api=WC_Basgate';
            return $url;
        }
    }

    public function init_form_fields()
    {

        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;

        // $webhookUrl = esc_url(get_site_url() . '/?wc-api=WC_Basgate&webhook=yes');
        // $basgateDashboardLink = esc_url("https://web.basgate.com:9191/");
        // $basgatePaymentStatusLink = esc_url("https://web.basgate.com:9191/");
        // $basgateContactLink = esc_url("https://basgate.com");
        $this->form_fields = array(
            'bas_description' => array(
                'title'         => __('Description', 'bassdk-woocommerce-payments'),
                'type'          => 'textarea',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('This controls the description which the user sees during checkout.', 'bassdk-woocommerce-payments'),
                'default'       => sprintf(
                    /* translators: 1: LogoUrl. */
                    esc_html__('The best payment gateway provider in Yemen for e-payment through most of wallets and banks <img src="%1$s" height="24px;" />', 'bassdk-woocommerce-payments'),
                    esc_url('https://ykbsocial.com/basgate/reportlogo.png')
                )
            ),
            'bas_environment' => array(
                'title'         => __('Environment Mode', 'bassdk-woocommerce-payments'),
                $this->id,
                'type'          => 'select',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'options'       => array("0" => "Test/Staging", "1" => "Production"),
                'description'   => __('Select "Test/Staging" to setup test transactions & "Production" once you are ready to go live', 'bassdk-woocommerce-payments'),
                'default'       => '0'
            ),
            'bas_application_id' => array(
                'title'         => __('Application Id', 'bassdk-woocommerce-payments'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the relevant Application ID for test or production environment you received on email.', 'bassdk-woocommerce-payments'),
            ),
            'bas_merchant_key' => array(
                'title'         => __('Merchant Key', 'bassdk-woocommerce-payments'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the Merchant Key for test or production environment you received on email.', 'bassdk-woocommerce-payments'),
            ),
            'bas_client_id' => array(
                'title'         => __('Client Id', 'bassdk-woocommerce-payments'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the Client Id for test or production environment you received on email.', 'bassdk-woocommerce-payments'),
            ),
            'bas_client_secret' => array(
                'title'         => __('Client Secret', 'bassdk-woocommerce-payments'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the Client Secret for test or production environment you received on email.', 'bassdk-woocommerce-payments'),
            ),
            'enabled'           => array(
                'title'             => __('Enable/Disable', 'bassdk-woocommerce-payments'),
                'type'          => 'checkbox',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled', 'display' => 'none'),
                'label'         => __('Enable Basgate Login/Payments.', 'bassdk-woocommerce-payments'),
                'default'       => 'yes'
            ),
            'debug'           => array(
                'title'             => __('Enable Debug', 'bassdk-woocommerce-payments'),
                'type'          => 'checkbox',
                'custom_attributes' => array('disabled' => 'disabled', 'display' => 'none'),
                'label'         => __('Enable debug mode to log all oprations.', 'bassdk-woocommerce-payments'),
                'default'       => 'yes'
            ),
        );
    }


    /**
     * Admin Panel Options
     * - Options for bits like 'title'
     **/
    public function admin_options()
    {
        //Echoing HTML safely start
        $default_attribs = array(
            'id' => array(),
            'class' => array(),
            'title' => array(),
            'style' => array(),
            'data' => array(),
            'data-mce-id' => array(),
            'data-mce-style' => array(),
            'data-mce-bogus' => array(),
        );
        $allowed_tags = array(
            'div'           => $default_attribs,
            'span'          => $default_attribs,
            'p'             => $default_attribs,
            'a'             => array_merge(
                $default_attribs,
                array(
                    'href' => array(),
                    'target' => array('_blank', '_top'),
                )
            ),
            'u'             =>  $default_attribs,
            'i'             =>  $default_attribs,
            'q'             =>  $default_attribs,
            'b'             =>  $default_attribs,
            'ul'            => $default_attribs,
            'ol'            => $default_attribs,
            'li'            => $default_attribs,
            'br'            => $default_attribs,
            'hr'            => $default_attribs,
            'strong'        => $default_attribs,
            'blockquote'    => $default_attribs,
            'del'           => $default_attribs,
            'strike'        => $default_attribs,
            'em'            => $default_attribs,
            'code'          => $default_attribs,
            'h1'            => $default_attribs,
            'h2'            => $default_attribs,
            'h3'            => $default_attribs,
            'h4'            => $default_attribs,
            'h5'            => $default_attribs,
            'h6'            => $default_attribs,
            'table'         => $default_attribs
        );
        //Echoing HTML safely end

        echo wp_kses('<h3>' . __('Basgate Payment Gateway', 'bassdk-woocommerce-payments') . '</h3>', $allowed_tags);
        echo wp_kses('<p>' . __('Online payment solutions for all your transactions by Basgate', 'bassdk-woocommerce-payments') . '</p>', $allowed_tags);
        echo wp_kses('<p>' . __('Please note disabled settings can be modified from ', 'bassdk-woocommerce-payments') . '<a href="' . esc_url(admin_url('admin.php?page=basgate')) . '">Basgate Login SDK</a></p>', $allowed_tags);

        // Transaction URL is not working properly or not able to communicate with basgate
        if (!empty(BasgateHelper::getBasgateURL(BasgateConstants::ORDER_STATUS_URL, $this->getSetting('bas_environment')))) {
            //wp_remote_get($url, array('sslverify' => FALSE));

            $response = (array)wp_remote_get(BasgateHelper::getBasgateURL(BasgateConstants::ORDER_STATUS_URL, $this->getSetting('bas_environment')));
            if (!empty($response['errors'])) {
                echo wp_kses('<div class="basgate_response error-box">' . BasgateConstants::ERROR_CURL_WARNING . '</div>', $allowed_tags);
            }
        }

        echo wp_kses('<table class="form-table">', $allowed_tags);
        $this->generate_settings_html();
        echo wp_kses('</table>', $allowed_tags);

        $last_updated = gmdate("d F Y", strtotime('20241023')) . ' - ' . BasgateConstants::PLUGIN_VERSION;

        $footer_text = '<div style="text-align: center;"><hr/>';
        $footer_text .= '<strong>' . __('PHP Version', 'bassdk-woocommerce-payments') . '</strong> ' . PHP_VERSION . ' | ';
        $footer_text .= '<strong>' . __('Wordpress Version', 'bassdk-woocommerce-payments') . '</strong> ' . get_bloginfo('version') . ' | ';
        $footer_text .= '<strong>' . __('WooCommerce Version', 'bassdk-woocommerce-payments') . '</strong> ' . WOOCOMMERCE_VERSION . ' | ';
        $footer_text .= '<strong>' . __('Last Updated', 'bassdk-woocommerce-payments') . '</strong> ' . $last_updated . ' | ';
        $footer_text .= '<a href="' . esc_url(BasgateConstants::PLUGIN_DOC_URL) . '" target="_blank">Developer Docs</a>';

        $footer_text .= '</div>';

        // echo wp_kses($footer_text, $allowed_tags);
    }

    /**
     *  There are no payment fields for basgate, but we want to show the description if set.
     **/
    public function payment_fields()
    {
        if ($this->description) {
            echo esc_attr(wpautop(wptexturize($this->description)));
        }
    }

    /**
     * Receipt Page
     **/
    public function receipt_page($order)
    {
        BasgateHelper::basgate_log('====== STARTED receipt_page');
        echo esc_html($this->generate_basgate_form($order));
        echo esc_html($this->generate_basgate_callback($order));
    }

    public function getOrderInfo($order)
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.7.2', '>=')) {
            $data = array(
                'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'contact' => $order->get_billing_phone(),
                'amount' => $order->get_total(),
                'currency' => $order->get_currency()
            );
        } else {
            $data = array(
                'name' => $order->billing_first_name . ' ' . $order->billing_last_name,
                'email' => $order->billing_email,
                'contact' => $order->billing_phone,
                'amount' => $order->order_total,
                'currency' => $order->get_currency()

            );
        }

        return $data;
    }

    /* 
     * Get the transaction token
    */
    public function blinkCheckoutSend($paramData = array())
    {
        try {
            $current_user = wp_get_current_user();
            if ($current_user->ID != 0) {
                $paramData['cust_name'] = $current_user->display_name;
                $paramData['cust_email'] = $current_user->user_email;
                $paramData['cust_mob_no'] = get_user_meta($current_user->ID, 'billing_phone', true);
                $paramData['open_id'] = get_user_meta($current_user->ID, 'open_id', true);
            } else {
                global $wp;
                $current_url = home_url(add_query_arg(array(), $wp->request));
                wp_redirect(wp_login_url($current_url));
                exit;
            }
            $callBackURL = $this->getCallbackUrl();
            $callBackURL = add_query_arg('orderId', $paramData['order_id'], $callBackURL);
            BasgateHelper::basgate_log('====== blinkCheckoutSend $callBackURL:' . $callBackURL);

            $data = array();
            if (!empty($paramData['amount']) && (int)$paramData['amount'] > 0) {
                $reqBody = '{"head":{"signature":"sigg","requestTimeStamp":"timess"},"body":bodyy}';
                // $requestTimestamp = gmdate("Y-m-d\TH:i:s\Z");
                $requestTimestamp = (string)  time();
                /* body parameters */
                $basgateParams["body"] = array(
                    "appId" => $this->getSetting('bas_application_id'),
                    "requestTimestamp" => $requestTimestamp,
                    "orderType" => "PayBill",
                    "callBackUrl" => $callBackURL,
                    "customerInfo" => array(
                        "id" => $paramData['open_id'],
                        "name" => $paramData['cust_name'],
                    ),
                    "amount" => array(
                        "value" => (float)$paramData['amount'],
                        "currency" => $paramData['currency'],
                    ),
                    "orderId" => $paramData['order_id'],
                    "orderDetails" => array(
                        "Id" => $paramData['order_id'],
                        "Currency" => $paramData['currency'],
                        "TotalPrice" => (float) $paramData['amount'],
                    )
                );
                $bodystr = wp_json_encode($basgateParams["body"], JSON_UNESCAPED_SLASHES);
                $checksum = BasgateChecksum::generateSignature($bodystr, $this->getSetting('bas_merchant_key'));

                if ($checksum === false) {
                    BasgateHelper::basgate_log(
                        sprintf(
                            /* translators: 1: Event data. */
                            __('Could not retrieve signature, please try again Data: %1$s.', 'bassdk-woocommerce-payments'),
                            $bodystr
                        )
                    );
                    throw new Exception(__('Could not retrieve signature, please try again.', 'bassdk-woocommerce-payments'));
                }

                /* prepare JSON string for request */
                $reqBody = str_replace('bodyy', $bodystr, $reqBody);
                $reqBody = str_replace('sigg', $checksum, $reqBody);
                $reqBody = str_replace('timess', $requestTimestamp, $reqBody);

                $url = BasgateHelper::getBasgateURL(BasgateConstants::INITIATE_TRANSACTION_URL, $this->getSetting('bas_environment'));
                $header = array('Accept' => ' text/plain', 'Content-Type' => 'application/json');

                $retry = 1;
                do {
                    $res = BasgateHelper::executecUrl($url, $reqBody, "POST", $header);
                    $retry++;
                } while (!isset($res['body']) && $retry < BasgateConstants::MAX_RETRY_COUNT);

                if (array_key_exists('success', $res) && $res['success'] == true) {
                    if (!empty($res['body']['trxToken'])) {
                        BasgateHelper::basgate_log('====== blinkCheckoutSend $res :' . wp_json_encode($res));

                        $data['trxToken'] = $res['body']['trxToken'];
                        $data['trxId'] = $res['body']['trxId'];
                        $data['callBackUrl'] = $callBackURL;
                    } else {
                        BasgateHelper::basgate_log(
                            sprintf(
                                /*translators: 1:response, 2: bodystr , 3:checksum. */
                                __('trxToken empty response: %1$s , \n bodystr: %2$s , \n $checksum: %3$s.', 'bassdk-woocommerce-payments'),
                                wp_json_encode($res),
                                $bodystr,
                                $checksum
                            )
                        );
                        // $data['trxToken'] = "";
                    }
                } else {
                    return null;
                }
            }
            return $data;
        } catch (\Throwable $th) {
            return new Exception("ERROR On blinkCheckoutSend :" . $th->getMessage());
        }
    }

    /**
     * Generate basgate button link
     **/
    public function generate_basgate_form($order_id)
    {
        BasgateHelper::basgate_log('====== STARTED generate_basgate_form');
        global $woocommerce;
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            $order = new WC_Order($order_id);
        } else {
            $order = new woocommerce_order($order_id);
        }

        $order_id = BasgateHelper::getBasgateOrderId($order_id);

        $getOrderInfo = $this->getOrderInfo($order);

        if (!empty($getOrderInfo['email'])) {
            $cust_id = $email = $getOrderInfo['email'];
        } else {
            $cust_id = "CUST_" . $order_id;
        }

        if (!empty($getOrderInfo['name'])) {
            $cust_name = $email = $getOrderInfo['name'];
        } else {
            $cust_name = "CUST_" . $order_id;
        }
        //get mobile no 
        if (isset($getOrderInfo['phone']) && !empty($getOrderInfo['phone'])) {
            $cust_mob_no = $getOrderInfo['phone'];
        } else {
            $cust_mob_no = "";
        }
        //get currency
        if (isset($getOrderInfo['currency']) && !empty($getOrderInfo['currency'])) {
            $currency = $getOrderInfo['currency'];
        } else {
            $currency = "YER";
        }

        // $settings = get_option(BasgateConstants::OPTION_DATA_NAME);

        // $checkout_url = plugin_dir_url(__FILE__) . 'assets/js/public.js';
        // <script type="application/javascript" crossorigin="anonymous" src="' . $checkout_url . '" onload="invokeBlinkCheckoutPopup();"></script>
        $wait_msg = '<div id="basgate-pg-spinner" class="basgate-woopg-loader">
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
                <div class="bounce4"></div>
                <div class="bounce5">
                </div>
                <p class="loading-basgate">Loading Basgate</p>
            </div>
            <div class="basgate-overlay basgate-woopg-loader"></div>
            <div class="basgate-action-btn"><a href="" class="refresh-payment re-invoke">Pay Now</a>
                <a href="' . wc_get_checkout_url() . '" class="refresh-payment">Cancel</a>
            </div>';

        $paramData = array(
            'amount' => $getOrderInfo['amount'],
            'order_id' => $order_id,
            'cust_id' => $cust_id,
            'cust_mob_no' => $cust_mob_no,
            'cust_name' => $cust_name,
            "currency" => $currency
        );

        $data = $this->blinkCheckoutSend($paramData);

        if (is_null($data) || empty($data) || is_wp_error($data)) {
            $error_msg = __('Could not retrieve the Transaction Token, please check that you are inside basgate platform and try again.', 'bassdk-woocommerce-payments');
            $this->setMessages($error_msg, "error");
            return new Exception(esc_attr($error_msg));
            exit;
        }

        BasgateHelper::basgate_log('====== generate_basgate_form INITIATE_TRANSACTION $data :' . wp_json_encode($data));

        return '<div class="pg-basgate-checkout">
            <script type="text/javascript">
                function invokeBlinkCheckoutPopup() {
                    console.log("===== method called");
                    var config = {
                        "appId": "' . $this->getSetting('bas_application_id') . '",
                        "orderId": "' . $order_id . '",
                        "trxToken": "' . $data['trxToken'] . '",
                        "amount": {
                            "value": "' . $getOrderInfo['amount'] . '",
                            "currency": "' . $getOrderInfo['currency'] . '"
                        },
                    };
                    console.log("===== invokeBlinkCheckoutPopup config:", JSON.stringify(config));
                    window.addEventListener("JSBridgeReady", async (event) => {
                        console.log("===== invokeBlinkCheckoutPopup JSBridge existed");
                        await getBasPayment(config)
                            .then(function(result) {
                                console.log("===== basPayment Result:", JSON.stringify(result));
                                if (result && result.status == 1) {
                                    // jQuery(".loading-basgate").hide();
                                    // jQuery(".basgate-woopg-loader").hide();
                                    // jQuery(".basgate-overlay").hide();
                                    // jQuery(".refresh-payment").show();
                                    // // if (jQuery(".pg-basgate-checkout").length > 1) {
                                    // //     jQuery(".pg-basgate-checkout:nth-of-type(2)").remove();
                                    // // }
                                  return basCheckOutCallback(result, "' . $data['callBackUrl'] . '");
                                } else {
                                    return null
                                }
                            }).catch(function onError(error) {
                                console.log("error => ", error);
                            });
                    }, false);
                }
                invokeBlinkCheckoutPopup();
            </script>
            ' . $wait_msg . '
        </div>';
    }

    public function generate_basgate_callback()
    {
        BasgateHelper::basgate_log('====== STARTED generate_basgate_callback');
?>
        <script type="text/javascript">
            // eslint-disable-next-line
            function basCheckOutCallback(resData, ajaxurl) { // jshint ignore:line
                var $ = jQuery;
                console.log("==== STARTED basCheckOutCallback() resData.status:", resData.status)
                if (resData.hasOwnProperty('status')) {
                    var nonce = '<?php echo esc_attr(wp_create_nonce('basgate_checkout_nonce')); ?>';
                    $.post(ajaxurl, {
                        data: resData.data,
                        status: resData.status,
                        nonce: nonce,
                    }, function(data, textStatus) {
                        console.log('===== basCheckOutCallback textStatus:', textStatus)
                        console.log('===== basCheckOutCallback data:', data)
                        try {
                            var res = JSON.parse(data);
                            if ('redirect' in res) {
                                window.location = res['redirect']
                            }
                        } catch (error) {

                        }
                        // return data;
                    });
                } else {
                    //TODO:Handle errors message return from getBasPayment 
                }
            }
        </script>
<?php
    }

    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
        BasgateHelper::basgate_log('==== STARTED process_payment : ' . print_r($order_id, true));

        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            $order = new WC_Order($order_id);
        } else {
            $order = new woocommerce_order($order_id);
        }

        if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
            $order_key = $order->get_order_key();
        } else {
            $order_key = $order->order_key;
        }

        if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>=')) {
            // $data = array(
            //     'result' => 'success',
            //     'redirect' => $this->get_return_url($order)
            // );
            $data = array(
                'result' => 'success',
                'redirect' => add_query_arg('key', $order_key, $order->get_checkout_payment_url(true))
            );
            BasgateHelper::basgate_log('==== STARTED process_payment  $data: ' . print_r($data, true));

            return $data;
        } else if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order',
                    $order->get_id(),
                    add_query_arg('key', $order_key, $order->get_checkout_payment_url(true))
                )
            );
        } else {
            $data = array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order',
                    $order->get_id(),
                    add_query_arg('key', $order_key, get_permalink(get_option('woocommerce_pay_page_id')))
                )
            );
            BasgateHelper::basgate_log('==== STARTED process_payment else $data: ' . print_r($data, true));

            return $data;
        }
    }

    /**
     * Check for valid basgate server callback // response processing //
     **/
    public function check_basgate_response()
    {
        global $woocommerce;

        BasgateHelper::basgate_log('====== STARTED check_basgate_response _POST :' . wp_json_encode($_POST));

        if (! isset($_POST['nonce']) ||            ! wp_verify_nonce(sanitize_key($_POST['nonce']), 'basgate_checkout_nonce')) {
            die(esc_html("ERROR check_basgate_response wrong nonce"));
        }

        if (!empty($_POST['data'])) {
            BasgateHelper::basgate_log('====== STARTED check_basgate_response $_POST["data"] :' . wp_json_encode($_POST['data']));
            $data = $_POST['data'];
            // $data = isset($data['data']) ? json_decode($data['data'], true) : null;
            $status = isset($data['status']) ? $data['status'] : '';

            BasgateHelper::basgate_log('====== STARTED check_basgate_response $status :' . $status);

            if (!empty($status)) {
                BasgateHelper::basgate_log('====== check_basgate_response inside if()');
                //check order status before executing webhook call
                // if (isset($_GET['webhook']) && $_GET['webhook'] == 'yes') {
                //     $getOrderId = !empty($data['orderId']) ? BasgateHelper::getOrderId(sanitize_text_field($data['orderId'])) : 0;
                //     if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                //         $orderCheck = new WC_Order($getOrderId);
                //     } else {
                //         $orderCheck = new woocommerce_order($getOrderId);
                //     }
                //     $result = getBasgateOrderData($getOrderId);
                //     if (isset($result) && json_decode($result['basgate_response'], true)['STATUS'] == "TXN_SUCCESS") {
                //         exit;
                //     }
                //     if ($orderCheck->status == "processing" || $orderCheck->status == "completed") {
                //         exit;
                //     }
                // }
                //end webhook check

                // if (!empty($_POST['CHECKSUMHASH'])) {
                //     $post_checksum = sanitize_text_field($_POST['CHECKSUMHASH']);
                //     unset($_POST['CHECKSUMHASH']);
                // } else {
                //     $post_checksum = "";
                // }
                $order = array();
                // $isValidChecksum = BasgateChecksum::verifySignature($_POST, $this->getSetting('bas_merchant_key'), $post_checksum);
                $isValidChecksum = !empty($data['authenticated']) && $data['authenticated'] === "true";
                $responseDescription = (!empty($data['messages'])) ? sanitize_text_field(implode(' -- ', $data['messages'])) : "";

                if ($isValidChecksum === true) {
                    $order_id = !empty($data['orderId']) ? BasgateHelper::getOrderId(sanitize_text_field($data['orderId'])) : 0;
                    BasgateHelper::basgate_log('====== check_basgate_response $order_id :' . $order_id);

                    /* save basgate response in db */
                    if (BasgateConstants::SAVE_BASGATE_RESPONSE && !empty($data['status'])) {
                        $order_data_id = saveTxnResponse(BasgateHelper::getOrderId(sanitize_text_field($data['orderId'])), false, $data);
                        BasgateHelper::basgate_log('====== check_basgate_response $order_data_id :' . $order_data_id);
                    }
                    /* save basgate response in db */


                    if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                        $order = new WC_Order($order_id);
                    } else {
                        $order = new woocommerce_order($order_id);
                    }
                    if (isset($_GET['webhook']) && $_GET['webhook'] == 'yes') {
                        $through = "webhook_" . time();
                    } else {
                        $through = "callback_" . time();
                    }

                    if (!empty($order)) {
                        BasgateHelper::basgate_log('====== check_basgate_response isset($order) :' . isset($order));
                        $reqBody = '{"head":{"signature":"sigg","requestTimeStamp":"timess"},"body":bodyy}';
                        $requestTimestamp = (string)time();
                        $reqParams = array(
                            "appId" => $this->getSetting('bas_application_id'),
                            "orderId" => sanitize_text_field($data['orderId']),
                            "requestTimestamp" => $requestTimestamp
                        );

                        $bodystr = wp_json_encode($reqParams, JSON_UNESCAPED_SLASHES);
                        $checksum = BasgateChecksum::generateSignature($bodystr, $this->getSetting('bas_merchant_key'));
                        if ($checksum === false) {
                            error_log(
                                sprintf(
                                    /* translators: 1: Event data. */
                                    __('Could not retrieve signature, please try again Data: %1$s.', 'bassdk-woocommerce-payments'),
                                    $bodystr
                                )
                            );
                            throw new Exception(esc_attr__('Could not retrieve signature, please try again.', 'bassdk-woocommerce-payments'));
                        }

                        /* prepare JSON string for request */
                        $reqBody = str_replace('bodyy', $bodystr, $reqBody);
                        $reqBody = str_replace('sigg', $checksum, $reqBody);
                        $reqBody = str_replace('timess', $requestTimestamp, $reqBody);

                        $url = BasgateHelper::getBasgateURL(BasgateConstants::ORDER_STATUS_URL, $this->getSetting('bas_environment'));
                        $header = array('Accept' => ' text/plain', 'Content-Type' => ' application/json');
                        BasgateHelper::basgate_log('====== check_basgate_response $reqBody:' . $reqBody);

                        $retry = 1;
                        do {
                            $resParams = BasgateHelper::executecUrl($url, $reqBody, "POST", $header);
                            $retry++;
                        } while (!isset($resParams['body']) && $retry < BasgateConstants::MAX_RETRY_COUNT);

                        BasgateHelper::basgate_log('====== check_basgate_response $retry:' . $retry . ' , $resParams:' . wp_json_encode($resParams));

                        if (!isset($resParams['status'])) {
                            $resParams = $data;
                        } else {
                            //TODO: Add checksum verify
                            $head = isset($resParams['head']) ? $resParams['head'] : '';
                            $post_checksum = isset($head['signature']) ? $head['signature'] : '';
                            BasgateHelper::basgate_log('====== check_basgate_response after ORDER_STATUS $post_checksum:' . $post_checksum);
                            $statusData = isset($resParams['body']) ? $resParams['body'] : $resParams;
                            $statusData['orderId'] = isset($statusData['order']['orderId']) ? $statusData['order']['orderId'] : $order_id;
                            $isValidChecksum = BasgateChecksum::verifySignature(wp_json_encode($statusData), $this->getSetting('bas_merchant_key'), $post_checksum);
                            BasgateHelper::basgate_log('====== check_basgate_response after ORDER_STATUS $isValidChecksum:' . $isValidChecksum);
                        }

                        BasgateHelper::basgate_log('====== check_basgate_response after ORDER_STATUS statusData:' . wp_json_encode($statusData));
                        BasgateHelper::basgate_log('====== check_basgate_response trxStatus:' . $statusData['trxStatus'] . ' , orderId: ' . $statusData['orderId']);

                        /* save basgate response in db */
                        if (BasgateConstants::SAVE_BASGATE_RESPONSE && isset($statusData['trxStatusId'])) {
                            saveTxnResponse(BasgateHelper::getOrderId(sanitize_text_field($statusData['orderId'])), $order_data_id, $statusData);
                        }
                        /* save basgate response in db */

                        BasgateHelper::basgate_log('====== check_basgate_response $trxStatus:' . $statusData['trxStatus'] . ' , trxStatusId:' . $statusData['trxStatusId']);

                        if (!isset($statusData['trxStatusId'])) {
                            $this->fireFailure($order, __("It seems some issue in server to server communication. Kindly connect with us.", 'bassdk-woocommerce-payments'));
                        } else {
                            $trxStatus = strtolower($statusData['trxStatus']);
                            $trxStatusId = (int)$statusData['trxStatusId'];
                            $trxId = $statusData['trxId'];
                            if ($trxStatus == 'completed' || $trxStatusId == 1003) {

                                BasgateHelper::basgate_log('====== check_basgate_response $trxStatus : ' . $trxStatus . ' , $order->status : ' . $order->status);

                                if ($order->status !== 'completed') {

                                    $this->msg['message'] = __("Thank you for your order. Your payment has been successfully received.", 'bassdk-woocommerce-payments');
                                    $this->msg['class'] = 'success';
                                    $responseDescription = isset($statusData['order']['description']) ? $statusData['order']['description'] : $this->msg['message'];

                                    if ($order->status !== 'processing') {
                                        $order->payment_complete($trxId);
                                        $order->reduce_order_stock();
                                        $message = "<br/><b>" . esc_html__("Transaction ID:", 'bassdk-woocommerce-payments') . '</b>' . $statusData['trxId'] . "<br/><b>" . esc_html__("Basgate Order ID:", 'bassdk-woocommerce-payments') . '</b> ' . $statusData['orderId'];
                                        $message .= '<br/><span class="msg-by-basgate">By: Basgate ' . $through . ' ' . $responseDescription . '</span>';

                                        $this->msg['class'] = 'warrning';

                                        $order->add_order_note($this->msg['message'] . $responseDescription);
                                        $order->update_status('completed');
                                        $woocommerce->cart->empty_cart();
                                    }
                                }
                            } else if ($trxStatus == 'pending') {
                                $message = __("Your payment has been pending!", 'bassdk-woocommerce-payments');
                                if (!empty($responseDescription)) {
                                    $message .= __(" Reason: ", 'bassdk-woocommerce-payments') . $responseDescription;
                                }
                                $message .= '<br/><span class="msg-by-basgate">By: Basgate ' . $through . '</span>';
                                $this->setStatusMessage($order, $message, 'pending');
                            } else {
                                $message = __("Your payment has been failed!", 'bassdk-woocommerce-payments');
                                if (!empty($responseDescription)) {
                                    $message .= __(" Reason: ", 'bassdk-woocommerce-payments') . $responseDescription;
                                }
                                $message .= '<br/><span class="msg-by-basgate">By: Basgate ' . $through . '</span>';
                                $this->setStatusMessage($order, $message);
                            }
                        }
                    } else {
                        $this->setStatusMessage($order, __("No order found to process. Kindly contact with us.", 'bassdk-woocommerce-payments'));
                    }
                } else {
                    $this->setStatusMessage($order, __("Security Error. Checksum Mismatched!", 'bassdk-woocommerce-payments') . $responseDescription);
                }

                $redirect_url = $this->redirectUrl($order);
                BasgateHelper::basgate_log('====== check_basgate_response $redirect_url:' . $redirect_url);

                $this->setMessages($this->msg['message'], $this->msg['class']);

                if (isset($_GET['webhook']) && $_GET['webhook'] == 'yes') {
                    echo "Webhook Received";
                } else {
                    BasgateHelper::basgate_log('====== check_basgate_response else wp_redirect($redirect_url):' . $redirect_url);
                    $returnData = array(
                        'result' => 'success',
                        'redirect' => $redirect_url
                    );
                    die(wp_json_encode($returnData));
                }
            }
            exit;
        } else {
            BasgateHelper::basgate_log('====== STARTED check_basgate_response else isset($_POST["data"]) :' . isset($_POST['data']));
        }
    }

    /**
     * Show template while response 
     */
    private function setStatusMessage($order, $msg = '', $status = 'failed')
    {

        $this->msg['class'] = 'error';
        $this->msg['message'] = $msg;
        if (!empty($order)) {
            $order->update_status($status);
            $order->add_order_note($this->msg['message']);
        }
    }

    private function setMessages($message = '', $class = '')
    {
        BasgateHelper::basgate_log('====== STARTED setMessages $class:' . $class . ' , $message:' . $message);

        global $woocommerce;
        if (function_exists('wc_add_notice')) {
            wc_add_notice($message, $class);
        } else {
            if ('success' == $class) {
                $woocommerce->add_message($message);
            } else {
                $woocommerce->add_error($message);
            }
            $woocommerce->set_messages();
        }
    }

    private function redirectUrl($order)
    {
        BasgateHelper::basgate_log('====== STARTED redirectUrl ');

        global $woocommerce;
        // Redirection after basgate payments response.
        if (!empty($order)) {
            if ('success' == $this->msg['class']) {
                $redirect_url = $order->get_checkout_order_received_url();
            } else {
                $redirect_url = wc_get_checkout_url();
                $redirect_url = $order->get_view_order_url();
            }
        } else {
            $redirect_url = $woocommerce->cart->get_checkout_url();
        }
        return $redirect_url;
    }


    /*
     * End basgate Essential Functions
    **/
}


// add_action('wp_ajax_setPaymentNotificationUrl', 'setPaymentNotificationUrl');

// function setPaymentNotificationUrl()
// {
//     if ($_POST['environment'] == 0) {
//         $url = BasgateConstants::WEBHOOK_STAGING_URL;
//     } else {
//         $url = BasgateConstants::WEBHOOK_PRODUCTION_URL;
//     }
//     $environment = sanitize_text_field($_POST['environment']);
//     $mid = sanitize_text_field($_POST['mid']);
//     $mkey = sanitize_text_field($_POST['mkey']);

//     if ($_POST['is_webhook'] == 1) {
//         $webhookUrl = sanitize_text_field($_POST['webhookUrl']);
//     } else {
//         $webhookUrl = esc_url("https://www.dummyUrl.com"); //set this when unchecked
//     }
//     $basgateParams = array(
//         "mid"       => $mid,
//         "queryParam" => "notificationUrls",
//         "paymentNotificationUrl" => $webhookUrl

//     );
//     $checksum = BasgateChecksum::generateSignature(wp_json_encode($basgateParams, JSON_UNESCAPED_SLASHES), $mkey);
//     $res = BasgateHelper::executecUrl($url . 'api/v1/external/putMerchantInfo', $basgateParams, $method = 'PUT', ['x-checksum' => $checksum]);
//     // print_r($res);
//     if (isset($res['success'])) {
//         $message = true;
//         $success = $response;
//         $showMsg = false;
//     } elseif (isset($res['E_400'])) {
//         $message = "Your webhook has already been configured";
//         $success = $response;
//         $showMsg = false;
//     } else {
//         $success = $response;
//         $message = "Something went wrong while configuring webhook. Please login to configure.";
//         $showMsg = true;
//     }
//     echo wp_json_encode(array('message' => $message, 'response' => $response, 'showMsg' => $showMsg));

//     die();
// }

function basgate_enqueue_script()
{
    wp_enqueue_style('basgateadminWoopayment', plugin_dir_url(__FILE__) . 'assets/css/admin/basgate-payments.css', array(), time(), '');
    wp_enqueue_script('basgate-script', plugin_dir_url(__FILE__) . 'assets/js/admin/basgate-payments.js', array('jquery'), time(), true);
}

if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'wc-settings') {
    add_action('admin_enqueue_scripts', 'basgate_enqueue_script');
}
