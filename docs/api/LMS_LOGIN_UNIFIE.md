# Guide d'Intégration LMS ↔ KLASSCI — Login Unifié Multi-Établissements

> Document à destination de l'équipe LMS pour intégrer le login unifié.

---

## Comment ça fonctionne (résumé)

Le LMS n'a besoin que d'**un seul token** : celui du master (`admin.klassci.com`).
Ensuite, chaque utilisateur (étudiant, enseignant) se connecte lui-même via le login
et obtient son propre token. **Pas besoin de tokens permanents par tenant.**

```
┌─────────────────────────────────────────────────────────────────┐
│                         FLOW COMPLET                            │
│                                                                 │
│  1. LMS démarre                                                 │
│     GET admin.klassci.com/api/lms/tenants                       │
│     → liste des écoles [{code, name, login_url, ...}]           │
│                                                                 │
│  2. Utilisateur arrive sur le LMS                               │
│     → choisit son école (dropdown) OU entre son email           │
│     → si email : check-user en parallèle sur chaque tenant     │
│                                                                 │
│  3. Login                                                       │
│     POST {tenant}/api/lms/auth/login                            │
│     → token Sanctum de l'utilisateur                            │
│                                                                 │
│  4. Appels API avec le token de l'utilisateur                   │
│     GET {tenant}/api/lms/classes    (Authorization: Bearer ...)  │
│     GET {tenant}/api/lms/matieres   (Authorization: Bearer ...)  │
│     etc.                                                        │
└─────────────────────────────────────────────────────────────────┘
```

**Aucun token permanent par tenant n'est nécessaire.**
Le token de l'utilisateur connecté donne accès à toutes les données
filtrées selon son rôle (étudiant voit sa classe, enseignant voit ses matières,
coordinateur/superAdmin voit tout).

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

## 2. Le seul token dont le LMS a besoin

Le LMS n'a besoin que du **token master** pour appeler `GET /api/lms/tenants` sur `admin.klassci.com`. Ce token est fourni par l'équipe KLASSCI.

```env
# .env côté LMS — seules variables nécessaires
KLASSCI_MASTER_URL=https://admin.klassci.com/api
KLASSCI_MASTER_TOKEN=token_fourni_par_klassci
```

Tout le reste passe par le **login utilisateur** : l'étudiant ou l'enseignant entre ses identifiants, obtient un token, et ce token sert pour tous les appels API.

> **Pas besoin de demander un token permanent par école.**
> Pas besoin de stocker de credentials KLASSCI dans le backend LMS.
> Pas besoin d'accès SSH aux serveurs KLASSCI.

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
- Les tokens Sanctum ne portent **pas** d'information tenant. Un token créé sur `esbtp-abidjan` ne fonctionne **que** sur `esbtp-abidjan`.
- Après le login, le LMS doit stocker le `token` ET le `tenant_url` ensemble en session. Si l'utilisateur change de page, le LMS doit savoir vers quel tenant envoyer les requêtes.
- Si un utilisateur se déconnecte côté KLASSCI (token révoqué), le LMS recevra un 401. Rediriger vers la page de login.

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

### Prérequis (une seule fois)
- [ ] Recevoir le token master de l'équipe KLASSCI (`KLASSCI_MASTER_TOKEN`)
- [ ] Configurer `KLASSCI_MASTER_URL` et `KLASSCI_MASTER_TOKEN` dans le `.env` du LMS

### Implémentation
- [ ] Au démarrage : appeler `GET /api/lms/tenants` (master) et cacher la liste 1h
- [ ] Page de login : dropdown des écoles OU détection auto par identifiant
- [ ] Login : `POST {tenant}/api/lms/auth/login` → stocker `token` + `tenant_url` en session
- [ ] Appels API : utiliser `{tenant_url}/api/lms/*` avec le token de l'utilisateur
- [ ] Gérer 401 (token expiré → rediriger vers login)
- [ ] Gérer 403 `NO_ACTIVE_ENROLLMENT` (étudiant non réinscrit → message explicite)
- [ ] Tester avec `presentation.klassci.com` (login: `superadmin` / `password123`)

---

## 8. Environnement de Test

```
URL: https://presentation.klassci.com
Login superAdmin: superadmin / password123

API test:
POST https://presentation.klassci.com/api/lms/auth/login
{"username": "superadmin", "password": "password123"}
```
