# Property Research System - Docker Environment

## Quick Start with Docker

Build and start the containers:

```bash
docker-compose up -d --build
```

Access the application at: http://localhost:8080

## What's Included

The Docker environment includes:

- **Web Server**: PHP 8.0 with Apache
- **Database**: MySQL 8.0
- **Auto-initialization**: Database schema is automatically created from `/sql/schema.sql`

## Docker Commands

### Start the application
```bash
docker-compose up -d
```

### Stop the application
```bash
docker-compose down
```

### Stop and remove all data (including database)
```bash
docker-compose down -v
```

### View logs
```bash
# All services
docker-compose logs -f

# Web server only
docker-compose logs -f web

# Database only
docker-compose logs -f db
```

### Check container status
```bash
docker-compose ps
```

### Restart containers
```bash
docker-compose restart
```

### Rebuild containers (after code changes)
```bash
docker-compose up -d --build
```

## Access Points

- **Application**: http://localhost:8080
- **Map View**: http://localhost:8080/public/map.html?id={property_id}
- **MySQL**: localhost:3306

## Database Connection

The Docker containers use the following default credentials:

- **Host**: db (or localhost:3306 from your machine)
- **Database**: property_research
- **User**: property_user
- **Password**: property_password
- **Root Password**: root_password

## Troubleshooting

### Port already in use

If port 8080 or 3306 is already in use, edit `docker-compose.yml`:

```yaml
services:
  web:
    ports:
      - "9080:80"  # Change 8080 to 9080
  db:
    ports:
      - "3307:3306"  # Change 3306 to 3307
```

### Database not initialized

If the database tables aren't created automatically:

```bash
# Enter the database container
docker-compose exec db mysql -u property_user -pproperty_password property_research

# Or manually import the schema
docker-compose exec db mysql -u property_user -pproperty_password property_research < sql/schema.sql
```

### Permission errors

```bash
docker-compose exec web chown -R www-data:www-data /var/www/html
```

### View web server errors

```bash
docker-compose exec web tail -f /var/log/apache2/error.log
```

## Development Workflow

1. Make code changes on your local machine
2. Changes are automatically reflected in the container (via volume mount)
3. Refresh your browser to see the changes
4. Check logs if something isn't working: `docker-compose logs -f web`

## Production Considerations

This Docker setup is for development/testing. For production:

1. Use environment variables for sensitive data (create a `.env` file)
2. Don't expose database port publicly
3. Use strong passwords
4. Enable HTTPS
5. Configure proper volume backups for the database
6. Use docker secrets for credentials
7. Implement health checks
8. Set resource limits

## Clean Up

To completely remove all containers, volumes, and images:

```bash
docker-compose down -v
docker rmi property_research_web
```
