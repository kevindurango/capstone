<?php
// This is a test script to verify the forgot-password.php endpoint

// Include the test email
$testEmail = "test@example.com";

// Create the payload
$payload = json_encode(array("email" => $testEmail));

// Set up cURL
$ch = curl_init('http://localhost/capstone/my-new-app/api/forgot-password.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

// Execute and get the response
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);

// Display results
echo "<h1>Test Results for Forgot Password API</h1>";
echo "<pre>";
echo "Status code: " . $info['http_code'] . "\n";
echo "Payload sent: " . $payload . "\n\n";

if ($error) {
    echo "cURL Error: " . $error . "\n\n";
}

echo "Raw response:\n";
var_dump($response);

echo "\n\nParsed response:\n";
$parsedResponse = json_decode($response, true);
var_dump($parsedResponse);

echo "</pre>";
