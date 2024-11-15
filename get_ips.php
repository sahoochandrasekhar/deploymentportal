<?php
// Include the database connection
include('config.php');

// Check if environment_id and search term are provided in the GET request
if (isset($_GET['env_id']) && isset($_GET['term'])) {
    $env_id = (int) $_GET['env_id'];  // The selected environment ID
    $term = '%' . $_GET['term'] . '%'; // The part of the IP address that the user has typed

    // Query to fetch matching IP addresses from the servers table
    $sql = "SELECT ip_address FROM servers WHERE environment_id = :env_id AND ip_address LIKE :term";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':env_id', $env_id, PDO::PARAM_INT);
    $stmt->bindParam(':term', $term, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch matching IPs
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the result in a format suitable for the autocomplete
    $result = [];
    foreach ($ips as $ip) {
        $result[] = ['value' => $ip['ip_address'], 'label' => $ip['ip_address']];
    }

    // Return the result as a JSON response
    echo json_encode($result);
} else {
    // If no environment or term provided, return empty JSON array
    echo json_encode([]);
}
