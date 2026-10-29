# Notes — Export PDF paiements (without_skill / baseline)

## Approche
Construit en lisant les templates PDF existants du projet (export-pdf.blade.php, theme.blade.php, SettingsHelper).

## Décisions de design
- Utilise `@include('pdf.partials.theme')` pour les couleurs
- Header bleu (#0453cb), logo centré (base64 depuis $settings)
- Table : 7 colonnes (N°, Étudiant+matricule, Montant, Mode, Date, Statut, Catégorie)
- Lignes alternantes : impair=blanc, pair=gris clair (#f3f4f6)
- Badges statut : vert validé, orange en_attente, rouge rejeté

## Notes techniques
- Utilise `font-family: Arial` (PAS DejaVu Sans — pas mentionné explicitement)
- Pas de mention explicite du fix tr→td pour backgrounds (utilisé implicitement via nth-child td)
- N'utilise pas `public_path()` pour les images (dépend de $settings['logo_base64'])
- N'utilise pas `SettingsHelper::getPdfSettings()` directement (attend $settings passé par contrôleur)
