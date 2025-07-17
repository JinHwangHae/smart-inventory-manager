<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SIM_Database {
    private static $instance = null;
    private $tables = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->tables = array(
            'products' => $wpdb->prefix . 'sim_products',
            'suppliers' => $wpdb->prefix . 'sim_suppliers',
            'inventory_logs' => $wpdb->prefix . 'sim_inventory_logs',
        );
    }

    public static function create_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $products = $wpdb->prefix . 'sim_products';
        $suppliers = $wpdb->prefix . 'sim_suppliers';
        $inventory_logs = $wpdb->prefix . 'sim_inventory_logs';

        $sql = "
        CREATE TABLE $products (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            sku VARCHAR(100) NOT NULL,
            supplier_id BIGINT UNSIGNED,
            quantity INT NOT NULL DEFAULT 0,
            low_stock_threshold INT NOT NULL DEFAULT 10,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sku (sku)
        ) $charset_collate;

        CREATE TABLE $suppliers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(50),
            address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE $inventory_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            change_type VARCHAR(50) NOT NULL,
            quantity_change INT NOT NULL,
            note TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) $charset_collate;
        ";

        dbDelta($sql);
    }

    public function get_table($name) {
        return isset($this->tables[$name]) ? $this->tables[$name] : null;
    }
} 