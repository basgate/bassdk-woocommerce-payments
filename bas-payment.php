<?php

/**
 * Plugin Name: Bassdk Payment for WooCommerce
 * Plugin URI: https://github.com/Basgate/bassdk-woocommerce-payments
 * Description: هذه الاضافة تمكنك من تشغيل الدفع بداخل منصة بس والذي تقدم لك العديد من المحافظ المالية
 * Version: 0.2.1
 * Author: Basgate Super APP 
 * Author URI: https://basgate.com/
 * Developer: Abdullah AlAnsi
 * Developer URI: https://basgate.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: BasSDK, BasSDK Payments, PayWithBasgate, BasSDK WooCommerce, BasSDK Plugin, BasSDK Payment Gateway
 * Requires at least: 6.0.1
 * Tested up to: 6.5.5
 * Requires PHP: 7.4
 * Text Domain: bassdk-woocommerce-payments
 * WC requires at least: 2.0.0
 * WC tested up to: 9.0.2
 */


/**
 * Add the Gateway to WooCommerce
 **/
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (! function_exists('is_plugin_active')) {
    require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

if (! is_plugin_active('woocommerce/woocommerce.php')) {
    add_action(
        'admin_notices',
        function () {
            echo '<div class="error"><p><strong>' . esc_html__('Basgate Payment requires WooCommerce to be installed and active. You can download.', 'bassdk-woocommerce-payments') . '<a href="' . esc_url('https://woocommerce.com/') . '" target="_blank">WooCommerce</a>' . '</strong></p></div>';
        }
    );

    return;
}

if (! is_plugin_active('bassdk-login/bassdk-login.php')) {
    add_action(
        'admin_notices',
        function () {
            echo '<div class="error"><p><strong>' . esc_html__('Basgate Payment requires Basgate Login SDK to be installed and active. You can download', 'bassdk-woocommerce-payments') . '<a href="' . esc_url('https://github.com/basgate/bassdk-login') . '" target="_blank">Basgate Login SDK</a>' . '</strong></p></div>';
        }
    );

    return;
}

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use BasgateSDK\Helper;

require_once __DIR__ . '/includes/BasgateHelper.php';
require_once __DIR__ . '/includes/BasgateChecksum.php';

add_action('before_woocommerce_init', function () {

    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});



/**
 * Checkout Block code Start
 */
BasgateHelper::basgate_log('======++++++++++ $isInBasPlatform :(' . BasgateHelper::$isInBasPlatform . ')');

//TODO: Check if is it on Bas Platform 
// if (BasgateHelper::$isInBasPlatform) {
// BasgateHelper::basgate_log("====++++ add_action STARTED woocommerce_blocks_loaded ++++====");
add_action('woocommerce_blocks_loaded', 'basgate_register_order_approval_payment_method_type');
// BasgateHelper::$isInBasPlatform = false;
// } else {

//     // BasgateHelper::basgate_log("====++++ STARTED remove_action woocommerce_blocks_loaded ++++====");
//     // remove_action('woocommerce_blocks_loaded', 'basgate_register_order_approval_payment_method_type');
// }

function basgate_register_order_approval_payment_method_type()
{
    // BasgateHelper::basgate_log("======++++++++++++ STARTED basgate_register_order_approval_payment_method_type +++++++=======");
    // Check if the required class exists
    if (! class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // BasSDK custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Basgate_Blocks);
        }
    );
}
/* ************************************************ */

/* Create table 'basgate_order_data' after install basgate plugin */
if (function_exists('register_activation_hook'))    register_activation_hook(__FILE__, 'install_basgate_plugin');
/* Drop table 'basgate_order_data' after uninstall basgate plugin */
if (function_exists('register_deactivation_hook'))    register_deactivation_hook(__FILE__, 'uninstall_basgate_plugin');


function install_basgate_plugin()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'basgate_order_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `basgate_order_id` VARCHAR(255) NOT NULL,
        `transaction_id` VARCHAR(255) NOT NULL,
        `status` VARCHAR(255) NOT NULL,
        `basgate_response` TEXT,
        `date_added` DATETIME NOT NULL,
        `date_modified` DATETIME NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function uninstall_basgate_plugin()
{
    /*  global $wpdb;
    $table_name = $wpdb->prefix . 'basgate_order_data';
    $query = "SELECT * FROM $table_name";
    $results = $wpdb->get_results($query);
    if(count($results) <= 0 ){
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
    }
    delete_option(BasgateConstants::OPTION_DATA_NAME); */
}
function basgateWoopayment_enqueue_style()
{
    wp_enqueue_style('basgateWoopayment', plugin_dir_url(__FILE__) . 'assets/css/basgate-payments.css', array(), time(), '');
    wp_enqueue_script('basgate-script', plugin_dir_url(__FILE__) . 'assets/js/basgate-payments.js', array('jquery'), time(), true);
}

function basgateWoopayment_js_css()
{
    if (class_exists('WooCommerce')) {
        if (is_cart() || is_checkout()) {
            add_action('wp_head', 'basgateWoopayment_enqueue_style');
        }
    }
}

add_action('wp_enqueue_scripts', 'basgateWoopayment_js_css');

if (BasgateConstants::SAVE_BASGATE_RESPONSE) {

    // Add a basgate payments box only for shop_order post type (order edit pages)
    add_action('add_meta_boxes', 'add_basgate_payment_block');

    //Function changes for woocommerce HPOS features
    function add_basgate_payment_block()
    {
        BasgateHelper::basgate_log('====== STARTED add_basgate_payment_block');
        global $wpdb;
        $settings = get_option(BasgateConstants::OPTION_DATA_NAME);
        // phpcs:ignore WordPress.Security.NonceVerification
        $post_id1 = sanitize_text_field(isset($_GET['post']) ? wp_unslash($_GET['post']) : '');
        $post_id = preg_replace('/[^a-zA-Z0-9]/', '', $post_id1);

        if ($post_id == '' && get_option("woocommerce_custom_orders_table_enabled") == 'yes') {
            // phpcs:ignore WordPress.Security.NonceVerification
            $post_id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        }

        if (! $post_id) return; // Exit
        $screen = wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';
        $results = getBasgateOrderData($post_id);

        // basgate enabled and order is exists with paym_order_data
        if ($settings['enabled'] == 'yes' && !empty($results)) {
            add_meta_box(
                '_basgate_response_table',
                __('BasSDK Payments', 'bassdk-woocommerce-payments'),
                '_basgate_response_table',
                $screen,
                'normal',
                'default',
                array(
                    'results' => $results
                )
            );
        }
    }

    function _basgate_response_table($post = array(), $data = array())
    {
        BasgateHelper::basgate_log('====== STARTED _basgate_response_table');
        //Echoing HTML safely start
        global $allowedposttags;
        $allowed_atts = array(
            'align'      => array(),
            'class'      => array(),
            'type'       => array(),
            'id'         => array(),
            'dir'        => array(),
            'lang'       => array(),
            'style'      => array(),
            'xml:lang'   => array(),
            'src'        => array(),
            'alt'        => array(),
            'href'       => array(),
            'rel'        => array(),
            'rev'        => array(),
            'target'     => array(),
            'novalidate' => array(),
            'type'       => array(),
            'value'      => array(),
            'name'       => array(),
            'tabindex'   => array(),
            'action'     => array(),
            'method'     => array(),
            'for'        => array(),
            'width'      => array(),
            'height'     => array(),
            'data'       => array(),
            'title'      => array(),
        );
        $allowedposttags['form']     = $allowed_atts;
        $allowedposttags['label']    = $allowed_atts;
        $allowedposttags['input']    = $allowed_atts;
        $allowedposttags['textarea'] = $allowed_atts;
        $allowedposttags['iframe']   = $allowed_atts;
        $allowedposttags['script']   = $allowed_atts;
        $allowedposttags['style']    = $allowed_atts;
        $allowedposttags['strong']   = $allowed_atts;
        $allowedposttags['small']    = $allowed_atts;
        $allowedposttags['table']    = $allowed_atts;
        $allowedposttags['span']     = $allowed_atts;
        $allowedposttags['abbr']     = $allowed_atts;
        $allowedposttags['code']     = $allowed_atts;
        $allowedposttags['pre']      = $allowed_atts;
        $allowedposttags['div']      = $allowed_atts;
        $allowedposttags['img']      = $allowed_atts;
        $allowedposttags['h1']       = $allowed_atts;
        $allowedposttags['h2']       = $allowed_atts;
        $allowedposttags['h3']       = $allowed_atts;
        $allowedposttags['h4']       = $allowed_atts;
        $allowedposttags['h5']       = $allowed_atts;
        $allowedposttags['h6']       = $allowed_atts;
        $allowedposttags['ol']       = $allowed_atts;
        $allowedposttags['ul']       = $allowed_atts;
        $allowedposttags['li']       = $allowed_atts;
        $allowedposttags['em']       = $allowed_atts;
        $allowedposttags['hr']       = $allowed_atts;
        $allowedposttags['br']       = $allowed_atts;
        $allowedposttags['tr']       = $allowed_atts;
        $allowedposttags['td']       = $allowed_atts;
        $allowedposttags['p']        = $allowed_atts;
        $allowedposttags['a']        = $allowed_atts;
        $allowedposttags['b']        = $allowed_atts;
        $allowedposttags['i']        = $allowed_atts;
        //Echoing HTML safely end

        $table_html = '<div class="" id="basgate_payment_area"><div class="message"></div>';
        $results = $data['args']['results'];
        $table_html .= '<div class="btn-area"><img class="basgate-img-loader" src="' . admin_url('images/loading.gif') . '"><button type="button" id="button-basgate-fetch-status" class="button-basgate-fetch-status button">' . __('Fetch Status', 'bassdk-woocommerce-payments') . '</button></div>';
        $basgate_data = array();
        if (!empty($results)) {
            $basgate_data = json_decode($results['basgate_response'], true);
            if (!empty($basgate_data)) {
                $table_html .= '<table class="basgate_payment_block" id="basgate_payment_table">';
                foreach ($basgate_data as $key => $value) {
                    if ($key !== 'request' && !is_array($value)) {
                        $table_html .= '<tr><td> ' . $key . '</td><td> ' . $value . '</td></tr>';
                    } else 
                        if ($key == 'order' && is_array($value)) {
                        foreach ($value as $order_key => $order_value) {
                            if ($order_key == 'amount') {
                                foreach ($order_value as $amount_key => $amount_value) {
                                    $table_html .= '<tr><td>' . $amount_key . '</td><td>' . $amount_value . '</td></tr>';
                                }
                            }
                        }
                    }
                }
                $table_html .= '</table>';
                $table_html .= '<input type="hidden" id="basgate_order_id" name="basgate_order_id" value="' . $results['basgate_order_id'] . '"><input type="hidden" id="order_data_id" name="order_data_id" value="' . $results['id'] . '"><input type="hidden" id="basgate_woo_nonce" name="basgate_woo_nonce" value="' . wp_create_nonce('basgate_woo_nonce') . '">';
            }
        }
        $table_html .= '</div>';
        /* echo $table_html;die; */

        echo wp_kses($table_html, $allowedposttags);
    }


    function getBasgateOrderData($order_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'basgate_order_data';
        $results = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE order_id = %s ORDER BY `id` DESC LIMIT 1",
                $order_id
            ),
            ARRAY_A
        );
        return $results;
    }

    function get_custom_order($order_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_orders';

        $order = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );

        if ($order) {
            $order_data = maybe_unserialize($order['order_data']);

            // Additional processing if needed

            return $order_data;
        }

        return null;
    }

    add_action('admin_head', 'woocommerce_basgate_add_css_js');

    function woocommerce_basgate_add_css_js()
    {
        BasgateHelper::basgate_log('====== STARTED woocommerce_basgate_add_css_js');

?>
        <style>
            #basgate_payment_area .message {
                float: left;
            }

            #basgate_payment_area .btn-area {
                float: right;
            }

            #basgate_payment_area .btn-area .basgate-img-loader {
                margin: 6px;
                float: left;
                display: none;
            }

            .basgate_response {
                padding: 7px 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
                text-align: center;
            }

            .basgate_response.error-box {
                color: #a94442;
                background-color: #f2dede;
                border-color: #ebccd1;
            }

            .basgate_response.success-box {
                color: #155724;
                background-color: #d4edda;
                border-color: #c3e6cb;
            }

            .basgate_payment_block {
                table-layout: fixed;
                width: 100%;
            }

            .basgate_payment_block td {
                word-wrap: break-word;
            }

            .basgate_highlight {
                font-weight: bold;
            }

            .redColor {
                color: #f00;
            }

            .wp-core-ui .button.button-basgate-fetch-status {
                float: left;
                line-height: normal;
                background: #2b9c2b;
                color: #fff;
                border-color: #2b9c2b;
            }

            .wp-core-ui .button.button-basgate-fetch-status:hover {
                background: #32bd32
            }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                jQuery("#button-basgate-fetch-status").click(function() {
                    var basgate_order_id = jQuery("#basgate_order_id").val();
                    var order_data_id = jQuery("#order_data_id").val();
                    var basgate_woo_nonce = jQuery("#basgate_woo_nonce").val();
                    $('.basgate-img-loader').show();

                    jQuery.ajax({
                        type: "POST",
                        dataType: 'json',
                        data: {
                            action: "savetxnstatus",
                            basgate_order_id: basgate_order_id,
                            order_data_id: order_data_id,
                            basgate_woo_nonce: basgate_woo_nonce
                        },
                        url: "<?php echo esc_url(admin_url("admin-ajax.php")); ?>",
                        success: function(data) {
                            $('.basgate-img-loader').hide();
                            if (data.success == true) {
                                var html = '';
                                $.each(data.response, function(index, value) {
                                    html += "<tr>";
                                    html += "<td> 1 - " + index + "</td>";
                                    html += "<td> 2 - " + value + "</td>";
                                    html += "</tr>";
                                });
                                jQuery('#basgate_payment_table').html(html);
                                jQuery('#basgate_payment_area div.message').html('<div class="basgate_response success-box">' + data.message + '</div>');
                            } else {
                                jQuery('#basgate_payment_area div.message').html('<div class="basgate_response error-box">' + data.message + '</div>');
                            }
                        }
                    });
                });
            });
        </script>
    <?php
    }


    add_action('wp_ajax_savetxnstatus', 'savetxnstatus');

    function savetxnstatus()
    {
        BasgateHelper::basgate_log('====== STARTED savetxnstatus');
        if (!wp_verify_nonce($_POST['basgate_woo_nonce'], 'basgate_woo_nonce')) die('You are not authorised!');

        $settings = get_option(BasgateConstants::OPTION_DATA_NAME);
        $json = array("success" => false, "response" => '', 'message' => __('Something went wrong. Please again', 'bassdk-woocommerce-payments'));
        $save_response = BasgateConstants::SAVE_BASGATE_RESPONSE;
        if (!empty($_POST['basgate_order_id']) && $save_response) {
            $reqParams = array(
                "MID"        => $settings['bas_application_id'],
                "ORDERID"    => sanitize_text_field($_POST['basgate_order_id'])
            );

            $reqParams['CHECKSUMHASH'] = BasgateChecksum::generateSignature($reqParams, $settings['bas_merchant_key']);

            $retry = 1;
            do {
                $resParams = BasgateHelper::executecUrl(BasgateHelper::getTransactionStatusURL($settings['bas_environment']), $reqParams);
                $retry++;
            } while (!isset($resParams['body']) && $retry < BasgateConstants::MAX_RETRY_COUNT);

            if (!empty($resParams['STATUS'])) {
                $response = saveTxnResponse(sanitize_text_field($_POST['basgate_order_id']), sanitize_text_field($_POST['order_data_id']), $resParams);
                if ($response) {
                    $message = __('Updated <b>STATUS</b> has been fetched', 'bassdk-woocommerce-payments');
                    $json = array("success" => true, "response" => $resParams, 'message' => $message);
                }
            }
        }
        echo wp_json_encode($json);
        die;
    }

    /**
     * Save response in db
     */
    function saveTxnResponse($order_id, $id = false, $data  = array())
    {
        BasgateHelper::basgate_log('====== STARTED saveTxnResponse $order_id :' . $order_id . ' , $id:' . $id);

        global $wpdb;

        if (empty($data['status']) && empty($data['trxStatusId'])) {
            return false;
        }

        $status             = (!empty($data['status'])) ? (int)$data['status'] : $data['trxStatusId'];
        $basgate_order_id     = (!empty($data['orderId']) ? $data['orderId'] : '');
        $transaction_id     = (!empty($data['trxId']) ? $data['trxId'] : '');

        BasgateHelper::basgate_log('====== STARTED saveTxnResponse $status:' . $status . ' , $basgate_order_id:' . $basgate_order_id . ' , $transaction_id:' . $transaction_id);

        $table_name = $wpdb->prefix . "basgate_order_data";

        if ($id !== false) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE  {$table_name}
                        SET `order_id` = %d, `basgate_order_id` = %s, `transaction_id` = %s, `status` = %s, `basgate_response` = %s, `date_modified` = NOW() 
                        WHERE `id` = %d AND `basgate_order_id` = %s",
                    $order_id,
                    $basgate_order_id,
                    $transaction_id,
                    $status,
                    wp_json_encode($data),
                    (int)$id,
                    $basgate_order_id
                )
            );
            BasgateHelper::basgate_log('====== STARTED saveTxnResponse after UPDATE  $id:' . $id);
            return $id;
        } else {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$table_name} 
                        (`order_id`, `basgate_order_id`, `transaction_id`, `status`, `basgate_response`, `date_added`, `date_modified`) 
                        VALUES (%d, %s, %s, %s, %s, NOW(), NOW())",
                    $order_id,
                    $basgate_order_id,
                    $transaction_id,
                    $status,
                    wp_json_encode($data)
                )
            );
            $result = $wpdb->insert_id;
            BasgateHelper::basgate_log('====== STARTED saveTxnResponse after INSERT  $result:' . $result);
            return $result;
        }
    }
}

add_action('plugins_loaded', 'woocommerce_basgate_init', 0);

function woocommerce_basgate_init()
{
    // If the WooCommerce payment gateway class is not available nothing will return
    if (!class_exists('WC_Payment_Gateway')) return;

    // WooCommerce payment gateway class to hook Payment gateway
    require_once(plugin_basename('class.basgate.php'));

    BasgateHelper::basgate_log('===++++ woocommerce_basgate_init 111');

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_basgate_gateway');
    function woocommerce_add_basgate_gateway($methods)
    {
        BasgateHelper::basgate_log('===++++ woocommerce_add_basgate_gateway $methods:' . wp_json_encode($methods));

        if (BasgateHelper::$isInBasPlatform == false) {
            BasgateHelper::basgate_log('===++++ woocommerce_add_basgate_gateway == false');
            // // Example: Remove 'cheque' payment method
            // if (($key = array_search('cheque', $methods)) !== false) {
            //     unset($methods[$key]);
            // }
        } else {
            BasgateHelper::basgate_log('===++++ woocommerce_add_basgate_gateway == true');
            $methods[] = 'WC_Basgate';
        }

        return $methods;
    }

    add_filter('woocommerce_available_payment_gateways', 'custom_hide_basgate_payment_method_advanced');

    function custom_hide_basgate_payment_method_advanced($available_gateways)
    {
        BasgateHelper::basgate_log('===++++ custom_hide_basgate_payment_method_advanced $available_gateways:' . wp_json_encode($available_gateways));
        // if (is_cart() || is_checkout()) {
        // if (WC()->cart->total < 50 && isset($available_gateways['paypal'])) {
        if (isset($available_gateways['basgate'])) {
            BasgateHelper::basgate_log('===++++ custom_hide_basgate_payment_method_advanced basgate:' . wp_json_encode($available_gateways['basgate']));

            // foreach ($available_gateways as $key => $value) {
            //     BasgateHelper::basgate_log('===++++ custom_hide_basgate_payment_method_advanced $key:' . $key);
            // }

            // unset($available_gateways['paypal']);
        }

        // if (current_user_can('subscriber') && isset($available_gateways['credit_card'])) {
        //     unset($available_gateways['credit_card']);
        // }
        // }
        return $available_gateways;
    }

    /**
     * Localisation
     */
    load_plugin_textdomain('wc-basgate', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (isset($_GET['basgate_response']) && sanitize_text_field($_GET['basgate_response'])) {
        add_action('the_content', 'basgateResponseMessage');
    }

    add_action('wp_head', 'woocommerce_basgate_front_add_css');

    function woocommerce_basgate_front_add_css()
    {
    ?>
        <style>
            .basgate_response {
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 4px;
                text-align: center;
            }

            .basgate_response.error-box {
                color: #a94442;
                background-color: #f2dede;
                border-color: #ebccd1;
            }

            .basgate_response.success-box {
                color: #155724;
                background-color: #d4edda;
                border-color: #c3e6cb;
            }
        </style>
<?php
    }

    function basgateResponseMessage($content)
    {
        return '<div class="basgate_response box ' . htmlentities(sanitize_text_field($_GET['type'])) . '-box">' . htmlentities(urldecode(sanitize_text_field($_GET['basgate_response']))) . '</div>' . $content;
    }
}

function plugin_root()
{
    return __FILE__;
}
