<?php
// Include the database connection
include('config.php');

// Check if environment_id, ip, and search term are provided in the GET request
if (isset($_GET['env_id']) && isset($_GET['ip']) && isset($_GET['term'])) {
    $env_id = (int) $_GET['env_id'];  // The selected environment ID
    $ip = $_GET['ip'];  // The selected IP address
    $term = '%' . $_GET['term'] . '%'; // The part of the WAR file path that the user has typed

    // Query to fetch matching WAR file paths from the deployment_paths table
    // Join with the servers table to get the server_id and filter by IP address
    $sql = "
        SELECT dp.deployment_path
        FROM deployment_paths dp
        JOIN servers s ON dp.server_id = s.id
        WHERE s.environment_id = :env_id 
        AND s.ip_address = :ip 
        AND dp.deployment_path LIKE :term
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':env_id', $env_id, PDO::PARAM_INT);
    $stmt->bindParam(':ip', $ip, PDO::PARAM_STR); // Filter by IP address
    $stmt->bindParam(':term', $term, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch matching WAR file paths
    $war_paths = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the result in a format suitable for the autocomplete
    $result = [];
    foreach ($war_paths as $path) {
        $result[] = ['value' => $path['deployment_path'], 'label' => $path['deployment_path']];
    }

    // Return the result as a JSON response
    echo json_encode($result);
} else {
    // If no environment, ip, or term provided, return empty JSON array
    echo json_encode([]);
}
