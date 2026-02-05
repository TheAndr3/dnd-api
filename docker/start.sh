#!/bin/sh
set -e

echo "🚀 Starting D&D API..."

# Create log directory for supervisor
mkdir -p /var/log/supervisor

# Wait for database to be ready (if DATABASE_URL is set)
if [ -n "$DATABASE_URL" ]; then
    echo "⏳ Waiting for database connection..."
    
    # Extract host and port from DATABASE_URL
    DB_HOST=$(echo $DATABASE_URL | sed -n 's/.*@\([^:]*\):.*/\1/p')
    DB_PORT=$(echo $DATABASE_URL | sed -n 's/.*:\([0-9]*\)\/.*/\1/p')
    
    # Simple wait loop (max 30 seconds)
    RETRIES=30
    until nc -z -v -w5 $DB_HOST $DB_PORT 2>/dev/null || [ $RETRIES -eq 0 ]; do
        echo "   Waiting for $DB_HOST:$DB_PORT... ($RETRIES attempts left)"
        RETRIES=$((RETRIES-1))
        sleep 1
    done
    
    if [ $RETRIES -eq 0 ]; then
        echo "❌ Could not connect to database!"
        exit 1
    fi
    
    echo "✅ Database is ready!"
fi

# Run migrations
echo "📦 Running database migrations..."
php artisan migrate --force

# Optimize Laravel for production
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Application ready!"

# Start supervisor (manages php-fpm and nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
