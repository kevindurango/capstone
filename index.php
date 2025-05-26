<?php
session_start();

// Function to get the base URL dynamically
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove trailing slashes and normalize the path
    $baseURL = $protocol . $host . $scriptDir;
    $baseURL = rtrim($baseURL, '/');
    
    return $baseURL;
}

// Set the base URL as a variable
$baseURL = getBaseURL();

// Check if user is already logged in with a specific role and redirect accordingly
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: {$baseURL}/views/admin/admin-dashboard.php");
    exit();
} elseif (isset($_SESSION['manager_logged_in']) && $_SESSION['manager_logged_in'] === true) {
    header("Location: {$baseURL}/views/manager/manager-dashboard.php");
    exit();
} elseif (isset($_SESSION['organization_head_logged_in']) && $_SESSION['organization_head_logged_in'] === true) {
    header("Location: {$baseURL}/views/organization-head/organization-head-dashboard.php");
    exit();
}

// Include the logging functionality with error handling
try {
    require_once 'models/Log.php';
    $logClass = new Log();
    
    // Log the page visit
    $logClass->logActivity(null, "Main login page visited");
} catch (Exception $e) {
    // Create a simple error log if the Log class fails
    error_log("Error in logging: " . $e->getMessage());
    // Continue execution even if logging fails
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Market Platform - Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1B5E20 0%, #4CAF50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .login-header {
            background-color: #1B5E20;
            color: white;
            padding: 30px;
            text-align: center;
            border-bottom: 5px solid #FFC107;
        }
        
        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .role-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-color: #4CAF50;
        }
        
        .role-icon {
            font-size: 48px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .role-card h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .role-card p {
            color: #777;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        .btn-role {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-role:hover {
            background-color: #1B5E20;
            color: white;
            transform: scale(1.05);
        }
        
        .mobile-info {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .mobile-info h3 {
            color: #1B5E20;
            margin-bottom: 15px;
        }
        
        .qr-code {
            max-width: 150px;
            margin: 15px auto;
        }
        
        .app-store-buttons {
            margin-top: 20px;
        }
        
        .app-store-buttons img {
            height: 40px;
            margin: 0 10px;
            transition: all 0.3s;
        }
        
        .app-store-buttons img:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .role-card {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main Login Container -->
        <div class="login-container">
            <!-- Login Header -->
            <div class="login-header">
                <h2>Farmers Market Platform</h2>
                <p class="mb-0">Select your role to login</p>
            </div>
            
            <!-- Login Body with Role Selection -->
            <div class="login-body">
                <div class="row">
                    <!-- Admin Role -->
                    <div class="col-md-4 mb-4">
                        <div class="role-card">
                            <div>
                                <div class="role-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <h3>Admin</h3>
                                <p>System administration access for managing core platform settings and user roles.</p>
                            </div>                            <a href="<?php echo $baseURL; ?>/views/admin/admin-login.php" class="btn btn-role btn-block">
                                Login as Admin
                            </a>
                        </div>
                    </div>
                    
                    <!-- Manager Role -->
                    <div class="col-md-4 mb-4">
                        <div class="role-card">
                            <div>
                                <div class="role-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h3>Manager</h3>
                                <p>Manage operations, inventory, and day-to-day activities of the farmers market.</p>
                            </div>
                            <a href="<?php echo $baseURL; ?>/views/manager/manager-login.php" class="btn btn-role btn-block">
                                Login as Manager
                            </a>
                        </div>
                    </div>
                    
                    <!-- Organization Head Role -->
                    <div class="col-md-4 mb-4">
                        <div class="role-card">
                            <div>
                                <div class="role-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <h3>Organization Head</h3>
                                <p>Executive access to view reports, analytics, and manage organizational aspects.</p>
                            </div>
                            <a href="<?php echo $baseURL; ?>/views/organization-head/organization-head-login.php" class="btn btn-role btn-block">
                                Login as Organization Head
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mobile App Information -->
        <div class="mobile-info">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h3>Access on Mobile</h3>
                    <p>Farmers and Consumers can use our mobile application for an optimized experience.</p>
                    <p class="mb-0"><strong>Features:</strong> Product management, order tracking, real-time notifications, and more!</p>
                </div>                <div class="col-md-5">
                    <div class="app-store-buttons">
                        <a href="<?php echo $baseURL; ?>/my-new-app/android/app/build/outputs/apk/release/app-release.apk" download class="btn btn-dark mb-2 mb-md-0">
                            <i class="fab fa-android mr-2"></i> Download Android APK
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
