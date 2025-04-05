<?php
require_once '../../models/Database.php';
require_once '../../models/DriverModel.php';

$database = new Database();
$conn = $database->connect();
$driverModel = new DriverModel();

$availableDrivers = $driverModel->getAvailableDrivers();
echo json_encode($availableDrivers);
?>
