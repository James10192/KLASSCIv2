<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPBulletin;
use Illuminate\Support\Facades\Route;

class SearchResultsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_results';
    }

    public function description(): string
    {
        return 'Rechercher les résultats académiques (moyenne générale, rang, mention) d\'un étudiant ou d\'une classe entière pour un semestre donné. Différent de search_notes qui montre les notes individuelles par évaluation.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'student_name' => [
                    'type' => 'string',
                    'description' => 'Nom de l\'étudiant',
                ],
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom ou code de la classe (ex: "B3 COM")',
                ],
                'periode' => [
                    'type' => 'string',
                    'description' => 'Période: "S1" (semestre 1), "S2" (semestre 2), ou "annuel"',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Nombre max de résultats (défaut: 10, max: 25)',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $query = ESBTPBulletin::query()
            ->with(['etudiant', 'classe.filiere', 'anneeUniversitaire', 'resultatsMatiere.matiere']);

        if (!empty($args['student_name'])) {
            $query->whereHas('etudiant', function ($q) use ($args) {
                $this->applyFuzzyNameSearch($q, $args['student_name']);
            });
        }

        if (!empty($args['classe'])) {
            $search = $args['classe'];
            $query->whereHas('classe', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($args['periode'])) {
            $periode = mb_strtoupper(trim($args['periode']));
            if (in_array($periode, ['S1', 'S2'])) {
                $query->where('periode', $periode);
            }
        }

        // Filtrer les bulletins publiés en priorité
        $query->orderByDesc('is_published')->orderByDesc('created_at');

        $limit = min(max((int) ($args['limit'] ?? 10), 1), 25);
        $total = (clone $query)->count();
        $bulletins = $query->limit($limit)->get();

        $results = $bulletins->map(function ($b) {
            $etudiant = $b->etudiant;
            $classe = $b->classe;

            return [
                'etudiant' => $etudiant ? trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')) : 'N/A',
                'classe' => $classe?->name ?? 'N/A',
                'filiere' => $classe?->filiere?->name ?? 'N/A',
                'periode' => $b->periode ?? 'N/A',
                'annee' => $b->anneeUniversitaire?->name ?? 'N/A',
                'moyenne' => $b->moyenne_generale !== null ? number_format($b->moyenne_generale, 2, ',', '') . '/20' : 'N/A',
                'rang' => $b->rang ? $b->rang . '/' . ($b->effectif_classe ?? '?') : 'N/A',
                'mention' => $b->mention ?? ($b->moyenne_generale !== null ? $this->getMention($b->moyenne_generale) : 'N/A'),
                'decision' => $b->moyenne_generale !== null ? ($b->moyenne_generale >= 10 ? 'Admis(e)' : 'Ajourné(e)') : 'N/A',
                'absences' => ($b->total_absences ?? 0) . 'h (' . ($b->absences_non_justifiees ?? 0) . 'h non justifiées)',
                'publie' => $b->is_published ? 'Oui' : 'Non',
                'lien' => $etudiant && Route::has('esbtp.resultats.etudiant')
                    ? route('esbtp.resultats.etudiant', $etudiant->id) : null,
                'lien_label' => 'Résultats',
                'lien_icon' => 'fas fa-graduation-cap',
            ];
        })->toArray();

        return [
            'results' => $results,
            'count' => count($results),
            'total' => $total,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.resultats.index') ? route('esbtp.resultats.index') : null,
        ];
    }

    private function getMention(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }
}
