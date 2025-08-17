# WhatsApp Agent - GitHub Copilot Development Instructions

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Bootstrap, Build, and Test the Repository:
- **Environment Requirements**: Docker 20.10+, Docker Compose 2.0+, Git
- **CRITICAL**: Copy environment configuration: `cp .env.docker .env`
- **Build Command**: `docker compose up --build -d`
- **Build Time**: Takes 3-6 minutes for initial build. NEVER CANCEL. Set timeout to 15+ minutes.
- **Initialization Time**: Additional 2-4 minutes for Laravel dependencies and database setup
- **Network Limitations**: In restricted environments, npm/composer may be slow or fail due to SSL/network issues

### Build Process Details:
1. **Docker Services Build** (30-90 seconds):
   - PHP-FPM containers (app, queue) with Laravel dependencies
   - Node.js WhatsApp bridge service (may fail in restricted networks)
   - Supporting services: MySQL, Redis, Nginx, Mailpit, Selenium, Ollama

2. **Laravel Initialization** (60-180 seconds):
   - Composer dependency installation (may take 3-5 minutes in restricted networks)
   - Database migrations and setup
   - Cache optimization
   - Permission configuration

3. **Service Startup** (10-30 seconds):
   - All services start and become available
   - Nginx may restart several times until PHP-FPM is ready

### Core Commands:
```bash
# Standard build and start
docker compose up -d

# Complete rebuild from scratch - NEVER CANCEL, takes 6-10 minutes
./docker/scripts/rebuild.sh

# Manual rebuild
docker compose down --volumes --remove-orphans
docker compose up --build -d

# Check status
docker compose ps

# View logs
docker compose logs app
docker compose logs -f  # follow logs

# Test application
curl -I http://localhost:8082/
```

## Service Configuration

### Ports and Services:
| Service | Local Port | Internal Port | Description |
|---------|------------|---------------|-------------|
| Application | http://localhost:8082 | 80 | Main Laravel web app |
| MySQL | localhost:3310 | 3306 | Database |
| Redis | localhost:6382 | 6379 | Cache/Sessions |
| Mailpit | http://localhost:8027 | 8025 | Email testing interface |
| Selenium | localhost:4446 | 4444 | E2E testing |
| Ollama | localhost:11434 | 11434 | AI services |
| WhatsApp Bridge | localhost:3000 | 3000 | Node.js WhatsApp API |

### Environment Configuration:
- **Primary config**: `.env.docker` → copy to `.env`
- **Database**: MySQL on port 3310, database `genericsaas`
- **Cache/Sessions**: Redis on port 6382
- **Email**: Mailpit for local email testing

## Development Workflows

### Laravel Development:
```bash
# Access Laravel container
docker exec -it genericsaas_app bash

# Laravel commands
docker exec genericsaas_app php artisan migrate
docker exec genericsaas_app php artisan make:controller UserController
docker exec genericsaas_app php artisan queue:work
docker exec genericsaas_app php artisan tinker

# Database operations
docker exec genericsaas_app php artisan migrate:status
docker exec genericsaas_app php artisan migrate:rollback
docker exec genericsaas_app php artisan db:seed

# Clear caches
docker exec genericsaas_app php artisan optimize:clear
```

### Node.js WhatsApp Bridge:
```bash
# Access WhatsApp bridge container (if running)
docker exec -it genericsaas_whatsapp_bridge bash

# Check bridge logs
docker compose logs whatsapp-bridge

# Restart bridge service
docker compose restart whatsapp-bridge
```

## Testing and Validation

### Manual Validation Requirements:
ALWAYS run through at least one complete end-to-end scenario after making changes:

1. **Basic Application Test**:
   ```bash
   # Check all services are running
   docker compose ps
   
   # Test web application response
   curl -I http://localhost:8082/
   
   # Check application loads in browser
   # Expected: Laravel application with login page
   ```

2. **Database Connectivity Test**:
   ```bash
   # Test database connection
   docker exec genericsaas_app php artisan migrate:status
   
   # Should show migration table without errors
   ```

3. **Cache/Session Test**:
   ```bash
   # Test Redis connection
   docker exec genericsaas_app php artisan cache:clear
   
   # Should complete without Redis connection errors
   ```

4. **Email Testing**:
   - Access http://localhost:8027
   - Should show Mailpit interface

### Testing Commands:
```bash
# Laravel tests
docker exec genericsaas_app php artisan test

# Browser tests (Dusk) - requires running application
docker exec genericsaas_app php artisan dusk

# Custom test scripts
./tests/run-registration-tests.sh
./tests/run-profile-tests.sh
```

## Known Issues and Troubleshooting

### Common Build Issues:

1. **WhatsApp Bridge npm SSL Errors**:
   - Common in restricted network environments
   - Workaround: Build without whatsapp-bridge service initially
   - Command: `docker compose up --build -d app queue nginx mysql redis mailpit selenium ollama`

2. **Composer SSL/Network Issues**:
   - Composer may fallback to git source downloads (slower but works)
   - Expected behavior in restricted environments
   - Allow 3-5 minutes for completion

3. **Nginx "host not found in upstream app" Error**:
   - Normal during startup - nginx starts before PHP-FPM is ready
   - Nginx will automatically restart and connect once PHP-FPM is available
   - No action needed, wait 1-2 minutes

4. **Permission Issues**:
   ```bash
   # Fix Laravel permissions if needed
   docker exec genericsaas_app chown -R www-data:www-data storage bootstrap/cache
   docker exec genericsaas_app chmod -R 775 storage bootstrap/cache
   ```

### Port Conflicts:
If ports are in use, modify `.env`:
```env
NGINX_PORT=8083
MYSQL_PORT=3311
REDIS_PORT=6383
```

### Complete Reset:
```bash
# Nuclear option - removes all data
docker compose down --volumes --remove-orphans
docker system prune -f
# Then rebuild from scratch
```

## Project Structure

### Key Directories:
- `app/` - Laravel application code
- `resources/views/` - Blade templates
- `routes/web.php` - Web routes
- `database/migrations/` - Database migrations
- `database/seeders/` - Database seeders
- `docker/` - Docker configuration
- `nodejs/whatsapp-bridge/` - Node.js WhatsApp integration
- `tests/` - Laravel tests

### Important Files:
- `.env.docker` - Docker environment template
- `docker-compose.yml` - Service orchestration
- `composer.json` - PHP dependencies
- `package.json` - Frontend build tools
- `BUILD.md` - Detailed build documentation

## Validation Requirements

### Before Committing Changes:
- ALWAYS run `docker compose ps` to ensure all services are healthy
- ALWAYS test the application loads at http://localhost:8082/
- ALWAYS run `docker compose logs app` to check for errors
- For Laravel changes: run `docker exec genericsaas_app php artisan test`
- For frontend changes: test in browser with real user interactions

### CI/Build Validation:
- The build process must complete successfully
- All core services (app, queue, nginx, mysql, redis) must start
- Application must respond to HTTP requests
- Database migrations must run without errors

## Time Expectations

### Build Timing (with generous safety margins):
- **Initial Docker build**: 6-10 minutes. NEVER CANCEL. Set timeout to 15+ minutes.
- **Subsequent builds**: 1-3 minutes (cached)
- **Laravel initialization**: 2-5 minutes (network dependent)
- **Total startup time**: 8-15 minutes for fresh build

### Development Timing:
- **Code changes**: Near-instant with Docker volumes
- **Dependency updates**: 2-5 minutes for composer/npm
- **Database migrations**: 10-30 seconds
- **Cache clearing**: 5-10 seconds

## Success Indicators

✅ **Build Successful When**:
- `docker compose ps` shows all services "Up" (nginx may restart initially)
- `curl -I http://localhost:8082/` returns HTTP 200
- `docker compose logs app` shows "Laravel initialization completed successfully!"
- Application accessible in browser

❌ **Build Failed When**:
- Services showing "Exit" status after 10+ minutes
- HTTP 500 errors that persist after initialization
- Database connection errors in logs
- Composer/npm errors that cause container exit

This WhatsApp Agent codebase is a complex multi-service application. Following these instructions ensures reliable development and deployment.