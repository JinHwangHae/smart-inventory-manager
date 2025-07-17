<?php
/**
 * Plugin Name: Smart Inventory Manager (Debug Version)
 * Plugin URI: https://yourwebsite.com/smart-inventory-manager
 * Description: Advanced inventory management system with real-time tracking, low stock alerts, and supplier management.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: smart-inventory-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SIM_PLUGIN_VERSION', '1.0.0');
define('SIM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SIM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Main plugin class
class SmartInventoryManagerDebug {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain for internationalization
        load_plugin_textdomain('smart-inventory-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Smart Inventory Debug',
            'Smart Inventory Debug',
            'manage_options',
            'smart-inventory-debug',
            array($this, 'admin_dashboard'),
            'dashicons-cart',
            30
        );
    }
    
    public function admin_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>Smart Inventory Manager Debug</h1>';
        echo '<p>Plugin is working! This is a debug version.</p>';
        echo '<p>Plugin Path: ' . SIM_PLUGIN_PATH . '</p>';
        echo '<p>Plugin URL: ' . SIM_PLUGIN_URL . '</p>';
        echo '</div>';
    }
    
    public function activate() {
        // Simple activation - just add an option
        add_option('sim_debug_activated', 'yes');
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        delete_option('sim_debug_activated');
        flush_rewrite_rules();
    }
}

// Initialize the plugin
SmartInventoryManagerDebug::get_instance(); 