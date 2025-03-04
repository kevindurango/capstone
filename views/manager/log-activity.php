<?php
session_start();
require_once '../../models/Log.php';

header('Content-Type: application/json');

// Verify that the request is from an authenticated manager
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logClass = new Log();
    
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $activity = filter_input(INPUT_POST, 'activity', FILTER_SANITIZE_STRING);
    
    if ($user_id && $activity) {
        $result = $logClass->logActivity($user_id, $activity);
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>