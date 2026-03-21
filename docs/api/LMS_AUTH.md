# API LMS - Authentification & Decouverte Multi-Tenant

## Vue d'ensemble

Endpoints d'authentification et de decouverte multi-tenant pour l'integration LMS.
Permet le login unifie depuis un seul lien LMS vers n'importe quel tenant KLASSCI.

**Base URL**: `https://{tenant}.klassci.com/api/lms`

**Authentification**: Mixte - certains endpoints publics (rate-limited), d'autres proteges par Bearer Token (Sanctum)

---

## Endpoints Publics (sans auth)

### 1. Login

**POST** `/api/lms/auth/login`

Authentifie un utilisateur et retourne un token Sanctum.

**Parametres:**

| Parametre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `username` | string | oui | Email ou nom d'utilisateur |
| `password` | string | oui | Mot de passe (min 6 caracteres) |
| `remember` | boolean | non | Se souvenir de moi |

**Exemple:**
```bash
curl -X POST "https://esbtp-abidjan.klassci.com/api/lms/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username": "jean.dupont@email.com", "password": "motdepasse123"}'
```

**Reponse succes (200):**
```json
{
  "success": true,
  "data": {
    "token": "5|xyzTokenHere...",
    "token_type": "Bearer",
    "user": {
      "id": 42,
      "nom": "Jean Dupont",
      "email": "jean.dupont@email.com",
      "role": "etudiant",
      "roles": ["etudiant"],
      "role_display_name": "Etudiant",
      "etudiant_data": {
        "etudiant_id": 15,
        "matricule": "2024-ETU-001",
        "classe": {
          "id": 3,
          "nom": "BTS Batiment 1ere annee",
          "filiere": "Batiment",
          "niveau": "1ere annee"
        }
      }
    }
  }
}
```

**Roles autorises**: `enseignant`, `coordinateur`, `etudiant`, `superAdmin`

---

### 2. Recherche utilisateur (Decouverte multi-tenant)

**POST** `/api/lms/auth/check-user`

Recherche un utilisateur par email, username ou matricule sur ce tenant.
Utilise par le LMS pour detecter automatiquement le tenant d'un utilisateur.

**Rate limit**: 10 requetes/minute par IP

**Parametres:**

| Parametre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `identifier` | string | oui | Email, username ou matricule (min 3 caracteres) |

**Exemple:**
```bash
curl -X POST "https://esbtp-abidjan.klassci.com/api/lms/auth/check-user" \
  -H "Content-Type: application/json" \
  -d '{"identifier": "jean.dupont@email.com"}'
```

**Reponse - Utilisateur trouve (200):**
```json
{
  "success": true,
  "data": {
    "found": true,
    "tenant_code": "esbtp-abidjan",
    "tenant_name": "ESBTP Abidjan",
    "tenant_url": "https://esbtp-abidjan.klassci.com",
    "user_hint": {
      "display_name": "Jean D.",
      "role_display": "Etudiant"
    }
  },
  "message": "Utilisateur trouve"
}
```

**Reponse - Non trouve (200):**
```json
{
  "success": true,
  "data": {
    "found": false,
    "tenant_code": "esbtp-abidjan"
  },
  "message": "Utilisateur non trouve sur ce tenant"
}
```

**Securite**: Le champ `display_name` retourne uniquement le prenom + initiale du nom (ex: "Jean D.") pour limiter la fuite d'informations.

---

### 3. Verification disponibilite identifiant

**POST** `/api/lms/auth/check-availability`

Verifie si un email ou username existe sur ce tenant.

**Rate limit**: 10 requetes/minute par IP

**Parametres:**

| Parametre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `email` | string | non* | Email a verifier |
| `username` | string | non* | Username a verifier |

*Au moins un des deux est requis.

**Exemple:**
```bash
curl -X POST "https://esbtp-abidjan.klassci.com/api/lms/auth/check-availability" \
  -H "Content-Type: application/json" \
  -d '{"email": "jean.dupont@email.com", "username": "jdupont"}'
```

**Reponse (200):**
```json
{
  "success": true,
  "data": {
    "tenant_code": "esbtp-abidjan",
    "email_exists": true,
    "username_exists": false
  },
  "message": "Verification effectuee"
}
```

---

### 4. Informations tenant

**GET** `/api/lms/tenant-info`

Retourne les informations publiques du tenant et les features API disponibles.

**Exemple:**
```bash
curl -X GET "https://esbtp-abidjan.klassci.com/api/lms/tenant-info"
```

**Reponse (200):**
```json
{
  "success": true,
  "data": {
    "tenant_code": "esbtp-abidjan",
    "tenant_name": "ESBTP Abidjan",
    "tenant_url": "https://esbtp-abidjan.klassci.com",
    "api_base_url": "https://esbtp-abidjan.klassci.com/api/lms",
    "api_version": "1.0",
    "annee_universitaire": {
      "id": 1,
      "nom": "2024-2025"
    },
    "features": {
      "login": true,
      "classes": true,
      "matieres": true,
      "enseignants": true,
      "evaluations": true,
      "emploi_temps": true,
      "notes_write": true,
      "presences_write": true,
      "visio_support": true
    }
  }
}
```

---

### 5. Documentation API auth

**GET** `/api/lms/auth/documentation`

Retourne la documentation interactive de l'API d'authentification.

---

## Endpoints Proteges (Bearer Token)

### 6. Profil utilisateur connecte

**GET** `/api/lms/auth/me`

**Headers**: `Authorization: Bearer {token}`

**Exemple:**
```bash
curl -X GET "https://esbtp-abidjan.klassci.com/api/lms/auth/me" \
  -H "Authorization: Bearer 5|xyzToken..."
```

---

### 7. Verification validite token

**GET** `/api/lms/auth/check`

**Headers**: `Authorization: Bearer {token}`

**Reponse (200):**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "user_id": 42,
    "expires_in": null
  }
}
```

---

### 8. Deconnexion

**POST** `/api/lms/auth/logout`

Revoque le token courant.

**POST** `/api/lms/auth/logout-all`

Revoque tous les tokens de l'utilisateur (deconnexion tous appareils).

---

## Filtrage par Role

Les donnees retournees au login sont automatiquement filtrees selon le role :

| Role | Donnees specifiques |
|------|---------------------|
| `enseignant` | `enseignant_data` : nb matieres, nb classes, matieres principales, classes enseignees |
| `etudiant` | `etudiant_data` : matricule, inscription active, classe, filiere, niveau |
| `coordinateur` / `superAdmin` | `admin_data` : statistiques globales, annee universitaire, permissions admin |

---

## Codes d'erreur

| Code | Message | Description |
|------|---------|-------------|
| 401 | Identifiants incorrects | Email/username ou mot de passe incorrect |
| 403 | Compte desactive | L'utilisateur est desactive (`is_active=false`) |
| 403 | Acces non autorise au LMS | Le role de l'utilisateur n'est pas dans la liste autorisee |
| 403 | Pas reinscrit (code: `NO_ACTIVE_ENROLLMENT`) | L'etudiant n'a pas d'inscription active pour l'annee courante |
| 422 | Donnees invalides | Parametres de requete manquants ou invalides |
| 429 | Too Many Requests | Rate limit atteint (10/min pour check-user/check-availability) |

---

## Architecture technique

**Controller**: `app/Http/Controllers/API/AuthController.php`

**Dependances**:
- Laravel Sanctum (`HasApiTokens` sur le modele `User`)
- Spatie Permission (`HasRoles`)
- `App\Helpers\RoleHelper` pour la resolution des roles

**Rate limiter**: `lms-discovery` defini dans `RouteServiceProvider` (10/min par IP)

**Tables utilisees**:
- `users` (email, username, is_active)
- `esbtp_etudiants` (matricule, user_id)
- `personal_access_tokens` (Sanctum)

---

## Historique des modifications

### Version 1.1 - 21 mars 2026

**Type**: Enhancement (backward compatible)

**Changements**:
- Ajout `POST /api/lms/auth/check-user` : recherche utilisateur par email/username/matricule
- Ajout `POST /api/lms/auth/check-availability` : verification existence email/username
- Ajout `GET /api/lms/tenant-info` : informations publiques du tenant
- Ajout rate limiter `lms-discovery` (10 requetes/minute par IP)
- Support login unifie multi-tenant

**Breaking changes**: Aucun

### Version 1.0 - 25 octobre 2025

**Changements**:
- Creation initiale : login, me, logout, logout-all, check, documentation

---

*Derniere mise a jour: 21 mars 2026*
