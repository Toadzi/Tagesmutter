#!/bin/bash

# MiniStep-App Startskript
# ===========================

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
PORT=8080
HOST=127.0.0.1

echo ""
echo "  ╔══════════════════════════════════════╗"
echo "  ║     MiniStep-Verwaltung              ║"
echo "  ╚══════════════════════════════════════╝"
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo "  FEHLER: PHP ist nicht installiert."
    echo "  Bitte installiere PHP 8.2 oder neuer."
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "  PHP-Version: $PHP_VERSION"

# Check/install Composer dependencies
if [ ! -d "$APP_DIR/vendor" ]; then
    echo "  Installiere Abhängigkeiten..."
    if command -v composer &> /dev/null; then
        cd "$APP_DIR" && composer install --no-dev --quiet
    else
        echo "  Composer nicht gefunden. Lade Composer herunter..."
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php --quiet
        php -r "unlink('composer-setup.php');"
        php composer.phar install --no-dev --quiet
    fi
    echo "  Abhängigkeiten installiert."
fi

# Create data directories
mkdir -p "$APP_DIR/data/uploads/fotos"
mkdir -p "$APP_DIR/data/uploads/thumbs"
mkdir -p "$APP_DIR/data/backups"

# Run backup
php -r "
require '$APP_DIR/vendor/autoload.php';
(new App\Services\BackupService())->run();
echo '  Backup durchgeführt.\n';
"

echo ""
echo "  Server startet auf: http://$HOST:$PORT"
echo "  Zum Beenden: Ctrl+C"
echo ""

# Open browser (macOS)
if command -v open &> /dev/null; then
    (sleep 1 && open "http://$HOST:$PORT") &
fi

# Start PHP built-in server
php -S "$HOST:$PORT" -t "$APP_DIR/public"
