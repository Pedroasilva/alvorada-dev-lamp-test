<?php
ob_start();
ini_set('display_errors','0');
require_once __DIR__ . '/../autoload.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    JsonResponder::send(['error' => 'Method not allowed'], 405);
    exit;
}

try {
    $pdo = Database::getConnection();
    $service = new PropertyService(new PropertyRepository($pdo), new NoteRepository($pdo));
    $recent = $service->recent(10); // default limit 10 para rota REST

    $properties = array_map(function ($p) {
        return [
            'id' => $p['id'],
            'name' => $p['name'],
            'address' => $p['address'],
            'latitude' => $p['latitude'],
            'longitude' => $p['longitude'],
            'created_at' => $p['created_at']
        ];
    }, $recent);

    JsonResponder::success(['properties' => $properties]);
} catch (Throwable $e) {
    error_log('Error fetching recent properties (REST): ' . $e->getMessage());
    JsonResponder::send(['error' => 'Failed to fetch recent properties'], 500);
}
