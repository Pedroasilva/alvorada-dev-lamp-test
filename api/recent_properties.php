<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT id, name, address, created_at 
        FROM properties 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'properties' => $properties
    ]);
} catch (Exception $e) {
    error_log("Error fetching recent properties: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch recent properties'
    ]);
}
