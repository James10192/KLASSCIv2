<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Domain\Notes\CorrectionDeNotes;
use App\Domain\Notes\SaisieDeMoyennes;
use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * POST /api/cli/resultats/moyennes — enregistrer ou retirer les moyennes de
 * matiere d'un eleve, comme l'ecran « Modifier les moyennes ».
 *
 * Nee d une reclamation d'eleve (ESBTP Abidjan, septembre 2026) traitee a
 * distance : le CLI lisait les moyennes mais ne savait pas en ecrire.
 *
 * SIMULATION PAR DEFAUT. Il faut `dry_run: false` pour ecrire : une moyenne
 * enregistree l'emporte sur les notes au bulletin, une erreur de saisie ici
 * change une decision de passage. Le motif est obligatoire et journalise.
 *
 * Body:
 *   etudiant_id, classe_id, periode (semestre1|semestre2),
 *   annee_universitaire_id? (annee courante par defaut),
 *   motif (>= 10 caracteres), dry_run? (true par defaut),
 *   moyennes: [{matiere_id, moyenne: 0..20 | null}]   null = retirer
 *
 * Voir docs/api/CLI_MOYENNES.md.
 */
class CLIMoyennesController extends BaseApiController
{
    public function enregistrer(Request $request, SaisieDeMoyennes $saisie): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
            'classe_id' => 'required|integer|exists:esbtp_classes,id',
            'periode' => 'required|in:semestre1,semestre2',
            'annee_universitaire_id' => 'nullable|integer|exists:esbtp_annee_universitaires,id',
            'motif' => 'required|string|min:10|max:500',
            'dry_run' => 'nullable|boolean',
            'moyennes' => 'required|array|min:1|max:40',
            'moyennes.*.matiere_id' => 'required|integer|distinct|exists:esbtp_matieres,id',
            'moyennes.*.moyenne' => 'present|nullable|numeric|min:0|max:20',
        ]);

        $classe = ESBTPClasse::findOrFail($v['classe_id']);
        if (CoherenceSystemeAcademique::classeEstLmd($classe->systeme_academique)) {
            return $this->errorResponse('Classe LMD : ses relevés passent par /esbtp/lmd/bulletins.', [], 422);
        }

        $anneeId = $v['annee_universitaire_id'] ?? ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
        $inscrit = ESBTPInscription::where('etudiant_id', $v['etudiant_id'])
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $anneeId)
            ->exists();
        if (! $inscrit) {
            return $this->errorResponse("L'élève n'est pas inscrit dans « {$classe->name} » pour cette année.", [], 422);
        }

        $simuler = (bool) ($v['dry_run'] ?? true);

        try {
            $resultat = $saisie->appliquer(
                (int) $v['etudiant_id'], $classe, (int) $anneeId, $v['periode'],
                $v['moyennes'], $simuler, $request->user()->id
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), $e->errors(), 422);
        }

        if (! $simuler) {
            Log::warning('CLI: moyennes enregistrees', [
                'etudiant_id' => $v['etudiant_id'],
                'classe' => $classe->name,
                'periode' => $v['periode'],
                'annee_universitaire_id' => $anneeId,
                'motif' => $v['motif'],
                'lignes' => $resultat['lignes'],
                'caller_user_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);
        }

        return $this->successResponse(['dry_run' => $simuler, 'classe' => $classe->name] + $resultat, $simuler
            ? 'Aucune écriture : prévisualisation. Relancer avec dry_run=false pour enregistrer.'
            : 'Moyennes enregistrées. Régénérer les bulletins listés pour qu’ils en tiennent compte.');
    }

    /**
     * POST /api/cli/notes/corriger — corriger des notes EXISTANTES d'un eleve,
     * puis recalculer ses moyennes de matiere (synchrone).
     *
     * Body: etudiant_id, motif (>= 10), dry_run? (true par defaut),
     *       notes: [{note_id, note}]
     */
    public function corrigerNotes(Request $request, CorrectionDeNotes $correction): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
            'motif' => 'required|string|min:10|max:500',
            'dry_run' => 'nullable|boolean',
            'notes' => 'required|array|min:1|max:60',
            'notes.*.note_id' => 'required|integer|distinct',
            'notes.*.note' => 'required|numeric|min:0',
        ]);
        $simuler = (bool) ($v['dry_run'] ?? true);

        try {
            $resultat = $correction->appliquer((int) $v['etudiant_id'], $v['notes'], $simuler, $request->user()->id);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), $e->errors(), 422);
        }

        if (! $simuler) {
            Log::warning('CLI: notes corrigees', [
                'etudiant_id' => $v['etudiant_id'],
                'motif' => $v['motif'],
                'lignes' => $resultat['lignes'],
                'moyennes' => $resultat['moyennes'],
                'caller_user_id' => $request->user()->id,
                'ip' => $request->ip(),
            ]);
        }

        return $this->successResponse(['dry_run' => $simuler] + $resultat, $simuler
            ? 'Aucune écriture : prévisualisation. Relancer avec dry_run=false pour enregistrer.'
            : 'Notes corrigées et moyennes recalculées. Régénérer le bulletin pour qu’il en tienne compte.');
    }
}
