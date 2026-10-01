<?php

namespace App\Http\Controllers;

use App\Domain\AcademicPilotage\Services\BtsBulkBulletinGenerationService;
use App\Domain\Bulletins\Taches\BulletinTache;
use App\Domain\Bulletins\Taches\ExecuteurTachesBulletins;
use App\Domain\Bulletins\Taches\LancementTachesBulletins;
use App\Domain\Bulletins\Taches\SuiviTachesBulletins;
use App\Http\Requests\Bulletin\GenerateClasseBulletinsRequest;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Travaux longs sur les bulletins : lancer, suivre, faire avancer, récupérer.
 *
 * Le lancement de la génération vit ici ; celui du PDF groupé vit avec le reste
 * de l'export ({@see Concerns\ExporteBulletinsParTranches}), qui sait déjà
 * quels bulletins la liste filtrée contient.
 */
class ESBTPBulletinTacheController extends Controller
{
    /**
     * Temps accordé à une requête de l'onglet : une seule tranche, sous la
     * limite d'exécution de l'hébergement.
     */
    private const BUDGET_ONGLET_SECONDES = 20;

    public function __construct(
        private readonly ExecuteurTachesBulletins $executeur,
    ) {}

    /** POST /esbtp-special/bulletins-taches/generation */
    public function lancerGeneration(
        GenerateClasseBulletinsRequest $request,
        BtsBulkBulletinGenerationService $generation,
        LancementTachesBulletins $lancement,
    ): JsonResponse {
        $classe = ESBTPClasse::findOrFail($request->integer('classe_id'));

        abort_if(
            ($classe->systeme_academique ?? '') === 'LMD',
            422,
            'Cette classe est LMD. Utilisez /esbtp/lmd/bulletins pour générer des bulletins LMD en masse.'
        );

        // Le pré-contrôle fait autorité, comme sur la page : c'est lui qui sait
        // qui sera traité et si la génération est permise.
        $preflight = $generation->preflight(
            $classe,
            $request->integer('annee_universitaire_id'),
            (string) $request->input('periode'),
            $request->user(),
            $request->boolean('recalculer')
        );

        $motif = trim((string) $request->input('incomplete_reason', ''));
        $permis = $preflight['status'] === 'ready'
            || ($preflight['status'] === 'needs_reason' && mb_strlen($motif) >= 8);

        if (! $permis) {
            return response()->json([
                'success' => false,
                'message' => $preflight['message'] ?? 'Des prérequis bloquent la génération.',
                'preflight' => $preflight,
            ], 422);
        }

        $ids = $preflight['student_ids'] ?? [];
        if ($ids === []) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun étudiant à générer pour cette période.',
            ], 422);
        }

        $tache = $lancement->generation(
            $request->user(),
            $classe,
            $request->integer('annee_universitaire_id'),
            (string) $preflight['periode'],
            $ids,
            $request->boolean('recalculer'),
            $motif !== '' ? $motif : null
        );

        return response()->json(['success' => true, 'tache' => SuiviTachesBulletins::etat($tache)], 201);
    }

    /** GET /esbtp-special/bulletins-taches : ce que le toast global doit savoir. */
    public function suivi(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'taches' => SuiviTachesBulletins::pourUtilisateur($request->user()),
        ]);
    }

    /** GET /esbtp-special/bulletins-taches/{tache} */
    public function etat(Request $request, BulletinTache $tache): JsonResponse
    {
        $this->assurerProprietaire($request, $tache);

        return response()->json(['success' => true, 'tache' => SuiviTachesBulletins::etat($tache)]);
    }

    /**
     * POST /esbtp-special/bulletins-taches/{tache}/avancer
     *
     * L'onglet ouvert fait avancer la tâche d'une tranche : tant qu'on reste,
     * le travail va aussi vite qu'avant. Si la planification tient déjà la
     * tâche, on rend l'état sans attendre.
     */
    public function avancer(Request $request, BulletinTache $tache): JsonResponse
    {
        $this->assurerProprietaire($request, $tache);

        $faites = $this->executeur->avancer($tache, self::BUDGET_ONGLET_SECONDES, 1);

        return response()->json([
            'success' => true,
            'occupee' => $faites === null,
            'tache' => SuiviTachesBulletins::etat($tache->fresh()),
        ]);
    }

    /** POST /esbtp-special/bulletins-taches/{tache}/vue : le toast ne la rejouera plus. */
    public function marquerVue(Request $request, BulletinTache $tache): JsonResponse
    {
        $this->assurerProprietaire($request, $tache);

        if ($tache->estFinale() && $tache->vue_at === null) {
            $tache->forceFill(['vue_at' => now()])->save();
        }

        return response()->json(['success' => true]);
    }

    /** GET /esbtp-special/bulletins-taches/{tache}/fichier */
    public function fichier(Request $request, BulletinTache $tache)
    {
        $this->assurerProprietaire($request, $tache);

        $chemin = $tache->fichier;
        if (! is_string($chemin) || ! is_file($chemin)) {
            return response(
                "Ce document n'est plus disponible (il est conservé ".BulletinTache::CONSERVATION_HEURES." heures). Relancez l'export depuis la liste des bulletins.",
                410
            )->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $nom = 'bulletins_'.$tache->terminee_at?->format('Ymd_His').'.pdf';

        return $request->input('mode', $tache->parametre('mode')) === 'apercu'
            ? response()->file($chemin, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$nom.'"',
            ])
            : response()->download($chemin, $nom);
    }

    /**
     * Une tâche appartient à celui qui l'a lancée. 404 plutôt que 403 : on ne
     * confirme pas l'existence d'un travail qui n'est pas le sien.
     */
    private function assurerProprietaire(Request $request, BulletinTache $tache): void
    {
        abort_unless((int) $tache->user_id === (int) $request->user()->id, 404);
    }
}
