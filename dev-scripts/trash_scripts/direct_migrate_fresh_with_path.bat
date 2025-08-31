@echo off
setlocal enabledelayedexpansion
echo Exécution directe de migrate:fresh avec seed (chemin complet vers PHP)...

REM Définir le chemin vers PHP
SET PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe

REM Vérifier si PHP existe
IF NOT EXIST "%PHP_PATH%" (
    echo Le chemin vers PHP n'existe pas: %PHP_PATH%
    
    REM Essayer de trouver PHP dans d'autres répertoires
    FOR /D %%G IN (C:\wamp64\bin\php\*) DO (
        IF EXIST "%%G\php.exe" (
            SET PHP_PATH=%%G\php.exe
            echo PHP trouvé à: !PHP_PATH!
            goto php_found
        )
    )
    
    echo Impossible de trouver PHP. Veuillez modifier le chemin dans le script.
    goto :end
)

:php_found
REM Aller au répertoire du projet Laravel
cd /d C:\wamp64\www\smart_school_new

REM Vérifier si nous sommes dans le bon répertoire
IF NOT EXIST "artisan" (
    echo Le fichier artisan n'a pas été trouvé dans: %CD%
    echo Veuillez vérifier le chemin du projet.
    goto :end
)

REM Exécuter la commande artisan avec le chemin complet vers PHP
echo Exécution de: "!PHP_PATH!" artisan migrate:fresh --seed
"!PHP_PATH!" artisan migrate:fresh --seed

:end
echo.
echo Appuyez sur une touche pour quitter...
pause > nul
endlocal 