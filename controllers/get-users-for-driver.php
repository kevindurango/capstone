<?php
require_once '../models/Database.php';
session_start();

// Check for manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$database = new Database();
$conn = $database->connect();

// Get users with Driver role that don't have driver details yet
$query = "SELECT u.user_id, u.username, u.first_name, u.last_name
          FROM users u
          LEFT JOIN driver_details d ON u.user_id = d.user_id
          WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'Driver')
          AND d.user_id IS NULL
          ORDER BY u.username";

$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'users' => $users]);
