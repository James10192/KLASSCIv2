@echo off
echo Exécution directe de migrate:fresh avec seed...

REM Aller au répertoire du projet Laravel
cd /d C:\wamp64\www\smart_school_new

REM Vérifier si nous sommes dans le bon répertoire
IF NOT EXIST "artisan" (
    echo Le fichier artisan n'a pas été trouvé dans: %CD%
    echo Veuillez vérifier le chemin du projet.
    goto :end
)

REM Exécuter la commande artisan directement
echo Exécution de: php artisan migrate:fresh --seed
php artisan migrate:fresh --seed

:end
echo.
echo Appuyez sur une touche pour quitter...
pause > nul 