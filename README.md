# Laravel Docker Setup

## Prerequisites
- [Docker](https://www.docker.com/get-started) and [Docker Compose](https://docs.docker.com/compose/install/) installed

## Quick Start

1. **Clone the repository:**
   ```sh
   git clone <your-repo-url>
   cd <your-project-directory>
   ```

2. **Copy the Docker environment file:**
   ```sh
   cp .env.docker.example .env.docker
   ```

3. **Build and start the containers:**
   ```sh
   docker-compose up --build
   ```

4. **Access the application:**
    - Open [http://localhost:8000](http://localhost:8000) in your browser.

## Useful Commands
- **log containers:**
  ```sh
  docker-compose logs -f app
  
- **Stop containers:**
  ```sh
  docker-compose down
  ```

- **Run artisan commands:**
  ```sh
  docker-compose exec app php artisan <command>
  ```
## No Docker Setup

1. **Clone the repository:**
   ```sh
   git clone <your-repo-url>
   cd <your-project-directory>

2. **Install dependencies:**
   ```sh
   composer install
   php artisan key:generate
   php artisan migrate
   ```
   
3. **Run project:**
   ```sh
   php artisan serve
   ```

4. **Code linting:**
   ```sh
   composer lint       # Check code style
   composer format     # Fix code style
   ```

## Code Linting

This project uses **Laravel Pint** for code style enforcement with automatic pre-commit hooks.

**Commands:**
```sh
composer lint       # Check code style
composer format     # Auto-fix code style
```

**Pre-commit Hook:**  
Code style is automatically checked before each commit.  If linting fails, fix with `composer format` and commit again.

## Notes

- The database service uses PostgreSQL and persists data in a Docker volume.
- The `.env.docker` file is used for environment configuration inside containers.
- All code must pass linting checks before commit.
```
Replace `<your-repo-url>` and `<your-project-directory>` as needed.
