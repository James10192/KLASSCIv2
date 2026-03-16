<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPEtudiant;
use Illuminate\Support\Facades\Route;

class SearchStudentsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_students';
    }

    public function description(): string
    {
        return 'Rechercher des étudiants dans le système KLASSCI. Retourne nom, matricule, classe, statut.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Nom ou prénom de l\'étudiant à rechercher',
                ],
                'matricule' => [
                    'type' => 'string',
                    'description' => 'Matricule de l\'étudiant',
                ],
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom de la classe (ex: "BTS Bâtiment 1ère année")',
                ],
                'filiere' => [
                    'type' => 'string',
                    'description' => 'Nom de la filière (ex: "BTS Bâtiment", "Génie Civil")',
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
        $query = ESBTPEtudiant::query()->with(['inscriptions.classe.filiere']);

        if (!empty($args['name'])) {
            $search = $args['name'];
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        if (!empty($args['matricule'])) {
            $query->where('matricule', 'like', "%{$args['matricule']}%");
        }

        if (!empty($args['classe'])) {
            $classeName = $args['classe'];
            $query->whereHas('inscriptions.classe', function ($q) use ($classeName) {
                $q->where('name', 'like', "%{$classeName}%");
            });
        }

        if (!empty($args['filiere'])) {
            $filiereName = $args['filiere'];
            $query->whereHas('inscriptions.classe.filiere', function ($q) use ($filiereName) {
                $q->where('name', 'like', "%{$filiereName}%");
            });
        }

        $limit = min(max((int) ($args['limit'] ?? 10), 1), 25);
        $total = (clone $query)->count();
        $results = $query->latest()->limit($limit)->get();

        $students = $results->map(function ($etudiant) {
            $inscription = $etudiant->inscriptions->first();
            return [
                'id' => $etudiant->id,
                'nom' => trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenom ?? '')),
                'matricule' => $etudiant->matricule ?? 'N/A',
                'classe' => $inscription?->classe?->name ?? 'Non inscrit',
                'filiere' => $inscription?->classe?->filiere?->name ?? 'N/A',
                'statut' => $inscription?->status ?? 'N/A',
                'lien' => Route::has('esbtp.etudiants.show') ? route('esbtp.etudiants.show', $etudiant->id) : null,
            ];
        })->toArray();

        return [
            'results' => $students,
            'count' => count($students),
            'total' => $total,
            'display_type' => 'table',
            'deep_link' => Route::has('esbtp.etudiants.index') ? route('esbtp.etudiants.index') : null,
        ];
    }
}
