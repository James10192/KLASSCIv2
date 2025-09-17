# 🔧 Référence Technique APIs LMS-KLASSCI

## 📁 Architecture des Fichiers

### **Contrôleurs Créés**
```
app/Http/Controllers/API/
├── BaseApiController.php      # Contrôleur de base avec logique commune
├── AuthController.php         # Authentification LMS
├── LMSDataController.php      # APIs de lecture (GET)
└── LMSWriteController.php     # APIs d'écriture (POST/PUT)
```

### **Routes API**
```
routes/api.php                 # Routes LMS ajoutées (/api/lms/*)
```

### **Modèles Utilisés**
```
app/Models/
├── ESBTPMatiere.php           # Matières et cours
├── ESBTPEvaluation.php        # Évaluations
├── ESBTPNote.php              # Notes des étudiants
├── ESBTPAttendance.php        # Présences étudiants
├── ESBTPEnseignantPresence.php # Présences enseignants
├── ESBTPSeanceCours.php       # Séances de cours
└── ESBTPAnneeUniversitaire.php # Année universitaire
```

---

## 🏗️ BaseApiController - Logique Commune

### **Fonctionnalités Principales**

1. **Réponses Standardisées**
   ```php
   protected function successResponse($data = null, string $message = '', array $meta = [], int $statusCode = 200)
   protected function errorResponse(string $message, array $errors = [], int $statusCode = 400)
   ```

2. **Métadonnées Automatiques**
   ```php
   protected function getBaseMeta(): array
   // Retourne : timestamp, api_version, annee_courante, user_context
   ```

3. **Filtrage par Rôle**
   ```php
   protected function applyRoleFilters($query, string $context = '')
   // Applique automatiquement les filtres selon le rôle (enseignant/étudiant/coordinateur)
   ```

4. **Gestion des Permissions**
   ```php
   protected function checkRoleAccess($roles): ?JsonResponse
   // Vérifie les rôles requis avant l'accès
   ```

### **Logique de Filtrage par Rôle**

#### **Coordinateur**
- Accès complet à toutes les données de l'année courante
- Aucun filtre appliqué

#### **Enseignant**
- **Matières :** Seulement celles qu'il enseigne cette année
- **Évaluations :** Seulement celles de ses matières
- **Classes :** Celles où il enseigne
- **Étudiants :** Ceux de ses classes

#### **Étudiant**
- **Matières :** Celles de sa classe
- **Évaluations :** Celles de sa classe
- **Classes :** Sa classe uniquement
- **Emploi du temps :** Son planning

---

## 🔐 AuthController - Authentification

### **Endpoints Disponibles**

| Méthode | Endpoint | Description | Middleware |
|---------|----------|-------------|------------|
| POST | `/api/lms/auth/login` | Connexion | Aucun |
| GET | `/api/lms/auth/me` | Profil utilisateur | auth:sanctum |
| POST | `/api/lms/auth/logout` | Déconnexion | auth:sanctum |
| GET | `/api/lms/auth/check` | Vérification token | auth:sanctum |

### **Logique de Connexion**

```php
public function login(Request $request): JsonResponse
{
    // 1. Validation des données (email, password)
    // 2. Tentative de connexion Laravel Auth
    // 3. Vérification compte actif
    // 4. Vérification rôles autorisés (enseignant/coordinateur/etudiant)
    // 5. Génération token Sanctum avec scope 'lms:access'
    // 6. Retour données utilisateur + contexte métier
}
```

### **Données Contextuelles**

#### **Pour Enseignant**
```php
private function getEnseignantData(User $user): array
{
    // Retourne : nb_matieres, nb_classes, matieres_principales, classes_enseignees
}
```

#### **Pour Étudiant**
```php
private function getEtudiantData(User $user): array
{
    // Retourne : etudiant_id, matricule, inscription_id, classe, statut_inscription
}
```

---

## 📖 LMSDataController - APIs de Lecture

### **Endpoints Principaux**

| Endpoint | Rôles | Filtres | Description |
|----------|-------|---------|-------------|
| `GET /api/lms/structure` | Tous | Aucun | Filières et niveaux |
| `GET /api/lms/matieres` | Tous | Rôle, filiere_id, niveau_id | Matières accessibles |
| `GET /api/lms/classes` | Tous | Rôle, filiere_id, niveau_id | Classes année courante |
| `GET /api/lms/classes/{id}/etudiants` | Ens/Coord | Rôle | Étudiants d'une classe |
| `GET /api/lms/emploi-temps` | Tous | Rôle, dates | Planning filtré |
| `GET /api/lms/evaluations` | Tous | Rôle, matiere_id, status | Évaluations programmées |

### **Exemple d'Implémentation - Matières**

```php
public function matieres(Request $request): JsonResponse
{
    $annee = $this->getAnneeCouraante(); // Année courante uniquement

    $query = ESBTPMatiere::with([
        'filiere', 'niveauEtude', 'classes', 'enseignants'
    ])->where('is_active', true);

    // Application automatique des filtres de rôle
    $query = $this->applyRoleFilters($query, 'matieres');

    // Filtres optionnels de la requête
    if ($request->has('filiere_id')) {
        $query->where('filiere_id', $request->filiere_id);
    }

    $matieres = $query->get();

    // Formatage pour le LMS
    $data = $matieres->map(function ($matiere) {
        return [
            'id' => $matiere->id,
            'nom' => $matiere->nom,
            // ... autres champs
            'lms_metadata' => [
                'has_online_courses' => false,
                'total_evaluations' => $matiere->evaluations()->count()
            ]
        ];
    });

    return $this->successResponse($data, 'Matières récupérées');
}
```

### **Logique de Relations**

Les requêtes utilisent Eloquent avec eager loading pour optimiser les performances :

```php
// Exemple : Classes avec relations
ESBTPClasse::with([
    'filiere',                    // Filière de la classe
    'niveau',                     // Niveau d'étude
    'matieres' => function ($q) { // Matières actives seulement
        $q->where('is_active', true);
    }
])->where('is_active', true)
  ->whereHas('inscriptions', function ($q) use ($annee) {
      $q->where('annee_universitaire_id', $annee->id)
        ->where('status', 'active');  // Seulement classes avec inscriptions actives
  });
```

---

## ✏️ LMSWriteController - APIs d'Écriture

### **Endpoints Principaux**

| Endpoint | Méthode | Rôles | Description |
|----------|---------|-------|-------------|
| `/api/lms/evaluations/{id}/notes` | POST | Ens/Coord | Sauvegarder notes |
| `/api/lms/cours/{id}/presences` | POST | Ens/Coord | Enregistrer présences |
| `/api/lms/cours/{id}/statut` | PUT | Ens/Coord | Mettre à jour statut cours |

### **Sauvegarde des Notes - Logique Détaillée**

```php
public function saveEvaluationNotes(Request $request, int $evaluationId): JsonResponse
{
    // 1. Vérification des permissions
    $roleCheck = $this->checkRoleAccess(['enseignant', 'coordinateur']);

    // 2. Validation de l'évaluation
    $evaluation = ESBTPEvaluation::find($evaluationId);

    // 3. Vérification droits enseignant (si rôle enseignant)
    if (auth()->user()->hasRole('enseignant')) {
        $hasAccess = $evaluation->matiere->enseignants()
            ->where('enseignant_id', auth()->id())
            ->exists();
    }

    // 4. Validation des données
    $validator = Validator::make($request->all(), [
        'notes' => 'required|array|min:1',
        'notes.*.etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
        'notes.*.note' => 'nullable|numeric|min:0|max:' . $evaluation->bareme,
        'notes.*.is_absent' => 'boolean',
        // ...
    ]);

    // 5. Transaction base de données
    DB::beginTransaction();
    try {
        foreach ($notesData as $noteData) {
            // Vérification appartenance étudiant à la classe
            $etudiant = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($evaluation) {
                $q->where('classe_id', $evaluation->classe_id)
                  ->where('annee_universitaire_id', $evaluation->annee_universitaire_id)
                  ->where('status', 'active');
            })->find($etudiantId);

            // Création/mise à jour note
            ESBTPNote::updateOrCreate([
                'evaluation_id' => $evaluation->id,
                'etudiant_id' => $etudiantId,
            ], [
                'note' => $valeurNote,
                'is_absent' => $isAbsent,
                // ...
            ]);
        }

        // Mise à jour statut évaluation
        $evaluation->update(['status' => ESBTPEvaluation::STATUS_COMPLETED]);

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        // Gestion erreur
    }
}
```

### **Enregistrement des Présences**

```php
public function saveCourseAttendance(Request $request, int $coursId): JsonResponse
{
    // 1. Récupération du cours
    $cours = ESBTPSeanceCours::find($coursId);

    // 2. Vérification permissions enseignant
    if (auth()->user()->hasRole('enseignant') && $cours->enseignant_id !== auth()->id()) {
        return $this->errorResponse('Accès non autorisé', [], 403);
    }

    // 3. Mise à jour informations cours
    $cours->update([
        'statut' => 'realise',
        'type_cours' => 'visio',
        // ...
    ]);

    // 4. Suppression anciennes présences
    ESBTPAttendance::where('seance_cours_id', $cours->id)->delete();

    // 5. Création nouvelles présences
    foreach ($tousEtudiants as $etudiant) {
        if (in_array($etudiant->id, $etudiantsPresents)) {
            ESBTPAttendance::create([
                'etudiant_id' => $etudiant->id,
                'seance_cours_id' => $cours->id,
                'statut' => 'present',
                'call_type' => 'lms_online',
                // ...
            ]);
        }
    }

    // 6. Présence enseignant
    if ($enseignantPresent) {
        ESBTPEnseignantPresence::updateOrCreate([
            'enseignant_id' => $cours->enseignant_id,
            'seance_cours_id' => $cours->id
        ], [
            'statut' => 'present',
            // ...
        ]);
    }
}
```

---

## 🛡️ Sécurité et Validations

### **Middleware Appliqués**

1. **auth:sanctum** - Toutes les routes sauf login
2. **Validation des rôles** - Dans BaseApiController
3. **Validation des données** - Validator Laravel
4. **Vérification propriété** - Enseignant peut modifier seulement ses cours/évaluations

### **Exemple de Validation**

```php
// Validation complexe pour les notes
$validator = Validator::make($request->all(), [
    'notes' => 'required|array|min:1',
    'notes.*.etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
    'notes.*.note' => 'nullable|numeric|min:0|max:' . ($evaluation->bareme ?? 20),
    'notes.*.is_absent' => 'boolean',
    'notes.*.commentaire' => 'nullable|string|max:500',
    'date_saisie' => 'nullable|date',
    'commentaire_general' => 'nullable|string|max:1000'
]);

// Validation métier personnalisée
if (!$isAbsent && $valeurNote === null) {
    $erreurs[] = "Note manquante pour l'étudiant {$etudiant->matricule} (non absent)";
}
```

### **Logs de Sécurité**

Tous les événements sensibles sont loggés :

```php
\Log::info('Notes saisies depuis LMS', [
    'evaluation_id' => $evaluation->id,
    'enseignant_id' => auth()->id(),
    'nb_notes' => count($notesSauvegardees),
    'source' => 'LMS'
]);
```

---

## 🔧 Configuration et Déploiement

### **Configuration Laravel Sanctum**

Assurez-vous que Sanctum est configuré dans `config/sanctum.php` :

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),

'guard' => ['web'],

'expiration' => null, // Tokens n'expirent pas par défaut
```

### **Variables d'Environnement**

```env
# API Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,lms.school.com
APP_URL=https://klassci.school.com

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://lms.school.com,http://localhost:3000
```

### **Publication des Assets**

```bash
# Publication des migrations Sanctum (si pas déjà fait)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Migration des tables
php artisan migrate

# Cache des routes
php artisan route:cache

# Cache de configuration
php artisan config:cache
```

---

## 🚀 Tests et Validation

### **Tests d'APIs Recommandés**

1. **Test d'Authentification**
   ```bash
   curl -X POST https://klassci.school.com/api/lms/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"test@school.com","password":"password"}'
   ```

2. **Test de Récupération de Données**
   ```bash
   curl -X GET https://klassci.school.com/api/lms/matieres \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Test de Sauvegarde de Notes**
   ```bash
   curl -X POST https://klassci.school.com/api/lms/evaluations/1/notes \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"notes":[{"etudiant_id":1,"note":15,"is_absent":false}]}'
   ```

### **Validation des Permissions**

Testez avec différents types d'utilisateurs :
- Enseignant : Accès limité à ses matières
- Coordinateur : Accès complet
- Étudiant : Accès lecture seule à ses données

---

## 📊 Monitoring et Performance

### **Métriques à Surveiller**

1. **Performance des requêtes**
   - Temps de réponse moyen
   - Requêtes les plus lentes
   - Nombre de requêtes par minute

2. **Utilisation de l'API**
   - Endpoints les plus utilisés
   - Erreurs par type (401, 403, 500)
   - Pic d'utilisation

3. **Intégration LMS**
   - Fréquence de synchronisation
   - Succès/échecs des sauvegardes
   - Volume de données échangées

### **Optimisations Appliquées**

1. **Eager Loading** - Relations chargées en une requête
2. **Filtrage intelligent** - Seules les données nécessaires
3. **Cache des métadonnées** - Année courante mise en cache
4. **Pagination** - Pour les grandes listes (à implémenter si nécessaire)

---

## 🐛 Débogage et Dépannage

### **Logs à Consulter**

```bash
# Logs Laravel généraux
tail -f storage/logs/laravel.log

# Filtrer les logs LMS
grep "LMS\|API\|Sanctum" storage/logs/laravel.log

# Logs d'erreurs uniquement
grep "ERROR" storage/logs/laravel.log | grep "LMS"
```

### **Commandes de Debug**

```bash
# Vérifier les routes API
php artisan route:list --path=api/lms

# Vérifier la configuration Sanctum
php artisan tinker
>>> config('sanctum')

# Tester l'authentification
php artisan tinker
>>> $user = App\Models\User::find(1)
>>> $token = $user->createToken('test')->plainTextToken
>>> echo $token
```

### **Erreurs Communes et Solutions**

1. **401 Unauthorized**
   - Vérifier le token dans l'en-tête Authorization
   - Vérifier que Sanctum est configuré
   - Vérifier que l'utilisateur existe et est actif

2. **403 Forbidden**
   - Vérifier les rôles de l'utilisateur
   - Vérifier les permissions sur la ressource demandée

3. **422 Unprocessable Entity**
   - Vérifier les données envoyées
   - Consulter les erreurs de validation retournées

4. **500 Internal Server Error**
   - Consulter les logs Laravel
   - Vérifier la configuration de la base de données
   - Vérifier les relations Eloquent

Cette documentation technique couvre tous les aspects de l'implémentation des APIs LMS-KLASSCI.