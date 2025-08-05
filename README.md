# Smart Inventory Manager(progress)

A comprehensive WordPress plugin for advanced inventory management with real-time tracking, low stock alerts, supplier management, and detailed reporting.

## 🚀 Features

### Core Inventory Management
- **Product Management**: Add, edit, and delete products with SKU, quantity, and low stock thresholds
- **Real-time Stock Tracking**: Monitor inventory levels with automatic updates
- **Bulk Operations**: Perform mass updates on multiple products simultaneously
- **Inventory History**: Track all stock changes with detailed logs

### Smart Alerts System
- **Low Stock Alerts**: Automatic email notifications when products fall below threshold
- **Customizable Thresholds**: Set individual low stock levels per product
- **Alert Frequency Control**: Configure immediate or scheduled alert delivery
- **Test Alerts**: Send test notifications to verify email settings
- **Bulk Alert Management**: Handle multiple low stock items efficiently

### Supplier Management
- **Supplier Database**: Maintain comprehensive supplier information
- **Product-Supplier Linking**: Associate products with their suppliers
- **Supplier Analytics**: Track supplier performance and product distribution
- **Contact Management**: Store supplier contact details and communication history

### Advanced Reporting
- **Dashboard Analytics**: Real-time overview of inventory status
- **Stock Reports**: Detailed inventory reports with filtering options
- **Activity Logs**: Complete audit trail of all inventory changes
- **Export Functionality**: Export data to CSV format for external analysis
- **Supplier Reports**: Analyze supplier performance and product distribution

### User Interface
- **Modern Admin Interface**: Clean, responsive WordPress admin design
- **Quick Actions**: Fast access to common inventory tasks
- **Search and Filter**: Easy product and supplier discovery
- **Mobile-Friendly**: Responsive design for mobile devices

### API Integration
- **REST API**: Access inventory data programmatically
- **AJAX Support**: Dynamic updates without page refreshes
- **Webhook Support**: Integrate with external systems

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Browser**: Modern browsers with JavaScript enabled

## 🛠️ Installation

### Method 1: Manual Installation
1. Download the plugin files
2. Upload the `smart-inventory-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to 'Smart Inventory' in the admin menu to configure

### Method 2: WordPress Admin
1. Go to Plugins > Add New in your WordPress admin
2. Click 'Upload Plugin' and select the plugin zip file
3. Click 'Install Now' and then 'Activate Plugin'

## ⚙️ Configuration

### Initial Setup
1. **Dashboard**: Review the main dashboard for inventory overview
2. **Products**: Add your first products with SKU, quantity, and thresholds
3. **Suppliers**: Create supplier records and link them to products
4. **Alerts**: Configure email settings and alert preferences
5. **Settings**: Customize plugin behavior and appearance

### Alert Configuration
- Set default low stock threshold
- Configure alert email address
- Choose alert frequency (immediate/daily/weekly)
- Enable/disable auto-reorder suggestions
- Test alert system

### Export Settings
- Configure CSV export options
- Set date formats for reports
- Choose export fields and order

## 📖 Usage

### Managing Products
1. Navigate to **Smart Inventory > Products**
2. Click **Add Product** to create new items
3. Fill in product details:
   - Product name and description
   - SKU (Stock Keeping Unit)
   - Current quantity
   - Low stock threshold
   - Supplier (optional)
   - Cost and pricing information
4. Save and monitor stock levels

### Managing Suppliers
1. Go to **Smart Inventory > Suppliers**
2. Add supplier information:
   - Company name and contact details
   - Email and phone numbers
   - Address and website
   - Notes and special requirements
3. Link suppliers to products for better organization

### Monitoring Alerts
1. Check **Smart Inventory > Alerts** for low stock notifications
2. Review alert history and settings
3. Send manual alerts when needed
4. Configure automatic alert delivery

### Generating Reports
1. Visit **Smart Inventory > Reports**
2. Choose report type (inventory, activity, suppliers)
3. Set date ranges and filters
4. Export data to CSV for external analysis

### Bulk Operations
1. Access **Smart Inventory > Bulk Operations**
2. Select products for mass updates
3. Choose operation type (update quantities, change thresholds, etc.)
4. Apply changes to multiple items simultaneously

## 🔧 Technical Details

### Database Structure
The plugin creates several custom tables:
- `wp_sim_products`: Product information and stock levels
- `wp_sim_suppliers`: Supplier details and contact information
- `wp_sim_inventory_logs`: Complete audit trail of stock changes
- `wp_sim_alert_logs`: Alert history and delivery tracking

### File Structure
```
smart-inventory-manager/
├── admin/                 # Admin interface files
│   └── views/            # Admin page templates
├── assets/               # CSS, JS, and media files
│   ├── css/             # Stylesheets
│   └── js/              # JavaScript files
├── includes/             # Core plugin classes
├── languages/            # Translation files
├── smart-inventory-manager.php  # Main plugin file
└── README.md            # This file
```

### Hooks and Filters
The plugin provides various WordPress hooks for customization:
- `sim_inventory_changed`: Triggered when stock levels change
- `sim_alert_sent`: Fired when alerts are delivered
- `sim_product_created`: Called when new products are added
- `sim_supplier_updated`: Triggered on supplier modifications

### API Endpoints
- `GET /wp-json/sim/v1/products`: Retrieve all products
- `GET /wp-json/sim/v1/products/{id}`: Get specific product
- `POST /wp-json/sim/v1/products`: Create new product
- `PUT /wp-json/sim/v1/products/{id}`: Update product
- `DELETE /wp-json/sim/v1/products/{id}`: Delete product

## 🚨 Troubleshooting

### Common Issues

**Alerts not sending:**
- Check email configuration in WordPress
- Verify alert email address is correct
- Test alert system from admin panel

**Products not saving:**
- Ensure database tables are created properly
- Check file permissions on upload directory
- Verify PHP memory limits are sufficient

**Slow performance:**
- Optimize database queries for large inventories
- Consider pagination for large product lists
- Review server resources and PHP settings

### Debug Mode
Enable debug mode by adding to `wp-config.php`:
```php
define('SIM_DEBUG', true);
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup
1. Clone the repository
2. Install dependencies (if any)
3. Set up a local WordPress development environment
4. Activate the plugin in debug mode
5. Make your changes and test thoroughly

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: Check the plugin's help sections
- **WordPress.org**: Visit our plugin page for community support
- **GitHub Issues**: Report bugs and request features
- **Email Support**: Contact us directly for premium support

## 🔄 Changelog

### Version 1.0.0
- Initial release
- Core inventory management features
- Alert system implementation
- Supplier management
- Basic reporting and export functionality
- WordPress admin interface
- API endpoints for external integration

## 🙏 Acknowledgments

- WordPress community for the excellent platform
- Contributors and beta testers
- Open source libraries and tools used in development

---

**Smart Inventory Manager** - Making inventory management simple and efficient for WordPress users worldwide.
