<?php
// Log Action Function
function logAction($user_id, $action, $details) {
    global $pdo;  // Assuming you have a PDO connection in $pdo

    // Fetch the username based on the user_id
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists
    if ($user) {
        $username = $user['username'];
    } else {
        $username = 'Unknown User'; // Fallback in case user is not found
    }

    // Insert log into the logs table
    $sql = "INSERT INTO logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'],  // Get the user's IP address
        $username                 // Insert the username into the user_agent field
    ]);
}
?>
