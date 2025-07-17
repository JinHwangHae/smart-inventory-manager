<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$products = SIM_Product::get_instance()->get_all();
$suppliers = SIM_Supplier::get_instance()->get_all();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Products', 'smart-inventory-manager'); ?></h1>
    <a href="?page=smart-inventory-products&action=add" class="page-title-action"><?php _e('Add New Product', 'smart-inventory-manager'); ?></a>
    <hr class="wp-header-end">

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Product Form -->
        <div class="sim-product-form">
            <h2><?php echo $action === 'add' ? __('Add New Product', 'smart-inventory-manager') : __('Edit Product', 'smart-inventory-manager'); ?></h2>
            
            <?php
            $product = null;
            if ($action === 'edit' && $product_id) {
                $product = SIM_Product::get_instance()->get($product_id);
            }
            ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="sim-product-form">
                <input type="hidden" name="action" value="sim_save_product">
                <?php wp_nonce_field('sim_product_nonce', 'sim_product_nonce'); ?>
                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                <?php if ($product_id): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_name"><?php _e('Product Name', 'smart-inventory-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="product_name" name="product_name" class="regular-text" 
                                   value="<?php echo $product ? esc_attr($product['name']) : ''; ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="product_sku"><?php _e('SKU', 'smart-inventory-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="product_sku" name="product_sku" class="regular-text" 
                                   value="<?php echo $product ? esc_attr($product['sku']) : ''; ?>" required>
                            <p class="description"><?php _e('Stock Keeping Unit - unique identifier for the product', 'smart-inventory-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="supplier_id"><?php _e('Supplier', 'smart-inventory-manager'); ?></label>
                        </th>
                        <td>
                            <select id="supplier_id" name="supplier_id">
                                <option value=""><?php _e('-- Select Supplier --', 'smart-inventory-manager'); ?></option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['id']; ?>" 
                                            <?php echo ($product && $product['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($supplier['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="quantity"><?php _e('Current Quantity', 'smart-inventory-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="quantity" name="quantity" class="small-text" min="0" 
                                   value="<?php echo $product ? intval($product['quantity']) : 0; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="low_stock_threshold"><?php _e('Low Stock Threshold', 'smart-inventory-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="small-text" min="0" 
                                   value="<?php echo $product ? intval($product['low_stock_threshold']) : 10; ?>">
                            <p class="description"><?php _e('Alert will be triggered when stock falls below this number', 'smart-inventory-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" 
                           value="<?php echo $action === 'add' ? __('Add Product', 'smart-inventory-manager') : __('Update Product', 'smart-inventory-manager'); ?>">
                    <a href="?page=smart-inventory-products" class="button"><?php _e('Cancel', 'smart-inventory-manager'); ?></a>
                </p>
            </form>
        </div>
        
    <?php else: ?>
        <!-- Products List -->
        <div class="sim-products-list">
            <?php if (empty($products)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No products found. <a href="?page=smart-inventory-products&action=add">Add your first product</a>.', 'smart-inventory-manager'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Product Name', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('SKU', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Supplier', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Quantity', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Status', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Actions', 'smart-inventory-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
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
                                <td><?php echo esc_html($supplier_name); ?></td>
                                <td>
                                    <span class="quantity-display"><?php echo intval($product['quantity']); ?></span>
                                    <button type="button" class="button button-small adjust-quantity" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-current-quantity="<?php echo $product['quantity']; ?>">
                                        <?php _e('Adjust', 'smart-inventory-manager'); ?>
                                    </button>
                                </td>
                                <td>
                                    <span class="stock-status <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?page=smart-inventory-products&action=edit&id=<?php echo $product['id']; ?>" 
                                       class="button button-small"><?php _e('Edit', 'smart-inventory-manager'); ?></a>
                                    <button type="button" class="button button-small button-link-delete delete-product" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo esc_attr($product['name']); ?>">
                                        <?php _e('Delete', 'smart-inventory-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
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
    <?php endif; ?>
</div> 