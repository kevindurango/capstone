<?php
session_start();
require_once '../models/farmerfield.php';
require_once '../models/log.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Create instances of required models
$farmerFieldModel = new FarmerField();
$logModel = new Log();

// Get the action from the request
$action = isset($_POST['action']) ? $_POST['action'] : 
         (isset($_GET['action']) ? $_GET['action'] : '');

// Route the request to the appropriate handler
switch ($action) {
    case 'list':
        getFieldsList();
        break;
    case 'create':
        createField();
        break;
    case 'update':
        updateField();
        break;
    case 'delete':
        deleteField();
        break;
    case 'get-details':
        getFieldDetails();
        break;
    case 'get-products':
        getFieldProducts();
        break;
    case 'assign-product':
        assignProductToField();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
        break;
}

/**
 * Get list of fields for a farmer
 */
function getFieldsList() {
    global $farmerFieldModel;
    
    // Determine if we're getting fields for the logged-in farmer or for a specific farmer (admin/manager view)
    $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Organization Head');
    $farmerId = (isset($_GET['farmer_id']) && $isAdmin) ? $_GET['farmer_id'] : $_SESSION['user_id'];
    
    $fields = $farmerFieldModel->getFieldsByFarmerId($farmerId);
    
    echo json_encode([
        'success' => true,
        'data' => $fields
    ]);
}

/**
 * Create a new field
 */
function createField() {
    global $farmerFieldModel, $logModel;
    
    // Determine if we're creating for the logged-in farmer or for a specific farmer (admin/manager view)
    $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Organization Head');
    $farmerId = (isset($_POST['farmer_id']) && $isAdmin) ? $_POST['farmer_id'] : $_SESSION['user_id'];
    
    // Validate input
    if (empty($_POST['barangay_id']) || empty($_POST['field_name'])) {
        echo json_encode(['success' => false, 'message' => 'Field name and barangay are required']);
        return;
    }
    
    // Prepare field data
    $fieldData = [
        'farmer_id' => $farmerId,
        'barangay_id' => $_POST['barangay_id'],
        'field_name' => $_POST['field_name'],
        'field_size' => $_POST['field_size'] ?? null,
        'field_type' => $_POST['field_type'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'coordinates' => $_POST['coordinates'] ?? null
    ];
    
    // Create the field
    $fieldId = $farmerFieldModel->createField($fieldData);
    
    if ($fieldId) {
        // Log the action
        $logMessage = "Created new farm field '{$_POST['field_name']}' in barangay ID: {$_POST['barangay_id']}";
        $logModel->logActivity($_SESSION['user_id'], $logMessage);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Field created successfully',
            'field_id' => $fieldId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create field']);
    }
}

/**
 * Update an existing field
 */
function updateField() {
    global $farmerFieldModel, $logModel;
    
    // Check for required fields
    if (empty($_POST['field_id']) || empty($_POST['barangay_id']) || empty($_POST['field_name'])) {
        echo json_encode(['success' => false, 'message' => 'Field ID, name and barangay are required']);
        return;
    }
    
    // Determine if we're updating for the logged-in farmer or for a specific farmer (admin/manager view)
    $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Organization Head');
    $farmerId = (isset($_POST['farmer_id']) && $isAdmin) ? $_POST['farmer_id'] : $_SESSION['user_id'];
    
    // Prepare field data
    $fieldData = [
        'field_id' => $_POST['field_id'],
        'farmer_id' => $farmerId,
        'barangay_id' => $_POST['barangay_id'],
        'field_name' => $_POST['field_name'],
        'field_size' => $_POST['field_size'] ?? null,
        'field_type' => $_POST['field_type'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'coordinates' => $_POST['coordinates'] ?? null
    ];
    
    // Update the field
    $success = $farmerFieldModel->updateField($fieldData);
    
    if ($success) {
        // Log the action
        $logMessage = "Updated farm field '{$_POST['field_name']}' (ID: {$_POST['field_id']})";
        $logModel->logActivity($_SESSION['user_id'], $logMessage);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Field updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update field']);
    }
}

/**
 * Delete a field
 */
function deleteField() {
    global $farmerFieldModel, $logModel;
    
    // Check for required fields
    if (empty($_POST['field_id'])) {
        echo json_encode(['success' => false, 'message' => 'Field ID is required']);
        return;
    }
    
    // Determine if we're deleting for the logged-in farmer or for a specific farmer (admin/manager view)
    $isAdmin = isset($_SESSION['role']) && ($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Organization Head');
    $farmerId = (isset($_POST['farmer_id']) && $isAdmin) ? $_POST['farmer_id'] : $_SESSION['user_id'];
    
    // Get field details for logging
    $field = $farmerFieldModel->getFieldById($_POST['field_id']);
    
    // Delete the field
    $success = $farmerFieldModel->deleteField($_POST['field_id'], $farmerId);
    
    if ($success) {
        // Log the action
        $logMessage = "Deleted farm field '{$field['field_name']}' (ID: {$_POST['field_id']})";
        $logModel->logActivity($_SESSION['user_id'], $logMessage);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Field deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete field']);
    }
}

/**
 * Get details for a specific field
 */
function getFieldDetails() {
    global $farmerFieldModel;
    
    if (empty($_GET['field_id'])) {
        echo json_encode(['success' => false, 'message' => 'Field ID is required']);
        return;
    }
    
    $fieldId = $_GET['field_id'];
    $field = $farmerFieldModel->getFieldById($fieldId);
    
    if ($field) {
        echo json_encode([
            'success' => true,
            'data' => $field
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Field not found']);
    }
}

/**
 * Get products planted in a specific field
 */
function getFieldProducts() {
    global $farmerFieldModel;
    
    if (empty($_GET['field_id'])) {
        echo json_encode(['success' => false, 'message' => 'Field ID is required']);
        return;
    }
    
    $fieldId = $_GET['field_id'];
    $products = $farmerFieldModel->getProductsByFieldId($fieldId);
    
    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
}

/**
 * Assign a product to a specific field
 */
function assignProductToField() {
    global $farmerFieldModel, $logModel;
    
    // Check for required fields
    if (empty($_POST['barangay_product_id']) || !isset($_POST['field_id'])) {
        echo json_encode(['success' => false, 'message' => 'Product ID and Field ID are required']);
        return;
    }
    
    $barangayProductId = $_POST['barangay_product_id'];
    $fieldId = $_POST['field_id'] ?: null; // Can be null to unassign
    
    // Assign product to field
    $success = $farmerFieldModel->assignProductToField($barangayProductId, $fieldId);
    
    if ($success) {
        // Log the action
        $action = $fieldId ? "Assigned" : "Unassigned";
        $logMessage = "{$action} product (ID: {$barangayProductId}) to field (ID: " . ($fieldId ?: 'none') . ")";
        $logModel->logActivity($_SESSION['user_id'], $logMessage);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product assignment updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product assignment']);
    }
}
?>