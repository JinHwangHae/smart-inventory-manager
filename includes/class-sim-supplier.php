<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SIM_Supplier {
    private static $instance = null;
    private $table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $db = SIM_Database::get_instance();
        $this->table = $db->get_table('suppliers');
    }

    public function get_all($args = array()) {
        global $wpdb;
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);
    }

    public function create($data) {
        global $wpdb;
        $wpdb->insert($this->table, $data);
        return $wpdb->insert_id;
    }

    public function update($id, $data) {
        global $wpdb;
        return $wpdb->update($this->table, $data, array('id' => $id));
    }

    public function delete($id) {
        global $wpdb;
        return $wpdb->delete($this->table, array('id' => $id));
    }
} 