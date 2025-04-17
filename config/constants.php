<?php
/**
 * Application-wide Constants
 * 
 * This file defines constants that are used throughout the application.
 * Using constants improves maintainability and reduces duplication.
 * 
 * @version 1.0
 */

// ============================
// Path Constants
// ============================

// Base directory (adjust this to your actual path)
define('BASE_DIR', dirname(dirname(__FILE__))); // Points to the project root

// Public directory (web-accessible files)
define('PUBLIC_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'public');

// Upload directories
define('UPLOAD_BASE_DIR', PUBLIC_DIR);
define('UPLOADS_DIR', PUBLIC_DIR . DIRECTORY_SEPARATOR . 'uploads');
define('PRODUCT_IMAGES_DIR', UPLOADS_DIR . DIRECTORY_SEPARATOR . 'products');

// Relative paths (for URLs)
define('UPLOADS_URL', 'uploads');
define('PRODUCT_IMAGES_URL', UPLOADS_URL . '/products');

// ============================
// File Upload Constants
// ============================

// Maximum file size in bytes
define('MAX_FILE_SIZE', 5242880); // 5MB

// Allowed file types
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
]);

// ============================
// Product Constants
// ============================

// Product status types
define('PRODUCT_STATUS_PENDING', 'pending');
define('PRODUCT_STATUS_APPROVED', 'approved');
define('PRODUCT_STATUS_REJECTED', 'rejected');

// Valid product unit types
define('PRODUCT_UNIT_TYPES', [
    'piece',
    'kilogram',
    'gram',
    'liter',
    'milliliter',
    'bunch',
    'sack',
    'pack'
]);

// Stock threshold for low stock warnings
define('LOW_STOCK_THRESHOLD', 10);

// ============================
// Database Constants
// ============================

// Table names
define('TABLE_PRODUCTS', 'products');
define('TABLE_PRODUCT_CATEGORIES', 'productcategories');
define('TABLE_PRODUCT_CATEGORY_MAPPING', 'productcategorymapping');
define('TABLE_USERS', 'users');
define('TABLE_ROLES', 'roles');
define('TABLE_ORDERS', 'orders');
define('TABLE_ACTIVITY_LOGS', 'activitylogs');

// ============================
// Debug Constants
// ============================

// Debug mode (set to false in production)
define('DEBUG_MODE', true);

// Log file path
define('LOG_FILE', BASE_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log');

/**
 * Custom error handler to log errors
 * Only used when DEBUG_MODE is true
 */
if (DEBUG_MODE) {
    // Make sure logs directory exists
    $logsDir = dirname(LOG_FILE);
    if (!file_exists($logsDir)) {
        mkdir($logsDir, 0777, true);
    }
    
    // Set custom error handler
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        $message = date('[Y-m-d H:i:s]') . " Error: [$errno] $errstr in $errfile on line $errline\n";
        error_log($message, 3, LOG_FILE);
        return DEBUG_MODE ? false : true; // Show errors in debug mode only
    });
}