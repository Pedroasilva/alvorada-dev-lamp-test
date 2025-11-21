# Makefile for Property Research System
# Provides convenient commands for development and deployment

.PHONY: help build up down restart logs logs-web logs-db shell shell-db clean test install status ps

# Default target
.DEFAULT_GOAL := help

## help: Show this help message
help:
	@echo "Property Research System - Available Commands:"
	@echo ""
	@sed -n 's/^##//p' ${MAKEFILE_LIST} | column -t -s ':' | sed -e 's/^/ /'
	@echo ""

## build: Build Docker containers
build:
	@echo "Building Docker containers..."
	docker-compose build

## up: Start the application in background
up:
	@echo "Starting application..."
	docker-compose up -d
	@echo "Application is running at http://localhost:8080"

## down: Stop the application
down:
	@echo "Stopping application..."
	docker-compose down

## restart: Restart the application
restart: down up

## logs: View logs from all containers
logs:
	docker-compose logs -f

## logs-web: View web server logs
logs-web:
	docker-compose logs -f web

## logs-db: View database logs
logs-db:
	docker-compose logs -f db

## ps: Show running containers
ps:
	docker-compose ps

## status: Show application status
status:
	@echo "=== Container Status ==="
	@docker-compose ps
	@echo ""
	@echo "=== Docker Resources ==="
	@docker stats --no-stream property_research_web property_research_db 2>/dev/null || echo "Containers not running"

## shell: Access web container shell
shell:
	docker-compose exec web bash

## shell-db: Access database shell
shell-db:
	docker-compose exec db mysql -u property_user -pproperty_password property_research

## shell-db-root: Access database as root
shell-db-root:
	docker-compose exec db mysql -u root -proot_password

## install: Initial setup - build and start
install: build up
	@echo ""
	@echo "Waiting for database to initialize..."
	@sleep 5
	@echo ""
	@echo "✓ Installation complete!"
	@echo "Access the application at http://localhost:8080"

## dev: Start in development mode with live logs
dev:
	docker-compose up

## clean: Stop and remove all containers, networks, and volumes
clean:
	@echo "Cleaning up Docker resources..."
	docker-compose down -v
	@echo "Cleanup complete!"

## clean-all: Remove containers, volumes, and images
clean-all:
	@echo "Removing all Docker resources..."
	docker-compose down -v --rmi all
	@echo "All resources removed!"

## db-reset: Reset database (WARNING: destroys all data)
db-reset:
	@echo "Resetting database..."
	docker-compose exec db mysql -u root -proot_password -e "DROP DATABASE IF EXISTS property_research;"
	docker-compose exec db mysql -u root -proot_password -e "CREATE DATABASE property_research CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	docker-compose exec db mysql -u property_user -pproperty_password property_research < sql/schema.sql
	@echo "Database reset complete!"

## db-backup: Backup database to sql/backup.sql
db-backup:
	@echo "Backing up database..."
	@mkdir -p sql/backups
	docker-compose exec -T db mysqldump -u property_user -pproperty_password property_research > sql/backups/backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "Backup saved to sql/backups/backup_$$(date +%Y%m%d_%H%M%S).sql"

## db-restore: Restore database from latest backup
db-restore:
	@echo "Restoring database from latest backup..."
	@LATEST=$$(ls -t sql/backups/*.sql 2>/dev/null | head -1); \
	if [ -z "$$LATEST" ]; then \
		echo "No backup files found in sql/backups/"; \
		exit 1; \
	fi; \
	echo "Restoring from $$LATEST..."; \
	docker-compose exec -T db mysql -u property_user -pproperty_password property_research < "$$LATEST"
	@echo "Database restored!"

## db-import: Import SQL file (usage: make db-import FILE=path/to/file.sql)
db-import:
	@if [ -z "$(FILE)" ]; then \
		echo "Error: Please specify FILE=path/to/file.sql"; \
		exit 1; \
	fi
	@echo "Importing $(FILE)..."
	docker-compose exec -T db mysql -u property_user -pproperty_password property_research < $(FILE)
	@echo "Import complete!"

## test: Run basic connectivity tests
test:
	@echo "Running connectivity tests..."
	@echo ""
	@echo "1. Testing web server..."
	@curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:8080 || echo "Web server not accessible"
	@echo ""
	@echo "2. Testing database connection..."
	@docker-compose exec -T db mysql -u property_user -pproperty_password -e "SELECT 1;" property_research > /dev/null 2>&1 && echo "Database: OK" || echo "Database: FAILED"
	@echo ""
	@echo "3. Testing API endpoint..."
	@curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:8080/api/property.php?id=1 || echo "API not accessible"

## lint-php: Check PHP syntax
lint-php:
	@echo "Checking PHP syntax..."
	@find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; | grep -v "No syntax errors"

## permissions: Fix file permissions
permissions:
	docker-compose exec web chown -R www-data:www-data /var/www/html
	docker-compose exec web find /var/www/html -type d -exec chmod 755 {} \;
	docker-compose exec web find /var/www/html -type f -exec chmod 644 {} \;
	@echo "Permissions fixed!"

## env-setup: Create .env file from .env.example
env-setup:
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ".env file created. Please update with your settings."; \
	else \
		echo ".env file already exists."; \
	fi

## update: Pull latest changes and restart
update:
	@echo "Pulling latest changes..."
	git pull
	@echo "Rebuilding containers..."
	docker-compose up -d --build
	@echo "Update complete!"

## monitor: Monitor container resource usage
monitor:
	docker stats property_research_web property_research_db

## apache-reload: Reload Apache configuration
apache-reload:
	docker-compose exec web apache2ctl graceful
	@echo "Apache reloaded!"

## apache-test: Test Apache configuration
apache-test:
	docker-compose exec web apache2ctl configtest

## composer-install: Install PHP dependencies (if using Composer)
composer-install:
	docker-compose exec web composer install

## npm-install: Install Node.js dependencies (if using npm)
npm-install:
	docker-compose exec web npm install

## production-check: Run production readiness checks
production-check:
	@echo "=== Production Readiness Check ==="
	@echo ""
	@echo "1. Checking for .env file..."
	@[ -f .env ] && echo "✓ .env exists" || echo "✗ .env missing"
	@echo ""
	@echo "2. Checking database connection..."
	@docker-compose exec -T db mysql -u property_user -pproperty_password -e "SELECT 1;" property_research > /dev/null 2>&1 && echo "✓ Database connected" || echo "✗ Database connection failed"
	@echo ""
	@echo "3. Checking required directories..."
	@[ -d sql ] && echo "✓ sql/ directory exists" || echo "✗ sql/ directory missing"
	@[ -d api ] && echo "✓ api/ directory exists" || echo "✗ api/ directory missing"
	@[ -d public ] && echo "✓ public/ directory exists" || echo "✗ public/ directory missing"
	@echo ""
	@echo "4. Checking SQL schema..."
	@[ -f sql/schema.sql ] && echo "✓ schema.sql exists" || echo "✗ schema.sql missing"

## info: Display project information
info:
	@echo "=== Property Research System ==="
	@echo "Project: Full-Stack PHP LAMP Application"
	@echo "Tech Stack: PHP 8.0, Apache, MySQL 8.0, JavaScript (Leaflet)"
	@echo ""
	@echo "URLs:"
	@echo "  - Application: http://localhost:8080"
	@echo "  - Map View: http://localhost:8080/public/map.html?id={id}"
	@echo ""
	@echo "Database:"
	@echo "  - Host: localhost:3306 (or 'db' from container)"
	@echo "  - Name: property_research"
	@echo "  - User: property_user"
	@echo ""
	@echo "Docker Containers:"
	@echo "  - Web: property_research_web"
	@echo "  - DB: property_research_db"
