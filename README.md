# Classified Ads API

Laravel API for classified ads with dynamic category fields, similar to OLX.

## Requirements

- PHP 8.1+
- MySQL/PostgreSQL
- Composer

**OR**

- Docker & Docker Compose

## Installation

### Option 1: Local Setup

```bash
# Clone and install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations and seed with OLX data
php artisan migrate
php artisan db:seed
```

### Option 2: Docker Setup

**Easy way (automated script):**
```bash
chmod +x docker-setup.sh
./docker-setup.sh
```

**Manual way:**
```bash
# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Generate app key
docker-compose exec app php artisan key:generate

# Run migrations and seed database
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

**Docker Services:**
- API: http://localhost:8000
- phpMyAdmin: http://localhost:8080 (user: `laravel`, pass: `secret`)
- MySQL: localhost:3307
- Redis: localhost:6379

**Useful Docker Commands:**
```bash
# Stop containers
docker-compose down

# View logs
docker-compose logs -f app

# Access container shell
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan [command]

# Fresh database (reset + seed)
docker-compose exec app php artisan migrate:fresh --seed
```

## Quick Start

### Local
```bash
php artisan serve
# Base URL: http://localhost:8000/api/v1
```

### Docker
```bash
docker-compose up -d
# Base URL: http://localhost:8000/api/v1
```

## Database Seeding

The seeder pulls real data from OLX Lebanon API:

```bash
# Seed database (local)
php artisan db:seed

# Or with Docker
docker-compose exec app php artisan db:seed

# Fresh start (drop everything and reseed)
php artisan migrate:fresh --seed

# Or with Docker
docker-compose exec app php artisan migrate:fresh --seed
```

**What gets seeded:**
- 114 categories from OLX (13 parent + 101 subcategories)
- All category-specific fields (make, year, bedrooms, etc.)
- Field options (car brands, colors, property types, etc.)

Data is cached for 24 hours. Clear cache to fetch fresh data:
```bash
php artisan olx:clear-cache
```

## Postman Collection

Import `Classified_Ads_API.postman_collection.json` for testing. Contains examples for:
- User registration/login
- Browse categories and fields
- Create ads with dynamic fields
- Get user's ads

Set these variables in Postman:
- `base_url`: http://localhost:8000/api/v1
- `token`: Your auth token (auto-set after login)

## Main Endpoints

### Auth
```
POST   /register
POST   /login
POST   /logout
```

### Categories
```
GET    /categories
GET    /categories/{id}/fields
```

### Ads
```
GET    /ads
POST   /ads
GET    /ads/{id}
GET    /my-ads
```

## Creating an Ad

Each category has different required fields. Check available fields first:

```bash
GET /categories/2/fields
```

Then create ad with dynamic fields:

```json
{
  "category_id": 2,
  "title": "2020 Toyota Camry",
  "description": "Well maintained",
  "price": 15000,
  "fields": {
    "make": "36",
    "year": 2020,
    "mileage": 35000,
    "petrol": "1",
    "transmission": "1",
    "body_type": "2",
    "color": "1",
    "new_used": "2",
    "price_type": "price"
  }
}
```

Validation errors include helpful info about required fields and valid options.

## Features

- **Dynamic Fields**: Categories have custom fields (text, number, select, etc.)
- **OLX Integration**: Seeded with real OLX Lebanon categories and fields
- **Smart Validation**: Field validation based on category
- **Helpful Errors**: Validation errors show available fields
- **Clean Architecture**: Action/Repository pattern with proper separation

## Testing

```bash
# Local
php artisan test

# Docker
docker-compose exec app php artisan test

# Or use PHPUnit
vendor/bin/phpunit
```

## Code Structure

```
app/
├── Actions/          # Business logic
├── Repositories/     # Data access
├── Http/
│   ├── Controllers/  # HTTP layer
│   ├── Requests/     # Validation
│   └── Resources/    # Response formatting
├── Models/           # Eloquent models
└── Services/         # External APIs
```

See `ARCHITECTURE.md` for detailed docs.

## Common Issues

**Docker: Permission denied on storage**
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

**Seeding fails: Connection refused**
Make sure database is running and credentials in `.env` are correct.

**Port already in use**
Change ports in `docker-compose.yml` if 8000, 8080, or 3307 are taken.

## License

MIT
