<?php

namespace App\Support;

use App\Models\Setting;

final class ReceiptPrintSettings
{
    public const PREFIX = 'receipt_print_';

    public static function fields(): array
    {
        return [
            'font_school' => ['label' => 'Nom établissement', 'default' => 11, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_contact' => ['label' => 'Coordonnées établissement', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_title' => ['label' => 'Titre', 'default' => 12, 'min' => 9, 'max' => 18, 'step' => 0.5, 'unit' => 'pt'],
            'font_year' => ['label' => 'Année scolaire', 'default' => 9, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_copy' => ['label' => 'Libellé exemplaire', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_number' => ['label' => 'Numéro de reçu', 'default' => 13, 'min' => 9, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_label' => ['label' => 'Libellés des champs', 'default' => 7.5, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_student' => ['label' => 'Nom étudiant', 'default' => 10, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_matricule' => ['label' => 'Matricule', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_filiere' => ['label' => 'Filière', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_niveau' => ['label' => 'Niveau', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_date' => ['label' => 'Date de paiement', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_timestamp' => ['label' => 'Horodatages', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_affectation' => ['label' => 'Affectation', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_mode' => ['label' => 'Mode de paiement', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_status' => ['label' => 'Statut', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_category' => ['label' => 'Ventilation des frais', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_reglement' => ['label' => 'Règlement', 'default' => 8.5, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_tranche' => ['label' => 'Tranche', 'default' => 8.5, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_reference' => ['label' => 'Référence', 'default' => 8.5, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_amount_label' => ['label' => 'Libellé montant', 'default' => 9, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_amount' => ['label' => 'Montant principal', 'default' => 14, 'min' => 10, 'max' => 18, 'step' => 0.5, 'unit' => 'pt'],
            'font_currency' => ['label' => 'Devise', 'default' => 9, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_words' => ['label' => 'Montant en lettres', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_fee' => ['label' => 'État des frais', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_check' => ['label' => 'Cases des frais', 'default' => 9, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_fee_note' => ['label' => 'Annotations frais', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_total_label' => ['label' => 'Libellés des soldes', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_total' => ['label' => 'Déjà versé', 'default' => 12, 'min' => 9, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_balance' => ['label' => 'Reste à payer', 'default' => 12, 'min' => 9, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_history_label' => ['label' => 'Titre historique', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_history' => ['label' => 'Historique des versements', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_cashier_label' => ['label' => 'Libellé encaisseur', 'default' => 8, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_cashier' => ['label' => 'Encaisseur', 'default' => 10, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_signature_title' => ['label' => 'Intitulés signatures', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_signature' => ['label' => 'Nom/date signature', 'default' => 9, 'min' => 8, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_notice' => ['label' => 'Mention légale', 'default' => 7.5, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_footer' => ['label' => 'Pied de page', 'default' => 7.5, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'font_cut' => ['label' => 'Ligne de découpe', 'default' => 7, 'min' => 7, 'max' => 14, 'step' => 0.5, 'unit' => 'pt'],
            'margin_top' => ['label' => 'Marge haute', 'default' => 5, 'min' => 3, 'max' => 12, 'step' => 0.5, 'unit' => 'mm'],
            'margin_bottom' => ['label' => 'Marge basse', 'default' => 5, 'min' => 3, 'max' => 12, 'step' => 0.5, 'unit' => 'mm'],
            'margin_left' => ['label' => 'Marge gauche', 'default' => 5, 'min' => 3, 'max' => 12, 'step' => 0.5, 'unit' => 'mm'],
            'margin_right' => ['label' => 'Marge droite', 'default' => 5, 'min' => 3, 'max' => 12, 'step' => 0.5, 'unit' => 'mm'],
            'logo_height' => ['label' => 'Hauteur logo', 'default' => 10, 'min' => 5, 'max' => 18, 'step' => 0.5, 'unit' => 'mm'],
            'logo_width' => ['label' => 'Largeur logo', 'default' => 24, 'min' => 12, 'max' => 36, 'step' => 1, 'unit' => 'mm'],
            'padding' => ['label' => 'Espacement vertical des cellules', 'default' => 0.45, 'min' => 0.2, 'max' => 1.5, 'step' => 0.05, 'unit' => 'mm'],
            'gap' => ['label' => 'Espacement entre sections', 'default' => 0.55, 'min' => 0.2, 'max' => 1.5, 'step' => 0.05, 'unit' => 'mm'],
            'line_height' => ['label' => 'Interligne', 'default' => 1.05, 'min' => 1, 'max' => 1.25, 'step' => 0.05, 'unit' => '×'],
            'signature_space' => ['label' => 'Réserve signature / cachet', 'default' => 15, 'min' => 15, 'max' => 25, 'step' => 0.5, 'unit' => 'mm'],
            'watermark_opacity' => ['label' => 'Opacité filigrane (0 = désactivé)', 'default' => 3, 'min' => 0, 'max' => 10, 'step' => 1, 'unit' => '%'],
            'watermark_size' => ['label' => 'Largeur filigrane', 'default' => 48, 'min' => 20, 'max' => 65, 'step' => 1, 'unit' => '%'],
            'fee_columns' => ['label' => 'Colonnes des frais', 'default' => 3, 'min' => 2, 'max' => 3, 'step' => 1, 'unit' => ''],
            'cut_height' => ['label' => 'Hauteur ligne de découpe', 'default' => 4, 'min' => 3, 'max' => 7, 'step' => 0.5, 'unit' => 'mm'],
        ];
    }

    public static function defaults(): array
    {
        return array_map(fn ($field) => $field['default'], self::fields());
    }

    public static function read(): array
    {
        $values = [];
        foreach (self::fields() as $key => $field) {
            $value = Setting::get(self::PREFIX.$key, $field['default']);
            $values[$key] = self::validNumber($value, $field) ? (float) $value : (float) $field['default'];
        }

        return $values;
    }

    private static function validNumber(mixed $value, array $field): bool
    {
        return is_numeric($value) && is_finite((float) $value)
            && (float) $value >= $field['min'] && (float) $value <= $field['max']
            && abs(((float) $value - $field['min']) / $field['step'] - round(((float) $value - $field['min']) / $field['step'])) < 0.00001;
    }

    /** Validate only the submitted receipt keys; no writes during preview. */
    public static function overrides(array $input): array
    {
        $values = [];
        $errors = [];
        foreach (self::fields() as $key => $field) {
            $name = 'setting_'.self::PREFIX.$key;
            if (! array_key_exists($name, $input)) {
                continue;
            }
            if (! self::validNumber($input[$name], $field)) {
                $errors[$name] = 'Valeur invalide pour '.$field['label'].' ('.$field['min'].'–'.$field['max'].' '.$field['unit'].').';
            } else {
                $values[$key] = (float) $input[$name];
            }
        }
        if ($errors !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return $values;
    }

    /** Existing isolated settings table; create only when saving. */
    public static function ensureDefaults(): void
    {
        foreach (self::fields() as $key => $field) {
            Setting::firstOrCreate(['key' => self::PREFIX.$key], [
                'value' => (string) $field['default'],
                'default_value' => (string) $field['default'],
                'type' => 'float',
                'group' => 'comptabilite',
                'category' => 'comptabilite',
                'description' => $field['label'].' ('.$field['unit'].')',
                'is_required' => false,
                'is_active' => true,
                'validation_rules' => ['required', 'numeric', 'min:'.$field['min'], 'max:'.$field['max']],
            ]);
        }
    }

    /** Compact spacing first, then a bounded legible preset; never clip content. */
    public static function compact(array $values): array
    {
        return array_replace($values, [
            'margin_top' => min($values['margin_top'], 5),
            'margin_bottom' => min($values['margin_bottom'], 5),
            'margin_left' => min($values['margin_left'], 5),
            'margin_right' => min($values['margin_right'], 5),
            'padding' => 0.2, 'gap' => 0.2, 'line_height' => 1,
            'logo_height' => min($values['logo_height'], 8), 'cut_height' => 3,
        ]);
    }
}
