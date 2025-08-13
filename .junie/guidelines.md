# Laravel Interview Project - Development Guidelines

## Build/Configuration Instructions

### Initial Project Setup
1. **Environment Configuration**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

2. **Database Setup**:
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

3. **Frontend Dependencies**:
   ```bash
   npm install
   ```

### Development Server
- **Backend**: `php artisan serve` (starts on http://localhost:8000)
- **Frontend Assets**: `npm run dev` (for development with hot reload)
- **Combined Development**: `composer run dev` (runs server, queue, logs, and Vite concurrently)

### Production Build
- **Frontend Assets**: `npm run build`
- **Application**: Standard Laravel deployment procedures

## Testing Information

### Test Configuration
- **PHPUnit Configuration**: `phpunit.xml` with separate Unit and Feature test suites
- **Testing Environment**: Uses SQLite in-memory database, array cache/session drivers
- **Test Database**: Automatically configured via phpunit.xml environment variables

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test
php artisan test --filter=SimpleTest

# Alternative PHPUnit command
vendor/bin/phpunit
```

### Test Structure
- **Unit Tests**: Located in `tests/Unit/`, extend `PHPUnit\Framework\TestCase`
- **Feature Tests**: Located in `tests/Feature/`, extend `Tests\TestCase` (Laravel's TestCase)
- **Custom TestCase**: `tests/TestCase.php` - minimal wrapper around Laravel's base TestCase

### Adding New Tests
```bash
# Create Feature test
php artisan make:test ExampleFeatureTest

# Create Unit test
php artisan make:test ExampleUnitTest --unit
```

### Test Example (Unit Test)
```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function test_addition(): void
    {
        $result = 2 + 3;
        $this->assertEquals(5, $result);
    }
}
```

## Additional Development Information

### Technology Stack
- **Framework**: Laravel 12.0
- **PHP**: 8.2+
- **Database**: SQLite (default), configurable to MySQL/PostgreSQL
- **Frontend**: Vite + TailwindCSS 4.0
- **Testing**: PHPUnit 11.5.3
- **Code Style**: Laravel Pint

### Laravel-Specific Configuration
- **Streamlined Structure**: Uses Laravel 11+ file structure (no middleware files, simplified bootstrap)
- **Database Services**: Sessions, cache, and queue use database drivers by default
- **Environment**: Local development uses `APP_ENV=local` with debug enabled
- **Logging**: Single stack configuration for local development

### Code Formatting
```bash
# Format code according to project standards
vendor/bin/pint --dirty

# Check formatting without fixing
vendor/bin/pint --test
```

### Key Configuration Files
- **Environment**: `.env` (copy from `.env.example`)
- **Database**: `database/database.sqlite` (SQLite file)
- **Frontend Build**: `vite.config.js` with Laravel plugin and TailwindCSS
- **Dependencies**: `composer.json` and `package.json`
- **Testing**: `phpunit.xml` with in-memory database configuration

### Development Tools Available
- **Laravel Tinker**: `php artisan tinker` (interactive shell)
- **Laravel Pail**: Real-time log monitoring
- **Laravel Sail**: Docker development environment (configured but optional)
- **Laravel Boost**: MCP server for enhanced development tools

### Database Notes
- Uses SQLite by default for simplicity
- Database file must be created manually: `touch database/database.sqlite`
- Migrations include users, cache, and jobs tables
- Queue jobs use database driver by default

### Frontend Asset Compilation
- Entry points: `resources/css/app.css`, `resources/js/app.js`
- TailwindCSS 4.0 with Vite plugin
- Hot reload available during development
- Build files output to `public/build/`
