<?php

namespace App;

use App\Cache\RedisCache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;
use Exception;

class WeatherAPI
{
    private const CACHE_TTL = 43200; // 12 hours in seconds
    private const UNITS = 'metric';
    private const DEFAULT_TIMEOUT = 5.0;

    private Client $client;
    private RedisCache $cache;
    private bool $cacheEnabled;

    public function __construct(RedisCache $cache, bool $cacheEnabled = true)
    {
        $this->cache = $cache;
        $this->cacheEnabled = $cacheEnabled;
        $this->client = new Client([
            'base_uri' => 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/',
            'timeout' => self::DEFAULT_TIMEOUT,
            'http_errors' => true,
        ]);
    }

    public function getWeather(string $city): array
    {
        if (empty(trim($city))) {
            throw new InvalidArgumentException('City name cannot be empty');
        }

        try {
            return $this->getWeatherData($city);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Failed to fetch weather data: ' . $e->getMessage(), 0, $e);
        }
    }

    private function getWeatherData(string $city): array
    {
        if (!$this->cacheEnabled) {
            return $this->fetchAndCacheWeatherData($city, '');
        }

        try {
            $cacheKey = $this->generateCacheKey($city);
            $cachedData = $this->getCachedData($cacheKey);
            
            if ($cachedData !== null) {
                return $cachedData;
            }

            return $this->fetchAndCacheWeatherData($city, $cacheKey);
        } catch (Exception $e) {
            // If there's any cache-related error, fallback to direct API call
            error_log("Cache error: " . $e->getMessage() . ". Falling back to API.");
            return $this->fetchAndCacheWeatherData($city, '');
        }
    }

    private function generateCacheKey(string $city): string
    {
        return "weather:" . md5(strtolower(trim($city)));
    }

    private function getCachedData(string $cacheKey): ?array
    {
        if (empty($cacheKey)) {
            return null;
        }

        try {
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData === null) {
                return null;
            }

            $data = json_decode($cachedData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Invalid JSON in cache, remove it
                $this->cache->delete($cacheKey);
                return null;
            }

            $response = $this->formatWeatherData($data);
            $response['cached'] = true;
            return $response;
        } catch (Exception $e) {
            error_log("Error retrieving from cache: " . $e->getMessage());
            return null;
        }
    }

    private function fetchAndCacheWeatherData(string $city, string $cacheKey): array
    {
        $apiKey = $this->getApiKey();
        $response = $this->client->get(urlencode($city), [
            'query' => [
                'unitGroup' => self::UNITS,
                'key' => $apiKey,
                'contentType' => 'json',
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON response from API');
        }

        // Only cache if caching is enabled and we have a cache key
        if ($this->cacheEnabled && !empty($cacheKey)) {
            try {
                $this->cache->set($cacheKey, json_encode($data), self::CACHE_TTL);
            } catch (Exception $e) {
                error_log("Failed to cache weather data: " . $e->getMessage());
                // Continue even if caching fails
            }
        }
        
        $formattedData = $this->formatWeatherData($data);
        $formattedData['cached'] = false;
        return $formattedData;
    }

    private function getApiKey(): string
    {
        $apiKey = $_ENV['VISUAL_CROSSING_API_KEY'] ?? '';
        if (empty($apiKey)) {
            throw new RuntimeException('API key not configured');
        }
        return $apiKey;
    }

    private function formatWeatherData(array $data): array
    {
        $current = $data['currentConditions'] ?? [];
        $location = $this->getLocation($data);
        
        return [
            'location' => $location,
            'current_weather' => [
                'temperature' => $this->formatTemperature($current['temp'] ?? null),
                'conditions' => $current['conditions'] ?? 'N/A',
                'humidity' => $this->formatHumidity($current['humidity'] ?? null),
                'wind_speed' => $this->formatWindSpeed($current['windspeed'] ?? null),
                'feels_like' => $this->formatTemperature($current['feelslike'] ?? null),
            ],
            'last_updated' => $this->formatLastUpdated($current['datetime'] ?? null)
        ];
    }

    private function getLocation(array $data): string
    {
        return $data['resolvedAddress'] ?? $data['address'] ?? 'Unknown Location';
    }

    private function formatTemperature(?float $temp): string
    {
        return isset($temp) ? round($temp) . '°C' : 'N/A';
    }

    private function formatHumidity(?float $humidity): string
    {
        return isset($humidity) ? $humidity . '%' : 'N/A';
    }

    private function formatWindSpeed(?float $speed): string
    {
        return isset($speed) ? round($speed) . ' km/h' : 'N/A';
    }

    private function formatLastUpdated(?string $datetime): string
    {
        return isset($datetime) ? date('H:i', strtotime($datetime)) : 'N/A';
    }
}
