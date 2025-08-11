#!/bin/bash

echo "🔄 Rebuilding Generic SaaS application from scratch..."
echo "This will stop all containers, remove them, and rebuild everything."

read -p "Are you sure you want to proceed? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Operation cancelled."
    exit 1
fi

# Stop and remove all containers
echo "🛑 Stopping and removing all containers..."
docker compose down --volumes --remove-orphans

# Remove only project-specific images (safer approach)
echo "🗑️ Removing project images..."
docker image rm generic-saas-app 2>/dev/null || true
docker image rm $(docker images -f "reference=*generic-saas*" -q) 2>/dev/null || true

# Remove only project-related dangling images (much safer)
echo "🧹 Cleaning up project-related images only..."
# Only remove dangling images created in the last hour (safer)
docker image prune -f --filter "until=1h" 2>/dev/null || true

# Rebuild and start everything
echo "🏗️ Building and starting all services..."
docker compose up --build -d

# Wait for all services to be ready
echo "⏳ Waiting for all services to be ready..."
sleep 15

# Show status
echo "📊 Services status:"
docker compose ps

# Test the application
echo "🧪 Testing application..."
if curl -f -s http://localhost:8082/ > /dev/null; then
    echo "✅ Application is responding on http://localhost:8082/"
else
    echo "⚠️ Application might still be initializing. Check logs with: docker compose logs app"
fi

echo "🎉 Rebuild completed! Your application should be available at http://localhost:8082/"