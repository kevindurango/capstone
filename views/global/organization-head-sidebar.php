<?php
$currentPage = basename($_SERVER['PHP_SELF']); 
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="sidebar-sticky">
        <!-- Logo Section -->
        <div class="logo-container">
            <img src="../../public/assets/logo.png" alt="Farmers Market Logo" class="logo">
        </div>

        <h4 class="sidebar-heading px-3 mt-4 mb-3 text-white">Organization Head Dashboard</h4>

        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'organization-head-dashboard.php') ? 'active' : '' ?>" href="organization-head-dashboard.php">
                    <i class="bi bi-house-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'organization-head-order-management.php') ? 'active' : '' ?>" href="organization-head-order-management.php">
                    <i class="bi bi-list-check"></i> Order Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'organization-head-sales-report.php') ? 'active' : '' ?>" href="organization-head-sales-report.php">
                    <i class="bi bi-graph-up"></i> Sales Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'organization-head-farmers.php') ? 'active' : '' ?>" href="organization-head-farmers.php">
                    <i class="bi bi-people-fill"></i> Farmers Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'organization-head-feedback.php') ? 'active' : '' ?>" href="organization-head-feedback.php">
                    <i class="bi bi-chat-dots-fill"></i> Customer Feedback
                </a>
            </li>
        </ul>
    </div>
</nav>