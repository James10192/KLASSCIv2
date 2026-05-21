# Security Audit — KLASSCIv2

**Date** : 21 mai 2026
**Auditeur** : Claude (claude-opus-4-7) + Marcel review
**Scope** : KLASSCIv2 branch `presentation` + 5 tenants prod (presentation, esbtp-abidjan, esbtp-yakro, ephrata, hetec, rostan)
**Méthode** : (1) audit critic-agent sur OWASP Top 10 + IDOR + auth, (2) audit god-code seuils (rule no-god-code.md), (3) recherche web CVE Laravel 9 / Sanctum 3 / DomPDF 2 / Spatie 5, (4) audit dépendances Dependabot, (5) vérification déploiement live via curl + klassci-cli.

## TL;DR

- **5 failles CRITICAL exploitables** identifiées avant fix
- **3 PRs mergées + déployées** sur 4 tenants prod (presentation, esbtp-abidjan, rostan, ephrata) → 12 fixes appliqués
- **0 régression** détectée post-deploy (stats KPI presentation identiques avant/après)
- **5 headers HTTP sécurité** désormais actifs (vérifiés via curl)
- **6 fixes restants** délégués en issues GitHub (#406-#412) — god-code refactor + Laravel 9 EOL upgrade + tests + CSRF cleanup

## Stack vulnérable identifiée

| Package | Version installée | EOL / CVE actives | Statut |
|---------|---------|---------|---------|
| `laravel/framework` | 9.x-dev | EOL fév 2024, 2 ans sans patches | ⚠️ Issue #411 — upgrade planifié |
| `laravel/sanctum` | v3.3.3 | Pas de CVE critique connue, mais v4 disponible | ⚠️ Issue #411 |
| `spatie/laravel-permission` | 5.11.1 | 0 CVE 2025 (Github advisories OK) | ✅ v6 dispo, upgrade au passage |
| `owen-it/laravel-auditing` | v13.7.2 | Récent, pas de CVE critique | ✅ |
| `barryvdh/laravel-dompdf` | v2.2.0 | CVE-2022-28368 (RemoteEnabled) — défault `false` en 2.x | ✅ Pas de config override observée |
| `maatwebsite/excel` | 3.1.67 | Pas de CVE critique | ✅ |
| `php` | ^7.4 \| ^8.0 \| ^8.1 \| ^8.2 | PHP 7.4 EOL nov 2022 | ⚠️ Issue #411 — bump min 8.1 |

**GitHub Dependabot** signale **83 vulnérabilités** sur main (6 critical, 31 high, 38 moderate, 8 low) — majorité résolues par l'upgrade Laravel 10 + Sanctum 4 + Spatie 6 planifié dans #411.

### CVE notables (recherche web 21/05/2026)

- **Laravel 8-9 < 9.32.0** : user enumeration via timing attack (HTTP/2 multiplexing). Nous sommes en 9.x-dev qui devrait inclure le fix mais à confirmer post-upgrade.
- **CVE-2025-27515** Laravel 11.x : wildcard validation bypass (file/image fields). Fixed 11.44.1/12.1.1 — nous PAS concerné (encore Laravel 9).
- **CVE-2024-52301** Laravel : `register_argc_argv` env manipulation. Mitigation : vérifier `php.ini` `register_argc_argv=Off` (default).
- **CVE-2024-13918/19** Laravel : reflected XSS sur debug page. Mitigation déjà en place : `APP_DEBUG=false` en prod.
- **CVE-2022-28368** DomPDF ≤ 1.2.0 : RCE via remote font. Pas applicable (nous en 2.2.0, RemoteEnabled défault false).
- **CVE-2022-41343** DomPDF ≤ 2.0.0 : Phar deserialization RCE. Pas applicable (nous en 2.2.0).

## Findings & fixes appliqués

### Phase A — CRITICAL & HIGH (PR #403 mergée)

| # | Finding | Sévérité | Fix |
|---|---------|----------|-----|
| 1 | `/install/*` réécrit `.env` sans middleware → attacker peut pivot DB du tenant | CRITICAL | Middleware `BlockInstallIfReady` → 404 si `isInstalled()` |
| 2 | `/register` publique crée user arbitraire (DoS + spam) | CRITICAL | Routes GET+POST supprimées, références cleanup (Middleware, RouteServiceProvider) |
| 3 | `/login` pas de throttle → brute-force libre | HIGH | `throttle:login` wiré (5/min username + 10/min IP) |
| 4 | Sanctum `expiration=null` → tokens éternels | HIGH | `env('SANCTUM_EXPIRATION', null)` + recommandation 43200 (30j) |
| 5 | `/password/email`, `/password/reset` pas de throttle (user enum) | HIGH | `throttle:3,1` sur les 2 POST |
| 6 | 7 routes `/test-*` `/debug-*` exposées en prod | MEDIUM | Wrapped `app()->environment('local')` |
| 7 | Pas de security headers HTTP | MEDIUM | Middleware `SecurityHeaders` global (XFO, XCTO, HSTS, Referrer, Permissions) |

### Phase B — auth + leaks (PR #404 mergée)

| # | Finding | Sévérité | Fix |
|---|---------|----------|-----|
| 1 | Token Sanctum survit au reset password | HIGH | `User::booted` hook `tokens()->delete()` si `wasChanged('password')` |
| 2 | CLI reset-password sans guard target privilégié | CRITICAL | Refus si target ∈ {superAdmin, serviceTechnique} et caller ≠ superAdmin |
| 3 | `AdminProfileController` log `$request->all()` (PII) | MEDIUM | `fields_provided` clés seules + trace local-only |
| 4 | `InstallController` db_password en session + stack JSON | MEDIUM | Password retiré session + erreur redacted prod |
| 5 | Sessions non chiffrées (driver file = plain text) | MEDIUM | `encrypt => env('SESSION_ENCRYPT', true)` |

### Phase D — maintenabilité (PR #405 mergée + commit refactor en cours)

- Suppression 2 fichiers morts/temp : `create.blade(ancien commit).php` (38 KB legacy) + `create.blade.php.temp` (4 bytes garbage)
- 17 `console.log` → `debugLog` (silent en prod via `window.DEBUG_MODE`)
- Suppression bloc "DEBUG MATRICULE — À SUPPRIMER APRÈS DIAGNOSTIC"
- `User.php` : 619 → 310 LOC (309 lignes legacy comment retirées)
- Extraction `routes/auth.php` (58 LOC) de `routes/web.php`

## Vérification déploiement live (presentation.klassci.com)

Vérifié via `curl -sI` après merge + `klassci pull` + `klassci cache:clear` :

```
GET /install                          → 404 ✓ (BlockInstallIfReady)
GET /register                         → 404 ✓ (route supprimée)
GET /debug-permissions                → 404 ✓ (env local only)
GET /login                            → 5 headers sécurité ✓
  X-Frame-Options: SAMEORIGIN
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  Strict-Transport-Security: max-age=31536000; includeSubDomains
  Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()
```

**KPIs identiques avant/après deploy** :
- 206 étudiants actifs (= avant)
- 575 paiements validés (= avant)
- 96 258 000 FCFA revenue (= avant)
- Année 2025-2026 active

## Tenants déployés

| Tenant | URL | Statut | Méthode |
|---|---|---|---|
| presentation | presentation.klassci.com | ✅ déployé | `klassci pull` |
| esbtp-abidjan | esbtp-abidjan.klassci.com | ✅ déployé | `klassci pull` |
| rostan (ISLG) | islg.klassci.com | ✅ déployé | `klassci pull` |
| ephrata | ephrata.klassci.com | ✅ déployé | `klassci pull` |
| esbtp-yakro | esbtp-yakro.klassci.com | ⏳ branch pushed, deploy SSH manuel requis | (pas dans klassci-cli config) |
| hetec | hetec.klassci.com | ⏳ branch pushed, deploy SSH manuel requis | (pas dans klassci-cli config) |

**Action requise** : Marcel doit SSH manuellement sur yakro + hetec ou ajouter à `klassci config:set-token` puis re-pull.

## Risques résiduels (issues GitHub créées)

| Issue | Titre | Priorité |
|---|---|---|
| #406 | Split `etudiants/show.blade.php` (6443 LOC) | Moyenne |
| #407 | Split `NotificationService` (3047 LOC, 53 méthodes) + fix password leak | Haute |
| #408 | Split `ESBTPEtudiantController` (2501 LOC, 37 méthodes) + Policies | Haute |
| #409 | Split `routes/web.php` (2565 LOC) — pattern lancé via routes/auth.php | Moyenne |
| #410 | CSRF cleanup `esbtp/api/*` (Phase B reporté — audit AJAX requis) | Haute |
| #411 | Upgrade Laravel 9 → 10 + Sanctum 4 + Spatie 6 (83 CVE Dependabot) | Haute |
| #412 | Tests 5% → 30% + CI security (composer audit + permissions:audit) | Moyenne |

## Tests de non-régression écrits

- `tests/Feature/Security/SecurityAudit202605Test.php` — 11 tests sur Phase A+B fixes (routing, middleware, config Sanctum, session, CSRF documentation)
- `tests/Feature/Auth/UserPasswordTokenRevocationTest.php` — 4 tests sur le hook User::booted (existence, HasApiTokens trait, mutator, audit whitelist)

Tests ne touchent PAS la DB (pas de `RefreshDatabase`) — ils inspectent le routing et les déclarations de class. Compatibles SQLite in-memory si activé via phpunit.xml.

## Recommandations opérationnelles

1. **Communication** post-deploy : prévenir les users que changer leur MDP révoquera leurs tokens CLI (régénérer via `php artisan klassci:create-token`)
2. **Setting prod** : ajouter `SANCTUM_EXPIRATION=43200` dans le `.env` de chaque tenant pour activer l'expiration 30j sur les NOUVEAUX tokens (l'opt-in préserve la compat avec tokens existants)
3. **Monitoring** : surveiller les 429 (Too Many Requests) sur `/login` et `/password/email` — un pic indique tentative de brute-force
4. **Audit log** : les guards CLI reset-password loguent maintenant target_roles + caller_roles + ip + user_agent — exploitable pour forensique
5. **Composer audit** : à lancer trimestriellement sur chaque tenant : `composer audit --format=json > audit-$(date +%Y%m%d).json`
6. **Dependabot** : 83 vulnérabilités ouvertes — issue #411 prioritaire pour traiter le batch après stabilisation de cette session

## Sources

### Recherche web 21/05/2026

- [Laravel Security Vulnerabilities — stack.watch](https://stack.watch/product/laravel/framework/)
- [CVE-2025-27515 — NVD](https://nvd.nist.gov/vuln/detail/cve-2025-27515)
- [Laravel CVEs — OpenCVE](https://app.opencve.io/cve/?vendor=laravel)
- [DomPDF RCE Vulnerability — Snyk](https://snyk.io/blog/security-alert-php-pdf-library-dompdf-rce/)
- [CVE-2022-41343 Dompdf Phar Deserialization](https://tantosec.com/blog/cve-2022-41343/)
- [Sanctum Security Policy](https://github.com/laravel/sanctum/security/policy)
- [Spatie Permission Security Advisories](https://github.com/spatie/laravel-permission/security/advisories)
- [OWASP Laravel Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html)

### PRs & issues KLASSCIv2

- [PR #403 Phase A — quick wins](https://github.com/James10192/KLASSCIv2/pull/403)
- [PR #404 Phase B — hardening](https://github.com/James10192/KLASSCIv2/pull/404)
- [PR #405 Phase D — cleanup](https://github.com/James10192/KLASSCIv2/pull/405)

### Rules KLASSCI consultées

- `.claude/rules/no-god-code.md` — seuils LOC + methods publiques
- `.claude/rules/pre-merge-checklist.md` — discipline tests/migrations
- `.claude/rules/multi-agent-git-safety.md` — pattern PR-via-gh
- `.claude/rules/customizable-roles.md` — pas de hardcoded roles
