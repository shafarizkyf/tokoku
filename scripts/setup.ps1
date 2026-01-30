$ErrorActionPreference = "Stop"

Write-Host "🚀 Setting up TokoKu..." -ForegroundColor Cyan

# 1. Env file
if (-Not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "✅ .env created"
} else {
    Write-Host "ℹ️ .env already exists"
}

# 2. Docker check
if (-Not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Error "❌ Docker not found. Install Docker Desktop first."
    exit 1
}

# 3. Build & run containers
Write-Host "🐳 Building & starting containers..."
docker compose --env-file .env.docker.dev -f docker-compose.prod.yml up --build -d

# 4. Generate APP_KEY
Write-Host "🔐 Generating APP_KEY..."
docker exec tokoku_app php artisan key:generate --force

# 5. Migrate & seed DB
Write-Host "🗄️ Migrating & seeding database..."
docker exec tokoku_app php artisan migrate --seed --force

Write-Host ""
Write-Host "🎉 TokoKu is ready!" -ForegroundColor Green
Write-Host "🌐 Web App     → http://localhost:7890"
Write-Host "🛢️ PhpMyAdmin → http://localhost:8081"
Write-Host "👤 First user = Admin"
