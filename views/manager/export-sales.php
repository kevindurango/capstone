<?php
session_start();

// Check manager authentication
if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true || $_SESSION['role'] !== 'Manager') {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/SalesManager.php';
require_once '../../models/Log.php';

$salesClass = new SalesManager();
$logClass = new Log();

// Get date parameters or use defaults
$startDate = isset($_GET['start']) && !empty($_GET['start']) ? $_GET['start'] : null;
$endDate = isset($_GET['end']) && !empty($_GET['end']) ? $_GET['end'] : null;

// Get report data
$reportData = $salesClass->exportSalesReport($startDate, $endDate);

// Log the export activity
$logClass->logActivity($_SESSION['user_id'], "Manager exported sales report from $startDate to $endDate");

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="sales_report_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Sales Report</title>
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>Sales Report</h2>
    <p>Period: <?= $reportData['period']['start'] ?> to <?= $reportData['period']['end'] ?></p>
    
    <h3>Summary</h3>
    <table>
        <tr>
            <th>Total Revenue</th>
            <td>₱<?= number_format($reportData['summary']['totalRevenue'], 2) ?></td>
        </tr>
        <tr>
            <th>Average Order Value</th>
            <td>₱<?= number_format($reportData['summary']['averageOrderValue'], 2) ?></td>
        </tr>
    </table>
    
    <h3>Daily Revenue</h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Revenue</th>
        </tr>
        <?php foreach ($reportData['salesData'] as $day): ?>
        <tr>
            <td><?= $day['date'] ?></td>
            <td>₱<?= number_format($day['amount'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h3>Top Products</h3>
    <table>
        <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Units Sold</th>
            <th>Revenue</th>
        </tr>
        <?php foreach ($reportData['topProducts'] as $product): ?>
        <tr>
            <td><?= $product['name'] ?></td>
            <td><?= $product['category'] ? $product['category'] : 'Uncategorized' ?></td>
            <td><?= $product['units_sold'] ?></td>
            <td>₱<?= number_format($product['revenue'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h3>Sales by Category</h3>
    <table>
        <tr>
            <th>Category</th>
            <th>Total Sales</th>
        </tr>
        <?php foreach ($reportData['categorySales'] as $category): ?>
        <tr>
            <td><?= $category['category'] ? $category['category'] : 'Uncategorized' ?></td>
            <td>₱<?= number_format($category['total'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
