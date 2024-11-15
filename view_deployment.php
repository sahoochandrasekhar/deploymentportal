<?php
session_start();

// Check if the user is an admin
// if ($_SESSION['is_admin'] != 1) {
//     header("Location: index.php"); // Redirect to login if not an admin
//     exit;
// }

require_once 'config.php';

if (isset($_GET['id'])) {
    $deployment_id = $_GET['id'];

    // Fetch deployment details with environment name
    $sql = "
        SELECT deployment.*, environments.name AS env_name 
        FROM deployment
        LEFT JOIN environments ON deployment.env_name = environments.id
        WHERE deployment.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deployment_id]);
    $deployment = $stmt->fetch();

    if (!$deployment) {
        header("Location: deployment.php?message=Deployment not found.");
        exit;
    }
} else {
    header("Location: deployment.php?message=Invalid request.");
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
                    <h1>View Deployment</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="deployment.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Deployments
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
                            <h3 class="card-title">Deployment Details</h3>
                        </div>
                        <div class="card-body">
                            <!-- Developer Name -->
                            <div class="form-group">
                                <label for="developer_name">Developer Name</label>
                                <input type="text" class="form-control" id="developer_name" value="<?= htmlspecialchars($deployment['developer_name']) ?>" readonly>
                            </div>

                            <!-- Release Notes (without HTML tags) -->
                            <div class="form-group">
                                <label for="release_notes">Release Notes</label>
                                <textarea class="form-control" id="release_notes" rows="5" readonly><?= htmlspecialchars(strip_tags($deployment['release_notes'])) ?></textarea>
                            </div>

                            <!-- Environment Name -->
                            <div class="form-group">
                                <label for="env_name">Environment</label>
                                <input type="text" class="form-control" id="env_name" value="<?= htmlspecialchars($deployment['env_name']) ?>" readonly>
                            </div>

                            <!-- IP Address -->
                            <div class="form-group">
                                <label for="ip">IP</label>
                                <input type="text" class="form-control" id="ip" value="<?= htmlspecialchars($deployment['ip']) ?>" readonly>
                            </div>

                            <!-- User -->
                            <div class="form-group">
                                <label for="user">User</label>
                                <input type="text" class="form-control" id="user" value="<?= htmlspecialchars($deployment['user']) ?>" readonly>
                            </div>

                            <!-- Service Name -->
                            <div class="form-group">
                                <label for="service_name">Service Name</label>
                                <input type="text" class="form-control" id="service_name" value="<?= htmlspecialchars($deployment['service_name']) ?>" readonly>
                            </div>

                            <!-- File Name -->
                            <div class="form-group">
                                <label for="file_name">File Name</label>
                                <input type="text" class="form-control" id="file_name" value="<?= htmlspecialchars($deployment['file_name']) ?>" readonly>
                            </div>

                             <!-- Deployment Path -->
                             <div class="form-group">
                                <label for="deployment_path">Deployment Path</label>
                                <input type="text" class="form-control" id="deployment_path" value="<?= htmlspecialchars($deployment['deployment_path']) ?>" readonly>
                            </div>


                             <!-- Backup Path -->
                            <div class="form-group">
                                <label for="backup_path">Backup Path</label>
                                <input type="text" class="form-control" id="backup_path" value="<?= htmlspecialchars($deployment['backup_path']) ?>" readonly>
                            </div>

                           
                            <!-- Deployer Name -->
                            <div class="form-group">
                                <label for="deployer_name">Deployer Name</label>
                                <input type="text" class="form-control" id="deployer_name" value="<?= htmlspecialchars($deployment['deployer_name']) ?>" readonly>
                            </div>
                            
                            <!-- Created At -->
                            <div class="form-group">
                                <label for="created_at">Created At</label>
                                <input type="text" class="form-control" id="created_at" value="<?= htmlspecialchars($deployment['created_at']) ?>" readonly>
                            </div>
                          
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('footer.php'); ?>
