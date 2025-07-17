jQuery(document).ready(function($) {
    'use strict';

    // Product Management
    var SIM_Admin = {
        init: function() {
            this.bindEvents();
            this.initModals();
        },

        bindEvents: function() {
            // Delete product
            $(document).on('click', '.delete-product', this.deleteProduct);
            
            // Adjust quantity
            $(document).on('click', '.adjust-quantity', this.showQuantityModal);
            
            // Quantity form submission
            $(document).on('submit', '#quantity-form', this.adjustQuantity);
            
            // Delete supplier
            $(document).on('click', '.delete-supplier', this.deleteSupplier);
            
            // View supplier products
            $(document).on('click', '.view-supplier-products', this.showSupplierProducts);
            
            // Alert system
            $(document).on('click', '#test-alert', this.sendTestAlert);
            $(document).on('click', '.send-alert', this.sendManualAlert);
            
            // Reports and exports
            $(document).on('click', '#export-inventory-csv', this.exportInventoryCSV);
            $(document).on('click', '#export-activity-csv', this.exportActivityCSV);
            $(document).on('click', '#export-suppliers-csv', this.exportSuppliersCSV);
            $(document).on('click', '#print-report', this.printReport);
            
            // Modal close
            $(document).on('click', '.sim-modal-close, .sim-modal-cancel', this.closeModal);
            
            // Close modal when clicking outside
            $(document).on('click', '.sim-modal', function(e) {
                if (e.target === this) {
                    SIM_Admin.closeModal();
                }
            });
        },

        initModals: function() {
            // Create modal overlay if it doesn't exist
            if ($('.sim-modal-overlay').length === 0) {
                $('body').append('<div class="sim-modal-overlay"></div>');
            }
        },

        deleteProduct: function(e) {
            e.preventDefault();
            
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            
            if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
                $.ajax({
                    url: sim_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sim_delete_product',
                        product_id: productId,
                        nonce: sim_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the product.');
                    }
                });
            }
        },

        showQuantityModal: function(e) {
            e.preventDefault();
            
            var productId = $(this).data('product-id');
            var currentQuantity = $(this).data('current-quantity');
            
            $('#modal-product-id').val(productId);
            $('#modal-current-quantity').text(currentQuantity);
            $('#adjustment-amount').val('');
            $('#adjustment-note').val('');
            
            $('#quantity-modal').show();
            $('.sim-modal-overlay').show();
        },

        adjustQuantity: function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            formData += '&action=sim_adjust_quantity&nonce=' + sim_ajax.nonce;
            
            $.ajax({
                url: sim_ajax.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Update the quantity display
                        var productId = $('#modal-product-id').val();
                        var newQuantity = response.data.new_quantity;
                        
                        $('button[data-product-id="' + productId + '"]')
                            .siblings('.quantity-display')
                            .text(newQuantity);
                        
                        // Update the data attribute
                        $('button[data-product-id="' + productId + '"]')
                            .attr('data-current-quantity', newQuantity);
                        
                        // Update status if needed
                        SIM_Admin.updateProductStatus(productId, newQuantity);
                        
                        SIM_Admin.closeModal();
                        alert('Quantity updated successfully!');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while updating the quantity.');
                }
            });
        },

        updateProductStatus: function(productId, quantity) {
            // This is a simplified version - you might want to get the threshold from the server
            var row = $('button[data-product-id="' + productId + '"]').closest('tr');
            var statusCell = row.find('.stock-status');
            
            if (quantity <= 0) {
                statusCell.removeClass('in-stock low-stock').addClass('out-of-stock').text('Out of Stock');
            } else if (quantity <= 10) { // Default threshold
                statusCell.removeClass('in-stock out-of-stock').addClass('low-stock').text('Low Stock');
            } else {
                statusCell.removeClass('low-stock out-of-stock').addClass('in-stock').text('In Stock');
            }
        },

        closeModal: function() {
            $('.sim-modal').hide();
            $('.sim-modal-overlay').hide();
        },

        deleteSupplier: function(e) {
            e.preventDefault();
            
            var supplierId = $(this).data('supplier-id');
            var supplierName = $(this).data('supplier-name');
            
            if (confirm('Are you sure you want to delete "' + supplierName + '"? This action cannot be undone.')) {
                $.ajax({
                    url: sim_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sim_delete_supplier',
                        supplier_id: supplierId,
                        nonce: sim_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the supplier.');
                    }
                });
            }
        },

        showSupplierProducts: function(e) {
            e.preventDefault();
            
            var supplierId = $(this).data('supplier-id');
            var supplierName = $(this).data('supplier-name');
            
            // Update modal title
            $('#supplier-products-modal h3').text('Products for ' + supplierName);
            
            // Show loading
            $('#supplier-products-content').html('<p>Loading products...</p>');
            $('#supplier-products-modal').show();
            $('.sim-modal-overlay').show();
            
            $.ajax({
                url: sim_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sim_get_supplier_products',
                    supplier_id: supplierId,
                    nonce: sim_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#supplier-products-content').html(response.data);
                    } else {
                        $('#supplier-products-content').html('<p>Error: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    $('#supplier-products-content').html('<p>An error occurred while loading products.</p>');
                }
            });
        },

        sendTestAlert: function(e) {
            e.preventDefault();
            
            if (confirm('Send a test alert email to verify your alert settings?')) {
                $.ajax({
                    url: sim_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sim_send_test_alert',
                        nonce: sim_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Test alert sent successfully! Check your email.');
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred while sending the test alert.');
                    }
                });
            }
        },

        sendManualAlert: function(e) {
            e.preventDefault();
            
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            
            if (confirm('Send a manual alert for "' + productName + '"?')) {
                $.ajax({
                    url: sim_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sim_send_manual_alert',
                        product_id: productId,
                        nonce: sim_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Alert sent successfully!');
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('An error occurred while sending the alert.');
                    }
                });
            }
        },

        exportInventoryCSV: function(e) {
            e.preventDefault();
            this.downloadCSV('sim_export_inventory_csv', 'inventory-report');
        },

        exportActivityCSV: function(e) {
            e.preventDefault();
            this.downloadCSV('sim_export_activity_csv', 'activity-report');
        },

        exportSuppliersCSV: function(e) {
            e.preventDefault();
            this.downloadCSV('sim_export_suppliers_csv', 'suppliers-report');
        },

        downloadCSV: function(action, filename) {
            var form = $('<form>', {
                'method': 'POST',
                'action': sim_ajax.ajax_url,
                'target': '_blank'
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': action
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'nonce',
                'value': sim_ajax.nonce
            }));

            $('body').append(form);
            form.submit();
            form.remove();
        },

        printReport: function(e) {
            e.preventDefault();
            window.print();
        }
    };

    // Initialize admin functionality
    SIM_Admin.init();
    
    // Bulk Operations functionality
    if (typeof window.openImportModal !== 'undefined') {
        // Import modal functionality is already defined in the view
        console.log('Bulk operations functionality loaded');
    }
}); 