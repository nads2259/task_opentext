#!/bin/bash
set -e

cd /var/www/html

echo "🧹 Removing old cache and log files..."
rm -rf var/cache/dev var/cache/prod var/cache/test
rm -rf var/log/*

echo "📂 Recreating cache and log directories..."
mkdir -p var/cache/dev var/cache/prod var/cache/test var/log

echo "🔑 Setting permissions for www-data..."
chown -R www-data:www-data var/cache var/log
chmod -R 775 var/cache var/log

echo "✅ Cache and log directories reset successfully."
