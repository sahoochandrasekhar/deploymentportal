<?php
session_start();

// Check if the user is an admin
if ($_SESSION['is_admin'] != 1) {
    header("Location: index.php"); // Redirect to login if not an admin
    exit;
}

require_once 'config.php';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Fetch user details
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "User not found!";
        exit;
    }

    // Handle form submission for updating user details
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check if role is set, otherwise default to '2' (User)
        $role = isset($_POST['role']) ? $_POST['role'] : '2'; // Default to '2' (User) if not set

        // Convert role to is_admin value
        if ($role == 'admin') {
            $is_admin = 1; // Admin role -> 1
        } else {
            $is_admin = 2; // User role -> 2
        }

        // If password is provided, hash it
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET fullname = ?, username = ?, email = ?, password = ?, is_admin = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fullname, $username, $email, $hashed_password, $is_admin, $user_id]);
        } else {
            $sql = "UPDATE users SET fullname = ?, username = ?, email = ?, is_admin = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fullname, $username, $email, $is_admin, $user_id]);
        }

        $message = "User details have been updated.";
    }
}
?>

<?php include('header.php'); ?>
<?php include('sidebar.php'); ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Update User Details</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>

                    <!-- Update User Form -->
                    <form action="update_user.php?id=<?= $user['id'] ?>" method="POST">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h3 class="card-title">Update User Details</h3>
                            </div>
                            <div class="card-body">
                                <!-- Full Name (Readonly) -->
                                <div class="form-group">
                                    <label for="fullname">Full Name:</label>
                                    <input type="text" name="fullname" id="fullname" class="form-control" value="<?= $user['fullname'] ?>" required readonly>
                                </div>

                                <!-- Email (Readonly) -->
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?= $user['email'] ?>" required readonly>
                                </div>

                                <!-- Username (Readonly) -->
                                <div class="form-group">
                                    <label for="username">Username:</label>
                                    <input type="text" name="username" id="username" class="form-control" value="<?= $user['username'] ?>" required readonly>
                                </div>

                                <!-- New Password (leave empty if not changing) -->
                                <div class="form-group">
                                    <label for="password">New Password (leave empty if not changing):</label>
                                    <input type="password" name="password" id="password" class="form-control">
                                </div>

                                <!-- Role -->
                                <div class="form-group">
                                    <label for="role">Role:</label>
                                    <select name="role" id="role" class="form-control" required>
                                        <option value="admin" <?= ($user['is_admin'] == 1 || isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                        <option value="user" <?= ($user['is_admin'] == 2 || isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : '' ?>>User</option>
                                    </select>
                                </div>

                                <!-- Back Button -->
                                <a href="approve_users.php" class="btn btn-secondary">Back</a>

                                <!-- Update Button -->
                                <button type="submit" class="btn btn-primary float-right">Update User</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('footer.php'); ?>
