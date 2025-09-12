# Système de Prévisualisation des Emplois du Temps ESBTP

## Vue d'ensemble
Le système de prévisualisation des emplois du temps permet aux utilisateurs de visualiser l'emploi du temps avant de le générer en PDF, similaire au système des bulletins. Cette fonctionnalité utilise les paramètres configurables depuis les settings et respecte les couleurs et styles de la page show.

## Architecture

### Fichiers Principaux

#### Controller
- **ESBTPEmploiTempsController.php** (`app/Http/Controllers/ESBTPEmploiTempsController.php`)
  - Méthode `getPDFConfig()` : récupère la configuration depuis les settings
  - Méthode `prepareLogoBase64()` : convertit les logos uploadés en base64
  - Méthode `previewEmploiTemps()` : génère la prévisualisation des emplois du temps

#### Template
- **preview-pdf.blade.php** (`resources/views/esbtp/emploi-temps/preview-pdf.blade.php`)
  - Template de prévisualisation des emplois du temps
  - Utilise `$settings` array pour la configuration
  - Support logo via `$logoBase64`
  - Reprend les couleurs et styles de la page show

## Configuration

### Paramètres Principaux (depuis SettingsHelper)
- **school_name** : Nom de l'établissement
- **school_address** : Adresse
- **school_phone** : Téléphone  
- **school_email** : Email
- **school_logo** : Logo uploadé
- **school_city** : Ville
- **school_country** : Pays
- **director_name** : Nom du directeur
- **director_title** : Titre du directeur

### Paramètres Spécifiques Emploi du Temps
- **timetable_show_logo** : Afficher le logo (par défaut: '1')
- **timetable_show_header** : Afficher l'en-tête (par défaut: '1') 
- **timetable_show_stats** : Afficher les statistiques (par défaut: '1')

## Fonctionnalités Techniques

### Méthode getPDFConfig()
```php
private function getPDFConfig()
{
    return [
        // Informations de l'établissement
        'school_name' => SettingsHelper::get('school_name', 'École Spéciale du Bâtiment et des Travaux Publics'),
        'school_type' => SettingsHelper::get('school_type', 'Enseignement Supérieur Technique'),
        // ... autres paramètres
        
        // Configuration spécifique emploi du temps
        'timetable_show_logo' => SettingsHelper::get('timetable_show_logo', '1'),
        'timetable_show_header' => SettingsHelper::get('timetable_show_header', '1'),
        'timetable_show_stats' => SettingsHelper::get('timetable_show_stats', '1'),
    ];
}
```

### Méthode previewEmploiTemps()
```php
public function previewEmploiTemps($id)
{
    try {
        $emploiTemps = ESBTPEmploiTemps::with([
            'seances.matiere',
            'classe',
            'classe.filiere',
            'classe.niveau',
            'annee'
        ])->findOrFail($id);

        // Récupérer la configuration PDF
        $config = $this->getPDFConfig();
        $settings = $config;
        
        // Préparer le logo en base64
        $logoBase64 = $this->prepareLogoBase64($config['school_logo']);

        // Grouper les séances par jour
        $seancesParJour = $emploiTemps->getSeancesParJour();

        // Préparer les données pour la vue
        return view('esbtp.emploi-temps.preview-pdf', [
            'emploiTemps' => $emploiTemps,
            'seances' => $emploiTemps->seances,
            'seancesParJour' => $seancesParJour,
            'logoBase64' => $logoBase64,
            'settings' => $settings,
            // ... autres variables
        ]);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Erreur lors de la prévisualisation.');
    }
}
```

## Interface Utilisateur

### Bouton de Prévisualisation
Ajouté dans la page show de l'emploi du temps :
```html
<a href="{{ route('esbtp.emploi-temps.preview', ['emploi_temp' => $emploiTemps->id]) }}" 
   class="btn-acasi info" target="_blank">
    <i class="fas fa-eye"></i>Prévisualiser PDF
</a>
```

### Template de Prévisualisation
- **En-tête stylé** avec dégradé et informations établissement
- **Logo intégré** avec gestion base64
- **Notice de prévisualisation** bien visible
- **Légende colorée** pour les types de séances
- **Tableau responsive** avec les mêmes couleurs que show
- **Statistiques** détaillées par matière
- **Boutons d'action** pour navigation

### Couleurs des Séances (identiques à show)
```css
.session-cours {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.session-td {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}
.session-tp {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}
.session-examen {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
}
.session-autre {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}
.session-pause {
    background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
}
.session-dejeuner {
    background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
}
```

## Routes

### Route Principale
```php
// Route pour prévisualiser l'emploi du temps avant génération PDF
Route::get('emploi-temps/{emploi_temp}/preview', [ESBTPEmploiTempsController::class, 'previewEmploiTemps'])
    ->name('emploi-temps.preview')
    ->middleware(['permission:view_timetables']);
```

**URL complète** : `GET esbtp/emploi-temps/{emploi_temp}/preview`

## Données Affichées

### Informations Emploi du Temps
- Nom de la classe
- Filière et niveau
- Année universitaire
- Effectif de la classe
- Date d'édition

### Tableau Horaire
- Créneaux de 8h à 18h (10 créneaux)
- 6 jours de cours (Lundi à Samedi)
- Séances colorées selon le type
- Informations matière, enseignant, salle

### Statistiques
- Total des séances
- Nombre de matières différentes
- Créneaux horaires utilisés
- Répartition détaillée par matière

### Actions Disponibles
- **Retour** : vers la page show de l'emploi du temps
- **Télécharger PDF** : génération du PDF final
- **Modifier** : édition de l'emploi du temps

## Upload de Logo (héritage bulletin)
1. **Stockage** : `storage/app/public/settings/`
2. **Conversion** : Base64 via `prepareLogoBase64()`
3. **Chemins testés** :
   - `storage/app/public/{logoPath}` (priorité)
   - `public/{logoPath}` (compatibilité)

## Synchronisation avec Settings
1. **getPDFConfig()** récupère tous les paramètres via `SettingsHelper::get()`
2. **Template** utilise `$settings` array
3. **Configuration** centralisée dans `/esbtp/settings`

## Responsive Design
- **Mobile first** : adaptation automatique sur petits écrans
- **Tableaux scrollables** : overflow-x sur mobile
- **Grilles flexibles** : adaptation des statistiques
- **Légende adaptable** : réorganisation verticale sur mobile

## Logging et Débogage
```php
\Log::error('Erreur lors de la prévisualisation de l\'emploi du temps', [
    'error' => $e->getMessage(),
    'emploi_temps_id' => $id
]);
```

## Avantages

### Pour les Utilisateurs
- **Prévisualisation avant génération** : évite les PDF incorrects
- **Validation visuelle** : vérification de la mise en page
- **Navigation intuitive** : boutons d'action clairs
- **Cohérence visuelle** : même rendu que la page show

### Pour les Développeurs
- **Code réutilisable** : même structure que les bulletins
- **Configuration centralisée** : via SettingsHelper
- **Maintenance facilitée** : séparation des concerns
- **Extensibilité** : ajout facile de nouveaux paramètres

## URLs de Test
- **Page show** : `http://localhost:8000/esbtp/emploi-temps/{id}`
- **Prévisualisation** : `http://localhost:8000/esbtp/emploi-temps/{id}/preview`
- **Settings** : `http://localhost:8000/esbtp/settings`
- **PDF final** : `http://localhost:8000/esbtp/emploi-temps/{id}/export-pdf`

## État Actuel
- ✅ Méthode `getPDFConfig()` implémentée
- ✅ Méthode `prepareLogoBase64()` adaptée 
- ✅ Méthode `previewEmploiTemps()` créée
- ✅ Route de prévisualisation ajoutée
- ✅ Template preview-pdf.blade.php complet
- ✅ Bouton preview ajouté dans show
- ✅ Couleurs et styles identiques à show
- ✅ Récupération settings via getPDFConfig
- ✅ Responsive design intégré
- ✅ Statistiques et légende incluses

## Workflow d'Utilisation
1. **Accéder** à la page show de l'emploi du temps
2. **Cliquer** sur "Prévisualiser PDF" (bouton bleu avec icône œil)
3. **Vérifier** l'aperçu dans un nouvel onglet
4. **Ajuster** les settings si nécessaire (/esbtp/settings)
5. **Générer** le PDF final si satisfait

---

**Documentation créée le** : 12/09/2025  
**Version système** : ESBTP-yAKRO v2 Pascal  
**Intégration** : Système de prévisualisation emplois du temps avec settings configurables