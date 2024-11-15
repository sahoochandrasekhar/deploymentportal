<?php
session_start();
require_once 'config.php';  // Database connection
require_once 'functions.php';  // Your custom functions
require_once 'vendor/autoload.php'; // Include phpseclib (for SSH/SFTP)

use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

try {
    // Fetch environments from the database
    $stmt = $pdo->prepare("SELECT id, name FROM environments WHERE id IN (1, 2)");
    $stmt->execute();
    $environments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching environments: " . $e->getMessage();
}

// Handle form submission for adding a new rollback
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $rollback_notes = $_POST['rollback_notes'];
    $env_name = $_POST['env_name'];
    $ip = $_POST['ip'];
    $user = $_POST['user'];
    $deployment_path = $_POST['deployment_path'];
    $old_backup_path = $_POST['old_backup_path'];  // Old backup path
    $new_backup_path = $_POST['new_backup_path'];  // New backup path
    $service_name = $_POST['service_name'];
    $rollback_by = $_POST['rollback_by'];
    $developer_name = $_POST['developer_name'];

    try {
        // Step 1: Insert rollback information into the database
        $sql = "INSERT INTO rollback (rollback_notes, env_name, ip, user, deployment_path, old_backup_path, new_backup_path, service_name, rollback_by, developer_name) 
                VALUES (:rollback_notes, :env_name, :ip, :user, :deployment_path, :old_backup_path, :new_backup_path, :service_name, :rollback_by , :developer_name)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':rollback_notes', $rollback_notes);
        $stmt->bindParam(':env_name', $env_name);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':user', $user);
        $stmt->bindParam(':deployment_path', $deployment_path);
        $stmt->bindParam(':old_backup_path', $old_backup_path);
        $stmt->bindParam(':new_backup_path', $new_backup_path);
        $stmt->bindParam(':service_name', $service_name);
        $stmt->bindParam(':rollback_by', $rollback_by);
        $stmt->bindParam(':developer_name', $developer_name);

        if ($stmt->execute()) {
            $message = "Rollback added successfully!";
            // Log the action of adding a new rollback
            logAction($_SESSION['user_id'], "Added New Rollback", "Rollback for service '$service_name' was added.");
            
            // Step 2: Fetch the password for SSH connection from the servers table using the selected IP address
            $stmt = $pdo->prepare("SELECT password FROM servers WHERE ip_address = :ip");
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();
            $server = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($server) {
                $password = $server['password']; // Get the password for SSH and SFTP
            } else {
                $error = "Server with the specified IP address not found.";
            }

            if (!isset($error)) {
                // Step 3: Connect to the remote server via SSH
                $ssh = new SSH2($ip);
                if (!$ssh->login($user, $password)) {
                    $error = "SSH login failed. Please check the credentials.";
                } else {
                    // Step 4: Stop the service on the remote server
                    $stopServiceCommand = "sudo systemctl stop $service_name";
                    $output = $ssh->exec($stopServiceCommand);
                    if ($output === null || strpos($output, "failed") !== false) {
                        $error = "Failed to stop service '$service_name'. Output: $output";
                    }

                    // Step 5: Backup the existing WAR file (if any)
                    $file_name = "*.war";  // Use a wildcard to refer to any .war file

                    // Add debugging: Check if the file exists before moving it
                    $checkFileCommand = "if [ -f $deployment_path/$file_name ]; then echo 'File exists'; else echo 'File does not exist'; fi";
                    $fileCheckOutput = $ssh->exec($checkFileCommand);

                    // Log file check output for debugging
                    error_log("File existence check result: $fileCheckOutput");

                    // Backup command
                    $backupCommand = "if [ -f $deployment_path/$file_name ]; then mkdir -p $new_backup_path && mv $deployment_path/$file_name $new_backup_path/; fi";
                    $output = $ssh->exec($backupCommand);



                    

                    // Log output for debugging
                    if ($output === null || strpos($output, "mv:") !== false) {
                        $error = "Failed to move the WAR file from '$deployment_path' to '$new_backup_path'. Output: $output";
                    } else {
                        error_log("Successfully moved WAR file to backup path.");
                    }

                    // Step 6: Clean the deployment path directory
                    if (!isset($error)) {
                        $cleanPathCommand = "rm -rf $deployment_path/*";
                        $output = $ssh->exec($cleanPathCommand);

                        // Log the output for debugging
                        if ($output === null || strpos($output, "error") !== false) {
                            $error = "Failed to clean the deployment path '$deployment_path'. Output: $output";
                        } else {
                            error_log("Successfully cleaned deployment path '$deployment_path'.");
                        }

                        // Step 7: Copy everything from the backup folder to the deployment path
                        if (!isset($error)) {
                            $copyBackupCommand = "cp -r $old_backup_path/* $deployment_path/";
                            $output = $ssh->exec($copyBackupCommand);

                            // Log the output for debugging
                            if ($output === null || strpos($output, "error") !== false) {
                                $error = "Failed to copy files from '$old_backup_path' to '$deployment_path'. Output: $output";
                            } else {
                                error_log("Successfully copied backup files from '$old_backup_path' to '$deployment_path'.");
                            }

                            // Step 8: Restart the service after restoring the files
                            if (!isset($error)) {
                                $restartServiceCommand = "sudo systemctl restart $service_name";
                                $output = $ssh->exec($restartServiceCommand);

                                // Log output to check service restart status
                                if ($output === null || strpos($output, "failed") !== false) {
                                    $error = "Failed to restart service '$service_name'. Output: $output";
                                } else {
                                    // Log successful restart
                                    error_log("Service '$service_name' restarted successfully.");
                                    $message = "Rollback for service '$service_name' completed successfully!";
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $error = "Error inserting rollback data into the database.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
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
                    <h1>Add New Rollback</h1>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <!-- Success or Error Message -->
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php elseif (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                
                    <!-- Rollback Form -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title">Add Rollback</h3>
                        </div>
                        <div class="card-body">
                            <form id="rollbackForm" action="add_rollback.php" method="POST">
                          
                            <div class="form-group">
                                   <label for="developer_name">Developer Name:<span class="text-danger">*</span></label>
                                   <input type="text" name="developer_name" id="developer_name" class="form-control" value="<?= isset($developer_name) ? htmlspecialchars($developer_name) : ''; ?>" required>
                            </div>
                                <!-- Rollback Notes -->
                                <div class="form-group">
                                    <label for="rollback_notes">Rollback Notes:<span class="text-danger">*</span></label>
                                    <div id="rollback_notes" class="form-control" style="height: 200px;"></div>
                                    <input type="hidden" name="rollback_notes" id="rollback_notes_input">
                                </div>

                                <!-- Environment Name -->
                                <div class="form-group">
                                    <label for="env_name">Environment Name:<span class="text-danger">*</span></label>
                                    <select name="env_name" id="env_name" class="form-control" required>
                                        <option value="">Select Environment</option>
                                        <?php if (isset($environments) && count($environments) > 0): ?>
                                            <?php foreach ($environments as $env): ?>
                                                <option value="<?= $env['id']; ?>"><?= htmlspecialchars($env['name']); ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="">No Environments Available</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- IP Address (Editable) -->
                                <div class="form-group">
                                       <label for="ip">IP Address:<span class="text-danger">*</span></label>
                                       <input type="text" name="ip" id="ip" class="form-control" placeholder="Start typing IP address" required>
                                </div>

                                <div class="form-group">
                                       <label for="user">Server Logged In-Username:<span class="text-danger">*</span></label>
                                       <select name="user" id="user" class="form-control" required>
                                       <option value="" disabled selected>--Select User--</option>
                                       <option value="apmosys">apmosys</option>
                                       </select>
                                </div>

                                  <!-- Old Backup Path -->
                                <div class="form-group">
                                    <label for="old_backup_path">Old Backup Path:<span class="text-danger">*</span></label>
                                    <input type="text" name="old_backup_path" id="old_backup_path" class="form-control" value="<?= isset($old_backup_path) ? htmlspecialchars($old_backup_path) : ''; ?>" required>
                                </div>

                                <!-- New Backup Path -->
                                <div class="form-group">
                                    <label for="new_backup_path">New Backup Path:<span class="text-danger">*</span></label>
                                    <input type="text" name="new_backup_path" id="new_backup_path" class="form-control" value="<?= isset($new_backup_path) ? htmlspecialchars($new_backup_path) : ''; ?>">
                                </div>


                                <!-- Deployment Path (Editable) -->
                                <div class="form-group">
                                    <label for="deployment_path">Deployment Path:<span class="text-danger">*</span></label>
                                    <input type="text" name="deployment_path" id="deployment_path" class="form-control" value="<?= isset($deployment_path) ? htmlspecialchars($deployment_path) : ''; ?>" required>
                                </div>

                                <!-- Service Name (Editable) -->
                                <div class="form-group">
                                    <label for="service_name">Service Name:<span class="text-danger">*</span></label>
                                    <input type="text" name="service_name" id="service_name" class="form-control" value="<?= isset($service_name) ? htmlspecialchars($service_name) : ''; ?>" required>
                                </div>

                                <!-- Rollback By (Editable) -->
                                <div class="form-group">
                                    <label for="rollback_by">Rollback By:<span class="text-danger">*</span></label>
                                    <input type="text" name="rollback_by" id="rollback_by" class="form-control" value="<?= isset($rollback_by) ? htmlspecialchars($rollback_by) : ''; ?>" required>
                                </div>


                                <!-- Back Button -->
                                <a href="rollback.php" class="btn btn-secondary btn-block">Back</a>

                                <!-- Submit Button -->
                                <button id="submitBtn" type="submit" class="btn btn-primary btn-block" disabled>Submit Rollback</button>


                               
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<!-- Custom CSS -->
<style>
    .content-wrapper {
        padding: 20px;
    }

    .form-group label {
        font-weight: bold;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #007bff;
        border-bottom: none;
    }

    .card-body {
        padding: 30px;
    }

    .form-control {
        border-radius: 4px;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.12);
        border: 1px solid #ccc;
    }

    .btn-block {
        font-size: 16px;
        padding: 10px;
    }

    .alert {
        margin-top: 20px;
    }

    .content-header h1 {
        font-size: 28px;
        font-weight: bold;
    }

    /* Red asterisk for required fields */
    .text-danger {
        color: red;
    }


    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5); /* Dark transparent background */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999; /* Ensure it sits on top */
        visibility: hidden; /* Hidden by default */
    }

    .spinner {
        border: 4px solid #f3f3f3; /* Light grey background */
        border-top: 4px solid #3498db; /* Blue spinner color */
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 1.5s linear infinite; /* Spinning animation */
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }


</style>
<!-- Footer -->
<?php include('footer.php'); ?>
<!-- Quill.js CDN -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
    // Initialize Quill.js editor for the rollback notes
    var quill = new Quill('#rollback_notes', {
        theme: 'snow',
        placeholder: 'Enter rollback notes here...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link']
            ]
        }
    });

    // Function to check if all required fields are filled
    function validateForm() {
        // Get the values from all the fields
        const rollbackNotes = quill.root.innerHTML.trim();  // Quill editor content
        const envName = document.getElementById('env_name').value;
        const ip = document.getElementById('ip').value.trim();
        const user = document.getElementById('user').value.trim();
        const deploymentPath = document.getElementById('deployment_path').value.trim();
        const oldBackupPath = document.getElementById('old_backup_path').value.trim();
        const newBackupPath = document.getElementById('new_backup_path').value.trim();
        const serviceName = document.getElementById('service_name').value.trim();
        const rollbackBy = document.getElementById('rollback_by').value.trim();
        const developerName = document.getElementById('developer_name').value.trim();  // New developer_name field

        // Check if all fields are filled (including Quill editor)
        if (
            rollbackNotes === '' || 
            envName === '' || 
            ip === '' || 
            user === '' || 
            deploymentPath === '' || 
            oldBackupPath === '' ||
            newBackupPath === '' ||
            serviceName === '' || 
            rollbackBy === ''
        ) {
            document.getElementById('submitBtn').disabled = true;  // Disable submit button if any field is empty
        } else {
            document.getElementById('submitBtn').disabled = false;  // Enable submit button if all fields are filled
        }

        // Set the value of rollback_notes in hidden input
        document.getElementById('rollback_notes_input').value = rollbackNotes;
    }

    // Listen for changes in the Quill editor
    quill.on('text-change', function() {
        validateForm();  // Trigger validation on text change in Quill
    });

    // Add event listeners to text inputs to trigger validation on any change
    document.getElementById('ip').addEventListener('input', validateForm);
    document.getElementById('user').addEventListener('input', validateForm);
    document.getElementById('deployment_path').addEventListener('input', validateForm);
    document.getElementById('old_backup_path').addEventListener('input', validateForm);
    document.getElementById('new_backup_path').addEventListener('input', validateForm);
    document.getElementById('service_name').addEventListener('input', validateForm);
    document.getElementById('rollback_by').addEventListener('input', validateForm);
    document.getElementById('env_name').addEventListener('change', validateForm); // For the dropdown change

    // Initial validation when the page loads to check if the submit button should be enabled or not
    document.addEventListener('DOMContentLoaded', function() {
        validateForm();
    });


    $(document).ready(function() {
    // Listen for changes in the environment dropdown
    $('#env_name').change(function() {
        var envId = $(this).val(); // Get the selected environment ID

        // If an environment is selected, initialize autocomplete for the IP field
        if (envId) {
            // Initialize the autocomplete for the IP address field
            $('#ip').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'get_ips.php',  // PHP script to fetch IPs
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            env_id: envId,  // Selected environment ID
                            term: request.term // The text typed by the user (search term)
                        },
                        success: function(data) {
                            response(data); // Return the matching IPs
                        }
                    });
                },
                minLength: 2,  // Minimum characters to trigger the search
                select: function(event, ui) {
                    // Set the selected IP address when a user selects an option
                    $('#ip').val(ui.item.value);  // Set the value of IP field
                }
            });
        }
    });
});

$(document).ready(function() {
    // Listen for changes in the environment dropdown
    $('#env_name').change(function() {
        var envId = $(this).val(); // Get the selected environment ID

        // If an environment is selected, initialize autocomplete for the IP field
        if (envId) {
            // Initialize the autocomplete for the IP address field
            $('#ip').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: 'get_ips.php',  // PHP script to fetch IPs
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            env_id: envId,  // Selected environment ID
                            term: request.term // The text typed by the user (search term)
                        },
                        success: function(data) {
                            response(data); // Return the matching IPs
                        }
                    });
                },
                minLength: 2,  // Minimum characters to trigger the search
                select: function(event, ui) {
                    // Set the selected IP address when a user selects an option
                    $('#ip').val(ui.item.value);  // Set the value of IP field
                    
                    // Trigger the WAR file path autocomplete based on the selected IP
                    $('#deployment_path').autocomplete('option', 'source', function(request, response) {
                        $.ajax({
                            url: 'get_war_paths.php',  // PHP script to fetch WAR file paths
                            type: 'GET',
                            dataType: 'json',
                            data: {
                                env_id: envId,  // Selected environment ID
                                ip: ui.item.value, // Selected IP address
                                term: request.term  // The typed search term (i.e., part of the WAR file path)
                            },
                            success: function(data) {
                                response(data); // Return the filtered WAR file paths
                            }
                        });
                    });
                }
            });
        }
    });

    // Trigger the autocomplete for WAR file path when environment and IP are selected
    $('#deployment_path').autocomplete({
        minLength: 2,  // Minimum characters to start showing suggestions
        select: function(event, ui) {
            // Set the selected WAR file path when the user selects from the dropdown
            $('#deployment_path').val(ui.item.value);
        }
    });
});

$('#deploymentForm').on('submit', function(e) {
        showLoadingSpinner();
        $('#submitBtn').prop('disabled', true);
        return true;
    });

    // Show loading spinner
    function showLoadingSpinner() {
        $('#loading').css('visibility', 'visible');
    }

</script>
<div id="loading" class="loading-overlay">
    <div class="spinner"></div>
</div>
