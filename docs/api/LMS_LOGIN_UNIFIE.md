# Guide d'Intégration LMS ↔ KLASSCI

> Document à destination de l'équipe LMS pour intégrer le login unifié multi-établissements.

---

## 1. Architecture Multi-Tenant KLASSCI

KLASSCI fonctionne en **multi-tenant isolé** : chaque établissement a sa propre instance Laravel avec sa propre base de données.

```
                    ┌──────────────────────────────┐
                    │   admin.klassci.com (Master)  │
                    │   DB: klassci_master          │
                    │   Rôle: registre des tenants  │
                    └──────────┬───────────────────┘
                               │
            ┌──────────────────┼──────────────────┐
            │                  │                  │
   ┌────────▼───────┐ ┌───────▼────────┐ ┌───────▼────────┐
   │ hetec.klassci  │ │ esbtp-abidjan  │ │ esbtp-yakro    │
   │ .com           │ │ .klassci.com   │ │ .klassci.com   │
   │ DB isolée      │ │ DB isolée      │ │ DB isolée      │
   │ API LMS: /api/ │ │ API LMS: /api/ │ │ API LMS: /api/ │
   │ lms/*          │ │ lms/*          │ │ lms/*          │
   └────────────────┘ └────────────────┘ └────────────────┘
```

**Conséquence** : Un utilisateur (étudiant, enseignant) n'existe que dans la BDD de SON tenant. Le LMS doit savoir vers quel tenant diriger l'authentification.

---

## 2. Accès Backend (Tokens Machine-to-Machine)

Pour que le backend LMS lise les données de chaque tenant (classes, matières, enseignants, évaluations), il faut **un token Sanctum permanent par tenant**.

### Générer un token

Sur chaque serveur tenant, exécuter :

```bash
php artisan tinker
```

```php
$user = \App\Models\User::role('superAdmin')->first();
$token = $user->createToken('LMS-Backend-Permanent', ['lms:access']);
echo $token->plainTextToken;
// Exemple: 3|abc123def456...
```

### Utiliser le token

```bash
# Lire les classes
curl -H "Authorization: Bearer 3|abc123def456..." \
     https://esbtp-abidjan.klassci.com/api/lms/classes

# Lire les matières
curl -H "Authorization: Bearer 3|abc123def456..." \
     https://esbtp-abidjan.klassci.com/api/lms/matieres

# Lire les enseignants
curl -H "Authorization: Bearer 3|abc123def456..." \
     https://esbtp-abidjan.klassci.com/api/lms/enseignants

# Lire les évaluations
curl -H "Authorization: Bearer 3|abc123def456..." \
     https://esbtp-abidjan.klassci.com/api/lms/evaluations
```

### Sécurité

- Les tokens **n'expirent pas** (config Sanctum `expiration: null`)
- Scope limité à `lms:access`
- Stocker les tokens côté LMS dans des **variables d'environnement**, jamais en dur dans le code
- Un token par tenant → si un token est compromis, seul ce tenant est affecté

---

## 3. Login Unifié (Utilisateurs Finaux)

### Approche recommandée : Dropdown + Login

C'est la plus fiable et la plus simple.

#### Étape 1 — Récupérer la liste des établissements

Au démarrage du LMS, appeler le master :

```
GET https://admin.klassci.com/api/lms/tenants
Authorization: Bearer {MASTER_API_TOKEN}
```

Réponse :
```json
{
  "success": true,
  "data": {
    "tenants": [
      {
        "code": "hetec",
        "name": "HETEC",
        "url": "https://hetec.klassci.com",
        "api_base_url": "https://hetec.klassci.com/api/lms",
        "login_url": "https://hetec.klassci.com/api/lms/auth/login",
        "check_user_url": "https://hetec.klassci.com/api/lms/auth/check-user",
        "tenant_info_url": "https://hetec.klassci.com/api/lms/tenant-info",
        "plan": "professional"
      },
      {
        "code": "esbtp-abidjan",
        "name": "ESBTP Abidjan",
        "url": "https://esbtp-abidjan.klassci.com",
        "...": "..."
      }
    ],
    "count": 3
  }
}
```

**Cacher cette liste** côté LMS (1h minimum). Les tenants changent rarement.

#### Étape 2 — Page de login

Deux options d'UX :

**Option A — Dropdown explicite (recommandé)** :
1. L'utilisateur choisit son école dans un dropdown
2. Entre son email/username + mot de passe
3. Le LMS appelle `POST {login_url}` du tenant choisi

**Option B — Détection automatique** :
1. L'utilisateur entre son identifiant (email, username, ou matricule)
2. Le LMS appelle `POST /api/lms/auth/check-user` sur CHAQUE tenant **en parallèle**
3. Le tenant qui répond `found: true` est le bon
4. Le LMS affiche "Vous êtes de {tenant_name}" et demande le mot de passe
5. Login sur le bon tenant

```javascript
// Exemple détection automatique (JS)
async function findUserTenant(identifier, tenants) {
  const results = await Promise.allSettled(
    tenants.map(tenant =>
      fetch(tenant.check_user_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identifier })
      }).then(r => r.json()).then(data => ({ tenant, data }))
    )
  );

  for (const result of results) {
    if (result.status === 'fulfilled' && result.value.data.data?.found) {
      return result.value; // { tenant, data }
    }
  }
  return null; // Utilisateur introuvable
}
```

#### Étape 3 — Authentification

```
POST https://{tenant}.klassci.com/api/lms/auth/login
Content-Type: application/json

{
  "username": "jean.dupont@email.com",  // email OU username
  "password": "motdepasse123"
}
```

Réponse succès (200) :
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
      "role_display_name": "Étudiant",
      "etudiant_data": {
        "etudiant_id": 15,
        "matricule": "2024-ETU-001",
        "classe": {
          "id": 3,
          "nom": "BTS Bâtiment 1ère année",
          "filiere": "Bâtiment",
          "niveau": "1ère année"
        }
      }
    }
  }
}
```

#### Étape 4 — Stocker la session

Côté LMS, stocker dans la session utilisateur :

```javascript
// Ce qu'il faut garder en session
session = {
  token: "5|xyzTokenHere...",
  tenant_url: "https://esbtp-abidjan.klassci.com",
  tenant_code: "esbtp-abidjan",
  user: { /* données retournées par login */ }
};

// Tous les appels API ensuite :
fetch(`${session.tenant_url}/api/lms/classes`, {
  headers: { 'Authorization': `Bearer ${session.token}` }
});
```

---

## 4. Endpoints Disponibles

### Sans authentification

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/lms/auth/login` | Connexion (email/username + password) → token |
| `POST` | `/api/lms/auth/check-user` | Cherche un utilisateur par email/username/matricule (rate-limited 10/min) |
| `POST` | `/api/lms/auth/check-availability` | Vérifie si email/username existe (rate-limited 10/min) |
| `GET` | `/api/lms/tenant-info` | Infos publiques du tenant (code, nom, URL, features) |
| `GET` | `/api/lms/auth/documentation` | Documentation de l'API auth |
| `GET` | `/api/lms/documentation` | Documentation complète de l'API |

### Avec authentification (Bearer token)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/lms/auth/me` | Profil utilisateur connecté |
| `GET` | `/api/lms/auth/check` | Vérifier validité du token |
| `POST` | `/api/lms/auth/logout` | Révoquer le token courant |
| `GET` | `/api/lms/classes` | Classes de l'année courante |
| `GET` | `/api/lms/classes/{id}` | Détails d'une classe |
| `GET` | `/api/lms/classes/{id}/etudiants` | Étudiants d'une classe |
| `GET` | `/api/lms/matieres` | Matières (filtrées par rôle) |
| `GET` | `/api/lms/matieres/{id}` | Détails d'une matière |
| `GET` | `/api/lms/enseignants` | Enseignants actifs |
| `GET` | `/api/lms/evaluations` | Évaluations programmées |
| `GET` | `/api/lms/emploi-temps` | Emploi du temps |
| `GET` | `/api/lms/filieres` | Filières |
| `GET` | `/api/lms/niveaux-etudes` | Niveaux d'études |
| `GET` | `/api/lms/structure` | Structure organisationnelle |
| `GET` | `/api/lms/me/dashboard` | Dashboard étudiant |
| `GET` | `/api/lms/me/teacher-dashboard` | Dashboard enseignant |
| `GET` | `/api/lms/seances/upcoming` | Séances à venir (visio) |
| `POST` | `/api/lms/evaluations/{id}/notes` | Soumettre des notes |
| `POST` | `/api/lms/cours/{id}/presences` | Enregistrer présences |
| `PUT` | `/api/lms/cours/{id}/statut` | Mettre à jour statut cours |

### Master API (admin.klassci.com)

| Méthode | Endpoint | Auth | Description |
|---------|----------|------|-------------|
| `GET` | `/api/lms/tenants` | Bearer token master | Liste tous les tenants actifs |
| `GET` | `/api/tenants/{code}/limits` | Bearer token master | Quotas et limites d'un tenant |

---

## 5. Filtrage par Rôle

Les données retournées sont **automatiquement filtrées** selon le rôle du token :

| Rôle | Accès |
|------|-------|
| `superAdmin` / `coordinateur` | Toutes les données du tenant |
| `enseignant` | Ses matières, ses classes, ses évaluations |
| `etudiant` | Sa classe, ses matières, ses évaluations |

Un enseignant ne verra que ses propres matières dans `/api/lms/matieres`. Pas besoin de filtrer côté LMS.

---

## 6. Pièges à Éviter

### CORS
Les API KLASSCI acceptent les requêtes cross-origin. Si le LMS est un SPA frontend, vérifier que `config/cors.php` sur chaque tenant autorise le domaine du LMS. Si c'est du backend-to-backend, pas de souci CORS.

### Rate-Limiting
- `check-user` et `check-availability` : **10 requêtes/minute par IP**
- Toutes les autres routes API : **60 requêtes/minute**
- Si le LMS fait du check-user sur 4 tenants en parallèle, ça compte comme 1 requête par tenant (IPs différentes = pas de problème)

### Tokens
- Les tokens Sanctum ne portent **pas** d'information tenant. Un token créé sur `esbtp-abidjan` ne fonctionne que sur `esbtp-abidjan`.
- Le token backend (machine-to-machine) est lié à un user. Si ce user est désactivé, le token ne fonctionnera plus.
- Recommandation : créer un user dédié "LMS Bot" avec le rôle `superAdmin` pour les tokens backend.

### Cache
- Cacher la liste des tenants (master) pendant au moins 1 heure
- Cacher le résultat de `tenant-info` pendant au moins 1 heure
- NE PAS cacher les résultats de `check-user` (données utilisateur changent)

### Erreurs courantes
- **401 Unauthorized** : Token manquant, expiré ou invalide
- **403 Forbidden** : Utilisateur inactif, rôle non autorisé, ou étudiant non réinscrit pour l'année courante
- **422 Unprocessable** : Données de requête invalides (voir `errors` dans la réponse)
- **429 Too Many Requests** : Rate-limit atteint, réessayer après 60 secondes

---

## 7. Checklist d'Intégration

- [ ] Stocker les tokens backend (1 par tenant) en variables d'environnement
- [ ] Appeler `GET /api/lms/tenants` (master) au démarrage du LMS
- [ ] Implémenter la page de login avec sélection d'école
- [ ] Stocker `token` + `tenant_url` en session après login
- [ ] Utiliser `{tenant_url}/api/lms/*` pour tous les appels data
- [ ] Gérer les erreurs 401 (token expiré → rediriger vers login)
- [ ] Gérer l'erreur 403 `NO_ACTIVE_ENROLLMENT` (étudiant non réinscrit)
- [ ] Tester avec le tenant `presentation.klassci.com` (environnement de test)

---

## 8. Environnement de Test

```
URL: https://presentation.klassci.com
Login superAdmin: superadmin / password123

API test:
POST https://presentation.klassci.com/api/lms/auth/login
{"username": "superadmin", "password": "password123"}
```
