<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class SearchAttendancesTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_attendances';
    }

    public function description(): string
    {
        return 'Rechercher les présences/absences des étudiants par étudiant, classe, date ou statut. Retourne le statut (présent, absent, retard), la date, la matière et si c\'est justifié.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'student_name' => [
                    'type' => 'string',
                    'description' => 'Nom ou prénom de l\'étudiant',
                ],
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom de la classe',
                ],
                'statut' => [
                    'type' => 'string',
                    'description' => 'Statut: "present", "absent", "late", "excused"',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Date début (format YYYY-MM-DD)',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'Date fin (format YYYY-MM-DD)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 15, max: 25)',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $query = ESBTPAttendance::query()->with(['etudiant', 'classe', 'matiere']);

        if (!empty($args['student_name'])) {
            $tool = $this;
            $query->whereHas('etudiant', function ($q) use ($args, $tool) {
                $tool->applyFuzzyNameSearch($q, $args['student_name']);
            });
        }

        if (!empty($args['classe'])) {
            $classe = $args['classe'];
            $query->whereHas('classe', fn($q) => $q->where('name', 'like', "%{$classe}%"));
        }

        if (!empty($args['statut'])) {
            $query->where('statut', $args['statut']);
        }

        if (!empty($args['date_from'])) {
            $query->whereDate('date', '>=', $args['date_from']);
        }

        if (!empty($args['date_to'])) {
            $query->whereDate('date', '<=', $args['date_to']);
        }

        $limit = min(max((int) ($args['limit'] ?? 15), 1), 25);
        $total = (clone $query)->count();
        $results = $query->orderByDesc('date')->limit($limit)->get();

        $statusMap = [
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'Retard',
            'excused' => 'Excusé',
        ];

        $attendances = $results->map(function ($att) use ($statusMap) {
            $etudiant = $att->etudiant;
            return [
                'etudiant' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : 'Inconnu',
                'classe' => $att->classe?->name ?? 'N/A',
                'matiere' => $att->matiere?->name ?? 'N/A',
                'date' => $att->date ? date('d/m/Y', strtotime($att->date)) : 'N/A',
                'horaire' => ($att->heure_debut ?? '') . ' - ' . ($att->heure_fin ?? ''),
                'statut' => $statusMap[$att->statut] ?? ucfirst($att->statut ?? 'N/A'),
                'justifie' => $att->is_justified ? 'Oui' : 'Non',
            ];
        })->toArray();

        // Stats résumées
        $stats = null;
        if ($total > 0) {
            $allStatuses = (clone $query)->select('statut', DB::raw('count(*) as count'))
                ->groupBy('statut')->pluck('count', 'statut')->toArray();
            $stats = [
                'total' => $total,
                'presents' => $allStatuses['present'] ?? 0,
                'absents' => $allStatuses['absent'] ?? 0,
                'retards' => $allStatuses['late'] ?? 0,
                'taux_presence' => $total > 0
                    ? round((($allStatuses['present'] ?? 0) / $total) * 100, 1) . '%'
                    : 'N/A',
            ];
        }

        return [
            'results' => $attendances,
            'count' => count($attendances),
            'total' => $total,
            'stats' => $stats,
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.attendances.index') ? route('esbtp.attendances.index') : null,
        ];
    }
}
