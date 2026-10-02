<?php

declare(strict_types=1);

namespace App\Domain\Notes;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saisie exceptionnelle, traçable et idempotente de notes LMD provenant d'une
 * fiche officielle (relevé d'une année écoulée) quand les évaluations n'avaient
 * pas été créées. Un seul chemin pour la CLI et pour Nanan.
 *
 * Une ligne est toujours créée dans esbtp_evaluations avant la note : une note
 * orpheline perdrait sa provenance et son lien avec la matière du bulletin.
 * Les évaluations sont terminées (comptées au bulletin) mais non publiées aux
 * étudiants.
 */
final class RegularisationDeNotesLmd
{
    /**
     * @param  array{etudiant_id: int, classe_id: int, annee_universitaire_id?: ?int, periode: string,
     *               date_regularisation: string, motif: string, notes: list<array{matiere_id: int, note: float|int}>}  $e
     * @return array{annee: string, lignes: list<array{matiere_id: int, matiere: string, evaluation: string, avant: ?float, apres: float, evaluation_id?: int, note_id?: int}>}
     *
     * @throws ValidationException quand la classe, l'inscription ou une matière ne convient pas
     */
    public function appliquer(array $e, bool $simulation, int $userId, string $canal = 'CLI'): array
    {
        $classe = ESBTPClasse::findOrFail((int) $e['classe_id']);
        if (! CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            $this->refuser('classe_id', 'Cette régularisation est réservée aux classes LMD.');
        }

        $anneeId = (int) ($e['annee_universitaire_id'] ?? 0) ?: (int) ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        $annee = ESBTPAnneeUniversitaire::find($anneeId) ?? $this->refuser('annee_universitaire_id', 'Aucune année universitaire active trouvée.');

        $inscrit = ESBTPInscription::query()
            ->where('etudiant_id', (int) $e['etudiant_id'])
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->exists();
        if (! $inscrit) {
            $this->refuser('etudiant_id', "L'étudiant n'est pas inscrit activement dans cette classe pour cette année.");
        }

        // La période d'une évaluation LMD est le semestre ABSOLU (semestre3 pour
        // une L2) : c'est ce que le bulletin LMD lit, et ce que porte la maquette.
        $periode = (string) $e['periode'];
        $semestre = (int) preg_replace('/\D/', '', $periode);
        if (! in_array($semestre, $classe->getSemestresLMD(), true)) {
            $this->refuser('periode', "S{$semestre} n'est pas un semestre de {$classe->name} (" . implode(' ou ', array_map(fn ($s) => "S{$s}", $classe->getSemestresLMD())) . ').');
        }
        $maquette = $this->ecuesDeLaMaquette($classe, $semestre);

        $lignes = collect($e['notes'])->map(function (array $l) use ($classe, $semestre, $maquette, $annee, $periode, $e): array {
            $matiere = ESBTPMatiere::findOrFail((int) $l['matiere_id']);
            if (! CoherenceSystemeAcademique::estCoherente($classe->systeme_academique, $matiere->unite_enseignement_id)) {
                $this->refuser('notes', "La matière {$matiere->name} n'est pas une ECUE LMD de cette classe.");
            }
            if (! $maquette->contains('id', $matiere->id)) {
                $this->refuser('notes', "La matière {$matiere->name} ne figure pas dans la maquette S{$semestre} de cette classe.");
            }
            $titre = $this->titre($periode, $matiere);
            $avant = ESBTPNote::query()
                ->where('etudiant_id', (int) $e['etudiant_id'])
                ->whereHas('evaluation', fn ($q) => $q->where('titre', $titre)->where('classe_id', $classe->id)
                    ->where('matiere_id', $matiere->id)->where('annee_universitaire_id', $annee->id)->where('periode', $periode))
                ->value('note');

            return ['matiere' => $matiere, 'titre' => $titre, 'avant' => $avant === null ? null : round((float) $avant, 2), 'apres' => round((float) $l['note'], 2)];
        });

        $rapport = fn (array $plus = []) => $lignes->values()->map(fn ($l, $i) => [
            'matiere_id' => (int) $l['matiere']->id,
            'matiere' => (string) $l['matiere']->name,
            'evaluation' => $l['titre'],
            'avant' => $l['avant'],
            'apres' => $l['apres'],
        ] + ($plus[$i] ?? []))->all();

        if ($simulation) {
            return ['annee' => (string) $annee->name, 'lignes' => $rapport()];
        }

        $ecrites = DB::transaction(fn () => $lignes->values()->map(function (array $l) use ($classe, $annee, $periode, $e, $userId, $canal): array {
            $evaluation = ESBTPEvaluation::firstOrCreate(
                ['titre' => $l['titre'], 'classe_id' => $classe->id, 'matiere_id' => $l['matiere']->id,
                    'annee_universitaire_id' => $annee->id, 'periode' => $periode],
                ['description' => $e['motif'], 'type' => 'controle', 'date_evaluation' => $e['date_regularisation'].' 08:00:00',
                    'duree_minutes' => 60, 'coefficient' => 1, 'bareme' => 20, 'status' => ESBTPEvaluation::STATUS_COMPLETED,
                    'is_published' => false, 'created_by' => $userId],
            );
            // Une épreuve passée : terminée, donc comptée au bulletin LMD (qui ne lit
            // que les évaluations terminées), mais non publiée aux étudiants. Une
            // régularisation créée en brouillon par une version antérieure est remise
            // au même statut, sinon ses notes n'y comptaient qu'au gré d'un passage
            // sur la liste des évaluations.
            if ($evaluation->status === ESBTPEvaluation::STATUS_DRAFT) {
                ChangementDeStatut::poser($evaluation, ESBTPEvaluation::STATUS_COMPLETED, User::find($userId));
            }
            $note = ESBTPNote::updateOrCreate(
                ['evaluation_id' => $evaluation->id, 'etudiant_id' => (int) $e['etudiant_id']],
                ['matiere_id' => $l['matiere']->id, 'classe_id' => $classe->id, 'note' => $l['apres'], 'is_absent' => false,
                    'type_evaluation' => 'controle', 'annee_universitaire' => $annee->name,
                    'commentaire' => "Note régularisée via {$canal} — ".$e['motif'], 'created_by' => $userId, 'updated_by' => $userId],
            );

            return ['evaluation_id' => (int) $evaluation->id, 'note_id' => (int) $note->id];
        })->all());

        return ['annee' => (string) $annee->name, 'lignes' => $rapport($ecrites)];
    }

    /**
     * La maquette telle que la classe la voit : la même lecture que le planning
     * et les bulletins. Le pivot esbtp_ue_matiere seul ne suffit pas : une
     * maquette importée par clé étrangère n'y a aucune ligne, et toutes ses
     * notes étaient refusées (ESBTP Abidjan, octobre 2026).
     */
    public function ecuesDeLaMaquette(ESBTPClasse $classe, int $semestre): Collection
    {
        return ESBTPLMDParcours::find($classe->parcours_id)
            ?->unitesEnseignement()
            ->wherePivot('semestre', $semestre)
            ->where('esbtp_unites_enseignement.is_active', true)
            ->with(['ecues', 'matieres'])
            ->get()
            ->flatMap(fn ($ue) => $ue->getEcuesEffectifs((int) $classe->parcours_id))
            ?? collect();
    }

    private function titre(string $periode, ESBTPMatiere $matiere): string
    {
        return 'Régularisation '.strtoupper($periode).' — '.$matiere->name;
    }

    private function refuser(string $champ, string $message): never
    {
        throw ValidationException::withMessages([$champ => [$message]]);
    }
}
