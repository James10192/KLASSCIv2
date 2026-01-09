# Guide Méthodologique - Documentation Projet Claude Code

**Version**: 1.0
**Date**: 5 décembre 2025
**Objectif**: Guide complet pour créer CLAUDE.md, BEST_PRACTICES.md et README.md modulaires

---

## 📋 Table des Matières

1. [Questionnaire PRD (Product Requirements)](#1-questionnaire-prd)
2. [Template CLAUDE.md](#2-template-claudemd)
3. [Template BEST_PRACTICES.md](#3-template-best_practicesmd)
4. [Guide README.md Modulaires](#4-guide-readmemd-modulaires)
5. [Checklist Versionning](#5-checklist-versionning)
6. [Méthodologie de Création](#6-méthodologie-de-création)

---

## 1. Questionnaire PRD

### Phase 1: Vision Produit

Répondre à ces questions **AVANT** de commencer la documentation :

#### 1.1 Contexte Business

```markdown
Q1: Quel est le nom du produit/projet ?
R: _______________________

Q2: Quelle est la proposition de valeur unique (en 1 phrase) ?
R: _______________________

Q3: Quel problème métier résout-il ?
R: _______________________

Q4: Quelle est l'industrie/domaine cible ?
R: _______________________

Q5: Le projet est-il :
[ ] SaaS B2B
[ ] SaaS B2C
[ ] Produit interne
[ ] Open source
[ ] Autre: _______________________
```

#### 1.2 Personas Utilisateurs

```markdown
Q6: Qui sont les utilisateurs finaux ? (minimum 2, maximum 4)

Persona 1:
- Nom: _______________________
- Rôle: _______________________
- Besoin principal: _______________________
- Point de douleur: _______________________

Persona 2:
- Nom: _______________________
- Rôle: _______________________
- Besoin principal: _______________________
- Point de douleur: _______________________

[Répéter pour Persona 3, 4 si applicable]
```

#### 1.3 Architecture & Stack Technique

```markdown
Q7: Type d'architecture ?
[ ] Monorepo (Turborepo, Nx, etc.)
[ ] Monolithe
[ ] Microservices
[ ] Serverless
[ ] Autre: _______________________

Q8: Framework frontend principal ?
[ ] Next.js
[ ] React (Vite)
[ ] Vue.js
[ ] Svelte
[ ] Angular
[ ] Autre: _______________________

Q9: Framework backend/API ?
[ ] Next.js API Routes
[ ] Express.js
[ ] NestJS
[ ] FastAPI (Python)
[ ] Spring Boot (Java)
[ ] Autre: _______________________

Q10: Base de données ?
[ ] PostgreSQL
[ ] MySQL
[ ] MongoDB
[ ] Supabase
[ ] Firebase
[ ] PlanetScale
[ ] Autre: _______________________

Q11: ORM/Query Builder ?
[ ] Prisma
[ ] Drizzle
[ ] TypeORM
[ ] Sequelize
[ ] Mongoose
[ ] Aucun (SQL brut)
[ ] Autre: _______________________

Q12: Authentification ?
[ ] Supabase Auth
[ ] NextAuth.js / Auth.js
[ ] Clerk
[ ] Firebase Auth
[ ] Custom JWT
[ ] Auth0
[ ] Autre: _______________________

Q13: Styling ?
[ ] Tailwind CSS
[ ] CSS Modules
[ ] Styled Components
[ ] Emotion
[ ] Sass/SCSS
[ ] Autre: _______________________

Q14: UI Component Library ?
[ ] shadcn/ui
[ ] Radix UI
[ ] Headless UI
[ ] Material UI
[ ] Chakra UI
[ ] Mantine
[ ] Custom
[ ] Aucune
[ ] Autre: _______________________

Q15: State Management ?
[ ] React Context + useState/useReducer
[ ] Zustand
[ ] Redux Toolkit
[ ] Jotai
[ ] Recoil
[ ] MobX
[ ] Aucun (Server State only)
[ ] Autre: _______________________

Q16: Data Fetching ?
[ ] React Server Components (RSC)
[ ] TanStack Query (React Query)
[ ] SWR
[ ] Apollo Client (GraphQL)
[ ] tRPC
[ ] Fetch API
[ ] Autre: _______________________

Q17: Validation ?
[ ] Zod
[ ] Yup
[ ] Joi
[ ] class-validator
[ ] AJV
[ ] Autre: _______________________

Q18: Testing ?
[ ] Vitest
[ ] Jest
[ ] Playwright
[ ] Cypress
[ ] Testing Library
[ ] Autre: _______________________

Q19: Deployment ?
[ ] Vercel
[ ] Netlify
[ ] Railway
[ ] AWS (EC2, ECS, Lambda, etc.)
[ ] Google Cloud
[ ] Azure
[ ] DigitalOcean
[ ] Autre: _______________________

Q20: CI/CD ?
[ ] GitHub Actions
[ ] GitLab CI
[ ] CircleCI
[ ] Jenkins
[ ] Aucun
[ ] Autre: _______________________

Q21: Monitoring/Analytics ?
[ ] Sentry (errors)
[ ] Vercel Analytics
[ ] PostHog
[ ] Google Analytics
[ ] Mixpanel
[ ] Aucun
[ ] Autre: _______________________

Q22: Caching ?
[ ] Upstash Redis
[ ] Vercel KV
[ ] Redis
[ ] In-memory
[ ] Aucun
[ ] Autre: _______________________
```

#### 1.4 Versioning & Package Management

```markdown
Q23: Package Manager ?
[ ] npm
[ ] pnpm
[ ] yarn
[ ] bun

Q24: Node.js version minimale requise ?
[ ] v18.x
[ ] v20.x
[ ] v22.x
[ ] Autre: _______________________

Q25: TypeScript version ?
[ ] 5.x (latest)
[ ] 4.x
[ ] Autre: _______________________

Q26: Versions exactes des dépendances critiques :

Framework principal:
- Nom: _______________________
- Version: _______________________

Database/ORM:
- Nom: _______________________
- Version: _______________________

Auth:
- Nom: _______________________
- Version: _______________________

UI Library:
- Nom: _______________________
- Version: _______________________

[Ajouter autres dépendances critiques]
```

#### 1.5 Règles de Développement

```markdown
Q27: TypeScript strict mode obligatoire ?
[ ] Oui
[ ] Non

Q28: Convention de nommage fichiers ?
[ ] kebab-case
[ ] camelCase
[ ] PascalCase
[ ] snake_case

Q29: Convention de nommage composants ?
[ ] PascalCase
[ ] Autre: _______________________

Q30: Pattern commit messages ?
[ ] Conventional Commits
[ ] Angular Commit Guidelines
[ ] Custom
[ ] Aucun standard

Q31: Linter ?
[ ] ESLint
[ ] Biome
[ ] Custom
[ ] Aucun

Q32: Formatter ?
[ ] Prettier
[ ] Biome
[ ] Aucun

Q33: Git hooks (Husky) ?
[ ] Oui - pre-commit (lint, format, type-check)
[ ] Oui - commit-msg (conventional commits)
[ ] Oui - pre-push (tests)
[ ] Non
```

#### 1.6 Sécurité

```markdown
Q34: Variables d'environnement validées au build ?
[ ] Oui - t3-env
[ ] Oui - envalid
[ ] Oui - custom Zod
[ ] Non

Q35: Rate limiting ?
[ ] Oui
[ ] Non

Q36: CORS configuration ?
[ ] Whitelist domaines
[ ] Allow all (dev only)
[ ] Custom

Q37: Gestion secrets en production ?
[ ] Vercel Env Vars
[ ] AWS Secrets Manager
[ ] .env.local (danger !)
[ ] Autre: _______________________
```

#### 1.7 Architecture Modulaire

```markdown
Q38: Structure projet ?

Décrire l'arborescence principale (exemple):
```
/
├── app/              # Next.js App Router
│   ├── (auth)/
│   ├── (admin)/
│   └── api/
├── components/
│   ├── ui/
│   └── [feature]/
├── lib/
│   ├── auth/
│   ├── db/
│   └── utils/
├── prisma/
└── docs/
```

Q39: Quels dossiers auront un README.md ?
[ ] /prisma
[ ] /src/lib
[ ] /src/lib/auth
[ ] /src/lib/supabase
[ ] /src/components/ui
[ ] /src/components/[feature]
[ ] /src/app/api
[ ] Autre: _______________________
```

---

## 2. Template CLAUDE.md

**Utilisation**: Fichier racine `/CLAUDE.md` pour LLM instructions.

**Taille cible**: 600-800 lignes (39k-48k caractères)

### Structure Recommandée

```markdown
# [NOM_PROJET] - Documentation Développement

**Version**: 1.0
**Dernière mise à jour**: [DATE]
**Stack**: [STACK_PRINCIPALE]

---

## 📖 Contexte du Projet

### Vision
[PROPOSITION_VALEUR]

### Architecture Globale
```
[SCHEMA_ASCII_ARCHITECTURE]
```

### Personas Cibles
1. **[Persona1]** ([Nom]) : [Besoin principal]
2. **[Persona2]** ([Nom]) : [Besoin principal]
3. **[Persona3]** ([Nom]) : [Besoin principal]

---

## 🎯 Règles de Développement Strictes

### 1. Standards de Code

#### TypeScript Strict
```typescript
// ✅ TOUJOURS utiliser TypeScript strict
{
  "compilerOptions": {
    "strict": true,
    "noUncheckedIndexedAccess": true,
    "noImplicitAny": true
  }
}

// ✅ Types explicites
[EXEMPLE_CODE]

// ❌ JAMAIS de any
[EXEMPLE_ANTI_PATTERN]
```

#### Conventions Nommage
```typescript
// Files: [CONVENTION]
// Components: [CONVENTION]
// Functions/variables: [CONVENTION]
// Constants: [CONVENTION]
// Types/Interfaces: [CONVENTION]
```

#### Structure Fichiers [FRAMEWORK]
```
[ARBORESCENCE]
```

📖 **Détails**: Voir [LIEN_README]

---

### 2. Gestion des Erreurs

#### Résumé
- ✅ [PRINCIPE_1]
- ✅ [PRINCIPE_2]
- ✅ [PRINCIPE_3]
- ✅ [PRINCIPE_4]

📖 **Détails**: Voir [LIEN_README]

---

### 3. Validation Variables d'Environnement ([OUTIL])

#### Setup
```typescript
[CODE_SETUP]
```

#### RÈGLE CRITIQUE
[DESCRIPTION_REGLES]

#### Utilisation
```typescript
[EXEMPLES_BON_MAUVAIS]
```

📖 **Configuration**: Voir [LIEN_FICHIER]

---

### 4. Base de Données ([ORM] + [DATABASE])

#### Règles Essentielles
- ✅ [REGLE_1]
- ✅ [REGLE_2]
- ✅ [REGLE_3]

```bash
# ✅ GOOD
[EXEMPLE_MIGRATION]

# ❌ BAD
[EXEMPLE_ANTI_PATTERN]
```

#### Connection Pooling (IMPORTANT)
[SI_APPLICABLE_SUPABASE_ETC]

📖 **Détails**: Voir [LIEN_README]

---

### 5. Authentification & Sécurité

#### Stack
- **[OUTIL_AUTH]**: [DESCRIPTION]
- **[ORM]**: Type-safe queries
- **[VALIDATION]**: Validation schemas partagés

#### Patterns
- ✅ [PATTERN_1]
- ✅ [PATTERN_2]
- ✅ [PATTERN_3]

📖 **Détails**: Voir [LIEN_README]

---

### 6. Architecture Client/Server ([FRAMEWORK])

#### RÈGLE CRITIQUE
[DESCRIPTION_SERVER_CLIENT_SEPARATION]

```typescript
// ❌ NE PAS FAIRE
[EXEMPLE_ERREUR]

// ✅ FAIRE
[EXEMPLE_CORRECT]
```

#### Checklist
1. [QUESTION_1]
2. [QUESTION_2]
3. [QUESTION_3]

📖 **Détails**: Voir [LIEN_README]

---

### 7. Components UI ([UI_LIBRARY])

#### Organisation
```
components/
├── ui/              → [DESCRIPTION]
├── [feature1]/      → [DESCRIPTION]
└── shared/          → [DESCRIPTION]
```

#### Best Practices
- ✅ [PRACTICE_1]
- ✅ [PRACTICE_2]
- ✅ [PRACTICE_3]

📖 **Détails**: Voir [LIEN_README]

---

### 8. API Routes

#### Pattern Standard
```typescript
[EXEMPLE_API_ROUTE]
```

#### Sécurité
- ✅ [SECURITE_1]
- ✅ [SECURITE_2]
- ✅ [SECURITE_3]

📖 **Détails**: Voir [LIEN_README]

---

### 9. Performance & Optimisation

#### React
- ✅ [OPTIMISATION_1]
- ✅ [OPTIMISATION_2]
- ✅ [OPTIMISATION_3]

#### Database
- ✅ [OPTIMISATION_1]
- ❌ [ANTI_PATTERN]

#### Caching ([OUTIL_SI_APPLICABLE])
- ✅ [USE_CASE_1]
- ✅ [USE_CASE_2]

📖 **Détails**: Voir [LIEN_README]

---

## 🔄 Règles de Commit Git

### Format Commit Messages

**OBLIGATOIRE**: Suivre [Conventional Commits](https://www.conventionalcommits.org/)

```bash
<type>(<scope>): <description>

[optional body]
[optional footer]
```

#### Types Autorisés
```
feat      → Nouvelle fonctionnalité
fix       → Correction bug
refactor  → Refactoring (pas de changement fonctionnel)
perf      → Amélioration performance
style     → Formatage, lint
test      → Ajout/modification tests
docs      → Documentation uniquement
chore     → Maintenance (deps, config)
ci        → CI/CD changes
```

#### Exemples Conformes
```bash
# ✅ GOOD
[EXEMPLES_CONFORMES]
```

#### Exemples NON Conformes
```bash
# ❌ BAD
[EXEMPLES_NON_CONFORMES]
```

### Règles Strictes

#### 1. Commits Atomiques
1 commit = 1 changement logique

#### 2. Interdiction Messages Génériques
```bash
# ❌ INTERDIT
[EXEMPLES_A_EVITER]

# ✅ Descriptions précises requises
[EXEMPLES_RECOMMANDES]
```

#### 3. Scope Recommandés
```
[LISTE_SCOPES]
```

---

## 🚀 Commandes Développement

### Setup Initial
```bash
[COMMANDES_SETUP]
```

### Développement
```bash
[COMMANDES_DEV]
```

### Database
```bash
[COMMANDES_DB]
```

### Production
```bash
[COMMANDES_PROD]
```

---

## 📦 Variables d'Environnement

### Variables Serveur (Server-only)
```env
[LISTE_VARIABLES_SERVER]
```

### Variables Client (Browser-accessible)
```env
[LISTE_VARIABLES_CLIENT]
```

**⚠️ IMPORTANT**: Variables validées via [OUTIL] (voir [LIEN])

📖 **Template**: Voir [.env.example]

---

## 📁 Documentation par Module/Dossier

### Principe
Chaque dossier majeur a un `README.md` qui documente son contenu.

### Structure Documentation
```
[NOM_PROJET]/
├── CLAUDE.md                    # Ce fichier (règles générales)
├── [module1]/README.md          # [Description]
├── docs/BEST_PRACTICES.md       # Best Practices [ANNÉE]
├── src/
│   ├── env.ts                   # Variables d'environnement
│   ├── lib/README.md            # [Description]
│   ├── lib/auth/README.md       # [Description]
│   ├── components/README.md     # [Description]
│   └── app/api/README.md        # [Description]
```

**Règle**: Consulter le README.md spécifique pour détails techniques.

---

## ✅ Checklist Avant Chaque PR

```markdown
- [ ] Code [LANGAGE] strict (no any)
- [ ] Tests unitaires ajoutés/modifiés (coverage ≥ [SEUIL]%)
- [ ] Documentation README mise à jour si nécessaire
- [ ] Commits suivent Conventional Commits
- [ ] Pas de console.log (utiliser logger)
- [ ] Pas de TODO/FIXME non trackés (créer issues)
- [ ] Migrations DB testées localement
- [ ] Variables env validées ([OUTIL])
- [ ] Performance vérifiée (no N+1 queries)
- [ ] Accessibilité vérifiée (aria-labels, contraste)
- [ ] Mobile responsive testé
- [ ] Erreurs gérées gracefully
- [ ] Sécurité validée (inputs sanitized, auth vérifiée)
- [ ] Build réussit sans erreurs
```

---

## 🔐 Sécurité Checklist

### Backend
- [x] [OUTIL_ENV] (variables validées au build)
- [x] Validation [OUTIL_VALIDATION] sur tous les inputs API
- [ ] [AUTRES_MESURES]

### Frontend
- [x] [MESURE_1]
- [ ] [MESURE_2]

### Database
- [x] [ORM] (protection SQL injection)
- [ ] [MESURE_1]
- [ ] [MESURE_2]

---

## 🔧 Troubleshooting

### [Erreur Commune 1]

**Description**:
[DESCRIPTION_PROBLEME]

**Cause**:
[EXPLICATION]

**Solutions**:
```bash
[COMMANDES_FIX]
```

[Répéter pour chaque erreur commune]

---

## 📚 Documentation Officielle & Ressources

### Core Stack
- [Lien vers doc framework principal]
- [Lien vers doc database]
- [Lien vers doc ORM]
- [Lien vers doc TypeScript]

### UI & Components
- [Lien vers doc UI library]
- [Lien vers doc CSS framework]
- [Liens autres]

### Backend Services
- [Lien vers services utilisés]

### Testing & Quality
- [Liens outils testing]

### Best Practices [ANNÉE]
📖 Voir [docs/BEST_PRACTICES.md]

---

## 🔄 Changelog

### [DATE] - [VERSION]
**✅ [Catégorie changement]:**
- [Changement 1]
- [Changement 2]

[Répéter pour chaque version]

---

**Fin de CLAUDE.md**

*Document vivant - Contribuez à l'améliorer !*
*Dernière mise à jour: [DATE]*

**📖 Pour plus de détails techniques, consultez les README.md spécifiques de chaque module.**
```

---

## 3. Template BEST_PRACTICES.md

**Utilisation**: Fichier `/docs/BEST_PRACTICES.md` pour best practices 2025.

**Taille cible**: 300-400 lignes

### Structure Recommandée

```markdown
# Best Practices [ANNÉE] - [NOM_PROJET]

**Dernière mise à jour**: [DATE]
**Sources**: Recherches officielles [FRAMEWORK], [DATABASE], [AUTRES] ([MOIS] [ANNÉE])

---

## 🚀 [FRAMEWORK_PRINCIPAL] ([VERSION])

### [Topic 1]

**Principe**: [DESCRIPTION]

**Quand utiliser [Pattern A]:**
- [Cas d'usage 1]
- [Cas d'usage 2]

**Quand utiliser [Pattern B]:**
- [Cas d'usage 1]
- [Cas d'usage 2]

**Pattern recommandé**: [DESCRIPTION]

```typescript
// ✅ [Pattern A]
[EXEMPLE_CODE]

// ✅ [Pattern B]
[EXEMPLE_CODE]
```

### [Topic 2]

[Répéter structure similaire]

---

## 🔐 [OUTIL_AUTH] + [PATTERN] ([ANNÉE])

### [Topic]

**⚠️ IMPORTANT**: [AVERTISSEMENT]
**✅ Utiliser**: [RECOMMANDATION]

### [Subtopic]

**Avantages:**
- [Avantage 1]
- [Avantage 2]

**Éviter**: [ANTI_PATTERN]

```typescript
// ✅ FAIRE: [Description]
[EXEMPLE_CODE]

// ❌ NE PAS FAIRE: [Description]
[EXEMPLE_CODE]
```

**Règle**: [REGLE_IMPORTANTE]

---

## 🗄️ [ORM] + [DATABASE] ([ANNÉE])

### Connection Pooling

**Configuration requise:**
```env
[EXEMPLE_CONFIG]
```

**Pourquoi `[PARAMETRE]` ?**
[EXPLICATION]

### [Autre Topic Database]

**Problème**: [DESCRIPTION]

**Solution**:
1. [Etape 1]
2. [Etape 2]

```bash
[COMMANDES]
```

---

## 🎨 [UI_LIBRARY] ([ANNÉE])

### Component Organization ([PATTERN])

**1. [Layer 1]** ([Description])
```typescript
[EXEMPLE]
```

**2. [Layer 2]** ([Description])
```typescript
[EXEMPLE]
```

**Avantages**: [LISTE]

### [Topic 2]

```typescript
// ✅ Utility-first
[EXEMPLE]

// ❌ Éviter [anti-pattern]
[EXEMPLE]
```

---

## 🔒 [OUTIL_ENV] ([ANNÉE])

### Environment Variables Validation

**Problème résolu**: [DESCRIPTION]

**Solution [OUTIL]**: Build-time validation, erreur explicite.

```typescript
[EXEMPLE_SETUP]
```

**Usage:**
```typescript
[EXEMPLES_BON_MAUVAIS]
```

**Avantage**: [LISTE]

---

## ⚡ Performance ([ANNÉE])

### Data Fetching Patterns

**✅ Parallel Fetching (recommandé):**
```typescript
[EXEMPLE]
```

**❌ Sequential Waterfall (éviter):**
```typescript
[EXEMPLE]
```

### [Autre Pattern]

[FRAMEWORK] déduplique automatiquement [DESCRIPTION]

```typescript
// Appelé 3x dans différents components → 1 seule requête réelle
[EXEMPLE]
```

---

## 🛡️ Security Checklist

### Backend
- [x] [MESURE_1]
- [x] [MESURE_2]
- [ ] [MESURE_3]

### Frontend
- [x] [MESURE_1]
- [ ] [MESURE_2]

### Database
- [x] [MESURE_1]
- [ ] [MESURE_2]

---

## 📚 Sources Officielles

- [Lien vers doc 1]
- [Lien vers doc 2]
- [Lien vers doc 3]

---

## 🔄 Changelog

### [DATE]
- Création document basé sur recherches [MOIS] [ANNÉE]
- [Topic 1], [Topic 2], [Topic 3]

---

*Maintenu par: [EQUIPE]*
```

---

## 4. Guide README.md Modulaires

### Principe

Chaque dossier majeur (≥3 fichiers critiques) mérite un README.md.

### Structure Standard README.md

```markdown
# [NOM_MODULE] - [DESCRIPTION_COURTE]

**Stack**: [STACK_SPECIFIQUE]
**Version**: [VERSION]
**Dernière mise à jour**: [DATE]

---

## 📖 Vue d'Ensemble

[DESCRIPTION_DETAILLEE_MODULE]

### Fonctionnalités

✅ **[Feature 1]**
- [Détail 1]
- [Détail 2]

✅ **[Feature 2]**
- [Détail 1]
- [Détail 2]

---

## 📁 Structure du Dossier

```
[ARBORESCENCE_DETAILLEE]
```

---

## [Section Spécifique au Module]

### [Topic 1]

> ⚠️ [AVERTISSEMENT_SI_APPLICABLE]

#### `[fonction/composant]`

[DESCRIPTION]

```typescript
[EXEMPLE_CODE]
```

**[Note importante]**:
- [Point 1]
- [Point 2]

---

## 🔧 Configuration

### [Configuration nécessaire]

```typescript/bash/env
[EXEMPLE_CONFIG]
```

---

## 📚 Ressources

- [Lien vers doc officielle]
- [Lien vers guide externe]

---

## 🔄 Changelog

### [DATE] - [VERSION]
- [Changement 1]
- [Changement 2]

---

*Dernière mise à jour: [DATE]*
```

### Quels dossiers documenter ?

**Toujours documenter** :
- `/prisma` ou `/drizzle` (database)
- `/src/lib` (utilities principales)
- `/src/lib/auth` (authentification)
- `/src/lib/[database-client]` (Supabase, Firebase, etc.)
- `/src/components/ui` (UI library)
- `/src/app/api` (API routes patterns)
- `/docs` (documentation projet)

**Documenter si complexe** :
- `/src/lib/[feature]` (si ≥5 fichiers)
- `/src/components/[feature]` (si composants réutilisables)
- `/src/hooks` (si ≥3 custom hooks)
- `/src/lib/types` (si système de types complexe)

---

## 5. Checklist Versionning

### Phase 1: Capture Versions Actuelles

```bash
# Node.js
node --version

# Package manager
npm --version
pnpm --version
yarn --version

# Framework principal
npx next --version  # ou autre framework

# TypeScript
npx tsc --version

# Afficher toutes les dépendances
cat package.json | jq '.dependencies, .devDependencies'
```

### Phase 2: Documenter Versions Critiques

**Template pour CLAUDE.md** :

```markdown
## 🔧 Versions Critiques

**Dernière vérification**: [DATE]

| Outil | Version | Notes |
|-------|---------|-------|
| Node.js | [VERSION] | Minimum requis |
| [Package Manager] | [VERSION] | |
| [Framework] | [VERSION] | Pinned sans `^` |
| TypeScript | [VERSION] | |
| [ORM] | [VERSION] | [Raison pinning si applicable] |
| [Auth Library] | [VERSION] | |
| [UI Library] | [VERSION] | |

### Version Pinning

Dépendances **SANS** `^` (version exacte) :

```json
{
  "dependencies": {
    "[package1]": "[VERSION]",  // [Raison]
    "[package2]": "[VERSION]"   // [Raison]
  }
}
```

**Pourquoi ?** [EXPLICATION_GENERALE]
```

### Phase 3: Checklist Pre-Documentation

```markdown
- [ ] Node.js version minimale définie
- [ ] Package manager choisi (npm/pnpm/yarn/bun)
- [ ] Framework principal version capturée
- [ ] TypeScript version capturée
- [ ] ORM/Query builder version capturée
- [ ] Auth library version capturée
- [ ] UI library version capturée
- [ ] Dépendances critiques identifiées pour pinning
- [ ] Raisons de pinning documentées
- [ ] .nvmrc créé (optionnel mais recommandé)
- [ ] package.json scripts standardisés
```

### Phase 4: Scripts package.json Standards

Recommandé dans **tout** projet :

```json
{
  "scripts": {
    "dev": "[COMMANDE_DEV]",
    "build": "[COMMANDE_BUILD]",
    "start": "[COMMANDE_START]",
    "lint": "[COMMANDE_LINT]",
    "lint:fix": "[COMMANDE_LINT_FIX]",
    "type-check": "tsc --noEmit",
    "test": "[COMMANDE_TEST]",
    "test:watch": "[COMMANDE_TEST_WATCH]",
    "test:coverage": "[COMMANDE_TEST_COVERAGE]"
  }
}
```

Si Prisma/Drizzle :

```json
{
  "scripts": {
    "db:generate": "[COMMANDE_GENERATE]",
    "db:migrate": "[COMMANDE_MIGRATE]",
    "db:push": "[COMMANDE_PUSH]",
    "db:studio": "[COMMANDE_STUDIO]",
    "db:seed": "[COMMANDE_SEED]",
    "postinstall": "[COMMANDE_POSTINSTALL]"
  }
}
```

---

## 6. Méthodologie de Création

### Étape 1: Discovery (Questionnaire PRD)

**Durée estimée**: 30-60 minutes

1. Remplir **Section 1.1 à 1.3** du Questionnaire PRD
2. Identifier les **3 personas** principales
3. Lister la **stack complète** (Q7 à Q22)
4. Capturer les **versions exactes** (Q23 à Q26)

**Livrable**: Document PRD complété

---

### Étape 2: Capture Standards Projet (Questionnaire PRD suite)

**Durée estimée**: 15-30 minutes

1. Définir **conventions de code** (Q27 à Q33)
2. Définir **règles de sécurité** (Q34 à Q37)
3. Dessiner **arborescence projet** (Q38 à Q39)

**Livrable**: Standards projet documentés

---

### Étape 3: Création CLAUDE.md (Racine)

**Durée estimée**: 1-2 heures

1. Copier template Section 2
2. Remplir **Contexte Projet** avec réponses PRD
3. Pour chaque section technique :
   - Identifier la stack (PRD)
   - Créer exemples code ✅ / ❌
   - Référencer README.md modulaire (à créer après)
4. Ajouter **Règles Git** (Conventional Commits)
5. Ajouter **Commandes Dev** (package.json scripts)
6. Lister **Variables Env** (.env.example)
7. Créer **Changelog initial**

**Livrable**: `/CLAUDE.md` (600-800 lignes)

---

### Étape 4: Création BEST_PRACTICES.md

**Durée estimée**: 1-2 heures

1. Copier template Section 3
2. Rechercher best practices 2025 pour :
   - Framework principal (Next.js, React, etc.)
   - Base de données + ORM
   - Auth library
   - UI library
   - Env validation tool
3. Créer sections avec code examples
4. Ajouter security checklist
5. Lister sources officielles

**Livrable**: `/docs/BEST_PRACTICES.md` (300-400 lignes)

---

### Étape 5: Création README.md Modulaires

**Durée estimée**: 2-4 heures (selon nombre de modules)

Pour chaque module identifié (Q39) :

1. Créer `/[module]/README.md`
2. Copier template Section 4
3. Documenter :
   - Structure dossier (arborescence)
   - Fonctions/composants principaux
   - Configuration nécessaire
   - Exemples code
4. Référencer depuis CLAUDE.md

**Ordre recommandé** :
1. `/prisma/README.md` (ou drizzle)
2. `/src/lib/README.md`
3. `/src/lib/auth/README.md`
4. `/src/lib/[database-client]/README.md`
5. `/src/components/ui/README.md`
6. `/src/app/api/README.md`
7. Autres modules spécifiques

**Livrable**: 5-10 README.md (200-600 lignes chacun)

---

### Étape 6: Synchronisation Cross-References

**Durée estimée**: 30 minutes

1. Vérifier tous liens `[texte](chemin/README.md)` dans CLAUDE.md
2. Vérifier tous liens vers docs officielles (URL valides)
3. Vérifier cohérence versions entre :
   - CLAUDE.md
   - package.json
   - Chaque README.md

**Livrable**: Documentation cohérente et linkée

---

### Étape 7: Validation & Review

**Durée estimée**: 30-60 minutes

**Checklist finale** :

```markdown
Documentation Générale
- [ ] CLAUDE.md existe et fait 600-800 lignes
- [ ] BEST_PRACTICES.md existe et fait 300-400 lignes
- [ ] Tous les README.md modulaires créés
- [ ] .env.example existe avec toutes les variables
- [ ] package.json scripts standardisés

Contenu CLAUDE.md
- [ ] Contexte projet rempli (Vision, Personas, Architecture)
- [ ] Règles de développement pour chaque stack
- [ ] Références vers README.md corrects
- [ ] Règles Git (Conventional Commits)
- [ ] Commandes dev complètes
- [ ] Variables env listées
- [ ] Troubleshooting avec ≥2 erreurs communes
- [ ] Changelog initial
- [ ] Versioning section avec versions critiques

Contenu BEST_PRACTICES.md
- [ ] Best practices pour framework principal
- [ ] Best practices pour database/ORM
- [ ] Best practices pour auth
- [ ] Best practices pour UI
- [ ] Security checklist
- [ ] Sources officielles listées
- [ ] Année correcte partout (2025)

README.md Modulaires
- [ ] Chaque README suit template Section 4
- [ ] Arborescences correctes
- [ ] Exemples code fonctionnels
- [ ] Références cross-modules cohérentes

Cross-Validation
- [ ] Tous liens internes valides
- [ ] Toutes URLs externes valides
- [ ] Versions cohérentes partout
- [ ] Pas de TODO/FIXME non résolus
- [ ] Orthographe/grammaire vérifiée
```

**Livrable**: Documentation production-ready

---

## 📚 Ressources Complémentaires

### Outils Recommandés

**Validation documentation** :
- [markdownlint](https://github.com/DavidAnson/markdownlint) - Linter Markdown
- [markdown-link-check](https://github.com/tcort/markdown-link-check) - Vérifier liens morts

**Recherche best practices** :
- Docs officielles framework (toujours source primaire)
- GitHub Discussions du framework
- Blog officiel du framework
- [patterns.dev](https://www.patterns.dev/) - Design patterns

**Versioning** :
- [npmview](https://npmview.vercel.app/) - Checker versions npm
- [bundlephobia](https://bundlephobia.com/) - Analyser tailles packages

---

## 🎯 Résumé Méthodologie

```
1. Discovery (PRD)           → 30-60 min
2. Capture Standards        → 15-30 min
3. CLAUDE.md                → 1-2h
4. BEST_PRACTICES.md        → 1-2h
5. README.md modulaires     → 2-4h
6. Cross-references         → 30 min
7. Validation               → 30-60 min
─────────────────────────────────────
TOTAL                       → 6-10h
```

**Résultat** :
- Documentation complète LLM-friendly
- Nouveaux devs onboardés en <1h
- Standards clairs pour toute l'équipe
- Maintenance facilitée

---

## 📝 Checklist Projet Nouveau

```markdown
Avant de commencer :
- [ ] Remplir Questionnaire PRD complet
- [ ] Capturer versions exactes de la stack
- [ ] Définir conventions code (nommage, commits, etc.)
- [ ] Identifier modules à documenter

Création documentation :
- [ ] CLAUDE.md (racine)
- [ ] BEST_PRACTICES.md (docs/)
- [ ] README.md par module
- [ ] .env.example
- [ ] package.json scripts standardisés

Post-création :
- [ ] Valider liens internes
- [ ] Valider liens externes
- [ ] Vérifier cohérence versions
- [ ] Review finale (checklist Section 6 Étape 7)
```

---

**Fin du Guide Méthodologique**

*Version 1.0 - 5 décembre 2025*
*Basé sur l'analyse du projet Vision Loyalty Platform*
