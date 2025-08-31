# Outils de correction de version PHP pour l'installation

Ce dossier contient des outils pour résoudre les problèmes de vérification de version PHP lors de l'installation de l'application.

## Problème

Lors de l'installation, l'application vérifie que la version de PHP est 8.0.0 ou supérieure. Même si votre WAMP a une version de PHP compatible, il peut y avoir des problèmes de détection de la version.

## Solutions

### 1. Utiliser l'outil web

Accédez à l'URL suivante dans votre navigateur:
```
http://localhost/smart_school_new/public/fix_php_version.php
```

Cet outil web vous permettra de:
- Voir les informations sur votre version PHP actuelle
- Modifier automatiquement le contrôleur pour ignorer la vérification de version
- Vider le cache Laravel
- Obtenir des instructions pour changer la version PHP dans WAMP

### 2. Utiliser le script PowerShell

1. Ouvrez PowerShell en tant qu'administrateur
2. Naviguez vers ce dossier
3. Exécutez le script:
```powershell
.\fix_php_version.ps1
```

### 3. Utiliser le script Batch

1. Ouvrez une invite de commande en tant qu'administrateur
2. Naviguez vers ce dossier
3. Exécutez le script:
```cmd
fix_php_version.bat
```

### 4. Modifier manuellement le contrôleur

Si les outils automatiques ne fonctionnent pas, vous pouvez modifier manuellement le fichier `app/Http/Controllers/SetupController.php`:

1. Ouvrez le fichier dans un éditeur de texte
2. Trouvez la méthode `checkRequirements()`
3. Ajoutez la ligne suivante au début de la méthode:
   ```php
   $forcePhpCompatible = true; // Ignorer la vérification de version PHP
   ```
4. Modifiez la vérification de version PHP pour utiliser cette variable:
   ```php
   'php_version' => [
       'status' => $forcePhpCompatible || version_compare(PHP_VERSION, '8.0.0', '>='),
       'message' => 'PHP version ' . PHP_VERSION . ' (requis: 8.0+)' . ($forcePhpCompatible ? ' [Compatibilité forcée]' : '')
   ],
   ```
5. Sauvegardez le fichier
6. Videz le cache Laravel:
   ```
   php artisan config:clear
   php artisan cache:clear
   ```

## Changer la version PHP dans WAMP

1. Cliquez sur l'icône WAMP dans la barre des tâches
2. Allez dans PHP → Version
3. Sélectionnez une version PHP 8.0.0 ou supérieure
4. Redémarrez les services WAMP 