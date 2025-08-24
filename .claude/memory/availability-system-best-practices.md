# Guide des Bonnes Pratiques - Système de Disponibilités ESBTP

**Date:** 20 août 2025  
**Objectif:** Éviter les incohérences lors du développement de nouvelles fonctionnalités liées aux disponibilités

## 🎯 Règles Fondamentales

### 1. Cohérence des Données d'Affichage

**RÈGLE ABSOLUE :** Toutes les pages affichant des disponibilités DOIVENT utiliser la même méthode de préparation des données.

```php
// ✅ CORRECT - Utiliser la méthode standardisée
$availabilityData = $this->prepareAvailabilityData($teacher);

// ❌ INCORRECT - Créer sa propre logique de formatage
$availabilityData = [];
foreach ($teacher->availabilities as $avail) {
    $key = $avail->day_of_week . '_' . $hour;
    $availabilityData[$key] = $avail->availability_type; // Format incohérent
}
```

### 2. Format Standard des Données

**Format obligatoire :** `$availability[$day][$hourIndex]`

```php
// Structure standard attendue
$availability = [
    'monday' => ['preferred', 'preferred', 'unavailable', ...], // 11 éléments (8h-18h)
    'tuesday' => ['unavailable', 'available', 'available', ...],
    // ... jusqu'à 'saturday' (exclure dimanche)
];
```

### 3. Gestion des Créneaux Temporels

**Principe :** Les données DB peuvent contenir des créneaux de durées variables (1h, 2h, etc.), mais l'affichage DOIT TOUJOURS être en créneaux de 1h.

```php
// Exemple de décomposition correcte
// DB: "8h-10h preferred" → Affichage: [8h=preferred, 9h=preferred]
for ($hour = $startHour; $hour < $endHour; $hour++) {
    $hourIndex = $hour - 8; // 8h = index 0
    $availability[$dayName][$hourIndex] = $avail->availability_type;
}
```

## 📋 Checklist de Développement

### Avant de Créer une Nouvelle Page de Disponibilités

- [ ] Vérifier si `prepareAvailabilityData()` existe dans le contrôleur cible
- [ ] Si non, copier la méthode depuis `ESBTPEnseignantController`
- [ ] Adapter les jours si nécessaire (inclure/exclure dimanche)
- [ ] Utiliser le format de vue standardisé avec `$availability[$day][$index]`

### Lors de Modifications des Pages Existantes

- [ ] Tester sur TOUTES les pages affichant des disponibilités
- [ ] Vérifier que les créneaux de 2h sont bien décomposés en créneaux de 1h
- [ ] S'assurer que les grilles ont le même nombre de colonnes/lignes
- [ ] Valider avec des données réelles de test

### Tests de Validation Obligatoires

```bash
# Test de cohérence des données entre pages
php artisan tinker --execute="
// Comparer les données admin vs teacher pour le même enseignant
\$teacher = \App\Models\ESBTPTeacher::with('availabilities')->first();

// Données admin (référence)
\$adminData = /* logique ESBTPEnseignantController */;

// Données teacher  
\$teacherData = /* logique TeacherDashboardController */;

// Vérification: doivent être identiques
assert(\$adminData === \$teacherData, 'Incohérence détectée');
"
```

## 🏗️ Architecture de Référence

### Structure des Contrôleurs

```php
class AnyAvailabilityController extends Controller
{
    /**
     * MÉTHODE OBLIGATOIRE - Copier depuis ESBTPEnseignantController
     */
    private function prepareAvailabilityData($teacher)
    {
        $hours = range(8, 18); // Standard: 8h-18h
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        // Exclure dimanche si pas de travail le dimanche
        
        $availability = [];
        foreach ($days as $day) {
            $availability[$day] = array_fill(0, count($hours), 'unavailable');
        }
        
        foreach ($teacher->availabilities as $avail) {
            $dayName = $days[$avail->day_of_week] ?? null;
            $startHour = /* parser correctement */;
            $endHour = /* parser correctement */;
            
            // CRITIQUE: Décomposer les créneaux multi-heures
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $hourIndex = $hour - 8;
                if ($hourIndex >= 0 && $hourIndex < count($hours)) {
                    $availability[$dayName][$hourIndex] = $avail->availability_type;
                }
            }
        }
        
        return $availability;
    }
}
```

### Structure des Vues

```php
{{-- Template standard de grille de disponibilités --}}
@php
    $hours = range(8, 18);
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    $dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
@endphp

<div class="availability-grid">
    @foreach($hours as $index => $hour)
        <div class="availability-time-slot">{{ sprintf('%02d:00', $hour) }}</div>
        @foreach($days as $dayIndex => $day)
            @php
                $availabilityClass = $availabilityData[$day][$index] ?? 'unavailable';
            @endphp
            <div class="availability-slot {{ $availabilityClass }}">
                {{-- Contenu de la case --}}
            </div>
        @endforeach
    @endforeach
</div>
```

## ⚠️ Pièges à Éviter

### 1. Parsing Incorrect des Heures

```php
// ❌ PIÈGE - Parsing depuis le début de la string timestamp
$hour = (int) substr($availability->start_time, 0, 2); // Retourne "20" au lieu de "8"

// ✅ CORRECT - Parsing depuis la position de l'heure
$hour = (int) substr($availability->start_time, 11, 2); // Retourne "8"
```

### 2. Gestion Inconsistante du Dimanche

```php
// ❌ PIÈGE - Inclure dimanche sur certaines pages seulement
$days = ['monday', ..., 'sunday']; // Page A avec dimanche
$days = ['monday', ..., 'saturday']; // Page B sans dimanche

// ✅ CORRECT - Décision uniforme basée sur les règles métier
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
// Pas de dimanche car pas de travail le dimanche (règle ESBTP)
```

### 3. Format de Données Hétérogène

```php
// ❌ PIÈGE - Formats différents selon les pages
$data["$day_$hour"] = $type;           // Format A
$data[$day][$hourIndex] = $type;       // Format B

// ✅ CORRECT - Format unique pour toutes les pages
$data[$day][$hourIndex] = $type; // Format standardisé
```

## 🧪 Tests de Régression

### Script de Validation Automatique

```bash
#!/bin/bash
# tests/availability-consistency-check.sh

echo "=== TEST DE COHÉRENCE DES DISPONIBILITÉS ==="

php artisan tinker --execute="
// Test avec plusieurs enseignants
\$teachers = \App\Models\ESBTPTeacher::with('availabilities')->take(3)->get();

foreach (\$teachers as \$teacher) {
    echo 'Testing teacher: ' . \$teacher->user->name;
    
    // Simuler données admin
    \$adminController = new \App\Http\Controllers\ESBTPEnseignantController();
    \$adminData = /* ... */;
    
    // Simuler données teacher
    \$teacherController = new \App\Http\Controllers\TeacherDashboardController();
    \$teacherData = /* ... */;
    
    // Comparer
    if (\$adminData !== \$teacherData) {
        echo 'ERREUR: Incohérence détectée pour ' . \$teacher->user->name;
        exit(1);
    }
}

echo 'SUCCÈS: Toutes les données sont cohérentes';
"
```

## 📚 Références Techniques

### Modèle de Données

```sql
-- Table de référence
CREATE TABLE esbtp_teacher_availabilities (
    id BIGINT PRIMARY KEY,
    teacher_id BIGINT,
    day_of_week TINYINT, -- 0=Lundi, 1=Mardi, ..., 5=Samedi, 6=Dimanche
    start_time TIME,     -- Peut être 08:00, 10:00, 14:00, etc.
    end_time TIME,       -- Peut être 10:00, 12:00, 16:00, etc. (créneaux variables)
    availability_type ENUM('available', 'preferred', 'unavailable'),
    UNIQUE KEY teacher_availability_unique (teacher_id, day_of_week, start_time)
);
```

### Mapping Jour de Semaine

```php
// Standard ESBTP
$dayMapping = [
    0 => 'monday',    // Lundi
    1 => 'tuesday',   // Mardi  
    2 => 'wednesday', // Mercredi
    3 => 'thursday',  // Jeudi
    4 => 'friday',    // Vendredi
    5 => 'saturday',  // Samedi
    6 => 'sunday'     // Dimanche (généralement exclu)
];
```

### Heures Standard

```php
// Créneaux de travail ESBTP
$workingHours = range(8, 18); // 8h00 à 18h00 (11 créneaux d'1h)
// Index 0 = 8h, Index 1 = 9h, ..., Index 10 = 18h
```

## 🔄 Processus de Maintenance

### Lors d'Ajout de Nouvelles Pages

1. **Copier** la méthode `prepareAvailabilityData()` 
2. **Adapter** les jours selon les besoins métier
3. **Tester** avec des données réelles
4. **Valider** la cohérence avec les pages existantes
5. **Documenter** les éventuelles variations

### Lors de Modifications des Données

1. **Impact Analysis** : Lister toutes les pages affectées
2. **Test Complet** : Valider chaque page impactée  
3. **Mise à Jour Documentation** : Refléter les changements
4. **Tests de Régression** : Vérifier la non-régression

---

## 🎯 Résumé

**3 règles d'or pour éviter les incohérences :**

1. 🔧 **Une seule méthode** de préparation des données : `prepareAvailabilityData()`
2. 📊 **Un seul format** d'affichage : `$availability[$day][$hourIndex]`
3. 🧪 **Tests systématiques** de cohérence entre toutes les pages

**En respectant ces règles, les futures extensions du système de disponibilités seront robustes et cohérentes.**

---

*Document créé suite à la résolution de l'incohérence teacher self-service vs admin pages - 20 août 2025*