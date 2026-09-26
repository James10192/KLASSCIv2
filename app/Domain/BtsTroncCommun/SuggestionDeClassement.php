<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPPlanificationAcademique;

/**
 * Ce que l'ecran « Maquette du bulletin » propose pour chaque matiere d'un
 * couple de tronc commun : tronc commun, specialite, ou rien.
 *
 * La proposition se fonde sur ce que l'ecole a reellement fait cette annee :
 * une matiere evaluee ou planifiee dans les classes du tronc commun en releve ;
 * une matiere evaluee seulement dans les classes de specialite n'en releve pas.
 *
 * L'ancienne regle — « rattachee aussi a une filiere fille, donc specialite » —
 * se trompait des que le rattachement couvrait toutes les filieres, ce qui est
 * le cas general : a Yakro, les mathematiques du tronc commun etaient proposees
 * en specialite, comme les vingt autres matieres.
 *
 * Sans aucune evaluation ni planification, on ne propose rien : deviner la
 * serait remplacer une question par une erreur silencieuse.
 */
final class SuggestionDeClassement
{
    /**
     * @return array<int, array{valeur: string, raison: string}>
     */
    public function pourCouple(ESBTPFiliere $troncCommun, int $niveauId): array
    {
        $annee = ESBTPAnneeUniversitaire::anneeCourante();
        $fillesIds = ESBTPFiliere::where('parent_id', $troncCommun->id)->pluck('id')->all();

        if ($annee === null || $fillesIds === []) {
            return [];
        }

        $classesTc = ESBTPClasse::where('filiere_id', $troncCommun->id)->where('niveau_etude_id', $niveauId)->pluck('id')->all();
        $classesFilles = ESBTPClasse::whereIn('filiere_id', $fillesIds)->where('niveau_etude_id', $niveauId)->pluck('id')->all();

        $evaluees = fn (array $classes) => $classes === [] ? [] : ESBTPEvaluation::query()
            ->where('annee_universitaire_id', $annee->id)
            ->whereIn('classe_id', $classes)
            ->distinct()
            ->pluck('matiere_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $evalueesTc = $evaluees($classesTc);
        $evalueesFilles = $evaluees($classesFilles);
        $planifieesTc = ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $troncCommun->id)
            ->where('niveau_etude_id', $niveauId)
            ->where('annee_universitaire_id', $annee->id)
            ->where('is_active', true)
            ->pluck('matiere_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $suggestions = [];
        $matieres = ESBTPMatiereFilierNiveau::where('filiere_id', $troncCommun->id)
            ->where('niveau_etude_id', $niveauId)
            ->pluck('matiere_id');

        foreach ($matieres as $matiereId) {
            $id = (int) $matiereId;

            if (isset($evalueesTc[$id]) || isset($planifieesTc[$id])) {
                $suggestions[$id] = [
                    'valeur' => ESBTPMatiereFilierNiveau::TRONC_COMMUN,
                    'raison' => isset($evalueesTc[$id])
                        ? 'Évaluée dans les classes de tronc commun cette année.'
                        : 'Planifiée pour le tronc commun cette année.',
                ];
            } elseif (isset($evalueesFilles[$id])) {
                $suggestions[$id] = [
                    'valeur' => ESBTPMatiereFilierNiveau::SPECIALITE,
                    'raison' => 'Évaluée seulement dans les classes de spécialité cette année.',
                ];
            }
        }

        return $suggestions;
    }
}
