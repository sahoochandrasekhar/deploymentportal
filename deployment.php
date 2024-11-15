<?php
session_start();
require_once 'config.php';

// Get all deployments from the database with the environment name
$sql = "
    SELECT 
        deployment.id,
        deployment.developer_name,
        deployment.ip,
        deployment.service_name,
        deployment.deployer_name,
        deployment.created_at,
        environments.name AS env_name
    FROM deployment
    LEFT JOIN environments ON deployment.env_name = environments.id
"; 
$stmt = $pdo->prepare($sql);
$stmt->execute();
$deployments = $stmt->fetchAll();
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
                    <h1>Manage Deployments</h1>
                </div>
                <!-- Add Deployment Button (Visible only for Admins) -->
                <div class="col-sm-6 text-right">
                    <a href="add_deployment.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add New Deployment
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
                    <!-- Success Message -->
                    <?php if (isset($_GET['message'])): ?>
                        <div id="notification" class="notification"><?= htmlspecialchars($_GET['message']) ?></div>
                    <?php endif; ?>

                    <!-- Deployments Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Deployment Management</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="deploymentTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Developer Name</th>
                                            <th>Environment</th>
                                            <th>IP</th>
                                            <th>Service Name</th>
                                            <th>Deployer Name</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deployments as $deployment): ?>
                                            <tr>
                                                <td><?= $deployment['id'] ?></td>
                                                <td><?= $deployment['developer_name'] ?></td>
                                                <td><?= $deployment['env_name'] ?></td> <!-- Display environment name -->
                                                <td><?= $deployment['ip'] ?></td>
                                                <td><?= $deployment['service_name'] ?></td>
                                                <td><?= $deployment['deployer_name'] ?></td>
                                                <td><?= $deployment['created_at'] ?></td>
                                                <td>
                                                    <!-- View Button for deployments -->
                                                    <a href="view_deployment.php?id=<?= $deployment['id'] ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
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
        $('#deploymentTable').DataTable({
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
