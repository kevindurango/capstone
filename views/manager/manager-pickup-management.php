<?php
session_start();

if (!isset($_SESSION['manager_logged_in']) || $_SESSION['manager_logged_in'] !== true) {
    header("Location: manager-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';

// Database Connection
$database = new Database();
$conn = $database->connect();
$log = new Log();

// Run debug functions
$manager_user_id = $_SESSION['manager_user_id'] ?? null;

// Pagination Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and Filter Setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$startDateFilter = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDateFilter = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Update Pickup Logic
if (isset($_POST['update_pickup'])) {    $pickup_id = $_POST['pickup_id'];
    $pickup_status = $_POST['pickup_status'];
    $pickup_date = $_POST['pickup_date'];
    $pickup_notes = $_POST['pickup_notes'];
    $contact_person = $_POST['contact_person'] ?? null;
      // Validate pickup status
    $valid_statuses = ['pending', 'ready', 'completed', 'canceled'];
    $pickup_status = strtolower(trim($pickup_status)); // Normalize the status
    if (!in_array($pickup_status, $valid_statuses)) {
        $_SESSION['message'] = "Invalid pickup status. Must be one of: " . implode(', ', $valid_statuses);
        $_SESSION['message_type'] = 'danger';
        header("Location: manager-pickup-management.php");
        exit();
    }

    // Get the old pickup data for logging
    $oldDataQuery = "SELECT * FROM pickups WHERE pickup_id = :pickup_id";
    $oldDataStmt = $conn->prepare($oldDataQuery);
    $oldDataStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    $oldDataStmt->execute();
    $oldData = $oldDataStmt->fetch(PDO::FETCH_ASSOC);

    // Update Query - Modified for office pickup
    $updateQuery = "UPDATE pickups
                   SET pickup_status = :pickup_status,
                       pickup_date = :pickup_date,
                       pickup_notes = :pickup_notes,
                       contact_person = :contact_person,
                       office_location = 'Municipal Agriculture Office'
                   WHERE pickup_id = :pickup_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':pickup_status', $pickup_status);
    $updateStmt->bindParam(':pickup_date', $pickup_date);
    $updateStmt->bindParam(':pickup_notes', $pickup_notes);
    $updateStmt->bindParam(':contact_person', $contact_person);
    $updateStmt->bindParam(':pickup_id', $pickup_id, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        if ($manager_user_id) {
            $log->logActivity($manager_user_id, "Updated pickup #$pickup_id");
        }
        $_SESSION['message'] = "Pickup details updated successfully!";
        $_SESSION['message_type'] = 'success';
    }
}

// Export to CSV functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set default dates if not provided
    $exportStartDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
    $exportEndDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $exportStatus = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="pickup_report_' . $exportStartDate . '_to_' . $exportEndDate . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Pickup ID', 'Order ID', 'Customer', 'Status', 'Pickup Date', 'Location', 'Contact Person', 'Notes']);
    
    // Prepare the query with filters
    $exportQuery = "SELECT p.*, o.order_id, o.order_date, c.username AS consumer_name
                    FROM pickups p
                    JOIN orders o ON p.order_id = o.order_id
                    JOIN users c ON o.consumer_id = c.user_id
                    WHERE 1=1";
    
    if ($exportStatus) {
        $exportQuery .= " AND p.pickup_status = :status";
    }
    
    $exportQuery .= " AND DATE(p.pickup_date) BETWEEN :startDate AND :endDate
                      ORDER BY p.pickup_date DESC";
    
    $exportStmt = $conn->prepare($exportQuery);
    if ($exportStatus) {
        $exportStmt->bindValue(':status', $exportStatus, PDO::PARAM_STR);
    }
    $exportStmt->bindValue(':startDate', $exportStartDate, PDO::PARAM_STR);
    $exportStmt->bindValue(':endDate', $exportEndDate, PDO::PARAM_STR);
    $exportStmt->execute();
    
    // Write data rows
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['pickup_id'],
            $row['order_id'],
            $row['consumer_name'],
            ucfirst($row['pickup_status']),
            date("Y-m-d H:i", strtotime($row['pickup_date'])),
            $row['office_location'] ?: 'Municipal Agriculture Office',
            $row['contact_person'] ?: 'Not specified',
            $row['pickup_notes'] ?: ''
        ]);
    }
    
    // Log the export action
    if ($manager_user_id) {
        $log->logActivity($manager_user_id, "Exported pickup report from $exportStartDate to $exportEndDate");
    }
    
    // Close the output stream
    fclose($output);
    exit;
}

// Count total pickups
$countQuery = "SELECT COUNT(*) FROM pickups";
$countStmt = $conn->query($countQuery);
$totalPickups = $countStmt->fetchColumn();
    
// Count pickups by status
$pendingQuery = "SELECT COUNT(*) FROM pickups WHERE pickup_status = 'pending'";
$pendingStmt = $conn->query($pendingQuery);
$pendingCount = $pendingStmt->fetchColumn();
    
// Count today's pickups
$todayQuery = "SELECT COUNT(*) FROM pickups WHERE DATE(pickup_date) = CURDATE()";
$todayStmt = $conn->query($todayQuery);
$todayPickups = $todayStmt->fetchColumn();

// Count ready pickups - Update the query to get accurate count
$readyQuery = "SELECT COUNT(*) FROM pickups WHERE pickup_status = 'ready'";
$readyStmt = $conn->query($readyQuery);
$readyPickups = $readyStmt->fetchColumn();

// Get card header colors for different pickup statuses with gradients
$statusColors = [
    'pending' => 'linear-gradient(135deg, #fff3cd 0%, #ffffff 100%)',    // Light yellow to white gradient
    'ready' => 'linear-gradient(135deg, #cce5ff 0%, #ffffff 100%)',      // Light blue to white gradient
    'completed' => 'linear-gradient(135deg, #d4edda 0%, #ffffff 100%)',  // Light green to white gradient
    'canceled' => 'linear-gradient(135deg, #f8d7da 0%, #ffffff 100%)',   // Light red to white gradient
    'default' => 'linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%)'     // Default light gradient
];

// Fetch Pickups Query with Filtering
$query = "SELECT p.*, o.order_id, o.order_date, c.username AS consumer_name
          FROM pickups p
          JOIN orders o ON p.order_id = o.order_id
          JOIN users c ON o.consumer_id = c.user_id
          WHERE 1=1";
if ($search) {
    $query .= " AND (o.order_id LIKE :search OR c.username LIKE :search)";
}
if ($statusFilter) {
    $query .= " AND p.pickup_status = :statusFilter";
}
if ($startDateFilter && $endDateFilter) {
    $query .= " AND DATE(p.pickup_date) BETWEEN :startDate AND :endDate";
} else if ($startDateFilter) {
    $query .= " AND DATE(p.pickup_date) >= :startDate";
} else if ($endDateFilter) {
    $query .= " AND DATE(p.pickup_date) <= :endDate";
}
$query .= " ORDER BY p.pickup_date DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
if ($statusFilter) {
    $stmt->bindValue(':statusFilter', $statusFilter, PDO::PARAM_STR);
}
if ($startDateFilter && $endDateFilter) {
    $stmt->bindValue(':startDate', $startDateFilter, PDO::PARAM_STR);
    $stmt->bindValue(':endDate', $endDateFilter, PDO::PARAM_STR);
} else if ($startDateFilter) {
    $stmt->bindValue(':startDate', $startDateFilter, PDO::PARAM_STR);
} else if ($endDateFilter) {
    $stmt->bindValue(':endDate', $endDateFilter, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct statuses for filter
$statusQuery = "SELECT DISTINCT pickup_status FROM pickups ORDER BY pickup_status";
$statusStmt = $conn->prepare($statusQuery);
$statusStmt->execute();
$statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

// Calculate total pages for pagination
$totalPages = ceil($totalPickups / $limit);

// Handle logout
if (isset($_POST['logout'])) {
    // Log the logout action
    if ($manager_user_id) {
        $log->logActivity($manager_user_id, "Manager logged out");
    }
    session_unset();
    session_destroy();
    header("Location: manager-login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pickup Management - Manager Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }
        
        /* Animation keyframes */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-10px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Apply animations */
        .stats-card {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .stats-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-card:nth-child(3) { animation-delay: 0.3s; }
        .stats-card:nth-child(4) { animation-delay: 0.4s; }
        
        .pickup-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .export-card, .filter-card {
            animation: slideIn 0.5s ease-out forwards;
        }
        
        /* Hover animation for buttons */
        .btn-primary:hover, .btn-success:hover, .export-btn:hover {
            animation: pulse 0.5s ease-in-out;
        }.manager-header {
            background: linear-gradient(135deg, #1a8754 0%, #34c38f 100%);
            color: white;
            padding: 18px 0;
            margin-bottom: 25px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .manager-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 60%);
            z-index: 1;
            pointer-events: none;
        }
        
        .manager-badge {
            background-color: #157347;
            color: white;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 6px;
            margin-left: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0.75rem 0;
            border-bottom: 2px solid rgba(40, 167, 69, 0.1);
            position: relative;
        }
        
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 80px;
            height: 2px;
            background: linear-gradient(90deg, #28a745, transparent);
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0.75rem 0;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        
        .breadcrumb-item a {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .breadcrumb-item a i {
            margin-right: 5px;
            font-size: 1.1em;
        }
        
        .breadcrumb-item a:hover {
            color: #218838;
            text-decoration: none;
            transform: translateX(2px);
        }
        
        .breadcrumb-item.active {
            color: #495057;
            font-weight: 600;
        }
        
        /* Section heading style */
        .section-heading {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #343a40;
            font-weight: 700;
        }
        
        .section-heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #28a745, transparent);
            border-radius: 3px;
        }
        
        .section-heading i {
            color: #28a745;
        }/* Card styling improvements */
        .stats-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07), 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, #28a745, #20c997);
            transition: all 0.3s ease;
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }
        
        .stats-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 14px 28px rgba(40, 167, 69, 0.18), 0 5px 10px rgba(40, 167, 69, 0.12);
        }
        
        .stats-card:hover::before {
            width: 10px;
            background: linear-gradient(to bottom, #34ce57, #28a745);
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.5);
        }
        
        .stats-card .icon {
            font-size: 2.4rem;
            color: #28a745;
            opacity: 0.9;
            transition: all 0.4s ease;
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.12) 0%, rgba(40, 167, 69, 0.05) 100%);
            width: 65px;
            height: 65px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.15);
        }
        
        .stats-card:hover .icon {
            opacity: 1;
            transform: scale(1.15) rotate(8deg);
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.18) 0%, rgba(40, 167, 69, 0.09) 100%);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.25);
        }        .stats-card .count {
            font-size: 2.6rem;
            font-weight: 800;
            margin: 0;
            color: #2d3436;
            line-height: 1.1;
            background: linear-gradient(135deg, #212529, #28a745);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: all 0.4s ease;
            text-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
            letter-spacing: -0.5px;
        }
        
        .stats-card:hover .count {
            transform: scale(1.08);
            background: linear-gradient(135deg, #212529, #34ce57);
            -webkit-background-clip: text;
            background-clip: text;
        }
        
        .stats-card .title {
            font-size: 0.95rem;
            color: #6c757d;
            margin: 0 0 8px 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            position: relative;
            padding-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover .title {
            color: #495057;
        }
        
        .stats-card .title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 3px;
            background: linear-gradient(to right, rgba(40, 167, 69, 0.8), rgba(40, 167, 69, 0.3));
            border-radius: 6px;
            transition: all 0.4s ease;
        }
        
        .stats-card:hover .title::after {
            width: 50px;
            background: linear-gradient(to right, rgba(52, 206, 87, 0.9), rgba(40, 167, 69, 0.4));
        }
          /* Status label styling */
        .status-label {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .status-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.12);
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }
        
        .status-ready {
            background: linear-gradient(135deg, #d1ecf1 0%, #c3e6f5 100%);
            color: #0c5460;
            border: 1px solid rgba(23, 162, 184, 0.2);
        }
        
        .status-completed {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .status-canceled {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        /* Badge styling enhancement */
        .badge-pill {
            padding: 0.6em 1em;
            border-radius: 50rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Button styling enhancements */
        .pickup-card .btn {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 10px;
            font-weight: 600;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        }
        
        .pickup-card .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 5px 10px rgba(0,0,0,0.15);
        }
        
        .pickup-card .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0069d9 100%);
            border: none;
        }
        
        .pickup-card .btn-primary:hover {
            background: linear-gradient(135deg, #0069d9 0%, #0056b3 100%);
        }
        
        .pickup-card .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            border: none;
        }
        
        .pickup-card .btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
        }/* Pickup card improvements */
        .pickup-card {
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05), 0 6px 10px rgba(0,0,0,0.04);
            margin-bottom: 30px;
            overflow: hidden;
            position: relative;
            background: white;
            border: 1px solid rgba(0,0,0,0.03);
            transform-origin: center bottom;
        }
        
        .pickup-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #28a745, #20c997);
            opacity: 0;
            transition: all 0.4s ease;
        }
        
        .pickup-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 16px 32px rgba(0,0,0,0.09), 0 8px 16px rgba(0,0,0,0.06);
            border-color: rgba(40, 167, 69, 0.1);
        }
        
        .pickup-card:hover::after {
            opacity: 1;
            height: 6px;
        }
        
        .pickup-card .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 18px 20px;
            font-weight: 600;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .pickup-card .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #28a745, #20c997);
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .pickup-card:hover .card-header::before {
            width: 7px;
            opacity: 0.9;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.4);
        }
        
        .pickup-card .card-body {
            padding: 24px 22px;
            background: linear-gradient(135deg, #ffffff 0%, #fcfcfc 100%);
        }
        
        .pickup-card .card-body p {
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            line-height: 1.6;
            transition: all 0.2s ease;
        }
        
        .pickup-card:hover .card-body p {
            transform: translateX(3px);
        }
        
        .pickup-card .card-body p i {
            width: 32px;
            height: 32px;
            margin-right: 14px;
            color: white;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.25);
            transition: all 0.3s ease;
        }
        
        .pickup-card:hover .card-body p i {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.3);
        }
        
        .pickup-card .card-footer {
            background-color: #fafafa;
            padding: 16px 22px;
            border-top: 1px dashed rgba(0,0,0,0.06);
            transition: all 0.3s ease;
        }
        
        .pickup-card:hover .card-footer {
            background-color: #f0f7f2;
        }
        
        .pickup-card .btn-group .btn {
            border-radius: 8px;
            margin-right: 6px;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        
        .pickup-notes {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            font-size: 0.92rem;
            margin-top: 12px;
            border-left: 3px solid #dee2e6;
        }        /* Filter card styling */
        .filter-card {
            border: none;
            border-radius: 22px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
            background: linear-gradient(145deg, #ffffff 0%, #fafafa 100%);
            position: relative;
            border: 1px solid rgba(0,0,0,0.03);
            transition: all 0.4s ease;
        }
        
        .filter-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.08), 0 6px 16px rgba(0,0,0,0.06);
        }
        
        .filter-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: -1;
            border-radius: 22px;
            background: linear-gradient(to bottom right, #28a745 0%, #20c997 100%);
            opacity: 0;
            transition: all 0.4s ease;
        }
        
        .filter-card:hover::before {
            opacity: 0.05;
        }
        
        .filter-card .card-header {
            background: linear-gradient(145deg, #f8f9fa 0%, #ffffff 100%);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        .filter-card:hover .card-header {
            background: linear-gradient(145deg, #f0f9f2 0%, #f8fff9 100%);
            border-bottom: 1px solid rgba(40, 167, 69, 0.1);
        }
        
        .filter-card .card-header h5 {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .filter-card .card-header h5 i {
            color: #28a745;
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }
        
        .filter-card:hover .card-header h5 i {
            transform: rotate(-15deg);
        }
        
        .filter-card .card-body {
            padding: 24px;
        }
        
        .filter-card label {
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #343a40;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        
        .filter-card label i {
            color: #28a745;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .filter-card:hover label i {
            transform: scale(1.1);
        }
        
        .filter-card .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s ease;
            padding: 12px 18px;
            background-color: #ffffff;
            box-shadow: 0 3px 6px rgba(0,0,0,0.02);
            font-size: 0.95rem;
            height: auto;
        }
        
        .filter-card .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
            transform: translateY(-2px);
        }
        
        .filter-card .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
            transition: all 0.3s ease;
        }
        
        .filter-card .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.25);
            background: linear-gradient(135deg, #34ce57, #2dd4a9);
        }
          .active-filters {
            border-top: 1px dashed rgba(0,0,0,0.08);
            padding-top: 15px;
            margin-top: 10px;
        }
        
        /* Pickup info styling enhancements */
        .pickup-info {
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            margin: 10px 0;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .pickup-card:hover .pickup-info {
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-color: rgba(40, 167, 69, 0.1);
        }
        
        .pickup-date-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            position: relative;
            padding-left: 8px;
        }
        
        .pickup-date-badge i {
            color: #28a745;
            font-size: 1.1rem;
        }
        
        .pickup-location {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: 8px;
            position: relative;
            padding-left: 20px;
            border-left: 2px dotted #dee2e6;
            height: 30px;
        }
        
        .pickup-location i {
            color: #17a2b8;
            font-size: 1.1rem;
            position: absolute;
            left: -10px;
            background: white;
            border-radius: 50%;
            padding: 2px;
        }
        
        /* Avatar circle enhancement */
        .avatar-circle {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border: 2px solid white;
            transition: all 0.3s ease;
        }
        
        .pickup-card:hover .avatar-circle {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
        
        /* Notes styling enhancement */
        .pickup-notes {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 12px;
            border-left: 4px solid #28a745;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .pickup-card:hover .pickup-notes {
            background-color: #f0f9f2;
            transform: translateX(5px);
        }
        
        .notes-text {
            color: #495057;
            font-style: italic;
            padding-left: 5px;
        }
        
        .view-notes {
            color: #28a745;
            font-weight: 600;
        }
        
        .badge-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            padding: 8px 12px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
            margin: 4px;
            transition: all 0.3s ease;
        }
        
        .badge-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(23, 162, 184, 0.25);
        }
        
        /* Export section styling */        .export-card {
            border: none;
            border-radius: 22px;
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.08), 0 4px 10px rgba(40, 167, 69, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
            background: linear-gradient(145deg, #f8fff9 0%, #f0f9f2 100%);
            position: relative;
            border: 1px solid rgba(40, 167, 69, 0.08);
            transition: all 0.4s ease;
        }
        
        .export-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(40, 167, 69, 0.12), 0 6px 12px rgba(40, 167, 69, 0.1);
            border-color: rgba(40, 167, 69, 0.15);
        }
        
        .export-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 7px;
            background: linear-gradient(to bottom, #28a745 0%, #20c997 100%);
            border-radius: 0 3px 3px 0;
            box-shadow: 2px 0 8px rgba(40, 167, 69, 0.15);
            transition: all 0.3s ease;
        }
        
        .export-card:hover::before {
            width: 10px;
            box-shadow: 3px 0 12px rgba(40, 167, 69, 0.25);
        }
        
        .export-card .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(40, 167, 69, 0.12);
            padding: 20px 24px;
            font-weight: 600;
        }
        
        .export-card .card-header h5 {
            color: #28a745;
            font-weight: 700;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .export-card .card-body {
            padding: 24px;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.6px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .export-btn:hover {
            background: linear-gradient(135deg, #34ce57 0%, #2dd4a9 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(40, 167, 69, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Improved section headings */
        .section-heading {
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            font-weight: 600;
            color: #343a40;
        }
        
        .section-heading i {
            color: #28a745;
            margin-right: 8px;
        }
        
        /* Improved statistics card design */
        .stats-row {
            margin-bottom: 30px;
        }

        /* Modal styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
        }
        
        /* Form controls in modals */
        .pickup-form .form-group label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .pickup-form .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        
        .pickup-form .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Pagination */
        .pagination-container {
            margin-top: 30px;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            color: #343a40;
            transition: all 0.2s;
        }
        
        .pagination .page-link:hover {
            background-color: #e9ecef;
            color: #28a745;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 0;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .empty-state h4 {
            margin-bottom: 15px;
            color: #343a40;
        }
        
        .empty-state p {
            margin-bottom: 20px;
            color: #6c757d;
        }
        
        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 15px 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Avatar circle for customer display */
        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Pickup date badge */
        .pickup-date-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 8px;
            background-color: #f8f9fa;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .pickup-date-badge i {
            margin-right: 6px;
            color: #28a745;
        }

        /* Notes truncation */
        .notes-text {
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* ...existing code... */
        
        :root {
          --primary-color: #28a745;
          --primary-light: #e8f5e9;
          --secondary-color: #007bff;
          --warning-color: #ffc107;
          --danger-color: #dc3545;
          --info-color: #17a2b8;
          --dark-color: #343a40;
          --light-color: #f8f9fa;
          --border-radius: 10px;
          --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
          --transition: all 0.3s ease;
        }

        /* Breadcrumb styling - added to match order-oversight */
        .breadcrumb {
          background-color: transparent;
          padding: 0.75rem 0;
          margin-bottom: 1.5rem;
        }

        .breadcrumb-item a {
          color: var(--primary-color);
          text-decoration: none;
          transition: var(--transition);
        }

        .breadcrumb-item a:hover {
          color: #2980b9;
          text-decoration: underline;
        }

        /* Action row with gradient - added to match order-oversight style */
        .action-row {
          display: flex;
          justify-content: space-between;
          margin-bottom: 1.5rem;
          padding-bottom: 1rem;
          position: relative;
        }

        .action-row:after {
          content: "";
          position: absolute;
          left: 0;
          right: 0;
          bottom: 0;
          height: 1px;
          background: linear-gradient(90deg, #28a745, transparent);
        }

        .action-row h1 {
          background: linear-gradient(45deg, var(--dark-color), #28a745);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          text-fill-color: transparent;
          font-weight: 600;
          letter-spacing: -0.5px;
        }

        /* Table styling - added to match order-oversight */
        .table-container {
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
          overflow: hidden;
        }

        .table thead th {
          background: #28a745;
          border-bottom: 2px solid #219a3a;
          color: white;
          font-weight: 600;
          padding: 12px 15px;
          text-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
          position: sticky;
          top: 0;
          z-index: 10;
        }

        /* Pickup Modal Improvements */
        .pickup-modal .modal-content {
          border: none;
          border-radius: var(--border-radius);
          box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .pickup-modal .modal-header {
          background: linear-gradient(145deg, var(--secondary-color), #27ae60);
          color: white;
          border-bottom: none;
          padding: 1.5rem;
        }

        .pickup-modal .modal-title {
          font-weight: 600;
          display: flex;
          align-items: center;
          gap: 0.75rem;
        }

        .pickup-modal .modal-body {
          padding: 2rem;
        }

        /* Form Styling - Enhanced for better dropdown visibility */
        .pickup-form .form-group {
          margin-bottom: 1.5rem;
        }

        .pickup-form label {
          font-weight: 500;
          color: var(--dark-color);
          margin-bottom: 0.5rem;
          display: flex;
          align-items: center;
          gap: 0.5rem;
        }

        /* Improved select and input styling for better visibility */
        .pickup-form .form-control,
        .pickup-modal select.form-control,
        .pickup-modal input.form-control,
        .pickup-form select.form-control {
          border-radius: var(--border-radius);
          padding: 0.75rem 1rem;
          border: 2px solid #e9ecef;
          transition: var(--transition);
          color: #000000; /* Darker text color for better visibility */
          background-color: white;
          height: auto; /* Ensure proper height */
          min-height: 42px; /* Minimum height to avoid sizing issues */
          width: 100%; /* Full width within container */
          font-size: 14px; /* Explicit font size */
          -webkit-appearance: none; /* Fix for Safari issues */
          -moz-appearance: none; /* Fix for Firefox issues */
          appearance: none; /* Standardized appearance */
        }

        .pickup-form .form-control:focus,
        .pickup-modal select.form-control:focus,
        .pickup-modal input.form-control:focus {
          border-color: var(--secondary-color);
          box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25);
          outline: none; /* Remove default outline */
        }

        /* Dropdown option text visibility fix */
        .pickup-form select.form-control option,
        .pickup-modal select.form-control option {
          color: #000000; /* Darker text for options */
          background-color: white;
          padding: 10px 15px; /* Match input padding */
          font-size: 14px; /* Match font size */
          min-height: 30px; /* Ensure options are tall enough */
          line-height: 1.5; /* Add line height for better readability */
        }

        /* Filter dropdown arrow styling */
        .pickup-form select.form-control,
        .pickup-modal select.form-control {
          background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
          background-repeat: no-repeat;
          background-position: right 0.75rem center;
          background-size: 16px 12px;
          padding-right: 2.5rem; /* Make room for the arrow */
        }

        /* Fix for Firefox styling */
        @-moz-document url-prefix() {
          .pickup-form select.form-control,
          .pickup-modal select.form-control {
            text-indent: 0.01px;
            text-overflow: "";
          }
        }

        /* Resize textarea to be taller */
        .pickup-form textarea.form-control {
          min-height: 100px;
          resize: vertical;
        }

        /* Status Indicators */
        .pickup-status {
          padding: 0.5rem 1rem;
          border-radius: 50px;
          font-size: 0.875rem;
          font-weight: 500;
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
        }

        .pickup-status.pending {
          background-color: var(--warning-light);
          color: var(--warning-color);
        }

        .pickup-status.in-progress {
          background-color: var(--info-light);
          color: var(--info-color);
        }

        .pickup-status.completed {
          background-color: var(--secondary-light);
          color: var(--secondary-color);
        }

        /* Action Buttons */
        .pickup-actions {
          display: flex;
          gap: 0.5rem;
          justify-content: flex-end;
          margin-top: 2rem;
        }

        .pickup-actions .btn {
          padding: 0.625rem 1.25rem;
          font-weight: 500;
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          border-radius: var(--border-radius);
          transition: var(--transition);
        }

        .pickup-actions .btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Timeline Display */
        .pickup-timeline {
          position: relative;
          padding: 1.5rem 0;
        }

        .timeline-item {
          position: relative;
          padding-left: 2.5rem;
          padding-bottom: 1.5rem;
        }

        .timeline-item::before {
          content: "";
          position: absolute;
          left: 0;
          top: 0;
          bottom: 0;
          width: 2px;
          background: #e9ecef;
        }

        .timeline-item::after {
          content: "";
          position: absolute;
          left: -4px;
          top: 0;
          width: 10px;
          height: 10px;
          border-radius: 50%;
          background: var(--secondary-color);
          border: 2px solid white;
        }

        .timeline-content {
          background: white;
          padding: 1rem;
          border-radius: var(--border-radius);
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        /* Map Container */
        .pickup-location-map {
          height: 200px;
          border-radius: var(--border-radius);
          margin-top: 1rem;
          overflow: hidden;
          box-shadow: var(--box-shadow);
        }

        /* Pickup Management Page Styles */
        .pickup-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 1.5rem;
          padding-bottom: 1rem;
          position: relative;
        }

        .pickup-header:after {
          content: "";
          position: absolute;
          left: 0;
          right: 0;
          bottom: 0;
          height: 1px;
          background: linear-gradient(90deg, #28a745, transparent);
        }

        .pickup-header h1 {
          background: linear-gradient(45deg, var(--dark-color), #28a745);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          text-fill-color: transparent;
          font-weight: 600;
          letter-spacing: -0.5px;
          margin-bottom: 5px;
        }

        .pickup-header .text-muted {
          font-size: 1rem;
        }

        .search-container {
          background-color: white;
          border-radius: var(--border-radius);
          padding: 20px;
          margin-bottom: 25px;
          box-shadow: var(--box-shadow);
        }

        .pickup-card {
          border: none;
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
          transition: var(--transition);
          overflow: hidden;
        }

        .pickup-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }

        .pickup-card .card-header {
          background-color: var(--primary-light);
          border-bottom: 2px solid var(--primary-color);
          font-weight: 600;
          color: var(--primary-color);
        }

        .pickup-card .card-body {
          padding: 20px;
        }

        .pickup-card .card-footer {
          background-color: white;
          border-top: 1px solid #f0f0f0;
          padding: 15px 20px;
        }

        .status-label {
          padding: 6px 12px;
          border-radius: 20px;
          font-size: 0.85rem;
          font-weight: 500;
        }        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-ready {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-canceled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .pickup-notes {
          max-height: 80px;
          overflow-y: auto;
          margin-top: 10px;
          padding: 10px;
          background-color: #f8f9fa;
          border-radius: 5px;
          font-size: 0.9rem;
        }

        .empty-state {
          text-align: center;
          padding: 60px 0;
          background-color: white;
          border-radius: var(--border-radius);
          box-shadow: var(--box-shadow);
          margin-bottom: 25px;
        }

        .empty-state i {
          font-size: 3rem;
          color: #adb5bd;
          margin-bottom: 15px;
          display: block;
        }
    </style>
</head>
<body>
    <!-- Manager Header -->
    <div class="manager-header text-center">
        <h2><i class="bi bi-truck"></i> PICKUP MANAGEMENT SYSTEM <span class="manager-badge">Manager Access</span></h2>
    </div>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/manager-sidebar.php'; ?>
            
            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4 pickup-management-page">
                <!-- Update breadcrumb styling -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="manager-dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pickup Management</li>
                    </ol>
                </nav>
                
                <!-- Enhanced Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="h2"><i class="bi bi-truck text-success"></i> Pickup Management</h1>
                        <p class="text-muted">Manage and track all product pickups in one place</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to logout?');">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
                
                <!-- Display Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <!-- Statistics Cards - Updated with modern clean design -->
                <div class="row stats-row">
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <div>
                                <p class="title">Total Pickups</p>
                                <p class="count"><?= $totalPickups ?></p>
                            </div>
                            <div class="icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <div>
                                <p class="title">Pending Pickups</p>
                                <p class="count"><?= $pendingCount ?></p>
                            </div>
                            <div class="icon text-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <div>
                                <p class="title">Today's Pickups</p>
                                <p class="count"><?= $todayPickups ?></p>
                            </div>
                            <div class="icon text-success">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stats-card">
                            <div>                                <p class="title">Ready Pickups</p>
                                <p class="count"><?= $readyPickups ?></p>
                            </div>
                            <div class="icon text-info">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Management Tools Section with improved heading -->
                <div class="d-flex align-items-center mb-3">
                    <h4 class="mb-0"><i class="bi bi-gear text-success"></i> Management Tools</h4>
                    <div class="dropdown ml-auto">
                        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" id="quickActionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-lightning-charge"></i> Quick Actions
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="quickActionsDropdown">
                            <a class="dropdown-item" href="#" id="bulkExport"><i class="bi bi-file-earmark-excel"></i> Export All Pickups</a>
                            <a class="dropdown-item" href="#" id="todaysPickups"><i class="bi bi-calendar-day"></i> View Today's Pickups</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="manager-dashboard.php"><i class="bi bi-speedometer2"></i> Go to Dashboard</a>
                        </div>
                    </div>
                </div>
                
                <!-- Export Functionality - Moved Above -->
                <div class="card export-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">
                            <i class="bi bi-file-earmark-arrow-down text-success"></i> Export Pickup Data
                        </h5>
                    </div>
                    <div class="card-body py-3">
                        <form method="GET" action="" id="exportForm">
                            <input type="hidden" name="export" value="csv">
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label for="export_start_date" class="small font-weight-bold">Start Date:</label>
                                    <input type="date" class="form-control" id="export_start_date" name="start_date" 
                                           value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="export_end_date" class="small font-weight-bold">End Date:</label>
                                    <input type="date" class="form-control" id="export_end_date" name="end_date" 
                                           value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="export_status" class="small font-weight-bold">Status Filter:</label>
                                    <select class="form-control" id="export_status" name="status">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= htmlspecialchars($status) ?>">
                                                <?= ucfirst(htmlspecialchars($status)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button type="submit" class="btn export-btn btn-block">
                                        <i class="bi bi-file-earmark-arrow-down"></i> Export to CSV
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Download pickup data as a CSV file based on date range and status.</small>
                        </form>
                    </div>
                </div>
                
                <!-- Search and Filter Controls -->
                <div class="card filter-card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel"></i> Search & Filter Pickups
                            <?php if($search || $statusFilter || $startDateFilter || $endDateFilter): ?>
                                <span class="badge badge-primary ml-2">Active</span>
                            <?php endif; ?>
                        </h5>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#filterCollapse" aria-expanded="true" aria-controls="filterCollapse">
                            <i class="bi bi-chevron-up" id="filterToggleIcon"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="filterCollapse">
                        <div class="card-body py-3">
                            <form method="GET" action="">
                                <div class="row">
                                    <!-- Search Field -->
                                    <div class="col-md-3 mb-2">
                                        <label class="small font-weight-bold">Search</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            </div>
                                            <input type="text" class="form-control" placeholder="Order/Customer" 
                                                   name="search" value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                    </div>
                                    <!-- Status Filter -->
                                    <div class="col-md-3 mb-2">
                                        <label class="small font-weight-bold">Status</label>
                                        <select class="form-control" name="status">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= htmlspecialchars($status) ?>" 
                                                        <?= $statusFilter === $status ? 'selected' : '' ?>>
                                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Date Range Filters -->
                                    <div class="col-md-2 mb-2">
                                        <label class="small font-weight-bold">From Date</label>
                                        <input type="date" class="form-control" placeholder="From Date"
                                               name="start_date" value="<?= htmlspecialchars($startDateFilter) ?>">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="small font-weight-bold">To Date</label>
                                        <input type="date" class="form-control" placeholder="To Date"
                                               name="end_date" value="<?= htmlspecialchars($endDateFilter) ?>">
                                    </div>
                                    <!-- Action Buttons -->
                                    <div class="col-md-2 mb-2 d-flex align-items-end">
                                        <div class="btn-group w-100">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-filter"></i> Apply
                                            </button>
                                            <a href="manager-pickup-management.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-circle"></i> Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Active Filters Display - Compact -->
                                <?php if($search || $statusFilter || $startDateFilter || $endDateFilter): ?>
                                <div class="mt-2 pt-2 active-filters">
                                    <small class="text-muted font-weight-bold">Active Filters:</small>
                                    <?php if($search): ?>
                                        <span class="badge badge-info mr-1"><?= htmlspecialchars($search) ?></span>
                                    <?php endif; ?>
                                    <?php if($statusFilter): ?>
                                        <span class="badge badge-info mr-1"><?= ucfirst(htmlspecialchars($statusFilter)) ?></span>
                                    <?php endif; ?>
                                    <?php if($startDateFilter): ?>
                                        <span class="badge badge-info mr-1">From: <?= htmlspecialchars($startDateFilter) ?></span>
                                    <?php endif; ?>
                                    <?php if($endDateFilter): ?>
                                        <span class="badge badge-info mr-1">To: <?= htmlspecialchars($endDateFilter) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Pickups List Section -->
                <h4 class="section-heading"><i class="bi bi-box-seam"></i> Pickups</h4>
                
                <!-- Pickups Content -->
                <?php if (count($pickups) > 0): ?>
                    <div class="row">
                        <?php foreach ($pickups as $pickup): 
                            // Determine appropriate status colors and icons
                            $statusClass = 'secondary';
                            $statusIcon = 'question-circle';
                              switch(strtolower($pickup['pickup_status'])) {
                                case 'pending':
                                    $statusClass = 'warning';
                                    $statusIcon = 'hourglass-split';
                                    break;
                                case 'ready':
                                    $statusClass = 'info';
                                    $statusIcon = 'check2-square';
                                    break;
                                case 'completed':
                                    $statusClass = 'success';
                                    $statusIcon = 'check-circle';
                                    break;
                                case 'canceled':
                                    $statusClass = 'danger';
                                    $statusIcon = 'x-circle';
                                    break;
                                default:
                                    $statusClass = 'secondary';
                                    $statusIcon = 'question-circle';
                            }
                            
                            // Format dates
                            $pickupDate = new DateTime($pickup['pickup_date']);
                            $now = new DateTime();
                            $isToday = $pickupDate->format('Y-m-d') === $now->format('Y-m-d');
                            $isPast = $pickupDate < $now && !$isToday;
                            $dateClass = $isToday ? 'text-success font-weight-bold' : ($isPast ? 'text-danger' : '');
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card pickup-card">
                                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: <?= $statusColors[$pickup['pickup_status']] ?? $statusColors['default'] ?>;">
                                        <span>
                                            <span class="badge badge-pill badge-light mr-2">#<?= htmlspecialchars($pickup['pickup_id']) ?></span>
                                            Order #<?= htmlspecialchars($pickup['order_id']) ?>
                                        </span>
                                        <span class="badge badge-pill badge-<?= $statusClass ?>">
                                            <i class="bi bi-<?= $statusIcon ?>"></i> <?= ucfirst(htmlspecialchars($pickup['pickup_status'])) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="media mb-3">
                                            <div class="mr-3">
                                                <div class="avatar-circle">
                                                    <span class="avatar-text"><?= strtoupper(substr($pickup['consumer_name'], 0, 1)) ?></span>
                                                </div>
                                            </div>
                                            <div class="media-body">
                                                <h6 class="mt-0 mb-1"><?= htmlspecialchars($pickup['consumer_name']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-person-badge"></i> 
                                                    <?= htmlspecialchars($pickup['contact_person'] ?? 'Not specified') ?>
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="pickup-info">
                                            <div class="pickup-date-badge <?= $dateClass ?>">
                                                <i class="bi bi-calendar-event"></i>
                                                <?= $isToday ? 'Today' : htmlspecialchars($pickupDate->format("M j, Y")) ?>
                                                at <?= htmlspecialchars($pickupDate->format("g:i A")) ?>
                                                <?= $isPast ? '<span class="badge badge-danger ml-1">Overdue</span>' : '' ?>
                                            </div>
                                            
                                            <div class="pickup-location mt-2">
                                                <i class="bi bi-geo-alt"></i>
                                                <span class="text-muted">Municipal Agriculture Office</span>
                                            </div>
                                        </div>

                                        <?php if(!empty($pickup['pickup_notes'])): ?>
                                        <div class="pickup-notes mt-3">
                                            <i class="bi bi-journal-text"></i> <strong>Notes:</strong>
                                            <p class="mb-0 text-truncate notes-text" title="<?= htmlspecialchars($pickup['pickup_notes']) ?>">
                                                <?= htmlspecialchars($pickup['pickup_notes']) ?>
                                            </p>
                                            <button class="btn btn-link btn-sm p-0 view-notes" data-notes="<?= htmlspecialchars($pickup['pickup_notes']) ?>">
                                                View all
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer bg-white d-flex justify-content-between">
                                        <button class="btn btn-sm btn-primary edit-pickup-btn" 
                                                data-toggle="modal" 
                                                data-target="#editPickupModal" 
                                                data-pickup-id="<?= htmlspecialchars($pickup['pickup_id']) ?>"
                                                data-pickup-status="<?= htmlspecialchars($pickup['pickup_status']) ?>"
                                                data-pickup-date="<?= htmlspecialchars($pickup['pickup_date']) ?>"
                                                data-pickup-notes="<?= htmlspecialchars($pickup['pickup_notes']) ?>"
                                                data-contact-person="<?= htmlspecialchars($pickup['contact_person'] ?? '') ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-info view-order-btn"
                                                data-toggle="modal"
                                                data-target="#viewOrderModal"
                                                data-order-id="<?= htmlspecialchars($pickup['order_id']) ?>">
                                            <i class="bi bi-eye-fill"></i> View Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="empty-state">
                        <i class="bi bi-box-seam"></i>
                        <h4>No Pickups Found</h4>
                        <p>There are no pickups matching your search criteria.</p>
                        <a href="manager-pickup-management.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container mt-4">
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-double-left"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">1</a>
                                    </li>
                                    <?php if ($startPage > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($endPage < $totalPages): ?>
                                    <?php if ($endPage < $totalPages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>"><?= $totalPages ?></a>
                                    </li>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&start_date=<?= urlencode($startDateFilter) ?>&end_date=<?= urlencode($endDateFilter) ?>">
                                            <i class="bi bi-chevron-double-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <p class="text-center text-muted">Showing page <?= $page ?> of <?= $totalPages ?></p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Edit Pickup Modal -->
    <div class="modal fade" id="editPickupModal" tabindex="-1" role="dialog" aria-labelledby="editPickupModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPickupModalLabel"><i class="bi bi-truck"></i> Edit Pickup Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" class="pickup-form">
                        <input type="hidden" name="pickup_id" id="pickup_id">                        <div class="form-group">
                            <label for="pickup_status"><i class="bi bi-tag"></i> Pickup Status</label>                            <select class="form-control" id="pickup_status" name="pickup_status">
                                <option value="pending">Pending</option>
                                <option value="ready">Ready</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                            <small class="form-text text-muted">Valid statuses are: pending, ready, completed, or canceled</small>
                        </div>
                        <div class="form-group">
                            <label for="pickup_date"><i class="bi bi-calendar"></i> Pickup Date</label>
                            <input type="datetime-local" class="form-control" id="pickup_date" name="pickup_date">
                        </div>
                        <div class="form-group">
                            <label for="pickup_notes"><i class="bi bi-card-text"></i> Pickup Notes</label>
                            <textarea class="form-control" id="pickup_notes" name="pickup_notes" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="contact_person"><i class="bi bi-person"></i> Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
                        </div>
                        <div class="pickup-actions">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success" name="update_pickup">
                                <i class="bi bi-check-circle"></i> Update Pickup
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1" role="dialog" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOrderModalLabel"><i class="bi bi-receipt"></i> Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printOrderBtn">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script>
        $(document).ready(function () {
            // Format the date for datetime-local input
            function formatDateForInput(dateString) {
                const date = new Date(dateString);
                return date.toISOString().slice(0, 16);
            }

            // Handle edit pickup button click
            $('.edit-pickup-btn').click(function () {
                const pickupId = $(this).data('pickup-id');
                const pickupStatus = $(this).data('pickup-status');
                const pickupDate = $(this).data('pickup-date');
                const pickupNotes = $(this).data('pickup-notes');
                const contactPerson = $(this).data('contact-person');

                // Populate the modal form
                $('#pickup_id').val(pickupId);
                $('#pickup_status').val(pickupStatus);
                $('#pickup_date').val(formatDateForInput(pickupDate));
                $('#pickup_notes').val(pickupNotes);
                $('#contact_person').val(contactPerson);
            });

            // Handle view order button click
            $('.view-order-btn').click(function () {
                const orderId = $(this).data('order-id');
                
                // Show loading message in the modal
                $('#orderDetails').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div><p class="mt-2">Loading order details...</p></div>');
                
                // Fetch order details via AJAX
                $.ajax({
                    url: 'fetch-order-details.php',
                    type: 'GET',
                    data: {
                        order_id: orderId
                    },
                    success: function(response) {
                        $('#orderDetails').html(response);
                    },
                    error: function() {
                        $('#orderDetails').html('<div class="alert alert-danger">Failed to load order details. Please try again.</div>');
                    }
                });
            });
            
            // Handle print button click
            $('#printOrderBtn').click(function() {
                const printContent = document.getElementById('orderDetails').innerHTML;
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                
                printWindow.document.open();
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Order Details</title>
                        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            body { padding: 20px; }
                            .print-header { text-align: center; margin-bottom: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="print-header">
                            <h2>Municipal Agriculture Office</h2>
                            <h4>Order Details</h4>
                        </div>
                        ${printContent}
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            });

            // Auto-dismiss alerts
            setTimeout(function () {
                $('.alert').alert('close');
            }, 5000);

            // Manage the collapse toggle icon
            $('#filterCollapse').on('show.bs.collapse', function () {
                $('#filterToggleIcon').removeClass('bi-chevron-down').addClass('bi-chevron-up');
            });
            
            $('#filterCollapse').on('hide.bs.collapse', function () {
                $('#filterToggleIcon').removeClass('bi-chevron-up').addClass('bi-chevron-down');
            });
        });
    </script>
</body>
</html>