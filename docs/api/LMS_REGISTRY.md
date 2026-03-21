# API LMS - Registre des Tenants (Admin Master)

## Vue d'ensemble

Endpoint sur le serveur master (`admin.klassci.com`) permettant au LMS de decouvrir dynamiquement tous les tenants KLASSCI actifs et leurs URLs API.

**Base URL**: `https://admin.klassci.com/api`

**Authentification**: Bearer Token (API token du tenant, stocke dans la table `tenants.api_token`)

---

## Endpoint: Liste des tenants actifs

**GET** `/api/lms/tenants`

Retourne la liste de tous les tenants KLASSCI actifs avec leurs URLs pour l'integration LMS.

**Exemple:**
```bash
curl -X GET "https://admin.klassci.com/api/lms/tenants" \
  -H "Authorization: Bearer {MASTER_API_TOKEN}"
```

**Reponse (200):**
```json
{
  "success": true,
  "data": {
    "tenants": [
      {
        "code": "hetec",
        "name": "HETEC",
        "subdomain": "hetec",
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
        "subdomain": "esbtp-abidjan",
        "url": "https://esbtp-abidjan.klassci.com",
        "api_base_url": "https://esbtp-abidjan.klassci.com/api/lms",
        "login_url": "https://esbtp-abidjan.klassci.com/api/lms/auth/login",
        "check_user_url": "https://esbtp-abidjan.klassci.com/api/lms/auth/check-user",
        "tenant_info_url": "https://esbtp-abidjan.klassci.com/api/lms/tenant-info",
        "plan": "professional"
      },
      {
        "code": "esbtp-yakro",
        "name": "ESBTP Yamoussoukro",
        "subdomain": "esbtp-yakro",
        "url": "https://esbtp-yakro.klassci.com",
        "api_base_url": "https://esbtp-yakro.klassci.com/api/lms",
        "login_url": "https://esbtp-yakro.klassci.com/api/lms/auth/login",
        "check_user_url": "https://esbtp-yakro.klassci.com/api/lms/auth/check-user",
        "tenant_info_url": "https://esbtp-yakro.klassci.com/api/lms/tenant-info",
        "plan": "essentiel"
      }
    ],
    "count": 3
  },
  "meta": {
    "timestamp": "2026-03-21T10:00:00.000Z",
    "api_version": "1.0",
    "usage": {
      "description": "Liste des etablissements KLASSCI actifs pour integration LMS",
      "login_flow": "Utiliser check_user_url sur chaque tenant pour trouver ou un utilisateur existe, puis login_url pour authentifier."
    }
  }
}
```

---

## Description des champs

| Champ | Type | Description |
|-------|------|-------------|
| `code` | string | Code unique du tenant (ex: `esbtp-abidjan`) |
| `name` | string | Nom complet de l'etablissement |
| `subdomain` | string | Sous-domaine (`{subdomain}.klassci.com`) |
| `url` | string | URL complete du tenant |
| `api_base_url` | string | URL de base pour toutes les APIs LMS |
| `login_url` | string | URL directe du endpoint de login |
| `check_user_url` | string | URL directe du endpoint de recherche utilisateur |
| `tenant_info_url` | string | URL directe des infos publiques du tenant |
| `plan` | string | Plan d'abonnement (`free`, `essentiel`, `professional`, `elite`) |

---

## Authentification

Le token d'acces est genere par la commande artisan sur le serveur master :

```bash
php artisan tenant:generate-token {tenant_code}
```

Ce token est stocke dans la colonne `api_token` de la table `tenants` et doit etre configure dans le `.env` du LMS :

```env
KLASSCI_MASTER_API_URL=https://admin.klassci.com/api
KLASSCI_MASTER_API_TOKEN=your_token_here
```

---

## Cas d'usage

### 1. Construire le dropdown des ecoles au demarrage du LMS

```javascript
// Au demarrage du LMS
const response = await fetch(`${KLASSCI_MASTER_API_URL}/lms/tenants`, {
  headers: { 'Authorization': `Bearer ${KLASSCI_MASTER_API_TOKEN}` }
});
const { data } = await response.json();

// Cacher la liste (les tenants changent rarement)
cache.set('klassci_tenants', data.tenants, { ttl: '1h' });
```

### 2. Detection automatique du tenant d'un utilisateur

```javascript
// Appeler check-user sur chaque tenant en parallele
async function findUserTenant(identifier) {
  const tenants = cache.get('klassci_tenants');

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
  return null; // Utilisateur introuvable sur aucun tenant
}
```

---

## Architecture technique

**Controller**: `adminKlassci/app/Http/Controllers/API/LMSRegistryController.php`

**Middleware**: `tenant.api` (`VerifyTenantApiToken` - verifie le Bearer token dans `tenants.api_token`)

**Table**: `tenants` (BDD `klassci_master`)

---

## Codes d'erreur

| Code | Message | Description |
|------|---------|-------------|
| 401 | Unauthorized | Token manquant ou invalide |

---

## Historique des modifications

### Version 1.0 - 21 mars 2026

**Changements**:
- Creation initiale : `GET /api/lms/tenants`
- Retourne tous les tenants actifs avec URLs pre-construites pour login/check-user/tenant-info

---

*Derniere mise a jour: 21 mars 2026*
