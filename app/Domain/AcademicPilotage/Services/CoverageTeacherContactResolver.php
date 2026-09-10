<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Qui relancer quand les notes d'une matiere manquent.
 *
 * Isole a dessein : c'est la SEULE lecture du planning general dans le calcul
 * de la couverture, et elle est strictement informative. Ni les matieres
 * attendues, ni le semestre, ni aucun compteur n'en dependent — le planning
 * n'entre pas dans le denominateur, il donne un nom et un numero.
 *
 * Une requete pour toute la classe, jamais une par matiere.
 */
final class CoverageTeacherContactResolver
{
    /**
     * @param  Collection<int, \App\Models\ESBTPMatiere>  $matieres
     * @return array<int, array<string, mixed>|null> matiere_id => contact (null = indetermine)
     */
    public function pourLaClasse(ESBTPClasse $classe, int $anneeId, ?int $semestre, Collection $matieres): array
    {
        if ($matieres->isEmpty() || ! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return [];
        }

        if (! Schema::hasTable('esbtp_planifications_academiques')) {
            return [];
        }

        $requete = ESBTPPlanificationAcademique::query()
            ->where('annee_universitaire_id', $anneeId)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('is_active', true)
            ->whereNotNull('enseignant_principal_id')
            ->whereIn('matiere_id', $matieres->pluck('id')->all());

        // Periode annuelle : pas de semestre a imposer, on prend les deux.
        if ($semestre !== null) {
            $requete->where('semestre', $semestre);
        }

        $lignes = $requete->with('enseignantPrincipal:id,name,phone')
            ->get(['matiere_id', 'enseignant_principal_id']);

        $carte = [];

        foreach ($lignes->groupBy('matiere_id') as $matiereId => $duMemeSujet) {
            $carte[(int) $matiereId] = $this->contactUnique($duMemeSujet);
        }

        return $carte;
    }

    /**
     * Deux enseignants differents sur la meme matiere : on ne tranche pas au
     * hasard. Designer la mauvaise personne ferait relancer quelqu'un qui n'y
     * peut rien ; un contact indetermine se voit et se corrige.
     *
     * @return array<string, mixed>|null
     */
    private function contactUnique(Collection $lignes): ?array
    {
        if ($lignes->pluck('enseignant_principal_id')->unique()->count() !== 1) {
            return null;
        }

        $enseignant = $lignes->first()->enseignantPrincipal;

        if (! $enseignant) {
            return null;
        }

        return [
            'id' => (int) $enseignant->id,
            'name' => (string) $enseignant->name,
            // `users` porte bien `phone` — jamais `telephone`, qui n'existe que
            // sur les etudiants et les parents et rendrait null en silence.
            'phone' => $enseignant->phone,
            'source' => 'planning',
        ];
    }
}
