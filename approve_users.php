<?php
session_start();

// Check if the user is an admin
if ($_SESSION['is_admin'] != 1) {
    header("Location: index.php"); // Redirect to login if not an admin
    exit;
}

require_once 'config.php';

// Get all users from the database
$sql = "SELECT * FROM users"; // Fetch all users, not just pending ones
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll();

// Log function to record actions
function logAction($user_id, $action, $details = "") {
    global $pdo;

    // Get IP Address and User Agent
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Prepare and execute the insert query for logging
    $sql = "INSERT INTO logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $action, $details, $ip_address, $user_agent]);
}

// Handle Approve or Reject action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $action = $_GET['action']; // Either 'approve' or 'reject'

    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        
        // Update status to 'approved' or 'rejected'
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $user_id]);

        // If the user is rejected, we also want to prevent them from logging in
        if ($status == 'rejected') {
            // Set any rejected user as 'inactive' or similar, to prevent login
            $sql = "UPDATE users SET active = 0 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
        }

        // Log the approval or rejection action
        logAction($_SESSION['user_id'], $action, "User ID $user_id has been $status.");

        // Set message and redirect with a delay to refresh the page
        $message = "User has been $status.";
        header("Location: approve_users.php?message=" . urlencode($message));  // Redirect to this page with message
        exit; // Stop further code execution
    }
}

// Handle Update or Delete action for users
if (isset($_GET['delete_id'])) {
    $delete_user_id = $_GET['delete_id'];
    
    // Ensure the user exists before deleting
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$delete_user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Log the deletion action
        logAction($_SESSION['user_id'], 'delete', "User ID $delete_user_id has been deleted.");

        // Delete related logs first to avoid foreign key issues
        $sql = "DELETE FROM logs WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$delete_user_id]);

        // Execute delete query for the user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$delete_user_id]);

        // Redirect after deletion
        header("Location: approve_users.php?message=User has been deleted.");
        exit;
    } else {
        // If user doesn't exist, show error
        header("Location: approve_users.php?message=User not found.");
        exit;
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
                    <h1>Manage Users and Approvals</h1>
                </div>
                <!-- Add User Button (Visible only for Admins) -->
                <?php if ($_SESSION['is_admin'] == 1): ?>
                    <div class="col-sm-6 text-right">
                        <a href="add_user.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Success Message -->
                    <?php if (isset($_GET['message'])): ?>
                        <div id="notification" class="notification"><?= htmlspecialchars($_GET['message']) ?></div>
                    <?php endif; ?>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">User Management</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="userTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Status</th>
                                            <th>Role</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td><?= $user['username'] ?></td>
                                                <td><?= $user['status'] ?></td>
                                                <td>
                                                    <?= ($user['is_admin'] == 1) ? 'Admin' : 'User'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['status'] == 'pending'): ?>
                                                        <!-- Approve Button for pending users -->
                                                        <a href="approve_users.php?action=approve&id=<?= $user['id'] ?>" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>

                                                        <!-- Reject Button for pending users -->
                                                        <a href="approve_users.php?action=reject&id=<?= $user['id'] ?>" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    <?php elseif ($user['status'] == 'rejected'): ?>
                                                        <!-- Only the Delete Button is shown for rejected users -->
                                                        <a href="approve_users.php?delete_id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- Update Button for approved users -->
                                                        <a href="update_user.php?id=<?= $user['id'] ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-edit"></i> Update
                                                        </a>

                                                        <!-- Delete Button for approved users -->
                                                        <a href="approve_users.php?delete_id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">
                                                            <i class="fas fa-trash-alt"></i> Delete
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('footer.php'); ?>

<!-- DataTables and jQuery Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        $('#userTable').DataTable({
            "paging": true,           // Enable pagination
            "lengthChange": true,     // Allow entries to be changed
            "searching": true,        // Enable search box
            "ordering": true,         // Enable sorting
            "info": true,             // Show table information
            "autoWidth": false,       // Disable automatic column width adjustment
            "responsive": true,       // Make the table responsive
            "lengthMenu": [5, 10, 15, 20], // Dropdown to select number of entries
            "pageLength": 10,         // Default number of entries per page
        });
    });

    // Display notification for a few seconds then hide it
    <?php if (isset($_GET['message'])): ?>
        $(document).ready(function() {
            $('#notification').fadeIn().delay(5000).fadeOut();
        });
    <?php endif; ?>
</script>

<!-- DataTables Stylesheet -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
