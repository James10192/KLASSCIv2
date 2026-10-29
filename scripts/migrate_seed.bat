@echo off
cd %~dp0\..
php artisan migrate:fresh --seed
echo Database migrations and seeding completed!
pause 