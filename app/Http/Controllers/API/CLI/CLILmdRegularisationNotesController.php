<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\CLI;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Saisie exceptionnelle, traçable et idempotente de notes LMD provenant d'une
 * fiche officielle quand les évaluations historiques n'avaient pas été créées.
 *
 * Une ligne est toujours créée dans esbtp_evaluations avant la note : écrire une
 * note orpheline ferait disparaître la provenance, les permissions et le lien
 * avec la matière du bulletin. Simulation par défaut ; dry_run=false est requis.
 */
final class CLILmdRegularisationNotesController extends BaseApiController
{
    public function enregistrer(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'etudiant_id' => ['required', 'integer', 'exists:esbtp_etudiants,id'],
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['required', 'in:semestre1,semestre2'],
            'date_regularisation' => ['required', 'date'],
            'motif' => ['required', 'string', 'min:20', 'max:1000'],
            'dry_run' => ['nullable', 'boolean'],
            'notes' => ['required', 'array', 'min:1', 'max:40'],
            'notes.*.matiere_id' => ['required', 'integer', 'distinct', 'exists:esbtp_matieres,id'],
            'notes.*.note' => ['required', 'numeric', 'min:0', 'max:20'],
        ]);

        $classe = ESBTPClasse::findOrFail($v['classe_id']);
        if (! CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            return $this->errorResponse('Cette régularisation est réservée aux classes LMD.', [], 422);
        }

        $anneeId = (int) ($v['annee_universitaire_id'] ?? ESBTPAnneeUniversitaire::where('is_current', true)->value('id'));
        $annee = ESBTPAnneeUniversitaire::find($anneeId);
        if (! $annee) {
            return $this->errorResponse('Aucune année universitaire active trouvée.', [], 422);
        }

        $inscription = ESBTPInscription::query()
            ->where('etudiant_id', $v['etudiant_id'])
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->exists();
        if (! $inscription) {
            return $this->errorResponse("L'étudiant n'est pas inscrit activement dans cette classe pour cette année.", [], 422);
        }

        $semestre = (int) substr((string) $v['periode'], -1);

        // La maquette telle que la classe la voit : la meme lecture que le
        // planning et les bulletins. Le pivot esbtp_ue_matiere seul ne suffit
        // pas : une maquette importee par cle etrangere n'y a aucune ligne, et
        // toutes ses notes etaient refusees (ESBTP Abidjan, octobre 2026).
        $maquette = ESBTPLMDParcours::find($classe->parcours_id)
            ?->unitesEnseignement()
            ->wherePivot('semestre', $semestre)
            ->where('esbtp_unites_enseignement.is_active', true)
            ->with(['ecues', 'matieres'])
            ->get()
            ->flatMap(fn ($ue) => $ue->getEcuesEffectifs((int) $classe->parcours_id))
            ?? collect();

        $lignes = collect($v['notes'])->map(function (array $ligne) use ($classe, $semestre, $maquette): array {
            $matiere = ESBTPMatiere::findOrFail($ligne['matiere_id']);
            if (! CoherenceSystemeAcademique::estCoherente($classe->systeme_academique, $matiere->unite_enseignement_id)) {
                throw ValidationException::withMessages(['notes' => ["La matière {$matiere->name} n'est pas une ECUE LMD de cette classe."]]);
            }

            $estDansMaquette = $maquette->contains('id', $matiere->id);
            if (! $estDansMaquette) {
                throw ValidationException::withMessages(['notes' => ["La matière {$matiere->name} ne figure pas dans la maquette S{$semestre} de cette classe."]]);
            }

            return ['matiere' => $matiere, 'note' => (float) $ligne['note']];
        });

        $dryRun = (bool) ($v['dry_run'] ?? true);
        $apercu = $lignes->map(fn (array $ligne) => [
            'matiere_id' => $ligne['matiere']->id,
            'matiere' => $ligne['matiere']->name,
            'note' => $ligne['note'],
            'evaluation' => 'Régularisation '.strtoupper($v['periode']).' — '.$ligne['matiere']->name,
        ])->values()->all();

        if ($dryRun) {
            return $this->successResponse([
                'dry_run' => true,
                'evaluations_et_notes' => $apercu,
            ], 'Prévisualisation : aucune évaluation ni note n’a été écrite.');
        }

        $resultat = DB::transaction(function () use ($lignes, $v, $classe, $annee, $request): array {
            $ecrites = [];
            foreach ($lignes as $ligne) {
                $matiere = $ligne['matiere'];
                $titre = 'Régularisation '.strtoupper($v['periode']).' — '.$matiere->name;
                $evaluation = ESBTPEvaluation::firstOrCreate(
                    [
                        'titre' => $titre,
                        'classe_id' => $classe->id,
                        'matiere_id' => $matiere->id,
                        'annee_universitaire_id' => $annee->id,
                        'periode' => $v['periode'],
                    ],
                    [
                        'description' => $v['motif'],
                        'type' => 'controle',
                        'date_evaluation' => $v['date_regularisation'].' 08:00:00',
                        'duree_minutes' => 60,
                        'coefficient' => 1,
                        'bareme' => 20,
                        'status' => ESBTPEvaluation::STATUS_DRAFT,
                        'is_published' => false,
                        'created_by' => $request->user()->id,
                    ]
                );

                $note = ESBTPNote::updateOrCreate(
                    ['evaluation_id' => $evaluation->id, 'etudiant_id' => $v['etudiant_id']],
                    [
                        'matiere_id' => $matiere->id,
                        'classe_id' => $classe->id,
                        'note' => $ligne['note'],
                        'is_absent' => false,
                        'type_evaluation' => 'controle',
                        'annee_universitaire' => $annee->nom,
                        'commentaire' => 'Note régularisée via CLI — '.$v['motif'],
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]
                );

                $ecrites[] = ['evaluation_id' => $evaluation->id, 'note_id' => $note->id, 'matiere' => $matiere->name, 'note' => $ligne['note']];
            }
            return $ecrites;
        });

        Log::warning('CLI: regularisation de notes LMD', [
            'etudiant_id' => $v['etudiant_id'],
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => $v['periode'],
            'lignes' => $resultat,
            'motif' => $v['motif'],
            'caller_user_id' => $request->user()->id,
        ]);

        return $this->successResponse(['dry_run' => false, 'evaluations_et_notes' => $resultat], 'Évaluations de régularisation et notes enregistrées.');
    }
}
