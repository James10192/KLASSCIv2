# Documentation du BulletinService

## Vue d'ensemble

Le `BulletinService` est un service centralisé qui gère la génération des données de bulletins scolaires pour le système ESBTP. Il garantit la cohérence entre la preview web et l'export PDF en utilisant une logique unique et partagée.

## Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Controller    │────│  BulletinService │────│   Template      │
│                 │    │                  │    │  (pdf-config)   │
│ - Preview       │    │ - Calculs moyens │    │                 │
│ - Export PDF    │    │ - Statistiques   │    │ - Affichage     │
│                 │    │ - Configuration  │    │ - Formatage     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## Fonctionnalités principales

### 1. Génération unifiée des données
- **Une seule source de vérité** pour les calculs de bulletin
- **Cohérence garantie** entre preview et PDF export
- **Logique centralisée** facile à maintenir et déboguer

### 2. Gestion des moyennes hybrides
- **Moyennes automatiques** : calculées à partir des évaluations
- **Moyennes manuelles** : saisies directement (priorité absolue)
- **Règle "Manuel prend le dessus"** : les moyennes manuelles écrasent toujours les automatiques

### 3. Calcul des statistiques de classe
- **Plus forte moyenne** de la classe
- **Plus faible moyenne** de la classe  
- **Moyenne générale** de la classe
- **Prise en compte** des moyennes manuelles dans les statistiques

### 4. Configuration dynamique
- **Settings flexibles** via SettingsHelper
- **Affichage conditionnel** des sections selon la configuration
- **Personnalisation** de l'apparence et du contenu

## Utilisation

### Méthode principale

```php
$bulletinService = app(\App\Services\BulletinService::class);

$donnees = $bulletinService->genererDonneesBulletin(
    $etudiantId,           // ID de l'étudiant
    $classeId,             // ID de la classe
    $anneeUniversitaireId, // ID de l'année universitaire
    'semestre1'            // Période (optionnel, défaut: semestre1)
);
```

### Données retournées

```php
[
    'etudiant' => ESBTPEtudiant,           // Objet étudiant
    'classe' => ESBTPClasse,               // Objet classe avec relations
    'anneeUniversitaire' => ESBTPAnneeUniversitaire,
    'periode' => 'semestre1',
    'resultatsGeneraux' => Collection,      // Matières d'enseignement général
    'resultatsTechniques' => Collection,    // Matières techniques/professionnelles
    'moyenneGenerale' => float,            // Moyenne enseignement général
    'moyenneTechnique' => float,           // Moyenne enseignement technique
    'moyenneGlobale' => float,             // Moyenne globale pondérée
    'moyenneAvecAssiduite' => float,       // Moyenne + note d'assiduité
    'noteAssiduite' => float,              // Bonus/malus d'assiduité
    'rang' => string,                      // Rang de l'étudiant
    'effectif' => int,                     // Effectif de la classe
    'meilleure_moyenne' => float,          // Statistique classe
    'plus_faible_moyenne' => float,        // Statistique classe
    'moyenne_classe' => float,             // Statistique classe
    'appreciation' => string,              // Appréciation globale
    'absences' => array,                   // Détail des absences
    'professeurs' => array,                // Professeurs configurés
    'date_edition' => string,              // Date d'édition du bulletin (format d/m/Y)
    'settings' => array                    // Configuration PDF/affichage
]
```

## Intégration dans le contrôleur

### Preview (méthode simplifiée)

```php
public function previewBulletinEtudiantNew(Request $request, $etudiantId)
{
    try {
        // Validation
        $validator = Validator::make($request->all(), [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Génération via le service
        $donnees = $this->bulletinService->genererDonneesBulletin(
            $etudiantId,
            $request->classe_id,
            $request->annee_universitaire_id,
            'semestre1'
        );

        // Ajout du logo
        $config = $this->getPDFConfig();
        $logoBase64 = $this->prepareLogoBase64($config['school_logo']);
        $donnees['logoBase64'] = $logoBase64;

        return view('esbtp.bulletins.pdf-configurable', $donnees);

    } catch (\Exception $e) {
        // Gestion des erreurs de configuration
        if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
            $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                'classe_id' => $request->classe_id,
                'periode' => 'semestre1',
                'annee_universitaire_id' => $request->annee_universitaire_id,
                'bulletin' => $etudiantId
            ]);
            
            return redirect($configMatieresUrl)->with('error', $e->getMessage());
        }
        
        return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
    }
}
```

### Export PDF (à migrer)

Le même service peut être utilisé pour l'export PDF en remplaçant les calculs manuels par :

```php
// Au lieu de tous les calculs manuels...
$donnees = $this->bulletinService->genererDonneesBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode);

// Puis générer le PDF
$pdf = PDF::loadView('esbtp.bulletins.pdf-configurable', $donnees);
return $pdf->download($filename);
```

## Logique des moyennes

### Priorité des moyennes

1. **Moyennes manuelles** (table `esbtp_resultats`) → **PRIORITÉ ABSOLUE**
2. **Moyennes automatiques** (calculées depuis `esbtp_notes` et `esbtp_evaluations`) → Fallback

### Algorithme de calcul

```php
// 1. Récupérer les évaluations et calculer les moyennes automatiques
foreach ($notesAvecEvaluations as $note) {
    // Calculer moyenne pondérée par matière
    $totalPoints += $note->note * $note->evaluation->coefficient;
    $totalCoeffs += $note->evaluation->coefficient;
    $moyenne = $totalPoints / $totalCoeffs;
}

// 2. Écraser avec les moyennes manuelles
foreach ($resultatsManuals as $resultatManuel) {
    // Les moyennes manuelles l'emportent TOUJOURS
    $resultatsParMatiere[$matiereId]->moyenne = $resultatManuel->moyenne;
}

// 3. Ajouter les matières qui n'ont QUE des moyennes manuelles
foreach ($resultatsManuals as $resultatManuel) {
    if (!isset($resultatsParMatiere[$matiereId])) {
        // Créer l'entrée pour cette matière
        $resultatsParMatiere[$matiereId] = new ResultatMatiere(...);
    }
}
```

## Configuration et personnalisation

### Settings supportées

Le service utilise toutes les configurations du système via `SettingsHelper` :

```php
// Configuration de l'établissement
'school_name' => 'KLASSCI',                     // Nom de l'école par défaut
'bulletin_school_name_custom' => '',            // Nom personnalisé pour le bulletin
'school_address' => '',                         // Adresse de l'école
'school_phone' => '',                           // Téléphone de l'école
'school_email' => '',                           // Email de l'école
'school_logo' => '',                            // Logo de l'école

// Configuration d'affichage
'bulletin_show_matricule' => '1',               // Afficher matricule
'bulletin_show_birth_date' => '1',              // Afficher date naissance
'bulletin_show_redoublant' => '1',              // Afficher statut redoublant
'bulletin_show_subjects_table' => '1',          // Afficher tableau matières
'bulletin_show_general_subjects' => '1',        // Afficher matières générales
'bulletin_show_technical_subjects' => '1',      // Afficher matières techniques
'bulletin_show_statistics' => '1',              // Afficher statistiques classe
'bulletin_show_absences' => '1',                // Afficher absences
'bulletin_include_attendance_in_stats' => '1',  // Inclure assiduité dans les statistiques
// ... plus de 30 autres settings
```

### Personnalisation du template

Le service génère les données, le template `pdf-configurable.blade.php` gère l'affichage :

```blade
{{-- Affichage conditionnel selon la configuration --}}
@if(($settings['bulletin_show_general_subjects'] ?? '1') == '1')
    <tr class="section-header">
        <td colspan="7">Enseignement Général</td>
    </tr>
    @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
        @foreach($resultatsGeneraux as $resultat)
            <tr>
                <td>{{ $resultat->matiere->name }}</td>
                <td>{{ number_format($resultat->moyenne, 2) }}</td>
                {{-- ... autres colonnes --}}
            </tr>
        @endforeach
    @else
        <tr>
            <td colspan="7">Aucune matière d'enseignement général</td>
        </tr>
    @endif
@endif
```

## Gestion des erreurs

### Erreurs de configuration

```php
// Configuration manquante
if (!$bulletin || !$bulletin->config_matieres || !$bulletin->professeurs) {
    throw new \Exception('Configuration bulletin manquante. Veuillez d\'abord configurer les matières et les professeurs.');
}

// Configuration vide
if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
    throw new \Exception('Aucune matière configurée dans le bulletin.');
}
```

### Gestion dans le contrôleur

```php
try {
    $donnees = $this->bulletinService->genererDonneesBulletin(...);
    return view('template', $donnees);
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
        // Rediriger vers la configuration
        return redirect($configUrl)->with('error', $e->getMessage());
    }
    
    // Autres erreurs
    return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
}
```

## Problèmes résolus

### 1. Synchronisation preview/PDF
- **Avant** : Preview et PDF utilisaient des logiques différentes
- **Après** : Un seul service pour les deux, garantit la cohérence

### 2. Moyennes manuelles non prises en compte
- **Avant** : Preview ignorait les moyennes manuelles de `esbtp_resultats`
- **Après** : Intégration automatique avec priorité absolue

### 3. Statistiques de classe incorrectes  
- **Avant** : Calculs basés uniquement sur les évaluations
- **Après** : Inclusion des moyennes manuelles dans les statistiques

### 4. Format d'année inconsistant
- **Avant** : "Année Universitaire 2024-2025" 
- **Après** : "2024-2025" (format unifié)

### 5. Matières manuelles invisibles
- **Avant** : Matières avec seulement moyennes manuelles non affichées
- **Après** : Toutes les matières configurées sont visibles

### 6. Variables manquantes dans le template
- **Avant** : Erreurs "Undefined array key 'bulletin_school_name_custom'" et "Undefined variable $date_edition"
- **Après** : Toutes les variables nécessaires définies dans le service
  - `bulletin_school_name_custom` ajouté dans `getPDFConfig()`
  - `date_edition` ajouté avec la date actuelle (format d/m/Y)
  - `note_assiduite` (alias de `noteAssiduite`) pour compatibilité template
  - Variables d'absences avec alias pour compatibilité

### 7. Statistiques de classe et note d'assiduité
- **Avant** : Les statistiques de classe (plus forte/faible moyenne) ne prenaient pas en compte la note d'assiduité
- **Après** : Configuration `bulletin_include_attendance_in_stats` permet d'inclure l'assiduité dans les statistiques
  - Les moyennes de classe reflètent maintenant les vraies moyennes finales des étudiants
  - Note d'assiduité calculée correctement selon les absences justifiées/non justifiées

## Avantages de l'architecture

### 🎯 **Cohérence garantie**
Preview et PDF utilisent exactement la même logique

### 🧹 **Code plus propre**
Suppression de la duplication entre les méthodes

### 🔧 **Maintenance simplifiée**  
Une modification dans le service impacte tous les usages

### 🚀 **Performance optimisée**
Calculs centralisés et optimisés

### 🧪 **Testabilité améliorée**
Service isolé facilement testable

### 📈 **Extensibilité**
Ajout facile de nouvelles fonctionnalités

## Migration

### Étapes de migration

1. **✅ Service créé** : `BulletinService` opérationnel
2. **✅ Preview migrée** : `previewBulletinEtudiantNew` utilise le service  
3. **✅ Export PDF migré** : `genererPDFParParamsUnified` utilise le service unifié
4. **✅ Nettoyage effectué** : Suppression des fichiers obsolètes
5. **✅ Rangs par matière** : Calcul automatique des rangs implémenté
6. **✅ Fichiers obsolètes supprimés** : Templates non utilisés supprimés

### Validation de la migration

- ✅ Preview affiche correctement les moyennes manuelles
- ✅ Statistiques de classe calculées correctement avec assiduité  
- ✅ Format d'année unifié (2024-2025)
- ✅ Gestion d'erreurs appropriée
- ✅ Configuration respectée
- ✅ Note d'assiduité calculée correctement (+0.13 bonus)
- ✅ Rangs par matière fonctionnels
- ✅ Export PDF utilise le même template que la preview
- ✅ Variables manquantes corrigées

## Exemples d'utilisation

### Cas d'usage typique

```php
// Dans un contrôleur
class MonController extends Controller 
{
    public function __construct(BulletinService $bulletinService) 
    {
        $this->bulletinService = $bulletinService;
    }
    
    public function afficherBulletin($etudiantId, $classeId, $anneeId) 
    {
        try {
            $donnees = $this->bulletinService->genererDonneesBulletin(
                $etudiantId, 
                $classeId, 
                $anneeId
            );
            
            return view('mon-template', $donnees);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
```

### Extension du service

```php
// Pour ajouter de nouveaux calculs
class BulletinService 
{
    public function genererDonneesBulletin(...) 
    {
        $donnees = [...]; // Calculs existants
        
        // Nouveau calcul
        $donnees['nouveau_champ'] = $this->calculerNouveauChamp($donnees);
        
        return $donnees;
    }
    
    private function calculerNouveauChamp($donnees) 
    {
        // Logique du nouveau calcul
        return $resultat;
    }
}
```

## Conclusion

Le `BulletinService` représente une amélioration majeure de l'architecture du système de bulletins ESBTP. Il résout les problèmes de cohérence, simplifie la maintenance et améliore l'expérience développeur tout en garantissant la fiabilité des données affichées aux utilisateurs finaux.

Cette approche centralisée constitue une base solide pour les futures évolutions du système de bulletins.

## Architecture finale

### Structure unifiée

```
Preview (Web)           Export PDF
      ↓                      ↓
      └─── BulletinService ───┘
                  ↓
         pdf-configurable.blade.php
```

**Un seul service, un seul template, deux usages :**
- **Preview web** : Affichage direct du template 
- **Export PDF** : Génération PDF du même template

### Nouvelles fonctionnalités

#### 1. Calcul automatique des rangs par matière
- **Fonctionnalité** : Chaque matière affiche le rang de l'étudiant
- **Logique** : Comparaison avec tous les étudiants configurés de la classe
- **Gestion des égalités** : Même moyenne = même rang

#### 2. Note d'assiduité correcte
- **Logique unified** : Même calcul que dans le contrôleur original
- **Bonus/Malus** : +0.13 si 0 absence, pénalités progressives
- **Intégration** : Prise en compte dans les statistiques de classe

#### 3. Méthodes unifiées

##### Preview simplifiée
```php
public function previewBulletinEtudiantNew(Request $request, $etudiantId)
{
    $donnees = $this->bulletinService->genererDonneesBulletin($etudiantId, $classeId, $anneeId);
    return view('esbtp.bulletins.pdf-configurable', $donnees);
}
```

##### Export PDF unifié  
```php
public function genererPDFParParamsUnified(Request $request)
{
    $donnees = $this->bulletinService->genererDonneesBulletin($etudiantId, $classeId, $anneeId);
    $pdf = PDF::loadView('esbtp.bulletins.pdf-configurable', $donnees);
    return $pdf->download($filename);
}
```

### Fichiers supprimés

**Templates obsolètes supprimés :**
- `pdf.blade.php` ❌ (ancien template PDF)
- `bulletin-pdf.blade.php` ❌ (ancien template PDF)
- `mon-bulletin.blade.php` ❌ (non utilisé)  
- `test-configurable.blade.php` ❌ (fichier de test)

**Template unifié conservé :**
- `pdf-configurable.blade.php` ✅ (template unique)

### Bénéfices de l'unification

1. **Cohérence absolue** : Preview = PDF (style et données identiques)
2. **Maintenance simplifiée** : Un seul code à maintenir
3. **Nouvelles fonctionnalités** : Rangs, assiduité, statistiques
4. **Code plus propre** : Suppression de 700+ lignes dupliquées
5. **Architecture claire** : Un service, un template, deux usages
6. **Rendu unifié** : CSS compatible DomPDF avec simulation PDF dans le navigateur

## Synchronisation visuelle Preview/PDF

### Problématique CSS et DomPDF

DomPDF ne supporte que **CSS 2.1** et quelques propriétés CSS3, ce qui crée des différences visuelles entre la preview web (navigateur moderne) et le PDF généré.

### Solution implémentée

#### 1. **CSS compatible DomPDF**
- Remplacement de `display: flex` par des structures de table (`<table>`)
- Utilisation de la police `DejaVu Sans` supportée par DomPDF
- Suppression des propriétés CSS3 non supportées (`box-shadow`, `border-radius`, etc.)

#### 2. **Mode PDF simulation** 
- **Bouton de basculement** : "Mode PDF" / "Mode Web" en haut à droite de la preview
- **Classe CSS `.pdf-mode`** : Simule l'apparence PDF dans le navigateur
- **Paramètre URL** : `?preview=pdf` active automatiquement le mode simulation

#### 3. **Structures HTML optimisées**
```html
<!-- Header avec table au lieu de flexbox -->
<div class="header">
    <table class="header-table">
        <tr>
            <td class="header-left">...</td>
            <td class="header-center">...</td>
            <td class="header-right">...</td>
        </tr>
    </table>
</div>

<!-- Informations étudiant avec table -->
<div class="student-info">
    <table class="student-info-table">
        <tr>
            <td class="info-group">...</td>
            <td class="info-group">...</td>
        </tr>
    </table>
</div>

<!-- Résultats et statistiques avec table -->
<div class="results-container">
    <table class="results-container-table">
        <tr>
            <td class="results-left">...</td>
            <td class="results-right">...</td>
        </tr>
    </table>
</div>

<!-- Mentions avec table -->
<div class="mention-box">
    <table class="mention-table">
        <tr>
            <td class="mention-label">...</td>
            <td class="mention-value">...</td>
        </tr>
    </table>
</div>
```

#### 4. **CSS Mode PDF**
```css
/* Simulation de l'apparence PDF */
.pdf-mode body {
    font-family: DejaVu Sans, Arial, sans-serif;
}

.pdf-mode .container {
    width: 210mm;           /* Format A4 */
    min-height: 297mm;      /* Hauteur A4 */
    padding: 20mm;          /* Marges PDF */
    box-shadow: 0 0 10px rgba(0,0,0,0.3); /* Effet papier */
}

/* Tables compatibles DomPDF */
.header-table, .student-info-table, 
.results-container-table, .mention-table {
    width: 100%;
    border-collapse: collapse;
}

.header-table td, .student-info-table td {
    border: none;
    padding: 5px;
    vertical-align: top;
}
```

#### 5. **JavaScript de basculement**
```javascript
function togglePDFMode() {
    const body = document.body;
    const button = document.getElementById('pdfToggle');
    
    if (body.classList.contains('pdf-mode')) {
        body.classList.remove('pdf-mode');
        button.textContent = 'Mode PDF';
    } else {
        body.classList.add('pdf-mode');
        button.textContent = 'Mode Web';
    }
}
```

### Utilisation du mode simulation

#### 1. **Manuel**
Cliquer sur le bouton "Mode PDF" dans la preview web

#### 2. **Automatique via URL**
```
http://localhost:8000/esbtp/bulletin/preview?preview=pdf&...
```

#### 3. **Comparaison facile**
- Mode Web : Affichage navigateur moderne avec flexbox
- Mode PDF : Simulation exacte du rendu PDF avec tables
- PDF généré : Identique au mode simulation

### Avantages de cette approche

#### ✅ **Cohérence visuelle garantie**
- La simulation PDF montre exactement ce qui sera généré
- Même structure HTML pour web et PDF
- CSS adaptatif selon le contexte

#### ✅ **Développement simplifié**  
- Un seul template à maintenir
- Test facile de l'apparence PDF sans générer le fichier
- Debugging visuel facilité

#### ✅ **Compatibilité maximale**
- CSS 2.1 compatible DomPDF
- Fallback gracieux pour les navigateurs
- Police DejaVu Sans intégrée

#### ✅ **Expérience utilisateur**
- Bouton intuitif de prévisualisation PDF
- Basculement instantané sans rechargement
- URL avec paramètre pour liens directs

### Corrections finales des problèmes de dimensionnement PDF

#### Problématiques identifiées et résolues

##### 1. **Bouton Mode PDF dans l'export PDF** ❌→✅
**Problème** : Le bouton "Mode PDF" et le JavaScript apparaissaient dans le PDF généré.

**Solution** : Condition Blade basée sur `$isPdfExport`
```php
// Contrôleur
$donnees['isPdfExport'] = true;
```

```blade
<!-- Template -->
@unless($isPdfExport ?? false)
<button class="pdf-toggle" onclick="togglePDFMode()">Mode PDF</button>
@endunless
```

##### 2. **Contenu décentré et débordements** ❌→✅
**Problème** : Le contenu PDF était décentré sur le côté avec des informations coupées à droite.

**Solution** : Application des meilleures pratiques DomPDF
- Utilisation de `@page { margin: 20mm 15mm !important; }` au lieu de marges forcées à 0
- Centrage automatique avec `margin: 0 auto` sur le container
- `table-layout: fixed` pour éviter les débordements de tables
- Reset CSS spécifique pour les éléments PDF

```css
@page {
    margin: 20mm 15mm !important;
}

body.pdf-export .container {
    width: 100%;
    margin: 0 auto;  /* Centrage automatique */
    padding: 0;
}

/* Reset optimisé pour DomPDF */
body.pdf-export th, td, p, div, h1, h2, h3 {
    margin: 0;
    padding: 2px;
}
```

##### 3. **Options DomPDF optimisées**
```php
$pdf->setOptions([
    'dpi' => 150, 
    'defaultFont' => 'DejaVu Sans',
    'isPhpEnabled' => true,
    'chroot' => public_path()
    // Suppression des marges forcées à 0 qui causaient les problèmes
]);
```

#### Workflow final parfaitement synchronisé

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  Preview Web    │    │  Mode PDF Simu  │    │   Export PDF    │
│                 │    │                 │    │                 │
│ • Bouton visible│ -> │ • Simulation A4  │ -> │ • Même contenu  │
│ • Flexbox/CSS3  │    │ • Même template  │    │ • Sans bouton   │
│ • Interactive   │    │ • Box-shadow     │    │ • Centré        │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         ↕                        ↕                        ↕
    BulletinService         pdf-configurable.blade.php
```

#### Validation complète ✅

1. **Preview web** : Affichage moderne avec bouton de simulation
2. **Mode PDF simulation** : Aperçu exact du PDF avec dimensions A4
3. **PDF généré** : 
   - ✅ Contenu parfaitement centré
   - ✅ Marges équilibrées (20mm/15mm)
   - ✅ Pas de débordement de contenu
   - ✅ Aucun élément d'interface web
   - ✅ Données identiques à la preview

#### Architecture technique finale

**Un seul service** : `BulletinService`  
**Un seul template** : `pdf-configurable.blade.php`  
**Trois contextes d'utilisation** :
1. Preview web normale
2. Simulation PDF (avec bouton)
3. Export PDF (sans bouton, optimisé DomPDF)

**Résultat** : Cohérence absolue des données et rendu visuel optimal pour chaque contexte.