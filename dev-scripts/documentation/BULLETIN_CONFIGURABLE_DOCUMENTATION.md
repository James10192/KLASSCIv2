# Système de Bulletin Configurable ESBTP

## Vue d'ensemble

Le système de bulletin configurable ESBTP permet aux administrateurs de contrôler entièrement l'apparence et le comportement des bulletins de notes générés. Avec **67 paramètres configurables**, le système offre une flexibilité maximale pour personnaliser chaque aspect du bulletin.

## Fonctionnalités principales

### 🎛️ Contrôle d'affichage complet (47 paramètres)

-   **En-tête** : Logo, informations école, république, ministère
-   **Informations étudiant** : Matricule, nom, date de naissance, statut redoublant
-   **Tableau des matières** : Matières générales/techniques, moyennes, coefficients, rangs
-   **Absences** : Justifiées et non justifiées
-   **Résultats** : Moyenne brute, note d'assiduité, rang
-   **Mentions** : Félicitation, encouragement, tableau d'honneur, avertissements
-   **Statistiques** : Moyennes de classe, meilleures/plus faibles moyennes
-   **Signatures** : Directeur, conseil de classe

### ⚙️ Fonctionnalités automatiques (6 paramètres)

-   **Calcul automatique du rang** : Classement basé sur les moyennes
-   **Calcul automatique des mentions** : Attribution selon les seuils configurés
-   **Calcul automatique de l'assiduité** : Note basée sur les absences
-   **Validation des professeurs** : Exiger l'assignation avant génération
-   **Validation des matières** : Exiger la configuration des matières
-   **Validation des moyennes** : Contrôles avant génération

### 🎯 Seuils configurables (4 paramètres)

-   **Félicitation** : Seuil par défaut 16/20
-   **Encouragement** : Seuil par défaut 14/20
-   **Tableau d'honneur** : Seuil par défaut 12/20
-   **Avertissement travail** : Seuil par défaut 8/20

### 🎨 Personnalisation du texte (5 paramètres)

-   **Nom de l'école personnalisé**
-   **Texte de la République**
-   **Devise nationale**
-   **Nom du ministère**
-   **Nom et abréviation du cycle**

### 📄 Options PDF (5 paramètres)

-   **Format papier** : A4, A3, Letter
-   **Orientation** : Portrait, Paysage
-   **Résolution DPI** : Qualité d'impression
-   **Taille de police** : Personnalisable
-   **Bouton d'impression** : Affichage optionnel

## Installation et Configuration

### 1. Installation des paramètres

```bash
php artisan db:seed --class=SettingsSeeder
```

### 2. Vérification de l'installation

```bash
php test_bulletin_simple.php
```

### 3. Accès aux interfaces

#### Interface de test

-   **URL** : `/bulletin/configurable/test`
-   **Description** : Interface complète pour tester le système
-   **Fonctionnalités** :
    -   Test des paramètres
    -   Prévisualisation des bulletins
    -   Génération PDF
    -   Statistiques des paramètres

#### API de test

-   **URL** : `/test-bulletin-parameters`
-   **Description** : Endpoint JSON pour tester les paramètres
-   **Réponse** : Nombre et liste des paramètres chargés

## Utilisation

### 1. Génération de bulletin configurable

#### Via API POST

```php
POST /bulletin/configurable/generate
{
    "etudiant_id": 1,
    "classe_id": 1,
    "periode": "1er_semestre",
    "annee_universitaire_id": 1
}
```

#### Via contrôleur

```php
$controller = new ESBTPBulletinController();
$request = new Request([
    'etudiant_id' => 1,
    'classe_id' => 1,
    'periode' => '1er_semestre',
    'annee_universitaire_id' => 1
]);
$pdf = $controller->generateConfigurableBulletin($request);
```

### 2. Prévisualisation

```php
GET /bulletin/configurable/preview?etudiant_id=1&classe_id=1&periode=1er_semestre&annee_universitaire_id=1
```

### 3. Configuration des paramètres

#### Via base de données

```sql
UPDATE settings
SET value = '0'
WHERE key = 'bulletin_show_logo' AND category = 'bulletin';
```

#### Via modèle Eloquent

```php
use App\Models\Setting;

Setting::where('key', 'bulletin_felicitation_threshold')
       ->where('category', 'bulletin')
       ->update(['value' => '15']);
```

## Structure des fichiers

### Contrôleur

-   **Fichier** : `app/Http/Controllers/ESBTPBulletinController.php`
-   **Méthodes principales** :
    -   `generateConfigurableBulletin()` : Génération PDF
    -   `previewConfigurableBulletin()` : Prévisualisation HTML
    -   `testBulletinParameters()` : Test des paramètres
    -   `getSettings()` : Chargement des paramètres

### Template

-   **Fichier** : `resources/views/esbtp/bulletins/pdf-configurable.blade.php`
-   **Caractéristiques** :
    -   Conditions d'affichage basées sur les paramètres
    -   Calculs automatiques des mentions
    -   Responsive design
    -   Optimisé pour l'impression

### Interface de test

-   **Fichier** : `resources/views/esbtp/bulletins/test-configurable.blade.php`
-   **Fonctionnalités** :
    -   Test interactif des paramètres
    -   Prévisualisation en modal
    -   Génération PDF en temps réel
    -   Statistiques détaillées

### Seeder

-   **Fichier** : `database/seeders/SettingsSeeder.php`
-   **Contenu** : 67 paramètres de bulletin avec valeurs par défaut

## Paramètres détaillés

### Paramètres d'affichage (bulletin*show*\*)

| Paramètre                       | Description        | Valeur par défaut |
| ------------------------------- | ------------------ | ----------------- |
| `bulletin_show_header`          | Afficher l'en-tête | 1                 |
| `bulletin_show_logo`            | Afficher le logo   | 1                 |
| `bulletin_show_student_info`    | Infos étudiant     | 1                 |
| `bulletin_show_subjects_table`  | Tableau matières   | 1                 |
| `bulletin_show_absences`        | Tableau absences   | 1                 |
| `bulletin_show_results_section` | Section résultats  | 1                 |
| `bulletin_show_mentions`        | Mentions           | 1                 |
| `bulletin_show_statistics`      | Statistiques       | 1                 |

### Paramètres fonctionnels (bulletin*auto*_, bulletin*require*_)

| Paramètre                             | Description           | Valeur par défaut |
| ------------------------------------- | --------------------- | ----------------- |
| `bulletin_auto_calculate_rank`        | Calcul auto rang      | 1                 |
| `bulletin_auto_calculate_mention`     | Calcul auto mention   | 1                 |
| `bulletin_auto_calculate_attendance`  | Calcul auto assiduité | 1                 |
| `bulletin_require_teacher_assignment` | Exiger professeurs    | 1                 |
| `bulletin_require_subject_config`     | Exiger matières       | 1                 |
| `bulletin_validate_averages`          | Valider moyennes      | 1                 |

### Seuils de mention (bulletin\_\*\_threshold)

| Paramètre                          | Description           | Valeur par défaut |
| ---------------------------------- | --------------------- | ----------------- |
| `bulletin_felicitation_threshold`  | Seuil félicitation    | 16                |
| `bulletin_encouragement_threshold` | Seuil encouragement   | 14                |
| `bulletin_honor_roll_threshold`    | Seuil tableau honneur | 12                |
| `bulletin_work_warning_threshold`  | Seuil avertissement   | 8                 |

### Personnalisation du texte (bulletin*\*\_text, bulletin*\*\_custom)

| Paramètre                     | Description       | Valeur par défaut              |
| ----------------------------- | ----------------- | ------------------------------ |
| `bulletin_republic_text`      | Texte république  | République de Côte d'Ivoire    |
| `bulletin_union_text`         | Devise nationale  | Union-Discipline-Travail       |
| `bulletin_ministry_text`      | Nom ministère     | Ministère de l'Enseignement... |
| `bulletin_cycle_text`         | Nom cycle         | Brevet de Technicien Supérieur |
| `bulletin_cycle_abbreviation` | Abréviation cycle | BTS                            |

### Options PDF (bulletin*paper*_, bulletin*font*_, bulletin_dpi)

| Paramètre                    | Description       | Valeur par défaut |
| ---------------------------- | ----------------- | ----------------- |
| `bulletin_paper_format`      | Format papier     | A4                |
| `bulletin_orientation`       | Orientation       | portrait          |
| `bulletin_font_size`         | Taille police     | 11                |
| `bulletin_dpi`               | Résolution        | 150               |
| `bulletin_show_print_button` | Bouton impression | 1                 |

## Logique métier

### Calcul des mentions automatiques

```php
if ($moyenne >= $felicitationThreshold) {
    return "Félicitation";
} elseif ($moyenne >= $encouragementThreshold) {
    return "Encouragement";
} elseif ($moyenne >= $honorRollThreshold) {
    return "Tableau d'honneur";
} elseif ($moyenne >= $workWarningThreshold) {
    return "Avertissement travail";
}
```

### Calcul de la note d'assiduité

```php
$penalite = ($absences_justifiees * 0.1) + ($absences_non_justifiees * 0.2);
$note_assiduite = max(0, 20 - $penalite);
```

### Calcul du rang

```php
// Tri des moyennes par ordre décroissant
// Attribution du rang selon la position
```

## Dépannage

### Problèmes courants

#### 1. Paramètres non trouvés

```bash
# Vérifier l'installation
php artisan tinker --execute="echo \App\Models\Setting::where('category', 'bulletin')->count();"

# Réinstaller si nécessaire
php artisan db:seed --class=SettingsSeeder
```

#### 2. Template non trouvé

```bash
# Vérifier l'existence
ls -la resources/views/esbtp/bulletins/pdf-configurable.blade.php
```

#### 3. Erreurs de génération PDF

-   Vérifier les données d'entrée (étudiant, classe, etc.)
-   Contrôler les paramètres de validation
-   Examiner les logs Laravel

### Logs et débogage

```php
// Activer les logs dans le contrôleur
\Log::info('Génération bulletin configurable', [
    'etudiant_id' => $request->etudiant_id,
    'settings_count' => count($settings)
]);
```

## Évolutions futures

### Fonctionnalités prévues

1. **Interface d'administration** : Gestion graphique des paramètres
2. **Templates multiples** : Plusieurs modèles de bulletin
3. **Historique des modifications** : Suivi des changements de paramètres
4. **Import/Export** : Sauvegarde et restauration de configurations
5. **Prévisualisation en temps réel** : Aperçu instantané des modifications

### Extensions possibles

1. **Bulletin numérique** : Version web interactive
2. **Notifications automatiques** : Envoi par email/SMS
3. **Signatures électroniques** : Validation numérique
4. **Multi-langues** : Support de plusieurs langues
5. **Thèmes visuels** : Personnalisation avancée du design

## Support et maintenance

### Contact technique

-   **Développeur** : Équipe ESBTP
-   **Documentation** : Ce fichier
-   **Tests** : Scripts de validation inclus

### Mise à jour

1. Sauvegarder les paramètres actuels
2. Appliquer les nouvelles migrations
3. Exécuter les nouveaux seeders
4. Tester le système complet

---

**Version** : 1.0  
**Date** : Mars 2024  
**Statut** : Production Ready ✅
