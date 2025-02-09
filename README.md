# PHP Weather API

A simple and efficient weather API service built with PHP that provides current weather information using the Visual Crossing Weather API. The service includes Redis caching for improved performance.

## Features

- Get current weather information by city name
- Redis caching with 12-hour TTL
- Docker containerization
- Clean and formatted weather data output
- Error handling and logging

## Prerequisites

- Docker
- Docker Compose
- Visual Crossing API key (get it from [Visual Crossing Weather](https://www.visualcrossing.com/weather-api))
- Git

## Project Structure

```
php-weather-api/
├── src/
│   ├── public/
│   │   └── index.php         # Main entry point
│   ├── Cache/
│   │   └── RedisCache.php    # Redis cache implementation
│   └── WeatherAPI.php        # Weather API implementation
├── Dockerfile                # PHP container configuration
├── docker-compose.yml        # Docker services configuration
├── apache.conf              # Apache configuration
├── composer.json            # PHP dependencies
└── .env                     # Environment variables
```

## Quick Start

1. Clone the repository:
   ```bash
   git clone [repository-url]
   cd php-weather-api
   ```

2. Copy the example environment file and configure it:
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` and add your Visual Crossing API key:
   ```env
   # API Configuration
   VISUAL_CROSSING_API_KEY=your_api_key_here

   # Redis Configuration
   REDIS_HOST=redis
   REDIS_PORT=6379
   REDIS_PASSWORD=
   REDIS_TIMEOUT=0
   REDIS_DATABASE=0
   ```

3. Build and start the Docker containers:
   ```bash
   docker-compose up -d
   ```

4. Install PHP dependencies:
   ```bash
   docker-compose exec php composer install
   ```

5. Verify the installation:
   ```bash
   curl "http://localhost:8080/weather/London"
   ```

## API Endpoints

### Get Weather Data
```
GET /weather/{city}
```

Example request:
```bash
curl "http://localhost:8080/weather/London"
```

Example response:
```json
{
    "location": "London, England, United Kingdom",
    "current_weather": {
        "temperature": "12°C",
        "conditions": "Partly cloudy",
        "humidity": "76%",
        "wind_speed": "15 km/h",
        "feels_like": "10°C"
    },
    "last_updated": "16:00"
}
```

## Caching

- Weather data is cached in Redis for 12 hours
- Cache keys are generated using MD5 hash of the city name
- If cached data exists, it will be returned instead of making an API call

## Redis Configuration

The Redis connection can be configured using the following environment variables:

- `REDIS_HOST`: Redis server hostname (default: redis)
- `REDIS_PORT`: Redis server port (default: 6379)
- `REDIS_PASSWORD`: Redis password if authentication is required (default: none)
- `REDIS_TIMEOUT`: Redis connection timeout in seconds (default: 0)
- `REDIS_DATABASE`: Redis database number (default: 0)

## Development

### Useful Docker Commands

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec php bash

# Access Redis CLI
docker-compose exec redis redis-cli
```

### Redis Cache Management

```bash
# List all cached keys
docker exec -it php-weather-api-redis-1 redis-cli keys '*'

# Get TTL for a specific key
docker exec -it php-weather-api-redis-1 redis-cli ttl "weather:key_hash"

# Monitor Redis operations in real-time
docker exec -it php-weather-api-redis-1 redis-cli monitor

# Clear all Redis cache
docker exec -it php-weather-api-redis-1 redis-cli FLUSHALL
```

## Troubleshooting

1. **API Key Issues**:
   - Verify your API key in `.env`
   - Check API key permissions at Visual Crossing dashboard

2. **Redis Connection Issues**:
   - Ensure Redis container is running: `docker-compose ps`
   - Check Redis logs: `docker-compose logs redis`
   - Verify Redis configuration in `.env`

3. **Container Issues**:
   - Rebuild containers: `docker-compose up -d --build`
   - Check logs: `docker-compose logs`
   - Verify ports are not in use

## Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin feature/my-new-feature`
5. Submit a pull request

## License

[Your License Here]

## Support

For support, please create an issue in the GitHub repository.
