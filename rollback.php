<?php
session_start();
require_once 'config.php';

// Get all rollbacks from the database with the environment name
$sql = "
    SELECT 
        rollback.id,
        rollback.developer_name,       -- Developer Name (unchanged)
        rollback.rollback_notes,       -- Added rollback_notes field
        rollback.env_name,             -- Environment Name (unchanged)
        rollback.ip,
        rollback.user,                 -- Added user field
        rollback.old_backup_path,          -- Added backup_path field
        rollback.deployment_path,      -- Added deployment_path field
        rollback.service_name,
        rollback.rollback_by,        -- Added deployer_name field
        rollback.created_at,
        rollback.updated_at,           -- Added updated_at field
        environments.name AS env_name  -- Still join on environments table
    FROM rollback
    LEFT JOIN environments ON rollback.env_name = environments.id
"; 
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rollbacks = $stmt->fetchAll();
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
                    <h1>Manage Rollbacks</h1>
                </div>
                <!-- Add rollback Button (Visible only for Admins) -->
                <div class="col-sm-6 text-right">
                    <a href="add_rollback.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add New Rollback
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

                    <!-- Rollbacks Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Rollback Management</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="rollbackTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Developer Name</th>
                                           
                                            <th>Environment</th>
                                            <th>IP</th>
                                            <th>User</th> <!-- Added column for user -->
                                            
                                            <th>Service Name</th>
                                            <th>Deployer Name</th> <!-- Added column for deployer name -->
                                            <th>Action</th> <!-- Action column with View button -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rollbacks as $rollback): ?>
                                            <tr>
                                                <td><?= $rollback['id'] ?></td>
                                                <td><?= $rollback['developer_name'] ?></td>
                                                
                                                <td><?= $rollback['env_name'] ?></td>
                                                <td><?= $rollback['ip'] ?></td>
                                                <td><?= $rollback['user'] ?></td> <!-- Display user -->
                                               
                                                <td><?= $rollback['service_name'] ?></td>
                                                <td><?= $rollback['rollback_by'] ?></td> <!-- Display deployer name -->
                                                
                                                <td>
                                                    <!-- View Button for rollbacks -->
                                                    <a href="view_rollback.php?id=<?= $rollback['id'] ?>" class="btn btn-primary btn-sm">
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
        $('#rollbackTable').DataTable({
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
