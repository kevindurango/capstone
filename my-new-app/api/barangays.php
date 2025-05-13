<?php
// Error handling - prevent PHP notices from breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include database connection
require_once('config/database.php');

// Default response
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'barangays' => []
];

try {
    // Get database connection
    $conn = getConnection();

    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    // Process GET request to retrieve barangay data
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Query to get all barangays
        $query = "SELECT barangay_id, barangay_name, municipality, province FROM barangays";
        
        // Add optional WHERE clause if needed
        if (isset($_GET['municipality'])) {
            $municipality = $_GET['municipality'];
            $query .= " WHERE municipality = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $municipality);
        } else {
            $stmt = $conn->prepare($query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $barangays = [];
            while ($row = $result->fetch_assoc()) {
                // Include both name formats to ensure compatibility with all components
                $barangays[] = [
                    'barangay_id' => (int)$row['barangay_id'],
                    'barangay_name' => $row['barangay_name'], // Use consistent naming
                    'name' => $row['barangay_name'], // Keep this for backward compatibility
                    'municipality' => $row['municipality'],
                    'province' => $row['province']
                ];
            }
            
            $response['success'] = true;
            $response['message'] = 'Barangay data retrieved successfully';
            $response['barangays'] = $barangays;
        } else {
            $response['message'] = 'No barangay data found';
        }
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error retrieving barangay data: ' . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Return the JSON response
echo json_encode($response);
exit;
?>