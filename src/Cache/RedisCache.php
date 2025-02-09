<?php

namespace App\Cache;

use Predis\Client;

class RedisCache
{
    private $redis;

    public function __construct(string $host = null)
    {
        error_log("Initializing Redis connection to host: " . ($host ?? $_ENV['REDIS_HOST']));
        try {
            $this->redis = new Client([
                'scheme' => 'tcp',
                'host' => $host ?? $_ENV['REDIS_HOST'],
                'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
                'read_write_timeout' => (int)($_ENV['REDIS_TIMEOUT'] ?? 0)
            ]);
            
            // Test connection
            $this->redis->ping();
            error_log("Redis connection successful");
        } catch (\Exception $e) {
            error_log("Redis connection error: " . $e->getMessage());
            throw new \Exception('Failed to connect to Redis: ' . $e->getMessage());
        }
    }

    public function get(string $key)
    {
        try {
            error_log("Getting from Redis, key: " . $key);
            $value = $this->redis->get($key);
            error_log("Redis get result for key {$key}: " . ($value ? "hit" : "miss"));
            return $value;
        } catch (\Exception $e) {
            error_log("Redis get error: " . $e->getMessage());
            throw new \Exception('Redis get operation failed: ' . $e->getMessage());
        }
    }

    public function set(string $key, string $value, int $ttl = 3600): bool
    {
        try {
            error_log("Setting Redis key: " . $key);
            $this->redis->setex($key, $ttl, $value);
            error_log("Successfully set Redis key: " . $key);
            return true;
        } catch (\Exception $e) {
            error_log("Redis set error: " . $e->getMessage());
            throw new \Exception('Redis set operation failed: ' . $e->getMessage());
        }
    }
}
