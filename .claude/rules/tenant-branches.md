# Tenant branches — Pattern KLASSCI

## Quand s'active

Cette rule s'active quand :
- L'utilisateur parle de **créer une nouvelle instance/école/tenant** (« on a un nouveau client », « ajoute le tenant X », « crée la branche pour X »).
- Tu envisages de pousser du code spécifique à un tenant dans le repo (settings hardcodés, branding inline, etc.) — **STOP**, ce n'est pas le pattern.
- L'utilisateur dit « sur le serveur prod {tenant} … » et tu te demandes depuis quelle branche le serveur déploie.
- Tu vois `git_branch` mentionné dans la doc (master DB `tenants` table).

## Architecture multi-instance KLASSCI

KLASSCI est **multi-instance isolé** (chaque école = DB séparée) — PAS multi-tenant logique partagé. Voir `docs/SAAS_ARCHITECTURE.md`.

- **`adminKlassci`** : app Filament centrale (DB `klassci_master`) — pilote les déploiements, gère les tenants, leurs plans, leurs branches Git.
- **`KLASSCI` (ce repo)** : app métier — UNE codebase, déployée plusieurs fois sur des serveurs différents avec des DB différentes.

## Pattern des branches Git

Chaque instance a sa **propre branche** dans ce repo, du même nom que le code tenant :

| Branche | Tenant | Offre | Inscriptions |
|---|---|---|---|
| `presentation` | `presentation` | Démo (Free) | démo |
| `esbtp-abidjan` | `esbtp-abidjan` | **Élite** | > 2000 |
| `esbtp-yakro` | `esbtp-yakro` | **Élite** | > 2000 |
| `ephrata` | `ephrata` | **Partenaire** | en cours |
| `hetec` | `hetec` | Test (vise Élite) | en cours |
| `rostan` | `rostan` | Test (vise Élite) | en cours |

**`presentation` est la branche de développement canonique** — c'est là qu'on push toutes les nouvelles features, fixes, refactors.

Les branches tenant sont des **snapshots stables** : elles dérivent de `presentation` puis sont sync périodiquement. **Aucune divergence de code par tenant** dans ce repo — la personnalisation se fait via :
- Variables d'environnement (`.env` sur le serveur)
- Settings DB (table `esbtp_settings` de l'instance)
- Master DB (`klassci_master.tenants.git_branch`, `tenants.config`)

## Workflow de création d'un nouveau tenant

Quand l'utilisateur dit « crée la branche pour {tenant} » :

```bash
# 1. Vérifier qu'on a la dernière version de presentation
git fetch origin

# 2. Créer la branche depuis le HEAD remote de presentation
git checkout -b {tenant} origin/presentation

# 3. Pousser sur origin (avec tracking)
git push -u origin {tenant}

# 4. Revenir sur presentation pour rester en posture de dev
git checkout presentation
```

→ **Ne pas committer** sur la branche tenant après création (sauf cas exceptionnel justifié). Tout code va sur `presentation`, puis sync.

**Ce que je dois faire EN PLUS** :
- Mettre à jour `CLAUDE.md` ligne « Instances actives » pour ajouter le nouveau tenant + son offre.
- Rappeler à l'utilisateur les actions hors-scope :
  - Sur le serveur prod du tenant : `git fetch && git checkout {tenant} && git pull origin {tenant}` puis clear caches Laravel.
  - Sur l'app `adminKlassci` : mettre à jour `tenants.git_branch = '{tenant}'` pour ce tenant.

## Workflow de sync d'un tenant existant

Quand l'utilisateur veut « mettre à jour la prod {tenant} avec les dernières features » :

Option A — **sync via merge (preferred)** : sur le serveur prod, fast-forward depuis `presentation` :
```bash
cd {tenant}
git fetch origin
git checkout {tenant}
git merge --ff-only origin/presentation   # ou origin/{tenant} si la branche tenant a été sync via PR/merge sur GitHub
```

Option B — **sync via GitHub** :
1. En local : `git checkout {tenant} && git merge --ff-only origin/presentation && git push`
2. Sur le serveur : `git pull origin {tenant}`

→ Si la branche tenant a divergé (ex: hotfix poussé directement dessus en urgence), un rebase ou merge non-ff peut être nécessaire — à faire côté GitHub avec PR pour visibilité.

## Naming convention

- Toujours **minuscules**, séparé par tirets si plusieurs mots : `esbtp-abidjan`, `esbtp-yakro`.
- Branches tenant **sans préfixe** (`feat/`, `fix/`, etc.) — ce sont des branches de déploiement, pas de feature.
- Le nom doit matcher le `code` du tenant dans la master DB (`tenants.code`).

## Anti-patterns à BLOQUER

1. ❌ **Pousser du code tenant-spécifique dans la branche tenant** : nom d'école hardcodé, logo en dur, settings figés. → Tout passe par les variables d'env, settings DB, ou conditional rendering basé sur `tenants.config`.
2. ❌ **Force-push sur une branche tenant** : casse le déploiement. Si rebase nécessaire, passer par un PR review.
3. ❌ **Préfixer la branche** (`feat/ephrata`, `tenant/ephrata`) : la convention est juste le code tenant.
4. ❌ **Créer la branche depuis autre chose que `presentation`** : casse le pattern de sync.
5. ❌ **Committer sur la branche tenant en local** depuis ce repo : sauf urgence absolue (hotfix prod), tout code va sur `presentation` d'abord.
6. ❌ **Oublier de mettre à jour `CLAUDE.md`** quand un nouveau tenant arrive — la liste « Instances actives » doit refléter la réalité.
7. ❌ **Confondre `tenants.git_branch` avec le nom de la branche prod** : le master DB pilote quel tenant pull quelle branche, mais c'est sur l'app `adminKlassci`, pas ici.

## Voir aussi

- `docs/SAAS_ARCHITECTURE.md` — architecture multi-instance, master DB schema
- `docs/SAAS_DEPLOYMENT_PLAN.md` — workflow de déploiement
- CLAUDE.md ligne « Instances actives » — liste à jour des tenants
- `.claude/rules/multi-agent-git-safety.md` — discipline git (ne s'applique pas spécifiquement aux branches tenant mais bonnes pratiques générales)
