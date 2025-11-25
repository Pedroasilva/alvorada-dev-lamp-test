<?php
ob_start();
ini_set('display_errors','0');
/**
 * Add Note API Endpoint (Refatorado)
 * POST /api/add_note.php
 * Cria nota associada usando NoteService
 */

require_once __DIR__ . '/autoload.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    JsonResponder::send(['error' => 'Method not allowed'], 405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (!isset($input['property_id']) || !is_numeric($input['property_id'])) {
    http_response_code(400);
    JsonResponder::send(['error' => 'Property ID is required'], 400);
    exit;
}
if (!isset($input['note']) || trim((string)$input['note']) === '') {
    http_response_code(400);
    JsonResponder::send(['error' => 'Note content is required'], 400);
    exit;
}

try {
    $pdo = Database::getConnection();
    $service = new NoteService(new NoteRepository($pdo), new PropertyRepository($pdo));
    $created = $service->add((int)$input['property_id'], (string)$input['note']);

    JsonResponder::success(['note_id' => $created['id'] ?? null]);
} catch (InvalidArgumentException $e) {
    // Para manter semântica anterior: Property not found => 404; validações => 400
    $msg = $e->getMessage();
    if (stripos($msg, 'Property') !== false && stripos($msg, 'not found') !== false) {
        http_response_code(404);
    } else {
        http_response_code(400);
    }
    JsonResponder::send(['error' => $msg], http_response_code());
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Error in add_note.php: ' . $e->getMessage());
    JsonResponder::send(['error' => 'Failed to add note. Please try again later.'], 500);
}
