#!/usr/bin/env bash
set -e

echo "🚀 Setting up TokoKu..."

# 1. Env file
if [ ! -f .env ]; then
  cp .env.example .env
  echo "✅ .env created"
else
  echo "ℹ️ .env already exists"
fi

# 2. Build & run containers
docker compose --env-file .env.docker.dev -f docker-compose.dev.yml up --build -d

# 3. Generate key
docker exec tokoku_app php artisan key:generate --force

# 4. Migrate & seed
docker exec tokoku_app php artisan migrate --seed --force

echo ""
echo "🎉 Tokoku is ready!"
echo "🌐 Web App: http://localhost:7890"
echo "🛢️ PhpMyAdmin: http://localhost:8081"
echo "👤 First user = Admin"
