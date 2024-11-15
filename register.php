<link rel="icon" type="image/png" href="favicon.ico">
<?php
session_start();

// Include the database connection
require_once 'config.php';
require_once 'functions.php';  // Include the functions file for logging

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (!empty($fullname) && !empty($email) && !empty($username) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Check if username or email already exists
            $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Log the failed registration attempt due to duplicate username/email
                logAction(null, 'Failed Registration - Username/Email Taken', "User with username '$username' or email '$email' attempted to register, but the username or email is already taken.");
                
                $error = "Username or Email already taken.";
            } else {
                // Hash the password before saving
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert the user with 'pending' status and default is_admin = 2 (normal user)
                $sql = "INSERT INTO users (fullname, email, username, password, status, is_admin) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fullname, $email, $username, $hashed_password, 'pending', 2]); // 2 for normal user

                // Get the user ID of the newly created user
                $user_id = $pdo->lastInsertId();

                // Log the successful registration
                logAction($user_id, 'Successful Registration', "User with username '$username' registered successfully.");

                $success = "Registration successful. Your account is pending approval.";
            }
        } else {
            // Log the failed registration attempt due to password mismatch
            logAction(null, 'Failed Registration - Password Mismatch', "User with username '$username' attempted to register, but the passwords do not match.");
            
            $error = "Passwords do not match.";
        }
    } else {
        // Log the failed registration attempt due to empty fields
        logAction(null, 'Failed Registration - Empty Fields', "User attempted to register but did not fill in all required fields.");
        
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styling for input fields */
        .input-group input:required {
            border: 1px solid #ccc; /* Default border color */
        }

        /* Red border for invalid required fields */
        .input-group input.invalid {
            border: 1px solid red; /* Red border when validation fails */
        }

        /* Red asterisk for required fields */
        .input-group label .text-danger {
            color: red;
        }

        /* Error message styling */
        .error {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }

        /* Success message styling */
        .success {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>

        <!-- Display any error or success messages -->
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php elseif (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Registration form -->
        <form action="register.php" method="POST" onsubmit="return validateForm()">
            <!-- Full Name -->
            <div class="input-group">
                <label for="fullname">Full Name:<span class="text-danger">*</span></label>
                <input type="text" name="fullname" id="fullname" value="<?= isset($fullname) ? htmlspecialchars($fullname) : '' ?>" required>
            </div>

            <!-- Email -->
            <div class="input-group">
                <label for="email">Email:<span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
            </div>

            <!-- Username -->
            <div class="input-group">
                <label for="username">Username:<span class="text-danger">*</span></label>
                <input type="text" name="username" id="username" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required>
            </div>

            <!-- Password -->
            <div class="input-group">
                <label for="password">Password:<span class="text-danger">*</span></label>
                <input type="password" name="password" id="password" required>
            </div>

            <!-- Confirm Password -->
            <div class="input-group">
                <label for="confirm_password">Confirm Password:<span class="text-danger">*</span></label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>

            <!-- Submit Button -->
            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>

    <!-- JavaScript for Form Validation -->
    <script>
        function validateForm() {
            let isValid = true;
            let fields = document.querySelectorAll('.input-group input[required]'); // All required fields
            let password = document.getElementById('password');
            let confirmPassword = document.getElementById('confirm_password');

            // Reset all fields' border
            fields.forEach(function(field) {
                field.classList.remove('invalid');
            });

            // Check if all required fields are filled
            fields.forEach(function(field) {
                if (field.value.trim() === '') {
                    field.classList.add('invalid');
                    isValid = false;
                }
            });

            // Check if password and confirm password match
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('invalid');
                isValid = false;
            }

            if (!isValid) {
                alert("Please fill in all fields correctly.");
            }

            return isValid;
        }
    </script>
</body>
</html>
