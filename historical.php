<?php
session_start();

// Check if the user is an admin
if ($_SESSION['is_admin'] != 1) {
    header("Location: index.php"); // Redirect to login if not an admin
    exit;
}

require_once 'config.php';
require_once 'functions.php'; // Assuming logAction is in functions.php

// Get the current page from URL, default to page 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Number of logs per page
$search = isset($_GET['search']) ? $_GET['search'] : ''; // Search keyword
$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'timestamp'; // Default sorting column
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC'; // Default sorting order

// Query to count the total logs (for pagination)
$count_sql = "SELECT COUNT(*) FROM logs WHERE action LIKE :search";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute([':search' => '%' . $search . '%']);
$total_logs = $count_stmt->fetchColumn();

// Calculate the offset for pagination
$offset = ($page - 1) * $limit;

// Modified query to join logs with users and fetch logs with username
$sql = "
    SELECT logs.*, users.username 
    FROM logs 
    LEFT JOIN users ON logs.user_id = users.id 
    WHERE logs.action LIKE :search 
    ORDER BY $sort_column $sort_order 
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);

// Bind the parameters
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT); // Ensure limit is an integer
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); // Ensure offset is an integer

$stmt->execute();
$logs = $stmt->fetchAll();

// Calculate the total number of pages
$total_pages = ceil($total_logs / $limit);
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
                    <h1>Historical Logs</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Logs Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">View Historical Logs</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="logTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>
                                                <a href="?sort_column=id&sort_order=<?= $sort_order === 'ASC' ? 'DESC' : 'ASC' ?>">ID</a>
                                            </th>
                                            <th>
                                                <a href="?sort_column=user_id&sort_order=<?= $sort_order === 'ASC' ? 'DESC' : 'ASC' ?>">Name</a>
                                            </th>
                                            <th>
                                                <a href="?sort_column=action&sort_order=<?= $sort_order === 'ASC' ? 'DESC' : 'ASC' ?>">Action</a>
                                            </th>
                                            <th>
                                                <a href="?sort_column=details&sort_order=<?= $sort_order === 'ASC' ? 'DESC' : 'ASC' ?>">Details</a>
                                            </th>
                                            <th>
                                                <a href="?sort_column=timestamp&sort_order=<?= $sort_order === 'ASC' ? 'DESC' : 'ASC' ?>">Timestamp</a>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($logs): ?>
                                            <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($log['id']) ?></td>
                                                    <td><?= htmlspecialchars($log['username']) ?></td> <!-- Display Username -->
                                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                                    <td><?= htmlspecialchars($log['details']) ?></td>
                                                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5">No logs found.</td></tr>
                                        <?php endif; ?>
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

<!-- Pagination -->
<div class="container">
    <div class="pagination">
        <ul class="pagination">
            <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=1&limit=<?= $limit ?>&search=<?= htmlspecialchars($search) ?>">First</a>
            </li>
            <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($search) ?>">Previous</a>
            </li>
            <li class="page-item <?= $page === $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($search) ?>">Next</a>
            </li>
            <li class="page-item <?= $page === $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $total_pages ?>&limit=<?= $limit ?>&search=<?= htmlspecialchars($search) ?>">Last</a>
            </li>
        </ul>
    </div>
</div>

<!-- Include the footer -->
<?php include('footer.php'); ?>

<!-- DataTables and jQuery Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        $('#logTable').DataTable({
            "paging": true,           // Enable pagination
            "lengthChange": true,     // Allow entries to be changed
            "searching": true,        // Enable search box
            "ordering": true,         // Enable sorting
            "info": true,             // Show table information
            "autoWidth": false,       // Disable automatic column width adjustment
            "responsive": true,       // Make the table responsive
            "lengthMenu": [5, 10, 15, 20], // Dropdown to select number of entries
            "pageLength": <?= $limit ?>,         // Default number of entries per page
        });
    });
</script>

<!-- DataTables Stylesheet -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css">
