<?php

namespace App;

use App\Cache\RedisCache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WeatherAPI
{
    private $client;
    private $cache;
    private const CACHE_TTL = 43200; // 12 hours in seconds

    public function __construct(RedisCache $cache)
    {
        error_log("WeatherAPI constructor called");
        $this->client = new Client([
            'base_uri' => 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/',
            'timeout' => 5.0,
            'http_errors' => true,
        ]);
        $this->cache = $cache;
    }

    private function formatWeatherData(array $data): array
    {
        $current = $data['currentConditions'] ?? [];
        $location = $data['resolvedAddress'] ?? $data['address'] ?? 'Unknown Location';
        
        return [
            'location' => $location,
            'current_weather' => [
                'temperature' => isset($current['temp']) ? round($current['temp']) . '°C' : 'N/A',
                'conditions' => $current['conditions'] ?? 'N/A',
                'humidity' => isset($current['humidity']) ? $current['humidity'] . '%' : 'N/A',
                'wind_speed' => isset($current['windspeed']) ? round($current['windspeed']) . ' km/h' : 'N/A',
                'feels_like' => isset($current['feelslike']) ? round($current['feelslike']) . '°C' : 'N/A',
            ],
            'last_updated' => isset($current['datetime']) ? date('H:i', strtotime($current['datetime'])) : 'N/A'
        ];
    }

    public function getWeather(string $city): array
    {
        error_log("Getting weather for city: " . $city);
        
        try {
            // Check cache first
            $cacheKey = "weather:" . md5($city);
            $cachedData = $this->cache->get($cacheKey);
            
            if ($cachedData !== null) {
                error_log("Cache hit for city: " . $city);
                return $this->formatWeatherData(json_decode($cachedData, true));
            }

            error_log("Cache miss for city: " . $city);

            // If not in cache, fetch from API
            $apiKey = $_ENV['VISUAL_CROSSING_API_KEY'] ?? '';
            if (empty($apiKey)) {
                error_log("API key not configured");
                throw new \Exception('API key not configured');
            }

            error_log("Making API request for city: " . $city);
            $response = $this->client->get(urlencode($city), [
                'query' => [
                    'unitGroup' => 'metric',
                    'key' => $apiKey,
                    'contentType' => 'json',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from API');
            }

            error_log("API response received for city: " . $city);
            
            // Cache the full response
            $this->cache->set($cacheKey, json_encode($data), self::CACHE_TTL);
            
            // Return formatted data
            return $this->formatWeatherData($data);
        } catch (GuzzleException $e) {
            error_log("API request error: " . $e->getMessage());
            throw new \Exception('Failed to fetch weather data: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log("Error getting weather: " . $e->getMessage());
            throw $e;
        }
    }
}
