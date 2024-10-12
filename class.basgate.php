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
        // $this->settings = get_option(BasgateConstants::OPTION_DATA_NAME);
        // $invertLogo = isset($getBasgateSetting['invertLogo']) ? $getBasgateSetting['invertLogo'] : "0";
        // if ($invertLogo == 1) {
        $this->icon = esc_url("https://ykbsocial.com/basgate/reportlogo.png");
        // } else {
        //     $this->icon = esc_url("https://ykbsocial.com/basgate/reportlogo.png");
        // }
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
            return get_site_url() . '/?page_id=' . $checkout_page_id . '&wc-api=WC_Basgate';
        }
    }

    public function init_form_fields()
    {

        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
        $webhookUrl = esc_url(get_site_url() . '/?wc-api=WC_Basgate&webhook=yes');
        // $basgateDashboardLink = esc_url("https://web.basgate.com:9191/");
        // $basgatePaymentStatusLink = esc_url("https://web.basgate.com:9191/");
        // $basgateContactLink = esc_url("https://basgate.com");
        $this->form_fields = array(
            'bas_description' => array(
                'title'         => __('Description', $this->id),
                'type'          => 'textarea',
                'description'   => __('This controls the description which the user sees during checkout.', $this->id),
                'default'       => __(BasgateConstants::DESCRIPTION, $this->id)
            ),
            'bas_environment' => array(
                'title'         => __('Environment Mode'),
                $this->id,
                'type'          => 'select',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'options'       => array("0" => "Test/Staging", "1" => "Production"),
                'description'   => __('Select "Test/Staging" to setup test transactions & "Production" once you are ready to go live', $this->id),
                'default'       => '0'
            ),
            'bas_application_id' => array(
                'title'         => __('Application Id'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the relevant Application ID for test or production environment you received on email.', $this->id),
            ),
            'bas_merchant_key' => array(
                'title'         => __('Merchant Key'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the Merchant Key for test or production environment you received on email.', $this->id),
            ),
            'bas_client_id' => array(
                'title'         => __('Client Id'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the Client Id for test or production environment you received on email.', $this->id),
            ),
            'bas_client_secret' => array(
                'title'         => __('Client Secret'),
                'type'          => 'text',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'description'   => __('Based on the selected Environment Mode, copy the Client Secret for test or production environment you received on email.', $this->id),
            ),
            'enabled'           => array(
                'title'             => __('Enable/Disable', $this->id),
                'type'          => 'checkbox',
                'custom_attributes' => array('required' => 'required', 'disabled' => 'disabled'),
                'label'         => __('Enable Basgate Login/Payments.', $this->id),
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

        echo wp_kses('<h3>' . __('Basgate Payment Gateway', $this->id) . '</h3>', $allowed_tags);
        echo wp_kses('<p>' . __('Online payment solutions for all your transactions by Basgate', $this->id) . '</p>', $allowed_tags);
        echo wp_kses('<p>' . __('Please note disabled settings can be modified from ', $this->id) . '<a href="' . esc_url(admin_url('admin.php?page=basgate')) . '">Basgate Login SDK</a></p>', $allowed_tags);

        // Check cUrl is enabled or not
        $curl_version = BasgateHelper::getcURLversion();

        if (empty($curl_version)) {
            echo wp_kses('<div class="basgate_response error-box">' . BasgateConstants::ERROR_CURL_DISABLED . '</div>', $allowed_tags);
        }

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

        $last_updated = date("d F Y", strtotime(BasgateConstants::LAST_UPDATED)) . ' - ' . BasgateConstants::PLUGIN_VERSION;

        $footer_text = '<div style="text-align: center;"><hr/>';
        $footer_text .= '<strong>' . __('PHP Version') . '</strong> ' . PHP_VERSION . ' | ';
        $footer_text .= '<strong>' . __('cURL Version') . '</strong> ' . $curl_version . ' | ';
        $footer_text .= '<strong>' . __('Wordpress Version') . '</strong> ' . get_bloginfo('version') . ' | ';
        $footer_text .= '<strong>' . __('WooCommerce Version') . '</strong> ' . WOOCOMMERCE_VERSION . ' | ';
        $footer_text .= '<strong>' . __('Last Updated') . '</strong> ' . $last_updated . ' | ';
        $footer_text .= '<a href="' . esc_url(BasgateConstants::PLUGIN_DOC_URL) . '" target="_blank">Developer Docs</a>';

        $footer_text .= '</div>';

        // echo wp_kses($footer_text, $allowed_tags);
    }

    /**
     *  There are no payment fields for basgate, but we want to show the description if set.
     **/
    public function payment_fields()
    {
        if ($this->description) echo wpautop(wptexturize($this->description));
    }


    /**
     * Receipt Page
     **/
    public function receipt_page($order)
    {
        echo $this->generate_basgate_form($order);
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

        $data = array();
        if (!empty($paramData['amount']) && (int)$paramData['amount'] > 0) {
            // if ($this->getSetting('otherWebsiteName') == "") {
            // $website = $this->getSetting('website');
            // } else {
            //     $website = $this->getSetting('otherWebsiteName');
            // }
            $requestTimestamp = gmdate("Y-m-d\TH:i:s\Z");
            /* body parameters */
            $basgateParams["body"] = array(
                "requestTimestamp" => $requestTimestamp,
                "appId" => $this->getSetting('bas_application_id'),
                "orderType" => "PayBill",
                "orderId" => $paramData['order_id'],
                "callbackUrl" => $this->getCallbackUrl(),
                "amount" => array(
                    "value" => $paramData['amount'],
                    "currency" => $paramData['currency'],
                ),
                "customerInfo" => array(
                    "id" => $paramData['cust_id'],
                    "name" => $paramData['cust_name'],
                    "mobile" => $paramData["cust_mob_no"]
                ),
                //TODO: from JS SDK 
                // params.Body["customerInfo"] = {};
                // params.Body["customerInfo"]["id"] = ("" + order.customerInfo.open_id).trim();
                // params.Body["customerInfo"]["name"] = ("" + order.customerInfo.name).trim();
            );
            $checksum = BasgateChecksum::generateSignature(json_encode($basgateParams["body"], JSON_UNESCAPED_SLASHES), $this->getSetting('bas_merchant_key'));

            $basgateParams["head"] = array(
                "signature" => $checksum,
                "requestTimeStamp" => $requestTimestamp
            );

            /* prepare JSON string for request */
            $post_data = json_encode($basgateParams, JSON_UNESCAPED_SLASHES);
            $url = BasgateHelper::getBasgateURL(BasgateConstants::INITIATE_TRANSACTION_URL, $this->getSetting('bas_environment'));

            $res = BasgateHelper::executecUrl($url, $post_data);

            if (!empty($res['body']['resultInfo']['resultStatus']) && $res['body']['resultInfo']['resultStatus'] == 'S') {
                $data['txnToken'] = $res['body']['txnToken'];
            } else {
                $data['txnToken'] = "";
            }
        }
        return $data;
    }
    /**
     * Generate basgate button link
     **/
    public function generate_basgate_form($order_id)
    {
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

        $settings = get_option(BasgateConstants::OPTION_DATA_NAME);

        $wait_msg = '<div id="basgate-pg-spinner" class="basgate-woopg-loader"><div class="bounce1"></div>
                    <div class="bounce2"></div><div class="bounce3"></div><div class="bounce4"></div><div class="bounce5">
                    </div><p class="loading-basgate">Loading Basgate</p></div><div class="basgate-overlay basgate-woopg-loader"></div>
                    <div class="basgate-action-btn"><a href="" class="refresh-payment re-invoke">Pay Now</a>
                    <a href="' . wc_get_checkout_url() . '" class="refresh-payment">Cancel</a></div>';

        $paramData = array('amount' => $getOrderInfo['amount'], 'order_id' => $order_id, 'cust_id' => $cust_id, 'cust_mob_no' => $cust_mob_no, 'cust_name' => $cust_name, "currency" => $currency);
        $data = $this->blinkCheckoutSend($paramData);
        return '<div class="pg-basgate-checkout"><script type="text/javascript">
			function invokeBlinkCheckoutPopup(){
				console.log("method called");
				var config = {
					"data": {
                        "appId":"' . $settings['bas_application_id'] . '",
                        "orderId": "' . $order_id . '", 
                        "txnToken": "' . $data['txnToken'] . '", 
                        "tokenType": "TXN_TOKEN",
                        "amount": "' . $getOrderInfo['amount'] . '",
                        "currency":"' . $getOrderInfo['currency'] . '"
					},
                };
                  //TODO: Call Bas payment SDK Here.
                if("JSBridge" in window){
                    window.JSBridge.call("basPayment",config.data)
                        .then(function (result) {
                            console.log("basPayment Result:", JSON.stringify(result));
                            if (result) {
                                // "notifyMerchant": function(eventName,data){
                                // console.log("notifyMerchant handler function called");
                                // if(eventName=="APP_CLOSED")
                                // {
                                //     jQuery(".loading-basgate").hide();
                                //     jQuery(".basgate-woopg-loader").hide();
                                //     jQuery(".basgate-overlay").hide();
                                //     jQuery(".refresh-payment").show();
                                //     if(jQuery(".pg-basgate-checkout").length>1){
                                //     jQuery(".pg-basgate-checkout:nth-of-type(2)").remove();
                                //     }
                                //     jQuery(".basgate-action-btn").show();
                                // }
                                return result;
                            } else {
                                return null
                            }
                        }).catch(function onError(error){
                            console.log("error => ",error);
                        });
                } 
			}

            invokeBlinkCheckoutPopup();
			jQuery(document).ready(function(){ jQuery(".re-invoke").on("click",function(){ window.Basgate.CheckoutJS.invoke();  return false; }); });
			</script>' . $wait_msg . '</div>
			';
    }
    /**
     * Process the payment and return the result
     **/
    public function process_payment($order_id)
    {
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
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('key', $order_key, $order->get_checkout_payment_url(true))
            );
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
            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order',
                    $order->get_id(),
                    add_query_arg('key', $order_key, get_permalink(get_option('woocommerce_pay_page_id')))
                )
            );
        }
    }

    /**
     * Check for valid basgate server callback // response processing //
     **/
    public function check_basgate_response()
    {
        global $woocommerce;

        if (!empty($_POST['STATUS'])) {

            //check order status before executing webhook call
            if (isset($_GET['webhook']) && $_GET['webhook'] == 'yes') {
                $getOrderId = !empty($_POST['ORDERID']) ? BasgateHelper::getOrderId(sanitize_text_field($_POST['ORDERID'])) : 0;
                if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                    $orderCheck = new WC_Order($getOrderId);
                } else {
                    $orderCheck = new woocommerce_order($getOrderId);
                }
                $result = getBasgateOrderData($getOrderId);
                if (isset($result) && json_decode($result['basgate_response'], true)['STATUS'] == "TXN_SUCCESS") {
                    exit;
                }
                if ($orderCheck->status == "processing" || $orderCheck->status == "completed") {
                    exit;
                }
            }
            //end webhook check

            if (!empty($_POST['CHECKSUMHASH'])) {
                $post_checksum = sanitize_text_field($_POST['CHECKSUMHASH']);
                unset($_POST['CHECKSUMHASH']);
            } else {
                $post_checksum = "";
            }
            $order = array();
            $isValidChecksum = BasgateChecksum::verifySignature($_POST, $this->getSetting('bas_merchant_key'), $post_checksum);
            if ($isValidChecksum === true) {
                $order_id = !empty($_POST['ORDERID']) ? BasgateHelper::getOrderId(sanitize_text_field($_POST['ORDERID'])) : 0;

                /* save basgate response in db */
                if (BasgateConstants::SAVE_BASGATE_RESPONSE && !empty($_POST['STATUS'])) {
                    $order_data_id = saveTxnResponse(BasgateHelper::getOrderId(sanitize_text_field($_POST['ORDERID'])), $_POST);
                }
                /* save basgate response in db */

                $responseDescription = (!empty($_POST['RESPMSG'])) ? sanitize_text_field($_POST['RESPMSG']) : "";

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

                    $reqParams = array(
                        "appId" => $this->getSetting('bas_application_id'),
                        "ORDERID" => sanitize_text_field($_POST['ORDERID']),
                    );

                    $reqParams['CHECKSUMHASH'] = BasgateChecksum::generateSignature($reqParams, $this->getSetting('bas_merchant_key'));

                    /* number of retries untill cURL gets success */
                    $retry = 1;
                    do {
                        $resParams = BasgateHelper::executecUrl(BasgateHelper::getBasgateURL(BasgateConstants::ORDER_STATUS_URL, $this->getSetting('bas_environment')), $reqParams);
                        $retry++;
                    } while (!$resParams['STATUS'] && $retry < BasgateConstants::MAX_RETRY_COUNT);
                    /* number of retries untill cURL gets success */

                    if (!isset($resParams['STATUS'])) {
                        $resParams = $_POST;
                    }

                    /* save basgate response in db */
                    if (BasgateConstants::SAVE_BASGATE_RESPONSE && !empty($resParams['STATUS'])) {
                        saveTxnResponse(BasgateHelper::getOrderId($resParams['ORDERID']), $order_data_id, $resParams);
                    }
                    /* save basgate response in db */

                    // if curl failed to fetch response
                    if (!isset($resParams['STATUS'])) {
                        $this->fireFailure($order, __(BasgateConstants::ERROR_SERVER_COMMUNICATION));
                    } else {
                        if ($resParams['STATUS'] == 'TXN_SUCCESS') {

                            if ($order->status !== 'completed') {

                                $this->msg['message'] = __(BasgateConstants::SUCCESS_ORDER_MESSAGE);
                                $this->msg['class'] = 'success';

                                if ($order->status !== 'processing') {
                                    $order->payment_complete($resParams['TXNID']);
                                    $order->reduce_order_stock();

                                    $message = "<br/>" . sprintf(__(BasgateConstants::TRANSACTION_ID), $resParams['TXNID']) . "<br/>" . sprintf(__(BasgateConstants::BASGATE_ORDER_ID), $resParams['ORDERID']);
                                    $message .= '<br/><span class="msg-by-basgate">By: Basgate ' . $through . '</span>';
                                    $order->add_order_note($this->msg['message'] . $message);
                                    $woocommerce->cart->empty_cart();
                                }
                            }
                        } else if ($resParams['STATUS'] == 'PENDING') {
                            $message = __(BasgateConstants::PENDING_ORDER_MESSAGE);
                            if (!empty($responseDescription)) {
                                $message .= sprintf(__(BasgateConstants::REASON), $responseDescription);
                            }
                            $message .= '<br/><span class="msg-by-basgate">By: Basgate ' . $through . '</span>';
                            $this->setStatusMessage($order, $message, 'pending');
                        } else {
                            $message = __(BasgateConstants::ERROR_ORDER_MESSAGE);
                            if (!empty($responseDescription)) {
                                $message .= sprintf(__(BasgateConstants::REASON), $responseDescription);
                            }
                            $message .= '<br/><span class="msg-by-basgate">By: Basgate ' . $through . '</span>';
                            $this->setStatusMessage($order, $message);
                        }
                    }
                } else {
                    $this->setStatusMessage($order, __(BasgateConstants::ERROR_INVALID_ORDER));
                }
            } else {
                $this->setStatusMessage($order, __(BasgateConstants::ERROR_CHECKSUM_MISMATCH));
            }

            $redirect_url = $this->redirectUrl($order);

            $this->setMessages($this->msg['message'], $this->msg['class']);

            if (isset($_GET['webhook']) && $_GET['webhook'] == 'yes') {
                echo "Webhook Received";
            } else {
                wp_redirect($redirect_url);
            }

            exit;
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
        global $woocommerce;
        // Redirection after basgate payments response.
        if (!empty($order)) {
            if ('success' == $this->msg['class']) {
                $redirect_url = $order->get_checkout_order_received_url();
            } else {
                //$redirect_url = wc_get_checkout_url();
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
//     $checksum = BasgateChecksum::generateSignature(json_encode($basgateParams, JSON_UNESCAPED_SLASHES), $mkey);
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
//     echo json_encode(array('message' => $message, 'response' => $response, 'showMsg' => $showMsg));

//     die();
// }

// function basgate_enqueue_script()
// {
//     wp_enqueue_style('basgateadminWoopayment', plugin_dir_url(__FILE__) . 'assets/' . BasgateConstants::PLUGIN_VERSION_FOLDER . '/css/admin/basgate-payments.css', array(), time(), '');
//     wp_enqueue_script('basgate-script', plugin_dir_url(__FILE__) . 'assets/' . BasgateConstants::PLUGIN_VERSION_FOLDER . '/js/admin/basgate-payments.js', array('jquery'), time(), true);
// }

// if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'wc-settings') {
//     add_action('admin_enqueue_scripts', 'basgate_enqueue_script');
// }
