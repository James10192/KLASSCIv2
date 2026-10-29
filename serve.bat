@echo off
echo Démarrage du serveur Laravel pour ESBTP...
cd %~dp0
php artisan serve --host=127.0.0.1 --port=8000 