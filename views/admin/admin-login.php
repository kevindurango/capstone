<?php
// Start the session to track login status
session_start();

// Include necessary files
require_once '../../models/Database.php';
require_once '../../models/Log.php';  // Include the Log class

// Create a new Database instance and get the PDO connection
$db = new Database();
$pdo = $db->connect();  // Get the database connection

// Create an instance of the Log class
$log = new Log();

// If the login form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare the query to check if the user exists with the given username and join the roles table to check the user's role
    $sql = "SELECT u.*, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = :username LIMIT 1";  // Added LIMIT to ensure only one record is fetched
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the user record
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists, password matches, and the user is an admin
    if ($user && password_verify($password, $user['password'])) {
        if ($user['role_name'] === 'Admin') {  // Check if the user is an admin
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Start the session and store user info
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];  // Store the user's role in session
            $_SESSION['admin_name'] = $user['username']; // Add this line to store admin name

            // Log the login activity to the activitylogs table
            $log->logActivity($user['user_id'], 'Admin logged in');  // Make sure the activity is logged

            // Redirect to the admin dashboard
            header("Location: admin-dashboard.php");
            exit();
        } else {
            $error = "You are not authorized to access this page.";  // Not an admin
        }
    } else {
        $error = "Invalid username or password";  // Invalid credentials
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('../../public/assets/admin-background.svg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 400px;
            transition: transform 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
        }
        .login-card h3 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: 600;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo {
            width: 80px;
            height: auto;
        }
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        .form-group label {
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid #e74c3c;
        }
        .input-icon {
            position: absolute;
            right: 15px;
            top: 43px;
            color: #777;
        }
    </style>
</head>
<body>

<!-- Login Form -->
<div class="login-card">

    
    <!-- Display error message if any -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="">
        <h3>Admin Login</h3>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <i class="bi bi-person input-icon"></i>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <i class="bi bi-lock input-icon"></i>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>