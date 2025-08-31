# Documentation Système Bulletin ESBTP

## Vue d'ensemble
Le système de bulletin ESBTP permet la génération de bulletins de notes configurables pour les étudiants, avec synchronisation complète entre les paramètres et l'affichage.

## Architecture

### Fichiers Principaux

#### Controllers
- **ESBTPBulletinController.php** (`app/Http/Controllers/ESBTPBulletinController.php`)
  - Gère la génération des bulletins
  - Méthode `getPDFConfig()` : récupère la configuration depuis les settings
  - Méthode `prepareLogoBase64()` : convertit les logos uploadés en base64
  - Méthode `previewBulletinEtudiant()` : génère la preview des bulletins

- **ESBTPSettingsController.php** (`app/Http/Controllers/ESBTP/ESBTPSettingsController.php`)
  - Gère les paramètres configurables
  - Traite les uploads de fichiers (logos)
  - Gère les checkboxes avec logique spécifique

#### Templates
- **pdf-configurable.blade.php** (`resources/views/esbtp/bulletins/pdf-configurable.blade.php`)
  - Template principal des bulletins
  - Utilise `$settings` array pour la configuration
  - Support logo via `$logoBase64`

- **settings/index.blade.php** (`resources/views/esbtp/settings/index.blade.php`)
  - Interface de configuration des paramètres
  - Sections organisées avec toggles bulk

### Modèles de Données
- **Setting** : stockage des paramètres configurables
- **ESBTPEtudiant** : données étudiants
- **ESBTPNote** : notes des étudiants
- **ESBTPEvaluation** : évaluations
- **ESBTPAbsence** : gestion des absences

## Configuration

### Paramètres Principaux
- **school_name** : Nom de l'établissement
- **school_address** : Adresse
- **school_phone** : Téléphone  
- **school_email** : Email
- **school_logo** : Logo uploadé (type 'file')
- **bulletin_school_name_custom** : Nom spécifique pour bulletins (optionnel)

### Paramètres d'affichage (checkboxes)
- **bulletin_show_logo** : Afficher le logo
- **bulletin_show_header** : Afficher l'en-tête
- **bulletin_show_student_info** : Informations étudiant
- **bulletin_show_statistics** : Statistiques
- **bulletin_show_highest_average** : Plus forte moyenne
- **bulletin_show_lowest_average** : Plus faible moyenne
- **bulletin_show_class_average** : Moyenne de classe
- **bulletin_show_attendance_note** : Note d'assiduité
- **bulletin_show_council_decision** : Décision du conseil

## Fonctionnalités Techniques

### Upload de Logo
1. **Stockage** : `storage/app/public/settings/`
2. **Validation** : `image|mimes:jpeg,png,jpg,gif|max:2048`
3. **Conversion** : Base64 via `prepareLogoBase64()`
4. **Chemins testés** :
   - `storage/app/public/{logoPath}` (priorité)
   - `public/{logoPath}` (compatibilité)
   - Chemins alternatifs par défaut

### Synchronisation Settings ↔ Bulletin
1. **getPDFConfig()** récupère tous les paramètres via `SettingsHelper::get()`
2. **Template** utilise `$settings` array
3. **Checkboxes** : logique spécifique pour gérer états décochés
4. **Valeurs par défaut** : supprimées pour forcer utilisation des settings

### Logique des Checkboxes
```php
// Traitement spécifique - checkboxes décochées n'envoient rien
foreach ($allCheckboxSettings as $setting) {
    $formKey = $setting->key;  // Pas de préfixe "setting_"
    $value = $request->has($formKey) ? '1' : '0';
    $setting->update(['value' => $value]);
}
```

### Génération Base64 Logo
```php
private function prepareLogoBase64($logoPath) {
    // Priorité 1: storage/app/public/ (logos uploadés)
    $storagePath = storage_path('app/public/' . $logoPath);
    
    // Priorité 2: public/ (compatibilité)
    $publicPath = public_path($logoPath);
    
    // Priorité 3: chemins alternatifs par défaut
}
```

## Routes
- `GET esbtp/resultats/etudiant/{etudiant}/preview` : Preview bulletin
- `GET esbtp/settings` : Page paramètres
- `POST esbtp/settings` : Sauvegarde paramètres

## Corrections Apportées

### Synchronisation Adresse (Fixed)
**Problème** : Valeurs par défaut codées en dur dans getPDFConfig()
**Solution** : Suppression des defaults, utilisation des settings uniquement

### Upload Logo (Fixed)
**Problème** : `prepareLogoBase64()` cherchait dans `public/` au lieu de `storage/`
**Solution** : Priorité aux fichiers uploadés dans `storage/app/public/`

### Checkboxes Settings (Fixed)
**Problème** : Checkboxes décochées gardaient anciennes valeurs
**Solution** : Traitement spécifique avec `$request->has()`

### Type Setting Logo (Fixed)
**Problème** : Setting `school_logo` avait type 'string' au lieu de 'file'
**Solution** : Correction du type vers 'file' avec validation

## Interface Utilisateur

### Bulk Actions
- Boutons "Tout cocher/Tout décocher" par section
- Animations visuelles pour feedback
- JavaScript `toggleSectionCheckboxes()`

### Upload Logo
- Preview immédiat avant sauvegarde
- Validation taille côté client (2MB max)
- Affichage logo actuel si existant

### Sections Organisées
1. Informations de l'Établissement
2. Configuration PDF de Base  
3. En-tête du Bulletin
4. Affichage du Contenu
5. Statistiques de Classe
6. Mentions et Seuils

## Nouvelles Corrections (Dernières Mises à Jour)

### Redesign Complet des Pages de Configuration
- **config-matieres** : Style moderne dashboard-acasi avec KPI cards, statistiques temps réel, boutons d'action rapide
- **moyennes-preview** : Interface moderne avec header, KPI, guide intégré, suppression du debug
- **edit-professeurs** : Style complet evaluations/index, KPI statistiques, input-groups modernes

### Correction Incohérence Professeurs/Classifications
**Problème identifié** : 
- Preview utilisait `getProfesseursParDefaut()` → "M.BONE Oussama"  
- Edit-professeurs utilisait données configurées → "MARC"
- Classification matières incohérente (générale vs technique)

**Solution implémentée** :
```php
// Récupération professeurs configurés depuis bulletin
$professeursConfigures = json_decode($bulletin->professeurs, true) ?: [];

// Priorité aux professeurs configurés
if (isset($professeursConfigures[$matiereId])) {
    $professeurs[$matiereId] = $professeursConfigures[$matiereId];
}

// Classification selon config bulletin
if (in_array($matiereId, $configMatieres['generales'] ?? [])) {
    $typeFormation = 'generale';
} elseif (in_array($matiereId, $configMatieres['techniques'] ?? [])) {
    $typeFormation = 'technologique_professionnelle';
}
```

### Configuration Obligatoire (Pas de Fallback)
**Changement majeur** : Suppression complète des valeurs par défaut

**Vérifications obligatoires dans `previewBulletinEtudiant` et `genererPDFParParams`** :
```php
// 1. Vérifier existence bulletin + config complète
if (!$bulletin || !$bulletin->config_matieres || !$bulletin->professeurs) {
    return redirect($configMatieresUrl)->with('error', $message);
}

// 2. Vérifier configuration non vide
$configMatieres = json_decode($bulletin->config_matieres, true);
if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
    return redirect($configMatieresUrl)->with('error', 'Aucune matière configurée');
}
```

**Workflow obligatoire** :
1. **Config-matières** → Classification des matières par type d'enseignement
2. **Edit-professeurs** → Assignation enseignants par matière  
3. **Prévisualisation/PDF** → Génération avec données configurées uniquement

### Ajout Méthode SettingsHelper::all()
```php
public static function all() {
    try {
        $settings = Setting::all();
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }
        return $result;
    } catch (\Exception $e) {
        return [];
    }
}
```

## Design System et Interface

### Nouveau Style Dashboard-Acasi
Toutes les pages de configuration utilisent maintenant le design système moderne :

```html
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-icon"></i>Titre</h1>
                <p class="header-subtitle">Sous-titre descriptif</p>
            </div>
            <div class="header-actions">
                <span class="badge bg-primary fs-6">Badge info</span>
            </div>
        </div>

        <!-- KPI Cards Grid -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne">
                <div class="kpi-title">Titre</div>
                <div class="kpi-value">Valeur</div>
                <div class="kpi-trend">Détail</div>
            </div>
        </div>

        <!-- Main Cards -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">Titre</div>
                <div class="main-card-subtitle">Sous-titre</div>
            </div>
            <div class="main-card-body">
                Contenu
            </div>
        </div>
    </div>
</div>
```

### Spécificités par Page

#### Page Config-Matières
- **Header** avec badge étudiant
- **KPI Grid** : Classe, Période, Nombre matières
- **Boutons d'action rapide** : "Toutes générales", "Toutes techniques", "Aucune"
- **Table responsive** avec input groups stylés
- **Statistiques temps réel** : Matières incluses/exclues par type

#### Page Edit-Professeurs  
- **Header** avec statistiques professeurs
- **KPI Grid** : Classe, Matières générales/techniques, Professeurs assignés
- **Cards séparées** par type d'enseignement
- **Input groups** avec icônes professeurs
- **Formulaire responsive** avec validation

#### Page Moyennes-Preview
- **Header** avec informations étudiant
- **KPI Grid** : Étudiant, Classe, Période, Nombre matières
- **Guide intégré** avec instructions
- **Table moderne** avec badges de moyennes
- **Actions groupées** avec boutons acasi

### CSS Utilisé
- **dashboard-moderne.css** : Styles principaux dashboard-acasi
- **Boutons acasi** : `btn-acasi primary/success/info/warning/danger`
- **Cards modernes** : `main-card`, `kpi-card`, `card-moderne`
- **Responsive design** : Bootstrap 5 + grilles personnalisées

## Messages d'Erreur et Redirections

### Types de Messages
1. **Configuration manquante** : "Configuration bulletin manquante. Veuillez d'abord configurer les matières et les professeurs"
2. **Configuration vide** : "Aucune matière n'est configurée pour ce bulletin"
3. **Professeurs manquants** : "Les professeurs n'ont pas été assignés"

### Redirections Automatiques
```php
// Vers config-matieres avec paramètres
$configMatieresUrl = route('esbtp.bulletins.config-matieres', [
    'classe_id' => $classeId,
    'periode' => $periode,
    'annee_universitaire_id' => $anneeId,
    'bulletin' => $etudiantId
]);
return redirect($configMatieresUrl)->with('error', $message);
```

## Corrections PDF

### Suppression Cadre/Bordure
```css
body {
    margin: 0;
    padding: 0;
    background-color: white;
}
.container {
    padding: 10px; /* Réduit de 20px à 10px */
    box-shadow: none; /* Supprimé */
}
```

## État Actuel
- ✅ Synchronisation complète settings ↔ bulletin
- ✅ Upload logo fonctionnel 
- ✅ Gestion checkboxes correcte
- ✅ Interface moderne avec bulk actions
- ✅ Preview bulletin temps réel
- ✅ Logging complet pour débogage
- ✅ **Correction incohérence professeurs/classifications**
- ✅ **Configuration obligatoire (pas de fallback)**
- ✅ **Redesign complet pages configuration**
- ✅ **Workflow forcé : config → professeurs → bulletin**

## URLs de Test
- Settings: `http://localhost:8000/esbtp/settings`
- Config matières: `http://localhost:8000/esbtp-special/bulletins/config-matieres?bulletin={id}&classe_id={classe}&periode={periode}&annee_universitaire_id={annee}`
- Edit professeurs: `http://localhost:8000/esbtp-special/bulletins/edit-professeurs?bulletin={id}&classe_id={classe}&periode={periode}&annee_universitaire_id={annee}`
- Moyennes preview: `http://localhost:8000/esbtp-special/bulletins/moyennes-preview?etudiant_id={id}&classe_id={classe}&periode={periode}&annee_universitaire_id={annee}`
- Preview: `http://localhost:8000/esbtp/resultats/etudiant/{id}/preview?classe_id={classe}&annee_universitaire_id={annee}`

## Bonnes Pratiques et Points d'Attention

### Workflow de Configuration Recommandé
1. **Étape 1** : Accéder à `/esbtp/settings` pour configurer les paramètres généraux (logo, nom établissement, etc.)
2. **Étape 2** : Aller vers config-matières pour chaque étudiant/bulletin
3. **Étape 3** : Configurer les professeurs via edit-professeurs
4. **Étape 4** : Prévisualiser et ajuster si nécessaire
5. **Étape 5** : Générer le PDF final

### Points d'Attention Techniques

#### Stockage des Configurations
- **config_matieres** : JSON `{'generales': [id1, id2], 'techniques': [id3, id4]}`
- **professeurs** : JSON `{matiereId: 'Nom Professeur'}`
- **Validation obligatoire** avant accès preview/PDF

#### Gestion des Erreurs
```php
// Toujours vérifier l'existence du bulletin
if (!$bulletin) {
    return redirect($configUrl)->with('error', 'Configuration requise');
}

// Vérifier la complétude des données
$configMatieres = json_decode($bulletin->config_matieres, true);
if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
    return redirect($configUrl)->with('error', 'Matières non configurées');
}
```

#### Performance et Cache
- Les settings sont récupérés via `SettingsHelper::get()` (cached automatiquement)
- Éviter les appels multiples à `getPDFConfig()` dans une même requête
- JSON decode une seule fois par requête pour les configurations bulletin

### Dépannage Courant

#### "Professeurs par défaut apparaissent"
- Vérifier que `$bulletin->professeurs` n'est pas null
- S'assurer que `json_decode($bulletin->professeurs, true)` retourne un array
- Contrôler que les ID matières correspondent

#### "Classifications matières incorrectes"  
- Vérifier `$bulletin->config_matieres`
- S'assurer que les ID matières sont dans le bon array (generales/techniques)
- Utiliser la méthode `in_array($matiereId, $configMatieres['generales'])`

#### "Redirections en boucle"
- Vérifier que la configuration est bien sauvegardée en base
- S'assurer que les colonnes JSON ne sont pas corrompues
- Tester avec `json_decode()` pour valider le format

### Extensions Futures Possibles

1. **Multi-périodes** : Adapter pour trimestres, semestres différents
2. **Templates PDF** : Plusieurs modèles de bulletins
3. **Signatures numériques** : Intégration signatures électroniques
4. **Exports multiples** : Excel, CSV en plus du PDF
5. **Notifications** : Alertes email lors de génération bulletins
6. **Historique** : Versioning des configurations et bulletins générés

### Maintenance et Monitoring

#### Logs à Surveiller
```php
\Log::info('Paramètres reçus pour genererPDFParParams:', [
    'classe_id' => $classe_id,
    'etudiant_id' => $etudiant_id,
    'periode' => $periode
]);
```

#### Commandes Utiles
```bash
# Vérifier les settings
php artisan tinker --execute="App\Helpers\SettingsHelper::all()"

# Nettoyer les configurations corrompues  
php artisan tinker --execute="App\Models\ESBTPBulletin::whereNull('config_matieres')->delete()"

# Tester un bulletin spécifique
php artisan tinker --execute="App\Models\ESBTPBulletin::find(ID)->config_matieres"
```

---

## Correction Personnel Unified Status (26/08/2025)

### Problème Identifié
Sur la page `/esbtp/personnel/unified`, l'affichage des statuts professeurs était incohérent :
- **Badge de statut** utilisait `$teacher->status === 'active'` (correct)
- **Boutons d'action** utilisaient `$teacher->user->is_active` (incorrect)

**Conséquence** : Professeurs actifs affichaient à la fois boutons "Activer" et "Désactiver"

### Solution Appliquée
Standardisation sur `$teacher->status === 'active'` dans `unified-index.blade.php`:

```php
// Badge de statut (ligne 639)
<div class="status-badge {{ $teacher->status === 'active' ? 'active' : 'inactive' }}">
    {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
</div>

// Boutons d'action (ligne 685)
<button class="btn-acasi {{ $teacher->status === 'active' ? 'warning' : 'success' }}">
    {{ $teacher->status === 'active' ? 'Désactiver' : 'Activer' }}
</button>
```

### Logique des Champs de Statut
Le contrôleur `ESBTPPersonnelUnifiedController` maintient la synchronisation :

1. **Champ principal** : `User.is_active` (boolean)
2. **Champ dérivé** : `ESBTPTeacher.status` (string) = `'active'` ou `'inactive'`

```php
// Ligne 435 dans toggleStatus()
$teacher->update([
    'status' => $user->is_active ? 'active' : 'inactive'
]);
```

### Cohérence avec Page Show
La page `show` (/esbtp/enseignants/{id}) utilise déjà `$teacher->status === 'active'` de manière cohérente. La page `unified` suit maintenant la même logique.

---

**Documentation mise à jour le** : 26/08/2025 12:30  
**Version système** : ESBTP-yAKRO v2 Pascal  
**Dernières modifications** : Configuration obligatoire, redesign pages, correction incohérences professeurs, **correction statut personnel unified**