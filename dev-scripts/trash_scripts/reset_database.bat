@echo off
echo Resetting database...

REM Vérifier si PHP existe dans le chemin spécifié
SET PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe
IF NOT EXIST "%PHP_PATH%" (
    echo Le chemin vers PHP n'existe pas: %PHP_PATH%
    echo Veuillez modifier le chemin dans le script.
    goto :end
)

REM Afficher le répertoire courant pour le débogage
echo Répertoire courant: %CD%

REM Vérifier si le script PHP existe
SET SCRIPT_PATH=%~dp0reset_database.php
IF NOT EXIST "%SCRIPT_PATH%" (
    echo Le script PHP n'a pas été trouvé: %SCRIPT_PATH%
    echo Veuillez vérifier le chemin du script.
    goto :end
)

REM Exécuter le script PHP
echo Exécution de: %PHP_PATH% "%SCRIPT_PATH%"
"%PHP_PATH%" "%SCRIPT_PATH%"

:end
echo.
echo Press any key to exit...
pause > nul 