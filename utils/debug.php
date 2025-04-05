<?php
function debugLog($message, $data = null) {
    $logMessage = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    error_log($logMessage);
}

function validateOrderStatus($status) {
    $validStatuses = ['pending', 'completed', 'canceled'];
    return in_array(strtolower($status), $validStatuses);
}
?>
