<?php
// File: C:\xampp\htdocs\capstone\models\Dashboard.php
require_once 'Database.php';

class Dashboard {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // Get count of users
    public function getUserCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }

    // Get count of products
    public function getProductCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM products");
        return $stmt->fetchColumn();
    }

    // Get count of orders by status
    public function getOrderCountByStatus($status) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE order_status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchColumn();
    }

    // Get count of shipping info by status
    public function getPickupCountByStatus($status) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM shippinginfo WHERE shipping_status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchColumn();
    }
}
?>
