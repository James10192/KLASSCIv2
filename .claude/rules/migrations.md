---
description: Règle pour la création de fichiers de migration Laravel
globs: database/migrations/**
---

# Migrations Laravel

**OBLIGATOIRE** : Toujours utiliser `php artisan make:migration nom_migration` pour créer les fichiers de migration.

Ne JAMAIS créer les fichiers de migration manuellement avec Write, cat, echo ou tout autre outil de création de fichier.

## Workflow correct

1. `php artisan make:migration create_table_name` → génère le fichier avec le bon timestamp
2. Utiliser `Edit` pour modifier le contenu du fichier généré (ajouter colonnes, index, etc.)

## Pourquoi

- Le timestamp est garanti correct et unique
- Le format du nom de fichier suit les conventions Laravel
- La structure de base (up/down, Schema::create/table) est correcte
