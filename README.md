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

## Notes

- The database service uses PostgreSQL and persists data in a Docker volume.
- The `.env.docker` file is used for environment configuration inside containers.
```
Replace `<your-repo-url>` and `<your-project-directory>` as needed.
