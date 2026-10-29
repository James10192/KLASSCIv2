@echo off
echo Running fresh migrations with seed...

REM Vérifier si PHP existe dans le chemin spécifié
SET PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe
IF NOT EXIST "%PHP_PATH%" (
    echo Le chemin vers PHP n'existe pas: %PHP_PATH%
    echo Veuillez modifier le chemin dans le script.
    goto :end
)

REM Afficher le répertoire courant pour le débogage
echo Répertoire courant: %CD%

REM Aller au répertoire du projet Laravel (racine du projet)
cd /d C:\wamp64\www\smart_school_new

REM Vérifier si nous sommes dans le bon répertoire
IF NOT EXIST "artisan" (
    echo Le fichier artisan n'a pas été trouvé dans: %CD%
    echo Veuillez vérifier le chemin du projet.
    goto :end
)

REM Exécuter les migrations avec l'option fresh et seed
echo Exécution de: %PHP_PATH% artisan migrate:fresh --seed
"%PHP_PATH%" artisan migrate:fresh --seed

:end
echo.
echo Press any key to exit...
pause > nul 