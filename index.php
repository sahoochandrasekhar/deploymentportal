<link rel="icon" type="image/png" href="favicon.ico">
<?php

session_start();

require_once 'functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); 
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'config.php';  
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        // Log the login attempt (successful or unsuccessful)
        logAction(null, "Login Attempt", "User with username '$username' attempted to log in.");

        // Check if the username exists in the database
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if the password matches
            if (password_verify($password, $user['password'])) {
                
                // Check if the account is approved
                if ($user['status'] === 'approved') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['is_admin'] = $user['is_admin']; // Store user role
                    
                    // Log the successful login attempt
                    logAction($user['id'], "User Logged In", "User with username '$username' logged in successfully.");
                    
                    // Redirect to the dashboard based on user role (admin or normal user)
                    if ($user['is_admin'] == 1) {
                        header("Location: dashboard.php"); // Admin dashboard (single dashboard for both roles)
                    } else {
                        header("Location: dashboard.php"); // Normal user dashboard (same dashboard)
                    }
                    exit;
                } else {
                    // Account is pending approval
                    $error = "Your account is pending approval by the manager.";
                    // Log failed attempt due to account approval status
                    logAction($user['id'], "Login Failed - Pending Approval", "User '$username' attempted to log in but the account is pending approval.");
                }
            } else {
                // Invalid password
                $error = "Invalid username or password.";
                // Log failed attempt due to incorrect password
                logAction(null, "Login Failed - Incorrect Password", "User '$username' provided an incorrect password.");
            }
        } else {
            // User not found
            $error = "Invalid username or password.";
            // Log failed attempt due to invalid username
            logAction(null, "Login Failed - User Not Found", "User '$username' attempted to log in but the username does not exist.");
        }
    } else {
        // Fields are empty
        $error = "Please fill in all fields.";
        // Log failed attempt due to empty fields
        logAction(null, "Login Failed - Empty Fields", "User attempted to log in with empty fields.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <!-- Display error message if there is one -->
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="index.php" method="POST">
            <div class="input-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Login</button>
        </form>

        <!-- Register Link -->
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
