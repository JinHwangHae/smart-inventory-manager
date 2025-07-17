<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SIM_API {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_sim_get_products', array($this, 'get_products'));
        add_action('wp_ajax_nopriv_sim_get_products', array($this, 'get_products'));
    }

    public function get_products() {
        $products = SIM_Product::get_instance()->get_all();
        wp_send_json_success($products);
    }
} 