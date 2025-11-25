<?php
ob_start();
ini_set('display_errors','0');
/**
 * Property API Endpoint (Refatorado)
 * GET /api/property.php?id={id}
 * Retorna detalhes de propriedade + notas via PropertyService
 */

require_once __DIR__ . '/autoload.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    JsonResponder::send(['error' => 'Method not allowed'], 405);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    JsonResponder::send(['error' => 'Property ID is required'], 400);
    exit;
}

$propertyId = (int)$_GET['id'];

try {
    $pdo = Database::getConnection();
    $service = new PropertyService(
        new PropertyRepository($pdo),
        new NoteRepository($pdo)
    );
    $data = $service->details($propertyId);
    if (!$data) {
        http_response_code(404);
        JsonResponder::send(['error' => 'Property not found'], 404);
        exit;
    }

    // Manter contrato: property e notes como arrays crus (nominatim_data originalmente string JSON)
    $property = $data['property'];
    if (isset($property['nominatim_data']) && is_array($property['nominatim_data'])) {
        $property['nominatim_data'] = json_encode($property['nominatim_data']);
    }

    $notes = $data['notes'];

    // Mantém contrato sem adicionar chave success
    JsonResponder::send([
        'property' => $property,
        'notes' => $notes
    ], 200);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Error in property.php: ' . $e->getMessage());
    JsonResponder::send(['error' => 'Failed to retrieve property data. Please try again later.'], 500);
}
