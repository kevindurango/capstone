<?php
// Start the session to track login status
session_start();

// Include the database connection file
require_once '../../models/Database.php';  // Ensure the path to Database.php is correct

// Create a new Database instance and get the PDO connection
$db = new Database();
$pdo = $db->connect();  // Get the database connection

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
    <link rel="stylesheet" href="../../public/style/sidebar.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('../../public/assets/background.svg'); /* Set background image */
            background-size: cover; /* Cover the entire screen */
            background-position: center; /* Center the background image */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background-color: rgba(255, 255, 255, 0.8); /* White background with transparency */
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
        .login-card h3 {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control {
            margin-bottom: 15px;
        }
        .btn {
            width: 100%;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- Login Form -->
<div class="login-card">
    <h3>Admin Login</h3>

    <!-- Display error message if login failed -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?> <!-- Use htmlspecialchars to prevent XSS attacks -->
        </div>
    <?php endif; ?>

    <form method="POST" action="admin-login.php">
        <!-- Username Field -->
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>

        <!-- Password Field -->
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
