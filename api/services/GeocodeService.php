<?php
class GeocodeService
{
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function lookup(string $address): array
    {
        $address = trim($address);
        if ($address === '') {
            throw new InvalidArgumentException('Address is required');
        }
        $cacheKey = md5(strtolower($address));
        $cached = $this->getCache($cacheKey);
        if ($cached) {
            return ['success' => true, 'cached' => true, 'data' => $cached];
        }
        $data = $this->callNominatim($address);
        if (empty($data)) {
            throw new RuntimeException("Address not found. Suggestions:\n• Include city and state\n• Use full street name\n• Try format: \"123 Main St, City, State\"");
        }
        $this->storeCache($cacheKey, $address, $data);
        $this->logUsage($address, count($data));
        return ['success' => true, 'cached' => false, 'data' => $data];
    }

    private function callNominatim(string $address): array
    {
        $userAgent = 'PropertyResearchSystem/1.0';
        $base = 'https://nominatim.openstreetmap.org/search?';
        $query = http_build_query([
            'format' => 'json',
            'q' => $address,
            'addressdetails' => 1,
            'limit' => 5
        ]);
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => ["User-Agent: $userAgent", 'Accept: application/json'],
                'timeout' => 10
            ]
        ];
        $context = stream_context_create($options);
        $response = @file_get_contents($base . $query, false, $context);
        if ($response === false) {
            $fallbackQuery = http_build_query([
                'format' => 'json',
                'q' => $address,
                'addressdetails' => 1,
                'limit' => 5,
                'accept-language' => 'en'
            ]);
            $response = @file_get_contents($base . $fallbackQuery, false, $context);
            if ($response === false) {
                throw new RuntimeException('Geocoding service unavailable. Please try again later.');
            }
        }
        return json_decode($response, true) ?: [];
    }

    private function getCache(string $cacheKey): ?array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT result FROM geocode_cache WHERE cache_key = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)');
            $stmt->execute([$cacheKey]);
            $row = $stmt->fetch();
            return $row ? json_decode($row['result'], true) : null;
        } catch (PDOException $e) {
            error_log('Cache read error: ' . $e->getMessage());
            return null;
        }
    }

    private function storeCache(string $cacheKey, string $address, array $data): void
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO geocode_cache (cache_key,address,result,created_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE result = ?, created_at = NOW()');
            $json = json_encode($data);
            $stmt->execute([$cacheKey, $address, $json, $json]);
        } catch (PDOException $e) {
            error_log('Cache write error: ' . $e->getMessage());
        }
    }

    private function logUsage(string $address, int $count): void
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO geocoding_logs (address,results_count,created_at) VALUES (?,?,NOW())');
            $stmt->execute([$address, $count]);
        } catch (PDOException $e) {
            error_log('Log write error: ' . $e->getMessage());
        }
    }
}
