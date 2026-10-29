# ESBTP - Système d'Export de Bulletins PDF

## Vue d'ensemble
Système complet d'export de bulletins de notes en PDF dans l'application ESBTP-yAKRO, utilisant Laravel DomPDF pour la génération de documents PDF avec plusieurs templates et configurations.

## Dépendances
- **Package**: `barryvdh/laravel-dompdf` v2.2
- **Façade**: `Barryvdh\DomPDF\Facade\Pdf as DomPDF`

## Contrôleurs d'Export

### 1. ESBTPBulletinController (Principal)
**Localisation**: `/app/Http/Controllers/ESBTPBulletinController.php`

**Méthodes clés**:
- `genererPDF(ESBTPBulletin $bulletin)`
  - **Route**: `/esbtp/bulletins/{bulletin}/download`
  - **Template**: `esbtp.bulletins.bulletin-pdf`
  - **Données**: étudiant, notes, moyennes, absences, configuration

- `genererPDFParParams(Request $request)`
  - **Route**: `/esbtp-special/bulletins-pdf`
  - **Accès**: SuperAdmin uniquement
  - **Usage**: Génération sans bulletin existant

**Configuration PDF**:
```php
private function getPDFConfig() {
    return [
        'pdf_margin_top' => 15,
        'pdf_margin_bottom' => 15,
        'pdf_margin_left' => 10,
        'pdf_margin_right' => 10,
        'pdf_font_size' => 12,
        'pdf_show_watermark' => false,
        'pdf_show_signature' => true,
    ];
}
```

### 2. ParentBulletinController
**Localisation**: `/app/Http/Controllers/ESBTP/ParentBulletinController.php`

**Méthodes**:
- `downloadPdf($bulletinId)`
  - **Route**: `/bulletins/{id}/pdf`
  - **Sécurité**: Validation parent-enfant
  - **Template**: `parent.bulletins.pdf`

### 3. GradeController
**Méthode**: `generateBulletinPdf($studentId, $semesterId)`
**Template**: `grades.bulletin_pdf`

## Templates PDF

### 1. bulletin-pdf.blade.php (Principal)
**Localisation**: `/resources/views/esbtp/bulletins/bulletin-pdf.blade.php`

**Caractéristiques**:
- Utilise `SettingsHelper::getSchoolInfo()` pour infos configurables
- Structure: header + student-info + notes + moyennes + absences + signature
- Logo en base64 pour intégration PDF
- Marges configurables

**En-tête**:
```php
<div class="school-info">
    <div class="school-name">{{ $schoolInfo['name'] ?? 'ÉCOLE SUPÉRIEURE' }}</div>
    <div class="school-address">
        {{ $schoolInfo['address'] ?? '' }}<br>
        {{ $schoolInfo['phone'] ?? '' }} | {{ $schoolInfo['email'] ?? '' }}
    </div>
</div>
```

### 2. pdf-configurable.blade.php (Avec infos ESBTP)
**Localisation**: `/resources/views/esbtp/bulletins/pdf-configurable.blade.php`

**SEUL template avec vraies informations ESBTP hardcodées**:
- **Nom**: "École Spéciale du Bâtiment et des Travaux Publics"
- **Adresse**: "BP 04 BP 1234 Abidjan 04"
- **Contact**: "Tel: 00 00 00 00 • Fax: 00 00 00 00"
- **Logo**: `esbtp_logo.png`

**Code source**:
```php
<div class="school-name">
    {{ $settings['bulletin_school_name_custom'] ?: 'École Spéciale du Bâtiment et des Travaux Publics' }}
</div>
<div class="school-address">BP 04 BP 1234 Abidjan 04 • Tel: 00 00 00 00 • Fax: 00 00 00 00</div>
```

**Paramètres configurables**:
- `bulletin_font_size`: Taille de police
- `bulletin_show_logo`: Affichage du logo
- `bulletin_show_school_info`: Affichage infos école

### 3. pdf.blade.php (Design moderne)
**Localisation**: `/resources/views/esbtp/bulletins/pdf.blade.php`

**Caractéristiques**:
- Design avec gradient header
- Variables: `school_name`, `school_address`, `school_phone`
- Nom par défaut: "ESBTP-yAKRO"
- Structure 3 colonnes: logo + centre + droite

### 4. Templates secondaires
- `/resources/views/pdf/bulletin.blade.php` - Template générique simple
- `/resources/views/parent/bulletins/pdf.blade.php` - Spécifique aux parents

## Routes d'Export

```php
// Principal
Route::get('/esbtp/bulletins/{bulletin}/download', [ESBTPBulletinController::class, 'genererPDF'])
    ->name('esbtp.bulletins.download');

// Paramètres avancés (SuperAdmin)
Route::get('/esbtp-special/bulletins-pdf', [ESBTPBulletinController::class, 'genererPDFParParams'])
    ->name('esbtp.bulletins.pdf-params');

// Parents
Route::get('/bulletins/{id}/pdf', [ParentController::class, 'downloadPdf'])
    ->name('bulletins.pdf');
```

## Données Incluses dans les PDF

### Informations Étudiant
- Nom, prénom, matricule
- Photo (si disponible)
- Classe, filière, niveau
- Année universitaire

### Données Académiques
- Notes par matière avec coefficients
- Moyennes (générale, technique, par matière)
- Rang de l'étudiant
- Absences justifiées/non justifiées

### Configuration École
- Logo de l'établissement
- Nom de l'école
- Adresse, téléphone, email
- Signature et cachet (optionnels)
- Watermark (optionnel)

## Workflow de Génération

1. **Récupération des données**: Bulletin avec relations (étudiant, classe, notes)
2. **Calculs**: Moyennes générale/technique, totaux par matière
3. **Absences**: Via `ESBTPAbsenceService`
4. **Configuration**: Settings PDF via `SettingsHelper`
5. **Template**: Sélection du template approprié
6. **Génération**: DomPDF avec données compilées
7. **Téléchargement**: Fichier nommé selon format standard

## Sécurité et Permissions

- **Authentification**: Requise pour tous les exports
- **Autorisation**: Basée sur les rôles (parent, admin, etc.)
- **Validation**: Parents ne voient que les bulletins de leurs enfants
- **Logs**: Traçabilité des générations PDF
- **Throttling**: Limitation des exports (route spécifique)

## Informations Officielles ESBTP

**Nom complet**: École Spéciale du Bâtiment et des Travaux Publics
**Adresse**: BP 04 BP 1234 Abidjan 04
**Téléphone**: 00 00 00 00
**Fax**: 00 00 00 00
**Logo**: esbtp_logo.png

*Note: Ces informations sont hardcodées uniquement dans `pdf-configurable.blade.php`*