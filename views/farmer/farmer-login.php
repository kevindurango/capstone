<?php
session_start();
require_once '../../models/Farmer.php';

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If there are no errors, try to login the farmer
    if (empty($username_err) && empty($password_err)) {
        // Create a Farmer instance to check login credentials
        $farmer = new Farmer();
        $login_result = $farmer->checkLogin($username, $password);

        // If the login is successful
        if ($login_result) {
            // Store the farmer's details in session
            $_SESSION['farmer_logged_in'] = true;
            $_SESSION['farmer_id'] = $login_result['user_id']; // Store the farmer's id (make sure it's 'user_id' from the DB)
            $_SESSION['username'] = $username;

            // Redirect to the farmer dashboard
            header("location: farmer-dashboard.php");
            exit();
        } else {
            // Invalid login credentials
            $password_err = "Invalid username or password, or you are not a farmer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/farmer.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../../public/assets/logo.png" alt="Logo" class="rounded-circle mr-2" style="width: 40px; height: 40px;">
            Farmer Dashboard
        </a>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <h2 class="text-center">Farmer Login</h2>

        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Login</div>
                    <div class="card-body">
                        <!-- Login Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <!-- Username Input -->
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>

                            <!-- Password Input -->
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>

                            <!-- Login Button -->
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>

                        <!-- Error Message -->
                        <?php if (!empty($password_err)) { ?>
                            <div class="alert alert-danger text-center">
                                <strong>Error!</strong> <?php echo $password_err; ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
