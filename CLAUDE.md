# KLASSCI — SaaS Multi-Tenant Laravel

> Historique détaillé → [CLAUDE_ARCHIVE.md](CLAUDE_ARCHIVE.md)

---

## ⚠️ RÈGLES IA

- **Docs API REST** → toujours dans `docs/api/NOM_API.md`
- Nouvelle API → créer le fichier. API modifiée → mettre à jour + section "Historique" + marquer breaking changes.

---

## Architecture

**Type** : SaaS Multi-Tenant Laravel 12.x
**Apps** :
- `klassci-master` : Admin SaaS central (Filament v3, DB `klassci_master`)
- `KLASSCIv2` : App métier par établissement (DB isolée par tenant)

**Tenants actifs** : `esbtp-abidjan` (Pro, 3000 inscriptions), `esbtp-yakro` (Essentiel, 700), `presentation` (Free, test)

**Stack** : Laravel 12.x · MySQL 8.x · Blade + Alpine.js + Chart.js + DataTables · DomPDF · Sanctum · Gemini 2.0 Flash

---

## Tables principales (DB tenant)

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

**Rôles** : `superAdmin`, `secretaire`, `enseignant`, `etudiant`, `comptable`, `coordinateur`

**Étudiant** (11 permissions `view_own_*`) : grades, exams, profile, timetable, attendances, bulletin, notes, evaluations, schedule, paiements, messages

**Comptable** : `comptabilite.access`, `comptabilite.dashboard.view`, `comptabilite.relances.send`, `comptabilite.reports.export` + paiements.* + frais.*

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
