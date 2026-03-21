# Guide d'Intégration LMS ↔ KLASSCI — Login Unifié Multi-Établissements

> Document à destination de l'équipe LMS pour intégrer le login unifié.

---

## Comment ça fonctionne (résumé)

**Le LMS n'a besoin d'aucun token côté serveur pour fonctionner.**
Tous les endpoints de découverte et de login sont publics.
Chaque utilisateur se connecte et obtient son propre token.

```
┌─────────────────────────────────────────────────────────────────┐
│                     FLOW COMPLET (ZERO TOKEN SERVEUR)           │
│                                                                 │
│  1. Page de login LMS                                           │
│     L'utilisateur choisit son école (dropdown hardcodé)         │
│     OU entre son email/matricule (détection automatique)        │
│                                                                 │
│  2. Détection auto (si pas de dropdown)                         │
│     POST {chaque_tenant}/api/lms/auth/check-user  ← SANS AUTH  │
│     → le tenant qui répond found:true est le bon                │
│                                                                 │
│  3. Login                                                       │
│     POST {tenant}/api/lms/auth/login              ← SANS AUTH  │
│     → token Sanctum de l'utilisateur                            │
│                                                                 │
│  4. Appels API avec le token de l'utilisateur                   │
│     GET {tenant}/api/lms/classes    (Authorization: Bearer ...)  │
│     GET {tenant}/api/lms/matieres   (Authorization: Bearer ...)  │
│     etc.                                                        │
└─────────────────────────────────────────────────────────────────┘
```

**Aucun token permanent, aucun accès serveur, aucune configuration côté KLASSCI.**
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

## 2. Aucun token serveur nécessaire

Les endpoints de découverte (`check-user`, `tenant-info`) et de login sont **tous publics**.
Le LMS peut fonctionner à 100% sans aucun token permanent côté serveur.

Le seul token dans le système est celui de l'**utilisateur** qui se connecte.

> **Pas besoin de token permanent par école.**
> **Pas besoin de token master.**
> **Pas besoin d'accès SSH aux serveurs KLASSCI.**
> **Pas besoin de demander quoi que ce soit à l'équipe KLASSCI.**

### Option avancée : liste dynamique des écoles (optionnel)

Si le LMS veut récupérer **dynamiquement** la liste des écoles KLASSCI au lieu
de la hardcoder, il peut appeler `GET admin.klassci.com/api/lms/tenants`.
Cet endpoint est protégé et nécessite un token master (à demander à l'équipe KLASSCI).

```env
# OPTIONNEL — uniquement si vous voulez la liste dynamique des écoles
KLASSCI_MASTER_URL=https://admin.klassci.com/api
KLASSCI_MASTER_TOKEN=token_fourni_par_klassci
```

**Mais ce n'est pas nécessaire.** Vous pouvez hardcoder les écoles :

```javascript
// Liste statique — fonctionne sans aucun token
const ECOLES = [
  { code: 'hetec', name: 'HETEC', url: 'https://hetec.klassci.com' },
  { code: 'esbtp-abidjan', name: 'ESBTP Abidjan', url: 'https://esbtp-abidjan.klassci.com' },
  { code: 'esbtp-yakro', name: 'ESBTP Yamoussoukro', url: 'https://esbtp-yakro.klassci.com' },
];
```

---

## 3. Login Unifié (Utilisateurs Finaux)

### Approche recommandée : Dropdown + Login

C'est la plus fiable et la plus simple.

#### Étape 1 — Connaître les établissements

**Option simple (recommandé)** : Hardcoder la liste dans le code LMS :

```javascript
const ECOLES = [
  { code: 'hetec', name: 'HETEC', url: 'https://hetec.klassci.com' },
  { code: 'esbtp-abidjan', name: 'ESBTP Abidjan', url: 'https://esbtp-abidjan.klassci.com' },
  { code: 'esbtp-yakro', name: 'ESBTP Yamoussoukro', url: 'https://esbtp-yakro.klassci.com' },
];
```

Quand une nouvelle école est ajoutée, on vous prévient et vous l'ajoutez à la liste.

**Option dynamique (optionnel)** : Appeler le master pour avoir la liste à jour automatiquement.
Voir section 2 "Option avancée" ci-dessus. Nécessite un token master.

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

### Master API (admin.klassci.com) — OPTIONNEL

Ces endpoints nécessitent un token master. Ils sont **optionnels** — le LMS fonctionne sans.

| Méthode | Endpoint | Auth | Description |
|---------|----------|------|-------------|
| `GET` | `/api/lms/tenants` | Bearer token master | Liste dynamique des tenants actifs |
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

### Prérequis
Aucun. Tous les endpoints nécessaires sont publics. Vous pouvez commencer immédiatement.

### Implémentation
- [ ] Hardcoder la liste des écoles (ou appeler le master si token disponible)
- [ ] Page de login : dropdown des écoles OU détection auto par identifiant
- [ ] Détection auto : `POST {tenant}/api/lms/auth/check-user` sur chaque école en parallèle
- [ ] Login : `POST {tenant}/api/lms/auth/login` → stocker `token` + `tenant_url` en session
- [ ] Appels API : utiliser `{tenant_url}/api/lms/*` avec le token de l'utilisateur connecté
- [ ] Gérer 401 (token expiré → rediriger vers login)
- [ ] Gérer 403 `NO_ACTIVE_ENROLLMENT` (étudiant non réinscrit → message explicite)
- [ ] Tester avec `presentation.klassci.com` (login: `superadmin` / `password123`)

### Optionnel
- [ ] Demander le token master pour la liste dynamique des écoles (`GET /api/lms/tenants`)

---

## 8. Environnement de Test

```
URL: https://presentation.klassci.com
Login superAdmin: superadmin / password123

API test:
POST https://presentation.klassci.com/api/lms/auth/login
{"username": "superadmin", "password": "password123"}
```
