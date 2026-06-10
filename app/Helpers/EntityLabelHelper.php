<?php

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * Convertit un nom de classe de modèle (App\Models\ESBTPNote) en libellé
 * humain affichable, SANS le préfixe technique « ESBTP ».
 *
 * Source de vérité unique pour l'affichage des types d'entités côté audit,
 * logs, exports, notifications. Demande fondateur Marcel (juin 2026) :
 * « au lieu de ESBTP Note tu mets seulement Note ».
 */
class EntityLabelHelper
{
    /**
     * Libellés français explicites (singulier) pour les entités connues.
     * Clé = FQCN du modèle. Toute entrée ici prime sur le fallback.
     */
    public const MAP = [
        'App\Models\ESBTPPaiement' => 'Paiement',
        'App\Models\ESBTPDepense' => 'Dépense',
        'App\Models\ESBTPFacture' => 'Facture',
        'App\Models\ESBTPFactureDetail' => 'Détail facture',
        'App\Models\ESBTPFraisScolarite' => 'Frais de scolarité',
        'App\Models\ESBTPFraisCategorie' => 'Catégorie de frais',
        'App\Models\ESBTPSalaire' => 'Salaire',
        'App\Models\ESBTPBourse' => 'Bourse',
        'App\Models\ESBTPEtudiant' => 'Étudiant',
        'App\Models\ESBTPInscription' => 'Inscription',
        'App\Models\ESBTPNote' => 'Note',
        'App\Models\ESBTPEvaluation' => 'Évaluation',
        'App\Models\ESBTPBulletin' => 'Bulletin',
        'App\Models\ESBTPResultat' => 'Résultat',
        'App\Models\ESBTPClasse' => 'Classe',
        'App\Models\ESBTPMatiere' => 'Matière',
        'App\Models\ESBTPFiliere' => 'Filière',
        'App\Models\ESBTPAnneeUniversitaire' => 'Année universitaire',
        'App\Models\ESBTPSeanceCours' => 'Séance de cours',
        'App\Models\ESBTPEmploiTemps' => 'Emploi du temps',
        'App\Models\ESBTPAttendance' => 'Présence',
        'App\Models\User' => 'Utilisateur',
        'App\Models\Role' => 'Rôle',
        'App\Models\Permission' => 'Permission',
    ];

    /**
     * Retourne le libellé affichable d'une classe de modèle, sans « ESBTP ».
     *
     * @param  string|null  $class  FQCN ou nom court du modèle.
     */
    public static function for(?string $class): string
    {
        if (! $class) {
            return '—';
        }

        if (isset(self::MAP[$class])) {
            return self::MAP[$class];
        }

        // Fallback générique : retire le préfixe ESBTP puis sépare le CamelCase
        // en gardant les acronymes (LMD, TPE...) groupés. Ex :
        //   ESBTPLMDJury    -> "LMD Jury"
        //   ESBTPFactureDetail -> "Facture Detail"
        $base = class_basename($class);
        $base = preg_replace('/^ESBTP/', '', $base);
        $base = preg_replace('/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', (string) $base);
        $base = trim((string) $base);

        return $base !== '' ? $base : class_basename($class);
    }

    /**
     * Variante pluriel (pour titres de sections, colonnes de tableaux).
     */
    public static function plural(?string $class): string
    {
        $label = self::for($class);

        return $label === '—' ? $label : Str::plural($label);
    }
}
