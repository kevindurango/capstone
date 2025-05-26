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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pages - Direct Access</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 40px;
            background-color: #f8f9fa;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #1B5E20;
            color: white;
        }
        .btn-access {
            background-color: #4CAF50;
            color: white;
        }
        .btn-access:hover {
            background-color: #1B5E20;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mt-5">
                    <div class="card-header">
                        <h3 class="mb-0">Farmers Market - Login Pages</h3>
                    </div>
                    <div class="card-body">
                        <p class="alert alert-info">
                            If you're having trouble with the main index.php, use the direct links below to access specific login pages.
                        </p>
                        
                        <div class="list-group mb-4">
                            <a href="<?php echo $baseURL; ?>/views/admin/admin-login.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                Admin Login
                                <span class="badge badge-primary badge-pill">Direct Link</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/views/manager/manager-login.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                Manager Login
                                <span class="badge badge-primary badge-pill">Direct Link</span>
                            </a>
                            <a href="<?php echo $baseURL; ?>/views/organization-head/organization-head-login.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                Organization Head Login
                                <span class="badge badge-primary badge-pill">Direct Link</span>
                            </a>
                        </div>
                        
                        <h5>Mobile App Access</h5>
                        <p>For Farmers and Consumers, download and install the mobile app:</p>
                        <a href="<?php echo $baseURL; ?>/my-new-app/android/app/build/outputs/apk/release/app-release.apk" download class="btn btn-dark">
                            <i class="fab fa-android"></i> Download Android APK
                        </a>
                        
                        <hr>
                        
                        <div class="mt-4">
                            <h6>Having Issues?</h6>
                            <ul>
                                <li><a href="<?php echo $baseURL; ?>/test-login-pages.php">Run diagnostics</a> to check if login pages are accessible</li>
                                <li><a href="<?php echo $baseURL; ?>/env-check.php">Check environment configuration</a></li>
                                <li>Verify XAMPP is running (Apache and MySQL)</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="<?php echo $baseURL; ?>/index.php" class="btn btn-secondary">Return to Main Login Page</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
