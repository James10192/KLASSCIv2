<?php

namespace App\Domain\Assistant\Actions\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\DB;

/**
 * Comment Nanan désigne un élève ou une inscription : par MATRICULE (ce que
 * l'école cite) ou par identifiant d'inscription (ce qu'un outil a rendu).
 * Jamais par nom seul : deux homonymes ne sont pas la même personne.
 *
 * Partagé par les actions du lot inscriptions et frais, pour qu'un matricule
 * introuvable ou ambigu se dise partout de la même façon.
 */
class DesignationDInscriptions
{
    public function anneeCouranteId(): ?int
    {
        $id = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        return $id ? (int) $id : null;
    }

    public function etudiantParMatricule(string $matricule): ?ESBTPEtudiant
    {
        $matricule = trim($matricule);
        if ($matricule === '') {
            return null;
        }

        $trouves = ESBTPEtudiant::query()
            ->whereRaw("UPPER(REPLACE(matricule, ' ', '')) = ?", [mb_strtoupper(str_replace(' ', '', $matricule))])
            ->limit(2)->get();

        return $trouves->count() === 1 ? $trouves->first() : null;
    }

    /**
     * L'inscription de l'année en cours d'un élève, par matricule ou par identifiant.
     *
     * @return array{0: ?ESBTPInscription, 1: ?string} l'inscription, ou la question à poser
     */
    public function inscriptionCourante(?string $matricule, ?int $inscriptionId): array
    {
        if ($inscriptionId) {
            $inscription = ESBTPInscription::find($inscriptionId);

            return $inscription ? [$inscription, null] : [null, "Inscription #{$inscriptionId} introuvable."];
        }
        if (! $matricule || trim($matricule) === '') {
            return [null, 'Quel élève ? Donne son matricule.'];
        }

        $etudiant = $this->etudiantParMatricule($matricule);
        if (! $etudiant) {
            return [null, "Matricule {$matricule} introuvable (ou porté par plusieurs élèves) : vérifie-le."];
        }

        $annee = $this->anneeCouranteId();
        $inscriptions = ESBTPInscription::where('etudiant_id', $etudiant->id)
            ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee))
            ->get();

        return match ($inscriptions->count()) {
            1 => [$inscriptions->first(), null],
            0 => [null, "{$matricule} n'a aucune inscription cette année."],
            default => [null, "{$matricule} a plusieurs inscriptions cette année : donne l'identifiant de celle visée."],
        };
    }

    /** Pose un verrou d'écriture sur ces lignes avant de relire leur état. */
    public static function verrouiller(string $table, array $ids): void
    {
        if ($ids !== []) {
            DB::table($table)->whereIn('id', $ids)->lockForUpdate()->get(['id']);
        }
    }
}
