# Architecture Réseau Social KLASSCI - Document Technique d'Implémentation

> **Document créé le** : 2 novembre 2025
> **Version** : 1.0 (Analyse de faisabilité)
> **Statut** : En cours de spécification - Prêt pour implémentation
> **Type** : Application cross-tenant séparée

---

## 📋 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture Système](#architecture-système)
3. [Modèle de Données Hybride](#modèle-de-données-hybride)
4. [API Tenant - Endpoints Requis](#api-tenant---endpoints-requis)
5. [Synchronisation & SSO](#synchronisation--sso)
6. [Exemples Concrets](#exemples-concrets)
7. [Stack Technique](#stack-technique)
8. [Roadmap Implémentation](#roadmap-implémentation)

---

## 🎯 Vue d'ensemble

### Concept

**Réseau social cross-tenant** où TOUS les étudiants de TOUS les établissements KLASSCI peuvent interagir, créant ainsi une grande communauté panafricaine élitiste.

### Principe Architectural Clé

**Architecture Hybride** :
- ✅ **Cache minimal local** : ID mapping + nom + photo (pour performance feed)
- ✅ **API temps réel** : Tout le reste récupéré via API tenant/master quand nécessaire
- ✅ **Pas de duplication** : Source de vérité = KLASSCI tenant + master

### Types de Comptes

| Type | Qui peut créer | Poster comme | Exemples |
|------|---------------|---------------|----------|
| **Student** | Étudiant avec inscription active | Nom propre | @jean.kouassi (ESBTP Abidjan) |
| **Teacher** | Enseignant/Coordinateur actif | Nom propre | @prof.bamba (Enseignant ESBTP Yakro) |
| **Institution** | SuperAdmin du tenant uniquement | Nom établissement (page officielle) | @esbtp-abidjan (Compte officiel) |

---

## 🏗️ Architecture Système

### Infrastructure Production

```
┌─────────────────────────────────────────────────────────────────┐
│                    KLASSCI ECOSYSTEM                             │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────┐     ┌──────────────────────┐     ┌──────────────────┐
│  admin.klassci.com   │     │  social.klassci.com  │     │  Mobile App      │
│  (Master Admin)      │────▶│  (Réseau Social)     │◀────│  (Future)        │
│                      │     │                      │     │                  │
│  Laravel 12.x        │     │  Laravel 12.x        │     │  Flutter/RN      │
│  DB: klassci_master  │     │  DB: klassci_social  │     │  API REST        │
│  API: /api/*         │     │  API: /api/v1/*      │     │                  │
└──────────────────────┘     └──────────────────────┘     └──────────────────┘
         │                            │
         │                            │
         ▼                            ▼
┌─────────────────────────────────────────────────────────────────┐
│              Tenants KLASSCI (isolés par BDD)                   │
├─────────────────────────────────────────────────────────────────┤
│  • esbtp-abidjan.klassci.com    (DB: esbtp_abidjan)           │
│  • esbtp-yakro.klassci.com      (DB: esbtp_yakro)             │
│  • presentation.klassci.com     (DB: presentation)             │
│  • [futurs tenants...]                                          │
└─────────────────────────────────────────────────────────────────┘
```

**Note développement local** : Remplacer sous-domaines par ports (localhost:8000, :8001, :8002)

---

## 📊 Modèle de Données Hybride

### BDD `klassci_social` - Tables Principales

#### 1. Users (Cache léger + données sociales)

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,

    -- ID Mapping (clés étrangères virtuelles)
    tenant_code VARCHAR(50) NOT NULL,      -- 'esbtp-abidjan', 'esbtp-yakro'
    tenant_user_id BIGINT NOT NULL,        -- users.id dans tenant DB

    -- Type de compte
    user_type ENUM('student', 'teacher', 'institution') NOT NULL,

    -- Cache léger (sync régulier)
    username VARCHAR(50) UNIQUE NOT NULL,  -- @jean.kouassi
    name VARCHAR(255) NOT NULL,            -- Cache: Nom complet
    email VARCHAR(255) UNIQUE NOT NULL,    -- Cache: Email
    photo_url VARCHAR(500),                -- Cache: URL photo

    -- Données spécifiques réseau social (PAS dans tenant)
    bio TEXT,                              -- Description profil social
    linkedin_url VARCHAR(500),             -- Liens externes
    website_url VARCHAR(500),              -- Pour institutions
    followers_count INT DEFAULT 0,         -- Cache compteur
    following_count INT DEFAULT 0,         -- Cache compteur
    posts_count INT DEFAULT 0,             -- Cache compteur

    -- Métadonnées
    status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
    is_verified BOOLEAN DEFAULT 0,         -- Badge vérifié
    last_sync_at TIMESTAMP,                -- Dernière sync cache depuis tenant
    last_seen_at TIMESTAMP,                -- Activité réseau social
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    UNIQUE KEY unique_tenant_user (tenant_code, tenant_user_id),
    INDEX idx_type (user_type),
    INDEX idx_status (status),
    INDEX idx_username (username),
    INDEX idx_tenant (tenant_code)
);
```

**Données NON dupliquées** (récupérées via API tenant en temps réel) :
- ❌ Classe, filière, niveau (→ API tenant)
- ❌ Matricule (→ API tenant)
- ❌ Date naissance, téléphone (→ API tenant)
- ❌ Adresse, contacts urgence (→ API tenant)
- ❌ Spécialisation enseignant (→ API tenant)

#### 2. Student Extensions (ID mapping uniquement)

```sql
CREATE TABLE student_extensions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNIQUE NOT NULL,        -- Lien vers users

    -- ID Mapping vers tenant (pour API calls)
    tenant_student_id BIGINT,              -- esbtp_etudiants.id
    tenant_inscription_id BIGINT,          -- esbtp_inscriptions.id active

    -- Cache minimal pour communautés
    institution_slug VARCHAR(50),          -- 'esbtp-abidjan' (cache)
    filiere_name VARCHAR(100),             -- Cache pour affichage cards
    niveau_name VARCHAR(50),               -- Cache pour affichage cards
    graduation_year INT,                   -- Calculé à la sync

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_institution (institution_slug),
    INDEX idx_graduation (graduation_year)
);
```

#### 3. Teacher Extensions (ID mapping)

```sql
CREATE TABLE teacher_extensions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNIQUE NOT NULL,

    -- ID Mapping
    tenant_teacher_id BIGINT,              -- esbtp_teachers.id

    -- Cache minimal
    institution_slug VARCHAR(50),
    title VARCHAR(50),                     -- Dr., Pr. (cache)

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 4. Institutions (Info de base uniquement)

```sql
CREATE TABLE institutions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNIQUE,                 -- Lien vers users (compte @esbtp-abidjan)

    -- Sync depuis klassci_master.tenants
    tenant_code VARCHAR(50) UNIQUE NOT NULL,
    short_name VARCHAR(100),               -- "ESBTP ABJ"
    city VARCHAR(100),
    country VARCHAR(100),
    plan ENUM('free', 'essentiel', 'pro', 'entreprise'),

    -- Sync depuis tenant.settings
    logo_url VARCHAR(500),                 -- settings.school_logo
    cover_photo_url VARCHAR(500),
    settings_json JSON,                    -- Snapshot complet settings tenant

    -- Stats
    student_count INT DEFAULT 0,
    teacher_count INT DEFAULT 0,
    post_count INT DEFAULT 0,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 5. Posts (Données sociales uniquement)

```sql
CREATE TABLE posts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    community_id BIGINT,                   -- Communauté (classe, filière, général)

    post_type ENUM('text', 'link', 'image', 'poll'),
    title VARCHAR(300),
    content TEXT,
    media_url VARCHAR(500),
    link_url VARCHAR(500),

    -- Cache pour performance
    vote_score INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    view_count INT DEFAULT 0,

    -- Modération
    is_pinned BOOLEAN DEFAULT 0,
    is_locked BOOLEAN DEFAULT 0,
    is_nsfw BOOLEAN DEFAULT 0,

    deleted_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_community (community_id, created_at),
    INDEX idx_user (user_id),
    INDEX idx_vote_score (vote_score DESC),
    INDEX idx_created (created_at DESC)
);
```

#### 6. Comments (Threading 3 niveaux max)

```sql
CREATE TABLE comments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    post_id BIGINT NOT NULL,
    parent_id BIGINT NULL,                 -- NULL = top-level
    user_id BIGINT NOT NULL,

    content TEXT NOT NULL,

    -- Threading
    depth INT DEFAULT 0,                   -- 0, 1, 2 (max 3 niveaux)
    path VARCHAR(500),                     -- "1/5/12" pour nested set

    -- Cache
    vote_score INT DEFAULT 0,
    reply_count INT DEFAULT 0,

    deleted_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_path (post_id, path),
    INDEX idx_parent (parent_id),
    INDEX idx_user (user_id)
);
```

#### 7. Votes (Upvote/Downvote)

```sql
CREATE TABLE votes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    votable_type VARCHAR(50) NOT NULL,     -- 'post' ou 'comment'
    votable_id BIGINT NOT NULL,
    vote_type TINYINT NOT NULL,            -- 1 = upvote, -1 = downvote
    created_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (user_id, votable_type, votable_id),
    INDEX idx_votable (votable_type, votable_id)
);
```

#### 8. Communities (Basées sur classes/filières)

```sql
CREATE TABLE communities (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon_url VARCHAR(500),
    cover_url VARCHAR(500),

    -- Type de communauté
    community_type ENUM('classe', 'filiere', 'general', 'custom'),

    -- Lien vers tenant (si classe ou filière)
    linked_tenant_code VARCHAR(50),        -- 'esbtp-abidjan'
    linked_tenant_id BIGINT,               -- classe_id ou filiere_id dans tenant

    -- Stats
    member_count INT DEFAULT 0,
    post_count INT DEFAULT 0,

    -- Paramètres
    is_private BOOLEAN DEFAULT 0,
    require_approval BOOLEAN DEFAULT 0,

    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_type (community_type),
    INDEX idx_slug (slug)
);
```

#### 9. Follows (Abonnements users/communautés)

```sql
CREATE TABLE follows (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    follower_id BIGINT NOT NULL,           -- Qui suit
    followable_type VARCHAR(50) NOT NULL,  -- 'user' ou 'community'
    followable_id BIGINT NOT NULL,         -- ID entité suivie
    created_at TIMESTAMP,

    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, followable_type, followable_id),
    INDEX idx_followable (followable_type, followable_id)
);
```

#### 10. Hashtags

```sql
CREATE TABLE hashtags (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP,
    INDEX idx_name (name)
);

CREATE TABLE post_hashtag (
    post_id BIGINT NOT NULL,
    hashtag_id BIGINT NOT NULL,
    PRIMARY KEY (post_id, hashtag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
);
```

---

## 🔌 API Tenant - Endpoints Requis

### 1. Vérification Étudiant Actif

**Endpoint existant (LMS API)** :
```
GET /api/lms/classes/{classeId}/etudiants
Authorization: Bearer {token}
```

**Nouveau endpoint dédié social** :
```
GET /api/social/students/verify
Authorization: Bearer {master_api_token}

Request Body:
{
  "student_id": 2,           // esbtp_etudiants.id
  "tenant_code": "esbtp-abidjan"
}

Response 200 OK:
{
  "success": true,
  "student": {
    "id": 2,
    "user_id": 8,
    "matricule": "FBTS2024/830",
    "nom_complet": "WELEO SAMUERA MARIELLE DJEDJE",
    "email": "weleo.djedje@esbtp.edu",
    "photo": null,
    "telephone": "0153887624",
    "sexe": "F",
    "inscription": {
      "id": 2,
      "classe_id": 30,
      "status": "active",
      "annee_universitaire_id": 5
    },
    "classe": {
      "id": 30,
      "code": "BTS2-TPB",
      "filiere": {
        "id": 9,
        "nom": "Génie des Travaux Publics",
        "code": "GTP"
      },
      "niveau": {
        "id": 2,
        "nom": "BTS 2ème année",
        "code": "BTS2"
      }
    },
    "is_eligible_social": true  // inscription active + année courante
  }
}

Response 403 Forbidden (si inactif):
{
  "success": false,
  "message": "Étudiant sans inscription active",
  "reason": "no_active_inscription"
}
```

**Logique backend** (à ajouter dans tenant) :
```php
public function verifyStudent(Request $request)
{
    $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();

    $etudiant = ESBTPEtudiant::with([
        'user',
        'inscriptions' => function($q) use ($annee) {
            $q->where('annee_universitaire_id', $annee->id)
              ->where('status', 'active')
              ->whereNotNull('classe_id');
        },
        'inscriptions.classe.filiere',
        'inscriptions.classe.niveauEtude'
    ])->find($request->student_id);

    if (!$etudiant || $etudiant->inscriptions->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Étudiant sans inscription active',
            'reason' => 'no_active_inscription'
        ], 403);
    }

    $inscription = $etudiant->inscriptions->first();

    return response()->json([
        'success' => true,
        'student' => [
            'id' => $etudiant->id,
            'user_id' => $etudiant->user_id,
            'matricule' => $etudiant->matricule,
            'nom_complet' => $etudiant->user->name,
            'email' => $etudiant->user->email,
            'photo' => $etudiant->photo,
            'telephone' => $etudiant->telephone,
            'sexe' => $etudiant->sexe,
            'inscription' => [
                'id' => $inscription->id,
                'classe_id' => $inscription->classe_id,
                'status' => $inscription->status,
                'annee_universitaire_id' => $inscription->annee_universitaire_id
            ],
            'classe' => [
                'id' => $inscription->classe->id,
                'code' => $inscription->classe->code,
                'filiere' => [
                    'id' => $inscription->classe->filiere->id,
                    'nom' => $inscription->classe->filiere->nom,
                    'code' => $inscription->classe->filiere->code
                ],
                'niveau' => [
                    'id' => $inscription->classe->niveauEtude->id,
                    'nom' => $inscription->classe->niveauEtude->nom,
                    'code' => $inscription->classe->niveauEtude->code
                ]
            ],
            'is_eligible_social' => true
        ]
    ]);
}
```

### 2. Vérification Enseignant Actif

```
GET /api/social/teachers/verify
Authorization: Bearer {master_api_token}

Request Body:
{
  "teacher_id": 1,           // esbtp_teachers.id
  "tenant_code": "esbtp-abidjan"
}

Response 200 OK:
{
  "success": true,
  "teacher": {
    "id": 1,
    "user_id": 1634,
    "matricule": "ENS1634",
    "name": "KOUASSI Jean",
    "email": "kouassi.jean@esbtp.ci",
    "title": "Dr.",
    "specialization": "Mathématiques et Physique",
    "status": "active",
    "is_eligible_social": true
  }
}
```

### 3. Récupération Settings Établissement

```
GET /api/social/institution/settings
Authorization: Bearer {master_api_token}

Request Body:
{
  "tenant_code": "esbtp-abidjan"
}

Response 200 OK:
{
  "success": true,
  "institution": {
    "name": "ESBTP-yAKRO",                     // settings.school_name
    "acronym": "ESBTP",                         // settings.school_acronym
    "address": "BP 1234",                       // settings.school_address
    "city": "Yamoussoukro",                     // settings.school_city
    "country": "Côte d'Ivoire",                 // settings.school_country
    "phone": "+225 27 30 00 00",                // settings.school_phone
    "email": "contact@esbtp.edu",               // settings.school_email
    "logo_url": "/storage/logos/esbtp.png",     // settings.school_logo
    "director_name": "Mme KOFFI Marie",         // settings.director_name
    "director_title": "Directrice Générale"     // settings.director_title
  }
}
```

**Logique backend** :
```php
use App\Helpers\SettingsHelper;

public function institutionSettings(Request $request)
{
    return response()->json([
        'success' => true,
        'institution' => [
            'name' => SettingsHelper::get('school_name', 'ESBTP'),
            'acronym' => SettingsHelper::get('school_acronym', 'ESBTP'),
            'address' => SettingsHelper::get('school_address', ''),
            'city' => SettingsHelper::get('school_city', ''),
            'country' => SettingsHelper::get('school_country', 'Côte d\'Ivoire'),
            'phone' => SettingsHelper::get('school_phone', ''),
            'email' => SettingsHelper::get('school_email', ''),
            'logo_url' => SettingsHelper::get('school_logo', ''),
            'director_name' => SettingsHelper::get('director_name', ''),
            'director_title' => SettingsHelper::get('director_title', 'Directeur Général')
        ]
    ]);
}
```

---

## 🔐 Synchronisation & SSO

### Flow d'authentification SSO

```
1. Étudiant connecté sur esbtp-abidjan.klassci.com
   ↓
2. Clique menu "Réseau Social KLASSCI"
   ↓
3. Tenant génère JWT token signé
   Payload: {
     user_id: 8,
     tenant_code: 'esbtp-abidjan',
     user_type: 'student',
     student_id: 2,
     exp: now + 5min
   }
   ↓
4. Redirect → https://social.klassci.com/auth/sso?token=xxx
   ↓
5. social.klassci.com décode JWT + valide signature
   ↓
6. API call → tenant/api/social/students/verify
   ↓
7. Si valide → Sync/Create user dans klassci_social
   ↓
8. Générer session social.klassci.com (Sanctum token)
   ↓
9. Redirect → https://social.klassci.com/feed
```

### Synchronisation Profils

**Trigger** :
- Login SSO (première fois ou tous les 7 jours)
- Webhook depuis tenant (changement nom, photo, classe)
- Cronjob quotidien (mise à jour massive)

**Logique sync** (dans klassci-social) :
```php
public function syncStudent($tenantCode, $tenantStudentId)
{
    // 1. Appeler API tenant
    $response = Http::withToken(config('services.klassci.api_token'))
        ->post("https://{$tenantCode}.klassci.com/api/social/students/verify", [
            'student_id' => $tenantStudentId,
            'tenant_code' => $tenantCode
        ]);

    $studentData = $response->json('student');

    // 2. Upsert dans klassci_social
    $user = User::updateOrCreate(
        [
            'tenant_code' => $tenantCode,
            'tenant_user_id' => $studentData['user_id']
        ],
        [
            'user_type' => 'student',
            'username' => $this->generateUsername($studentData['nom_complet']),
            'name' => $studentData['nom_complet'],
            'email' => $studentData['email'],
            'photo_url' => $studentData['photo'],
            'last_sync_at' => now()
        ]
    );

    // 3. Upsert extension
    StudentExtension::updateOrCreate(
        ['user_id' => $user->id],
        [
            'tenant_student_id' => $studentData['id'],
            'tenant_inscription_id' => $studentData['inscription']['id'],
            'institution_slug' => $tenantCode,
            'filiere_name' => $studentData['classe']['filiere']['nom'],
            'niveau_name' => $studentData['classe']['niveau']['nom'],
            'graduation_year' => $this->calculateGraduationYear($studentData['classe']['niveau']['code'])
        ]
    );

    return $user;
}
```

---

## 💡 Exemples Concrets

### Exemple 1 : Afficher un Post dans le Feed

**Problème** : Comment afficher nom + photo + établissement sans tout dupliquer ?

**Solution** :
```php
// klassci-social/app/Services/FeedService.php

public function buildFeedItem(Post $post)
{
    $author = $post->user; // users table (cache minimal)

    // Données du cache (rapide)
    $feedItem = [
        'post_id' => $post->id,
        'title' => $post->title,
        'content' => $post->content,
        'created_at' => $post->created_at,
        'vote_score' => $post->vote_score,
        'comment_count' => $post->comment_count,

        'author' => [
            'username' => $author->username,       // Cache
            'name' => $author->name,                // Cache
            'photo_url' => $author->photo_url,      // Cache
            'is_verified' => $author->is_verified
        ]
    ];

    // Si étudiant, afficher établissement (cache extension)
    if ($author->user_type === 'student') {
        $extension = $author->studentExtension; // studentExtension relation

        $feedItem['author']['institution'] = [
            'slug' => $extension->institution_slug,  // Cache
            'filiere' => $extension->filiere_name,   // Cache
            'niveau' => $extension->niveau_name      // Cache
        ];
    }

    // Si on a besoin de détails complets (ex: clic sur profil)
    // → API call tenant (lazy loading)

    return $feedItem;
}
```

**Résultat JSON** :
```json
{
  "post_id": 42,
  "title": "Offre de stage BTP à Abidjan",
  "content": "Salut à tous ! Je recherche...",
  "created_at": "2025-11-02T10:30:00Z",
  "vote_score": 15,
  "comment_count": 3,
  "author": {
    "username": "jean.kouassi",
    "name": "KOUASSI Jean",
    "photo_url": "/storage/photos/jean.jpg",
    "is_verified": true,
    "institution": {
      "slug": "esbtp-abidjan",
      "filiere": "Génie Civil",
      "niveau": "Licence 3"
    }
  }
}
```

### Exemple 2 : Page Profil Étudiant (Détails Complets)

**Problème** : Utilisateur clique sur profil → besoin de TOUT (téléphone, classe complète, etc.)

**Solution** : API call tenant en temps réel

```php
// klassci-social/app/Http/Controllers/ProfileController.php

public function show($username)
{
    // 1. Récupérer user depuis cache
    $user = User::where('username', $username)->firstOrFail();

    if ($user->user_type !== 'student') {
        abort(404);
    }

    // 2. API call tenant pour données complètes
    $tenantApiService = new TenantAPIService();
    $fullProfile = $tenantApiService->getStudentFullProfile(
        $user->tenant_code,
        $user->studentExtension->tenant_student_id
    );

    // 3. Combiner cache + API
    return view('profiles.student', [
        // Depuis cache
        'user' => $user,
        'followers_count' => $user->followers_count,
        'posts_count' => $user->posts_count,
        'bio' => $user->bio,

        // Depuis API tenant (fraîcheur garantie)
        'matricule' => $fullProfile['matricule'],
        'telephone' => $fullProfile['telephone'],
        'classe' => $fullProfile['classe'],
        'filiere' => $fullProfile['filiere'],
        'niveau' => $fullProfile['niveau'],

        // Données sociales (BDD social)
        'recent_posts' => $user->posts()->latest()->take(5)->get()
    ]);
}
```

**TenantAPIService** :
```php
public function getStudentFullProfile($tenantCode, $studentId)
{
    $response = Http::withToken(config('services.klassci.api_token'))
        ->post("https://{$tenantCode}.klassci.com/api/social/students/verify", [
            'student_id' => $studentId,
            'tenant_code' => $tenantCode
        ]);

    return $response->json('student');
}
```

---

## 🚀 Stack Technique

### Backend
- ✅ **Laravel 12.x** (nouveau repo `klassci-social`)
- ✅ **MySQL 8.x** (BDD `klassci_social`)
- ✅ **Redis** (cache feed + queues)
- ✅ **Laravel Sanctum** (API tokens mobile)
- ✅ **Laravel Horizon** (queues management)
- 🆕 **Guzzle HTTP** (appels API tenant/master)

### Frontend Web
- ✅ **Blade + Alpine.js** (cohérence avec KLASSCI)
- 🆕 **Livewire 3** (réactivité feed/comments)
- ✅ **Tailwind CSS** (design system)

### Infrastructure
- ✅ **S3/DigitalOcean Spaces** (médias posts)
- 🆕 **Meilisearch** (recherche full-text posts/users)
- 🆕 **Laravel Echo + Pusher** (notifications temps réel)

---

## 📅 Roadmap Implémentation

### Phase 0 : Préparation (2 semaines)

**Dans tenants (esbtp-abidjan, esbtp-yakro, etc.)** :
- [ ] Créer endpoint `/api/social/students/verify`
- [ ] Créer endpoint `/api/social/teachers/verify`
- [ ] Créer endpoint `/api/social/institution/settings`
- [ ] Tester avec Postman
- [ ] Documenter dans README tenant

**Dans klassci-master** :
- [ ] Créer endpoint `/api/tenants/list` (liste établissements actifs)
- [ ] Générer Master API token dédié social

### Phase 1 : MVP Social (3-4 mois)

**Semaines 1-2 : Setup projet**
- [ ] Créer repo `klassci-social`
- [ ] Laravel 12 fresh install
- [ ] Migrations BDD (users, posts, comments, votes)
- [ ] Seeders données test

**Semaines 3-4 : Authentification SSO**
- [ ] Génération JWT token depuis tenant
- [ ] Validation JWT dans social
- [ ] Sync profils automatique (étudiants + enseignants)

**Semaines 5-8 : Posts & Feed**
- [ ] CRUD posts (text, image)
- [ ] Système votes (upvote/downvote)
- [ ] Feed chronologique
- [ ] Filtre par communauté

**Semaines 9-12 : Commentaires & Interactions**
- [ ] Comments threadés (3 niveaux)
- [ ] Système follow (users + communautés)
- [ ] Hashtags
- [ ] Recherche basique

**Semaines 13-16 : Polish & Tests**
- [ ] Tests unitaires
- [ ] Tests E2E
- [ ] Performance optimization (cache Redis)
- [ ] Déploiement production `social.klassci.com`

---

## 📊 Métriques de Succès

**Technique** :
- ✅ API tenant response time < 200ms
- ✅ Feed loading < 500ms
- ✅ Cache hit ratio > 80%
- ✅ 99% uptime

**Produit** :
- 🎯 50% étudiants actifs créent compte (mois 1)
- 🎯 3 posts/semaine en moyenne
- 🎯 20% taux engagement (likes/comments)
- 🎯 Rétention 30 jours > 40%

---

## 📞 Contact & Support

**Équipe technique** : KLASSCI Development Team
**Documentation** : https://docs.klassci.com
**Repo** : `klassci-social` (privé)

---

*Document créé le 2 novembre 2025 - Version 1.0*
