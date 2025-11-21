<?php
/**
 * Add Note API Endpoint
 * POST /api/add_note.php
 * Adds a new note to a property
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

if (!isset($input['property_id']) || !is_numeric($input['property_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Property ID is required']);
    exit;
}

if (!isset($input['note']) || empty(trim($input['note']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Note content is required']);
    exit;
}

$propertyId = (int)$input['property_id'];
$note = trim($input['note']);

try {
    // Verify property exists
    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Property not found']);
        exit;
    }

    // Insert note
    $stmt = $pdo->prepare("INSERT INTO notes (property_id, note, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$propertyId, $note]);

    echo json_encode([
        'success' => true,
        'note_id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error in add_note.php: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to add note. Please try again later.']);
}
