<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPBulletin;
use Illuminate\Support\Facades\Route;

class SearchBulletinsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_bulletins';
    }

    public function description(): string
    {
        return 'Vérifier si les bulletins sont générés/publiés pour un étudiant ou une classe. Montre le statut (généré, publié, signé), la moyenne, le rang et le lien pour télécharger le PDF.';
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
                    'description' => 'Nom ou code de la classe',
                ],
                'periode' => [
                    'type' => 'string',
                    'description' => 'Période: "S1", "S2"',
                ],
                'published_only' => [
                    'type' => 'boolean',
                    'description' => 'Si true, ne montre que les bulletins publiés',
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
            ->with(['etudiant', 'classe.filiere', 'anneeUniversitaire']);

        if (!empty($args['student_name'])) {
            $query->whereHas('etudiant', function ($q) use ($args) {
                $this->applyFuzzyNameSearch($q, $args['student_name']);
            });
        }

        if (!empty($args['classe'])) {
            $this->applyClasseSearch($query, $args['classe']);
        }

        if (!empty($args['periode'])) {
            $mapped = $this->mapPeriode($args['periode']);
            if ($mapped) {
                $query->where('periode', $mapped);
            }
        }

        if (!empty($args['published_only'])) {
            $query->where('is_published', true);
        }

        $query->orderByDesc('created_at');

        $limit = $this->clampLimit($args);
        $total = (clone $query)->count();
        $bulletins = $query->limit($limit)->get();

        $results = $bulletins->map(function ($b) {
            $etudiant = $b->etudiant;
            $nom = $this->studentFullName($etudiant);
            $initials = $this->studentInitials($etudiant);

            $signatures = [];
            if ($b->signature_responsable) $signatures[] = 'Resp.';
            if ($b->signature_directeur) $signatures[] = 'Dir.';
            if ($b->signature_parent) $signatures[] = 'Parent';

            $statut = 'Généré';
            if ($b->is_published) $statut = 'Publié';
            if (count($signatures) > 0) $statut .= ' + Signé';

            return [
                'nom' => $nom,
                'initials' => $initials,
                'classe' => $b->classe?->name ?? 'N/A',
                'periode' => mb_strtoupper($b->periode ?? 'N/A') . ' — ' . ($b->anneeUniversitaire?->name ?? ''),
                'moyenne' => $b->moyenne_generale !== null ? number_format($b->moyenne_generale, 2, ',', '') . '/20' : 'En attente',
                'rang' => $b->rang ? 'Rang ' . $b->rang . '/' . ($b->effectif_classe ?? '?') : null,
                'statut' => $statut,
                'lien' => $etudiant && Route::has('esbtp.resultats.etudiant')
                    ? route('esbtp.resultats.etudiant', $etudiant->id) . '?' . http_build_query(array_filter([
                        'classe_id' => $b->classe_id,
                        'annee_universitaire_id' => $b->annee_universitaire_id,
                        'periode' => $b->periode === 'semestre1' ? 1 : ($b->periode === 'semestre2' ? 2 : null),
                    ])) : null,
                'lien_label' => 'Résultats',
                'lien_icon' => 'fas fa-graduation-cap',
            ];
        })->toArray();

        return [
            'results' => $results,
            'count' => count($results),
            'total' => $total,
            'display_type' => 'cards',
            'deep_link' => Route::has('esbtp.bulletins.index') ? route('esbtp.bulletins.index') : null,
        ];
    }
}
