# Plan : Fix FPDI "Class FPDF not found"

## Problème
`\setasign\Fpdi\Fpdi` étend `FpdfTpl` qui étend `\FPDF`. La classe `FPDF` n'est pas installée sur le serveur.

## Solution
Ajouter `setasign/fpdf` comme dépendance dans `composer.json`.

## Étape unique

1. **Ajouter `"setasign/fpdf": "^1.8"` dans `composer.json`** (section `require`)

## Déploiement serveur
```bash
composer require setasign/fpdf
```

## Impact
- 1 fichier modifié : `composer.json`
- Aucun changement de code PHP
