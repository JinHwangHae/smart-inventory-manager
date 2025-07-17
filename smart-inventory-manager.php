<?php
/**
 * Plugin Name: Smart Inventory Manager
 * Plugin URI: https://yourwebsite.com/smart-inventory-manager
 * Description: Advanced inventory management system with real-time tracking, low stock alerts, and supplier management.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-inventory-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIM_PLUGIN_VERSION', '1.0.0');
define('SIM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SIM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Main plugin class
class SmartInventoryManager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_post_sim_save_product', array($this, 'handle_save_product'));
        add_action('wp_ajax_sim_delete_product', array($this, 'handle_delete_product'));
        add_action('wp_ajax_sim_adjust_quantity', array($this, 'handle_adjust_quantity'));
        add_action('admin_post_sim_save_supplier', array($this, 'handle_save_supplier'));
        add_action('wp_ajax_sim_delete_supplier', array($this, 'handle_delete_supplier'));
        add_action('wp_ajax_sim_get_supplier_products', array($this, 'handle_get_supplier_products'));
        add_action('admin_post_sim_save_alert_settings', array($this, 'handle_save_alert_settings'));
        add_action('wp_ajax_sim_send_test_alert', array($this, 'handle_send_test_alert'));
        add_action('wp_ajax_sim_send_manual_alert', array($this, 'handle_send_manual_alert'));
        add_action('wp_ajax_sim_export_inventory_csv', array($this, 'handle_export_inventory_csv'));
        add_action('wp_ajax_sim_export_activity_csv', array($this, 'handle_export_activity_csv'));
        add_action('wp_ajax_sim_export_suppliers_csv', array($this, 'handle_export_suppliers_csv'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('smart-inventory-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->init_components();
    }
    
    private function init_components() {
        // Include required files
        require_once SIM_PLUGIN_PATH . 'includes/class-sim-database.php';
require_once SIM_PLUGIN_PATH . 'includes/class-sim-product.php';
require_once SIM_PLUGIN_PATH . 'includes/class-sim-supplier.php';
require_once SIM_PLUGIN_PATH . 'includes/class-sim-alerts.php';
require_once SIM_PLUGIN_PATH . 'includes/class-sim-api.php';
require_once SIM_PLUGIN_PATH . 'includes/class-bulk-operations.php';
        
        // Initialize database
        SIM_Database::get_instance();
        
        // Initialize other components
        SIM_Product::get_instance();
        SIM_Supplier::get_instance();
        SIM_Alerts::get_instance();
        SIM_API::get_instance();
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Smart Inventory', 'smart-inventory-manager'),
            __('Smart Inventory', 'smart-inventory-manager'),
            'manage_options',
            'smart-inventory-manager',
            array($this, 'admin_dashboard'),
            'dashicons-cart',
            30
        );
        
        add_submenu_page(
            'smart-inventory-manager',
            __('Dashboard', 'smart-inventory-manager'),
            __('Dashboard', 'smart-inventory-manager'),
            'manage_options',
            'smart-inventory-manager',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'smart-inventory-manager',
            __('Products', 'smart-inventory-manager'),
            __('Products', 'smart-inventory-manager'),
            'manage_options',
            'smart-inventory-products',
            array($this, 'admin_products')
        );
        
        add_submenu_page(
            'smart-inventory-manager',
            __('Suppliers', 'smart-inventory-manager'),
            __('Suppliers', 'smart-inventory-manager'),
            'manage_options',
            'smart-inventory-suppliers',
            array($this, 'admin_suppliers')
        );
        
        add_submenu_page(
            'smart-inventory-manager',
            __('Alerts', 'smart-inventory-manager'),
            __('Alerts', 'smart-inventory-manager'),
            'manage_options',
            'smart-inventory-alerts',
            array($this, 'admin_alerts')
        );
        
        add_submenu_page(
            'smart-inventory-manager',
            __('Reports', 'smart-inventory-manager'),
            __('Reports', 'smart-inventory-manager'),
            'manage_options',
            'smart-inventory-reports',
            array($this, 'admin_reports')
        );
        
        add_submenu_page(
            'smart-inventory-manager',
            __('Bulk Operations', 'smart-inventory-manager'),
            __('Bulk Operations', 'smart-inventory-manager'),
            'manage_options',
            'sim-bulk-operations',
            array($this, 'bulk_operations_page')
        );
        
        add_submenu_page(
            'smart-inventory-manager',
            __('Settings', 'smart-inventory-manager'),
            __('Settings', 'smart-inventory-manager'),
            'manage_options',
            'smart-inventory-settings',
            array($this, 'admin_settings')
        );
    }
    
    public function admin_dashboard() {
        include SIM_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    public function admin_products() {
        include SIM_PLUGIN_PATH . 'admin/views/products.php';
    }
    
    public function admin_suppliers() {
        include SIM_PLUGIN_PATH . 'admin/views/suppliers.php';
    }
    
    public function admin_alerts() {
        include SIM_PLUGIN_PATH . 'admin/views/alerts.php';
    }
    
    public function admin_reports() {
        include SIM_PLUGIN_PATH . 'admin/views/reports.php';
    }
    
    public function bulk_operations_page() {
        include SIM_PLUGIN_PATH . 'admin/views/bulk-operations.php';
    }
    
    public function admin_settings() {
        include SIM_PLUGIN_PATH . 'admin/views/settings.php';
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smart-inventory') !== false) {
            wp_enqueue_script('sim-admin', SIM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SIM_PLUGIN_VERSION, true);
            wp_enqueue_style('sim-admin', SIM_PLUGIN_URL . 'assets/css/admin.css', array(), SIM_PLUGIN_VERSION);
            
            // Localize script for AJAX
            wp_localize_script('sim-admin', 'sim_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sim_nonce')
            ));
        }
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_script('sim-frontend', SIM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SIM_PLUGIN_VERSION, true);
        wp_enqueue_style('sim-frontend', SIM_PLUGIN_URL . 'assets/css/frontend.css', array(), SIM_PLUGIN_VERSION);
    }
    
    public function activate() {
        // Include database class first
        require_once SIM_PLUGIN_PATH . 'includes/class-sim-database.php';
        
        // Create database tables
        SIM_Database::create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function set_default_options() {
        $default_options = array(
            'low_stock_threshold' => 10,
            'alert_email' => get_option('admin_email'),
            'auto_reorder_enabled' => false,
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'timezone' => wp_timezone_string()
        );
        
        foreach ($default_options as $key => $value) {
            if (get_option('sim_' . $key) === false) {
                update_option('sim_' . $key, $value);
            }
        }
    }

    public function handle_save_product() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        if (!wp_verify_nonce($_POST['sim_product_nonce'], 'sim_product_nonce')) {
            wp_die(__('Security check failed.'));
        }

        $action = sanitize_text_field($_POST['form_action']);
        $product_data = array(
            'name' => sanitize_text_field($_POST['product_name']),
            'sku' => sanitize_text_field($_POST['product_sku']),
            'supplier_id' => !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null,
            'quantity' => intval($_POST['quantity']),
            'low_stock_threshold' => intval($_POST['low_stock_threshold'])
        );

        if ($action === 'add') {
            $product_id = SIM_Product::get_instance()->create($product_data);
            $message = 'added';
        } else {
            $product_id = intval($_POST['product_id']);
            SIM_Product::get_instance()->update($product_id, $product_data);
            $message = 'updated';
        }

        wp_redirect(admin_url('admin.php?page=smart-inventory-products&message=' . $message));
        exit;
    }

    public function handle_delete_product() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $result = SIM_Product::get_instance()->delete($product_id);

        if ($result) {
            wp_send_json_success('Product deleted successfully');
        } else {
            wp_send_json_error('Failed to delete product');
        }
    }

    public function handle_adjust_quantity() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $adjustment_type = sanitize_text_field($_POST['adjustment_type']);
        $amount = intval($_POST['amount']);
        $note = sanitize_textarea_field($_POST['note']);

        $product = SIM_Product::get_instance()->get($product_id);
        if (!$product) {
            wp_send_json_error('Product not found');
        }

        $current_quantity = intval($product['quantity']);
        $new_quantity = $current_quantity;

        switch ($adjustment_type) {
            case 'add':
                $new_quantity = $current_quantity + $amount;
                break;
            case 'subtract':
                $new_quantity = max(0, $current_quantity - $amount);
                break;
            case 'set':
                $new_quantity = $amount;
                break;
        }

        $result = SIM_Product::get_instance()->update($product_id, array('quantity' => $new_quantity));

        if ($result) {
            // Log the inventory change
            $this->log_inventory_change($product_id, $adjustment_type, $amount, $note);
            wp_send_json_success(array(
                'new_quantity' => $new_quantity,
                'message' => 'Quantity updated successfully'
            ));
        } else {
            wp_send_json_error('Failed to update quantity');
        }
    }

    private function log_inventory_change($product_id, $change_type, $quantity_change, $note = '') {
        global $wpdb;
        $db = SIM_Database::get_instance();
        $table = $db->get_table('inventory_logs');

        $wpdb->insert($table, array(
            'product_id' => $product_id,
            'change_type' => $change_type,
            'quantity_change' => $quantity_change,
            'note' => $note
        ));

        // Trigger alert check
        do_action('sim_inventory_changed', $product_id, $change_type, $quantity_change);
    }

    public function handle_save_supplier() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        if (!wp_verify_nonce($_POST['sim_supplier_nonce'], 'sim_supplier_nonce')) {
            wp_die(__('Security check failed.'));
        }

        $action = sanitize_text_field($_POST['form_action']);
        $supplier_data = array(
            'name' => sanitize_text_field($_POST['supplier_name']),
            'email' => sanitize_email($_POST['supplier_email']),
            'phone' => sanitize_text_field($_POST['supplier_phone']),
            'address' => sanitize_textarea_field($_POST['supplier_address'])
        );

        if ($action === 'add') {
            $supplier_id = SIM_Supplier::get_instance()->create($supplier_data);
            $message = 'added';
        } else {
            $supplier_id = intval($_POST['supplier_id']);
            SIM_Supplier::get_instance()->update($supplier_id, $supplier_data);
            $message = 'updated';
        }

        wp_redirect(admin_url('admin.php?page=smart-inventory-suppliers&message=' . $message));
        exit;
    }

    public function handle_delete_supplier() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $supplier_id = intval($_POST['supplier_id']);
        
        // Check if supplier has products
        $products = SIM_Product::get_instance()->get_all();
        $supplier_products = array_filter($products, function($product) use ($supplier_id) {
            return $product['supplier_id'] == $supplier_id;
        });

        if (!empty($supplier_products)) {
            wp_send_json_error('Cannot delete supplier with associated products. Please reassign or delete the products first.');
        }

        $result = SIM_Supplier::get_instance()->delete($supplier_id);

        if ($result) {
            wp_send_json_success('Supplier deleted successfully');
        } else {
            wp_send_json_error('Failed to delete supplier');
        }
    }

    public function handle_get_supplier_products() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $supplier_id = intval($_POST['supplier_id']);
        $products = SIM_Product::get_instance()->get_all();
        $supplier_products = array_filter($products, function($product) use ($supplier_id) {
            return $product['supplier_id'] == $supplier_id;
        });

        $html = '';
        if (!empty($supplier_products)) {
            $html .= '<table class="wp-list-table widefat fixed striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __('Product Name', 'smart-inventory-manager') . '</th>';
            $html .= '<th>' . __('SKU', 'smart-inventory-manager') . '</th>';
            $html .= '<th>' . __('Quantity', 'smart-inventory-manager') . '</th>';
            $html .= '<th>' . __('Status', 'smart-inventory-manager') . '</th>';
            $html .= '</tr></thead><tbody>';
            
            foreach ($supplier_products as $product) {
                $status_class = '';
                $status_text = '';
                if ($product['quantity'] <= 0) {
                    $status_class = 'out-of-stock';
                    $status_text = __('Out of Stock', 'smart-inventory-manager');
                } elseif ($product['quantity'] <= $product['low_stock_threshold']) {
                    $status_class = 'low-stock';
                    $status_text = __('Low Stock', 'smart-inventory-manager');
                } else {
                    $status_class = 'in-stock';
                    $status_text = __('In Stock', 'smart-inventory-manager');
                }

                $html .= '<tr>';
                $html .= '<td>' . esc_html($product['name']) . '</td>';
                $html .= '<td>' . esc_html($product['sku']) . '</td>';
                $html .= '<td>' . intval($product['quantity']) . '</td>';
                $html .= '<td><span class="stock-status ' . $status_class . '">' . $status_text . '</span></td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
        } else {
            $html = '<p>' . __('No products found for this supplier.', 'smart-inventory-manager') . '</p>';
        }

        wp_send_json_success($html);
    }

    public function handle_save_alert_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        if (!wp_verify_nonce($_POST['sim_alert_settings_nonce'], 'sim_alert_settings_nonce')) {
            wp_die(__('Security check failed.'));
        }

        $alert_email = sanitize_email($_POST['alert_email']);
        $low_stock_threshold = intval($_POST['low_stock_threshold']);
        $auto_reorder_enabled = isset($_POST['auto_reorder_enabled']) ? true : false;
        $alert_frequency = sanitize_text_field($_POST['alert_frequency']);

        update_option('sim_alert_email', $alert_email);
        update_option('sim_low_stock_threshold', $low_stock_threshold);
        update_option('sim_auto_reorder_enabled', $auto_reorder_enabled);
        update_option('sim_alert_frequency', $alert_frequency);

        wp_redirect(admin_url('admin.php?page=smart-inventory-alerts&message=settings_updated'));
        exit;
    }

    public function handle_send_test_alert() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $result = SIM_Alerts::get_instance()->send_test_alert();

        if ($result) {
            wp_send_json_success('Test alert sent successfully!');
        } else {
            wp_send_json_error('Failed to send test alert. Please check your email settings.');
        }
    }

    public function handle_send_manual_alert() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $product = SIM_Product::get_instance()->get($product_id);

        if (!$product) {
            wp_send_json_error('Product not found');
        }

        $result = SIM_Alerts::get_instance()->send_alert($product, true);

        if ($result) {
            wp_send_json_success('Alert sent successfully!');
        } else {
            wp_send_json_error('Failed to send alert. Please check your email settings.');
        }
    }

    public function handle_export_inventory_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_die('Security check failed');
        }

        $products = SIM_Product::get_instance()->get_all();
        $suppliers = SIM_Supplier::get_instance()->get_all();
        
        // Create supplier lookup array
        $supplier_lookup = array();
        foreach ($suppliers as $supplier) {
            $supplier_lookup[$supplier['id']] = $supplier['name'];
        }

        $filename = 'inventory-report-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Product Name',
            'SKU',
            'Current Stock',
            'Low Stock Threshold',
            'Status',
            'Supplier',
            'Created Date'
        ));
        
        foreach ($products as $product) {
            $supplier_name = isset($supplier_lookup[$product['supplier_id']]) ? $supplier_lookup[$product['supplier_id']] : '';
            
            $status = '';
            if ($product['quantity'] <= 0) {
                $status = 'Out of Stock';
            } elseif ($product['quantity'] <= $product['low_stock_threshold']) {
                $status = 'Low Stock';
            } else {
                $status = 'In Stock';
            }
            
            fputcsv($output, array(
                $product['name'],
                $product['sku'],
                $product['quantity'],
                $product['low_stock_threshold'],
                $status,
                $supplier_name,
                $product['created_at']
            ));
        }
        
        fclose($output);
        exit;
    }

    public function handle_export_activity_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $db = SIM_Database::get_instance();
        $activities = $wpdb->get_results("
            SELECT il.*, p.name as product_name, p.sku
            FROM {$db->get_table('inventory_logs')} il
            LEFT JOIN {$db->get_table('products')} p ON il.product_id = p.id
            ORDER BY il.created_at DESC
        ", ARRAY_A);

        $filename = 'activity-report-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Product Name',
            'SKU',
            'Action Type',
            'Quantity Change',
            'Note',
            'Date'
        ));
        
        foreach ($activities as $activity) {
            fputcsv($output, array(
                $activity['product_name'],
                $activity['sku'],
                ucfirst($activity['change_type']),
                $activity['quantity_change'],
                $activity['note'],
                $activity['created_at']
            ));
        }
        
        fclose($output);
        exit;
    }

    public function handle_export_suppliers_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'sim_nonce')) {
            wp_die('Security check failed');
        }

        $suppliers = SIM_Supplier::get_instance()->get_all();
        $products = SIM_Product::get_instance()->get_all();
        
        // Calculate product counts for each supplier
        $supplier_products = array();
        foreach ($products as $product) {
            if ($product['supplier_id']) {
                $supplier_products[$product['supplier_id']] = ($supplier_products[$product['supplier_id']] ?? 0) + 1;
            }
        }

        $filename = 'suppliers-report-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Supplier Name',
            'Email',
            'Phone',
            'Address',
            'Product Count',
            'Created Date'
        ));
        
        foreach ($suppliers as $supplier) {
            $product_count = $supplier_products[$supplier['id']] ?? 0;
            
            fputcsv($output, array(
                $supplier['name'],
                $supplier['email'],
                $supplier['phone'],
                $supplier['address'],
                $product_count,
                $supplier['created_at']
            ));
        }
        
        fclose($output);
        exit;
    }
}

// Initialize the plugin
SmartInventoryManager::get_instance(); 