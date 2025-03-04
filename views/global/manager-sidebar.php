<?php
$currentPage = basename($_SERVER['PHP_SELF']); 
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="sidebar-sticky">
        <!-- Logo Section -->
        <div class="logo-container">
            <img src="../../public/assets/logo.png" alt="Farmers Market Logo" class="logo">
        </div>

        <h4 class="sidebar-heading px-3 mt-4 mb-3 text-white">Manager Dashboard</h4>

        <!-- Navigation Links -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'manager-dashboard.php') ? 'active' : '' ?>" href="manager-dashboard.php">
                    <i class="bi bi-house-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'manager-user-management.php') ? 'active' : '' ?>" href="manager-user-management.php">
                    <i class="bi bi-people-fill"></i> User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'manager-sales-management.php') ? 'active' : '' ?>" href="manager-sales-management.php">
                    <i class="bi bi-cash-coin"></i> Sales
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'manager-product.php') ? 'active' : '' ?>" href="manager-product.php">
                    <i class="bi bi-box-fill"></i> Product Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'manager-order-oversight.php') ? 'active' : '' ?>" href="manager-order-oversight.php">
                    <i class="bi bi-list-check"></i> Order Oversight
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'manager-pickup-management.php') ? 'active' : '' ?>" href="manager-pickup-management.php">
                    <i class="bi bi-truck"></i> Pick-Up Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($currentPage == 'manager-reports.php') ? 'active' : '' ?>" href="manager-reports.php">
                    <i class="bi bi-bar-chart-fill"></i> Reports
                </a>
            </li>
        </ul>
    </div>
</nav>
