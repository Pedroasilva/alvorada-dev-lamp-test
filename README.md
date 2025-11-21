# Full-Stack PHP Engineer Test

This is a standalone, end-to-end mini-project designed to evaluate real-world full-stack skills using the LAMP stack (Linux, Apache, MySQL, PHP) plus JavaScript for frontend mapping. The project simulates a lightweight property research workflow.

> **Note:** This README provides complete setup and run instructions for implementing the property research application. The project structure and files referenced in this document should be created as part of the implementation process.

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- **Linux** (Ubuntu 20.04+ or similar distribution recommended)
- **Apache** 2.4+
- **MySQL** 5.7+ or MariaDB 10.3+
- **PHP** 7.4+ or 8.0+
  - Required PHP extensions:
    - `php-mysql` (or `php-mysqli`)
    - `php-json`
    - `php-mbstring`
    - `php-xml`
- **Git** (for cloning the repository)

### Alternative: Using Docker (Optional)

If you prefer using Docker, ensure you have:
- Docker 20.10+
- Docker Compose 1.29+

## Setup Instructions

### Option 1: Manual Setup (LAMP Stack)

#### 1. Clone the Repository

```bash
git clone https://github.com/Pedroasilva/alvorada-dev-lamp-test.git
cd alvorada-dev-lamp-test
```

#### 2. Install Apache and PHP

On Ubuntu/Debian:

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql php-json php-mbstring php-xml
```

On CentOS/RHEL:

```bash
sudo yum install httpd php php-mysqlnd php-json php-mbstring php-xml
```

#### 3. Install MySQL/MariaDB

On Ubuntu/Debian:

```bash
sudo apt install mysql-server
sudo mysql_secure_installation
```

On CentOS/RHEL:

```bash
sudo yum install mariadb-server
sudo systemctl start mariadb
sudo mysql_secure_installation
```

> **Note:** On CentOS/RHEL 8+, MySQL is not available in default repositories. Use MariaDB (compatible with MySQL) or enable the MySQL repository from the official MySQL website.

#### 4. Configure Apache

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/alvorada-test.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/alvorada-test

    <Directory /var/www/alvorada-test>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/alvorada-test-error.log
    CustomLog ${APACHE_LOG_DIR}/alvorada-test-access.log combined
</VirtualHost>
```

Enable the site and required modules:

```bash
sudo a2ensite alvorada-test.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 5. Copy Project Files

Copy the project files to the Apache document root:

```bash
sudo mkdir -p /var/www/alvorada-test
sudo cp -r * /var/www/alvorada-test/
sudo chown -R www-data:www-data /var/www/alvorada-test
sudo find /var/www/alvorada-test -type d -exec chmod 755 {} \;
sudo find /var/www/alvorada-test -type f -exec chmod 644 {} \;
```

#### 6. Database Setup

Login to MySQL:

```bash
sudo mysql -u root -p
```

Create the database and user:

```sql
CREATE DATABASE alvorada_properties CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'alvorada_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON alvorada_properties.* TO 'alvorada_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> **Security Warning:** Replace `your_secure_password` with a strong, unique password. Use a combination of uppercase, lowercase, numbers, and special characters (minimum 12 characters recommended).

Import the database schema (if `database/schema.sql` exists):

```bash
mysql -u alvorada_user -p alvorada_properties < database/schema.sql
```

> **Note:** The `database/schema.sql` file should be created as part of the project implementation with the necessary table definitions.

#### 7. Configure Database Connection

Create or edit the configuration file `config/database.php`:

```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'alvorada_properties');
define('DB_USER', 'alvorada_user');
define('DB_PASS', 'your_secure_password');
define('DB_CHARSET', 'utf8mb4');
?>
```

**Important:** Never commit `config/database.php` with real credentials. Use `config/database.example.php` as a template.

### Option 2: Docker Setup

If Docker and Docker Compose are installed:

> **Note:** You'll need to create a `docker-compose.yml` file first. See the example configuration below.

#### 1. Clone the Repository

```bash
git clone https://github.com/Pedroasilva/alvorada-dev-lamp-test.git
cd alvorada-dev-lamp-test
```

#### 2. Start Docker Containers

First, create a `docker-compose.yml` file in the project root with the following content:

```yaml
version: '3.8'

services:
  web:
    image: php:8.0-apache
    container_name: alvorada_web
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_NAME=alvorada_properties
      - DB_USER=alvorada_user
      - DB_PASS=${DB_PASSWORD:?Please set DB_PASSWORD in .env file}

  db:
    image: mysql:8.0
    container_name: alvorada_db
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:?Please set MYSQL_ROOT_PASSWORD in .env file}
      - MYSQL_DATABASE=alvorada_properties
      - MYSQL_USER=alvorada_user
      - MYSQL_PASSWORD=${DB_PASSWORD:?Please set DB_PASSWORD in .env file}
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

> **Security Warning:** This configuration requires a `.env` file with strong passwords. Create it with:
> ```
> DB_PASSWORD=your_strong_database_password
> MYSQL_ROOT_PASSWORD=your_strong_root_password
> ```
> **Important:** Add `.env` to your `.gitignore` to prevent committing credentials.

Then start the containers:

```bash
docker-compose up -d
```

This will start:
- Apache/PHP container on port 8080
- MySQL container on port 3306

#### 3. Access the Application

Open your browser and navigate to:
```
http://localhost:8080
```

## Run Instructions

### Starting the Application

#### Manual Setup (LAMP)

1. Ensure Apache and MySQL are running:

```bash
sudo systemctl status apache2
sudo systemctl status mysql
```

If not running, start them:

```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

2. Open your web browser and navigate to:
```
http://localhost
```
or if using a custom domain:
```
http://your-domain.local
```

#### Docker Setup

1. Start the containers (if not already running):

```bash
docker-compose up -d
```

2. Access the application at:
```
http://localhost:8080
```

### Stopping the Application

#### Manual Setup

To stop the services:

```bash
sudo systemctl stop apache2
sudo systemctl stop mysql
```

#### Docker Setup

To stop the containers:

```bash
docker-compose down
```

To stop and remove all data:

```bash
docker-compose down -v
```

## Project Structure

```
alvorada-dev-lamp-test/
├── config/              # Configuration files
│   ├── database.php     # Database connection settings
│   └── app.php          # Application settings
├── database/            # Database files
│   ├── schema.sql       # Database schema
│   └── seeds.sql        # Sample data
├── public/              # Public web root
│   ├── index.php        # Main entry point
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── assets/          # Images and other assets
├── src/                 # PHP source code
│   ├── controllers/     # Application controllers
│   ├── models/          # Data models
│   ├── views/           # View templates
│   └── utils/           # Utility functions
├── docker-compose.yml   # Docker configuration (if using Docker)
├── .htaccess            # Apache rewrite rules
└── README.md            # This file
```

## Features

The property research workflow includes:

- **Property Listing**: Browse and search properties
- **Property Details**: View detailed information about properties
- **Interactive Map**: JavaScript-based map integration for property locations
- **Filter & Search**: Advanced filtering and search capabilities
- **Responsive Design**: Mobile-friendly interface

## Development

### PHP Code Standards

This project uses pure PHP without frameworks. Follow these guidelines:

- Use PHP 7.4+ features
- Follow PSR-12 coding standards
- Sanitize all user inputs
- Use prepared statements for database queries
- Implement proper error handling

### Database Schema

The main tables include:

- `properties`: Stores property information
- `locations`: Geographic location data
- `images`: Property images
- `categories`: Property types/categories

### JavaScript

JavaScript is used for:
- Interactive mapping (using Leaflet.js or similar)
- AJAX requests for dynamic content
- Form validation
- User interface interactions

## Troubleshooting

### Apache Issues

**Problem:** Cannot access the application

**Solution:**
- Check if Apache is running: `sudo systemctl status apache2`
- Verify virtual host configuration
- Check Apache error logs: `sudo tail -f /var/log/apache2/error.log`

### Database Connection Issues

**Problem:** Cannot connect to database

**Solution:**
- Verify MySQL is running: `sudo systemctl status mysql`
- Check database credentials in `config/database.php`
- Ensure the database and user exist
- Check MySQL error logs: `sudo tail -f /var/log/mysql/error.log`

### Permission Issues

**Problem:** 403 Forbidden or file permission errors

**Solution:**
```bash
sudo chown -R www-data:www-data /var/www/alvorada-test
sudo find /var/www/alvorada-test -type d -exec chmod 755 {} \;
sudo find /var/www/alvorada-test -type f -exec chmod 644 {} \;
```

### PHP Extensions Missing

**Problem:** PHP extension errors

**Solution:**
```bash
sudo apt install php-mysql php-json php-mbstring php-xml
sudo systemctl restart apache2
```

## Testing

To verify the setup is working correctly:

1. Navigate to the homepage
2. Check that the property listing loads
3. Test the search functionality
4. Verify the map displays correctly
5. Test property detail pages

## Security Considerations

- Always use prepared statements for database queries
- Sanitize and validate all user inputs
- Keep PHP and MySQL updated
- Use HTTPS in production
- Implement proper session management
- Set appropriate file permissions
- Never commit sensitive credentials to version control

## License

This is a test project for evaluation purposes.

## Support

For issues or questions, please open an issue in the repository.
