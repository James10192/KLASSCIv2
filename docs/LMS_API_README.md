# 🎓 APIs LMS-KLASSCI Integration

[![Version](https://img.shields.io/badge/Version-1.0.0-blue.svg)](https://github.com/klassci/apis)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![Sanctum](https://img.shields.io/badge/Auth-Laravel%20Sanctum-green.svg)](https://laravel.com/docs/sanctum)

## 📋 Description

APIs REST pour l'intégration entre votre LMS et KLASSCI. Permet l'échange bidirectionnel de données éducatives : structure organisationnelle, planning, notes et présences.

## ⚡ Quick Start

### 1. Authentification
```bash
curl -X POST "https://your-klassci.com/api/lms/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "enseignant@school.com",
    "password": "your-password"
  }'
```

### 2. Récupération des données
```bash
# Utiliser le token reçu
curl -X GET "https://your-klassci.com/api/lms/matieres" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Sauvegarde des notes
```bash
curl -X POST "https://your-klassci.com/api/lms/evaluations/1/notes" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "notes": [
      {"etudiant_id": 123, "note": 16.5, "is_absent": false}
    ]
  }'
```

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [📖 Guide d'Intégration](./LMS_INTEGRATION_GUIDE.md) | Guide complet pour développeurs LMS |
| [🔧 Référence Technique](./LMS_API_TECHNICAL_REFERENCE.md) | Documentation technique détaillée |
| [🌐 Documentation API Live](https://your-klassci.com/api/lms/documentation) | Documentation interactive |

## 🛠️ Installation

### Prérequis
- Laravel 10.x avec Sanctum
- PHP 8.1+
- Base de données KLASSCI configurée

### Configuration

1. **Ajouter les routes API** (déjà fait dans `routes/api.php`)

2. **Configurer Sanctum** dans `.env` :
```env
SANCTUM_STATEFUL_DOMAINS=localhost,your-lms-domain.com
```

3. **Publier et migrer** (si nécessaire) :
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

4. **Cacher les routes** :
```bash
php artisan route:cache
```

## 📡 Endpoints Principaux

### 🔐 Authentification
| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/lms/auth/login` | Connexion |
| GET | `/api/lms/auth/me` | Profil utilisateur |
| POST | `/api/lms/auth/logout` | Déconnexion |

### 📖 Lecture (LMS ← KLASSCI)
| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/lms/structure` | Filières et niveaux |
| GET | `/api/lms/matieres` | Matières accessibles |
| GET | `/api/lms/classes` | Classes de l'année |
| GET | `/api/lms/classes/{id}/etudiants` | Étudiants d'une classe |
| GET | `/api/lms/emploi-temps` | Planning des cours |
| GET | `/api/lms/evaluations` | Évaluations programmées |

### ✏️ Écriture (LMS → KLASSCI)
| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/lms/evaluations/{id}/notes` | Sauvegarder notes |
| POST | `/api/lms/cours/{id}/presences` | Enregistrer présences |
| PUT | `/api/lms/cours/{id}/statut` | Mettre à jour statut cours |

## 🔒 Sécurité

### Authentification
- **Type :** Bearer Token (Laravel Sanctum)
- **Scope :** `lms:access`
- **Expiration :** Configurable (par défaut : aucune)

### Permissions par Rôle

| Rôle | Accès Données | Écriture |
|------|---------------|----------|
| **Coordinateur** | Toutes les données | Toutes les actions |
| **Enseignant** | Ses matières/classes | Ses évaluations/cours |
| **Étudiant** | Ses données | Aucune |

### Filtrage Automatique
- **Année universitaire :** Courante uniquement
- **Données :** Filtrées selon le rôle automatiquement
- **Validation :** Permissions vérifiées à chaque requête

## 📊 Format des Réponses

### Succès
```json
{
  "success": true,
  "data": { ... },
  "message": "Opération réussie",
  "meta": {
    "timestamp": "2024-10-15T14:30:00Z",
    "api_version": "1.0",
    "annee_universitaire_courante": {
      "id": 3,
      "nom": "2024-2025"
    },
    "user_context": {
      "role": "enseignant",
      "is_enseignant": true
    }
  }
}
```

### Erreur
```json
{
  "success": false,
  "message": "Description de l'erreur",
  "errors": { ... },
  "meta": { ... }
}
```

## 🚨 Codes d'Erreur

| Code | Signification | Action |
|------|---------------|--------|
| 200 | Succès | - |
| 401 | Non authentifié | Vérifier le token |
| 403 | Accès refusé | Vérifier les permissions |
| 404 | Ressource introuvable | Vérifier l'ID |
| 422 | Données invalides | Corriger les données |
| 500 | Erreur serveur | Contacter le support |

## 🔧 Développement

### Structure des Contrôleurs

```
app/Http/Controllers/API/
├── BaseApiController.php      # Logique commune
├── AuthController.php         # Authentification
├── LMSDataController.php      # Lecture des données
└── LMSWriteController.php     # Écriture des données
```

### Tests

```bash
# Test de connexion
curl -X POST "/api/lms/auth/login" \
  -d '{"email":"test@test.com","password":"password"}'

# Test de récupération
curl -X GET "/api/lms/matieres" \
  -H "Authorization: Bearer TOKEN"

# Test de sauvegarde
curl -X POST "/api/lms/evaluations/1/notes" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"notes":[{"etudiant_id":1,"note":15}]}'
```

### Debug

```bash
# Voir les routes
php artisan route:list --path=api/lms

# Logs
tail -f storage/logs/laravel.log | grep "LMS"

# Test en console
php artisan tinker
>>> $user = App\Models\User::find(1)
>>> $token = $user->createToken('test')->plainTextToken
```

## 📈 Monitoring

### Métriques Recommandées
- Nombre de requêtes par endpoint
- Temps de réponse moyen
- Taux d'erreur par type
- Volume de données échangées

### Logs
Les APIs loggent automatiquement :
- Connexions/déconnexions
- Sauvegardes de notes
- Enregistrements de présences
- Erreurs et exceptions

## 🔄 Workflow d'Intégration

### 1. Cours en Ligne
```
LMS récupère planning → Démarre visio → Met à jour statut →
Enregistre présences → Marque cours terminé
```

### 2. Évaluation
```
LMS récupère évaluations → Crée examen en ligne →
Collecte résultats → Sauvegarde notes dans KLASSCI
```

### 3. Synchronisation
```
Vérification auth → Récupération nouvelles données →
Mise à jour cache LMS → Programmation prochaine sync
```

## 🆘 Support

### Documentation
- **Guide complet :** [LMS_INTEGRATION_GUIDE.md](./LMS_INTEGRATION_GUIDE.md)
- **Référence technique :** [LMS_API_TECHNICAL_REFERENCE.md](./LMS_API_TECHNICAL_REFERENCE.md)
- **API Live :** `/api/lms/documentation`

### Aide au Debug
- **Logs :** `storage/logs/laravel.log`
- **Routes :** `php artisan route:list --path=api/lms`
- **Config Sanctum :** `php artisan config:show sanctum`

### Contact
- **Team :** KLASSCI Development Team
- **Issues :** Utiliser le système de tickets KLASSCI

---

## 📄 Changelog

### Version 1.0.0 (2024-10-15)
- ✅ Authentification unifiée avec Sanctum
- ✅ APIs de lecture pour structure, matières, classes
- ✅ APIs d'écriture pour notes et présences
- ✅ Filtrage automatique par rôle et année
- ✅ Documentation complète
- ✅ Gestion d'erreurs et logging

---

## 📜 Licence

APIs développées pour l'écosystème KLASSCI. Usage autorisé pour les intégrations LMS approuvées.