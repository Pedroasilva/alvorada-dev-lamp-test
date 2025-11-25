<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cache.php';

try {
    // Cache recent properties for 5 minutes to reduce DB load
    $properties = QueryCache::remember('recent_properties', function() use ($pdo) {
        // Uses idx_recent_props covering index for optimal performance
        // FORCE INDEX ensures MySQL uses the covering index
        $stmt = $pdo->prepare("
            SELECT id, name, address, created_at 
            FROM properties FORCE INDEX (idx_recent_props)
            ORDER BY created_at DESC 
            LIMIT 4
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, 300); // 5 minutes TTL
    
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
