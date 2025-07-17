<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SIM_Alerts {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into inventory changes to trigger alerts
        add_action('sim_inventory_changed', array($this, 'check_and_send_alerts'), 10, 3);
    }

    public function check_low_stock() {
        $products = SIM_Product::get_instance()->get_all();
        $alerts = array();
        foreach ($products as $product) {
            if ($product['quantity'] <= $product['low_stock_threshold']) {
                $alerts[] = $product;
            }
        }
        return $alerts;
    }

    public function send_alert($product, $force = false) {
        $alert_email = get_option('sim_alert_email', get_option('admin_email'));
        $frequency = get_option('sim_alert_frequency', 'immediate');
        
        // Check if we should send based on frequency
        if (!$force && $frequency !== 'immediate') {
            return false;
        }

        $subject = sprintf(
            __('[%s] Low Stock Alert: %s', 'smart-inventory-manager'),
            get_bloginfo('name'),
            $product['name']
        );

        $message = $this->build_alert_message($product);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($alert_email, $subject, $message, $headers);
        
        if ($sent) {
            $this->log_alert_sent($product['id']);
        }
        
        return $sent;
    }

    private function build_alert_message($product) {
        $supplier_name = '';
        if ($product['supplier_id']) {
            $supplier = SIM_Supplier::get_instance()->get($product['supplier_id']);
            $supplier_name = $supplier ? $supplier['name'] : '';
        }

        $auto_reorder_enabled = get_option('sim_auto_reorder_enabled', false);
        $suggested_quantity = $this->calculate_suggested_reorder($product);

        $message = '<html><body>';
        $message .= '<h2>' . __('Low Stock Alert', 'smart-inventory-manager') . '</h2>';
        $message .= '<p><strong>' . __('Product:', 'smart-inventory-manager') . '</strong> ' . esc_html($product['name']) . '</p>';
        $message .= '<p><strong>' . __('SKU:', 'smart-inventory-manager') . '</strong> ' . esc_html($product['sku']) . '</p>';
        $message .= '<p><strong>' . __('Current Stock:', 'smart-inventory-manager') . '</strong> ' . intval($product['quantity']) . '</p>';
        $message .= '<p><strong>' . __('Low Stock Threshold:', 'smart-inventory-manager') . '</strong> ' . intval($product['low_stock_threshold']) . '</p>';
        
        if ($supplier_name) {
            $message .= '<p><strong>' . __('Supplier:', 'smart-inventory-manager') . '</strong> ' . esc_html($supplier_name) . '</p>';
        }

        if ($auto_reorder_enabled && $suggested_quantity > 0) {
            $message .= '<p><strong>' . __('Suggested Reorder Quantity:', 'smart-inventory-manager') . '</strong> ' . $suggested_quantity . '</p>';
        }

        $message .= '<p><a href="' . admin_url('admin.php?page=smart-inventory-products&action=edit&id=' . $product['id']) . '">' . __('View Product in Admin', 'smart-inventory-manager') . '</a></p>';
        $message .= '<hr>';
        $message .= '<p><small>' . __('This alert was sent by Smart Inventory Manager plugin.', 'smart-inventory-manager') . '</small></p>';
        $message .= '</body></html>';

        return $message;
    }

    private function calculate_suggested_reorder($product) {
        // Simple algorithm: suggest 2x the threshold or minimum 10 units
        $suggested = max($product['low_stock_threshold'] * 2, 10);
        
        // If out of stock, suggest more
        if ($product['quantity'] <= 0) {
            $suggested = max($suggested, 20);
        }
        
        return $suggested;
    }

    public function check_and_send_alerts($product_id, $change_type, $quantity_change) {
        // Only check for alerts on stock reductions
        if ($quantity_change >= 0) {
            return;
        }

        $product = SIM_Product::get_instance()->get($product_id);
        if (!$product) {
            return;
        }

        // Check if this change triggered a low stock alert
        if ($product['quantity'] <= $product['low_stock_threshold']) {
            $this->send_alert($product);
        }
    }

    public function send_test_alert() {
        $test_product = array(
            'id' => 0,
            'name' => __('Test Product', 'smart-inventory-manager'),
            'sku' => 'TEST-001',
            'quantity' => 5,
            'low_stock_threshold' => 10,
            'supplier_id' => null
        );

        return $this->send_alert($test_product, true);
    }

    public function send_bulk_alert($products) {
        if (empty($products)) {
            return false;
        }

        $alert_email = get_option('sim_alert_email', get_option('admin_email'));
        $subject = sprintf(
            __('[%s] Bulk Low Stock Alert - %d Products', 'smart-inventory-manager'),
            get_bloginfo('name'),
            count($products)
        );

        $message = $this->build_bulk_alert_message($products);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $sent = wp_mail($alert_email, $subject, $message, $headers);
        
        if ($sent) {
            foreach ($products as $product) {
                $this->log_alert_sent($product['id']);
            }
        }
        
        return $sent;
    }

    private function build_bulk_alert_message($products) {
        $message = '<html><body>';
        $message .= '<h2>' . __('Bulk Low Stock Alert', 'smart-inventory-manager') . '</h2>';
        $message .= '<p>' . sprintf(__('You have %d products with low stock levels:', 'smart-inventory-manager'), count($products)) . '</p>';
        
        $message .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
        $message .= '<tr>';
        $message .= '<th>' . __('Product', 'smart-inventory-manager') . '</th>';
        $message .= '<th>' . __('SKU', 'smart-inventory-manager') . '</th>';
        $message .= '<th>' . __('Current Stock', 'smart-inventory-manager') . '</th>';
        $message .= '<th>' . __('Threshold', 'smart-inventory-manager') . '</th>';
        $message .= '</tr>';

        foreach ($products as $product) {
            $message .= '<tr>';
            $message .= '<td>' . esc_html($product['name']) . '</td>';
            $message .= '<td>' . esc_html($product['sku']) . '</td>';
            $message .= '<td>' . intval($product['quantity']) . '</td>';
            $message .= '<td>' . intval($product['low_stock_threshold']) . '</td>';
            $message .= '</tr>';
        }

        $message .= '</table>';
        $message .= '<p><a href="' . admin_url('admin.php?page=smart-inventory-alerts') . '">' . __('View All Alerts in Admin', 'smart-inventory-manager') . '</a></p>';
        $message .= '<hr>';
        $message .= '<p><small>' . __('This alert was sent by Smart Inventory Manager plugin.', 'smart-inventory-manager') . '</small></p>';
        $message .= '</body></html>';

        return $message;
    }

    private function log_alert_sent($product_id) {
        global $wpdb;
        $db = SIM_Database::get_instance();
        $table = $db->get_table('inventory_logs');

        $wpdb->insert($table, array(
            'product_id' => $product_id,
            'change_type' => 'alert_sent',
            'quantity_change' => 0,
            'note' => 'Low stock alert sent via email'
        ));
    }

    public function get_alert_history($limit = 50) {
        global $wpdb;
        $db = SIM_Database::get_instance();
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT il.*, p.name as product_name, p.sku
            FROM {$db->get_table('inventory_logs')} il
            LEFT JOIN {$db->get_table('products')} p ON il.product_id = p.id
            WHERE il.change_type = 'alert_sent'
            ORDER BY il.created_at DESC 
            LIMIT %d
        ", $limit), ARRAY_A);
    }

    public function clear_old_alerts($days = 30) {
        global $wpdb;
        $db = SIM_Database::get_instance();
        $table = $db->get_table('inventory_logs');
        
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$table} 
            WHERE change_type = 'alert_sent' 
            AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
        ", $days));
    }
} 