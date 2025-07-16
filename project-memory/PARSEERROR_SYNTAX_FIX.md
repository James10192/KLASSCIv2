# Fix ParseError - Erreur de Syntaxe PHP

## Erreur
```
ParseError
syntax error, unexpected identifier "existe"
http://localhost:8000/esbtp/inscriptions/create
```

## Cause
L'apostrophe dans le message d'erreur français n'était pas échappée correctement dans le code PHP.

## Ligne problématique
```php
$messages["parents.$index.parent_id.exists"] = 'Le parent sélectionné n'existe pas';
```

## Solution
Échapper l'apostrophe avec un antislash :

```php
$messages["parents.$index.parent_id.exists"] = 'Le parent sélectionné n\'existe pas';
```

## Fichier corrigé
- `app/Http/Controllers/ESBTPInscriptionController.php` : ligne 238

## Leçon
- Toujours échapper les apostrophes dans les chaînes PHP avec des guillemets simples
- Alternative : utiliser des guillemets doubles pour permettre les apostrophes
- Tester la syntaxe PHP avec `php -l filename.php`

## Statut
✅ Corrigé - Le formulaire d'inscription devrait maintenant se charger sans erreur de syntaxe