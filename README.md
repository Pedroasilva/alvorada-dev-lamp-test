# Full-Stack PHP Engineer - Property Research System

This is a standalone, end-to-end mini-project designed to evaluate real-world full-stack skills using the LAMP stack (Linux, Apache, MySQL, PHP) plus JavaScript for frontend mapping. The project simulates a lightweight property research workflow.

## Project Goal

Build a small working system that allows a user to:

- Enter a property address
- Automatically enrich the address using a public geolocation API
- Store the property in MySQL
- View the details via an API
- Display the property on an interactive map
- Add notes to the property
- Provide a short written proposal describing how AI/LLM automation could enhance the workflow

Everything must exist as one coherent, standalone project.

## Deliverables

The project folder contains:

- `index.php` – form to add properties
- `/api` – PHP endpoints
- `/public` – static assets and map.html
- `/sql/schema.sql` – database schema
- `AI_PROPOSAL.md` – short AI/LLM proposal
- `README.md` – setup + run instructions

**Note:** No PHP frameworks allowed (vanilla PHP only). Minimal JS mapping libraries (ArcGIS or Leaflet) are allowed.

## Project Requirements

### 1. Property Intake + Enrichment (PHP + MySQL)

Simple form at `index.php` with fields:
- Property name
- Address

When submitted:
- Calls a public API:
  - OpenStreetMap Nominatim
- Extracts:
  - Latitude
  - Longitude
  - One additional data point (e.g., confidence score, zoning type, census block)
- Inserts a record into MySQL

**Database Schema:** See `/sql/schema.sql` for the `properties` table structure.

Shows a confirmation page with:
- The saved data
- A link to "View on Map"

Uses prepared statements for database interactions.

### 2. Property Details API (PHP)

Endpoints:

- **GET** `/api/property.php?id={id}`
  - Returns JSON with property details and all associated notes

- **POST** `/api/add_note.php`
  - Adds a new note to a property

**Database Schema:** See `/sql/schema.sql` for the `notes` table structure.

### 3. Interactive Map (HTML + JS)

Page: `/public/map.html?id={PROPERTY_ID}`

Features:
- Fetches property data from `/api/property.php`
- Loads a map using ArcGIS JS API or Leaflet
- Centers the map on the property location
- Creates a marker
- Shows a popup containing:
  - Property name
  - Address
  - Notes

### 4. AI/LLM Proposal (Written Only)

File: `AI_PROPOSAL.md`

Contains (up to one page):
- A workflow in this system to enhance with AI
- Which model/tool to use (e.g., OpenAI API, LangChain)
- High-level architecture
- Risks and mitigation strategies

**Examples:**
- Auto-summarizing properties
- Extracting zoning info from text
- Address normalization
- Lead scoring/prioritization

## Project Structure

```
project-root/
  index.php
  README.md
  AI_PROPOSAL.md
  api/
    db.php
    property.php
    add_note.php
  public/
    map.html
  sql/
    schema.sql
```

## Setup Instructions

You can set up this project using either a traditional LAMP stack installation or Docker. Choose the method that best suits your environment.

### Option 1: Traditional LAMP Stack Setup

#### Prerequisites

- **Linux**: Ubuntu 20.04+ or Debian-based distribution
- **Apache**: 2.4+
- **MySQL**: 5.7+ or MariaDB 10.3+
- **PHP**: 7.4+ or 8.0+
  - Required PHP extensions:
    - `php-mysql` or `php-mysqli`
    - `php-json`
    - `php-mbstring`
    - `php-xml`
- **Git**: For cloning the repository

#### Installation Steps

#### 1. Clone the Repository

```bash
git clone https://github.com/Pedroasilva/alvorada-dev-lamp-test.git
cd alvorada-dev-lamp-test
```

#### 2. Install LAMP Stack

On Ubuntu/Debian:

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql php-json php-mbstring php-xml mysql-server
```

#### 3. Configure Apache

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/property-research.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/property-research

    <Directory /var/www/property-research>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/property-research-error.log
    CustomLog ${APACHE_LOG_DIR}/property-research-access.log combined
</VirtualHost>
```

Enable the site and required modules:

```bash
sudo a2ensite property-research.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 4. Copy Project Files

```bash
sudo mkdir -p /var/www/property-research
sudo cp -r * /var/www/property-research/
sudo chown -R www-data:www-data /var/www/property-research
sudo find /var/www/property-research -type d -exec chmod 755 {} \;
sudo find /var/www/property-research -type f -exec chmod 644 {} \;
```

#### 5. Database Setup

**Step 1:** Secure MySQL installation (if not done yet)

```bash
sudo mysql_secure_installation
```

**Step 2:** Create database and user

Login to MySQL:

```bash
sudo mysql -u root -p
```

Create the database and user (run inside MySQL console):

```sql
CREATE DATABASE property_research CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'property_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON property_research.* TO 'property_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Step 3:** Run the database scripts

Execute all SQL scripts located in the `/sql` folder:

```bash
mysql -u property_user -p property_research < /var/www/property-research/sql/schema.sql
```

#### 6. Configure Database Connection

Edit `api/db.php` with your database credentials:

```php
<?php
$host = 'localhost';
$dbname = 'property_research';
$username = 'property_user';
$password = 'your_secure_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

### Option 2: Docker Setup

#### Prerequisites

- **Docker**: 20.10+
- **Docker Compose**: 1.29+
- **Git**: For cloning the repository

#### Installation Steps

##### 1. Clone the Repository

```bash
git clone https://github.com/Pedroasilva/alvorada-dev-lamp-test.git
cd alvorada-dev-lamp-test
```

##### 2. Create Docker Configuration Files

Create a `docker-compose.yml` file in the project root:

```yaml
version: '3.8'

services:
  web:
    image: php:8.0-apache
    container_name: property_research_web
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_NAME=property_research
      - DB_USER=property_user
      - DB_PASS=property_password
    networks:
      - property_network

  db:
    image: mysql:8.0
    container_name: property_research_db
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=property_research
      - MYSQL_USER=property_user
      - MYSQL_PASSWORD=property_password
    volumes:
      - db_data:/var/lib/mysql
      - ./sql:/docker-entrypoint-initdb.d
    networks:
      - property_network

volumes:
  db_data:

networks:
  property_network:
    driver: bridge
```

Create a `Dockerfile` in the project root to add PHP extensions:

```dockerfile
FROM php:8.0-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
```

Update `docker-compose.yml` to use the custom Dockerfile:

```yaml
version: '3.8'

services:
  web:
    build: .
    container_name: property_research_web
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_NAME=property_research
      - DB_USER=property_user
      - DB_PASS=property_password
    networks:
      - property_network

  db:
    image: mysql:8.0
    container_name: property_research_db
    ports:
      - "3306:3306"
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=property_research
      - MYSQL_USER=property_user
      - MYSQL_PASSWORD=property_password
    volumes:
      - db_data:/var/lib/mysql
      - ./sql:/docker-entrypoint-initdb.d
    networks:
      - property_network

volumes:
  db_data:

networks:
  property_network:
    driver: bridge
```

##### 3. Update Database Connection

Update `api/db.php` to use Docker environment variables:

```php
<?php
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'property_research';
$username = getenv('DB_USER') ?: 'property_user';
$password = getenv('DB_PASS') ?: 'property_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

##### 4. Start Docker Containers

Build and start the containers:

```bash
docker-compose up -d --build
```

Check container status:

```bash
docker-compose ps
```

View logs:

```bash
docker-compose logs -f
```

##### 5. Access the Application

Open your web browser and navigate to:

```text
http://localhost:8080
```

The database will be automatically initialized with the SQL scripts from the `/sql` folder.

##### 6. Stop Docker Containers

To stop the containers:

```bash
docker-compose down
```

To stop and remove all data (including database):

```bash
docker-compose down -v
```

## Running the Application

### Using Traditional LAMP Stack

#### 1. Start Services

Ensure Apache and MySQL are running:

```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

To enable services to start on boot:

```bash
sudo systemctl enable apache2
sudo systemctl enable mysql
```

Check service status:

```bash
sudo systemctl status apache2
sudo systemctl status mysql
```

#### 2. Access the Application

Open your web browser and navigate to:

```text
http://localhost
```

#### 3. Use the Application

- Enter a property name and address in the form
- Submit to save and enrich the property data
- Click "View on Map" to see the property location
- Add notes to properties via the API

#### 4. Stop Services (when needed)

```bash
sudo systemctl stop apache2
sudo systemctl stop mysql
```

### Using Docker

#### 1. Start Containers

```bash
docker-compose up -d
```

#### 2. Access the Application

Open your web browser and navigate to:

```text
http://localhost:8080
```

#### 3. Use the Application

- Enter a property name and address in the form
- Submit to save and enrich the property data
- Click "View on Map" to see the property location
- Add notes to properties via the API

#### 4. View Logs

```bash
docker-compose logs -f web
docker-compose logs -f db
```

#### 5. Stop Containers

```bash
docker-compose down
```

## API Documentation

### GET /api/property.php

**Parameters:**
- `id` (required) - Property ID

**Response:**
```json
{
  "property": {
    "id": 1,
    "name": "Example Property",
    "address": "123 Main St, City, State",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "extra_field": "residential",
    "created_at": "2025-11-21 10:00:00"
  },
  "notes": [
    {
      "id": 1,
      "note": "Great location",
      "created_at": "2025-11-21 10:30:00"
    }
  ]
}
```

### POST /api/add_note.php

**Parameters:**
- `property_id` (required) - Property ID
- `note` (required) - Note content

**Response:**
```json
{
  "success": true,
  "note_id": 1
}
```

## Technologies Used

- **Backend:** PHP (vanilla, no frameworks)
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Mapping:** ArcGIS JS API or Leaflet
- **Geolocation API:** OpenStreetMap Nominatim (or similar)

## Security Considerations

- All database queries use prepared statements to prevent SQL injection
- Input validation on all user-submitted data
- Proper error handling and logging
- Secure password storage
- Never commit sensitive credentials to version control

## Troubleshooting

### Cannot connect to database

Verify MySQL is running:

```bash
sudo systemctl status mysql
```

If not running, start it:

```bash
sudo systemctl start mysql
```

Check database credentials in `api/db.php` and ensure the database and user exist.

Test MySQL connection:

```bash
mysql -u property_user -p property_research
```

Check PHP MySQL extensions:

```bash
php -m | grep -i mysql
```

### 403 Forbidden or permission errors

Fix file permissions:

```bash
sudo chown -R www-data:www-data /var/www/property-research
sudo find /var/www/property-research -type d -exec chmod 755 {} \;
sudo find /var/www/property-research -type f -exec chmod 644 {} \;
```

### Apache not serving PHP files

Verify PHP module is installed:

```bash
sudo apt install libapache2-mod-php
```

Enable PHP module:

```bash
sudo a2enmod php7.4  # or php8.0, depending on your version
sudo systemctl restart apache2
```

Check Apache error logs:

```bash
sudo tail -f /var/log/apache2/error.log
```

### Port 80 already in use

Check what's using port 80:

```bash
sudo netstat -tulpn | grep :80
```

Or:

```bash
sudo lsof -i :80
```

## License

This project is for evaluation purposes only.
