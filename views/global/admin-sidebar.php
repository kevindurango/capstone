<?php
$currentPage = basename($_SERVER['PHP_SELF']); 
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="sidebar-sticky">
        <!-- Logo Section -->
        <div class="logo-container">
            <img src="../../public/assets/logo.png" alt="Farmers Market Logo" class="logo">
        </div>

        <h4 class="sidebar-heading">Admin Dashboard</h4>

        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <li class="nav-item <?= ($currentPage == 'admin-dashboard.php') ? 'active' : '' ?>">
                <a class="nav-link <?= ($currentPage == 'admin-dashboard.php') ? 'active' : '' ?>" href="admin-dashboard.php">
                    <i class="bi bi-house-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item <?= ($currentPage == 'user-management.php') ? 'active' : '' ?>">
                <a class="nav-link <?= ($currentPage == 'user-management.php') ? 'active' : '' ?>" href="user-management.php">
                    <i class="bi bi-people-fill"></i> User Management
                </a>
            </li>
            <li class="nav-item <?= ($currentPage == 'activity-logs.php') ? 'active' : '' ?>">
                <a class="nav-link <?= ($currentPage == 'activity-logs.php') ? 'active' : '' ?>" href="activity-logs.php">
                    <i class="bi bi-clock-history"></i> Activity Logs
                </a>
            </li>
            <li class="nav-item <?= ($currentPage == 'product-management.php') ? 'active' : '' ?>">
                <a class="nav-link <?= ($currentPage == 'product-management.php') ? 'active' : '' ?>" href="product-management.php">
                    <i class="bi bi-box-seam"></i> Product Management
                </a>
            </li>
            <li class="nav-item <?= ($currentPage == 'order-oversight.php') ? 'active' : '' ?>">
                <a class="nav-link <?= ($currentPage == 'order-oversight.php') ? 'active' : '' ?>" href="order-oversight.php">
                    <i class="bi bi-cart-check"></i> Order Oversight
                </a>
            </li>
            <li class="nav-item <?= ($currentPage == 'pickup-management.php') ? 'active' : '' ?>">
                <a class="nav-link <?= ($currentPage == 'pickup-management.php') ? 'active' : '' ?>" href="pickup-management.php">
                    <i class="bi bi-truck"></i> Pick-Up Management
                </a>
            </li>
            <li class="nav-item <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">
                <a class="nav-link <?= ($currentPage == 'reports.php') ? 'active' : '' ?>" href="reports.php">
                    <i class="bi bi-graph-up"></i> Reports
                </a>
            </li>
        </ul>
    </div>
</nav>
