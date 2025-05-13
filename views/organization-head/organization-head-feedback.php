<?php
session_start();

// Check if user is logged in as Organization Head
if (!isset($_SESSION['organization_head_logged_in']) || $_SESSION['organization_head_logged_in'] !== true || $_SESSION['role'] !== 'Organization Head') {
    header("Location: organization-head-login.php");
    exit();
}

require_once '../../models/Database.php';
require_once '../../models/Log.php';

$logClass = new Log();
$db = new Database();
$conn = $db->connect();

// Get organization_head_user_id from session
$organization_head_user_id = $_SESSION['organization_head_user_id'] ?? null;

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Handle feedback response submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_response']) && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $feedback_id = $_POST['feedback_id'];
    $response_text = $_POST['response_text'];
    
    try {
        // Insert or update response
        $checkQuery = "SELECT * FROM feedback_responses WHERE feedback_id = :feedback_id";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bindParam(':feedback_id', $feedback_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update existing response
            $query = "UPDATE feedback_responses 
                      SET response_text = :response_text, 
                          responded_by = :responded_by,
                          response_date = CURRENT_TIMESTAMP
                      WHERE feedback_id = :feedback_id";
        } else {
            // Insert new response
            $query = "INSERT INTO feedback_responses 
                      (feedback_id, response_text, responded_by, response_date)
                      VALUES 
                      (:feedback_id, :response_text, :responded_by, CURRENT_TIMESTAMP)";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':feedback_id', $feedback_id, PDO::PARAM_INT);
        $stmt->bindParam(':response_text', $response_text, PDO::PARAM_STR);
        $stmt->bindParam(':responded_by', $organization_head_user_id, PDO::PARAM_INT);
        
        $result = $stmt->execute();
        
        if ($result) {
            // Update feedback status to "responded"
            $updateQuery = "UPDATE feedback SET status = 'responded' WHERE feedback_id = :feedback_id";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bindParam(':feedback_id', $feedback_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $logClass->logActivity($organization_head_user_id, "Responded to feedback ID: $feedback_id");
            $_SESSION['message'] = "Response submitted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to submit response.";
            $_SESSION['message_type'] = 'danger';
        }
    } catch (PDOException $e) {
        error_log("Database error in submitting feedback response: " . $e->getMessage());
        $_SESSION['message'] = "Database error occurred: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    // Redirect to refresh the page
    header("Location: organization-head-feedback.php");
    exit();
}

// Get all feedback with filtering
try {
    // Base query to get feedback with user and product details
    $query = "SELECT f.feedback_id, f.feedback_text, f.rating, f.created_at, f.status,
              u.first_name, u.last_name, u.email,
              p.name AS product_name, p.product_id,
              (SELECT response_text FROM feedback_responses WHERE feedback_id = f.feedback_id) AS response_text
              FROM feedback f
              JOIN users u ON f.user_id = u.user_id
              LEFT JOIN products p ON f.product_id = p.product_id";
    
    $conditions = [];
    $params = [];
    
    // Add search condition if search parameter is provided
    if (!empty($search)) {
        $conditions[] = "(f.feedback_text LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR p.name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Add rating filter if provided
    if ($rating_filter > 0) {
        $conditions[] = "f.rating = :rating";
        $params[':rating'] = $rating_filter;
    }
    
    // Add date range filters if provided
    if (!empty($date_from)) {
        $conditions[] = "DATE(f.created_at) >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if (!empty($date_to)) {
        $conditions[] = "DATE(f.created_at) <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    // Combine conditions if any
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Add ordering
    $query .= " ORDER BY f.created_at DESC";
    
    // Count total feedback for pagination
    $countQuery = "SELECT COUNT(*) FROM (" . $query . ") as counted";
    $stmt = $conn->prepare($countQuery);
    
    // Bind parameters for count query
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $totalFeedbacks = $stmt->fetchColumn();
    
    // Add pagination
    $query .= " LIMIT :offset, :limit";
    
    // Prepare and execute the main query
    $stmt = $conn->prepare($query);
    
    // Bind parameters for main query
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total pages for pagination
    $totalPages = ceil($totalFeedbacks / $limit) ?: 1; // Ensure at least 1 page
    
} catch (PDOException $e) {
    error_log("Database error in getting feedback: " . $e->getMessage());
    $_SESSION['message'] = "Error retrieving feedback. Please try again later.";
    $_SESSION['message_type'] = 'danger';
    $feedbacks = [];
    $totalFeedbacks = 0;
    $totalPages = 1;
}

// Get feedback statistics
try {
    // Total feedback count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM feedback");
    $stmt->execute();
    $totalFeedbackCount = $stmt->fetchColumn();
    
    // Average rating
    $stmt = $conn->prepare("SELECT AVG(rating) FROM feedback");
    $stmt->execute();
    $avgRating = $stmt->fetchColumn() ?: 0;
    
    // Count by rating
    $ratingCounts = [];
    for ($i = 1; $i <= 5; $i++) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM feedback WHERE rating = ?");
        $stmt->execute([$i]);
        $ratingCounts[$i] = $stmt->fetchColumn();
    }
    
    // Unresponded feedback count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM feedback WHERE status = 'pending' OR status IS NULL");
    $stmt->execute();
    $unrespondedCount = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Database error in getting feedback statistics: " . $e->getMessage());
    $totalFeedbackCount = 0;
    $avgRating = 0;
    $ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $unrespondedCount = 0;
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    if (isset($_POST['logout'])) {
        $logClass->logActivity($organization_head_user_id, "Organization Head logged out.");
        session_unset();
        session_destroy();
        header("Location: organization-head-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Organization Head Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/style/admin.css">
    <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
    <link rel="stylesheet" href="../../public/style/organization-head-dashboard.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Improved header */
        .organization-header {
            background: linear-gradient(
         #1a8754 100%
  );            color: white;
            padding: 15px 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .organization-badge {
            background-color: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .table-container:hover {
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            transform: translateY(-3px);
        }
        
        .table thead th {
            background-color: #198754; /* Solid green color instead of gradient */
            color: white;
            font-weight: 600;
            padding: 12px 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 135, 84, 0.05);
            cursor: pointer;
            transform: translateX(3px);
            transition: all 0.2s ease;
        }
        
        .card-stats {
            transition: all 0.3s ease;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
            height: 100%;
            border: none;
        }
        
        .card-stats:hover {
            transform: translateY(-7px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .card-stats .card-header {
            background: linear-gradient(135deg, #155d27 0%, #198754 100%);
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            border: none;
        }
        
        .card-stats .card-body {
            padding: 1.8rem;
        }
        
        .stats-icon {
            font-size: 2rem;
            margin-right: 0.8rem;
            color: #198754;
            opacity: 0.8;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #198754;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .data-filter-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 1.8rem;
            margin-bottom: 2rem;
            border-left: 5px solid #198754;
            transition: all 0.3s ease;
        }
        
        .data-filter-card:hover {
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
        
        .feedback-card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            border: none;
            transition: all 0.3s ease;
        }
        
        .feedback-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .feedback-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .feedback-card .card-footer {
            background: #f8f9fa;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 15px 20px;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.25rem;
        }
        
        .rating-filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            margin-right: 0.5rem;
            background-color: white;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .rating-filter-btn:hover, .rating-filter-btn.active {
            background-color: #198754;
            color: white;
            border-color: #198754;
        }
        
        .rating-filter-btn .bi-star-fill {
            color: #ffc107;
        }
        
        .rating-filter-btn:hover .bi-star-fill, .rating-filter-btn.active .bi-star-fill {
            color: white;
        }
        
        .btn-respond {
            background: linear-gradient(135deg, #20c997 0%, #198754 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(32,201,151,0.3);
            border-radius: 50px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-respond:hover {
            background: linear-gradient(135deg, #1cbb8c 0%, #16784c 100%);
            color: white;
            box-shadow: 0 6px 15px rgba(32,201,151,0.4);
            transform: translateY(-2px);
        }
        
        .feedback-response {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid #20c997;
        }
        
        .feedback-status {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-responded {
            background-color: #198754;
            color: white;
        }
        
        /* Form styling */
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.2);
            transition: all 0.2s ease;
        }
        
        .pagination .page-link:hover {
            background-color: rgba(25, 135, 84, 0.1);
            border-color: rgba(25, 135, 84, 0.2);
            transform: translateY(-2px);
        }
        
        .pagination .active .page-link {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }
        
        /* Rating gauge styling */
        .rating-gauge {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .rating-gauge-fill {
            height: 100%;
            background-color: #20c997;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        main {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Organization Header -->
    <div class="organization-header text-center">
        <h2><i class="bi bi-building"></i> ORGANIZATION MANAGEMENT SYSTEM
            <span class="organization-badge">Organization Head Access</span>
        </h2>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../global/organization-head-sidebar.php'; ?>

            <!-- Main Content -->
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-success"><i class="bi bi-chat-dots-fill"></i> Customer Feedback</h1>
                    <form method="POST" class="ml-3" onsubmit="return confirm('Are you sure you want to logout?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="logout" class="btn btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php 
                        unset($_SESSION['message']); 
                        unset($_SESSION['message_type']);
                    ?>
                <?php endif; ?>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-chat-dots stats-icon"></i> Total Feedback</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= $totalFeedbackCount ?></div>
                                <div class="stats-label">Submitted by customers</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-star stats-icon"></i> Average Rating</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= number_format($avgRating, 1) ?> <small>/ 5</small></div>
                                <div class="rating-stars">
                                    <?php
                                    $fullStars = floor($avgRating);
                                    $halfStar = $avgRating - $fullStars >= 0.5;
                                    
                                    for ($i = 1; $i <= $fullStars; $i++) {
                                        echo '<i class="bi bi-star-fill"></i>';
                                    }
                                    
                                    if ($halfStar) {
                                        echo '<i class="bi bi-star-half"></i>';
                                        $i++;
                                    }
                                    
                                    for (; $i <= 5; $i++) {
                                        echo '<i class="bi bi-star"></i>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-graph-up stats-icon"></i> Rating Distribution</h5>
                            </div>
                            <div class="card-body">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <?php 
                                    $percentage = $totalFeedbackCount > 0 ? ($ratingCounts[$i] / $totalFeedbackCount) * 100 : 0;
                                    ?>
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="mr-2" style="width: 40px;"><?= $i ?> <i class="bi bi-star-fill" style="color: #ffc107;"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="rating-gauge">
                                                <div class="rating-gauge-fill" style="width: <?= $percentage ?>%;"></div>
                                            </div>
                                        </div>
                                        <div class="ml-2" style="width: 40px;"><?= $ratingCounts[$i] ?></div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-stats">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-hourglass stats-icon"></i> Pending Responses</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="stats-number"><?= $unrespondedCount ?></div>
                                <div class="stats-label">Waiting for response</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Feedback List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-chat-text"></i> Customer Feedback List</h5>
                            </div>
                            
                            <!-- Search and Filter Section -->
                            <div class="card-body pb-0">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <form method="GET" action="" class="form-inline">
                                            <div class="form-group mr-3">
                                                <label for="search" class="sr-only">Search</label>
                                                <input type="text" id="search" name="search" class="form-control" 
                                                       placeholder="Search feedback..." value="<?= htmlspecialchars($search) ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                            <a href="organization-head-feedback.php" class="btn btn-outline-secondary ml-2">
                                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                                            </a>
                                        </form>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-end">
                                            <div class="rating-filter-buttons">
                                                <a href="?rating=0<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                                   class="rating-filter-btn <?= $rating_filter == 0 ? 'active' : '' ?>">All</a>
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <a href="?rating=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                                       class="rating-filter-btn <?= $rating_filter == $i ? 'active' : '' ?>">
                                                        <?= $i ?> <i class="bi bi-star-fill"></i>
                                                    </a>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <form method="GET" action="" class="form-inline">
                                            <div class="form-group mr-2">
                                                <label for="date_from" class="mr-2">From:</label>
                                                <input type="date" id="date_from" name="date_from" class="form-control" 
                                                       value="<?= htmlspecialchars($date_from) ?>">
                                            </div>
                                            <div class="form-group mr-2">
                                                <label for="date_to" class="mr-2">To:</label>
                                                <input type="date" id="date_to" name="date_to" class="form-control" 
                                                       value="<?= htmlspecialchars($date_to) ?>">
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-calendar-check"></i> Filter Dates
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <button id="exportCSV" class="btn btn-success">
                                            <i class="bi bi-download"></i> Export Feedback
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <?php if (empty($feedbacks)): ?>
                                    <div class="alert alert-info">
                                        No feedback found. <?= !empty($search) ? 'Try a different search term.' : '' ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Feedback Cards -->
                                    <?php foreach ($feedbacks as $feedback): ?>
                                        <div class="feedback-card card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="font-weight-bold"><?= htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']) ?></span>
                                                    <?php if (isset($feedback['status'])): ?>
                                                        <span class="feedback-status status-<?= $feedback['status'] ?>">
                                                            <?= ucfirst($feedback['status']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="feedback-status status-pending">Pending</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $feedback['rating']): ?>
                                                            <i class="bi bi-star-fill"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <strong>Product:</strong> <?= htmlspecialchars($feedback['product_name'] ?? 'General Feedback') ?>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Feedback:</strong>
                                                    <p class="mt-2"><?= nl2br(htmlspecialchars($feedback['feedback_text'])) ?></p>
                                                </div>
                                                
                                                <?php if (!empty($feedback['response_text'])): ?>
                                                    <div class="feedback-response">
                                                        <strong><i class="bi bi-reply-fill"></i> Your Response:</strong>
                                                        <p class="mt-2"><?= nl2br(htmlspecialchars($feedback['response_text'])) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (empty($feedback['response_text'])): ?>
                                                    <button type="button" class="btn btn-respond mt-2 respond-btn"
                                                            data-toggle="modal" 
                                                            data-target="#responseModal" 
                                                            data-feedback-id="<?= $feedback['feedback_id'] ?>"
                                                            data-customer-name="<?= htmlspecialchars($feedback['first_name'] . ' ' . $feedback['last_name']) ?>"
                                                            data-product-name="<?= htmlspecialchars($feedback['product_name'] ?? 'General Feedback') ?>"
                                                            data-feedback-text="<?= htmlspecialchars($feedback['feedback_text']) ?>">
                                                        <i class="bi bi-reply"></i> Respond
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer text-muted">
                                                <i class="bi bi-clock"></i> <?= date('F d, Y h:i A', strtotime($feedback['created_at'])) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Pagination -->
                                    <nav>
                                        <ul class="pagination justify-content-center mt-4">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&rating=<?= $rating_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">First</a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&rating=<?= $rating_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&rating=<?= $rating_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&rating=<?= $rating_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Next</a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&rating=<?= $rating_filter ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">Last</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                        <p class="text-center">Page <?= $page ?> of <?= $totalPages ?></p>
                                    </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1" role="dialog" aria-labelledby="responseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="responseModalLabel"><i class="bi bi-reply-fill"></i> Respond to Feedback</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="feedback_id" id="modal_feedback_id">
                        
                        <div class="form-group">
                            <label>Customer Name:</label>
                            <p class="form-control-plaintext" id="modal_customer_name"></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Product:</label>
                            <p class="form-control-plaintext" id="modal_product_name"></p>
                        </div>
                        
                        <div class="form-group">
                            <label>Feedback:</label>
                            <div class="p-3 bg-light rounded" id="modal_feedback_text"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="response_text">Your Response:</label>
                            <textarea class="form-control" id="response_text" name="response_text" rows="5" required
                                      placeholder="Write your response to the customer feedback here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_response" class="btn btn-success">
                            <i class="bi bi-send"></i> Submit Response
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle respond button click
            $('.respond-btn').click(function() {
                const feedbackId = $(this).data('feedback-id');
                const customerName = $(this).data('customer-name');
                const productName = $(this).data('product-name');
                const feedbackText = $(this).data('feedback-text');
                
                $('#modal_feedback_id').val(feedbackId);
                $('#modal_customer_name').text(customerName);
                $('#modal_product_name').text(productName);
                $('#modal_feedback_text').text(feedbackText);
            });
            
            // Export to CSV
            $('#exportCSV').click(function() {
                // Collect feedback data
                const feedbackData = [];
                
                // Add header row
                feedbackData.push(['Customer Name', 'Product', 'Rating', 'Feedback', 'Date', 'Status', 'Response']);
                
                // Add feedback rows
                $('.feedback-card').each(function() {
                    const customerName = $(this).find('.card-header span.font-weight-bold').text().trim();
                    const status = $(this).find('.feedback-status').text().trim();
                    const rating = $(this).find('.rating-stars .bi-star-fill').length;
                    const product = $(this).find('.card-body div').eq(0).text().replace('Product:', '').trim();
                    const feedback = $(this).find('.card-body div').eq(1).find('p').text().trim();
                    const response = $(this).find('.feedback-response p').text().trim() || 'No response yet';
                    const date = $(this).find('.card-footer').text().trim();
                    
                    // Format data for CSV (handle commas, quotes)
                    const formatForCSV = (text) => {
                        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                            return `"${text.replace(/"/g, '""')}"`;
                        }
                        return text;
                    };
                    
                    feedbackData.push([
                        formatForCSV(customerName),
                        formatForCSV(product),
                        rating,
                        formatForCSV(feedback),
                        formatForCSV(date),
                        formatForCSV(status),
                        formatForCSV(response)
                    ]);
                });
                
                // Create CSV content
                const csvContent = feedbackData.map(row => row.join(',')).join('\n');
                
                // Create download link
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', `feedback_report_${new Date().toISOString().slice(0,10)}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</body>
</html>
