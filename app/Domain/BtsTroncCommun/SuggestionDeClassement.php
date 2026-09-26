<?php

namespace App\Domain\BtsTroncCommun;

use App\Domain\BtsTroncCommun\Diagnostics\TcSpecialiteLeakEvidence;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiereFilierNiveau;

/**
 * Ce que l'ecran « Maquette du bulletin » propose pour chaque matiere d'un
 * couple de tronc commun : tronc commun, specialite, ou rien. Une proposition
 * n'enregistre rien : l'ecole l'accepte ou non.
 *
 * Deux sources de preuve, dans cet ordre :
 *
 * 1. La planification du tronc commun, quand elle existe. C'est le critere du
 *    diagnostic des fuites (TcSpecialiteLeakDiagnostic::typeDeSuspicion) :
 *    planifiee, la matiere releve du tronc commun ; non planifiee et rattachee
 *    a une filiere fille, elle est suspecte, donc proposee en specialite. Les
 *    deux ecrans repondent ainsi la meme chose a la meme question.
 *
 * 2. Sans planification (le cas de Yakro), les evaluations non annulees de
 *    l'annee. Une matiere evaluee dans au moins la moitie des classes du tronc
 *    commun en releve. Une seule evaluation egaree ne suffit pas : c'est
 *    justement le symptome qu'on cherche (une epreuve de Securite posee par
 *    erreur sur une classe de tronc commun), et « Appliquer les suggestions »
 *    la transformerait en classement officiel. Une matiere evaluee seulement
 *    dans les classes de specialite n'en releve pas.
 *
 * Entre les deux, on ne propose rien : deviner serait remplacer une question
 * par une erreur silencieuse.
 */
final class SuggestionDeClassement
{
    public function __construct(private readonly TcSpecialiteLeakEvidence $pieces) {}

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

        $matieres = ESBTPMatiereFilierNiveau::where('filiere_id', $troncCommun->id)
            ->where('niveau_etude_id', $niveauId)
            ->pluck('matiere_id')
            ->map(fn ($id) => (int) $id);

        $planifiees = $this->pieces->planifiees((int) $troncCommun->id, $niveauId, (int) $annee->id);

        return $planifiees !== []
            ? $this->selonLaPlanification($matieres->all(), $planifiees, $fillesIds, $niveauId)
            : $this->selonLesEvaluations($matieres->all(), $troncCommun, $fillesIds, $niveauId, (int) $annee->id);
    }

    /**
     * @param  list<int>  $matieres
     * @param  array<int, true>  $planifiees
     * @param  list<int>  $fillesIds
     * @return array<int, array{valeur: string, raison: string}>
     */
    private function selonLaPlanification(array $matieres, array $planifiees, array $fillesIds, int $niveauId): array
    {
        $rattacheesAUneFille = ESBTPMatiereFilierNiveau::whereIn('filiere_id', $fillesIds)
            ->where('niveau_etude_id', $niveauId)
            ->pluck('matiere_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $suggestions = [];
        foreach ($matieres as $id) {
            if (isset($planifiees[$id])) {
                $suggestions[$id] = $this->suggestion(ESBTPMatiereFilierNiveau::TRONC_COMMUN, 'Planifiée pour le tronc commun cette année.');
            } elseif (isset($rattacheesAUneFille[$id])) {
                $suggestions[$id] = $this->suggestion(ESBTPMatiereFilierNiveau::SPECIALITE, 'Non planifiée pour le tronc commun, et rattachée à une spécialité.');
            }
        }

        return $suggestions;
    }

    /**
     * @param  list<int>  $matieres
     * @param  list<int>  $fillesIds
     * @return array<int, array{valeur: string, raison: string}>
     */
    private function selonLesEvaluations(array $matieres, ESBTPFiliere $troncCommun, array $fillesIds, int $niveauId, int $anneeId): array
    {
        $classesTc = ESBTPClasse::where('filiere_id', $troncCommun->id)->where('niveau_etude_id', $niveauId)->pluck('id')->all();
        $classesFilles = ESBTPClasse::whereIn('filiere_id', $fillesIds)->where('niveau_etude_id', $niveauId)->pluck('id')->all();

        $classesTcParMatiere = $this->classesEvalueesParMatiere($classesTc, $anneeId);
        $classesFillesParMatiere = $this->classesEvalueesParMatiere($classesFilles, $anneeId);
        $totalTc = count($classesTc);

        $suggestions = [];
        foreach ($matieres as $id) {
            $nbTc = $classesTcParMatiere[$id] ?? 0;

            if ($totalTc > 0 && $nbTc * 2 >= $totalTc) {
                $suggestions[$id] = $this->suggestion(
                    ESBTPMatiereFilierNiveau::TRONC_COMMUN,
                    "Évaluée dans {$nbTc} classe(s) de tronc commun sur {$totalTc} cette année."
                );
            } elseif ($nbTc === 0 && ($classesFillesParMatiere[$id] ?? 0) > 0) {
                $suggestions[$id] = $this->suggestion(
                    ESBTPMatiereFilierNiveau::SPECIALITE,
                    'Évaluée seulement dans les classes de spécialité cette année.'
                );
            }
        }

        return $suggestions;
    }

    /**
     * Nombre de classes, parmi celles donnees, ou chaque matiere porte au
     * moins une evaluation non annulee de l'annee.
     *
     * @param  list<int>  $classes
     * @return array<int, int>
     */
    private function classesEvalueesParMatiere(array $classes, int $anneeId): array
    {
        if ($classes === []) {
            return [];
        }

        return ESBTPEvaluation::query()
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('classe_id', $classes)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('matiere_id, COUNT(DISTINCT classe_id) as nb')
            ->groupBy('matiere_id')
            ->pluck('nb', 'matiere_id')
            ->mapWithKeys(fn ($nb, $id) => [(int) $id => (int) $nb])
            ->all();
    }

    /** @return array{valeur: string, raison: string} */
    private function suggestion(string $valeur, string $raison): array
    {
        return ['valeur' => $valeur, 'raison' => $raison];
    }
}
