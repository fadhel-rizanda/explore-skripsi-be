# Pawsitive Hub - Backend (Laravel)

This repository contains the backend API for **Pawsitive Hub**, built with [Laravel](https://laravel.com/). It provides RESTful APIs and WebSocket (Reverb) functionalities for the platform, including modules for Pets, Adoptions, Communities, Posts, Chats, and Notifications.

## Project Structure

```bash
.
├── app/                # Application logic (Controllers, Models, Services)
├── bootstrap/          # Framework bootstrap files
├── config/             # Configuration files
├── database/           # Migrations and seeders
├── public/             # Publicly accessible files
├── resources/          # Views and raw assets
├── routes/             # API and Web routes
├── storage/            # Compiled templates, file uploads, logs
├── tests/              # Automated tests
└── docker-compose.yaml # Docker orchestration
```

## Dependencies and Libraries

**Core Requirements**
- [PHP 8.2+](https://www.php.net/downloads.php)
- [Composer](https://getcomposer.org/)
- [PostgreSQL](https://www.postgresql.org/)
- [Redis](https://redis.io/) (for caching and queues)
- [Docker](https://www.docker.com/get-started) & [Docker Compose](https://docs.docker.com/compose/install/)
- [Node.js & npm](https://nodejs.org/) (for WebSockets testing)

**Main Libraries**
- **Tymon/jwt-auth:** JWT (JSON Web Token) based authentication management.
- **Laravel Reverb:** Real-time event broadcasting feature in Laravel.
- **Spatie/laravel-permission:** Role and permission management for user authorization.
- **League/flysystem-aws-s3-v3:** Document storage integration with AWS S3 services.

## Architecture & Deployment

This project utilizes a highly scalable and observable containerized architecture via Docker Compose:

- **FrankenPHP**: High-performance application server built on Caddy, replacing the traditional Nginx/PHP-FPM setup.
- **PostgreSQL**: Primary relational database.
- **Redis**: In-memory data store for caching and queuing background jobs.
- **Laravel Reverb**: Standalone WebSocket server for handling real-time features.
- **Queue Workers & Scheduler**: Dedicated containers for processing background jobs and executing cron tasks.
- **Observability Stack**: Fully integrated monitoring with **Prometheus**, **Grafana**, **Node Exporter**, and **Postgres Exporter** to track application health, database metrics, and server load.

## Quick Start

### Docker Setup
1. **Clone the repository:**
   ```sh
   git clone <your-repo-url>
   cd skripsi-be
   ```
2. **Copy the Docker environment file:**
   ```sh
   cp .env.docker.example .env.docker
   ```
3. **Build and start the containers:**
   ```sh
   docker-compose up -d --build
   ```
4. **Access the application:**
   - App is running on: [http://localhost:8000](http://localhost:8000)

### Manual Setup (Without Docker)
1. **Setup environment & dependencies:**
   ```sh
   cp .env.example .env
   composer install
   php artisan key:generate
   ```
2. **Configure Database:**
   Update your `.env` file with your PostgreSQL database credentials, then run migrations:
   ```sh
   php artisan migrate
   ```
3. **Run the services (Multiple terminals needed):**
   ```sh
   php artisan serve                # API Server
   php artisan schedule:work        # Task Scheduler
   php artisan reverb:start --debug # WebSocket Server
   php artisan queue:work --verbose # Queue Worker
   ```


