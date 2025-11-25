<?php
/**
 * Geocoding API Endpoint (Backend Proxy)
 * POST /api/geocode.php
 * Proxies geocoding requests to Nominatim with caching and rate limiting
 */

header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['address']) || empty(trim($input['address']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Address is required']);
    exit;
}

$address = trim($input['address']);

// Check cache first (opcional - requer tabela geocode_cache)
$cacheKey = md5(strtolower($address));
try {
    $stmt = $pdo->prepare("
        SELECT result 
        FROM geocode_cache 
        WHERE cache_key = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$cacheKey]);
    $cached = $stmt->fetch();
    
    if ($cached) {
        $result = json_decode($cached['result'], true);
        echo json_encode([
            'success' => true,
            'cached' => true,
            'data' => $result
        ]);
        exit;
    }
} catch (PDOException $e) {
    // Tabela não existe ou erro - continua sem cache
    error_log("Cache check failed: " . $e->getMessage());
}

// Nominatim request with proper User-Agent
$userAgent = 'PropertyResearchSystem/1.0';
$nominatimUrl = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'format' => 'json',
    'q' => $address,
    'addressdetails' => 1,
    'limit' => 5
]);

$options = [
    'http' => [
        'method' => 'GET',
        'header' => [
            "User-Agent: $userAgent",
            'Accept: application/json'
        ],
        'timeout' => 10
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($nominatimUrl, false, $context);

if ($response === false) {
    // Fallback com accept-language=en
    $fallbackUrl = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'format' => 'json',
        'q' => $address,
        'addressdetails' => 1,
        'limit' => 5,
        'accept-language' => 'en'
    ]);
    
    $response = @file_get_contents($fallbackUrl, false, $context);
    
    if ($response === false) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Geocoding service unavailable. Please try again later.'
        ]);
        exit;
    }
}

$data = json_decode($response, true);

if (empty($data)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => "Address not found. Suggestions:\n• Include city and state\n• Use full street name\n• Try format: \"123 Main St, City, State\""
    ]);
    exit;
}

// Store in cache (opcional)
try {
    $stmt = $pdo->prepare("
        INSERT INTO geocode_cache (cache_key, address, result, created_at) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE result = ?, created_at = NOW()
    ");
    $resultJson = json_encode($data);
    $stmt->execute([$cacheKey, $address, $resultJson, $resultJson]);
} catch (PDOException $e) {
    // Cache write falhou - não crítico, continua
    error_log("Cache write failed: " . $e->getMessage());
}

// Log usage (opcional - para analytics)
try {
    $stmt = $pdo->prepare("
        INSERT INTO geocoding_logs (address, results_count, created_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$address, count($data)]);
} catch (PDOException $e) {
    error_log("Logging failed: " . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'cached' => false,
    'data' => $data
]);
