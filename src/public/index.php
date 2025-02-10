<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\WeatherAPI;
use App\Cache\RedisCache;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Enable error reporting in development only
if ($_ENV['APP_ENV'] ?? 'production' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set default timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

/**
 * Handle CORS
 */
function setCorsHeaders(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

/**
 * Send JSON response
 */
function sendJsonResponse(mixed $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    exit;
}

// Basic routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Set CORS headers for all responses
setCorsHeaders();

try {
    // Documentation UI endpoint
    if ($method === 'GET' && ($uri === '/docs' || $uri === '/docs/')) {
        header('Content-Type: text/html; charset=utf-8');
        include __DIR__ . '/../docs/index.html';
        exit;
    }

    // Documentation API endpoint
    if ($method === 'GET' && $uri === '/api/documentation') {
        $yamlFile = __DIR__ . '/../docs/openapi.yaml';
        if (!file_exists($yamlFile)) {
            throw new RuntimeException('API documentation file not found');
        }

        $yaml = file_get_contents($yamlFile);
        if ($yaml === false) {
            throw new RuntimeException('Failed to read API documentation file');
        }

        $data = yaml_parse($yaml);
        if ($data === false) {
            throw new RuntimeException('Failed to parse API documentation file');
        }

        sendJsonResponse($data);
    }

    // Weather endpoint
    if ($method === 'GET' && preg_match('/^\/weather\/([^\/]+)$/', $uri, $matches)) {
        // Create Redis cache instance
        $redis = new RedisCache($_ENV['REDIS_HOST'] ?? 'redis');

        // Create Weather API instance with cache enabled/disabled based on config
        $weatherApi = new WeatherAPI(
            $redis, 
            filter_var($_ENV['CACHE_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN)
        );

        $city = urldecode($matches[1]);
        sendJsonResponse($weatherApi->getWeather($city));
    }

    // Default response for undefined routes
    sendJsonResponse(
        ['error' => true, 'message' => 'Not Found'],
        404
    );

} catch (JsonException $e) {
    sendJsonResponse(
        ['error' => true, 'message' => 'Invalid JSON data'],
        500
    );
} catch (InvalidArgumentException $e) {
    sendJsonResponse(
        ['error' => true, 'message' => $e->getMessage()],
        400
    );
} catch (RuntimeException $e) {
    sendJsonResponse(
        ['error' => true, 'message' => $e->getMessage()],
        500
    );
} catch (Throwable $e) {
    // Log unexpected errors
    error_log("Unexpected error: " . $e->getMessage());
    sendJsonResponse(
        ['error' => true, 'message' => 'Internal Server Error'],
        500
    );
}
