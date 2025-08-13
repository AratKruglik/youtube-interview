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

===

<laravel-boost-guidelines>
=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.
</laravel-boost-guidelines>