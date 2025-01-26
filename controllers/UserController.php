<?php
require_once '../../models/User.php';

class UserController {

    private $userClass;

    public function __construct() {
        $this->userClass = new User();
    }

    // Handle the POST request for adding a new user
    public function addUser($username, $email, $password, $role_id) {
        if (!empty($username) && !empty($email) && !empty($password) && !empty($role_id)) {
            return $this->userClass->addUser($username, $email, $password, $role_id);
        }
        return false; // Return false if inputs are missing
    }

    // Get all users
    public function getUsers() {
        return $this->userClass->getUsers();
    }

    // Get all roles
    public function getRoles() {
        return $this->userClass->getRoles();
    }

    // Get role name by role_id
    public function getRoleName($role_id) {
        return $this->userClass->getRoleName($role_id);  // Calling getRoleName from User model
    }

    // Delete a user
    public function deleteUser($user_id) {
        return $this->userClass->deleteUser($user_id);
    }

    // Update a user
    public function updateUser($user_id, $username, $email, $role_id) {
        if (!empty($user_id) && !empty($username) && !empty($email) && !empty($role_id)) {
            return $this->userClass->updateUser($user_id, $username, $email, $role_id);
        }
        return false; // Return false if inputs are missing
    }
}

// Instantiate the controller
$userController = new UserController();

// Handle the form submission for adding a user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];

    // Try adding the user
    if ($userController->addUser($username, $email, $password, $role_id)) {
        // Redirect after successful addition
        header("Location: user-management.php");
        exit();
    } else {
        $error_message = "Error adding user. Please check the form fields and try again.";
    }
}

// Handle the form submission for updating a user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];

    // Try updating the user
    if ($userController->updateUser($user_id, $username, $email, $role_id)) {
        // Redirect after successful update
        header("Location: user-management.php");
        exit();
    } else {
        $error_message = "Error updating user. Please check the form fields and try again.";
    }
}

// Handle the deletion of a user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    // Try deleting the user
    if ($userController->deleteUser($user_id)) {
        // Redirect after successful deletion
        header("Location: user-management.php");
        exit();
    } else {
        $error_message = "Error deleting user. Please try again.";
    }
}

// Fetch users and roles
$users = $userController->getUsers();
$roles = $userController->getRoles();
?>
