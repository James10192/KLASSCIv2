@echo off
echo Recherche du chemin de PHP sur votre système...
echo.

REM Vérifier dans les emplacements courants de WAMP
echo Vérification des emplacements WAMP...

REM Vérifier si le répertoire WAMP existe
IF NOT EXIST "C:\wamp64\bin\php" (
    echo Le répertoire C:\wamp64\bin\php n'existe pas.
    echo Veuillez vérifier votre installation WAMP.
    goto check_path
)

echo Répertoires PHP trouvés dans WAMP:
echo.

REM Lister tous les répertoires PHP dans WAMP
FOR /D %%G IN (C:\wamp64\bin\php\*) DO (
    IF EXIST "%%G\php.exe" (
        echo PHP trouvé à: %%G\php.exe
    )
)

:check_path
echo.
echo Vérification de PHP dans le PATH système...

REM Vérifier si PHP est dans le PATH
where php 2>nul
IF %ERRORLEVEL% EQU 0 (
    echo PHP est disponible dans le PATH système.
    echo Chemin complet:
    where php
) ELSE (
    echo PHP n'est pas disponible dans le PATH système.
)

echo.
echo Vérification terminée. Utilisez un des chemins ci-dessus dans vos scripts.
echo.
echo Appuyez sur une touche pour quitter...
pause > nul 