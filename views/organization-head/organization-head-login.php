<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['organization_head_logged_in']) && $_SESSION['organization_head_logged_in'] === true) {
    header("Location: organization-head-dashboard.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/User.php';
require_once '../../models/Log.php';

$database = new Database();
$conn = $database->connect();
$userModel = new User($conn);
$logClass = new Log();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            // Query to check user credentials and get all necessary information
            $query = "SELECT u.*, CONCAT(u.first_name, ' ', u.last_name) as full_name 
                     FROM users u 
                     WHERE u.email = :email AND u.role_id = 5"; // role_id 5 for Organization Head
            $stmt = $conn->prepare($query);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Store user information in session
                $_SESSION['organization_head_logged_in'] = true;
                $_SESSION['organization_head_user_id'] = $user['user_id'];
                $_SESSION['role'] = 'Organization Head';
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];

                // Log the successful login
                $logClass->logActivity($user['user_id'], "Organization Head logged in successfully");

                // Redirect to dashboard
                header("Location: organization-head-dashboard.php");
                exit();
            } else {
                $error = 'Invalid email or password.';
                // Log failed login attempt
                $logClass->logActivity(null, "Failed login attempt for Organization Head account: $email");
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
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
        <!-- Email Field -->
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
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