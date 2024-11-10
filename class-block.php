<?php
require_once __DIR__ . '/includes/BasgateHelper.php';

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Basgate_Blocks extends AbstractPaymentMethodType
{

    private $gateway;
    protected $name = 'basgate';

    public function initialize()
    {
        $this->settings = get_option(BasgateConstants::OPTION_DATA_NAME, []);
        // $this->gateway = new WC_Basgate(); 
    }

    public function get_payment_method_script_handles()
    {
        BasgateHelper::basgate_log('=====+++++ STARTED get_payment_method_script_handles()');
        wp_register_script(
            'basgate-blocks-integration',
            plugin_dir_url(__FILE__) . 'assets/js/admin/checkout-block.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc_basgate-blocks-integration');
        }
        return ['basgate-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        BasgateHelper::basgate_log('====+++++ get_payment_method_data $isInBasPlatform :(' . BasgateHelper::$isInBasPlatform . ')');

        if (BasgateHelper::$isInBasPlatform == false) {
            return [];
        }
        BasgateHelper::basgate_log('===== STARTED get_payment_method_data()');
        return [
            'title' => __("Pay via Basgate", 'bassdk-woocommerce-payments'),
            'description' => $this->settings['bas_description'],
        ];
    }
}
