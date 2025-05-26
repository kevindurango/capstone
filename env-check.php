<?php
// Set error reporting for diagnostics
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Environment Check for Farmers Market Platform</h1>";

// Check PHP Version
echo "<h2>PHP Version</h2>";
echo "<p>Current PHP version: " . phpversion() . "</p>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<p style='color: red;'>Warning: PHP version 7.4 or higher is recommended.</p>";
} else {
    echo "<p style='color: green;'>PHP version is adequate.</p>";
}

// Check required extensions
echo "<h2>PHP Extensions</h2>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'gd', 'mbstring', 'xml', 'curl', 'zip'];
echo "<ul>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color: green;'>$ext: Loaded</li>";
    } else {
        echo "<li style='color: red;'>$ext: Not loaded</li>";
    }
}
echo "</ul>";

// Check directory permissions
echo "<h2>Directory Permissions</h2>";
$directories = [
    'public/uploads' => 'For file uploads',
    'cache' => 'For caching data',
    'logs' => 'For error logs'
];

echo "<ul>";
foreach ($directories as $dir => $purpose) {
    $fullPath = __DIR__ . '/' . $dir;
    if (file_exists($fullPath)) {
        if (is_writable($fullPath)) {
            echo "<li style='color: green;'>$dir: Writable ($purpose)</li>";
        } else {
            echo "<li style='color: red;'>$dir: Not writable ($purpose)</li>";
        }
    } else {
        echo "<li style='color: orange;'>$dir: Directory does not exist ($purpose)</li>";
    }
}
echo "</ul>";

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once 'models/Database.php';
    $database = new Database();
    $conn = $database->connect();
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Check if tables exist
    $tables = ['users', 'products', 'orders', 'activitylogs'];
    echo "<h3>Core Tables</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $query = "SELECT 1 FROM $table LIMIT 1";
        try {
            $stmt = $conn->query($query);
            echo "<li style='color: green;'>$table: Table exists</li>";
        } catch (PDOException $e) {
            echo "<li style='color: red;'>$table: Table does not exist or is not accessible</li>";
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
}

// Check mobile app API connection
echo "<h2>Mobile App API Access</h2>";
$api_url = "http://" . $_SERVER['HTTP_HOST'] . "/capstone/my-new-app/api/test.php";
echo "<p>Testing API access at: $api_url</p>";

// Create a test API endpoint if it doesn't exist
$test_api_dir = __DIR__ . "/my-new-app/api";
$test_api_file = $test_api_dir . "/test.php";

if (!file_exists($test_api_file)) {
    if (!is_dir($test_api_dir)) {
        mkdir($test_api_dir, 0755, true);
    }
    
    $test_api_content = '<?php
header("Content-Type: application/json");
echo json_encode(["status" => "success", "message" => "API is working correctly"]);
?>';
    
    file_put_contents($test_api_file, $test_api_content);
}

// Test API access
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $api_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 5);
$response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($status == 200) {
    echo "<p style='color: green;'>API access successful!</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
} else {
    echo "<p style='color: red;'>API access failed with status code: $status</p>";
}

// Final summary
echo "<h2>Summary</h2>";
echo "<p>The Farmers Market Platform requires:</p>";
echo "<ol>";
echo "<li>PHP 7.4 or higher</li>";
echo "<li>MySQL/MariaDB database</li>";
echo "<li>Required PHP extensions</li>";
echo "<li>Writable directories for uploads, logs, and cache</li>";
echo "<li>API access for the mobile app</li>";
echo "</ol>";

echo "<p>For the mobile app to connect properly, ensure your local IP address is correctly set in the my-new-app/constants/IPConfig.ts file.</p>";
?>
