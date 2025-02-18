<?php
session_start();
require_once '../../controllers/FarmerController.php';

$farmerController = new FarmerController();

if (isset($_POST['logout'])) {
    $farmerController->farmerLogout();
}

if (!isset($_SESSION['farmer_logged_in'])) {
    header("Location: farmer-login.php");
    exit();
}

$farmer_id = $_SESSION['farmer_id'];

$farmer = new Farmer();
$farmerDetails = $farmer->getFarmerDetails($farmer_id);
$userDetails = $farmer->getUserDetails($farmer_id);

if ($farmerDetails === false || $userDetails === false) {
    $_SESSION['error'] = "Farmer details or user details not found.";
    header("Location: farmer-profile.php");
    exit();
}

$firstName = htmlspecialchars($userDetails['first_name']);
$lastName = htmlspecialchars($userDetails['last_name']);
$contactNumber = htmlspecialchars($userDetails['contact_number']);
$address = htmlspecialchars($userDetails['address']);
$farmName = htmlspecialchars($farmerDetails['farm_name'] ?? '');
$farmLocation = htmlspecialchars($farmerDetails['farm_location'] ?? '');
$farmType = htmlspecialchars($farmerDetails['farm_type'] ?? '');
$certifications = htmlspecialchars($farmerDetails['certifications'] ?? '');
$cropVarieties = htmlspecialchars($farmerDetails['crop_varieties'] ?? '');
$machineryUsed = htmlspecialchars($farmerDetails['machinery_used'] ?? '');
$farmSize = htmlspecialchars($farmerDetails['farm_size'] ?? '');
$income = htmlspecialchars($farmerDetails['income'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedFirstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $updatedLastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $updatedContactNumber = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
    $updatedAddress = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $updatedFarmName = filter_input(INPUT_POST, 'farm_name', FILTER_SANITIZE_STRING);
    $updatedFarmLocation = filter_input(INPUT_POST, 'farm_location', FILTER_SANITIZE_STRING);
    $updatedFarmType = filter_input(INPUT_POST, 'farm_type', FILTER_SANITIZE_STRING);
    $updatedCertifications = filter_input(INPUT_POST, 'certifications', FILTER_SANITIZE_STRING);
    $updatedCropVarieties = filter_input(INPUT_POST, 'crop_varieties', FILTER_SANITIZE_STRING);
    $updatedMachineryUsed = filter_input(INPUT_POST, 'machinery_used', FILTER_SANITIZE_STRING);
    $updatedFarmSize = filter_input(INPUT_POST, 'farm_size', FILTER_SANITIZE_STRING);
    $updatedIncome = filter_input(INPUT_POST, 'income', FILTER_SANITIZE_STRING);

    $updatePersonalSuccess = $farmerController->updatePersonalDetails($farmer_id, $updatedFirstName, $updatedLastName, $updatedContactNumber, $updatedAddress);
    $updateFarmSuccess = $farmerController->updateFarmDetails($farmer_id, $updatedFarmName, $updatedFarmLocation, $updatedFarmType, $updatedCertifications, $updatedCropVarieties, $updatedMachineryUsed, $updatedFarmSize, $updatedIncome);

    if ($updatePersonalSuccess && $updateFarmSuccess) {
        $_SESSION['message'] = "Profile updated successfully!";
        header("Location: farmer-profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../public/style/farmer.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #28a745; }
        .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .card-header { background-color: #28a745; color: white; font-weight: bold; border-radius: 10px 10px 0 0; }
        .form-control:focus { border-color: #28a745; box-shadow: 0 0 5px rgba(40, 167, 69, 0.5); }
        .btn-primary { background-color: #28a745; border: none; padding: 10px 20px; font-weight: 600; }
        .btn-primary:hover { background-color: #218838; }
        .footer { background-color: #343a40; color: white; padding: 20px 0; margin-top: 40px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../../public/assets/logo.png" alt="Logo" width="40" height="40" class="rounded-circle me-2">
                Farmer Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="farmer-dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="farmer-products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="farmer-orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="farmer-feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link active" href="farmer-profile.php">Profile</a></li>
                    <li class="nav-item">
                        <form method="POST" class="d-inline">
                            <button type="submit" name="logout" class="btn btn-danger ms-2">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h1 class="text-center mb-4">Update Your Profile</h1>
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="card mb-4">
                <div class="card-header">Personal Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="first_name">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?= $firstName ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="last_name">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?= $lastName ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?= $contactNumber ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?= $address ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Farm Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="farm_name">Farm Name</label>
                                <input type="text" class="form-control" id="farm_name" name="farm_name" value="<?= $farmName ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="farm_location">Farm Location</label>
                                <input type="text" class="form-control" id="farm_location" name="farm_location" value="<?= $farmLocation ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="farm_type">Farm Type</label>
                                <input type="text" class="form-control" id="farm_type" name="farm_type" value="<?= $farmType ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="farm_size">Farm Size</label>
                                <input type="text" class="form-control" id="farm_size" name="farm_size" value="<?= $farmSize ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Additional Information</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="certifications">Certifications</label>
                                <input type="text" class="form-control" id="certifications" name="certifications" value="<?= $certifications ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="crop_varieties">Crop Varieties</label>
                                <input type="text" class="form-control" id="crop_varieties" name="crop_varieties" value="<?= $cropVarieties ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="machinery_used">Machinery Used</label>
                                <input type="text" class="form-control" id="machinery_used" name="machinery_used" value="<?= $machineryUsed ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="income">Income</label>
                                <input type="text" class="form-control" id="income" name="income" value="<?= $income ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
