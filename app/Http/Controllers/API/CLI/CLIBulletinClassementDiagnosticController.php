<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Audit en lecture seule : meme cohorte et meme moyenne que RankingService.
 * Aucun nom, matricule ou resultat individuel de tiers n'est expose.
 */
final class CLIBulletinClassementDiagnosticController extends BaseApiController
{
    public function __invoke(Request $request, RankingService $ranking): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $valides = $request->validate([
            'classe_id' => ['required', 'integer', 'exists:esbtp_classes,id'],
            'annee_universitaire_id' => ['required', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['required', 'in:semestre1,semestre2'],
            'etudiant_id' => ['sometimes', 'integer', 'exists:esbtp_etudiants,id'],
        ]);

        $classeId = (int) $valides['classe_id'];
        $anneeId = (int) $valides['annee_universitaire_id'];
        $periode = (string) $valides['periode'];
        $annee = ESBTPAnneeUniversitaire::findOrFail($anneeId);

        $classement = $ranking->calculerRangsClasse($classeId, $anneeId, $periode);
        $lignes = $classement['rows'];
        $cle = $classement['sort_key'];
        $notees = $lignes->filter(static fn (array $ligne): bool => $ligne[$cle] !== null)->values();
        $moyennes = $notees->pluck($cle)->map(static fn ($moyenne): float => (float) $moyenne)->all();

        // Reconstituer exactement le filtre de GET /api/cli/students?annee_id=&class_id= :
        // sur une annee ecoulee, les inscriptions terminees apparaissent aussi.
        $statutsListe = $annee->is_current ? ['active'] : ['active', 'terminée'];
        $idsListe = ESBTPInscription::query()
            ->where('annee_universitaire_id', $anneeId)
            ->where('classe_id', $classeId)
            ->where('workflow_step', 'etudiant_cree')
            ->whereIn('status', $statutsListe)
            ->distinct()
            ->pluck('etudiant_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $idsCanoniques = $lignes->pluck('etudiant_id')->map(static fn ($id): int => (int) $id)->all();
        $horsListe = $lignes->filter(static fn (array $ligne): bool => ! in_array((int) $ligne['etudiant_id'], $idsListe, true))->values();
        $horsListeNotees = $horsListe->filter(static fn (array $ligne): bool => $ligne[$cle] !== null)->values();
        $moyennesHorsListe = $horsListeNotees->pluck($cle)->map(static fn ($moyenne): float => (float) $moyenne)->all();
        $premier = $lignes->firstWhere('rang', 1);

        $cible = null;
        if (isset($valides['etudiant_id'])) {
            $ligneCible = $lignes->firstWhere('etudiant_id', (int) $valides['etudiant_id']);
            $cible = [
                'dans_cohorte' => $ligneCible !== null,
                'rang' => $ligneCible['rang'] ?? null,
                'moyenne_canonique' => $ligneCible[$cle] ?? null,
                'moyenne_brute' => $ligneCible['moyenne_brute'] ?? null,
                'note_assiduite' => $ligneCible['note_assiduite'] ?? null,
                'state' => $ligneCible['snapshot_state'] ?? null,
            ];
        }

        return $this->successResponse([
            'source' => 'RankingService::calculerRangsClasse',
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeId,
            'periode' => $periode,
            'assiduite_active' => $classement['attendance_enabled'],
            'cohorte' => [
                'membres_canoniques' => $lignes->count(),
                'avec_moyenne' => $notees->count(),
                'dans_liste_students' => count(array_intersect($idsCanoniques, $idsListe)),
                'hors_liste_students' => $horsListe->count(),
                'liste_students_total' => count($idsListe),
                'liste_students_hors_cohorte' => count(array_diff($idsListe, $idsCanoniques)),
            ],
            'statistiques' => [
                'plus_forte_moyenne' => $moyennes === [] ? null : max($moyennes),
                'plus_faible_moyenne' => $moyennes === [] ? null : min($moyennes),
                'moyenne_classe' => $moyennes === [] ? null : round(array_sum($moyennes) / count($moyennes), 2),
                'premier_moyenne' => $premier[$cle] ?? null,
                'premiers_ex_aequo' => $lignes->where('rang', 1)->count(),
            ],
            'ecart_liste_students' => [
                'hors_liste_avec_moyenne' => $horsListeNotees->count(),
                'max_moyenne_hors_liste' => $moyennesHorsListe === [] ? null : max($moyennesHorsListe),
                'min_moyenne_hors_liste' => $moyennesHorsListe === [] ? null : min($moyennesHorsListe),
                // Le nom et le matricule de l'eleve manquant ne quittent pas le serveur.
                'rangs_hors_liste' => $horsListeNotees->pluck('rang')->all(),
            ],
            'etudiant_cible' => $cible,
        ], 'Diagnostic canonique en lecture seule');
    }
}
