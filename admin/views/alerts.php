<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current alerts and settings
$current_alerts = SIM_Alerts::get_instance()->check_low_stock();
$alert_email = get_option('sim_alert_email', get_option('admin_email'));
$low_stock_threshold = get_option('sim_low_stock_threshold', 10);
$auto_reorder_enabled = get_option('sim_auto_reorder_enabled', false);

// Get alert history (last 50 alerts)
global $wpdb;
$db = SIM_Database::get_instance();
$alert_history = $wpdb->get_results("
    SELECT il.*, p.name as product_name, p.sku, p.low_stock_threshold
    FROM {$db->get_table('inventory_logs')} il
    LEFT JOIN {$db->get_table('products')} p ON il.product_id = p.id
    WHERE il.change_type IN ('subtract', 'set') 
    AND il.quantity_change < 0
    ORDER BY il.created_at DESC 
    LIMIT 50
", ARRAY_A);

// Filter alerts that triggered low stock
$triggered_alerts = array();
foreach ($alert_history as $alert) {
    if ($alert['quantity_change'] < 0) {
        // Get the product's current state after this change
        $product = SIM_Product::get_instance()->get($alert['product_id']);
        if ($product && $product['quantity'] <= $product['low_stock_threshold']) {
            $triggered_alerts[] = $alert;
        }
    }
}
?>

<div class="wrap">
    <h1><?php _e('Alert Management', 'smart-inventory-manager'); ?></h1>
    
    <!-- Alert Statistics -->
    <div class="sim-alert-stats">
        <div class="sim-stat-card">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo count($current_alerts); ?></h3>
                <p><?php _e('Active Alerts', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-stat-card">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo count($triggered_alerts); ?></h3>
                <p><?php _e('Alerts Sent', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-stat-card">
            <div class="sim-stat-icon">
                <span class="dashicons dashicons-admin-settings"></span>
            </div>
            <div class="sim-stat-content">
                <h3><?php echo $low_stock_threshold; ?></h3>
                <p><?php _e('Default Threshold', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
    </div>

    <!-- Alert Settings -->
    <div class="sim-dashboard-section">
        <h2><?php _e('Alert Settings', 'smart-inventory-manager'); ?></h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="sim-alert-settings-form">
            <input type="hidden" name="action" value="sim_save_alert_settings">
            <?php wp_nonce_field('sim_alert_settings_nonce', 'sim_alert_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="alert_email"><?php _e('Alert Email Address', 'smart-inventory-manager'); ?></label>
                    </th>
                    <td>
                        <input type="email" id="alert_email" name="alert_email" class="regular-text" 
                               value="<?php echo esc_attr($alert_email); ?>" required>
                        <p class="description"><?php _e('Email address where low stock alerts will be sent', 'smart-inventory-manager'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="low_stock_threshold"><?php _e('Default Low Stock Threshold', 'smart-inventory-manager'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="small-text" 
                               value="<?php echo intval($low_stock_threshold); ?>" min="0" required>
                        <p class="description"><?php _e('Default threshold for low stock alerts (can be overridden per product)', 'smart-inventory-manager'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="auto_reorder_enabled"><?php _e('Auto Reorder Suggestions', 'smart-inventory-manager'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="auto_reorder_enabled" name="auto_reorder_enabled" value="1" 
                                   <?php checked($auto_reorder_enabled, true); ?>>
                            <?php _e('Enable automatic reorder suggestions in alerts', 'smart-inventory-manager'); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, alerts will include suggested reorder quantities', 'smart-inventory-manager'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="alert_frequency"><?php _e('Alert Frequency', 'smart-inventory-manager'); ?></label>
                    </th>
                    <td>
                        <select id="alert_frequency" name="alert_frequency">
                            <option value="immediate" <?php selected(get_option('sim_alert_frequency', 'immediate'), 'immediate'); ?>>
                                <?php _e('Immediate', 'smart-inventory-manager'); ?>
                            </option>
                            <option value="daily" <?php selected(get_option('sim_alert_frequency', 'immediate'), 'daily'); ?>>
                                <?php _e('Daily Summary', 'smart-inventory-manager'); ?>
                            </option>
                            <option value="weekly" <?php selected(get_option('sim_alert_frequency', 'immediate'), 'weekly'); ?>>
                                <?php _e('Weekly Summary', 'smart-inventory-manager'); ?>
                            </option>
                        </select>
                        <p class="description"><?php _e('How often to send alert emails', 'smart-inventory-manager'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" 
                       value="<?php _e('Save Alert Settings', 'smart-inventory-manager'); ?>">
                <button type="button" class="button" id="test-alert"><?php _e('Send Test Alert', 'smart-inventory-manager'); ?></button>
            </p>
        </form>
    </div>

    <!-- Current Alerts -->
    <?php if (!empty($current_alerts)): ?>
    <div class="sim-dashboard-section">
        <h2><?php _e('Current Low Stock Alerts', 'smart-inventory-manager'); ?></h2>
        <div class="sim-alerts-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('SKU', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Current Stock', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Threshold', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Supplier', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Status', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Actions', 'smart-inventory-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_alerts as $alert): ?>
                        <?php
                        $supplier_name = '';
                        if ($alert['supplier_id']) {
                            $supplier = SIM_Supplier::get_instance()->get($alert['supplier_id']);
                            $supplier_name = $supplier ? $supplier['name'] : '';
                        }
                        
                        $status_class = $alert['quantity'] <= 0 ? 'out-of-stock' : 'low-stock';
                        $status_text = $alert['quantity'] <= 0 ? __('Out of Stock', 'smart-inventory-manager') : __('Low Stock', 'smart-inventory-manager');
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($alert['name']); ?></strong></td>
                            <td><?php echo esc_html($alert['sku']); ?></td>
                            <td>
                                <span class="stock-status <?php echo $status_class; ?>">
                                    <?php echo intval($alert['quantity']); ?>
                                </span>
                            </td>
                            <td><?php echo intval($alert['low_stock_threshold']); ?></td>
                            <td><?php echo esc_html($supplier_name); ?></td>
                            <td>
                                <span class="alert-status <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <a href="?page=smart-inventory-products&action=edit&id=<?php echo $alert['id']; ?>" 
                                   class="button button-small"><?php _e('Edit', 'smart-inventory-manager'); ?></a>
                                <button type="button" class="button button-small adjust-quantity" 
                                        data-product-id="<?php echo $alert['id']; ?>"
                                        data-current-quantity="<?php echo $alert['quantity']; ?>">
                                    <?php _e('Adjust Stock', 'smart-inventory-manager'); ?>
                                </button>
                                <button type="button" class="button button-small send-alert" 
                                        data-product-id="<?php echo $alert['id']; ?>"
                                        data-product-name="<?php echo esc_attr($alert['name']); ?>">
                                    <?php _e('Send Alert', 'smart-inventory-manager'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="sim-dashboard-section">
        <div class="notice notice-success">
            <p><?php _e('No active low stock alerts. All products are above their threshold levels.', 'smart-inventory-manager'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert History -->
    <?php if (!empty($triggered_alerts)): ?>
    <div class="sim-dashboard-section">
        <h2><?php _e('Alert History', 'smart-inventory-manager'); ?></h2>
        <div class="sim-alert-history">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Date', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Stock Change', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Triggered Threshold', 'smart-inventory-manager'); ?></th>
                        <th><?php _e('Note', 'smart-inventory-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($triggered_alerts, 0, 20) as $alert): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($alert['product_name']); ?></strong>
                                <br><small><?php echo esc_html($alert['sku']); ?></small>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($alert['created_at'])); ?></td>
                            <td>
                                <span class="quantity-change negative">
                                    <?php echo $alert['quantity_change']; ?>
                                </span>
                            </td>
                            <td><?php echo intval($alert['low_stock_threshold']); ?></td>
                            <td><?php echo esc_html($alert['note']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quantity Adjustment Modal -->
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
</div> 