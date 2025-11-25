<?php
ob_start();
ini_set('display_errors','0');
/**
 * Geocoding API Endpoint (Refatorado)
 * POST /api/geocode.php
 * Usa GeocodeService para lookup com cache e fallback
 */

require_once __DIR__ . '/autoload.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    JsonResponder::send(['error' => 'Method not allowed'], 405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
if (!isset($input['address']) || trim((string)$input['address']) === '') {
    http_response_code(400);
    JsonResponder::send(['error' => 'Address is required'], 400);
    exit;
}

$address = trim($input['address']);

try {
    $pdo = Database::getConnection();
    $service = new GeocodeService($pdo);
    $result = $service->lookup($address);

    if ($result['status'] === 'not_found') {
        http_response_code(404);
        JsonResponder::send([
            'success' => false,
            'error' => "Address not found. Suggestions:\n• Include city and state\n• Use full street name\n• Try format: \"123 Main St, City, State\""
        ], 404);
        exit;
    }
    if ($result['status'] === 'error') {
        http_response_code(503);
        JsonResponder::send([
            'success' => false,
            'error' => 'Geocoding service unavailable. Please try again later.'
        ], 503);
        exit;
    }

    JsonResponder::success([
        'cached' => $result['cached'] ?? false,
        'data' => $result['data'] ?? []
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Error in geocode.php: ' . $e->getMessage());
    JsonResponder::send(['error' => 'Unexpected error performing geocode.'], 500);
}
    
