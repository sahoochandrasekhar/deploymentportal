<?php
// ssh_deployment.php

// Function to execute an SSH command
function executeSSHCommand($ip, $user, $password, $command) {
    $connection = ssh2_connect($ip);
    if (!$connection) {
        return ['status' => 'error', 'message' => 'Unable to connect to the server via SSH.'];
    }

    // Authenticate with the server
    if (!ssh2_auth_password($connection, $user, $password)) {
        return ['status' => 'error', 'message' => 'Authentication failed.'];
    }

    // Execute the command
    $stream = ssh2_exec($connection, $command);
    if (!$stream) {
        return ['status' => 'error', 'message' => 'Failed to execute the command on the server.'];
    }

    // Enable blocking and fetch the result
    stream_set_blocking($stream, true);
    $output = stream_get_contents($stream);
    fclose($stream);

    return ['status' => 'success', 'message' => $output];
}

// Function to deploy a WAR file
function deployWarFile($ip, $user, $deployment_path, $backup_path, $service_name, $warFilePath) {
    // Fetch the server password from the database
    global $pdo;
    $stmt = $pdo->prepare("SELECT password FROM servers WHERE ip = :ip");
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return ['status' => 'error', 'message' => "Server with IP $ip not found in the database."];
    }

    $server_password = $result['password'];

    // Step 1: Stop the service (backup and replace WAR file)
    $stopServiceCommand = "sudo systemctl stop $service_name";
    $stopServiceResult = executeSSHCommand($ip, $user, $server_password, $stopServiceCommand);
    if ($stopServiceResult['status'] === 'error') {
        return $stopServiceResult; // Return the error if stopping the service failed
    }

    // Step 2: Backup the current WAR file
    if ($backup_path) {
        $backupCommand = "cp $deployment_path $backup_path/$(basename $deployment_path).bak";
        $backupResult = executeSSHCommand($ip, $user, $server_password, $backupCommand);
        if ($backupResult['status'] === 'error') {
            return $backupResult; // Return the error if backup failed
        }
    }

    // Step 3: Upload the new WAR file to the server (replace existing WAR file)
    $uploadCommand = "sudo cp $warFilePath $deployment_path";
    $uploadResult = executeSSHCommand($ip, $user, $server_password, $uploadCommand);
    if ($uploadResult['status'] === 'error') {
        return $uploadResult; // Return the error if upload failed
    }

    // Step 4: Restart the service to apply the new WAR file
    $restartServiceCommand = "sudo systemctl start $service_name";
    $restartServiceResult = executeSSHCommand($ip, $user, $server_password, $restartServiceCommand);
    if ($restartServiceResult['status'] === 'error') {
        return $restartServiceResult; // Return the error if restarting the service failed
    }

    return ['status' => 'success', 'message' => 'Deployment completed successfully.'];
}
?>
