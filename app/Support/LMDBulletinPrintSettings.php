<?php

namespace App\Support;

final class LMDBulletinPrintSettings
{
    public static function fontFields(): array
    {
        return [
            'lmd_bulletin_font_republic' => ['label' => 'République / Ministère', 'default' => 9.0],
            'lmd_bulletin_font_school_name' => ['label' => 'Nom établissement', 'default' => 15.0],
            'lmd_bulletin_font_school_meta' => ['label' => 'Coordonnées établissement', 'default' => 8.5],
            'lmd_bulletin_font_title' => ['label' => 'Titre du bulletin', 'default' => 14.0],
            'lmd_bulletin_font_header_meta' => ['label' => 'Année / édition / niveau / semestre', 'default' => 9.0],
            'lmd_bulletin_font_establishment' => ['label' => 'Code / statut / direction', 'default' => 9.5],
            'lmd_bulletin_font_student' => ['label' => 'Identité étudiant / affectation', 'default' => 10.5],
            'lmd_bulletin_font_structure' => ['label' => 'Domaine / mention / parcours', 'default' => 10.0],
            'lmd_bulletin_font_table_header' => ['label' => 'En-tête du tableau', 'default' => 9.0],
            'lmd_bulletin_font_table' => ['label' => 'Lignes UE / ECUE', 'default' => 9.5],
            'lmd_bulletin_font_teacher' => ['label' => 'Nom des enseignants', 'default' => 8.5],
            'lmd_bulletin_font_summary' => ['label' => 'Moyenne / crédits', 'default' => 13.0],
            'lmd_bulletin_font_decision' => ['label' => 'Décision', 'default' => 10.5],
            'lmd_bulletin_font_notice' => ['label' => 'Notice importante', 'default' => 8.5],
            'lmd_bulletin_font_signature' => ['label' => 'Titre + nom de signature', 'default' => 10.0],
            'lmd_bulletin_font_legend' => ['label' => 'Légende', 'default' => 8.0],
            'lmd_bulletin_font_bottom' => ['label' => 'Pied de page', 'default' => 8.5],
        ];
    }

    public static function layoutFields(): array
    {
        return [
            'lmd_bulletin_logo_height' => [
                'label' => 'Hauteur du logo',
                'default' => 72,
                'min' => 40,
                'max' => 140,
                'step' => 2,
                'hint' => 'Logo de l’établissement dans la moitié gauche de l’en-tête.',
            ],
            'lmd_bulletin_header_padding_y' => [
                'label' => 'Marge verticale de l’en-tête',
                'default' => 6,
                'min' => 2,
                'max' => 14,
                'step' => 1,
                'hint' => 'Réduit ou augmente la hauteur du grand bandeau 50/50.',
            ],
            'lmd_bulletin_header_meta_padding_y' => [
                'label' => 'Marge verticale Année / Édition / Niveau / Semestre',
                'default' => 2,
                'min' => 0,
                'max' => 8,
                'step' => 0.5,
                'hint' => 'Agit seulement sur les deux petites lignes à droite.',
            ],
            'lmd_bulletin_signature_space_height' => [
                'label' => 'Espace libre pour signature / cachet',
                'default' => 42,
                'min' => 20,
                'max' => 120,
                'step' => 2,
                'hint' => 'Espace blanc entre « Directeur des Études » et son nom.',
            ],
            'lmd_bulletin_bottom_width_percent' => [
                'label' => 'Largeur du pied de page',
                'default' => 104,
                'min' => 90,
                'max' => 108,
                'step' => 1,
                'hint' => 'Élargit les deux lignes finales (duplicata et identité de l’établissement). 100 % = largeur du contenu, au-delà utilise les marges latérales du PDF.',
            ],
        ];
    }

    public static function colorFields(): array
    {
        return [
            'lmd_bulletin_official_header_bg',
            'lmd_bulletin_header_label_color',
            'lmd_bulletin_meta_label_color',
        ];
    }

    public static function fieldKeys(): array
    {
        return array_merge(array_keys(self::fontFields()), array_keys(self::layoutFields()), self::colorFields());
    }

    public static function validationRules(): array
    {
        $rules = [];

        foreach (array_keys(self::fontFields()) as $key) {
            $rules[$key] = ['nullable', 'numeric', 'min:6', 'max:32'];
        }

        foreach (self::layoutFields() as $key => $field) {
            $rules[$key] = ['nullable', 'numeric', 'min:'.$field['min'], 'max:'.$field['max']];
        }

        foreach (self::colorFields() as $key) {
            $rules[$key] = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        return $rules;
    }
}
