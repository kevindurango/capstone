<?php
session_start();

// Include necessary models
require_once '../../models/Database.php';
require_once '../../models/User.php'; // Assuming you have a User model for authentication

// Instantiate necessary classes
$database = new Database();
$db = $database->connect();
$userClass = new User($db);

// Initialize variables
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate credentials
    $user = $userClass->login($username, $password, 'Organization Head');
    if ($user) {
        // Set session variables
        $_SESSION['organization_head_logged_in'] = true;
        $_SESSION['organization_head_user_id'] = $user['user_id'];
        $_SESSION['role'] = 'Organization Head';

        // Redirect to the dashboard
        header("Location: organization-head-dashboard.php");
        exit();
    } else {
        $error = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Head Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('../../public/assets/admin-background.svg'); /* Set background image */
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
    <h3>Organization Head Login</h3>

    <!-- Display error message if login failed -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="organization-head-login.php">
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