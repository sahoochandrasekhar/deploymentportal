<?php
session_start();

require_once 'config.php';
require_once 'functions.php';
require_once 'vendor/autoload.php'; // Include phpseclib (you can use composer to install this)



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

// Handle form submission for adding a new deployment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $developer_name = $_POST['developer_name'];
    $release_notes = $_POST['release_notes'];
    $env_name = $_POST['env_name'];
    $ip = $_POST['ip'];
    $user = $_POST['user'];
    $deployment_path = $_POST['deployment_path']; // Get the custom WAR file path from the form
    $backup_path = isset($_POST['backup_path']) ? $_POST['backup_path'] : null; // Check for backup_path
    $service_name = $_POST['service_name'];
    $file_name = $_POST['file_name'];
    $deployer_name = $_POST['deployer_name'];

    // Handle WAR file upload
    if (isset($_FILES['war_file']) && $_FILES['war_file']['error'] == UPLOAD_ERR_OK) {
        $timestamp = time();
        $uploadDir = 'uploads/' . $timestamp . '/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Create directory with timestamp
        }
        
        $warFileName = $_FILES['war_file']['name'];
        $warFileTmpName = $_FILES['war_file']['tmp_name'];
        $warFilePath = $uploadDir . $warFileName;

        // Move the uploaded WAR file to the designated directory
        if (move_uploaded_file($warFileTmpName, $warFilePath)) {
            $message = "WAR file uploaded successfully!";
        } else {
            $error = "Error uploading WAR file.";
        }
    } else {
        $error = "No WAR file uploaded or there was an upload error.";
    }

    if (!isset($error)) {
        try {
            // Step 1: Insert data into the deployment table
            $sql = "INSERT INTO deployment (
                        developer_name, 
                        release_notes, 
                        env_name, 
                        ip, 
                        user, 
                        deployment_path, 
                        backup_path, 
                        service_name, 
                        file_name, 
                        deployer_name
                    ) VALUES (:developer_name, :release_notes, :env_name, :ip, :user, :deployment_path, :backup_path, :service_name, :file_name, :deployer_name)";
            
            // Prepare the statement
            $stmt = $pdo->prepare($sql);

            // Bind the form values to the prepared statement
            $stmt->bindParam(':developer_name', $developer_name);
            $stmt->bindParam(':release_notes', $release_notes);
            $stmt->bindParam(':env_name', $env_name);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':user', $user);
            $stmt->bindParam(':deployment_path', $deployment_path); // Bind the WAR file path (user input)
            $stmt->bindParam(':backup_path', $backup_path);
            $stmt->bindParam(':service_name', $service_name);
            $stmt->bindParam(':file_name', $file_name);
            $stmt->bindParam(':deployer_name', $deployer_name);

            // Execute the statement
            if ($stmt->execute()) {
                $message = "Deployment added successfully!";
                // Log the action of adding a new deployment
                logAction($_SESSION['user_id'], "Added New Deployment", "Deployment for service '$service_name' was added.");
                
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
                        
                        // Step 5: Backup the existing WAR file (if any)
                        $backupCommand = "if [ -f $deployment_path/$file_name ]; then mkdir -p $backup_path && mv $deployment_path/$file_name $backup_path/; fi";
                        $output = $ssh->exec($backupCommand);
                        
                        // Step 4: Clean the deployment path directory
                         $cleanPathCommand = "rm -rf $deployment_path/*";
                         $ssh->exec($cleanPathCommand);

                        // Step 6: Upload the new WAR file to the deployment path on the remote server
                        $sftp = new SFTP($ip);
                        if (!$sftp->login($user, $password)) {
                            $error = "SFTP login failed. Please check the credentials.";
                        } else {
                            $remotePath = $deployment_path . '/' . $file_name;
                            if ($sftp->put($remotePath, $warFilePath, SFTP::SOURCE_LOCAL_FILE)) {
                                // Step 7: Restart the service after uploading the new WAR file
                                $restartServiceCommand = "sudo systemctl restart $service_name";
                                $output = $ssh->exec($restartServiceCommand);
                                $message = "Deployment of $service_name completed successfully!";
                            } else {
                                $error = "Failed to upload the WAR file via SFTP.";
                            }
                        }
                    }
                }
            } else {
                $error = "Error: " . $stmt->errorInfo()[2];
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
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
                    <h1>Add New Deployment</h1>
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

                    <!-- Deployment Form -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title">Add Deployment</h3>
                        </div>
                        <div class="card-body">
                            <form id="deploymentForm" action="add_deployment.php" method="POST" enctype="multipart/form-data">
                                <!-- Developer Name -->
                                <div class="form-group">
                                    <label for="developer_name">Developer Name:<span class="text-danger">*</span></label>
                                    <input type="text" name="developer_name" id="developer_name" class="form-control" required>
                                </div>

                                <!-- Release Notes (Quill.js) -->
                                <div class="form-group">
                                    <label for="release_notes">Release Notes:<span class="text-danger">*</span></label>
                                    <div id="release_notes" class="form-control" style="height: 200px;"></div>
                                    <input type="hidden" name="release_notes" id="release_notes_input">
                                </div>

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

                                <div class="form-group">
                                       <label for="ip">IP Address:<span class="text-danger">*</span></label>
                                       <input type="text" name="ip" id="ip" class="form-control" placeholder="Start typing IP address" required>
                                </div>

                                <!-- User -->
                                <div class="form-group">
    <label for="user">Server Logged In-Username:<span class="text-danger">*</span></label>
    <select name="user" id="user" class="form-control" required>
        <option value="" disabled selected>--Select User--</option>
        <option value="apmosys">apmosys</option>
    </select>
</div>

                              <!-- WAR File Path (Auto-filled) -->
                                <div class="form-group">
                                     <label for="deployment_path">Deployment Path:<span class="text-danger">*</span></label>
                                     <input type="text" name="deployment_path" id="deployment_path" class="form-control" placeholder="Start typing the WAR file path" required>
                                </div>

                                <!-- Backup Path -->
                                <div class="form-group">
                                    <label for="backup_path">Backup Path:<span class="text-danger">*</span></label>
                                    <input type="text" name="backup_path" id="backup_path" class="form-control" placeholder="Enter Roll-Back Path" required>
                                </div>

                                <!-- WAR File Upload -->
                                <div class="form-group">
                                    <label for="war_file">Upload WAR File:<span class="text-danger">*</span></label>
                                    <input type="file" name="war_file" id="war_file" class="form-control" accept=".war" required>
                                </div>

                                <!-- File Name (Auto-filled) -->
                                <div class="form-group d-none">
    <label for="file_name">File Name:<span class="text-danger">*</span></label>
    <input type="text" name="file_name" id="file_name" class="form-control" readonly>
</div>

                                <!-- Service Name -->
                                <div class="form-group">
                                    <label for="service_name">Service Name:<span class="text-danger">*</span></label>
                                    <input type="text" name="service_name" id="service_name" class="form-control" placeholder="Enter the service name for war file" required>
                                </div>

                                <!-- Deployer Name -->
                                <div class="form-group">
                                    <label for="deployer_name">Deployer Name:<span class="text-danger">*</span></label>
                                    <input type="text" name="deployer_name" id="deployer_name" class="form-control" required>
                                </div>

                                <!-- Back Button -->
                                <a href="deployment.php" class="btn btn-secondary btn-block">Back</a>

                                <!-- Submit Button -->
                                <button id="submitBtn" type="submit" class="btn btn-primary btn-block" disabled>Deploy</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('footer.php'); ?>

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

<!-- Quill.js CDN -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<!-- JavaScript for Quill.js Editor, File Input Handling, and Enable/Disable Submit Button -->
<script>
    var quill = new Quill('#release_notes', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': '1'}, { 'header': '2'}, { 'font': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['bold', 'italic', 'underline'],
                ['link']
            ]
        },
        placeholder: 'Enter release notes here...',
        scrollingContainer: '#release_notes'
    });

    // Enable/Disable Submit Button based on Form Validation
    function checkFormCompletion() {
        var isComplete = true;
        var requiredFields = document.querySelectorAll('input[required]');
        for (var i = 0; i < requiredFields.length; i++) {
            if (requiredFields[i].value.trim() === "") {
                isComplete = false;
                break;
            }
        }

        // Check if release notes have content
        if (quill.root.innerHTML.trim() === "<p><br></p>") {
            isComplete = false;
        }

        document.getElementById('submitBtn').disabled = !isComplete;
    }

    // Attach event listeners to required fields
    document.querySelectorAll('input[required]').forEach(function(input) {
        input.addEventListener('input', checkFormCompletion);
    });

    // File input change event to auto-fill WAR file name and path
    document.getElementById('war_file').addEventListener('change', function(event) {
        var filePath = event.target.value;
        var fileName = filePath.split('\\').pop();
        document.getElementById('file_name').value = fileName;
        checkFormCompletion();
    });

    // Also check Quill editor on form submit
    document.getElementById('deploymentForm').onsubmit = function() {
        var release_notes = quill.root.innerHTML;
        document.getElementById('release_notes_input').value = release_notes;
    };

    // Initial check for enabling/disabling submit button
    checkFormCompletion();

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
