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

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Delete the user from the database
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);

    // Log the deletion action
    logAction($_SESSION['user_id'], "Deleted User", "User with ID '$user_id' has been deleted.");

    // Set a message to show after the page refresh
    $message = "User has been successfully deleted.";

    // Redirect back to the manage users page with the success message
    header("Location: approve_users.php?message=" . urlencode($message));
    exit;
}
?>
