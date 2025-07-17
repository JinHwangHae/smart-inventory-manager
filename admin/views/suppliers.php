<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$supplier_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$suppliers = SIM_Supplier::get_instance()->get_all();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Suppliers', 'smart-inventory-manager'); ?></h1>
    <a href="?page=smart-inventory-suppliers&action=add" class="page-title-action"><?php _e('Add New Supplier', 'smart-inventory-manager'); ?></a>
    <hr class="wp-header-end">

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Supplier Form -->
        <div class="sim-supplier-form">
            <h2><?php echo $action === 'add' ? __('Add New Supplier', 'smart-inventory-manager') : __('Edit Supplier', 'smart-inventory-manager'); ?></h2>
            
            <?php
            $supplier = null;
            if ($action === 'edit' && $supplier_id) {
                $supplier = SIM_Supplier::get_instance()->get($supplier_id);
            }
            ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="sim-supplier-form">
                <input type="hidden" name="action" value="sim_save_supplier">
                <?php wp_nonce_field('sim_supplier_nonce', 'sim_supplier_nonce'); ?>
                <input type="hidden" name="form_action" value="<?php echo $action; ?>">
                <?php if ($supplier_id): ?>
                    <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="supplier_name"><?php _e('Supplier Name', 'smart-inventory-manager'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="supplier_name" name="supplier_name" class="regular-text" 
                                   value="<?php echo $supplier ? esc_attr($supplier['name']) : ''; ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="supplier_email"><?php _e('Email Address', 'smart-inventory-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="supplier_email" name="supplier_email" class="regular-text" 
                                   value="<?php echo $supplier ? esc_attr($supplier['email']) : ''; ?>">
                            <p class="description"><?php _e('Primary contact email for this supplier', 'smart-inventory-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="supplier_phone"><?php _e('Phone Number', 'smart-inventory-manager'); ?></label>
                        </th>
                        <td>
                            <input type="tel" id="supplier_phone" name="supplier_phone" class="regular-text" 
                                   value="<?php echo $supplier ? esc_attr($supplier['phone']) : ''; ?>">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="supplier_address"><?php _e('Address', 'smart-inventory-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="supplier_address" name="supplier_address" class="large-text" rows="4"><?php echo $supplier ? esc_textarea($supplier['address']) : ''; ?></textarea>
                            <p class="description"><?php _e('Full address including street, city, state, and zip code', 'smart-inventory-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" 
                           value="<?php echo $action === 'add' ? __('Add Supplier', 'smart-inventory-manager') : __('Update Supplier', 'smart-inventory-manager'); ?>">
                    <a href="?page=smart-inventory-suppliers" class="button"><?php _e('Cancel', 'smart-inventory-manager'); ?></a>
                </p>
            </form>
        </div>
        
    <?php else: ?>
        <!-- Suppliers List -->
        <div class="sim-suppliers-list">
            <?php if (empty($suppliers)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No suppliers found. <a href="?page=smart-inventory-suppliers&action=add">Add your first supplier</a>.', 'smart-inventory-manager'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Supplier Name', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Contact Information', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Address', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Products', 'smart-inventory-manager'); ?></th>
                            <th scope="col"><?php _e('Actions', 'smart-inventory-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <?php
                            // Get products for this supplier
                            $products = SIM_Product::get_instance()->get_all();
                            $supplier_products = array_filter($products, function($product) use ($supplier) {
                                return $product['supplier_id'] == $supplier['id'];
                            });
                            $product_count = count($supplier_products);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($supplier['name']); ?></strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="?page=smart-inventory-suppliers&action=edit&id=<?php echo $supplier['id']; ?>">
                                                <?php _e('Edit', 'smart-inventory-manager'); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="#" class="delete-supplier" 
                                               data-supplier-id="<?php echo $supplier['id']; ?>"
                                               data-supplier-name="<?php echo esc_attr($supplier['name']); ?>">
                                                <?php _e('Delete', 'smart-inventory-manager'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($supplier['email']): ?>
                                        <div><strong><?php _e('Email:', 'smart-inventory-manager'); ?></strong> 
                                             <a href="mailto:<?php echo esc_attr($supplier['email']); ?>">
                                                 <?php echo esc_html($supplier['email']); ?>
                                             </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($supplier['phone']): ?>
                                        <div><strong><?php _e('Phone:', 'smart-inventory-manager'); ?></strong> 
                                             <a href="tel:<?php echo esc_attr($supplier['phone']); ?>">
                                                 <?php echo esc_html($supplier['phone']); ?>
                                             </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($supplier['address']): ?>
                                        <div class="supplier-address">
                                            <?php echo nl2br(esc_html($supplier['address'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-address"><?php _e('No address provided', 'smart-inventory-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product_count > 0): ?>
                                        <span class="product-count">
                                            <?php printf(_n('%d product', '%d products', $product_count, 'smart-inventory-manager'), $product_count); ?>
                                        </span>
                                        <button type="button" class="button button-small view-supplier-products" 
                                                data-supplier-id="<?php echo $supplier['id']; ?>"
                                                data-supplier-name="<?php echo esc_attr($supplier['name']); ?>">
                                            <?php _e('View Products', 'smart-inventory-manager'); ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="no-products"><?php _e('No products', 'smart-inventory-manager'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?page=smart-inventory-suppliers&action=edit&id=<?php echo $supplier['id']; ?>" 
                                       class="button button-small"><?php _e('Edit', 'smart-inventory-manager'); ?></a>
                                    <button type="button" class="button button-small button-link-delete delete-supplier" 
                                            data-supplier-id="<?php echo $supplier['id']; ?>"
                                            data-supplier-name="<?php echo esc_attr($supplier['name']); ?>">
                                        <?php _e('Delete', 'smart-inventory-manager'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Supplier Products Modal -->
        <div id="supplier-products-modal" class="sim-modal" style="display: none;">
            <div class="sim-modal-content">
                <span class="sim-modal-close">&times;</span>
                <h3><?php _e('Supplier Products', 'smart-inventory-manager'); ?></h3>
                <div id="supplier-products-content">
                    <!-- Products will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    <?php endif; ?>
</div> 