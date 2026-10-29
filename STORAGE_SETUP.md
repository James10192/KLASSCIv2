# Configuration du Système de Stockage ESBTP

## 📸 Gestion des Images et Fichiers

Cette application utilise le système de stockage Laravel pour gérer différents types de fichiers :

### 🗂️ Structure des Dossiers

```
storage/app/public/
├── photos/
│   ├── etudiants/          # Photos des étudiants
│   ├── enseignants/        # Photos des enseignants
│   └── secretaires/        # Photos des secrétaires
├── logos/                  # Logo de l'établissement
├── documents/
│   ├── bulletins/          # Bulletins générés
│   ├── attestations/       # Attestations de fréquentation
│   ├── certificats/        # Certificats de scolarité
│   └── reçus/             # Reçus de paiement
├── annonces/               # Images d'annonces
├── partenariats/           # Logos de partenaires
├── evenements/             # Images d'événements
└── uploads/temp/           # Fichiers temporaires
```

### 🔗 URLs d'Accès

Une fois configuré, les fichiers sont accessibles via :
- **Photos étudiants** : `http://votre-domaine.com/storage/photos/etudiants/nom-fichier.jpg`
- **Logo établissement** : `http://votre-domaine.com/storage/logos/logo.png`
- **Documents** : `http://votre-domaine.com/storage/documents/bulletins/bulletin.pdf`

### 📄 Pages qui utilisent les images

#### Photos d'étudiants :
- **Upload** : `inscriptions/create.blade.php` - Formulaire d'inscription
- **Affichage** :
  - `etudiants/index.blade.php` - Liste des étudiants (miniatures)
  - `etudiants/show.blade.php` - Profil étudiant (photo principale)
  - `inscriptions/show.blade.php` - Détails d'inscription
  - `reinscription/show.blade.php` - Réinscription

#### Logo de l'établissement :
- **Configuration** : `settings/index.blade.php` - Paramètres système
- **Affichage** : Bulletins, attestations, certificats, reçus

### 🔧 Configuration Automatique

## Script d'initialisation : `init_storage.php`

Le script `init_storage.php` configure automatiquement tout le système de stockage :

### ✅ Fonctionnalités du script :

1. **Création de la structure de dossiers**
2. **Configuration du lien symbolique** (`public/storage` → `storage/app/public`)
3. **Sécurisation des dossiers** (fichiers `.gitignore` et `index.html`)
4. **Images de test/placeholder**
5. **Vérification de l'accès**

### 🚀 Utilisation

#### Sur votre serveur local :
```bash
php init_storage.php
```

#### Sur votre serveur distant :
```bash
# 1. Copier le script sur le serveur
scp init_storage.php user@server:/path/to/laravel/

# 2. Exécuter le script
php init_storage.php

# 3. Ajuster les permissions (Linux)
chmod -R 755 storage/
chown -R www-data:www-data storage/
```

### 🛠️ Configuration Manuelle (Alternative)

Si le script automatique ne fonctionne pas :

#### 1. Créer le lien symbolique :
```bash
# Avec Artisan
php artisan storage:link

# Ou manuellement (Linux)
ln -s ../storage/app/public public/storage

# Ou manuellement (Windows - en tant qu'administrateur)
mklink /D "public\storage" "storage\app\public"
```

#### 2. Créer les dossiers manuellement :
```bash
mkdir -p storage/app/public/{photos/{etudiants,enseignants,secretaires},logos,documents/{bulletins,attestations,certificats,reçus},annonces,partenariats,evenements,uploads/temp}
```

#### 3. Permissions (Linux) :
```bash
chmod -R 755 storage/
chown -R www-data:www-data storage/
```

### 🎨 Images de Test Incluses

Le script crée automatiquement :

1. **`photos/placeholder.svg`** - Image placeholder pour les étudiants sans photo
2. **`logos/esbtp-logo.svg`** - Logo ESBTP par défaut

### 🔍 Vérification

Pour vérifier que tout fonctionne :

1. **Tester l'accès aux images** :
   - `http://localhost:8000/storage/photos/placeholder.svg`
   - `http://localhost:8000/storage/logos/esbtp-logo.svg`

2. **Vérifier la structure** :
   ```bash
   ls -la storage/app/public/
   ls -la public/storage
   ```

3. **Tester l'upload** : Aller sur `/esbtp/inscriptions/create` et tester l'upload d'une photo

### 🚨 Dépannage

#### Problème : Images non accessibles (404)
- ✅ Vérifier que le lien symbolique existe : `ls -la public/storage`
- ✅ Recréer le lien : `php artisan storage:link`
- ✅ Vérifier les permissions : `chmod -R 755 storage/`

#### Problème : Upload ne fonctionne pas
- ✅ Vérifier les permissions d'écriture : `chmod -R 755 storage/app/public/`
- ✅ Vérifier la configuration PHP : `upload_max_filesize`, `post_max_size`
- ✅ Vérifier l'espace disque disponible

#### Problème : Lien symbolique ne se crée pas (Windows)
- ✅ Exécuter l'invite de commandes en tant qu'administrateur
- ✅ Utiliser la commande PowerShell : `New-Item -ItemType SymbolicLink -Path "public\storage" -Target "storage\app\public"`
- ✅ Alternative : copier manuellement les fichiers dans `public/storage/`

### 🔐 Sécurité

- ✅ Fichiers `index.html` dans chaque dossier pour prévenir le listing
- ✅ Fichiers `.gitignore` pour éviter de committer les uploads
- ✅ Validation des types de fichiers dans les contrôleurs
- ✅ Restriction des extensions autorisées

### 📋 Checklist de Déploiement

- [ ] Script `init_storage.php` exécuté
- [ ] Lien symbolique créé et fonctionnel
- [ ] Permissions correctes (755 pour dossiers, 644 pour fichiers)
- [ ] Test d'upload sur `/esbtp/inscriptions/create`
- [ ] Test d'affichage sur `/esbtp/etudiants`
- [ ] Configuration du logo dans `/esbtp/settings`

---

🎯 **Pour un déploiement rapide sur un nouveau serveur** : Copiez simplement `init_storage.php` et exécutez-le !