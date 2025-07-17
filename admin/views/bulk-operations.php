<?php
/**
 * Bulk Operations Page
 * 
 * @package Smart_Inventory_Manager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle CSV template downloads
if (isset($_GET['download_template']) && wp_verify_nonce($_GET['_wpnonce'], 'sim_download_template')) {
    $template_type = sanitize_text_field($_GET['download_template']);
    
    if ($template_type === 'products') {
        SIM_Bulk_Operations::get_products_csv_template();
    } elseif ($template_type === 'suppliers') {
        SIM_Bulk_Operations::get_suppliers_csv_template();
    }
}

$products = SIM_Product::get_instance()->get_all();
$suppliers = SIM_Supplier::get_instance()->get_all();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Bulk Operations</h1>
    <hr class="wp-header-end">
    
    <div class="sim-bulk-operations">
        <!-- Import/Export Section -->
        <div class="sim-section">
            <h2>Import & Export</h2>
            
            <div class="sim-grid">
                <!-- Import Products -->
                <div class="sim-card">
                    <h3>Import Products</h3>
                    <p>Import products from a CSV file. Download the template first to see the required format.</p>
                    
                    <div class="sim-import-export-actions">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sim-bulk-operations&download_template=products'), 'sim_download_template'); ?>" 
                           class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            Download Template
                        </a>
                        
                        <button type="button" class="button button-primary" onclick="openImportModal('products')">
                            <span class="dashicons dashicons-upload"></span>
                            Import Products
                        </button>
                    </div>
                </div>
                
                <!-- Import Suppliers -->
                <div class="sim-card">
                    <h3>Import Suppliers</h3>
                    <p>Import suppliers from a CSV file. Download the template first to see the required format.</p>
                    
                    <div class="sim-import-export-actions">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sim-bulk-operations&download_template=suppliers'), 'sim_download_template'); ?>" 
                           class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            Download Template
                        </a>
                        
                        <button type="button" class="button button-primary" onclick="openImportModal('suppliers')">
                            <span class="dashicons dashicons-upload"></span>
                            Import Suppliers
                        </button>
                    </div>
                </div>
                
                <!-- Export Products -->
                <div class="sim-card">
                    <h3>Export Products</h3>
                    <p>Export all products to a CSV file for backup or external processing.</p>
                    
                    <button type="button" class="button button-primary" onclick="exportData('products')">
                        <span class="dashicons dashicons-download"></span>
                        Export Products (<?php echo count($products); ?>)
                    </button>
                </div>
                
                <!-- Export Suppliers -->
                <div class="sim-card">
                    <h3>Export Suppliers</h3>
                    <p>Export all suppliers to a CSV file for backup or external processing.</p>
                    
                    <button type="button" class="button button-primary" onclick="exportData('suppliers')">
                        <span class="dashicons dashicons-download"></span>
                        Export Suppliers (<?php echo count($suppliers); ?>)
                    </button>
                </div>
                
                <!-- Export Inventory Log -->
                <div class="sim-card">
                    <h3>Export Inventory Log</h3>
                    <p>Export the complete inventory activity log for analysis or backup.</p>
                    
                    <button type="button" class="button button-primary" onclick="exportData('inventory_log')">
                        <span class="dashicons dashicons-download"></span>
                        Export Inventory Log
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Bulk Actions Section -->
        <div class="sim-section">
            <h2>Bulk Actions</h2>
            
            <div class="sim-grid">
                <!-- Bulk Product Operations -->
                <div class="sim-card">
                    <h3>Bulk Product Operations</h3>
                    <p>Perform bulk operations on selected products.</p>
                    
                    <div class="sim-bulk-actions">
                        <button type="button" class="button button-secondary" onclick="openBulkDeleteModal('products')">
                            <span class="dashicons dashicons-trash"></span>
                            Bulk Delete
                        </button>
                        
                        <button type="button" class="button button-primary" onclick="openBulkQuantityModal()">
                            <span class="dashicons dashicons-update"></span>
                            Bulk Update Quantities
                        </button>
                    </div>
                </div>
                
                <!-- Bulk Supplier Operations -->
                <div class="sim-card">
                    <h3>Bulk Supplier Operations</h3>
                    <p>Perform bulk operations on selected suppliers.</p>
                    
                    <div class="sim-bulk-actions">
                        <button type="button" class="button button-secondary" onclick="openBulkDeleteModal('suppliers')">
                            <span class="dashicons dashicons-trash"></span>
                            Bulk Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Selection for Bulk Operations -->
        <div class="sim-section">
            <h2>Product Selection</h2>
            <p>Select products for bulk operations:</p>
            
            <div class="sim-product-selection">
                <div class="sim-selection-controls">
                    <label>
                        <input type="checkbox" id="select-all-products" onchange="toggleAllProducts(this.checked)">
                        Select All Products
                    </label>
                    <span class="sim-selected-count">0 products selected</span>
                </div>
                
                <div class="sim-products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="sim-product-item" data-product-id="<?php echo $product->id; ?>">
                            <label class="sim-product-checkbox">
                                <input type="checkbox" class="product-checkbox" value="<?php echo $product->id; ?>" onchange="updateSelectedCount()">
                                <div class="sim-product-info">
                                    <strong><?php echo esc_html($product->name); ?></strong>
                                    <small>SKU: <?php echo esc_html($product->sku); ?></small>
                                    <small>Stock: <?php echo $product->quantity; ?></small>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Supplier Selection for Bulk Operations -->
        <div class="sim-section">
            <h2>Supplier Selection</h2>
            <p>Select suppliers for bulk operations:</p>
            
            <div class="sim-supplier-selection">
                <div class="sim-selection-controls">
                    <label>
                        <input type="checkbox" id="select-all-suppliers" onchange="toggleAllSuppliers(this.checked)">
                        Select All Suppliers
                    </label>
                    <span class="sim-selected-count">0 suppliers selected</span>
                </div>
                
                <div class="sim-suppliers-grid">
                    <?php foreach ($suppliers as $supplier): ?>
                        <div class="sim-supplier-item" data-supplier-id="<?php echo $supplier->id; ?>">
                            <label class="sim-supplier-checkbox">
                                <input type="checkbox" class="supplier-checkbox" value="<?php echo $supplier->id; ?>" onchange="updateSelectedCount()">
                                <div class="sim-supplier-info">
                                    <strong><?php echo esc_html($supplier->name); ?></strong>
                                    <small><?php echo esc_html($supplier->email); ?></small>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="sim-modal">
    <div class="sim-modal-content">
        <div class="sim-modal-header">
            <h3 id="import-modal-title">Import Data</h3>
            <span class="sim-modal-close" onclick="closeImportModal()">&times;</span>
        </div>
        
        <div class="sim-modal-body">
            <form id="import-form" enctype="multipart/form-data">
                <div class="sim-form-group">
                    <label for="csv-file">Select CSV File:</label>
                    <input type="file" id="csv-file" name="csv_file" accept=".csv" required>
                </div>
                
                <div class="sim-form-group">
                    <label>
                        <input type="checkbox" id="skip-header" checked>
                        Skip first row (header)
                    </label>
                </div>
                
                <div class="sim-import-preview" id="import-preview" style="display: none;">
                    <h4>File Preview:</h4>
                    <div id="preview-content"></div>
                </div>
            </form>
        </div>
        
        <div class="sim-modal-footer">
            <button type="button" class="button button-secondary" onclick="closeImportModal()">Cancel</button>
            <button type="button" class="button button-primary" onclick="importData()">Import</button>
        </div>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div id="bulk-delete-modal" class="sim-modal">
    <div class="sim-modal-content">
        <div class="sim-modal-header">
            <h3 id="bulk-delete-title">Confirm Bulk Delete</h3>
            <span class="sim-modal-close" onclick="closeBulkDeleteModal()">&times;</span>
        </div>
        
        <div class="sim-modal-body">
            <div class="sim-warning">
                <span class="dashicons dashicons-warning"></span>
                <p>This action cannot be undone. Are you sure you want to delete the selected items?</p>
            </div>
            
            <div id="bulk-delete-summary"></div>
        </div>
        
        <div class="sim-modal-footer">
            <button type="button" class="button button-secondary" onclick="closeBulkDeleteModal()">Cancel</button>
            <button type="button" class="button button-danger" onclick="confirmBulkDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- Bulk Quantity Update Modal -->
<div id="bulk-quantity-modal" class="sim-modal">
    <div class="sim-modal-content">
        <div class="sim-modal-header">
            <h3>Bulk Update Quantities</h3>
            <span class="sim-modal-close" onclick="closeBulkQuantityModal()">&times;</span>
        </div>
        
        <div class="sim-modal-body">
            <div class="sim-form-group">
                <label for="quantity-action">Action:</label>
                <select id="quantity-action">
                    <option value="set">Set to specific quantity</option>
                    <option value="add">Add to current quantity</option>
                    <option value="subtract">Subtract from current quantity</option>
                </select>
            </div>
            
            <div class="sim-form-group">
                <label for="quantity-value">Quantity:</label>
                <input type="number" id="quantity-value" min="0" value="0">
            </div>
            
            <div class="sim-form-group">
                <label for="quantity-notes">Notes (optional):</label>
                <textarea id="quantity-notes" placeholder="Reason for bulk update"></textarea>
            </div>
            
            <div id="bulk-quantity-summary"></div>
        </div>
        
        <div class="sim-modal-footer">
            <button type="button" class="button button-secondary" onclick="closeBulkQuantityModal()">Cancel</button>
            <button type="button" class="button button-primary" onclick="confirmBulkQuantityUpdate()">Update</button>
        </div>
    </div>
</div>

<script>
let currentImportType = '';
let currentDeleteType = '';

function openImportModal(type) {
    currentImportType = type;
    const title = type === 'products' ? 'Import Products' : 'Import Suppliers';
    document.getElementById('import-modal-title').textContent = title;
    document.getElementById('import-modal').style.display = 'block';
    
    // Reset form
    document.getElementById('import-form').reset();
    document.getElementById('import-preview').style.display = 'none';
}

function closeImportModal() {
    document.getElementById('import-modal').style.display = 'none';
}

function openBulkDeleteModal(type) {
    currentDeleteType = type;
    const title = type === 'products' ? 'Delete Selected Products' : 'Delete Selected Suppliers';
    document.getElementById('bulk-delete-title').textContent = title;
    
    const selectedCount = type === 'products' ? 
        document.querySelectorAll('.product-checkbox:checked').length :
        document.querySelectorAll('.supplier-checkbox:checked').length;
    
    document.getElementById('bulk-delete-summary').innerHTML = 
        `<p>You are about to delete <strong>${selectedCount}</strong> ${type}.</p>`;
    
    document.getElementById('bulk-delete-modal').style.display = 'block';
}

function closeBulkDeleteModal() {
    document.getElementById('bulk-delete-modal').style.display = 'none';
}

function openBulkQuantityModal() {
    const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
    if (selectedProducts.length === 0) {
        alert('Please select at least one product.');
        return;
    }
    
    document.getElementById('bulk-quantity-summary').innerHTML = 
        `<p>You are about to update <strong>${selectedProducts.length}</strong> products.</p>`;
    
    document.getElementById('bulk-quantity-modal').style.display = 'block';
}

function closeBulkQuantityModal() {
    document.getElementById('bulk-quantity-modal').style.display = 'none';
}

function toggleAllProducts(checked) {
    document.querySelectorAll('.product-checkbox').forEach(checkbox => {
        checkbox.checked = checked;
    });
    updateSelectedCount();
}

function toggleAllSuppliers(checked) {
    document.querySelectorAll('.supplier-checkbox').forEach(checkbox => {
        checkbox.checked = checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const productCount = document.querySelectorAll('.product-checkbox:checked').length;
    const supplierCount = document.querySelectorAll('.supplier-checkbox:checked').length;
    
    document.querySelectorAll('.sim-selected-count').forEach(span => {
        const parent = span.closest('.sim-product-selection, .sim-supplier-selection');
        if (parent.classList.contains('sim-product-selection')) {
            span.textContent = `${productCount} products selected`;
        } else {
            span.textContent = `${supplierCount} suppliers selected`;
        }
    });
}

function importData() {
    const formData = new FormData();
    const fileInput = document.getElementById('csv-file');
    
    if (!fileInput.files[0]) {
        alert('Please select a file.');
        return;
    }
    
    formData.append('action', currentImportType === 'products' ? 'sim_import_products' : 'sim_import_suppliers');
    formData.append('nonce', sim_ajax.nonce);
    formData.append('csv_file', fileInput.files[0]);
    
    // Show loading
    const importBtn = document.querySelector('#import-modal .button-primary');
    const originalText = importBtn.textContent;
    importBtn.textContent = 'Importing...';
    importBtn.disabled = true;
    
    fetch(sim_ajax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            closeImportModal();
            location.reload(); // Refresh to show new data
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during import.');
    })
    .finally(() => {
        importBtn.textContent = originalText;
        importBtn.disabled = false;
    });
}

function exportData(type) {
    const formData = new FormData();
    formData.append('action', 'sim_export_' + type);
    formData.append('nonce', sim_ajax.nonce);
    
    // Create a temporary form to trigger download
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = sim_ajax.ajax_url;
    form.target = '_blank';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'sim_export_' + type;
    
    const nonceInput = document.createElement('input');
    nonceInput.type = 'hidden';
    nonceInput.name = 'nonce';
    nonceInput.value = sim_ajax.nonce;
    
    form.appendChild(actionInput);
    form.appendChild(nonceInput);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function confirmBulkDelete() {
    const ids = currentDeleteType === 'products' ? 
        Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value) :
        Array.from(document.querySelectorAll('.supplier-checkbox:checked')).map(cb => cb.value);
    
    if (ids.length === 0) {
        alert('Please select at least one item.');
        return;
    }
    
    const action = currentDeleteType === 'products' ? 'sim_bulk_delete_products' : 'sim_bulk_delete_suppliers';
    const data = {
        action: action,
        nonce: sim_ajax.nonce,
        [currentDeleteType + '_ids']: ids
    };
    
    // Show loading
    const deleteBtn = document.querySelector('#bulk-delete-modal .button-danger');
    const originalText = deleteBtn.textContent;
    deleteBtn.textContent = 'Deleting...';
    deleteBtn.disabled = true;
    
    fetch(sim_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            closeBulkDeleteModal();
            location.reload(); // Refresh to show updated data
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during deletion.');
    })
    .finally(() => {
        deleteBtn.textContent = originalText;
        deleteBtn.disabled = false;
    });
}

function confirmBulkQuantityUpdate() {
    const selectedProducts = document.querySelectorAll('.product-checkbox:checked');
    if (selectedProducts.length === 0) {
        alert('Please select at least one product.');
        return;
    }
    
    const action = document.getElementById('quantity-action').value;
    const quantity = parseInt(document.getElementById('quantity-value').value);
    const notes = document.getElementById('quantity-notes').value;
    
    if (isNaN(quantity) || quantity < 0) {
        alert('Please enter a valid quantity.');
        return;
    }
    
    const updates = Array.from(selectedProducts).map(checkbox => ({
        product_id: checkbox.value,
        quantity: quantity,
        action: action
    }));
    
    const data = {
        action: 'sim_bulk_update_quantities',
        nonce: sim_ajax.nonce,
        updates: updates
    };
    
    // Show loading
    const updateBtn = document.querySelector('#bulk-quantity-modal .button-primary');
    const originalText = updateBtn.textContent;
    updateBtn.textContent = 'Updating...';
    updateBtn.disabled = true;
    
    fetch(sim_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            closeBulkQuantityModal();
            location.reload(); // Refresh to show updated data
        } else {
            alert('Error: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during update.');
    })
    .finally(() => {
        updateBtn.textContent = originalText;
        updateBtn.disabled = false;
    });
}

// File preview functionality
document.getElementById('csv-file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const content = e.target.result;
            const lines = content.split('\n').slice(0, 5); // Show first 5 lines
            
            document.getElementById('preview-content').innerHTML = 
                '<pre>' + lines.join('\n') + '</pre>';
            document.getElementById('import-preview').style.display = 'block';
        };
        reader.readAsText(file);
    }
});

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.sim-modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}
</script> 