<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");  // Redirect to login if not logged in
    exit;
}

// Get user role (is_admin) from the session
$is_admin = $_SESSION['is_admin'];

// Include database connection file
include('config.php');

// Fetch counts for users, deployments, and staging servers
$query_counts = "
    SELECT 
    (SELECT COUNT(*) FROM users) AS user_count, 
    (SELECT COUNT(*) FROM deployment) AS deployment_count,
     (SELECT COUNT(*) FROM rollback) AS rollback_count,
    (SELECT COUNT(*) FROM servers WHERE environment_id IN (1, 2)) AS staging_count
";
$stmt_counts = $pdo->query($query_counts);
$counts = $stmt_counts->fetch(PDO::FETCH_ASSOC);

// Fetch deployment and rollback data for graphs (deployments and rollbacks by month)
$query_deployment_graph = "
    SELECT COUNT(*) AS deployment_count, DATE_FORMAT(created_at, '%Y-%m') AS month
    FROM deployment
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
";
$query_rollback_graph = "
    SELECT COUNT(*) AS rollback_count, DATE_FORMAT(created_at, '%Y-%m') AS month
    FROM rollback
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
";

$stmt_deployment_graph = $pdo->query($query_deployment_graph);
$stmt_rollback_graph = $pdo->query($query_rollback_graph);

$deployments = $stmt_deployment_graph->fetchAll(PDO::FETCH_ASSOC);
$rollbacks = $stmt_rollback_graph->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for Chart.js
$months = [];
$deployment_counts = [];
$rollback_counts = [];
foreach (array_reverse($deployments) as $deployment) {  // Reverse to show oldest to newest
    $months[] = $deployment['month'];
    $deployment_counts[] = $deployment['deployment_count'];
}
foreach (array_reverse($rollbacks) as $rollback) {  // Reverse to show oldest to newest
    $rollback_counts[] = $rollback['rollback_count'];
}

// Fetch recent deployments and rollbacks for the table (limit to 5 most recent)
$query_recent_deployment = "
    SELECT 
        deployment.*, 
        environments.name AS env_name 
    FROM deployment 
    LEFT JOIN environments ON deployment.env_name = environments.id
    ORDER BY deployment.created_at DESC 
    LIMIT 5
";
$query_recent_rollback = "
    SELECT 
        rollback.*, 
        environments.name AS env_name 
    FROM rollback 
    LEFT JOIN environments ON rollback.env_name = environments.id
    ORDER BY rollback.created_at DESC 
    LIMIT 5
";
$stmt_recent_deployment = $pdo->query($query_recent_deployment);
$stmt_recent_rollback = $pdo->query($query_recent_rollback);

$recent_deployments = $stmt_recent_deployment->fetchAll(PDO::FETCH_ASSOC);
$recent_rollbacks = $stmt_recent_rollback->fetchAll(PDO::FETCH_ASSOC);
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
            <div class="col-sm-6 d-flex justify-content-center mx-auto">
            <h1 style="font-family: 'Poppins', sans-serif; color: #2980b9; text-align: center; font-size: 1.5rem; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">
    Welcome to Deployment Portal
</h1>

        </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Display cards with summary statistics -->
                    <div class="row">
                        <!-- Number of Users -->
                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?php echo htmlspecialchars($counts['user_count']); ?></h3>
                                    <p>Number of Users</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-person"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Number of Deployments -->
                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo htmlspecialchars($counts['deployment_count']); ?></h3>
                                    <p>Number of Deployments</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-stats-bars"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Number of Rollbacks -->
                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo htmlspecialchars($counts['rollback_count']); ?></h3>
                                    <p>Number of Roll-Backs</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-stats-bars"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Number of Staging Servers -->
                        <div class="col-lg-3 col-md-4 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo htmlspecialchars($counts['staging_count']); ?></h3>
                                    <p>Number of Staging & UAT Servers</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-server"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deployment and Rollback Statistics Graphs Side by Side -->
                    <div class="row">
                        <!-- Deployments Over the Last 12 Months -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h3 class="card-title font-weight-bold text-dark">Deployments Over the Last 12 Months</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="deploymentChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Rollbacks Over the Last 12 Months -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h3 class="card-title font-weight-bold text-dark">Rollbacks Over the Last 12 Months</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="rollbackChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Deployments and Rollbacks Tables Side by Side -->
                    <div class="row">
                        <!-- Recent Deployments -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card mt-4">
                                <div class="card-header">
                                <h3 class="card-title font-weight-bold text-dark">Recent Deployments</h3>
                                </div>
                                <div class="card-body table-responsive" style="max-height: 400px;">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Deployment Time</th>
                                                <th>Release Notes</th>
                                                <th>Developer Name</th>
                                                <th>Deployer Name</th>
                                                <th>Backup Path</th>
                                                <th>Environment</th>
                                                <th>Options</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_deployments as $deployment): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($deployment['created_at']); ?></td>
                                                    <td><?php echo htmlspecialchars(strip_tags($deployment['release_notes'])); ?></td>
                                                    <td><?php echo htmlspecialchars($deployment['developer_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($deployment['deployer_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($deployment['backup_path']); ?></td>
                                                    <td><?php echo htmlspecialchars($deployment['env_name']); ?></td>
                                                    <td>
                                                        <a href="view_deployment.php?id=<?php echo $deployment['id']; ?>" class="btn btn-info btn-sm">View Details</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Rollbacks -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card mt-4">
                                <div class="card-header">
                                <h3 class="card-title font-weight-bold text-dark">Recent Rollbacks</h3>
                                </div>
                                <div class="card-body table-responsive" style="max-height: 400px;">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Rollback Time</th>
                                                <th>Roll-Back Notes</th>
                                                <th>Rollback By</th>
                                                <th>Environment</th>
                                                <th>Developer Name</th>
                                                <th>Options</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_rollbacks as $rollback): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($rollback['created_at']); ?></td>
                                                    <td><?php echo htmlspecialchars(strip_tags($rollback['rollback_notes'])); ?></td>
                                                    <td><?php echo htmlspecialchars($rollback['rollback_by']); ?></td>
                                                    <td><?php echo htmlspecialchars($rollback['env_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($rollback['developer_name']); ?></td>
                                                    <td>
                                                        <a href="view_rollback.php?id=<?php echo $rollback['id']; ?>" class="btn btn-info btn-sm">View Details</a>
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
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Render the deployments chart using Chart.js
    const ctxDeployment = document.getElementById('deploymentChart').getContext('2d');
    const deploymentChart = new Chart(ctxDeployment, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Deployments',
                data: <?php echo json_encode($deployment_counts); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Deployments'
                    }
                }
            }
        }
    });

    // Render the rollbacks chart using Chart.js
    const ctxRollback = document.getElementById('rollbackChart').getContext('2d');
    const rollbackChart = new Chart(ctxRollback, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Rollbacks',
                data: <?php echo json_encode($rollback_counts); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Rollbacks'
                    }
                }
            }
        }
    });
</script>

<?php include('footer.php'); ?>
