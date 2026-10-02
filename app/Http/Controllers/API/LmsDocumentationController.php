<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/lms/documentation — description publique de l'API LMS.
 *
 * Était une closure de routes/api.php : voir RoutesSimplesController pour
 * la raison de la sortie.
 */
class LmsDocumentationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'title' => 'API LMS-KLASSCI Integration',
            'version' => '1.0.0',
            'description' => 'API pour l\'intégration entre le LMS et KLASSCI',
            'base_url' => url('/api/lms'),
            'authentication' => [
                'type' => 'Bearer Token (Laravel Sanctum)',
                'login_endpoint' => '/api/lms/auth/login',
                'header_format' => 'Authorization: Bearer {token}'
            ],
            'endpoints' => [
                'read_only' => [
                    'GET /api/lms/structure' => 'Structure organisationnelle (filières, niveaux)',
                    'GET /api/lms/matieres' => 'Liste des matières accessibles',
                    'GET /api/lms/classes' => 'Classes de l\'année courante',
                    'GET /api/lms/classes/{id}/etudiants' => 'Étudiants d\'une classe',
                    'GET /api/lms/emploi-temps' => 'Emploi du temps filtré par rôle',
                    'GET /api/lms/evaluations' => 'Évaluations programmées'
                ],
                'write_only' => [
                    'POST /api/lms/evaluations/{id}/notes' => 'Sauvegarder notes d\'évaluation',
                    'POST /api/lms/cours/{id}/presences' => 'Enregistrer présences cours',
                    'PUT /api/lms/cours/{id}/statut' => 'Mettre à jour statut cours'
                ]
            ],
            'roles_supported' => ['enseignant', 'coordinateur', 'etudiant'],
            'data_scope' => 'Année universitaire courante uniquement',
            'contact' => [
                'team' => 'KLASSCI Development Team',
                'documentation' => url('/api/lms/auth/documentation')
            ]
        ]);
    }
}
