<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun\Diagnostics;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPResultat;
use Illuminate\Support\Collection;

/**
 * Les pieces du diagnostic, chargees par cohorte et non par etudiant.
 *
 * Chaque methode rend une seule requete pour toute la cohorte d'une classe a
 * un semestre : une classe de tronc commun compte couramment soixante
 * etudiants, et un diagnostic qui interrogerait la base pour chacun tomberait
 * sur la limite d'execution des serveurs mutualises.
 *
 * Lecture seule.
 */
final class TcSpecialiteLeakEvidence
{
    /**
     * Notes de la cohorte sur l'annee et le semestre, TOUTES classes
     * d'evaluation confondues : c'est justement la classe de l'evaluation que
     * le diagnostic doit pouvoir montrer.
     *
     * Memes exclusions que la generation du bulletin : evaluation annulee ou
     * supprimee, note supprimee.
     *
     * @param  list<int>  $etudiantIds
     * @return Collection<int, object>
     */
    public function notes(array $etudiantIds, int $anneeId, int $semestre): Collection
    {
        if ($etudiantIds === []) {
            return collect();
        }

        return ESBTPNote::query()
            ->join('esbtp_evaluations', 'esbtp_evaluations.id', '=', 'esbtp_notes.evaluation_id')
            ->leftJoin('esbtp_classes', 'esbtp_classes.id', '=', 'esbtp_evaluations.classe_id')
            ->whereIn('esbtp_notes.etudiant_id', $etudiantIds)
            ->whereNull('esbtp_notes.deleted_at')
            ->whereNull('esbtp_evaluations.deleted_at')
            ->where('esbtp_evaluations.annee_universitaire_id', $anneeId)
            ->where('esbtp_evaluations.status', '!=', 'cancelled')
            ->whereIn('esbtp_evaluations.periode', ESBTPEvaluation::aliasDePeriode('semestre'.$semestre))
            ->orderBy('esbtp_notes.id')
            ->toBase()
            ->get([
                'esbtp_notes.id as note_id',
                'esbtp_notes.etudiant_id',
                'esbtp_notes.note',
                'esbtp_notes.is_absent',
                'esbtp_evaluations.id as evaluation_id',
                'esbtp_evaluations.titre',
                'esbtp_evaluations.date_evaluation',
                'esbtp_evaluations.periode',
                'esbtp_evaluations.matiere_id',
                'esbtp_evaluations.classe_id as evaluation_classe_id',
                'esbtp_classes.name as evaluation_classe',
            ]);
    }

    /**
     * Moyennes enregistrees sur la classe de tronc commun. Le bulletin les
     * reprend telles quelles (« le manuel l'emporte ») : une moyenne persistee
     * depuis une note egaree survit a l'exclusion de cette note.
     *
     * @param  list<int>  $etudiantIds
     * @return Collection<int, object>
     */
    public function moyennes(array $etudiantIds, int $classeId, int $anneeId, int $semestre): Collection
    {
        if ($etudiantIds === []) {
            return collect();
        }

        return ESBTPResultat::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', ESBTPEvaluation::aliasDePeriode('semestre'.$semestre))
            ->orderBy('id')
            ->toBase()
            ->get(['id as resultat_id', 'etudiant_id', 'matiere_id', 'moyenne', 'periode']);
    }

    /**
     * L'inscription de chaque etudiant pour l'annee, elue comme le fait
     * `BtsAnnualClassMapResolver` : celle de la classe demandee d'abord, puis
     * l'active, puis la plus recente.
     *
     * @param  list<int>  $etudiantIds
     * @return array<int, ESBTPInscription>
     */
    public function inscriptions(array $etudiantIds, int $anneeId, int $classeId): array
    {
        if ($etudiantIds === []) {
            return [];
        }

        return ESBTPInscription::query()
            ->with([
                'etudiant:id,matricule,nom,prenoms',
                'filiere',
                'classe.filiere',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ])
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('annee_universitaire_id', $anneeId)
            ->get()
            ->sortBy([
                fn ($a, $b) => ((int) $a->classe_id !== $classeId) <=> ((int) $b->classe_id !== $classeId),
                fn ($a, $b) => ($a->status !== 'active') <=> ($b->status !== 'active'),
                fn ($a, $b) => (string) $b->date_inscription <=> (string) $a->date_inscription,
                fn ($a, $b) => $b->id <=> $a->id,
            ])
            ->unique('etudiant_id')
            ->keyBy('etudiant_id')
            ->all();
    }

    /**
     * Matieres planifiees pour le couple de tronc commun sur l'annee, tous
     * semestres confondus. Le resolveur de maquette ne connait pas les
     * semestres : une matiere planifiee au seul semestre 1 sort aussi dans la
     * liste du semestre 2. La signaler pour autant serait du bruit, pas une fuite.
     *
     * @return array<int, true>
     */
    public function planifiees(int $filiereId, int $niveauId, int $anneeId): array
    {
        return ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('is_active', true)
            ->pluck('matiere_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }
}
