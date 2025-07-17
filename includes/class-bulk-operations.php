<?php
/**
 * Bulk Operations Class
 * 
 * Handles bulk operations, import/export functionality
 * 
 * @package Smart_Inventory_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIM_Bulk_Operations {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_sim_bulk_delete_products', array($this, 'bulk_delete_products'));
        add_action('wp_ajax_sim_bulk_delete_suppliers', array($this, 'bulk_delete_suppliers'));
        add_action('wp_ajax_sim_bulk_update_quantities', array($this, 'bulk_update_quantities'));
        add_action('wp_ajax_sim_import_products', array($this, 'import_products'));
        add_action('wp_ajax_sim_import_suppliers', array($this, 'import_suppliers'));
        add_action('wp_ajax_sim_export_products', array($this, 'export_products'));
        add_action('wp_ajax_sim_export_suppliers', array($this, 'export_suppliers'));
        add_action('wp_ajax_sim_export_inventory_log', array($this, 'export_inventory_log'));
    }
    
    /**
     * Bulk delete products
     */
    public function bulk_delete_products() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
        
        if (empty($product_ids)) {
            wp_send_json_error('No products selected');
        }
        
        $deleted_count = 0;
        $errors = array();
        
        foreach ($product_ids as $product_id) {
            $result = SIM_Product::get_instance()->delete($product_id);
            if ($result) {
                $deleted_count++;
            } else {
                $errors[] = "Failed to delete product ID: $product_id";
            }
        }
        
        wp_send_json_success(array(
            'deleted_count' => $deleted_count,
            'errors' => $errors,
            'message' => "Successfully deleted $deleted_count products"
        ));
    }
    
    /**
     * Bulk delete suppliers
     */
    public function bulk_delete_suppliers() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $supplier_ids = isset($_POST['supplier_ids']) ? array_map('intval', $_POST['supplier_ids']) : array();
        
        if (empty($supplier_ids)) {
            wp_send_json_error('No suppliers selected');
        }
        
        $deleted_count = 0;
        $errors = array();
        
        foreach ($supplier_ids as $supplier_id) {
            $result = SIM_Supplier::get_instance()->delete($supplier_id);
            if ($result) {
                $deleted_count++;
            } else {
                $errors[] = "Failed to delete supplier ID: $supplier_id";
            }
        }
        
        wp_send_json_success(array(
            'deleted_count' => $deleted_count,
            'errors' => $errors,
            'message' => "Successfully deleted $deleted_count suppliers"
        ));
    }
    
    /**
     * Bulk update quantities
     */
    public function bulk_update_quantities() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $updates = isset($_POST['updates']) ? $_POST['updates'] : array();
        
        if (empty($updates)) {
            wp_send_json_error('No updates provided');
        }
        
        $updated_count = 0;
        $errors = array();
        
        foreach ($updates as $update) {
            $product_id = intval($update['product_id']);
            $quantity = intval($update['quantity']);
            $action = sanitize_text_field($update['action']); // 'set', 'add', 'subtract'
            
            $product = SIM_Product::get_instance()->get($product_id);
            $current_quantity = $product ? $product->quantity : 0;
            
            switch ($action) {
                case 'set':
                    $new_quantity = $quantity;
                    break;
                case 'add':
                    $new_quantity = $current_quantity + $quantity;
                    break;
                case 'subtract':
                    $new_quantity = max(0, $current_quantity - $quantity);
                    break;
                default:
                    $errors[] = "Invalid action for product ID: $product_id";
                    continue;
            }
            
            $result = SIM_Product::get_instance()->update($product_id, array('quantity' => $new_quantity));
            if ($result) {
                $updated_count++;
                
                // Log the activity
                $this->log_inventory_change($product_id, 'bulk_update', $new_quantity - $current_quantity, "Bulk quantity update: $action $quantity");
            } else {
                $errors[] = "Failed to update product ID: $product_id";
            }
        }
        
        wp_send_json_success(array(
            'updated_count' => $updated_count,
            'errors' => $errors,
            'message' => "Successfully updated $updated_count products"
        ));
    }
    
    /**
     * Import products from CSV
     */
    public function import_products() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload error: ' . $file['error']);
        }
        
        $csv_data = $this->parse_csv_file($file['tmp_name']);
        
        if (empty($csv_data)) {
            wp_send_json_error('No data found in CSV file');
        }
        
        $imported_count = 0;
        $errors = array();
        $headers = array_shift($csv_data); // Remove header row
        
        foreach ($csv_data as $row_index => $row) {
            $row_number = $row_index + 2; // +2 because we removed header and arrays are 0-indexed
            
            if (count($row) < 4) {
                $errors[] = "Row $row_number: Insufficient data";
                continue;
            }
            
            $product_data = array(
                'name' => sanitize_text_field($row[0]),
                'sku' => sanitize_text_field($row[1]),
                'description' => sanitize_textarea_field($row[2]),
                'quantity' => intval($row[3]),
                'price' => floatval($row[4]),
                'supplier_id' => !empty($row[5]) ? intval($row[5]) : 0,
                'category' => sanitize_text_field($row[6]),
                'location' => sanitize_text_field($row[7]),
                'min_stock' => !empty($row[8]) ? intval($row[8]) : 0
            );
            
            $result = SIM_Product::get_instance()->create($product_data);
            if ($result) {
                $imported_count++;
            } else {
                $errors[] = "Row $row_number: Failed to import product";
            }
        }
        
        wp_send_json_success(array(
            'imported_count' => $imported_count,
            'errors' => $errors,
            'message' => "Successfully imported $imported_count products"
        ));
    }
    
    /**
     * Import suppliers from CSV
     */
    public function import_suppliers() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('File upload error: ' . $file['error']);
        }
        
        $csv_data = $this->parse_csv_file($file['tmp_name']);
        
        if (empty($csv_data)) {
            wp_send_json_error('No data found in CSV file');
        }
        
        $imported_count = 0;
        $errors = array();
        $headers = array_shift($csv_data); // Remove header row
        
        foreach ($csv_data as $row_index => $row) {
            $row_number = $row_index + 2; // +2 because we removed header and arrays are 0-indexed
            
            if (count($row) < 3) {
                $errors[] = "Row $row_number: Insufficient data";
                continue;
            }
            
            $supplier_data = array(
                'name' => sanitize_text_field($row[0]),
                'email' => sanitize_email($row[1]),
                'phone' => sanitize_text_field($row[2]),
                'address' => sanitize_textarea_field($row[3]),
                'website' => esc_url_raw($row[4]),
                'notes' => sanitize_textarea_field($row[5])
            );
            
            $result = SIM_Supplier::get_instance()->create($supplier_data);
            if ($result) {
                $imported_count++;
            } else {
                $errors[] = "Row $row_number: Failed to import supplier";
            }
        }
        
        wp_send_json_success(array(
            'imported_count' => $imported_count,
            'errors' => $errors,
            'message' => "Successfully imported $imported_count suppliers"
        ));
    }
    
    /**
     * Export products to CSV
     */
    public function export_products() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $products = SIM_Product::get_instance()->get_all();
        
        $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, array(
            'Name', 'SKU', 'Description', 'Quantity', 'Price', 
            'Supplier ID', 'Category', 'Location', 'Min Stock', 'Created Date'
        ));
        
        // Add data
        foreach ($products as $product) {
            fputcsv($output, array(
                $product->name,
                $product->sku,
                $product->description,
                $product->quantity,
                $product->price,
                $product->supplier_id,
                $product->category,
                $product->location,
                $product->min_stock,
                $product->created_at
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export suppliers to CSV
     */
    public function export_suppliers() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $suppliers = SIM_Supplier::get_instance()->get_all();
        
        $filename = 'suppliers_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, array(
            'Name', 'Email', 'Phone', 'Address', 'Website', 'Notes', 'Created Date'
        ));
        
        // Add data
        foreach ($suppliers as $supplier) {
            fputcsv($output, array(
                $supplier->name,
                $supplier->email,
                $supplier->phone,
                $supplier->address,
                $supplier->website,
                $supplier->notes,
                $supplier->created_at
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export inventory log to CSV
     */
    public function export_inventory_log() {
        check_ajax_referer('sim_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $db = SIM_Database::get_instance();
        $table = $db->get_table('inventory_logs');
        
        $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        
        $filename = 'inventory_log_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, array(
            'Product ID', 'Product Name', 'Action', 'Old Quantity', 
            'New Quantity', 'Notes', 'Date'
        ));
        
        // Add data
        foreach ($logs as $log) {
            $product = SIM_Product::get_instance()->get($log->product_id);
            $product_name = $product ? $product->name : 'Unknown Product';
            
            fputcsv($output, array(
                $log->product_id,
                $product_name,
                $log->change_type,
                $log->old_quantity,
                $log->new_quantity,
                $log->note,
                $log->created_at
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Parse CSV file
     */
    private function parse_csv_file($file_path) {
        $data = array();
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Get CSV template for products
     */
    public static function get_products_csv_template() {
        $filename = 'products_template.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array(
            'Name', 'SKU', 'Description', 'Quantity', 'Price', 
            'Supplier ID', 'Category', 'Location', 'Min Stock'
        ));
        
        // Add example row
        fputcsv($output, array(
            'Sample Product', 'SKU001', 'Sample description', '10', '29.99', 
            '1', 'Electronics', 'Warehouse A', '5'
        ));
        
        fclose($output);
        exit;
    }
    
    /**
     * Get CSV template for suppliers
     */
    public static function get_suppliers_csv_template() {
        $filename = 'suppliers_template.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array(
            'Name', 'Email', 'Phone', 'Address', 'Website', 'Notes'
        ));
        
        // Add example row
        fputcsv($output, array(
            'Sample Supplier', 'supplier@example.com', '+1234567890', 
            '123 Main St, City, State', 'https://example.com', 'Sample notes'
        ));
        
        fclose($output);
        exit;
    }
    
    /**
     * Log inventory change
     */
    private function log_inventory_change($product_id, $change_type, $quantity_change, $note = '') {
        global $wpdb;
        $db = SIM_Database::get_instance();
        $table = $db->get_table('inventory_logs');
        
        $product = SIM_Product::get_instance()->get($product_id);
        $old_quantity = $product ? $product->quantity - $quantity_change : 0;
        $new_quantity = $product ? $product->quantity : 0;
        
        $wpdb->insert($table, array(
            'product_id' => $product_id,
            'change_type' => $change_type,
            'old_quantity' => $old_quantity,
            'new_quantity' => $new_quantity,
            'note' => $note,
            'created_at' => current_time('mysql')
        ));
    }
} 