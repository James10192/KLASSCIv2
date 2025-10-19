# API LMS-KLASSCI - Résumé des Corrections

**Date:** 19 octobre 2025
**Branch:** presentation
**Status:** ✅ Corrections complétées

---

## 📋 Contexte

L'API LMS-KLASSCI permet à un système de Learning Management System (LMS) externe de consommer les données KLASSCI via des endpoints REST protégés par Laravel Sanctum, sans dupliquer les utilisateurs ni les données.

**Problèmes initiaux identifiés:**

1. ❌ Utilisation de la table obsolète `esbtp_classe_matiere` pour récupérer les matières par classe
2. ❌ Pas de vérification que les étudiants ont une inscription **active** dans l'**année courante** (`is_current=true`)
3. ❌ Confusion entre les relations directes (obsolètes) et les relations via **combinaisons globales** (filière + niveau)
4. ❌ Manque de validation empêchant les étudiants sans inscription active de se connecter au LMS

---

## ✅ Corrections Appliquées

### 1. AuthController - Validation Inscription Étudiante

**Fichier:** `app/Http/Controllers/API/AuthController.php`

**Méthode:** `login()` (lignes 125-138)

**Problème:** Les étudiants sans inscription active dans l'année courante pouvaient se connecter au LMS.

**Solution:** Ajout d'une vérification qui bloque la connexion si `getEtudiantData()` retourne un tableau vide.

```php
// VÉRIFIER que l'étudiant a une inscription active pour l'année courante
$etudiantData = $this->getEtudiantData($user);

if (empty($etudiantData)) {
    Auth::logout();
    return $this->errorResponse(
        'Vous n\'êtes pas encore réinscrit pour l\'année universitaire en cours. Veuillez contacter le secrétariat pour procéder à votre réinscription.',
        ['code' => 'NO_ACTIVE_ENROLLMENT'],
        403
    );
}
```

**Impact:**
- ✅ Les étudiants doivent avoir une inscription **active** (`status='active'`) dans l'**année courante** (`is_current=true`)
- ✅ Message clair renvoyé au LMS avec code d'erreur `NO_ACTIVE_ENROLLMENT`
- ✅ HTTP 403 Forbidden au lieu de 200 OK avec données vides

---

### 2. LMSDataController - Méthode `matieres()`

**Fichier:** `app/Http/Controllers/API/LMSDataController.php`

**Méthode:** `matieres()` (lignes 52-138)

**Problème:** N'utilisait pas les **tables pivot globales** pour récupérer les combinaisons filière+niveau.

**Solution:** Utilisation des relations `filieres` (many-to-many) et `niveaux` (many-to-many) au lieu de la relation obsolète `filiere` (belongsTo).

```php
// Base query avec relations - Utilise les tables pivot globales (filiere + niveau)
$query = ESBTPMatiere::with([
    'filiere',  // Relation BelongsTo (unique) - OBSOLÈTE mais conservée pour compatibilité
    'niveauEtude',  // Relation BelongsTo (unique) - OBSOLÈTE mais conservée
    'filieres',  // ✅ Relation BelongsToMany (plusieurs via pivot esbtp_matiere_filiere)
    'niveaux',   // ✅ Relation BelongsToMany (plusieurs via pivot esbtp_matiere_niveau)
    'enseignants' => function ($q) use ($annee) {
        $q->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
          ->where('esbtp_enseignant_matiere.is_active', true);
    }
])->where('esbtp_matieres.is_active', true);

// Pour chaque matière, calculer TOUTES les combinaisons possibles
$combinaisons = [];
foreach ($matiere->filieres as $filiere) {
    foreach ($matiere->niveaux as $niveau) {
        $combinaisons[] = [
            'filiere_id' => $filiere->id,
            'filiere_nom' => $filiere->name,
            'filiere_code' => $filiere->code,
            'niveau_id' => $niveau->id,
            'niveau_nom' => $niveau->name,
            'niveau_code' => $niveau->code,
        ];
    }
}

$matiereFormatted['combinaisons'] = $combinaisons; // ✅ TOUTES les combinaisons
```

**Impact:**
- ✅ Chaque matière retourne maintenant un tableau `combinaisons` avec TOUTES les paires `(filiere_id, niveau_id)` valides
- ✅ Le LMS peut filtrer les matières disponibles pour une classe en cherchant la combinaison `(classe.filiere_id, classe.niveau_id)`
- ✅ Plus besoin de la table obsolète `esbtp_classe_matiere`

**Exemple de réponse:**

```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "nom": "Mathématiques",
      "code": "MATH101",
      "combinaisons": [
        {
          "filiere_id": 1,
          "filiere_nom": "Génie Civil",
          "filiere_code": "GC",
          "niveau_id": 3,
          "niveau_nom": "Licence 3",
          "niveau_code": "L3"
        },
        {
          "filiere_id": 2,
          "filiere_nom": "Génie Electrique",
          "filiere_code": "GE",
          "niveau_id": 3,
          "niveau_nom": "Licence 3",
          "niveau_code": "L3"
        }
      ]
    }
  ]
}
```

---

### 3. LMSDataController - Méthode `classes()`

**Fichier:** `app/Http/Controllers/API/LMSDataController.php`

**Méthode:** `classes()` (lignes 140-269)

**Problème:** Utilisait la table obsolète `esbtp_classe_matiere` pour récupérer les matières disponibles par classe.

**Solution:** Requête via combinaisons globales avec `whereHas('filieres')` ET `whereHas('niveaux')`.

```php
// ❌ AVANT (obsolète)
$matieres = $classe->matieres()->where('is_active', true)->get();

// ✅ APRÈS (correct - via combinaisons globales)
$matieres = ESBTPMatiere::where('is_active', true)
    ->whereHas('filieres', function ($q) use ($classe) {
        $q->where('esbtp_filieres.id', $classe->filiere_id);
    })
    ->whereHas('niveaux', function ($q) use ($classe) {
        $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
    })
    ->get();
```

**Impact:**
- ✅ Les matières disponibles pour une classe sont celles qui ont la combinaison `(classe.filiere_id, classe.niveau_id)`
- ✅ Cohérence avec la logique KLASSCI dans `ESBTPClasseController::show()` et `::matieres()`
- ✅ Coefficient identique pour toutes les classes avec même filière+niveau

**Exemple de réponse:**

```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "nom": "L3 GC - 2024/2025",
      "code": "L3-GC-2425",
      "filiere": {
        "id": 1,
        "nom": "Génie Civil",
        "code": "GC"
      },
      "niveau": {
        "id": 3,
        "nom": "Licence 3",
        "code": "L3"
      },
      "matieres_disponibles": [
        {
          "id": 42,
          "nom": "Mathématiques",
          "code": "MATH101",
          "coefficient": 3.0
        }
      ],
      "nb_etudiants": 25,
      "nb_matieres": 8
    }
  ]
}
```

---

### 4. LMSDataController - Méthode `etudiantsClasse()`

**Fichier:** `app/Http/Controllers/API/LMSDataController.php`

**Méthode:** `etudiantsClasse()` (lignes 271-365)

**Problème:** Ne filtrait pas correctement par `status='active'` ET `annee_universitaire_id=année_courante`.

**Solution:** Ajout des filtres manquants dans le `whereHas('inscriptions')`.

```php
// ✅ FILTRE CORRECT: année courante + status active + classe spécifique
$etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $annee) {
    $q->where('classe_id', $classeId)
      ->where('annee_universitaire_id', $annee->id)
      ->where('status', 'active');  // ✅ AJOUTÉ
})->with(['user', 'inscriptions' => function ($q) use ($annee, $classeId) {
    $q->where('annee_universitaire_id', $annee->id)
      ->where('classe_id', $classeId)
      ->where('status', 'active');  // ✅ AJOUTÉ
}])->get();
```

**Impact:**
- ✅ Seuls les étudiants avec **inscription active** dans l'**année courante** sont retournés
- ✅ Cohérence avec `ESBTPClasseController::show()` (ligne 268-271)
- ✅ Évite les étudiants transférés, suspendus, ou en réinscription

---

## 📊 Récapitulatif des Modifications

| Fichier | Méthode | Ligne(s) | Type de correction |
|---------|---------|----------|-------------------|
| `AuthController.php` | `login()` | 125-138 | ✅ Validation inscription étudiant |
| `LMSDataController.php` | `matieres()` | 52-138 | ✅ Utilisation pivot tables globales |
| `LMSDataController.php` | `classes()` | 207-220 | ✅ Matières via combinaisons globales |
| `LMSDataController.php` | `etudiantsClasse()` | 318-330 | ✅ Filtrage status='active' |

---

## 🧪 Tests à Effectuer

### Test 1: Connexion étudiant sans inscription active

**Requête:**
```bash
curl -X POST http://localhost:8001/api/lms/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "etudiant@test.com",
    "password": "password123"
  }'
```

**Résultat attendu si PAS d'inscription active:**
```json
{
  "success": false,
  "message": "Vous n'êtes pas encore réinscrit pour l'année universitaire en cours. Veuillez contacter le secrétariat pour procéder à votre réinscription.",
  "errors": {
    "code": "NO_ACTIVE_ENROLLMENT"
  }
}
```
**HTTP Code:** 403

---

### Test 2: Récupération matières avec combinaisons

**Requête:**
```bash
curl -X GET http://localhost:8001/api/lms/matieres \
  -H "Authorization: Bearer {TOKEN}"
```

**Vérifications:**
- ✅ Chaque matière a un champ `combinaisons` (array)
- ✅ Chaque combinaison contient `filiere_id`, `filiere_nom`, `niveau_id`, `niveau_nom`
- ✅ Une matière enseignée dans plusieurs filières/niveaux a plusieurs combinaisons

---

### Test 3: Matières disponibles pour une classe

**Requête:**
```bash
curl -X GET http://localhost:8001/api/lms/classes \
  -H "Authorization: Bearer {TOKEN}"
```

**Vérifications:**
- ✅ Chaque classe a un champ `matieres_disponibles` (array)
- ✅ Les matières listées ont la combinaison `(classe.filiere_id, classe.niveau_id)` dans leurs combinaisons
- ✅ Pas de matières d'autres filières/niveaux

---

### Test 4: Étudiants d'une classe

**Requête:**
```bash
curl -X GET http://localhost:8001/api/lms/classes/15/etudiants \
  -H "Authorization: Bearer {TOKEN}"
```

**Vérifications:**
- ✅ Tous les étudiants retournés ont `inscriptions[0].status = 'active'`
- ✅ Tous ont `inscriptions[0].annee_universitaire_id = année_courante.id`
- ✅ Pas d'étudiants transférés, suspendus, ou en attente

---

## 📖 Documentation Endpoints

### POST `/api/lms/auth/login`

**Paramètres:**
```json
{
  "username": "email@example.com",  // ou nom d'utilisateur
  "password": "mot_de_passe",
  "remember": false  // optionnel
}
```

**Réponse succès (200):**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": 42,
      "nom": "Jean DUPONT",
      "email": "jean@example.com",
      "role": "etudiant",
      "etudiant_data": {
        "etudiant_id": 15,
        "matricule": "ESB2024001",
        "inscription_id": 105,
        "classe": {
          "id": 15,
          "nom": "L3 GC - 2024/2025"
        },
        "statut_inscription": "active"
      }
    }
  }
}
```

**Réponse erreur - Pas d'inscription active (403):**
```json
{
  "success": false,
  "message": "Vous n'êtes pas encore réinscrit pour l'année universitaire en cours. Veuillez contacter le secrétariat pour procéder à votre réinscription.",
  "errors": {
    "code": "NO_ACTIVE_ENROLLMENT"
  }
}
```

---

### GET `/api/lms/matieres`

**Headers:**
```
Authorization: Bearer {token}
```

**Réponse (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "nom": "Mathématiques",
      "code": "MATH101",
      "description": "Mathématiques appliquées",
      "coefficient": 3.0,
      "heures_totales": 60,
      "combinaisons": [
        {
          "filiere_id": 1,
          "filiere_nom": "Génie Civil",
          "filiere_code": "GC",
          "niveau_id": 3,
          "niveau_nom": "Licence 3",
          "niveau_code": "L3"
        }
      ],
      "enseignants": [
        {
          "id": 5,
          "nom": "Prof. KOUASSI"
        }
      ]
    }
  ],
  "meta": {
    "total": 45,
    "annee_universitaire": "2024/2025"
  }
}
```

---

### GET `/api/lms/classes`

**Headers:**
```
Authorization: Bearer {token}
```

**Filtres optionnels:**
- `?filiere_id=1` - Filtrer par filière
- `?niveau_id=3` - Filtrer par niveau
- `?search=GC` - Recherche texte

**Réponse (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "nom": "L3 GC - 2024/2025",
      "code": "L3-GC-2425",
      "filiere": {
        "id": 1,
        "nom": "Génie Civil",
        "code": "GC"
      },
      "niveau": {
        "id": 3,
        "nom": "Licence 3",
        "code": "L3"
      },
      "annee_universitaire": {
        "id": 5,
        "nom": "2024/2025",
        "is_current": true
      },
      "matieres_disponibles": [
        {
          "id": 42,
          "nom": "Mathématiques",
          "code": "MATH101",
          "coefficient": 3.0,
          "heures_totales": 60
        }
      ],
      "nb_etudiants": 25,
      "nb_matieres": 8,
      "places_disponibles": 5
    }
  ],
  "meta": {
    "total": 12,
    "annee_universitaire": "2024/2025"
  }
}
```

---

### GET `/api/lms/classes/{classeId}/etudiants`

**Headers:**
```
Authorization: Bearer {token}
```

**Réponse (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "matricule": "ESB2024001",
      "nom_complet": "Jean DUPONT",
      "user": {
        "id": 42,
        "email": "jean@example.com",
        "is_active": true
      },
      "inscription": {
        "id": 105,
        "status": "active",
        "date_inscription": "2024-09-01"
      }
    }
  ],
  "meta": {
    "classe_id": 15,
    "classe_nom": "L3 GC - 2024/2025",
    "total_etudiants": 25
  }
}
```

---

## 🔒 Sécurité

### Authentification

- **Type:** Bearer Token (Laravel Sanctum)
- **Header:** `Authorization: Bearer {token}`
- **Scope:** `lms:access`

### Roles autorisés

- ✅ `enseignant` - Accès aux classes enseignées
- ✅ `coordinateur` - Accès complet
- ✅ `superAdmin` - Accès complet
- ✅ `etudiant` - Accès aux données personnelles uniquement

### Restrictions

- ❌ Les `secretaire` n'ont PAS accès au LMS
- ❌ Les étudiants sans inscription active ne peuvent pas se connecter
- ❌ Les utilisateurs inactifs (`is_active=false`) ne peuvent pas se connecter

---

## 🎯 Règles Métier Appliquées

### 1. Inscription Active Obligatoire

> **Règle:** Un étudiant ne peut accéder au LMS QUE s'il a une inscription avec `status='active'` dans l'année universitaire `is_current=true`.

**Implications:**
- Empêche les anciens étudiants d'accéder au LMS après leur diplôme
- Empêche les étudiants en attente de réinscription d'accéder au LMS
- Empêche les étudiants transférés/suspendus d'accéder au LMS

### 2. Matières via Combinaisons Globales

> **Règle:** Les matières sont disponibles pour TOUTES les classes ayant la combinaison `(filiere_id, niveau_id)` correspondante.

**Implications:**
- Un coefficient défini pour "Math L3 GC" s'applique à TOUTES les classes L3 GC
- Pas de configuration individuelle par classe (sauf `esbtp_config_matieres` pour bulletins)
- Cohérence entre toutes les classes de même filière+niveau

### 3. Année Universitaire Courante Uniquement

> **Règle:** L'API ne retourne QUE les données de l'année universitaire avec `is_current=true`.

**Implications:**
- Pas d'accès aux données historiques via l'API LMS
- Données historiques accessibles uniquement via KLASSCI directement
- Simplification de la logique LMS (pas besoin de gérer plusieurs années)

---

## 📝 Notes Techniques

### Tables Pivot Utilisées

| Table | Type | Usage | Status |
|-------|------|-------|--------|
| `esbtp_matiere_filiere` | Many-to-Many | Matières disponibles par filière | ✅ Actuelle |
| `esbtp_matiere_niveau` | Many-to-Many | Matières disponibles par niveau | ✅ Actuelle |
| `esbtp_classe_matiere` | Many-to-Many | Matières par classe individuelle | ❌ OBSOLÈTE |
| `esbtp_config_matieres` | One-to-Many | Configuration bulletins (coefficients/périodes/enseignants) | ✅ Actuelle (bulletins uniquement) |

### Relations Eloquent

```php
// ✅ CORRECT - Utiliser ces relations
ESBTPMatiere::with(['filieres', 'niveaux']);  // Many-to-many global

// ❌ INCORRECT - Ne plus utiliser
ESBTPClasse::with(['matieres']);  // Many-to-many obsolète
ESBTPMatiere::with(['classes']);  // Many-to-many obsolète
```

### Logique de Filtrage

```php
// Pattern correct pour récupérer matières d'une classe
$matieres = ESBTPMatiere::where('is_active', true)
    ->whereHas('filieres', function ($q) use ($classe) {
        $q->where('esbtp_filieres.id', $classe->filiere_id);
    })
    ->whereHas('niveaux', function ($q) use ($classe) {
        $q->where('esbtp_niveau_etudes.id', $classe->niveau_etude_id);
    })
    ->get();

// Pattern correct pour récupérer étudiants d'une classe
$etudiants = ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classeId, $annee) {
    $q->where('classe_id', $classeId)
      ->where('annee_universitaire_id', $annee->id)
      ->where('status', 'active');
})->get();
```

---

## ✅ Checklist de Validation

- [x] AuthController bloque les étudiants sans inscription active
- [x] LMSDataController::matieres() retourne les combinaisons globales
- [x] LMSDataController::classes() récupère matières via combinaisons
- [x] LMSDataController::etudiantsClasse() filtre par status='active'
- [x] Suppression de toutes les références à `esbtp_classe_matiere` (obsolète)
- [x] Documentation mise à jour avec exemples de réponses
- [x] Cohérence avec la logique KLASSCI (ESBTPClasseController)
- [ ] Tests manuels effectués (nécessite application en cours d'exécution)
- [ ] Tests automatisés créés (à faire)

---

## 🚀 Prochaines Étapes

1. **Tests manuels:**
   - Démarrer `php artisan serve --port=8001`
   - Tester avec Postman/Insomnia les 4 endpoints principaux
   - Vérifier les réponses JSON correspondent à la documentation

2. **Tests automatisés (recommandé):**
   ```bash
   php artisan make:test LMSAuthenticationTest
   php artisan make:test LMSMatieresTest
   php artisan make:test LMSClassesTest
   php artisan make:test LMSEtudiantsTest
   ```

3. **Documentation LMS:**
   - Fournir cette documentation au développeur LMS
   - Créer des exemples de code côté LMS (fetch/axios)
   - Documenter le workflow complet de consommation API

4. **Monitoring:**
   - Ajouter logs pour tracking des appels API
   - Mettre en place rate limiting (throttle middleware)
   - Monitorer les erreurs 403 (NO_ACTIVE_ENROLLMENT)

---

**Auteur:** Claude Code
**Validé par:** [À remplir]
**Date de mise en production:** [À remplir]
