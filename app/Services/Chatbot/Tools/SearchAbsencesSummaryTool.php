<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Route;

class SearchAbsencesSummaryTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_absences_summary';
    }

    public function description(): string
    {
        return 'Classement des étudiants par nombre d\'absences dans une classe. Montre le taux de présence, les absences justifiées/non justifiées. Différent de search_attendances qui liste les présences individuelles.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom ou code de la classe (obligatoire)',
                ],
                'month' => [
                    'type' => 'integer',
                    'description' => 'Mois (1-12). Si omis, toute l\'année.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 15, max: 30)',
                ],
            ],
            'required' => ['classe'],
        ];
    }

    public function execute(array $args, $user): array
    {
        $search = $args['classe'];

        // Trouver les étudiants de la classe
        $inscriptions = ESBTPInscription::query()
            ->with(['etudiant', 'classe'])
            ->where('status', 'active');
        $this->applyClasseSearch($inscriptions, $search);
        $inscriptions = $inscriptions->get();

        if ($inscriptions->isEmpty()) {
            return [
                'results' => [],
                'count' => 0,
                'display_type' => 'text',
                'message' => "Aucune inscription active trouvée pour la classe \"{$search}\".",
            ];
        }

        $classe = $inscriptions->first()->classe;
        $etudiantIds = $inscriptions->pluck('etudiant_id')->unique();

        // Agrégation SQL directe (évite de charger tous les enregistrements en mémoire)
        $attendanceStats = ESBTPAttendance::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->when(!empty($args['month']), fn($q) => $q->whereMonth('date', (int) $args['month']))
            ->selectRaw("
                etudiant_id,
                COUNT(*) as total,
                SUM(status = 'present') as presents,
                SUM(status = 'absent') as absents,
                SUM(status = 'late') as retards,
                SUM(status = 'absent' AND is_justified = 1) as justifiees
            ")
            ->groupBy('etudiant_id')
            ->get()
            ->keyBy('etudiant_id');

        // Construire les résultats triés par absences décroissantes
        $stats = [];
        foreach ($etudiantIds as $etudiantId) {
            $row = $attendanceStats[$etudiantId] ?? null;
            $total = (int) ($row?->total ?? 0);
            $presents = (int) ($row?->presents ?? 0);
            $absents = (int) ($row?->absents ?? 0);
            $retards = (int) ($row?->retards ?? 0);
            $justifiees = (int) ($row?->justifiees ?? 0);
            $nonJustifiees = $absents - $justifiees;

            $insc = $inscriptions->firstWhere('etudiant_id', $etudiantId);
            $etudiant = $insc?->etudiant;

            $tauxPresence = $total > 0 ? round(($presents / $total) * 100, 1) : null;

            $stats[] = [
                'nom' => $this->studentFullName($etudiant),
                'initials' => $this->studentInitials($etudiant),
                'taux_presence' => $tauxPresence !== null ? $tauxPresence . '%' : 'N/A',
                'absences' => $absents . ' (' . $nonJustifiees . ' non just.)',
                'retards' => (string) $retards,
                'seances' => $presents . '/' . $total . ' présences',
                'absences_brut' => $absents,
                'lien' => $etudiant && Route::has('esbtp.etudiants.show')
                    ? route('esbtp.etudiants.show', $etudiant->id) : null,
                'lien_label' => 'Fiche',
                'lien_icon' => 'fas fa-user',
            ];
        }

        // Trier par absences décroissantes
        usort($stats, fn ($a, $b) => $b['absences_brut'] <=> $a['absences_brut']);

        $limit = $this->clampLimit($args, 15, 30);
        $stats = array_slice($stats, 0, $limit);

        // Retirer champ de tri
        $stats = array_map(function ($s) {
            unset($s['absences_brut']);
            return $s;
        }, $stats);

        // Statistiques globales de la classe (somme des agrégats SQL)
        $totalSeances = $attendanceStats->sum('total');
        $totalPresents = $attendanceStats->sum('presents');
        $tauxClasse = $totalSeances > 0 ? round(($totalPresents / $totalSeances) * 100, 1) : 0;

        return [
            'results' => $stats,
            'count' => count($stats),
            'classe' => $classe?->name ?? $search,
            'taux_presence_classe' => $tauxClasse . '%',
            'total_seances_classe' => $totalSeances,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.attendances.index') ? route('esbtp.attendances.index') : null,
        ];
    }
}
