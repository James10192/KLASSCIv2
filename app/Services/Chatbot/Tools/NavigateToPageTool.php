<?php

namespace App\Services\Chatbot\Tools;

use Illuminate\Support\Facades\Route;

class NavigateToPageTool extends ChatbotTool
{
    public function name(): string
    {
        return 'navigate_to_page';
    }

    public function description(): string
    {
        return 'Générer un lien direct vers une page du système KLASSCI. L\'utilisateur peut cliquer pour naviguer directement. Utiliser quand l\'utilisateur demande "ouvre la page", "emmène-moi à", "lien vers", ou quand tu veux suggérer une page à visiter.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'page' => [
                    'type' => 'STRING',
                    'description' => 'Nom de la page cible. Valeurs possibles: "dashboard", "etudiants", "inscriptions", "inscriptions.create", "paiements", "classes", "frais", "evaluations", "notes", "attendances", "emploi_temps", "planning", "bulletins", "resultats", "comptabilite"',
                ],
                'id' => [
                    'type' => 'INTEGER',
                    'description' => 'ID de la ressource spécifique (pour les pages show/edit)',
                ],
            ],
            'required' => ['page'],
        ];
    }

    public function execute(array $args, $user): array
    {
        $page = $args['page'] ?? '';
        $id = $args['id'] ?? null;

        $routeMap = [
            'dashboard' => 'dashboard',
            'etudiants' => 'esbtp.etudiants.index',
            'etudiants.show' => 'esbtp.etudiants.show',
            'inscriptions' => 'esbtp.inscriptions.index',
            'inscriptions.create' => 'esbtp.inscriptions.create',
            'inscriptions.show' => 'esbtp.inscriptions.show',
            'paiements' => 'esbtp.paiements.index',
            'classes' => 'esbtp.classes.index',
            'frais' => 'esbtp.frais.index',
            'evaluations' => 'esbtp.evaluations.index',
            'notes' => 'esbtp.notes.index',
            'attendances' => 'esbtp.attendances.index',
            'emploi_temps' => 'esbtp.emploi-temps.index',
            'planning' => 'esbtp.planning.index',
            'bulletins' => 'esbtp.bulletins.index',
            'resultats' => 'esbtp.resultats.index',
            'comptabilite' => 'esbtp.comptabilite.dashboard',
        ];

        $routeName = $routeMap[$page] ?? null;

        if (!$routeName || !Route::has($routeName)) {
            return [
                'url' => null,
                'error' => "Page \"{$page}\" non trouvée.",
                'pages_disponibles' => array_keys($routeMap),
            ];
        }

        $url = $id ? route($routeName, $id) : route($routeName);

        return [
            'url' => $url,
            'page' => $page,
            'display_type' => 'text',
        ];
    }
}
