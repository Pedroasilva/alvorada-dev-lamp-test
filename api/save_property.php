<?php
ob_start();
ini_set('display_errors','0');
/**
 * Save Property API Endpoint (Refatorado)
 * POST /api/save_property.php
 * Cria e retorna uma propriedade usando camada de serviços
 */

require_once __DIR__ . '/autoload.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    JsonResponder::send(['error' => 'Method not allowed'], 405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $pdo = Database::getConnection();
    $service = new PropertyService(
        new PropertyRepository($pdo),
        new NoteRepository($pdo),
        new GeocodeService($pdo)
    );

    $created = $service->create($input); // agora latitude/longitude podem ser inferidos

    // Ajuste para manter contrato anterior: nominatim_data como string JSON, igual fetch direto
    if (isset($created['nominatim_data']) && is_array($created['nominatim_data'])) {
        $created['nominatim_data'] = json_encode($created['nominatim_data']);
    }

    JsonResponder::success(['property' => $created]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    JsonResponder::send(['error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Error in save_property.php: ' . $e->getMessage());
    JsonResponder::send(['error' => 'Failed to save property. Please try again later.'], 500);
}
