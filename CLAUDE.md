# KLASSCI — SaaS Multi-Instance Laravel

> Historique détaillé → [CLAUDE_ARCHIVE.md](CLAUDE_ARCHIVE.md)

---

## ⚠️ RÈGLES IA

- **Migrations** → TOUJOURS utiliser `php artisan make:migration nom_migration` pour créer les fichiers. Ne JAMAIS créer les fichiers de migration manuellement avec Write/cat/echo. La commande artisan génère le bon timestamp et le bon format.
- **Docs API REST** → toujours dans `docs/api/NOM_API.md`
- Nouvelle API → créer le fichier. API modifiée → mettre à jour + section "Historique" + marquer breaking changes.

---

## Architecture

**Type** : SaaS Multi-Instance Laravel 12.x
**Apps** :
- `adminKlassci` : Admin SaaS central (Filament v3, DB `klassci_master`)
- `KLASSCI` : App métier par établissement (repo `KLASSCIv2`, DB isolée par instance)

**Instances actives** :
- `esbtp-abidjan` — offre **Élite**, > 2000 inscriptions
- `esbtp-yakro` — offre **Élite**, > 2000 inscriptions
- `ephrata` — offre **Partenaire**
- `hetec`, `rostan` — phase test (visent l'offre Élite)
- `presentation` — instance démo (Free, test)

Chaque instance a sa propre branche Git du même nom (snapshot de `presentation` synchronisé périodiquement). Voir [.claude/rules/tenant-branches.md](.claude/rules/tenant-branches.md).

**Stack** : Laravel 12.x · MySQL 8.x · Blade + Alpine.js + Chart.js + DataTables · DomPDF · Sanctum · Gemini 2.0 Flash

---

## Tables principales (DB instance)

| Domaine | Tables |
|---------|--------|
| Académique | `esbtp_classes`, `esbtp_matieres`, `esbtp_planifications_academiques`, `esbtp_emploi_temps`, `esbtp_seance_cours` |
| Étudiants | `esbtp_etudiants`, `esbtp_inscriptions`, `esbtp_paiements` |
| Notes | `esbtp_evaluations`, `esbtp_notes`, `esbtp_resultats`, `esbtp_bulletins` |
| Présences | `esbtp_attendances`, `esbtp_teacher_attendances` |
| Frais | `esbtp_frais_categories`, `esbtp_frais_variants`, `esbtp_frais_subscriptions` |

---

## Design System

**Classes CSS** : `dashboard-acasi`, `main-card`, `stat-card`, `btn-acasi primary/secondary/warning`, `status-badge-success/danger/warning`, `table-modern`

**Couleurs** :
```css
--primary: #0453cb     /* Gradient → #5e91de */
--success: #10b981
--text-primary: #1e293b
--text-secondary: #64748b
```
**JAMAIS** : purple `#7c3aed`, amber `#f59e0b`, rouge `#ef4444`

**Spacing** : 8px grid — `--space-sm: 0.5rem`, `--space-md: 1rem`, `--space-lg: 1.5rem`

---

## Permissions & Rôles

**Source de vérité** : `config/permissions.php` (registry centralisé). Lecture via `App\Services\PermissionRegistry`. Voir [.claude/rules/permissions.md](.claude/rules/permissions.md).

**Rôles UI** : `superAdmin`, `secretaire`, `comptable`, `caissier`, `coordinateur`, `enseignant`, `etudiant` (+ `serviceTechnique` masqué).
Le rôle `parent` a été supprimé : les parents utilisent le compte de leur enfant étudiant.

**Convention canonique** : `domaine.action[.qualifier]` snake_case (ex: `students.view`, `notes.view_own`, `comptabilite.dashboard.view`). Les noms legacy (`view_students`, `view cycles`...) sont conservés comme aliases pour rétrocompat.

**Audit** : `php artisan permissions:audit` → détecte permissions cassées, hors-registry, aliases utilisés, orphelines en DB.

**Déploiement** : `php bin/deploy/fix_permissions.php` synchronise rôles/permissions à partir du registry.

---

## TODO Actif

### 🔴 Haute priorité
**Blocage classes pleines** (`/esbtp/inscriptions/create`) :
- Afficher "Places disponibles: X / Y" avec seuils Vert/Jaune/Rouge
- Bloquer submit si `available_places <= 0`
- Backend prêt : `GET /esbtp/classes/{id}/available-places` + `ClasseManagementService::getAvailablePlaces()`

### 🟡 Moyenne
- Refactoring controllers : `ESBTPBulletinController` (6852 lignes), `ESBTPComptabiliteController` (~2950 lignes), `ESBTPInscriptionController` (3275 lignes) → extraire services
- Chatbot : intents `get_etudiants`, `get_classes` + contexte conversationnel

### 🟢 Backlog
- CM/TD/TP dans planning général (tables ont déjà `volume_horaire_cm/td/tp`)

---

## Commandes utiles

```bash
# Maintenance
php artisan config:clear && cache:clear && view:clear
php artisan permission:cache-reset

# SaaS Master
php artisan tenant:provision --code=xxx --name="..." --plan=pro
php artisan tenant:deploy --all
php artisan tenant:health-check --all
```

---

## Config essentielle

```env
# Mail
MAIL_MAILER=smtp  MAIL_HOST=mail.klassci.com  MAIL_PORT=465  MAIL_ENCRYPTION=ssl

# IA
GEMINI_API_KEY=...  GEMINI_MODEL=gemini-2.0-flash-exp

# Master API
MASTER_API_URL=http://localhost:8001/api  TENANT_CODE=presentation
```

---

*Voir [CLAUDE_ARCHIVE.md](CLAUDE_ARCHIVE.md) pour l'historique Oct/Nov 2025*
