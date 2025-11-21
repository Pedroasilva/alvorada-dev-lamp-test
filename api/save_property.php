<?php
/**
 * Save Property API Endpoint
 * POST /api/save_property.php
 * Saves a new property to the database
 */

header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required = ['name', 'address', 'latitude', 'longitude'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['error' => ucfirst($field) . ' is required']);
        exit;
    }
}

$name = trim($input['name']);
$address = trim($input['address']);
$latitude = (float)$input['latitude'];
$longitude = (float)$input['longitude'];
$nominatimData = isset($input['nominatim_data']) ? json_encode($input['nominatim_data']) : null;

// Validate latitude and longitude ranges
if ($latitude < -90 || $latitude > 90) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid latitude value']);
    exit;
}

if ($longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid longitude value']);
    exit;
}

try {
    // Insert property
    $stmt = $pdo->prepare(
        "INSERT INTO properties (name, address, latitude, longitude, nominatim_data, created_at) 
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$name, $address, $latitude, $longitude, $nominatimData]);
    
    $propertyId = $pdo->lastInsertId();
    
    // Get the inserted property
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'property' => $property
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error in save_property.php: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to save property. Please try again later.']);
}
