<?php
require_once 'models/Log.php';

$logClass = new Log();
$userId = 1; // Replace with a valid user ID from your database
$action = "Test logging activity";
$context = ['key' => 'value']; // Optional contextual information

if ($logClass->logActivity($userId, $action, $context)) {
    echo "Activity logged successfully.";
} else {
    echo "Failed to log activity.";
}
?>