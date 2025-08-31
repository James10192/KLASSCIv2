# Documentation Système Reçus de Paiements ESBTP

## Vue d'ensemble
Le système de reçus de paiements ESBTP permet la génération de reçus PDF configurables avec prévisualisation HTML, intégrant une gestion avancée des catégories de frais et une interface utilisateur moderne.

## Architecture

### Fichiers Principaux

#### Controllers
- **ESBTPPaiementController.php** (`app/Http/Controllers/ESBTPPaiementController.php`)
  - Gère la génération des reçus PDF et HTML
  - Méthode `previewRecu()` : génère la prévisualisation HTML
  - Méthode `genererRecu()` : génère le PDF final avec settings
  - Méthode `getReceiptSettings()` : récupère la configuration depuis les settings
  - Méthode `prepareLogoBase64()` : convertit les logos uploadés en base64

#### Templates
- **recu.blade.php** (`resources/views/esbtp/paiements/recu.blade.php`)
  - Template PDF principal des reçus
  - Design moderne optimisé format A4
  - Support logo via `$settings` array et base64
  - Affichage des catégories de frais avec badges colorés

- **preview.blade.php** (`resources/views/esbtp/paiements/preview.blade.php`)
  - Template de prévisualisation HTML
  - Interface moderne avec barre d'outils d'actions
  - Design identique au PDF pour cohérence visuelle

- **index.blade.php** (`resources/views/esbtp/paiements/index.blade.php`)
  - Page liste des paiements
  - Dropdown PDF compact avec deux options
  - CSS personnalisé pour éviter les débordements

- **show.blade.php** (`resources/views/esbtp/paiements/show.blade.php`)
  - Page détails d'un paiement
  - Dropdown PDF avec gestion de l'overflow
  - Styles CSS spécifiques pour la visibilité

### Modèles de Données
- **ESBTPPaiement** : paiements des étudiants
- **ESBTPFraisCategory** : nouveau système de catégories de frais
- **ESBTPFraisConfiguration** : anciennes catégories (compatibilité)
- **ESBTPEtudiant** : données étudiants
- **ESBTPInscription** : inscriptions avec filière/niveau/année
- **Setting** : paramètres configurables de l'établissement

## Configuration

### Paramètres Principaux
- **school_name** : Nom de l'établissement
- **school_address** : Adresse complète
- **school_phone** : Numéro de téléphone  
- **school_email** : Adresse email
- **school_logo** : Logo uploadé (type 'file')
- **receipt_show_logo** : Afficher/masquer le logo sur les reçus (boolean)

### Paramètres de Catégories de Frais
Le système supporte trois types de catégories :
- **academic** : Frais académiques (scolarité, inscription)
- **service** : Services optionnels (cantine, transport)
- **administrative** : Frais administratifs (documentation, examens)

## Fonctionnalités Techniques

### Upload et Gestion du Logo
1. **Stockage** : `storage/app/public/settings/`
2. **Validation** : `image|mimes:jpeg,png,jpg,gif|max:2048`
3. **Conversion** : Base64 via `prepareLogoBase64()`
4. **Chemins testés** :
   - `storage/app/public/{logoPath}` (priorité)
   - `public/{logoPath}` (compatibilité)
   - `public/images/LOGO-KLASSCI-PNG.png` (fallback)

### Synchronisation Settings ↔ Reçus
1. **getReceiptSettings()** récupère tous les paramètres via `SettingsHelper::get()`
2. **Templates** utilisent `$settings` array
3. **Logo dynamique** : converti en base64 si paramètre activé
4. **Valeurs par défaut** : définies pour compatibilité

### Logique des Catégories de Frais
```php
// Priorité 1: Nouveau système
if ($paiement->fraisCategory) {
    $categoryInfo = [
        'name' => $paiement->fraisCategory->name,
        'type' => $paiement->fraisCategory->category_type ?? 'academic',
    ];
}
// Priorité 2: Ancien système  
elseif ($paiement->categorie) {
    $categoryInfo = [
        'name' => $paiement->categorie->nom ?? 'Catégorie ancienne',
        'type' => str_contains(strtolower($paiement->categorie->nom), 'cantine') ? 'service' : 'academic',
    ];
}
// Priorité 3: Inféré du motif
elseif ($paiement->motif) {
    $type = 'academic';
    if (str_contains($motifLower, 'cantine') || str_contains($motifLower, 'transport')) {
        $type = 'service';
    } elseif (str_contains($motifLower, 'documentation') || str_contains($motifLower, 'examen')) {
        $type = 'administrative';
    }
    $categoryInfo = ['name' => $paiement->motif, 'type' => $type];
}
```

### Génération Base64 Logo
```php
private function prepareLogoBase64($logoPath) {
    if (!$logoPath) return null;
    
    // Essayer différents chemins possibles
    $paths = [
        storage_path('app/public/' . $logoPath),
        public_path($logoPath),
        public_path('images/LOGO-KLASSCI-PNG.png'), // Fallback
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $imageData = file_get_contents($path);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            return 'data:image/' . $extension . ';base64,' . base64encode($imageData);
        }
    }
    return null;
}
```

## Interface Utilisateur

### Dropdown PDF Unifié
Remplacement des boutons multiples par un dropdown compact :

#### Page Index (`index.blade.php`)
```html
<div class="dropdown pdf-dropdown">
    <button class="btn btn-outline-primary dropdown-toggle" type="button" 
            data-bs-toggle="dropdown" title="Options PDF">
        <i class="fas fa-file-pdf"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route('esbtp.paiements.preview', $paiement->id) }}">
            <i class="fas fa-eye me-1"></i>Prévisualiser</a></li>
        <li><a class="dropdown-item" href="{{ route('esbtp.paiements.recu', $paiement->id) }}">
            <i class="fas fa-download me-1"></i>Télécharger</a></li>
    </ul>
</div>
```

#### Page Show (`show.blade.php`)  
```html
<div class="dropdown pdf-dropdown-show mb-2">
    <button class="btn-action primary dropdown-toggle" type="button" 
            data-bs-toggle="dropdown">
        <i class="fas fa-file-pdf me-1"></i>Reçu PDF
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="{{ route('esbtp.paiements.preview', $paiement->id) }}">
            <i class="fas fa-eye me-1"></i>Prévisualiser</a></li>
        <li><a class="dropdown-item" href="{{ route('esbtp.paiements.recu', $paiement->id) }}">
            <i class="fas fa-download me-1"></i>Télécharger</a></li>
    </ul>
</div>
```

### Styles CSS pour Dropdowns Compacts

#### Index CSS
```css
.pdf-dropdown .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    min-width: auto;
}

.pdf-dropdown .dropdown-menu {
    min-width: 140px;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.pdf-dropdown .dropdown-item i {
    width: 14px;
    text-align: center;
}
```

#### Show CSS avec Gestion Overflow
```css
.pdf-dropdown-show {
    position: relative;
    z-index: 1000;
}

.pdf-dropdown-show .dropdown-menu {
    min-width: 150px;
    font-size: 0.9rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border: 1px solid var(--border);
    position: absolute !important;
    z-index: 1050;
    right: 0;
    left: auto;
}

/* Forcer la visibilité du dropdown */
.action-buttons {
    overflow: visible !important;
}

.payment-header {
    overflow: visible !important;
}

.payment-header .text-end {
    overflow: visible !important;
}
```

### Barre d'Outils de Prévisualisation
```html
<div class="preview-toolbar">
    <div class="toolbar-info">
        <h4><i class="fas fa-eye me-2"></i>Prévisualisation du Reçu</h4>
        <small class="text-muted">{{ $paiement->numero_recu }} - {{ $paiement->etudiant->user->name }}</small>
    </div>
    
    <div class="preview-actions">
        <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
        
        @if($paiement->status == 'validé')
            <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}" class="btn-acasi success">
                <i class="fas fa-file-pdf me-1"></i>Générer PDF
            </a>
        @endif
        
        <button onclick="window.print()" class="btn-acasi info">
            <i class="fas fa-print me-1"></i>Imprimer
        </button>
    </div>
</div>
```

## Design System et Styles

### Template PDF Optimisé A4
Le template PDF a été entièrement repensé pour tenir sur une page A4 :

#### Dimensions Principales
- **Font-size** : `11px` (base)
- **Line-height** : `1.3` (espacement vertical réduit)
- **Container** : `max-width: 750px`, `padding: 15px`
- **Logo** : `max-width: 80px`

#### Styles Modernisés
```css
/* En-tête moderne */
.receipt-header {
    text-align: center;
    margin-bottom: 15px;
    border-bottom: 2px solid #1e40af;
    padding-bottom: 10px;
}

.receipt-title {
    font-size: 16px;
    font-weight: bold;
    color: #1e40af;
    text-transform: uppercase;
}

/* Numéro de reçu avec style moderne */
.receipt-number {
    font-size: 16px;
    font-weight: bold;
    margin: 15px 0;
    text-align: center;
    border: 2px solid #1e40af;
    padding: 8px;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-radius: 6px;
    color: #1e40af;
}

/* Tables modernisées */
.receipt-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}

.receipt-table th,
.receipt-table td {
    padding: 6px 8px;
    font-size: 10px;
    border-bottom: 1px solid #e2e8f0;
}

.receipt-table th {
    background: #f8fafc;
    font-weight: bold;
    color: #1e40af;
}

/* Montant avec mise en valeur */
.amount-display {
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    margin: 12px 0;
    color: #059669;
    padding: 12px;
    background: rgba(5, 150, 105, 0.1);
    border-radius: 6px;
    border: 2px solid #10b981;
}

/* Badges de statut */
.status-badge {
    display: inline-block;
    padding: 3px 6px;
    border-radius: 12px;
    font-size: 8px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.status-badge.success {
    background: rgba(5, 150, 105, 0.1);
    color: #059669;
    border: 1px solid #10b981;
}

.status-badge.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border: 1px solid #fbbf24;
}

.status-badge.danger {
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    border: 1px solid #ef4444;
}
```

### Styles de Prévisualisation
Le template de prévisualisation utilise un design identique mais adapté pour le web :

```css
.preview-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
}

.preview-content {
    border: 1px solid #ddd;
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    padding: 0;
    background: white;
    min-height: 800px;
}

/* Design identique au PDF mais avec couleurs CSS variables */
.receipt-title {
    color: var(--primary);
}

.amount-display {
    color: var(--success);
    background: rgba(var(--success-rgb), 0.1);
}
```

## Affichage des Catégories de Frais

### Structure des Données
```php
$categoryInfo = [
    'name' => 'Frais de scolarité',
    'type' => 'academic'  // academic|service|administrative
];

$categoryColors = [
    'academic' => 'success',      // Vert
    'service' => 'warning',       // Orange  
    'administrative' => 'info'    // Bleu
];

$typeLabels = [
    'academic' => 'Académique',
    'service' => 'Service',
    'administrative' => 'Administratif'
];
```

### Rendu dans le Reçu PDF
```html
@if($categoryInfo)
<tr>
    <th>Catégorie</th>
    <td>
        {{ $categoryInfo['name'] }}
        <span class="status-badge {{ $color }}" style="margin-left: 8px;">
            {{ $typeLabel }}
        </span>
    </td>
</tr>
@endif
```

### Rendu dans la Prévisualisation
```html
@if($categoryInfo)
<tr>
    <th>Catégorie</th>
    <td>
        {{ $categoryInfo['name'] }}
        <span class="badge bg-{{ $color }} ms-2">{{ $typeLabel }}</span>
    </td>
</tr>
@endif
```

## Routes

### Routes Principales
```php
// Prévisualisation HTML
Route::get('/paiements/{paiement}/preview', [ESBTPPaiementController::class, 'previewRecu'])
    ->name('paiements.preview');

// Génération PDF
Route::get('/paiements/{paiement}/recu', [ESBTPPaiementController::class, 'genererRecu'])
    ->name('paiements.recu');

// Liste des paiements
Route::get('/paiements', [ESBTPPaiementController::class, 'index'])
    ->name('paiements.index');

// Détails d'un paiement
Route::get('/paiements/{paiement}', [ESBTPPaiementController::class, 'show'])
    ->name('paiements.show');
```

## Workflow Utilisateur

### Processus Standard
1. **Accès** : Navigation vers `/esbtp/paiements`
2. **Sélection** : Clic sur dropdown PDF d'un paiement validé
3. **Options** : 
   - **Prévisualiser** → Vue HTML moderne avec barre d'outils
   - **Télécharger** → PDF optimisé A4 avec logo et catégories
4. **Actions prévisualisation** :
   - **Retour** vers liste/détails
   - **Générer PDF** final
   - **Imprimer** directement
5. **PDF final** : Téléchargement avec nom `Recu_{numero}.pdf`

### Permissions
- **Visualisation** : `paiements.view`
- **Génération PDF** : `paiements.validate` (paiements validés uniquement)
- **Prévisualisation** : Disponible pour tous les paiements validés

## Corrections et Améliorations

### Problèmes Résolus

#### 1. Dropdown débordant
**Problème** : Menu dropdown coupé par conteneurs avec `overflow: hidden`
**Solution** : 
- Ajout de `overflow: visible !important` sur conteneurs parents
- Position `absolute` avec `z-index: 1050`
- Alignement `right: 0; left: auto`

#### 2. PDF trop volumineux pour A4
**Problème** : Contenu débordait sur 2 pages
**Solution** :
- Réduction globale des tailles de police (14px → 11px)
- Optimisation des espacements et marges
- Ajustement des paddings de cellules
- Logo réduit (120px → 80px)

#### 3. Catégorie de frais manquante
**Problème** : Aucune information sur le type de frais
**Solution** :
- Logique de détection intelligente (nouveau → ancien → motif)
- Badges colorés par type (Académique/Service/Administratif)
- Affichage cohérent PDF et prévisualisation

#### 4. Icons confondues dans dropdowns
**Problème** : Deux boutons "œil" créaient la confusion
**Solution** :
- Dropdown unifié avec icône PDF
- Options claires : "Prévisualiser" (œil) + "Télécharger" (download)
- Taille optimisée pour éviter débordements

### Améliorations UX

#### Interface Modernisée
- **Design system** : Cohérent avec dashboard-acasi
- **Couleurs harmonisées** : Bleu primaire (#1e40af), vert succès (#059669)
- **Typographie** : Arial optimisée pour PDF et web
- **Espacement** : Variables CSS pour cohérence

#### Responsive Design
- **Mobile** : Dropdowns adaptés aux petits écrans
- **Tablet** : Interface optimisée pour tablettes
- **Desktop** : Pleine exploitation de l'espace disponible

#### Performance
- **Cache settings** : Paramètres récupérés une fois par requête
- **Base64 optimisé** : Conversion logo uniquement si nécessaire
- **CSS minifié** : Styles compacts pour PDF rapide

## Maintenance et Monitoring

### Logs à Surveiller
```php
// Génération PDF
Log::info('PDF Receipt generated', [
    'paiement_id' => $paiement->id,
    'numero_recu' => $paiement->numero_recu,
    'user_id' => auth()->id()
]);

// Settings récupérés
Log::debug('Receipt settings retrieved', [
    'school_name' => $settings['school_name'],
    'logo_present' => isset($settings['logo_base64'])
]);
```

### Commandes Utiles
```bash
# Tester les settings reçus
php artisan tinker --execute="
$controller = new App\Http\Controllers\ESBTPPaiementController();
$settings = $controller->getReceiptSettings();
print_r($settings);
"

# Vérifier un paiement spécifique
php artisan tinker --execute="
$paiement = App\Models\ESBTPPaiement::with(['fraisCategory', 'categorie'])->find(8);
echo 'Catégorie: ' . ($paiement->fraisCategory ? $paiement->fraisCategory->name : 'None');
"

# Test génération PDF
php artisan tinker --execute="
$paiement = App\Models\ESBTPPaiement::find(8);
$url = route('esbtp.paiements.recu', $paiement->id);
echo 'Test PDF: ' . $url;
"
```

### Fichiers de Configuration
- **Settings** : Géré via interface `/esbtp/settings`
- **Logo** : Upload dans `storage/app/public/settings/`
- **Permissions** : Définies dans les rôles Spatie

## Dépannage Courant

### "Logo ne s'affiche pas"
1. Vérifier que `receipt_show_logo` = '1'
2. Vérifier l'existence du fichier logo
3. Contrôler les permissions de `storage/`
4. Tester la conversion base64

### "Dropdown coupé"
1. Vérifier les styles CSS `overflow: visible !important`
2. Contrôler le `z-index` du menu
3. Tester l'alignement `dropdown-menu-end`

### "Catégorie non affichée"
1. Vérifier la relation `fraisCategory` du paiement
2. Contrôler le type de catégorie (`category_type`)
3. Tester la logique de fallback (categorie → motif)

### "PDF déborde sur 2 pages"
1. Vérifier les tailles de police (max 11px base)
2. Contrôler les marges et paddings
3. Tester avec différents contenus (long/court)

## Extensions Futures Possibles

### Fonctionnalités Avancées
1. **Templates multiples** : Différents designs de reçus
2. **Signature numérique** : Intégration de signatures électroniques
3. **QR Code** : Code de vérification sur les reçus
4. **Multi-langues** : Reçus en français/anglais/etc.
5. **Notifications** : Email automatique lors de génération
6. **Historique** : Traçabilité des générations de reçus

### Optimisations Techniques
1. **Cache PDF** : Mise en cache des reçus générés
2. **Queue Jobs** : Génération asynchrone pour gros volumes
3. **CDN Integration** : Hébergement logos sur CDN
4. **API REST** : Endpoints pour génération programmatique
5. **Webhooks** : Notifications tiers lors de génération

### Analytics et Reporting
1. **Dashboard** : Statistiques de génération
2. **Audit Trail** : Logs détaillés des accès
3. **Performance Metrics** : Temps de génération PDF
4. **Usage Reports** : Reçus générés par période/utilisateur

---

**Documentation mise à jour le** : 26/08/2025 15:45  
**Version système** : ESBTP-yAKRO v2 Pascal  
**Dernières modifications** : Système complet de prévisualisation et génération PDF des reçus de paiements avec catégories de frais et interface moderne