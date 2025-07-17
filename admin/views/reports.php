<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get data for reports
$products = SIM_Product::get_instance()->get_all();
$suppliers = SIM_Supplier::get_instance()->get_all();
$alerts = SIM_Alerts::get_instance()->check_low_stock();

// Calculate statistics
$total_products = count($products);
$total_suppliers = count($suppliers);
$total_quantity = array_sum(array_column($products, 'quantity'));
$low_stock_count = count($alerts);
$out_of_stock_count = count(array_filter($products, function($p) { return $p['quantity'] <= 0; }));

// Get inventory value (assuming average value per unit)
$total_value = $total_quantity * 10; // Placeholder calculation

// Get recent activity (last 30 days)
global $wpdb;
$db = SIM_Database::get_instance();
$recent_activity = $wpdb->get_results("
    SELECT il.*, p.name as product_name, p.sku
    FROM {$db->get_table('inventory_logs')} il
    LEFT JOIN {$db->get_table('products')} p ON il.product_id = p.id
    WHERE il.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY il.created_at DESC
", ARRAY_A);

// Prepare chart data
$stock_levels = array();
$supplier_distribution = array();
$activity_by_type = array();

foreach ($products as $product) {
    // Stock levels
    if ($product['quantity'] <= 0) {
        $stock_levels['Out of Stock'] = ($stock_levels['Out of Stock'] ?? 0) + 1;
    } elseif ($product['quantity'] <= $product['low_stock_threshold']) {
        $stock_levels['Low Stock'] = ($stock_levels['Low Stock'] ?? 0) + 1;
    } else {
        $stock_levels['In Stock'] = ($stock_levels['In Stock'] ?? 0) + 1;
    }
    
    // Supplier distribution
    if ($product['supplier_id']) {
        $supplier = SIM_Supplier::get_instance()->get($product['supplier_id']);
        if ($supplier) {
            $supplier_name = $supplier['name'];
            $supplier_distribution[$supplier_name] = ($supplier_distribution[$supplier_name] ?? 0) + 1;
        }
    }
}

foreach ($recent_activity as $activity) {
    $type = $activity['change_type'];
    $activity_by_type[$type] = ($activity_by_type[$type] ?? 0) + 1;
}

// Get top products by quantity
$top_products = array_slice($products, 0, 10);
usort($top_products, function($a, $b) {
    return $b['quantity'] - $a['quantity'];
});

// Get products with most activity
$product_activity = array();
foreach ($recent_activity as $activity) {
    $product_name = $activity['product_name'];
    $product_activity[$product_name] = ($product_activity[$product_name] ?? 0) + 1;
}
arsort($product_activity);
$most_active_products = array_slice($product_activity, 0, 10, true);
?>

<div class="wrap">
    <h1><?php _e('Reports & Analytics', 'smart-inventory-manager'); ?></h1>
    
    <!-- Report Filters -->
    <div class="sim-report-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="smart-inventory-reports">
            <select name="period" onchange="this.form.submit()">
                <option value="30" <?php selected(isset($_GET['period']) ? $_GET['period'] : '30', '30'); ?>>
                    <?php _e('Last 30 Days', 'smart-inventory-manager'); ?>
                </option>
                <option value="90" <?php selected(isset($_GET['period']) ? $_GET['period'] : '30', '90'); ?>>
                    <?php _e('Last 90 Days', 'smart-inventory-manager'); ?>
                </option>
                <option value="365" <?php selected(isset($_GET['period']) ? $_GET['period'] : '30', '365'); ?>>
                    <?php _e('Last Year', 'smart-inventory-manager'); ?>
                </option>
            </select>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="sim-metrics-grid">
        <div class="sim-metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="metric-content">
                <h3><?php echo number_format($total_products); ?></h3>
                <p><?php _e('Total Products', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="metric-content">
                <h3><?php echo number_format($total_quantity); ?></h3>
                <p><?php _e('Total Stock', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="metric-content">
                <h3>$<?php echo number_format($total_value); ?></h3>
                <p><?php _e('Estimated Value', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
        
        <div class="sim-metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="metric-content">
                <h3><?php echo number_format($low_stock_count); ?></h3>
                <p><?php _e('Low Stock Items', 'smart-inventory-manager'); ?></p>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="sim-charts-section">
        <div class="sim-chart-container">
            <h2><?php _e('Stock Levels Distribution', 'smart-inventory-manager'); ?></h2>
            <canvas id="stockLevelsChart" width="400" height="200"></canvas>
        </div>
        
        <div class="sim-chart-container">
            <h2><?php _e('Supplier Distribution', 'smart-inventory-manager'); ?></h2>
            <canvas id="supplierChart" width="400" height="200"></canvas>
        </div>
        
        <div class="sim-chart-container">
            <h2><?php _e('Activity by Type', 'smart-inventory-manager'); ?></h2>
            <canvas id="activityChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="sim-reports-tables">
        <!-- Top Products by Stock -->
        <div class="sim-dashboard-section">
            <h2><?php _e('Top Products by Stock Level', 'smart-inventory-manager'); ?></h2>
            <div class="sim-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('SKU', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Current Stock', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Threshold', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Status', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Supplier', 'smart-inventory-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                            <?php
                            $supplier_name = '';
                            if ($product['supplier_id']) {
                                $supplier = SIM_Supplier::get_instance()->get($product['supplier_id']);
                                $supplier_name = $supplier ? $supplier['name'] : '';
                            }
                            
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
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($product['name']); ?></strong></td>
                                <td><?php echo esc_html($product['sku']); ?></td>
                                <td><?php echo number_format($product['quantity']); ?></td>
                                <td><?php echo number_format($product['low_stock_threshold']); ?></td>
                                <td>
                                    <span class="stock-status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($supplier_name); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Most Active Products -->
        <div class="sim-dashboard-section">
            <h2><?php _e('Most Active Products (Last 30 Days)', 'smart-inventory-manager'); ?></h2>
            <div class="sim-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Product', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Activity Count', 'smart-inventory-manager'); ?></th>
                            <th><?php _e('Last Activity', 'smart-inventory-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($most_active_products as $product_name => $activity_count): ?>
                            <?php
                            // Find the most recent activity for this product
                            $last_activity = null;
                            foreach ($recent_activity as $activity) {
                                if ($activity['product_name'] === $product_name) {
                                    $last_activity = $activity;
                                    break;
                                }
                            }
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($product_name); ?></strong></td>
                                <td><?php echo number_format($activity_count); ?></td>
                                <td>
                                    <?php if ($last_activity): ?>
                                        <?php echo date('M j, Y g:i A', strtotime($last_activity['created_at'])); ?>
                                    <?php else: ?>
                                        <?php _e('N/A', 'smart-inventory-manager'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="sim-dashboard-section">
            <h2><?php _e('Recent Inventory Activity', 'smart-inventory-manager'); ?></h2>
            <div class="sim-table-container">
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
                        <?php foreach (array_slice($recent_activity, 0, 20) as $activity): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($activity['product_name']); ?></strong>
                                    <br><small><?php echo esc_html($activity['sku']); ?></small>
                                </td>
                                <td>
                                    <span class="activity-type <?php echo $activity['change_type']; ?>">
                                        <?php echo ucfirst($activity['change_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="quantity-change <?php echo $activity['quantity_change'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo ($activity['quantity_change'] >= 0 ? '+' : '') . $activity['quantity_change']; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($activity['note']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="sim-export-section">
        <h2><?php _e('Export Reports', 'smart-inventory-manager'); ?></h2>
        <div class="sim-export-buttons">
            <button type="button" class="button button-primary" id="export-inventory-csv">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Inventory CSV', 'smart-inventory-manager'); ?>
            </button>
            <button type="button" class="button button-primary" id="export-activity-csv">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Activity CSV', 'smart-inventory-manager'); ?>
            </button>
            <button type="button" class="button button-primary" id="export-suppliers-csv">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Suppliers CSV', 'smart-inventory-manager'); ?>
            </button>
            <button type="button" class="button" id="print-report">
                <span class="dashicons dashicons-printer"></span>
                <?php _e('Print Report', 'smart-inventory-manager'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart data
var stockLevelsData = <?php echo json_encode($stock_levels); ?>;
var supplierData = <?php echo json_encode($supplier_distribution); ?>;
var activityData = <?php echo json_encode($activity_by_type); ?>;

// Initialize charts when page loads
jQuery(document).ready(function($) {
    // Stock Levels Chart
    var stockCtx = document.getElementById('stockLevelsChart').getContext('2d');
    new Chart(stockCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(stockLevelsData),
            datasets: [{
                data: Object.values(stockLevelsData),
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Supplier Distribution Chart
    var supplierCtx = document.getElementById('supplierChart').getContext('2d');
    new Chart(supplierCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(supplierData),
            datasets: [{
                label: 'Products per Supplier',
                data: Object.values(supplierData),
                backgroundColor: '#0073aa',
                borderColor: '#005a87',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Activity Chart
    var activityCtx = document.getElementById('activityChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'pie',
        data: {
            labels: Object.keys(activityData),
            datasets: [{
                data: Object.values(activityData),
                backgroundColor: ['#28a745', '#dc3545', '#17a2b8'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script> 