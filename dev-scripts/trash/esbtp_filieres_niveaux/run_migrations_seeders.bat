@echo off
echo ======================================================
echo  Installation des filieres et niveaux d'etudes ESBTP
echo ======================================================
echo.

REM Définir le chemin vers PHP
set PHP_PATH=C:\wamp64\bin\php\php8.1.31\php.exe

echo Execution des migrations...
%PHP_PATH% artisan migrate

echo.
echo Execution des seeders...
%PHP_PATH% artisan db:seed --class=ESBTPFiliereSeeder
%PHP_PATH% artisan db:seed --class=ESBTPNiveauEtudeSeeder
%PHP_PATH% artisan db:seed --class=ESBTPAnneeUniversitaireSeeder

echo.
echo Nettoyage du cache...
%PHP_PATH% artisan optimize:clear

echo.
echo Installation terminee avec succes!
echo.

pause 