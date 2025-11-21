<?php
/**
 * Property API Endpoint
 * GET /api/property.php?id={id}
 * Returns property details and associated notes
 */

header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Property ID is required']);
    exit;
}

$propertyId = (int)$_GET['id'];

try {
    // Get property details
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    if (!$property) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }

    // Get associated notes
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE property_id = ? ORDER BY created_at DESC");
    $stmt->execute([$propertyId]);
    $notes = $stmt->fetchAll();

    echo json_encode([
        'property' => $property,
        'notes' => $notes
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error in property.php: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to retrieve property data. Please try again later.']);
}
