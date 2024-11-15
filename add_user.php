<?php
session_start();

// Include the functions file where logAction() is defined
require_once 'functions.php';

// Check if the user is an admin
if ($_SESSION['is_admin'] != 1) {
    header("Location: index.php"); // Redirect to login if not an admin
    exit;
}

require_once 'config.php';

// Handle form submission for adding a new user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // Either 'admin' or 'user'

    // Check if the passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the password before storing it
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user into the database
        $sql = "INSERT INTO users (fullname, email, username, password, is_admin, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullname, $email, $username, $hashed_password, ($role === 'admin' ? 1 : 0)]);
        
        // Get the ID of the newly created user
        $new_user_id = $pdo->lastInsertId();
        
        // Log the action of adding a new user
        logAction($_SESSION['user_id'], "Added New User", "User with username '$username' was added.");

        $message = "New user has been added successfully.";
    }
    
}
?>

<?php include('header.php'); ?>

<!-- Sidebar -->
<?php include('sidebar.php'); ?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Add New User</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <!-- Success or Error Message -->
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php elseif (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <!-- User Registration Form -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title">Register New User</h3>
                        </div>
                        <div class="card-body">
                            <form action="add_user.php" method="POST" onsubmit="return validateForm()">
                                <!-- Full Name -->
                                <div class="form-group">
                                    <label for="fullname">Full Name:<span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" id="fullname" class="form-control" required>
                                </div>

                                <!-- Email -->
                                <div class="form-group">
                                    <label for="email">Email:<span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control" required>
                                </div>

                                <!-- Username -->
                                <div class="form-group">
                                    <label for="username">Username:<span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username" class="form-control" required>
                                </div>

                                <!-- Password -->
                                <div class="form-group">
                                    <label for="password">Password:<span class="text-danger">*</span></label>
                                    <input type="password" name="password" id="password" class="form-control" required>
                                </div>

                                <!-- Confirm Password -->
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password:<span class="text-danger">*</span></label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                </div>

                                <!-- Role -->
                                <div class="form-group">
                                    <label for="role">Role:<span class="text-danger">*</span></label>
                                    <select name="role" id="role" class="form-control" required>
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>

                                <!-- Back Button -->
                                <a href="approve_users.php" class="btn btn-secondary btn-block">Back</a>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary btn-block">Add User</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('footer.php'); ?>

<!-- Custom CSS -->
<style>
    .content-wrapper {
        padding: 20px;
    }

    .form-group label {
        font-weight: bold;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #007bff;
        border-bottom: none;
    }

    .card-body {
        padding: 30px;
    }

    .form-control {
        border-radius: 4px;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.12);
        border: 1px solid #ccc; /* Normal border */
    }

    .btn-block {
        font-size: 16px;
        padding: 10px;
    }

    .alert {
        margin-top: 20px;
    }

    .content-header h1 {
        font-size: 28px;
        font-weight: bold;
    }

    /* Red asterisk for required fields */
    .text-danger {
        color: red;
    }
</style>

<!-- JavaScript for Form Validation -->
<script>
    function validateForm() {
        let isValid = true; // Flag to track form validity
        let requiredFields = document.querySelectorAll('input[required], select[required]'); // Get all required inputs and selects
        let errorMessages = ""; // For gathering error messages

        // Loop through each required field and check if it's empty
        requiredFields.forEach(function(field) {
            if (field.value.trim() === "") {
                field.style.border = "1px solid red"; // Highlight the field with red border
                isValid = false; // Set validity to false
            } else {
                field.style.border = "1px solid #ccc"; // Reset border to normal if filled
            }
        });

        // Check if passwords match
        let password = document.getElementById("password").value;
        let confirmPassword = document.getElementById("confirm_password").value;
        if (password !== confirmPassword) {
            alert("Passwords do not match.");
            isValid = false;
        }

        if (!isValid) {
            alert("Please fill in all required fields.");
        }

        return isValid; // Allow or prevent form submission based on validity
    }
</script>
