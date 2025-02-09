<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\WeatherAPI;
use App\Cache\RedisCache;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON API
header('Content-Type: application/json');

try {
    // Create Redis cache instance
    $redis = new RedisCache($_ENV['REDIS_HOST'] ?? 'redis');

    // Create Weather API instance
    $weatherApi = new WeatherAPI($redis);

    // Basic routing
    $uri = $_SERVER['REQUEST_URI'];
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET' && preg_match('/^\/weather\/([^\/]+)$/', $uri, $matches)) {
        $city = urldecode($matches[1]);
        echo json_encode($weatherApi->getWeather($city));
        exit;
    }

    // Default response for undefined routes
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
