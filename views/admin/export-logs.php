<?php
// export-logs.php - Script for exporting activity logs
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin-login.php");
    exit();
}

// Include required files
require_once '../../models/Database.php';
require_once '../../models/Log.php';

// Create database and log instances
$database = new Database();
$conn = $database->connect();
$logClass = new Log();

// Get parameters from request
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Fetch logs based on filter and date range
$logs = $logClass->getFilteredLogs($filter, $startDate, $endDate);

// Set appropriate headers based on format
if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Output CSV header row
    fputcsv($output, ['Log ID', 'User', 'Activity', 'Timestamp']);
    
    // Output data rows
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['log_id'],
            $log['username'],
            $log['action'],
            $log['action_date']
        ]);
    }
    
    // Close output stream
    fclose($output);
    exit();
} else if ($format === 'pdf') {
    // Use a PHP PDF library like TCPDF or FPDF
    require_once '../../vendor/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Farmers Market Admin');
    $pdf->SetAuthor('Farmers Market System');
    $pdf->SetTitle('Activity Logs Report');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Activity Logs Report', 0, 1, 'C');
    
    // Add date range information if applicable
    if ($startDate && $endDate) {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Date Range: ' . date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate)), 0, 1, 'C');
    }
    
    // Add filter information
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Filter: ' . ucfirst($filter), 0, 1, 'C');
    
    // Add generation timestamp
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y, g:i a'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Create table header
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(20, 10, 'Log ID', 1, 0, 'C');
    $pdf->Cell(40, 10, 'User', 1, 0, 'C');
    $pdf->Cell(90, 10, 'Activity', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Timestamp', 1, 1, 'C');
    
    // Add table rows
    $pdf->SetFont('helvetica', '', 10);
    foreach ($logs as $log) {
        // Calculate row height based on content
        $activityText = $log['action'];
        $cellHeight = $pdf->getStringHeight(90, $activityText);
        $cellHeight = max(10, $cellHeight); // Minimum height
        
        $pdf->Cell(20, $cellHeight, $log['log_id'], 1, 0, 'C');
        $pdf->Cell(40, $cellHeight, $log['username'], 1, 0, 'L');
        
        // Multi-cell for activity to handle long text
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell(90, $cellHeight, $activityText, 1, 'L');
        $pdf->SetXY($x + 90, $y);
        
        $pdf->Cell(40, $cellHeight, date('Y-m-d H:i:s', strtotime($log['action_date'])), 1, 1, 'C');
    }
    
    // Output the PDF
    $pdf->Output('activity_logs_' . date('Y-m-d') . '.pdf', 'D');
    exit();
}

// Default case - redirect back to activity logs page
header("Location: activity-logs.php");
exit();
?>