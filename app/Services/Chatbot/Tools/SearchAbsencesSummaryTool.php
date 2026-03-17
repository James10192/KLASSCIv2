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
            ->where('status', 'active')
            ->whereHas('classe', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->get();

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

        // Requête d'absences
        $attendanceQuery = ESBTPAttendance::query()
            ->whereIn('etudiant_id', $etudiantIds);

        if (!empty($args['month'])) {
            $attendanceQuery->whereMonth('date', (int) $args['month']);
        }

        $attendances = $attendanceQuery->get();

        // Agrégation par étudiant
        $stats = [];
        foreach ($etudiantIds as $etudiantId) {
            $etudiantAttendances = $attendances->where('etudiant_id', $etudiantId);
            $total = $etudiantAttendances->count();
            $presents = $etudiantAttendances->where('status', 'present')->count();
            $absents = $etudiantAttendances->where('status', 'absent')->count();
            $retards = $etudiantAttendances->where('status', 'late')->count();
            $justifiees = $etudiantAttendances->where('status', 'absent')->where('is_justified', true)->count();
            $nonJustifiees = $absents - $justifiees;

            $insc = $inscriptions->firstWhere('etudiant_id', $etudiantId);
            $etudiant = $insc?->etudiant;

            $tauxPresence = $total > 0 ? round(($presents / $total) * 100, 1) : null;
            $nom = $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : 'N/A';
            $initials = $etudiant ? mb_strtoupper(mb_substr($etudiant->nom ?? '', 0, 1) . mb_substr($etudiant->prenoms ?? '', 0, 1)) : '?';

            $stats[] = [
                'nom' => $nom,
                'initials' => $initials,
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

        $limit = min(max((int) ($args['limit'] ?? 15), 1), 30);
        $stats = array_slice($stats, 0, $limit);

        // Retirer champ de tri
        $stats = array_map(function ($s) {
            unset($s['absences_brut']);
            return $s;
        }, $stats);

        // Statistiques globales de la classe
        $totalSeances = $attendances->count();
        $totalPresents = $attendances->where('status', 'present')->count();
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
