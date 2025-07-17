<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$products = SIM_Product::get_instance()->get_all();
$suppliers = SIM_Supplier::get_instance()->get_all();
$alerts = SIM_Alerts::get_instance()->check_low_stock();

$total_products = count($products);
$total_suppliers = count($suppliers);
$low_stock_count = count($alerts);
$out_of_stock_count = 0;
$total_quantity = 0;

foreach ($products as $product) {
    $total_quantity += intval($product['quantity']);
    if ($product['quantity'] <= 0) {
        $out_of_stock_count++;
    }
}

// Get recent inventory changes (last 10)
global $wpdb;
$db = SIM_Database::get_instance();
$recent_changes = $wpdb->get_results("
    SELECT il.*, p.name as product_name, p.sku 
    FROM {$db->get_table('inventory_logs')} il
    LEFT JOIN {$db->get_table('products')} p ON il.product_id = p.id
    ORDER BY il.created_at DESC 
    LIMIT 10
", ARRAY_A);

// Get top suppliers by product count
$supplier_stats = array();
foreach ($suppliers as $supplier) {
    $supplier_products = array_filter($products, function($product) use ($supplier) {
        return $product['supplier_id'] == $supplier['id'];
    });
    $supplier_stats[] = array(
        'name' => $supplier['name'],
        'product_count' => count($supplier_products),
        'total_quantity' => array_sum(array_column($supplier_products, 'quantity'))
    );
}
usort($supplier_stats, function($a, $b) {
    return $b['product_count'] - $a['product_count'];
});
$top_suppliers = array_slice($supplier_stats, 0, 5);
?>

<div class="wrap">
    <h1><?php _e('Smart Inventory Dashboard', 'smart-inventory-manager'); ?></h1>
    
    <!-- Statistics Cards -->
    <div class="sim-dashboard-stats">
        <div class="sim-stat-card">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo number_format($total_products); ?></h3>
                <p><?php _e('Total Products', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-stat-card">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-businessman"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo number_format($total_suppliers); ?></h3>
                <p><?php _e('Suppliers', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-stat-card">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo number_format($total_quantity); ?></h3>
                <p><?php _e('Total Stock', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-stat-card <?php echo $low_stock_count > 0 ? 'alert' : ''; ?>">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo number_format($low_stock_count); ?></h3>
                <p><?php _e('Low Stock Alerts', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-stat-card <?php echo $out_of_stock_count > 0 ? 'critical' : ''; ?>">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo number_format($out_of_stock_count); ?></h3>
                <p><?php _e('Out of Stock', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="sim-quick-actions">
        <h2><?php _e('Quick Actions', 'smart-inventory-manager'); ?></h2>
        <div class="sim-action-buttons">
            <a href="?page=smart-inventory-products&action=add" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add Product', 'smart-inventory-manager'); ?>
            </a>
            <a href="?page=smart-inventory-suppliers&action=add" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add Supplier', 'smart-inventory-manager'); ?>
            </a>
            <a href="?page=smart-inventory-products" class="button">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('View Products', 'smart-inventory-manager'); ?>
            </a>
            <a href="?page=smart-inventory-suppliers" class="button">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('View Suppliers', 'smart-inventory-manager'); ?>
            </a>
            <a href="?page=smart-inventory-alerts" class="button">
                <span class="dashicons dashicons-bell"></span>
                <?php _e('View Alerts', 'smart-inventory-manager'); ?>
            </a>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="sim-dashboard-content">
        <!-- Low Stock Alerts -->
        <?php if (!empty($alerts)): ?>
        <div class="sim-dashboard-section">
            <h2><?php _e('Low Stock Alerts', 'smart-inventory-manager'); ?></h2>
            <div class="sim-alerts-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('SKU', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Current Stock', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Threshold', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Supplier', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Actions', 'smart-inventory-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($alerts, 0, 5) as $alert): ?>
                            <?php
                            $supplier_name = '';
                            if ($alert['supplier_id']) {
                                $supplier = SIM_Supplier::get_instance()->get($alert['supplier_id']);
                                $supplier_name = $supplier ? $supplier['name'] : '';
                            }
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($alert['name']); ?></strong></td>
                                <td><?php echo esc_html($alert['sku']); ?></td>
                                <td>
                                    <span class="stock-status low-stock"><?php echo intval($alert['quantity']); ?></span>
                                </td>
                                <td><?php echo intval($alert['low_stock_threshold']); ?></td>
                                <td><?php echo esc_html($supplier_name); ?></td>
                                <td>
                                    <a href="?page=smart-inventory-products&action=edit&id=<?php echo $alert['id']; ?>" 
                                       class="button button-small"><?php _e('Edit', 'smart-inventory-manager'); ?></a>
                                    <button type="button" class="button button-small adjust-quantity" 
                                            data-product-id="<?php echo $alert['id']; ?>"
                                            data-current-quantity="<?php echo $alert['quantity']; ?>">
                                        <?php _e('Adjust Stock', 'smart-inventory-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($alerts) > 5): ?>
                    <p class="sim-view-more">
                        <a href="?page=smart-inventory-alerts"><?php _e('View all alerts', 'smart-inventory-manager'); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <?php if (!empty($recent_changes)): ?>
        <div class="sim-dashboard-section">
            <h2><?php _e('Recent Activity', 'smart-inventory-manager'); ?></h2>
            <div class="sim-activity-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Action', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Quantity Change', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Note', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Date', 'smart-inventory-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_changes as $change): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($change['product_name']); ?></strong>
                                    <br><small><?php echo esc_html($change['sku']); ?></small>
                                </td>
                                <td>
                                    <span class="activity-type <?php echo $change['change_type']; ?>">
                                        <?php echo ucfirst($change['change_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="quantity-change <?php echo $change['quantity_change'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo ($change['quantity_change'] >= 0 ? '+' : '') . $change['quantity_change']; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($change['note']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($change['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Suppliers -->
        <?php if (!empty($top_suppliers)): ?>
        <div class="sim-dashboard-section">
            <h2><?php _e('Top Suppliers', 'smart-inventory-manager'); ?></h2>
            <div class="sim-suppliers-overview">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Supplier', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Products', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Total Stock', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Actions', 'smart-inventory-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_suppliers as $supplier): ?>
                            <tr>
                                <td><strong><?php echo esc_html($supplier['name']); ?></strong></td>
                                <td><?php echo number_format($supplier['product_count']); ?></td>
                                <td><?php echo number_format($supplier['total_quantity']); ?></td>
                                <td>
                                    <button type="button" class="button button-small view-supplier-products" 
                                            data-supplier-id="<?php echo array_search($supplier, $supplier_stats); ?>"
                                            data-supplier-name="<?php echo esc_attr($supplier['name']); ?>">
                                        <?php _e('View Products', 'smart-inventory-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quantity Adjustment Modal (reused from products) -->
    <div id="quantity-modal" class="sim-modal" style="display: none;">
        <div class="sim-modal-content">
            <span class="sim-modal-close">&times;</span>
            <h3><?php _e('Adjust Quantity', 'smart-inventory-manager'); ?></h3>
            <form id="quantity-form">
                <input type="hidden" id="modal-product-id" name="product_id">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Current Quantity', 'smart-inventory-manager'); ?></th>
                        <td><span id="modal-current-quantity"></span></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Adjustment Type', 'smart-inventory-manager'); ?></th>
                        <td>
                            <select id="adjustment-type" name="adjustment_type">
                                <option value="add"><?php _e('Add Stock', 'smart-inventory-manager'); ?></option>
                                <option value="subtract"><?php _e('Remove Stock', 'smart-inventory-manager'); ?></option>
                                <option value="set"><?php _e('Set to Specific Amount', 'smart-inventory-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Amount', 'smart-inventory-manager'); ?></th>
                        <td><input type="number" id="adjustment-amount" name="amount" class="small-text" min="0" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Note', 'smart-inventory-manager'); ?></th>
                        <td><textarea id="adjustment-note" name="note" class="regular-text" rows="3"></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="<?php _e('Update Quantity', 'smart-inventory-manager'); ?>">
                    <button type="button" class="button sim-modal-cancel"><?php _e('Cancel', 'smart-inventory-manager'); ?></button>
                </p>
            </form>
        </div>
    </div>

    <!-- Supplier Products Modal (reused from suppliers) -->
    <div id="supplier-products-modal" class="sim-modal" style="display: none;">
        <div class="sim-modal-content">
            <span class="sim-modal-close">&times;</span>
            <h3><?php _e('Supplier Products', 'smart-inventory-manager'); ?></h3>
            <div id="supplier-products-content">
                <!-- Products will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div> 