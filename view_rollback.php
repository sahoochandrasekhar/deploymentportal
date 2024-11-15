<?php
session_start();

// Check if the user is an admin (uncomment and modify if needed)
// if ($_SESSION['is_admin'] != 1) {
//     header("Location: index.php"); // Redirect to login if not an admin
//     exit;
// }

require_once 'config.php';

if (isset($_GET['id'])) {
    $rollback_id = $_GET['id'];

    // Fetch rollback details with environment name
    $sql = "
        SELECT rollback.*, environments.name AS env_name 
        FROM rollback
        LEFT JOIN environments ON rollback.env_name = environments.id
        WHERE rollback.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rollback_id]);
    $rollback = $stmt->fetch();

    if (!$rollback) {
        header("Location: rollback.php?message=Rollback not found.");
        exit;
    }
} else {
    header("Location: rollback.php?message=Invalid request.");
    exit;
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
                    <h1>View Rollback</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="rollback.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Rollbacks
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Rollback Details</h3>
                        </div>
                        <div class="card-body">
                            <!-- Developer Name -->
                            <div class="form-group">
                                <label for="developer_name">Developer Name</label>
                                <input type="text" class="form-control" id="developer_name" value="<?= htmlspecialchars($rollback['developer_name']) ?>" readonly>
                            </div>

                            <!-- Rollback Notes (without HTML tags) -->
                            <div class="form-group">
                                <label for="rollback_notes">Rollback Notes</label>
                                <textarea class="form-control" id="rollback_notes" rows="5" readonly><?= htmlspecialchars(strip_tags($rollback['rollback_notes'])) ?></textarea>
                            </div>

                            <!-- Environment Name -->
                            <div class="form-group">
                                <label for="env_name">Environment</label>
                                <input type="text" class="form-control" id="env_name" value="<?= htmlspecialchars($rollback['env_name']) ?>" readonly>
                            </div>

                            <!-- IP Address -->
                            <div class="form-group">
                                <label for="ip">IP</label>
                                <input type="text" class="form-control" id="ip" value="<?= htmlspecialchars($rollback['ip']) ?>" readonly>
                            </div>

                            <!-- User -->
                            <div class="form-group">
                                <label for="user">User</label>
                                <input type="text" class="form-control" id="user" value="<?= htmlspecialchars($rollback['user']) ?>" readonly>
                            </div>

                            <!-- Backup Path -->
                            <div class="form-group">
                                <label for="old_backup_path">Old Backup Path</label>
                                <input type="text" class="form-control" id="old_backup_path" value="<?= htmlspecialchars($rollback['old_backup_path']) ?>" readonly>
                            </div>
                              <!-- Backup Path -->
                              <div class="form-group">
                                <label for="new_backup_path">New Backup Path</label>
                                <input type="text" class="form-control" id="new_backup_path" value="<?= htmlspecialchars($rollback['new_backup_path']) ?>" readonly>
                            </div>

                            <!-- Deployment Path -->
                            <div class="form-group">
                                <label for="deployment_path">Deployment Path</label>
                                <input type="text" class="form-control" id="deployment_path" value="<?= htmlspecialchars($rollback['deployment_path']) ?>" readonly>
                            </div>

                            <!-- Service Name -->
                            <div class="form-group">
                                <label for="service_name">Service Name</label>
                                <input type="text" class="form-control" id="service_name" value="<?= htmlspecialchars($rollback['service_name']) ?>" readonly>
                            </div>

                            <!-- Deployer Name -->
                            <div class="form-group">
                                <label for="rollback_by">Deployer Name</label>
                                <input type="text" class="form-control" id="rollback_by" value="<?= htmlspecialchars($rollback['rollback_by']) ?>" readonly>
                            </div>

                            <!-- Created At -->
                            <div class="form-group">
                                <label for="created_at">Created At</label>
                                <input type="text" class="form-control" id="created_at" value="<?= htmlspecialchars($rollback['created_at']) ?>" readonly>
                            </div>

                            <!-- Updated At -->
                            <div class="form-group">
                                <label for="updated_at">Updated At</label>
                                <input type="text" class="form-control" id="updated_at" value="<?= htmlspecialchars($rollback['updated_at']) ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('footer.php'); ?>
