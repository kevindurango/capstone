<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once('../config/database.php');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'notifications' => []
];

// Get database connection
$conn = getConnection();

// Check connection
if (!$conn) {
    $response['message'] = 'Database connection failed: ' . mysqli_connect_error();
    echo json_encode($response);
    exit;
}

// Process based on request type
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Check for user_id parameter
        if (!isset($_GET['user_id'])) {
            $response['message'] = 'Missing required parameter: user_id';
            echo json_encode($response);
            exit;
        }

        $user_id = intval($_GET['user_id']);
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10; // Default to 10 notifications
        
        // Get notifications for the user
        $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'notification_id' => (int)$row['notification_id'],
                'user_id' => (int)$row['user_id'],
                'message' => $row['message'],
                'is_read' => (bool)$row['is_read'],
                'created_at' => $row['created_at'],
                'type' => $row['type'],
                'reference_id' => $row['reference_id'] ? (int)$row['reference_id'] : null
            ];
        }
        
        $response['success'] = true;
        $response['message'] = count($notifications) > 0 ? 
            'Notifications retrieved successfully' : 'No notifications found';
        $response['notifications'] = $notifications;
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Mark notification as read
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['notification_id'])) {
            $response['message'] = 'Missing required parameter: notification_id';
            echo json_encode($response);
            exit;
        }
        
        $notification_id = intval($data['notification_id']);
        
        // Update notification status
        $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $notification_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Notification marked as read';
        } else {
            $response['message'] = 'Failed to update notification status';
        }
    } else {
        $response['message'] = 'Method not allowed';
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Return JSON response
echo json_encode($response);