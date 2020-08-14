<?php
/**
 * Plugin Name: TBC განვადება
 * Description: პლაგინი საშუალებას აძლევს მომხმარებელს შეიძინოს სასურველი პროდუქტი თიბისი ბანკის განვადებით.
 * Author: Saba Meskhi
 * Author URI: https://ink.ge/
 * Version: 1.0.0
 * Text Domain: wc-tbc-ganvadeba
 */

defined('ABSPATH') or exit;


// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

function wc_tbc_ganvadeba_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_tbc_ganv';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wc_tbc_ganvadeba_add_to_gateways');


function tbc_gateway_icon( $gateways ) {
    if ( isset( $gateways['tbc_ganvadeba'] ) ) {
        $gateways['tbc_ganvadeba']->icon =  plugin_dir_url(__FILE__) . 'tbc_logo.png';
    }

    return $gateways;
}

add_filter( 'woocommerce_available_payment_gateways', 'tbc_gateway_icon' );


function wc_tbc_ganvadeba_plugin_links($links)
{
    
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=tbc_ganvadeba') . '">' . __('Configure', 'wc-tbc-ganvadeba') . '</a>'
    );
    
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_tbc_ganvadeba_plugin_links');


add_action('plugins_loaded', 'wc_tbc_ganvadeba_init', 11);

function wc_tbc_ganvadeba_init()
{
    
    class WC_Gateway_tbc_ganv extends WC_Payment_Gateway
    {
        
        /**
         * Constructor
         */
        public function __construct()
        {
            
            $this->id                 = 'tbc_ganvadeba';
            $this->icon               = apply_filters('woocommerce_tbc_ganv_icon', '');
            $this->order_button_text  = __('განვადებით ყიდვა', 'tbc-gateway-free');
            $this->has_fields         = false;
            $this->method_title       = __('თიბისი განვადება', 'wc-tbc-ganvadeba');
            $this->method_description = __('დაამატეთ თიბისი ბანკის განვადება თქვენს საიტზე', 'wc-tbc-ganvadeba');
            
            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();
            
            // Define user set variables
            $this->title        = $this->get_option('title');
            $this->description  = $this->get_option('description');
            $this->instructions = $this->get_option('instructions', $this->description);
            
            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
         
        }
        
        
        /**
         * Init Form Fields
         */
        public function init_form_fields()
        {
            
            $this->form_fields = apply_filters('wc_tbc_ganvadeba_form_fields', array(
                
                'enabled' => array(
                    'title' => __('ჩართვა/გამორთვა', 'wc-tbc-ganvadeba'),
                    'type' => 'checkbox',
                    'label' => __('თიბისი განვადების ჩართვა', 'wc-tbc-ganvadeba'),
                    'default' => 'no'
                ),
                
                'title' => array(
                    'title' => __('სათაური', 'wc-tbc-ganvadeba'),
                    'type' => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-tbc-ganvadeba'),
                    'default' => __('განვადებით ყიდვა', 'wc-tbc-ganvadeba'),
                    'desc_tip' => true
                ),
                
                'description' => array(
                    'title' => __('აღწერა', 'wc-tbc-ganvadeba'),
                    'type' => 'textarea',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'wc-tbc-ganvadeba'),
                    'default' => __('შეიძინეთ პროდუქცია თიბისის განვადებით.', 'wc-tbc-ganvadeba'),
                    'desc_tip' => true
                )
               
            ));
        }
        
        
      
        public function process_payment($order_id)
        {
            
            $order = wc_get_order($order_id);
            
            $website_title = get_site_url();
            $items         = '';
            $itemAmount    = '';
            $totalAmount   = WC()->cart->subtotal;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product    = $cart_item['data'];
                $item_name  = $cart_item['data']->get_title();
                $product_id = $cart_item['product_id'];
                $quantity   = $cart_item['quantity'];
                $price      = WC()->cart->get_product_price($product);
                $subtotal   = WC()->cart->get_product_subtotal($product, $cart_item['quantity']);
                
                $items .= $item_name . ';';
                
                $itemAmount .= $item_name .'-' . $quantity . ';';
            }
            
            $tbc_link = 'https://tbccredit.ge/ganvadeba?utm_source=' . $website_title . '&productName=' . $items . ' &productAmount=' . $itemAmount . '&totalAmount=' . $totalAmount . '';
            $tbc_link = preg_replace('/\s+/', '', $tbc_link);

            $order->update_status('on-hold', __('თიბისის განვადება', 'wc-tbc-ganvadeba'));
            
      
            $order->reduce_order_stock();
            

            WC()->cart->empty_cart();
            
            return array(
                'result' => 'success',
                'redirect' => $tbc_link
            );
        }
        
    } 
}