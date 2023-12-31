<?php

namespace By\Gateways;

use Exception;
use WC_Payment_Gateway;
use ByPaymentApgController;

if (!defined('ABSPATH')) {
    exit;
}

class By_Apg_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'beyounger-apg'; // 支付网关插件ID
        $this->icon = ''; // todo 将显示在结帐页面上您的网关名称附近的图标的URL
        $this->has_fields = true; // todo 如果需要自定义信用卡形式
        $this->method_title = 'Beyounger APG Payments Gateway';
        $this->method_description = 'Take Credit/Debit Card payments on your store.'; // 将显示在选项页面上
        // 网关可以支持订阅，退款，保存付款方式，
        // 这里仅支持支付功能
        $this->supports = array(
            'products'
        );
        // 具有所有选项字段的方法
        $this->init_form_fields();

        // 加载设置。
        $this->init_settings();
        $this->enabled = $this->get_option( 'enabled' );
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );

        $this->domain = $this->get_option( 'domain' );
        $this->api_key = $this->get_option( 'api_key' );
        $this->api_secret = $this->get_option( 'api_secret' );
        $this->api_webhook = $this->get_option( 'api_webhook' );
        //$this->iframe = $this->get_option( 'iframe' );

        // 这个action hook保存设置
//        add_action( 'wp_enqueue_scripts'. $this->id, [$this, 'payment_scripts'] );//payment_scripts
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        //add_action( 'woocommerce_checkout_order_processed', 'is_express_delivery',  1, 1  );

        // apply_filters( 'wp_doing_ajax', true );

        $this->controller = new ByPaymentApgController;

    }

    function is_express_delivery( $order_id ){
        //echo '#########is_express_delivery' . "\n";
        //$order = new WC_Order( $order_id );
        //something else
    }

    /**
     * 插件设置选项
     */
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Beyounger Payment Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Credit/Debit Card',
//                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with your credit card via our super-cool payment gateway.',
            ),
            'domain' => array (
                'title'       => 'Beyounger Domain',
                'type'        => 'text',
                'default'     => 'https://api.beyounger.com',
            ),
            'api_key' => array (
                'title'       => 'API Key',
                'type'        => 'text',
            ),
            'api_secret' => array (
                'title'       => 'API Secret',
                'type'        => 'text',
            ),
            'api_webhook' => array (
                'title'       => 'Your Domain',
                'type'        => 'text',
                'default'     => 'https://yourdomain.com',
            ),
//            'iframe' => array (
//                'title'       => 'Iframe',
//                'label'       => 'Enable Beyounger Payment Iframe',
//                'type'        => 'checkbox',
//                'description' => 'If use iframe, page will be displayed as an iframe on the receipt_page of woo.',
//                'default'     => 'no',
//            ),
        );

    }

    /**
     * 字段验证
     */
    public function validate_fields() {

    }

    /**
     * 处理付款
     * @throws Exception
     */
    public function process_payment( $order_id ) {
        WC()->session->set('beyounger_order', $order_id);
        return $this->controller->payment($this, '');
    }


    public function receipt_page($order_id)
    {
        wp_enqueue_style( 'custom-css' ,  plugins_url( '/asset/style.css' , __FILE__ ));


        wp_enqueue_style( 'custom-css1' ,  plugins_url( '/asset/apg-payment-sdk/assets/styles/paymentForm.css' , __FILE__ ));
        wp_enqueue_style( 'custom-css2' ,  plugins_url( '/asset/apg-payment-sdk/assets/styles/paymentGlobal.css' , __FILE__ ));
        wp_enqueue_style( 'custom-css3' ,  plugins_url( '/asset/apg-payment-sdk/fonts/iconfont.css' , __FILE__ ));
        wp_enqueue_style( 'custom-css4' ,  plugins_url( '/asset/apg.css' , __FILE__ ));

        wp_enqueue_script('custom-load2', plugins_url('/asset/apg-payment-sdk/paymentInline-other.min.js', __FILE__), [], null, false);

        wp_enqueue_script('e-1', 'https://cdn.bootcdn.net/ajax/libs/js-sha512/0.8.0/sha512.min.js', [], null, false);
        wp_enqueue_script('e-2', 'https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js', [], null, false);
        wp_enqueue_script('e-3', 'https://cdn.bootcdn.net/ajax/libs/axios/0.27.2/axios.min.js', [], null, false);
        wp_enqueue_script('e-4', 'https://cdn.bootcdn.net/ajax/libs/jsencrypt/3.2.1/jsencrypt.min.js', [], null, false);
        wp_enqueue_script('e-5', 'https://cdn.bootcdn.net/ajax/libs/js-sha256/0.9.0/sha256.min.js', [], null, false);
        wp_enqueue_script('e-6', 'https://cdn.bootcdn.net/ajax/libs/crypto-js/4.1.1/crypto-js.min.js', [], null, false);
        wp_enqueue_script('custom-load', plugins_url('/asset/load-apg.js', __FILE__), [], null, false);
        $orderId = get_post_meta($order_id, 'orderNo', true);

        wp_localize_script( 'custom-load', 'plugin_name_ajax_object',
            array( 'var_order_id'=> $orderId,)
        );


        //$by_url = get_post_meta($order_id, 'by_url', true);

        ?>
            <body>
            <div class="currencyContainer">
                <div style="background-color: #fff">
                    <div class="payContent"></div>

                    <button class="pay-button" type="button" onclick="submitCardApg(event)">Pay</button>
                </div>
            </div>
            </body>
            <script type="text/javascript" >
                getTradeIDMethod().then((jsonStr) => {
                    console.log('jsonStr',jsonStr);
                    // console.log('payInit',payInit)
                    console.log('pay-button', document.querySelector('.pay-button'))
                    console.log('payContent', document.querySelector('.payContent'))
                    if(!jsonStr["tradeId"]){
                        console.log('tradeId is empty')
                        return
                    }
                    payInit({
                        tradeId: jsonStr["tradeId"],
                        userElement: "payContent",
                        url: jsonStr["action"],
                        buttonName: "pay-button",
                    });
                }).catch(()=>{
                    alert('tradeId can not be empty');
                });
            </script>



        <?php


    }


    /**
     * 自定义信用卡表格
     */
    public function payment_fields() {


    }

    /*
     * 自定义CSS和JS，在大多数情况下，仅在使用自定义信用卡表格时才需要
     */
    public function payment_scripts() {


    }

}
