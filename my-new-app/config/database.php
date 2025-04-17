<?php
$host = '127.0.0.1'; // or your database host
$username = 'root'; // your database username
$password = ''; // your database password
$database = 'farmersmarketdb'; // your database name

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
