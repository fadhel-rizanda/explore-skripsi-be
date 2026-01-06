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
    - Open logs in another terminal:
      ```sh
      docker logs -f laravel_frankenphp
      docker logs -f laravel_scheduler
      docker logs -f laravel_queue
      docker logs -f laravel_reverb
      ```
      
5. **Restart the application:**
      ```sh
      docker compose down -v
      docker rmi explore-skripsi-app explore-skripsi-queue explore-skripsi-reverb explore-skripsi-scheduler
      del bootstrap\cache\*.php
      docker compose build --no-cache
      docker compose up
      ```

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

4. **Run Scheduler:**
   ```sh
   php artisan schedule:work
   ```

5. **Run Reverb WebSocket server:**
   ```sh
   php artisan reverb:start --debug
   php artisan queue:work --verbose 
   ```
   
6. **Test Reverb WebSocket server (public):**
   ```sh
   npm install -g wscat
   
   wscat -c "ws://localhost:8080/app/<REVERB_APP_KEY>?protocol=7&client=js&version=6.0.0"
    # first response will be connection established
    > {"event":"pusher:connection_established","data":"{\"socket_id\":\"206735107.416029623\",\"activity_timeout\":30}"}
    # input this for public channel
    > {"event":"pusher:subscribe","data":{"channel":"<CHANNEL_NAME>"}}
    # input this for private channel
    > {"event":"pusher:subscribe","data":{"auth":"<TOKEN_BROADCASTING_AUTH>","channel":"<CHANNEL_NAME>"}} # TOKEN_BROADCASTING_AUTH from get from api/broadcasting/auth {socket_id, channel_name}
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
