# PHP Weather API

A modern PHP-based Weather API that provides current weather data using the Visual Crossing Weather API. Features include Redis caching, OpenAPI documentation, and Docker deployment.

## Features

- 🌤 Real-time weather data from Visual Crossing
- 📦 Redis caching for improved performance
- 📚 OpenAPI/Swagger documentation
- 🐳 Docker and Docker Compose setup
- 🔒 Environment-based configuration
- ⚡ CORS support
- 🚀 Production/Development modes

## Prerequisites

Before you begin, ensure you have:
- Docker Desktop installed and running ([Download here](https://www.docker.com/products/docker-desktop/))
- Visual Crossing API key ([Get free key here](https://www.visualcrossing.com/weather-api))
- Git installed ([Download here](https://git-scm.com/downloads))
- Port 8080 available on your machine

## Installation & Running (Step by Step)

### 1. Get the Code
```bash
# Clone the repository
git clone https://github.com/yourusername/php-weather-api.git

# Navigate to project directory
cd php-weather-api
```

### 2. Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Open .env in your favorite editor (e.g., vim, nano, VS Code)
# Replace these values:
VISUAL_CROSSING_API_KEY=your_api_key_here  # Add your API key
APP_ENV=development                        # Keep as development for testing
APP_DEBUG=true                            # Keep as true for testing
```

### 3. Start the Application
```bash
# Build and start containers
docker-compose up -d

# Verify containers are running
docker-compose ps
```

You should see two containers running:
- `php-weather-api-php-1`
- `php-weather-api-redis-1`

### 4. Verify Installation

1. Check API Status:
```bash
# Test with curl
curl "http://localhost:8080/weather/London"

# Or open in your browser:
# http://localhost:8080/weather/London
```

2. Access Documentation:
```bash
# Open in your browser:
# http://localhost:8080/docs/
```

Expected Response:
```json
{
    "location": "London, England",
    "current_weather": {
        "temperature": "18°C",
        "conditions": "Partly cloudy",
        "humidity": "65%",
        "wind_speed": "12 km/h",
        "feels_like": "17°C"
    },
    "last_updated": "14:30",
    "cached": false
}
```

### 5. Common Issues & Solutions

1. Port Conflict:
```bash
# If port 8080 is in use, edit docker-compose.yml:
ports:
  - "8081:80"  # Change 8080 to any available port
```

2. Container Issues:
```bash
# View container logs
docker-compose logs

# Restart containers
docker-compose restart

# Rebuild containers
docker-compose up -d --build
```

3. Redis Connection:
```bash
# Check Redis connection
docker-compose exec redis redis-cli ping
# Should return: PONG
```

### 6. Stopping the Application
```bash
# Stop containers but keep data
docker-compose stop

# Stop containers and remove data
docker-compose down
```

### 7. Updating the Application
```bash
# Pull latest changes
git pull

# Rebuild containers
docker-compose up -d --build
```

## Testing the API

Once running, you can:

1. Use Swagger UI:
   - Open `http://localhost:8080/docs/`
   - Try out the /weather endpoint

2. Use curl:
```bash
# Get weather for a city
curl "http://localhost:8080/weather/London"

# Get weather for a city with spaces
curl "http://localhost:8080/weather/New%20York"
```

3. Use your browser:
   - Open `http://localhost:8080/weather/London`
   - Open `http://localhost:8080/weather/Tokyo`

## API Endpoints

### Get Weather Data
```http
GET /weather/{city}
```

Parameters:
- `city` (string, required): Name of the city to get weather for

Example Response:
```json
{
    "location": "London, England",
    "current_weather": {
        "temperature": "18°C",
        "conditions": "Partly cloudy",
        "humidity": "65%",
        "wind_speed": "12 km/h",
        "feels_like": "17°C"
    },
    "last_updated": "14:30",
    "cached": false
}
```

## Documentation

- API Documentation UI: `http://localhost:8080/docs/`
- OpenAPI JSON: `http://localhost:8080/api/documentation`

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| APP_ENV | Application environment (development/production) | development |
| APP_DEBUG | Enable debug mode | true |
| APP_TIMEZONE | Application timezone | UTC |
| CACHE_ENABLED | Enable Redis caching | true |
| CACHE_TTL | Cache duration in seconds | 43200 (12 hours) |
| REDIS_HOST | Redis host | redis |
| REDIS_PORT | Redis port | 6379 |

See `.env.example` for all available options.

## Caching

The API uses Redis for caching weather data:
- Cache duration: 12 hours by default (configurable)
- Cache key format: `weather:md5(city_name)`
- Automatic cache invalidation
- Cache can be disabled via CACHE_ENABLED environment variable

## Development

### Project Structure
```
php-weather-api/
├── src/
│   ├── public/         # Web root
│   ├── Cache/          # Cache implementation
│   ├── docs/           # API documentation
│   └── WeatherAPI.php  # Core API logic
├── docker-compose.yml  # Docker composition
├── Dockerfile         # PHP container definition
└── apache.conf       # Apache configuration
```

### Local Development

1. Set APP_ENV to development in .env:
```ini
APP_ENV=development
APP_DEBUG=true
```

2. Rebuild containers with development settings:
```bash
docker-compose up -d --build
```

## Production Deployment

1. Update environment settings:
```ini
APP_ENV=production
APP_DEBUG=false
CACHE_ENABLED=true
```

2. Ensure secure values for:
- VISUAL_CROSSING_API_KEY
- REDIS_PASSWORD (if used)

3. Deploy using Docker:
```bash
docker-compose -f docker-compose.yml up -d
```

## Error Handling

The API uses standard HTTP status codes:
- 200: Successful request
- 400: Invalid input
- 404: City not found
- 500: Server error

Error responses include:
```json
{
    "error": true,
    "message": "Error description"
}
```

## Security

- API keys stored in environment variables
- Production error messages don't expose system details
- Input validation and sanitization
- CORS headers for API access control

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- [Visual Crossing](https://www.visualcrossing.com/) for weather data
- [OpenAPI/Swagger](https://swagger.io/) for API documentation
- [Redis](https://redis.io/) for caching
- [Docker](https://www.docker.com/) for containerization
