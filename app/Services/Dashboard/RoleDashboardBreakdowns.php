<?php

namespace App\Services\Dashboard;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPClasse;
use App\Models\ESBTPDocumentApproval;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNotesWindow;
use Illuminate\Support\Facades\Cache;

/**
 * Ventilations de l'état courant pour les tableaux de bord de rôle.
 *
 * Volontairement sans série temporelle : les courbes d'évolution vivent dans le
 * pilotage académique. Ici on répond à « où ça coince maintenant », ce qui reste
 * lisible dès le premier jour d'un établissement.
 *
 * Chaque méthode est mise en cache 5 minutes : sur Yakro et Abidjan la
 * ventilation par classe balaie plus de 2000 inscriptions.
 */
class RoleDashboardBreakdowns
{
    private const TTL = 300;

    /** Nombre de classes affichées : au-delà le graphique devient illisible. */
    private const MAX_CLASSES = 12;

    /**
     * Directeur des études — « quelles classes ne sont pas prêtes ».
     * Pour chaque classe : emploi du temps posé, notes saisies, assiduité relevée.
     * Les classes les moins couvertes remontent en tête.
     */
    public function pedagogicalCoverage(): array
    {
        return Cache::remember('dash.coverage', self::TTL, function () {
            $annee = $this->currentYear();

            $classes = ESBTPClasse::query()
                ->where('is_active', true)
                ->get(['id', 'name']);

            if ($classes->isEmpty()) {
                return ['rows' => [], 'total' => 0];
            }

            $withEdt = ESBTPEmploiTemps::query()
                ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee->id))
                ->distinct()
                ->pluck('classe_id')
                ->flip();

            // Une classe « couverte en notes » a au moins une évaluation passée
            // qui porte des notes. On compare aux évaluations passées, pas au
            // total : une évaluation à venir sans note est normale.
            $evaluations = ESBTPEvaluation::query()
                ->whereDate('date_evaluation', '<', today())
                ->when($annee, fn ($q) => $q->whereHas('classe', fn ($c) => $c->where('annee_universitaire_id', $annee->id)))
                ->selectRaw('classe_id, COUNT(*) as total')
                ->groupBy('classe_id')
                ->pluck('total', 'classe_id');

            $evaluationsNotees = ESBTPEvaluation::query()
                ->whereDate('date_evaluation', '<', today())
                ->whereHas('notes')
                ->selectRaw('classe_id, COUNT(*) as total')
                ->groupBy('classe_id')
                ->pluck('total', 'classe_id');

            $assiduite = ESBTPAttendance::query()
                ->whereDate('date', '>=', now()->subDays(30)->toDateString())
                ->whereNotNull('classe_id')
                ->distinct()
                ->pluck('classe_id')
                ->flip();

            $rows = $classes->map(function ($classe) use ($withEdt, $evaluations, $evaluationsNotees, $assiduite) {
                $attendues = (int) ($evaluations[$classe->id] ?? 0);
                $notees = (int) ($evaluationsNotees[$classe->id] ?? 0);
                $notesOk = $attendues === 0 ? true : $notees >= $attendues;

                $acquis = 0;
                $acquis += $withEdt->has($classe->id) ? 1 : 0;
                $acquis += $notesOk ? 1 : 0;
                $acquis += $assiduite->has($classe->id) ? 1 : 0;

                return [
                    'id' => $classe->id,
                    'name' => $classe->name,
                    'edt' => $withEdt->has($classe->id),
                    'notes' => $notesOk,
                    'assiduite' => $assiduite->has($classe->id),
                    'evaluations_attendues' => $attendues,
                    'evaluations_notees' => $notees,
                    'score' => $acquis,
                ];
            })
                ->sortBy('score')          // les moins prêtes d'abord
                ->values();

            return [
                'rows' => $rows->take(self::MAX_CLASSES)->all(),
                'total' => $rows->count(),
                'incompletes' => $rows->where('score', '<', 3)->count(),
            ];
        });
    }

    /**
     * Responsable scolarité — « puis-je fermer cette fenêtre ».
     * Progression de la saisie pour chaque classe dont la fenêtre est ouverte.
     */
    public function noteEntryProgress(): array
    {
        return Cache::remember('dash.note-progress', self::TTL, function () {
            $windows = ESBTPNotesWindow::query()
                ->whereNull('closed_at')
                ->whereDate('starts_at', '<=', today())
                ->whereDate('ends_at', '>=', today())
                ->with('classe:id,name')
                ->orderBy('ends_at')
                ->get();

            if ($windows->isEmpty()) {
                return ['rows' => [], 'total' => 0];
            }

            $classeIds = $windows->pluck('classe_id')->filter()->all();

            $attendues = ESBTPEvaluation::query()
                ->whereIn('classe_id', $classeIds)
                ->whereDate('date_evaluation', '<=', today())
                ->selectRaw('classe_id, COUNT(*) as total')
                ->groupBy('classe_id')
                ->pluck('total', 'classe_id');

            $notees = ESBTPEvaluation::query()
                ->whereIn('classe_id', $classeIds)
                ->whereDate('date_evaluation', '<=', today())
                ->whereHas('notes')
                ->selectRaw('classe_id, COUNT(*) as total')
                ->groupBy('classe_id')
                ->pluck('total', 'classe_id');

            $rows = $windows->map(function ($window) use ($attendues, $notees) {
                $total = (int) ($attendues[$window->classe_id] ?? 0);
                $faites = (int) ($notees[$window->classe_id] ?? 0);

                return [
                    'classe_id' => $window->classe_id,
                    'name' => $window->classe->name ?? 'Classe #'.$window->classe_id,
                    'ends_at' => $window->ends_at,
                    'jours_restants' => (int) now()->startOfDay()->diffInDays($window->ends_at, false),
                    'attendues' => $total,
                    'faites' => $faites,
                    'pourcentage' => $total > 0 ? (int) round($faites / $total * 100) : 0,
                    'complete' => $total > 0 && $faites >= $total,
                ];
            })->values();

            return [
                'rows' => $rows->all(),
                'total' => $rows->count(),
                'completes' => $rows->where('complete', true)->count(),
            ];
        });
    }

    /**
     * Service scolarité — ce qui reste à traiter, par nature de tâche.
     */
    public function clerkWorkload(): array
    {
        return Cache::remember('dash.clerk-workload', self::TTL, function () {
            $aImprimer = ESBTPDocumentApproval::query()
                ->where('status', ESBTPDocumentApproval::STATUS_APPROVED)
                ->selectRaw('document_type, COUNT(*) as total')
                ->groupBy('document_type')
                ->pluck('total', 'document_type');

            $classesOuvertes = ESBTPNotesWindow::query()
                ->whereNull('closed_at')
                ->whereDate('starts_at', '<=', today())
                ->whereDate('ends_at', '>=', today())
                ->count();

            $segments = [];
            foreach ($aImprimer as $type => $total) {
                $segments[] = ['label' => ucfirst((string) $type), 'value' => (int) $total, 'kind' => 'impression'];
            }
            if ($classesOuvertes > 0) {
                $segments[] = ['label' => 'Classes à saisir', 'value' => $classesOuvertes, 'kind' => 'saisie'];
            }

            return [
                'segments' => $segments,
                'total' => array_sum(array_column($segments, 'value')),
            ];
        });
    }

    /**
     * Agent d'inscription — entonnoir des dossiers par étape.
     * Aucun montant : ce rôle ne voit jamais les sommes.
     */
    public function enrollmentFunnel(): array
    {
        return Cache::remember('dash.enrollment-funnel', self::TTL, function () {
            $valide = 'valid'.chr(233);

            $enAttente = ESBTPInscription::query()->whereIn('status', ['en_attente', 'pending']);

            $attentePaiement = (clone $enAttente)
                ->whereDoesntHave('paiements', fn ($q) => $q->where('status', $valide))
                ->count();

            $aValider = (clone $enAttente)
                ->whereHas('paiements', fn ($q) => $q->where('status', $valide))
                ->count();

            $sousReserve = ESBTPInscription::query()->where('is_sous_reserve', true)->count();

            $finalisees = ESBTPInscription::query()
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->count();

            $etapes = [
                ['label' => 'En attente de paiement', 'value' => $attentePaiement, 'statut' => 'en_attente'],
                ['label' => 'À valider', 'value' => $aValider, 'statut' => 'en_attente'],
                ['label' => 'Sous réserve', 'value' => $sousReserve, 'statut' => 'sous_reserve'],
                ['label' => 'Finalisées', 'value' => $finalisees, 'statut' => 'active'],
            ];

            return [
                'etapes' => $etapes,
                'total' => array_sum(array_column($etapes, 'value')),
                'bloquees' => $attentePaiement + $sousReserve,
            ];
        });
    }

    private function currentYear(): ?ESBTPAnneeUniversitaire
    {
        try {
            return ESBTPAnneeUniversitaire::where('is_current', true)->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
