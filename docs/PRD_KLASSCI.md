# PRD KLASSCI - Product Requirements Document

**Version**: 1.0
**Date**: 17 décembre 2024
**Projet**: KLASSCI SaaS Multi-Tenant
**Stack**: Laravel 9.x/10.x | PHP 8.x | MySQL 8.x

---

## 📋 Questionnaire PRD - KLASSCI

Réponses aux 39 questions essentielles pour documenter l'architecture, le stack technique, et les règles de développement de KLASSCI.

---

## Phase 1: Vision Produit

### 1.1 Contexte Business

#### Q1: Quel est le nom du produit/projet ?

**R**: KLASSCI - Système de Gestion d'École SaaS Multi-Tenant

---

#### Q2: Quelle est la proposition de valeur unique (en 1 phrase) ?

**R**: Plateforme SaaS complète pour la gestion académique, administrative et comptable des établissements d'enseignement technique et professionnel en Afrique.

---

#### Q3: Quel problème métier résout-il ?

**R**: Élimination des processus manuels chronophages (registres papier, calculs de notes, suivi paiements) et centralisation de toutes les opérations d'un établissement scolaire (inscriptions, notes, bulletins, paiements, emplois du temps, absences) dans une seule plateforme accessible en ligne.

**Problèmes spécifiques résolus** :
- **Gestion inscriptions** : Élimination des formulaires papier et des doublons étudiants
- **Suivi paiements** : Traçabilité automatique des paiements de scolarité avec reliquats
- **Génération bulletins** : Automatisation complète (calcul moyennes, génération PDF, envoi email)
- **Gestion enseignants** : Suivi heures effectuées vs prévues, émargements numériques
- **Communication** : Notifications multi-canal (email, SMS, WhatsApp, app)

---

#### Q4: Quelle est l'industrie/domaine cible ?

**R**: **Éducation** - Établissements d'Enseignement Technique et Professionnel (ESBTP, écoles d'ingénieurs, BTS, etc.)

**Géographie cible** : Afrique francophone (Côte d'Ivoire prioritaire)

---

#### Q5: Le projet est-il :

- [x] **SaaS B2B**
- [ ] SaaS B2C
- [ ] Produit interne
- [ ] Open source
- [ ] Autre

**Précision** : SaaS Multi-Tenant B2B avec 3 plans tarifaires (Free, Essentiel, Pro) vendus aux établissements scolaires.

---

### 1.2 Personas Utilisateurs

#### Q6: Qui sont les utilisateurs finaux ? (minimum 2, maximum 4)

**Persona 1** :
- **Nom** : Super Admin SaaS
- **Rôle** : Administrateur de la plateforme master KLASSCI
- **Besoin principal** : Provisionner nouveaux tenants, monitoring santé système, gestion abonnements
- **Point de douleur** : Gestion manuelle des déploiements multi-tenants

**Persona 2** :
- **Nom** : Directeur d'Établissement / Admin Établissement
- **Rôle** : Administrateur d'un tenant KLASSCI (une école)
- **Besoin principal** : Superviser toutes les opérations (inscriptions, paiements, notes, enseignants), accès KPIs et rapports
- **Point de douleur** : Dispersion des données dans plusieurs outils (Excel, emails, registres papier)

**Persona 3** :
- **Nom** : Secrétaire Académique / Coordinateur
- **Rôle** : Gestionnaire quotidien des opérations administratives
- **Besoin principal** : Inscrire étudiants, valider paiements, générer bulletins, gérer emplois du temps
- **Point de douleur** : Processus répétitifs manuels, saisies multiples des mêmes données

**Persona 4** :
- **Nom** : Enseignant
- **Rôle** : Professeur d'une ou plusieurs matières
- **Besoin principal** : Saisir notes évaluations, marquer présences, consulter emploi du temps, ém

arger séances
- **Point de douleur** : Registres de notes papier sujets à erreurs, suivi difficile des heures effectuées

**Persona 5** (bonus) :
- **Nom** : Étudiant
- **Rôle** : Apprenant inscrit dans l'établissement
- **Besoin principal** : Consulter notes, bulletins, paiements, emploi du temps, absences
- **Point de douleur** : Manque de visibilité sur ses résultats et paiements effectués

**Persona 6** (bonus) :
- **Nom** : Parent
- **Rôle** : Tuteur légal d'un ou plusieurs étudiants
- **Besoin principal** : Suivre progression académique et financière de son enfant
- **Point de douleur** : Communication difficile avec l'établissement

---

### 1.3 Architecture & Stack Technique

#### Q7: Type d'architecture ?

- [ ] Monorepo (Turborepo, Nx, etc.)
- [ ] Monolithe
- [ ] Microservices
- [ ] Serverless
- [x] **Autre** : **SaaS Multi-Tenant avec Application Master + Tenants**

**Détails** :
```
klassci-master (Master App)
├── Gestion tenants (provisioning, deployments, health checks)
├── Backups automatisés
├── Monitoring multi-tenant
└── Dashboard Filament admin

KLASSCIv2 (Tenant App Template)
├── tenant: esbtp-abidjan (DB: esbtp_abidjan)
├── tenant: esbtp-yakro (DB: esbtp_yakro)
├── tenant: presentation (DB: presentation)
└── tenant: test-local (DB: test-local)
```

**Isolation** : Base de données séparée par tenant, domaines/sous-domaines dédiés.

---

#### Q8: Framework frontend principal ?

- [ ] Next.js
- [ ] React (Vite)
- [ ] Vue.js
- [ ] Svelte
- [ ] Angular
- [x] **Autre** : **Blade Templates** (Laravel)

**Détails** :
- **Blade** : Moteur de templates Laravel (syntaxe `@foreach`, `@if`, `@include`)
- **Alpine.js** : Framework JavaScript réactif léger (alternative à React pour interactivité)
- **Chart.js** : Graphiques statistiques
- **DataTables** : Tableaux interactifs avec tri, recherche, pagination

---

#### Q9: Framework backend/API ?

- [ ] Next.js API Routes
- [ ] Express.js
- [ ] NestJS
- [ ] FastAPI (Python)
- [ ] Spring Boot (Java)
- [x] **Autre** : **Laravel 9.x / 10.x**

**Détails** :
- **Version** : Laravel 9.x / 10.x (compatible PHP 7.4 à 8.2)
- **Pattern** : MVC (Model-View-Controller)
- **API** : Laravel Sanctum pour authentification API tokens (mobile à venir)
- **Routing** : Eloquent ORM + Service Layer Architecture

---

#### Q10: Base de données ?

- [ ] PostgreSQL
- [x] **MySQL 8.x**
- [ ] MongoDB
- [ ] Supabase
- [ ] Firebase
- [ ] PlanetScale
- [ ] Autre

**Détails** :
- **Version** : MySQL 8.x (features: JSON columns, window functions, CTEs)
- **Connexion** : Tenant-specific databases (isolation stricte)
- **Migrations** : Laravel migrations (`php artisan migrate`)

---

#### Q11: ORM/Query Builder ?

- [ ] Prisma
- [ ] Drizzle
- [ ] TypeORM
- [ ] Sequelize
- [ ] Mongoose
- [ ] Aucun (SQL brut)
- [x] **Autre** : **Eloquent ORM** (Laravel)

**Détails** :
- **Eloquent ORM** : ORM Laravel (Active Record pattern)
- **Query Builder** : Fluent interface pour requêtes SQL (`DB::table()`)
- **Relations** : `hasMany`, `belongsTo`, `belongsToMany`, `morphMany`, etc.
- **Scopes** : Réutilisation de queries (`->actives()`, `->withTrashed()`)

**Exemple** :
```php
ESBTPEtudiant::where('status', 'actif')
    ->with(['inscriptions', 'classe'])
    ->whereHas('inscriptions', function($q) {
        $q->where('annee_academique', '2024-2025');
    })
    ->paginate(20);
```

---

#### Q12: Authentification ?

- [ ] Supabase Auth
- [ ] NextAuth.js / Auth.js
- [ ] Clerk
- [ ] Firebase Auth
- [ ] Custom JWT
- [ ] Auth0
- [x] **Autre** : **Laravel Breeze + Laravel Sanctum**

**Détails** :
- **Laravel Breeze** : Authentification web (login, register, password reset)
- **Laravel Sanctum** : API tokens pour authentification stateless (mobile app future)
- **Spatie Laravel Permission** : Gestion rôles et permissions granulaires
- **Sessions** : `SessionGuard` (web) + `TokenGuard` (API)

**Rôles** : `superAdmin`, `admin`, `coordinateur`, `enseignant`, `etudiant`, `parent`, `comptable`

---

#### Q13: Styling ?

- [x] **Tailwind CSS** (partiellement)
- [ ] CSS Modules
- [ ] Styled Components
- [ ] Emotion
- [x] **Sass/SCSS** (partiellement)
- [x] **Autre** : **Bootstrap 5.3 + Custom CSS**

**Détails** :
- **Framework principal** : **Bootstrap 5.3** (grid system, components, utilities)
- **Design System** : ACASI 2025 (variables CSS custom, 8px grid)
- **Tailwind CSS** : Utilisé ponctuellement (Filament admin panel)
- **Custom CSS** : `dashboard-moderne.css`, `dashboard-acasi.css`

**Variables CSS** :
```css
:root {
    --space-sm: 0.5rem;   /* 8px */
    --space-md: 1rem;     /* 16px */
    --space-lg: 1.5rem;   /* 24px */
    --primary: #0453cb;
    --secondary: #5e91de;
    --success: #10b981;
}
```

---

#### Q14: UI Component Library ?

- [ ] shadcn/ui
- [ ] Radix UI
- [ ] Headless UI
- [ ] Material UI
- [ ] Chakra UI
- [ ] Mantine
- [x] **Custom**
- [ ] Aucune
- [ ] Autre

**Détails** :
- **Bootstrap 5.3 Components** : Cards, Modals, Dropdowns, Navbars, Badges, Alerts
- **Custom Components** :
  - `stat-card` (cartes statistiques)
  - `status-badge-success/danger/warning` (badges colorés)
  - `btn-acasi primary/secondary` (boutons design system)
  - `table-modern` (tableaux stylisés)
- **Filament v3.3** : Admin panel master (classe de components pré-faits)

---

#### Q15: State Management ?

- [x] **Vanilla JavaScript + Alpine.js**
- [ ] React Context + useState/useReducer
- [ ] Zustand
- [ ] Redux Toolkit
- [ ] Jotai
- [ ] Recoil
- [ ] MobX
- [ ] Aucun (Server State only)
- [ ] Autre

**Détails** :
- **Alpine.js** : Réactivité légère côté client (`x-data`, `x-model`, `x-show`, `@click`)
- **Server-side rendering** : Blade templates (pas de SPA)
- **State global** : Sessions Laravel + cache Redis (pas de state management JS complexe)

**Exemple Alpine.js** :
```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Contenu</div>
</div>
```

---

#### Q16: Data Fetching ?

- [ ] React Server Components (RSC)
- [ ] TanStack Query (React Query)
- [ ] SWR
- [ ] Apollo Client (GraphQL)
- [ ] tRPC
- [x] **Fetch API + AJAX (jQuery/Axios)**
- [ ] Autre

**Détails** :
- **Pattern principal** : Server-Side Rendering (SSR) avec Blade
- **AJAX** : Fetch API natif + jQuery `$.ajax()` (legacy)
- **Polling** : Refresh automatique 30s pour certaines pages (inscriptions, paiements)
- **Real-time** : Pas de WebSockets actuellement (Laravel Echo prévu future)

**Exemple AJAX** :
```javascript
fetch('/api/classes/' + classeId + '/available-places')
    .then(r => r.json())
    .then(data => {
        document.getElementById('places').textContent = data.available;
    });
```

---

#### Q17: Validation ?

- [ ] Zod
- [ ] Yup
- [ ] Joi
- [ ] class-validator
- [ ] AJV
- [x] **Autre** : **Laravel Validation + Form Requests**

**Détails** :
- **Laravel Validation** : Rules intégrées (`required`, `email`, `unique`, `exists`, `min`, `max`, etc.)
- **Form Request Classes** : Validation centralisée dans `app/Http/Requests/`
- **Custom Validation Rules** : Règles métier spécifiques (ex: validation matricule unique)

**Exemple** :
```php
// app/Http/Requests/StoreInscriptionRequest.php
public function rules()
{
    return [
        'etudiant_id' => 'required|exists:esbtp_etudiants,id',
        'classe_id' => 'required|exists:esbtp_classes,id',
        'montant_inscription' => 'required|numeric|min:0',
        'annee_academique' => 'required|string',
    ];
}
```

---

#### Q18: Testing ?

- [ ] Vitest
- [ ] Jest
- [x] **PHPUnit** (Laravel default)
- [ ] Playwright
- [ ] Cypress
- [x] **Laravel Dusk** (Browser tests - prévu)
- [ ] Autre

**Détails** :
- **PHPUnit** : Tests unitaires et Feature tests Laravel
- **Factories & Seeders** : Génération données test (`database/factories/`)
- **Test Database** : SQLite in-memory pour tests rapides
- **Coverage** : Objectif 80%+ (actuellement ~30%)

**Commandes** :
```bash
php artisan test
php artisan test --filter InscriptionTest
php artisan test --coverage
```

---

#### Q19: Deployment ?

- [ ] Vercel
- [ ] Netlify
- [ ] Railway
- [x] **AWS (EC2, RDS, S3)**
- [ ] Google Cloud
- [ ] Azure
- [x] **DigitalOcean** (VPS)
- [ ] Autre

**Détails** :
- **Production** : AWS EC2 (Ubuntu 20.04) + RDS MySQL + S3 (photos étudiants)
- **Staging** : DigitalOcean VPS (LAMP stack)
- **Local** : Laravel Valet / Homestead / Docker
- **Web Server** : Nginx + PHP-FPM
- **Process Manager** : Supervisor (queue workers)

**Domaines** :
- Master : `admin.klassci.com`
- Tenant : `{tenant}.klassci.com` (ex: `esbtp-abidjan.klassci.com`)

---

#### Q20: CI/CD ?

- [x] **GitHub Actions**
- [ ] GitLab CI
- [ ] CircleCI
- [ ] Jenkins
- [ ] Aucun
- [ ] Autre

**Détails** :
- **GitHub Actions** : Workflows automatisés (tests, lint, deploy)
- **Branches** :
  - `main` : Production stable
  - `staging` : Pré-production
  - `presentation` : Tenant de démonstration
  - `feature/*` : Développement

**Workflow deploy** :
```yaml
name: Deploy to Production
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install --no-dev
      - run: php artisan migrate --force
      - run: ssh user@server 'cd /var/www && git pull'
```

---

#### Q21: Monitoring/Analytics ?

- [x] **Sentry** (errors - prévu)
- [ ] Vercel Analytics
- [ ] PostHog
- [x] **Google Analytics** (trafic web)
- [ ] Mixpanel
- [x] **Laravel Telescope** (dev uniquement)
- [ ] Autre

**Détails** :
- **Laravel Telescope** : Debugger local (requests, queries, jobs, mail)
- **Laravel Log Viewer** : Visualisation logs production
- **Laravel Debugbar** : Debug mode dev (queries N+1, performance)
- **Sentry** : Prévu pour tracking erreurs production

**Logs** : `storage/logs/laravel.log` (rotation quotidienne)

---

#### Q22: Caching ?

- [ ] Upstash Redis
- [ ] Vercel KV
- [x] **Redis** (local + production)
- [x] **In-memory** (array cache)
- [ ] Aucun
- [ ] Autre

**Détails** :
- **Redis** : Cache principal (sessions, queues, broadcast)
- **File Cache** : Fallback si Redis indisponible
- **Cache Tags** : Invalidation sélective (`cache()->tags(['classes'])->flush()`)
- **TTL** : Variable selon type de données (5min à 24h)

**Exemple** :
```php
Cache::remember('classes.actives', 3600, function() {
    return ESBTPClasse::actives()->with('filiere')->get();
});
```

---

### 1.4 Versioning & Package Management

#### Q23: Package Manager ?

- [ ] npm
- [ ] pnpm
- [ ] yarn
- [ ] bun
- [x] **Composer** (PHP)
- [x] **npm** (assets frontend)

**Détails** :
- **Composer** : Gestionnaire dépendances PHP (backend)
- **npm** : Gestionnaire assets frontend (JS, CSS)
- **Version Composer** : 2.x
- **Version npm** : 8.x

**Fichiers** :
- `composer.json` : Dépendances PHP
- `package.json` : Dépendances JS/CSS
- `composer.lock` : Versions exactes PHP
- `package-lock.json` : Versions exactes npm

---

#### Q24: PHP version minimale requise ?

- [x] **PHP 7.4**
- [x] **PHP 8.0**
- [x] **PHP 8.1**
- [x] **PHP 8.2**
- [ ] Autre

**Détails** (selon `composer.json`) :
```json
"require": {
    "php": "^7.4|^8.0|^8.1|^8.2"
}
```

**Version recommandée production** : **PHP 8.2** (meilleure performance)

---

#### Q25: Versions exactes des dépendances critiques

**Framework principal** :
- **Nom** : Laravel Framework
- **Version** : `^9.0|^10.0` (compatible Laravel 9.x et 10.x)

**Database/ORM** :
- **Nom** : Eloquent ORM (inclus dans Laravel)
- **Version** : Même que Laravel (9.x/10.x)

**Auth** :
- **Nom** : Laravel Sanctum
- **Version** : `^3.0`

**Permissions** :
- **Nom** : Spatie Laravel Permission
- **Version** : `^5.0`

**Exports Excel** :
- **Nom** : Maatwebsite Excel
- **Version** : `^3.1`

**Génération PDF** :
- **Nom** : DomPDF + mPDF + Browsershot
- **Version** : `^2.0` (DomPDF), `^8.2` (mPDF), `^5.0` (Browsershot)

**IA Chatbot** :
- **Nom** : Google Gemini PHP Laravel
- **Version** : `^2.0`

**Audit Trail** :
- **Nom** : Owen-it Laravel Auditing
- **Version** : `^13.0`

**Frontend** :
- Bootstrap : `5.3`
- Alpine.js : `3.x`
- Chart.js : `4.x`
- DataTables : `1.13.x`

---

### 1.5 Règles de Développement

#### Q26: Convention de nommage fichiers ?

- [x] **kebab-case** (routes, vues)
- [x] **PascalCase** (Controllers, Models)
- [x] **snake_case** (migrations, database)
- [ ] camelCase

**Détails** :
- **Controllers** : `ESBTPInscriptionController.php` (PascalCase)
- **Models** : `ESBTPEtudiant.php` (PascalCase)
- **Migrations** : `2024_01_15_create_esbtp_etudiants_table.php` (snake_case)
- **Vues Blade** : `esbtp/inscriptions/create.blade.php` (kebab-case)
- **Routes** : `/esbtp/inscriptions/create` (kebab-case)

---

#### Q27: Convention de nommage composants/classes ?

- [x] **PascalCase** (Classes PHP)
- [ ] Autre

**Exemples** :
- Classes : `ESBTPEtudiant`, `InscriptionWorkflowService`
- Méthodes : `calculateMoyennes()`, `sendEmail()` (camelCase)
- Variables : `$etudiantId`, `$moyenneGenerale` (camelCase)

---

#### Q28: Pattern commit messages ?

- [x] **Conventional Commits**
- [ ] Angular Commit Guidelines
- [ ] Custom
- [ ] Aucun standard

**Format** :
```
<type>(<scope>): <description>

[body optionnel]

[footer optionnel]
```

**Types** :
- `feat` : Nouvelle fonctionnalité
- `fix` : Correction bug
- `docs` : Documentation
- `style` : Formatage (pas de changement logique)
- `refactor` : Refactoring
- `test` : Ajout tests
- `chore` : Maintenance

**Exemples** :
```
feat(inscriptions): ajouter détection doublons fuzzy search
fix(bulletins): corriger calcul moyennes coefficients
docs(comptabilite): documenter module obsolète
```

**Footer** :
```
🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

---

#### Q29: Linter ?

- [ ] ESLint (JavaScript)
- [ ] Biome
- [x] **Laravel Pint** (PHP)
- [x] **PHPStan / Larastan** (static analysis - prévu)
- [ ] Aucun

**Détails** :
- **Laravel Pint** : Linter PHP basé sur PHP-CS-Fixer (PSR-12)
- **Commande** : `./vendor/bin/pint`
- **Config** : `pint.json`

---

#### Q30: Formatter ?

- [ ] Prettier
- [ ] Biome
- [x] **Laravel Pint** (auto-format)
- [ ] Aucun

**Détails** :
- **Laravel Pint** : Formatage automatique selon PSR-12
- **Format on save** : Configurable dans IDE (PhpStorm, VS Code)

---

#### Q31: Git hooks (Husky) ?

- [ ] Oui - pre-commit (lint, format, type-check)
- [ ] Oui - commit-msg (conventional commits)
- [ ] Oui - pre-push (tests)
- [x] **Non** (prévu dans future)

**Prévu** :
- `pre-commit` : Laravel Pint auto-format
- `commit-msg` : Validation Conventional Commits
- `pre-push` : PHPUnit tests

---

### 1.6 Sécurité

#### Q32: Variables d'environnement validées au build ?

- [ ] Oui - t3-env
- [ ] Oui - envalid
- [ ] Oui - custom Zod
- [x] **Oui - Laravel .env validation**

**Détails** :
- **Validation** : Check au démarrage Laravel (`.env.example` comme référence)
- **Variables critiques** :
  ```env
  APP_KEY=                    # Encryption key (requis)
  DB_DATABASE=                # Database name (requis)
  MAIL_HOST=                  # SMTP host (requis si emails)
  GEMINI_API_KEY=             # IA chatbot (optionnel)
  MASTER_API_URL=             # Tenant communication (requis)
  ```

---

#### Q33: Rate limiting ?

- [x] **Oui** (Laravel Throttle Middleware)

**Détails** :
- **API endpoints** : 60 req/min par user authentifié, 10 req/min par IP guest
- **Login** : 5 tentatives max par 1 minute
- **Middleware** : `throttle:60,1` (60 req par 1 min)

**Exemple** :
```php
Route::middleware('throttle:api')->group(function() {
    Route::get('/api/etudiants', [API::class, 'index']);
});
```

---

#### Q34: CORS configuration ?

- [x] **Whitelist domaines** (production)
- [x] **Allow all** (dev only)
- [ ] Custom

**Détails** :
- **Config** : `config/cors.php`
- **Allowed Origins** : `*.klassci.com`, `localhost:*`
- **Allowed Methods** : GET, POST, PUT, PATCH, DELETE
- **Allowed Headers** : Authorization, Content-Type, X-Requested-With

---

#### Q35: Gestion secrets en production ?

- [ ] Vercel Env Vars
- [x] **AWS Secrets Manager** (prévu)
- [ ] .env.local (danger !)
- [x] **Server .env** (actuellement)

**Détails** :
- **Actuellement** : `.env` sur serveur (chmod 600, owner www-data)
- **Prévu** : Migration vers AWS Secrets Manager
- **Backup** : Variables critiques stockées dans 1Password (équipe)

---

### 1.7 Architecture Modulaire

#### Q36: Structure projet ?

**Arborescence principale** :

```
/home/levraimd/workspace/KLASSCIv2/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ESBTP/                    # Controllers métier
│   │   │   │   ├── ESBTPInscriptionController.php (3275 lignes)
│   │   │   │   ├── ESBTPBulletinController.php (6852 lignes)
│   │   │   │   ├── ESBTPPaiementController.php (3024 lignes)
│   │   │   │   ├── ESBTPComptabiliteController.php (4150 lignes)
│   │   │   │   └── [20+ autres controllers]
│   │   │   ├── API/                      # API REST
│   │   │   └── Auth/                     # Authentification
│   │   ├── Middleware/
│   │   │   ├── CheckTenantAccess.php
│   │   │   ├── VerifyStudentStatus.php
│   │   │   └── [autres middlewares]
│   │   └── Requests/                     # Form Validation
│   ├── Models/
│   │   ├── ESBTPEtudiant.php             # Modèle étudiant
│   │   ├── ESBTPInscription.php          # Modèle inscription
│   │   ├── ESBTPClasse.php               # Modèle classe
│   │   ├── ESBTPPaiement.php             # Modèle paiement
│   │   └── [60+ autres modèles]
│   ├── Services/                         # Business Logic
│   │   ├── InscriptionWorkflowService.php
│   │   ├── BulletinGenerationService.php (prévu)
│   │   ├── ClasseManagementService.php
│   │   └── [autres services]
│   ├── Exports/                          # Excel exports
│   │   ├── ClassesExport.php
│   │   ├── PaiementsExport.php
│   │   └── [autres exports]
│   └── helpers.php                       # Global helpers
├── resources/
│   ├── views/
│   │   ├── esbtp/                        # Vues métier
│   │   │   ├── inscriptions/
│   │   │   ├── bulletins/
│   │   │   ├── paiements/
│   │   │   ├── classes/
│   │   │   └── [autres modules]
│   │   ├── layouts/
│   │   │   ├── app.blade.php             # Layout principal
│   │   │   └── guest.blade.php           # Layout public
│   │   └── components/                   # Blade components
│   └── css/
│       ├── dashboard-moderne.css         # Design system
│       └── dashboard-acasi.css           # ACASI 2025
├── database/
│   ├── migrations/                       # Migrations SQL
│   ├── seeders/                          # Data seeders
│   └── factories/                        # Test factories
├── routes/
│   ├── web.php                           # Routes web (~2000 lignes)
│   └── api.php                           # Routes API
├── public/
│   ├── css/                              # Compiled CSS
│   ├── js/                               # Compiled JS
│   └── storage/                          # Symlink vers storage
├── storage/
│   ├── app/
│   │   ├── public/
│   │   │   └── photos/                   # Photos étudiants
│   │   └── exports/                      # Excel exports temp
│   └── logs/                             # Application logs
├── docs/                                 # Documentation
│   ├── api/                              # API docs
│   ├── architecture/                     # Architecture docs
│   ├── workflows/                        # Workflows métier
│   ├── deployment/                       # Déploiement
│   └── legacy-scripts/                   # Scripts archivés
├── bin/
│   └── deploy/                           # Scripts déploiement
│       ├── init_storage.php
│       ├── fix_permissions.php
│       └── deploy_settings.php
├── setup.php                             # Orchestrateur init
├── verify.php                            # Vérificateur état
├── CLAUDE.md                             # Instructions LLM
├── composer.json                         # Dépendances PHP
└── package.json                          # Dépendances npm
```

---

#### Q37: Quels dossiers auront un README.md ?

**🔴 Priorité HAUTE (8 fichiers)** :
- [x] `/app/README.md` - Organisation code métier
- [x] `/app/Http/Controllers/README.md` - Controllers overview
- [x] `/app/Http/Controllers/ESBTP/README.md` - Controllers ESBTP détaillés
- [x] `/app/Models/README.md` - Schéma BDD, relations
- [x] `/app/Services/README.md` - Services business logic
- [x] `/docs/README.md` - Index documentation
- [x] `/docs/architecture/SAAS_MULTI_TENANT.md` - Architecture SaaS
- [x] `/docs/deployment/README.md` - Guide déploiement

**🟡 Priorité MOYENNE (12 fichiers)** :
- [x] `/resources/views/README.md`
- [x] `/resources/views/esbtp/README.md`
- [x] `/database/README.md`
- [x] `/database/migrations/README.md`
- [x] `/routes/README.md`
- [x] `/app/Exports/README.md`
- [x] `/app/Http/Middleware/README.md`
- [x] `/bin/deploy/README.md`
- [x] `/docs/api/README.md`
- [x] `/docs/workflows/README.md`
- [x] `/docs/personas/README.md`
- [x] `/docs/deployment/ENVIRONMENT.md`

**🟢 Priorité BASSE (5-10 fichiers)** :
- [x] `/public/README.md`
- [x] `/app/Services/[service-specific]/README.md`
- [x] `/tests/README.md`

**Total** : **25-30 fichiers README.md**

---

#### Q38: Modules métier principaux ?

**Modules fonctionnels KLASSCI** :

1. **Inscriptions** :
   - Gestion étudiants, détection doublons, génération matricules
   - Workflow validation (brouillon → validée → payée)
   - Frais scolarité flexibles par filière/niveau

2. **Bulletins & Évaluations** :
   - Configuration matières par classe
   - Saisie notes évaluations
   - Calcul moyennes automatiques (coefficients)
   - Génération bulletins PDF
   - Édition professeurs/absences

3. **Paiements & Comptabilité** :
   - Gestion frais (catégories, variants, souscriptions)
   - Suivi paiements (validé, en_attente, rejeté)
   - Reliquats années précédentes
   - Exports comptables (Excel, CSV, PDF)

4. **Classes & Emplois du Temps** :
   - Gestion capacités classes
   - Planning général volumes horaires
   - Emplois du temps hebdomadaires
   - Timeline visuelle séances
   - Export PDF emploi du temps

5. **Enseignants** :
   - Assignation matières
   - Suivi heures effectuées vs prévues
   - Émargements séances (QR codes)
   - Taux réalisation planning

6. **Présences** :
   - Marquage présences étudiants
   - Marquage présences enseignants
   - Statistiques absences
   - Justificatifs

7. **Dashboard & Analytics** :
   - KPIs établissement (inscriptions, paiements, classes)
   - Graphiques évolution (Chart.js)
   - Rapports exportables

8. **API LMS** :
   - Endpoints publics (enseignants, classes, matières, séances)
   - Authentification token tenant
   - Visioconférences (Zoom/Google Meet)
   - Soumission notes en ligne

9. **Notifications Multi-Canal** :
   - Email (11 templates)
   - SMS (Orange API)
   - WhatsApp (API Business)
   - Notifications in-app

10. **Chatbot IA** :
    - Google Gemini 2.0 Flash
    - Exploration autonome code source
    - Génération deep links contextuels
    - Intents validés (inscriptions, frais)

---

#### Q39: Documentation existante ?

**Fichiers documentation actuels** :
- [x] `CLAUDE.md` - Instructions LLM (39920 tokens - **À RESTRUCTURER**)
- [x] `CLAUDE_ARCHIVE.md` - Historique développements 2025
- [x] `docs/DOCUMENTATION_GUIDE.md` - Guide méthodologique
- [x] `docs/BEST_PRACTICES.md` - Best practices (**À REMPLACER** : Python → Laravel)
- [x] `docs/COMPTABILITE_MODULE_DOCUMENTATION.md` - Module comptabilité
- [x] `docs/COMPTABILITE_CLEANUP_PLAN.md` - Plan nettoyage comptabilité
- [x] `docs/api/SOCIAL_NETWORK_ARCHITECTURE.md` - Architecture réseau social (future)
- [x] `docs/api/LMS_ENSEIGNANTS.md` - API LMS enseignants
- [x] `docs/legacy-scripts/README.md` - Scripts archivés (9 fichiers)

**À créer** (Phase 5 de ce plan) :
- [ ] `docs/PRD_KLASSCI.md` - **CE FICHIER** (Phase 1 ✅)
- [ ] `docs/research/best-practices-2025.md` - Recherches web (Phase 2)
- [ ] `docs/BEST_PRACTICES.md` - Nouvelle version Laravel (Phase 3)
- [ ] `docs/archives/CLAUDE_ARCHIVE_FULL.md` - Backup CLAUDE.md (Phase 4)
- [ ] `docs/CLAUDE_RESTRUCTURATION_MAP.md` - Mapping extraction (Phase 4)
- [ ] 25-30 `README.md` modulaires (Phase 5)
- [ ] `docs/CODE_AUDIT.md` - Audit zones d'ombre (Phase 6)
- [ ] `docs/DOCUMENTATION_VALIDATION.md` - Validation finale (Phase 7)

---

## ✅ Checklist PRD Complété

- [x] **Q1-Q5** : Contexte Business (5 questions)
- [x] **Q6** : Personas Utilisateurs (6 personas détaillés)
- [x] **Q7-Q22** : Architecture & Stack Technique (16 questions)
- [x] **Q23-Q26** : Versioning & Package Management (4 questions + dépendances)
- [x] **Q27-Q31** : Règles de Développement (5 questions)
- [x] **Q32-Q35** : Sécurité (4 questions)
- [x] **Q36-Q39** : Architecture Modulaire (4 questions)

**Total** : **39 questions répondues**

---

## 📊 Résumé Stack KLASSCI

| Catégorie | Technologie | Version |
|-----------|-------------|---------|
| **Framework Backend** | Laravel | 9.x / 10.x |
| **Langage** | PHP | 7.4 / 8.0 / 8.1 / 8.2 |
| **Base de données** | MySQL | 8.x |
| **ORM** | Eloquent | (Laravel) |
| **Authentification** | Laravel Breeze + Sanctum | ^3.0 |
| **Permissions** | Spatie Laravel Permission | ^5.0 |
| **Frontend Templates** | Blade | (Laravel) |
| **JavaScript** | Alpine.js | 3.x |
| **CSS Framework** | Bootstrap | 5.3 |
| **Graphiques** | Chart.js | 4.x |
| **Tableaux** | DataTables | 1.13.x |
| **Exports Excel** | Maatwebsite Excel | ^3.1 |
| **PDF** | DomPDF + mPDF + Browsershot | ^2.0 / ^8.2 / ^5.0 |
| **IA Chatbot** | Google Gemini PHP Laravel | ^2.0 |
| **Audit Trail** | Owen-it Laravel Auditing | ^13.0 |
| **Cache** | Redis | (local + prod) |
| **Queue** | Redis | (Laravel Queue) |
| **Tests** | PHPUnit | ^9.5 |
| **Linter** | Laravel Pint | ^1.0 |
| **Deployment** | AWS EC2 + RDS + S3 | - |
| **CI/CD** | GitHub Actions | - |

---

## 🎯 Prochaines Étapes

**Phase 1 ✅ TERMINÉE** : PRD_KLASSCI.md créé (764 lignes)

**Phase 2 (suivante)** : Recherches web best practices Laravel 10 / PHP 8.x / MySQL 8 (2025)

**Phase 3** : Remplacer BEST_PRACTICES.md (Python → Laravel)

**Phase 4** : Restructurer CLAUDE.md (39920 tokens → 600-800 lignes)

**Phase 5** : Créer 25-30 README.md modulaires

**Phase 6** : Identifier zones d'ombre (code obsolète)

**Phase 7** : Validation finale + checklist

---

*Document créé : 17 décembre 2024*
*Méthodologie : docs/DOCUMENTATION_GUIDE.md*
*Plan de référence : /home/levraimd/.claude/plans/steady-sprouting-metcalfe.md*

**✅ PRD KLASSCI complet et prêt pour utilisation**
