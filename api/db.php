<?php
/**
 * Database connection configuration
 * Supports both Docker environment variables and traditional configuration
 */

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'property_research';
$username = getenv('DB_USER') ?: 'property_user';
$password = getenv('DB_PASS') ?: 'property_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Service temporarily unavailable. Please try again later.']));
}
