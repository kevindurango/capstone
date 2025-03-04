<?php
// Start the session to track login status
session_start();

// Check if the user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: admin-login.php");
    exit();
}

// Include the User and Log models
require_once '../../models/User.php';
require_once '../../models/Log.php'; // Include the Log class

// Instantiate the User model and Log model
$userClass = new User();
$logClass = new Log(); // Create an instance of the Log class

// Fetch all users and roles
$users = $userClass->getUsers();
$roles = $userClass->getRoles();

// Generate a CSRF token if one doesn't already exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // Add User
    if (isset($_POST['add_user']) && !empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['role_id'])) {
        $userClass->addUser($_POST['username'], $_POST['email'], $_POST['password'], $_POST['role_id'], $_POST['first_name'], $_POST['last_name'], $_POST['contact_number'], $_POST['address']);
        
        // Log the action
        $logClass->logActivity($_SESSION['user_id'], 'Added new user: ' . $_POST['username']);

        header("Location: user-management.php");
        exit();
    }

    // Edit User
    if (isset($_POST['edit_user'])) {
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        $userClass->updateUser($_POST['user_id'], $_POST['username'], $_POST['email'], $password, $_POST['role_id'], $_POST['first_name'], $_POST['last_name'], $_POST['contact_number'], $_POST['address']);
        
        // Log the action
        $logClass->logActivity($_SESSION['user_id'], 'Edited user: ' . $_POST['username']);

        header("Location: user-management.php");
        exit();
    }

    // Delete User
    if (isset($_POST['delete_user'])) {
        $userClass->deleteUser($_POST['user_id']);
        
        // Log the action
        $logClass->logActivity($_SESSION['user_id'], 'Deleted user with ID: ' . $_POST['user_id']);

        header("Location: user-management.php");
        exit();
    }

    // Logout
    if (isset($_POST['logout'])) {
        // Log the logout action
        $logClass->logActivity($_SESSION['user_id'], 'Admin logged out');

        // Destroy session and log out the user
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
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../public/style/admin.css">
  <link rel="stylesheet" href="../../public/style/admin-sidebar.css">
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <?php include '../global/admin-sidebar.php'; ?>

      <!-- Main Content -->
      <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
        <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2 text-success">User Management</h1>
          <div class="d-flex">
            <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
              <i class="bi bi-plus-lg"></i> Add New User
            </button>
            <form method="POST" class="ml-3">
              <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
              <button type="submit" name="logout" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
              </button>
            </form>
          </div>
        </div>

        <!-- User Table -->
        <div class="card custom-card table-container">
          <div class="card-body">
            <h5 class="card-title">User List</h5>
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
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                          <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                          <button type="submit" name="delete_user" class="btn btn-danger btn-sm btn-action" 
                                  onclick="return confirm('Are you sure you want to delete this user?');">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
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
                  <select name="role_id" class="form-control mb-3" required>
                    <?php foreach ($roles as $role): ?>
                      <option value="<?= $role['role_id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                    <?php endforeach; ?>
                  </select>

                  <!-- Additional fields for personal information -->
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

                  <!-- Additional fields for personal information -->
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
      document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
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
    });
  </script>
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
