@echo off
echo Exécution simple de migrate:fresh avec seed...

REM Aller au répertoire du projet Laravel
cd /d C:\wamp64\www\smart_school_new

REM Vérifier si nous sommes dans le bon répertoire
IF NOT EXIST "artisan" (
    echo Le fichier artisan n'a pas été trouvé dans: %CD%
    echo Veuillez vérifier le chemin du projet.
    goto :end
)

REM Essayer d'exécuter avec php dans le PATH
echo Tentative d'exécution avec php dans le PATH...
php artisan migrate:fresh --seed

IF %ERRORLEVEL% NEQ 0 (
    echo Échec de l'exécution avec php dans le PATH.
    echo Tentative avec le chemin complet vers PHP...
    
    REM Essayer avec différentes versions de PHP
    FOR /D %%G IN (C:\wamp64\bin\php\*) DO (
        IF EXIST "%%G\php.exe" (
            echo Tentative avec: %%G\php.exe
            "%%G\php.exe" artisan migrate:fresh --seed
            IF %ERRORLEVEL% EQU 0 (
                echo Succès avec: %%G\php.exe
                goto :success
            )
        )
    )
    
    echo Toutes les tentatives ont échoué.
    goto :end
)

:success
echo Les migrations ont été exécutées avec succès!

:end
echo.
echo Appuyez sur une touche pour quitter...
pause > nul 