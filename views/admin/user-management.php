<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Include models
require_once '../../models/User.php';
require_once '../../models/Log.php';

$userClass = new User();
$logClass  = new Log();

// Fetch all users and roles
$users = $userClass->getUsers();
$roles = $userClass->getRoles();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Near the top of the file, add this to show messages
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-' . $_SESSION['message_type'] . ' alert-dismissible fade show" role="alert">
        ' . $_SESSION['message'] . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

    // Add User
    if (isset($_POST['add_user']) && !empty($_POST['username']) && !empty($_POST['email']) &&
        !empty($_POST['password']) && !empty($_POST['role_id'])) {
        
        $userId = $userClass->addUser(
            $_POST['username'],
            $_POST['email'],
            $_POST['password'],
            $_POST['role_id'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['contact_number'],
            $_POST['address']
        );
        
        $logClass->logActivity($_SESSION['user_id'], 'Added new user: ' . $_POST['username']);
        header("Location: user-management.php");
        exit();
    }

    // Edit User
    if (isset($_POST['edit_user'])) {
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        
        // Update the user details
        $userClass->updateUser(
            $_POST['user_id'],
            $_POST['username'],
            $_POST['email'],
            $password,
            $_POST['role_id'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['contact_number'],
            $_POST['address']
        );
        
        $logClass->logActivity($_SESSION['user_id'], 'Edited user: ' . $_POST['username']);
        header("Location: user-management.php");
        exit();
    }

    // Delete User handler
    if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
        try {
            $userId = $_POST['user_id'];
            
            // Use admin_user_id from the session for logging
            $adminUserId = $_SESSION['admin_user_id'] ?? $_SESSION['user_id'] ?? null;
            
            // Delete the user
            if ($userClass->deleteUser($userId)) {
                if ($adminUserId) {
                    $logClass->logActivity($adminUserId, 'Deleted user ID: ' . $userId);
                }
                $_SESSION['message'] = "User deleted successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to delete user.";
                $_SESSION['message_type'] = "danger";
            }
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            $_SESSION['message'] = "Error deleting user: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
        
        header("Location: user-management.php");
        exit();
    }

    // Logout
    if (isset($_POST['logout'])) {
        $logClass->logActivity($_SESSION['user_id'], 'Admin logged out');
        session_unset();
        session_destroy();
        header("Location: admin-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - Admin Dashboard</title>
  <!-- Bootstrap and Icon Libraries -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- Custom Styles -->
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
  <link rel="stylesheet" href="../../public/style/user-management.css">
  <style>
    .admin-header {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
        padding: 10px 0;
    }
    .admin-badge {
        background-color: #6a11cb;
        color: white;
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 4px;
        margin-left: 10px;
    }
  </style>
</head>
<body>
  <!-- Add Admin Header -->
  <div class="admin-header text-center">
    <h2><i class="bi bi-shield-lock"></i> ADMIN CONTROL PANEL <span class="admin-badge">Restricted Access</span></h2>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../global/admin-sidebar.php'; ?>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4 py-4">
        <!-- Add breadcrumb -->
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">User Management</li>
          </ol>
        </nav>

        <!-- Updated header section with page-header class -->
        <div class="page-header">
          <h1 class="h2"><i class="bi bi-people"></i> User Management</h1>
          <div class="d-flex align-items-center">
            <button class="btn btn-success mr-2" onclick="generateReport()">
              <i class="bi bi-file-earmark-text"></i> Generate Report
            </button>
            <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#addUserModal">
              <i class="bi bi-plus-lg"></i> Add New User
            </button>
            <!-- Updated logout button styling -->
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <button type="submit" name="logout" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
              </button>
            </form>
          </div>
        </div>

        <!-- User Table with Search and Filter -->
        <div class="card custom-card table-container mb-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="card-title">User List</h5>
              <div class="d-flex">
                <input type="text" id="searchUser" class="form-control mr-2" placeholder="Search users...">
                <select id="roleFilter" class="form-control">
                  <option value="">All Roles</option>
                  <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['role_name'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-striped table-bordered mb-0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Contact Number</th>
                    <th>Address</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $user): ?>
                  <tr>
                    <td><?= htmlspecialchars($user['user_id']) ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                      <?php 
                        foreach ($roles as $role) {
                          if ($role['role_id'] == $user['role_id']) {
                              echo htmlspecialchars($role['role_name']);
                              break;
                          }
                        }
                      ?>
                    </td>
                    <td><?= htmlspecialchars($user['first_name']) ?></td>
                    <td><?= htmlspecialchars($user['last_name']) ?></td>
                    <td><?= htmlspecialchars($user['contact_number']) ?></td>
                    <td><?= htmlspecialchars($user['address']) ?></td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-action edit-btn" 
                              data-user-id="<?= $user['user_id'] ?>" 
                              data-username="<?= htmlspecialchars($user['username']) ?>" 
                              data-email="<?= htmlspecialchars($user['email']) ?>" 
                              data-role-id="<?= $user['role_id'] ?>"
                              data-first-name="<?= htmlspecialchars($user['first_name']) ?>"
                              data-last-name="<?= htmlspecialchars($user['last_name']) ?>"
                              data-contact-number="<?= htmlspecialchars($user['contact_number']) ?>"
                              data-address="<?= htmlspecialchars($user['address']) ?>">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button type="button" class="btn btn-danger btn-sm btn-action delete-btn" 
                              data-user-id="<?= $user['user_id'] ?>"
                              data-username="<?= htmlspecialchars($user['username']) ?>">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header">
                  <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
                  <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                  <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                  <select name="role_id" id="role_id" class="form-control mb-3" required>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="first_name" class="form-control mb-3" placeholder="First Name" required>
                  <input type="text" name="last_name" class="form-control mb-3" placeholder="Last Name" required>
                  <input type="text" name="contact_number" class="form-control mb-3" placeholder="Contact Number">
                  <textarea name="address" class="form-control mb-3" placeholder="Address"></textarea>
                </div>
                <div class="modal-footer">
                  <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header">
                  <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <input type="text" name="username" id="edit_username" class="form-control mb-3" placeholder="Username" required>
                  <input type="email" name="email" id="edit_email" class="form-control mb-3" placeholder="Email" required>
                  <input type="password" name="password" class="form-control mb-3" placeholder="Password (leave blank to keep current)">
                  <select name="role_id" id="edit_role_id" class="form-control mb-3" required>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="first_name" id="edit_first_name" class="form-control mb-3" placeholder="First Name" required>
                  <input type="text" name="last_name" id="edit_last_name" class="form-control mb-3" placeholder="Last Name" required>
                  <input type="text" name="contact_number" id="edit_contact_number" class="form-control mb-3" placeholder="Contact Number">
                  <textarea name="address" id="edit_address" class="form-control mb-3" placeholder="Address"></textarea>
                </div>
                <div class="modal-footer">
                  <button type="submit" name="edit_user" class="btn btn-warning">Update User</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Edit user functionality
      document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
          // Populate basic user fields
          document.getElementById('edit_user_id').value = button.dataset.userId;
          document.getElementById('edit_username').value = button.dataset.username;
          document.getElementById('edit_email').value = button.dataset.email;
          document.getElementById('edit_role_id').value = button.dataset.roleId;
          document.getElementById('edit_first_name').value = button.dataset.firstName;
          document.getElementById('edit_last_name').value = button.dataset.lastName;
          document.getElementById('edit_contact_number').value = button.dataset.contactNumber;
          document.getElementById('edit_address').value = button.dataset.address;

          $('#editUserModal').modal('show');
        });
      });

      // Search and filter functionality
      const searchInput = document.getElementById('searchUser');
      const roleFilter = document.getElementById('roleFilter');
      const tableRows = document.querySelectorAll('tbody tr');

      function filterTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const roleSelected = roleFilter.value.trim();

        tableRows.forEach(row => {
          const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase().trim();
          const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase().trim();
          const role = row.querySelector('td:nth-child(4)').textContent.trim();
          const firstName = row.querySelector('td:nth-child(5)').textContent.toLowerCase().trim();
          const lastName = row.querySelector('td:nth-child(6)').textContent.toLowerCase().trim();

          const matchesSearch = username.includes(searchTerm) || 
                              email.includes(searchTerm) || 
                              firstName.includes(searchTerm) || 
                              lastName.includes(searchTerm);
          
          const matchesRole = !roleSelected || 
                             role.toLowerCase() === roleSelected.toLowerCase();

          row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
        });
      }

      searchInput.addEventListener('input', filterTable);
      roleFilter.addEventListener('change', filterTable);

      // Report generation function
      window.generateReport = function() {
        const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
        
        let reportContent = `
          <style>
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f4f4f4; }
            h1 { color: #333; }
            .header { margin-bottom: 20px; }
            .generated-date { color: #666; }
          </style>
          <div class="header">
            <h1>User Management Report</h1>
            <p class="generated-date">Generated on: ${new Date().toLocaleString()}</p>
            <p>Total Users: ${visibleRows.length}</p>
          </div>
          <table>
            <thead>
              <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Full Name</th>
                <th>Contact</th>
                <th>Address</th>
              </tr>
            </thead>
            <tbody>
        `;

        visibleRows.forEach(row => {
          const username = row.cells[1].textContent;
          const email = row.cells[2].textContent;
          const role = row.cells[3].textContent;
          const firstName = row.cells[4].textContent;
          const lastName = row.cells[5].textContent;
          const contact = row.cells[6].textContent;
          const address = row.cells[7].textContent;

          reportContent += `
            <tr>
              <td>${username}</td>
              <td>${email}</td>
              <td>${role}</td>
              <td>${firstName} ${lastName}</td>
              <td>${contact}</td>
              <td>${address}</td>
            </tr>
          `;
        });

        reportContent += '</tbody></table>';

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
          <html>
            <head>
              <title>User Management Report</title>
            </head>
            <body>${reportContent}</body>
          </html>
        `);
        
        printWindow.document.close();
        setTimeout(() => {
          printWindow.print();
        }, 500);
      };

      // Delete user confirmation
      $('.delete-btn').click(function() {
          const userId = $(this).data('user-id');
          const username = $(this).data('username');
          
          if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
              // Create and submit a form dynamically
              const form = document.createElement('form');
              form.method = 'POST';
              form.action = 'user-management.php'; // Explicitly set the action
              form.innerHTML = `
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <input type="hidden" name="user_id" value="${userId}">
                  <input type="hidden" name="delete_user" value="1">
              `;
              document.body.appendChild(form);
              form.submit();
          }
      });
    });
  </script>
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
