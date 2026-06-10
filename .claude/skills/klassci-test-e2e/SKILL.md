---
name: klassci-test-e2e
description: Rejouer un test end-to-end KLASSCI supérieur sur un tenant réel, avec priorité aux flux BTS tronc commun, diagnostics klassci-cli, mutations API CLI, seed académique contrôlée et vérification navigateur. À utiliser pour "continue le test e2e", "valide de bout en bout", "teste sur presentation", "BTS TC end-to-end".
---

# KLASSCI Test E2E

Méthode courte et reproductible pour valider un flux réel sur tenant, sans s'arrêter au code ou aux tests unitaires.

## Quand utiliser ce skill

- L'utilisateur demande une validation de bout en bout sur un tenant
- Il faut vérifier UI + données + endpoints + mutations réelles
- Le chantier touche BTS tronc commun, résultats, bulletins, inscriptions, orientation

## Principes

1. Toujours partir d'un cas réel, tenant + année + étudiant + inscription + classe.
2. Prouver chaque étape par au moins deux sources : diagnostic CLI/API et écran navigateur.
3. Ne pas conclure "c'est bon" sans données métiers réelles.
4. En cas d'outil `klassci-cli` cassé sur un POST, basculer vers l'endpoint `api/cli` signé par token tenant.
5. Quand le moteur et l'UI divergent, corriger d'abord la source de vérité puis la projection.
6. **Tester le code DÉPLOYÉ** : l'e2e valide le code en prod sur le tenant. Si tu viens de merger une feature,
   **déploie d'abord** (`klassci pull <tenant> && klassci cache:clear <tenant>`) sinon tu testes l'ancien code.

## Deux CLI distincts (ne pas confondre)

| Outil | Quoi | Commandes |
|---|---|---|
| `klassci` (binaire sur PATH) | Ops tenant + KPIs | `pull`, `cache:clear`, `migrate`, `permissions:fix`, `stats`, `students:list`, `students:show <id>`, `classes:list`, `inscriptions:list`, `payments:list`, `config:list` |
| `klassci-cli.ps1` (repo, à la racine) | **Diagnostics BTS-TC / résultats riches** | `bts-tc:student-journey`, `bts-tc:diagnose`, `bts-tc:legacy-audit`, `bts-tc:results-consistency`, `bts-tc:orient`, `bts-tc:seed-academic-sample`, `resultats:diagnose`, `resultats:bulletin-consistency-diagnose`, `resultats:bts-annual-snapshot` |

`powershell -ExecutionPolicy Bypass -File ./klassci-cli.ps1 <cmd>` — chaque sous-commande sans arg affiche son `Usage:`.
Le binaire `klassci` n'a **pas** de passthrough artisan : une commande custom serveur (ex: backfill) se lance
via le **terminal cPanel** (pas de SSH:22 vers LWS depuis la machine dev), ou via un endpoint `/api/cli/*` dédié.

## ⚡ Chemin rapide « le bulletin annuel prend-il bien le S1 du Tronc Commun ? » (validé Plan C 2026-06-10)

Pour valider en ~3 commandes que la chaîne TC→spécialité fonctionne sur le code déployé, **sans construire
un cas from scratch** : utilise un étudiant déjà orienté en **modèle phases** (`source_model: phase_based`,
le cas qui était cassé).

```bash
# 1. Trouver/confirmer un étudiant orienté phases (cas de réf presentation : 831, TC=classe 98 / spé=99, année 1)
powershell -ExecutionPolicy Bypass -File ./klassci-cli.ps1 bts-tc:student-journey presentation 831 1
#    → vérifier source_model=phase_based + timeline tronc_commun(S1, classe TC) → specialisation(S2, classe spé)

# 2. Diagnostic résultats ANNUEL (exécute le code déployé)
powershell -ExecutionPolicy Bypass -File ./klassci-cli.ps1 resultats:diagnose presentation 831 99 1 annuel 1
#    ✅ ATTENDU : class_map.semestre1_classe_id = la classe TC (98, pas la spé 99)
#              + semestre1.moyenne lue depuis la classe TC, semestre2 depuis la spé
#              + annual_state = "annual_complete" + annual_weighted = agrégat des DEUX semestres
#    ❌ BUG (pré-Plan C) : S1 perdu, annuel incomplet ou ne reflétant que la spé

# 3. Cohérence fiche ↔ bulletin (snapshot vs bulletin officiel)
powershell -ExecutionPolicy Bypass -File ./klassci-cli.ps1 resultats:bulletin-consistency-diagnose presentation 831 99 1 annuel
#    → has_divergence=false attendu. NB : si official_bulletin_exists=false (pas de bulletin persisté),
#      la divergence ne peut pas se mesurer → la preuve vient de l'étape 2 (class_map + annual_complete).
```

Si aucun étudiant orienté phases n'existe (`bts-tc:legacy-audit` renvoie items vides), construire le cas :
`bts-tc:orient <inscription_id> <target_classe_id>` puis `bts-tc:seed-academic-sample <inscription_id> <noteS1> <noteS2>`
(écrit sur une inscription de test, **jamais** de `migrate:fresh`/wipe).

## Backfill des bulletins annuels existants (post-correctif TC)

Quand un correctif change le calcul du bulletin (ex: Plan C), les bulletins **déjà persistés** gardent
l'ancienne valeur jusqu'à régénération. La commande dédiée est **idempotente** et **dry-run par défaut** :

```bash
# DRY-RUN d'abord (read-only, recompute en transaction+rollback) — sur le serveur tenant via cPanel :
!  cd ~/public_html/presentation && php artisan bts:tc-bulletins-backfill presentation --dry-run --annee=<id>
# Lire le compte de bulletins divergents. Si OK → run réel (sans --dry-run), backup mysqldump AVANT.
```

**Ordre tenants** (moins→plus critique) : `presentation` → `hetec` → `rostan` → `ephrata` → `esbtp-yakro`
→ `esbtp-abidjan`. **JAMAIS** prod Élite direct sans dry-run validé. Runbook : `docs/runbooks/bts-tc-bulletins-backfill.md`.

## Pré-requis machine (tests + diagnostics locaux)

- MySQL local (XAMPP) doit tourner pour les diagnostics/tests qui tapent une DB locale — voir rule
  `klassci-local-test-suite.md` (démarrage `mysqld`, DB `klassci_testing`, lenteur RefreshDatabase, pièges seed).
- `klassci_local` peut être **vide** de données métier → un dry-run/diagnostic local n'y trouvera rien ;
  les diagnostics réels se font contre le tenant (`presentation`) via le CLI signé par token.

## Boucle E2E standard

### 1. Cadre du cas réel

Identifier :

- tenant, souvent `presentation`
- `annee_universitaire_id`
- `etudiant_id`
- `inscription_id`
- `classe_source_id`
- `classe_cible_id` si orientation

Commandes utiles :

```powershell
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 bts-tc:student-journey presentation 831 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 bts-tc:diagnose presentation 831
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 99 1 annuel 1
```

### 2. Vérifier le parcours BTS TC

Valider :

- phase active
- timeline `tronc_commun -> specialisation`
- mapping de classes S1/S2
- cohérence année / niveau / filière

Si la sortie n'existe pas encore :

- créer la cible autorisée
- orienter l'inscription

Privilégier `klassci-cli` si le wrapper fonctionne. Sinon appeler `api/cli` directement avec le token tenant.

### 3. Vérifier la donnée académique réelle

Sans notes ni bulletins, le test académique n'est pas clos.

Checklist minimale :

- au moins une matière et une évaluation en S1 sur la classe TC
- au moins une matière et une évaluation en S2 sur la classe de spécialisation
- au moins une note par semestre
- bulletins S1 et S2 présents ou recalculables

Si besoin, utiliser un seed contrôlé côté API CLI sur une inscription de test, jamais un `migrate:fresh` ni un wipe.

### 4. Vérifier les résultats et l'annualisation

À prouver :

- `semestre1` lit la classe TC
- `semestre2` lit la classe de spécialisation
- `annuel` agrège S1 et S2 avec les poids configurés

Commandes utiles :

```powershell
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 98 1 semestre1 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 99 1 semestre2 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:diagnose presentation 831 99 1 annuel 1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose presentation 831 98 1 semestre1
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose presentation 831 99 1 semestre2
powershell -ExecutionPolicy Bypass -File .\klassci-cli.ps1 resultats:bulletin-consistency-diagnose presentation 831 99 1 annuel
```

### 5. Vérifier l'UI réelle

Utiliser `npx dev-browser`.

**⚠️ Bypass LWS DDoS Protection (tenants prod hébergés chez LWS)**

Les tenants prod (esbtp-yakro, esbtp-abidjan, ephrata, rostan) sont derrière LWS DDoS Protection qui détecte les navigateurs headless / fingerprint Playwright et retourne « Vérification échouée, accès non autorisé ». Pour passer le filtre :

1. **NE PAS utiliser `--headless`** (détection immédiate).
2. **Spoof le User-Agent** vers un Chrome standard via `page.setExtraHTTPHeaders` AVANT le `goto`.
3. **Utiliser `--browser <nom>`** pour persister la session (cookies/login).

Recette canonique inline (utilise heredoc, pas de fichier `/tmp/*.js`) :

```bash
npx dev-browser --browser test-yakro --timeout 90 <<'EOF' 2>&1 | tail -15
const page = await browser.getPage("main");
await page.setExtraHTTPHeaders({
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
});
await page.goto("https://esbtp-yakro.klassci.com/login", { waitUntil: "domcontentloaded", timeout: 25000 });
await page.fill('input[placeholder="Votre identifiant"]', "modestekouakou@esbtp-yakro.klassci.com");
await page.fill('input[placeholder="Votre mot de passe"]', "modestekouakou2025");
await page.click('button[type="submit"]');
await new Promise(r => setTimeout(r, 4000));
console.log("Logged in:", page.url());

// Navigation cible avec cache-bust pour bypass Varnish edge cache
await page.goto("https://esbtp-yakro.klassci.com/esbtp/resultats/etudiant/1287?periode=semestre2&classe_id=52&annee_universitaire_id=6&_t=" + Date.now(), { waitUntil: "domcontentloaded", timeout: 25000 });
await new Promise(r => setTimeout(r, 3000));
const path = await saveScreenshot(await page.screenshot({ fullPage: true }), "etudiant-1287-s2.png");
console.log("Screenshot:", path);
EOF
```

**Logins tenants prod (testés)** :

| Tenant | Email | Password |
|---|---|---|
| esbtp-yakro | `modestekouakou@esbtp-yakro.klassci.com` | `modestekouakou2025` |

**Capturer + lire le screenshot** : `await saveScreenshot(await page.screenshot(), "nom.png")` retourne le path. Lis ensuite via Read tool sur `C:\Users\PAVILION\.dev-browser\tmp\nom.png`.

**Si le PDF reste vide / matières manquantes** : croiser avec `/api/cli/logs?lines=100&search=YOUR_TAG` pour confirmer si le code patché s'exécute. Les `Log::info` n'apparaissent que si CLI logs sans filtre level — préférer `Log::error('DEBUG_TAG …')` pour debug rapide.

À contrôler au minimum :

- `etudiants.show`
- `inscriptions.show`
- `etudiants.index`
- `inscriptions.index`
- `resultats.etudiant` si concerné

Points BTS TC :

- badge de phase visible et lisible
- bloc `Parcours BTS` présent
- historique S1/S2 correct
- moyenne annuelle affichée cohérente avec le diagnostic
- aucun fallback visuel trompeur

### 6. Déclarer le statut final

Ne dire "validé de bout en bout" que si :

- mutation métier réelle exécutée
- diagnostics BTS et résultats alignés
- UI alignée avec la donnée
- annualisation prouvée sur vraies données académiques

Sinon, conclure explicitement ce qui manque.

## Pattern de sortie

Toujours rendre :

```markdown
## Statut

Validé partiellement | Validé de bout en bout | Bloqué

## Preuves

- Cas réel : étudiant / inscription / classes / année
- Diagnostics : commandes + points saillants
- UI : pages vérifiées + écarts restants

## Gaps

- ce qui manque encore pour conclure
```

## Notes BTS TC

- Une inscription annuelle unique peut changer de phase sans changer d'identité.
- Le diagnostic n'est pas une preuve suffisante si aucune note réelle n'existe.
- Le bon ordre de preuve est : orientation réelle, seed ou données réelles, diagnostic semestre 1, diagnostic semestre 2, diagnostic annuel, vérification UI.
