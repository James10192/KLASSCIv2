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
            'type' => 'object',
            'properties' => [
                'page' => [
                    'type' => 'string',
                    'description' => 'Nom de la page cible. Valeurs possibles: "dashboard", "etudiants", "etudiants.show", "inscriptions", "inscriptions.create", "inscriptions.show", "paiements", "classes", "frais", "evaluations", "notes", "attendances", "emploi_temps", "emploi_temps.create", "emploi_temps.show", "planning", "bulletins", "resultats", "comptabilite"',
                ],
                'id' => [
                    'type' => 'integer',
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
            'emploi_temps.create' => 'esbtp.emploi-temps.create',
            'emploi_temps.show' => 'esbtp.emploi-temps.show',
            'planning' => 'esbtp.planning.index',
            'bulletins' => 'esbtp.bulletins.index',
            'resultats' => 'esbtp.resultats.index',
            'comptabilite' => 'esbtp.comptabilite.dashboard',
        ];

        // Guides détaillés par page pour enrichir la réponse de Claude
        $pageGuides = [
            'emploi_temps' => "Sur la page Emplois du temps :\n"
                . "- Tu verras la liste de tous les emplois du temps existants (cards ou tableau)\n"
                . "- Le **Raccourci** en haut détecte les classes sans emploi du temps ou avec un emploi expiré, et propose \"Créer maintenant\"\n"
                . "- Le bouton **Nouveau** crée un emploi du temps vide (socle : classe, dates, semestre)\n"
                . "- Le bouton **Modifier rapidement** permet de sélectionner plusieurs emplois du temps et les éditer en même temps (vue accordéon)\n"
                . "- Pour voir/modifier un emploi du temps spécifique, clique dessus pour accéder à la vue détaillée",
            'emploi_temps.create' => "Pour créer un emploi du temps :\n"
                . "1. Sélectionner la **classe** concernée\n"
                . "2. Définir la **période** (date début → date fin, max 1 semaine)\n"
                . "3. Choisir le **semestre** (S1 ou S2)\n"
                . "4. Une fois créé, c'est le socle vide — il faut ensuite y ajouter les **séances de cours** depuis la vue détaillée",
            'emploi_temps.show' => "Sur la vue détaillée d'un emploi du temps :\n"
                . "- La **grille horaire** affiche les séances par jour (Lundi → Samedi) et créneau\n"
                . "- Le bouton **Ajouter une séance** permet de créer un cours : matière, enseignant, jour, horaire, salle\n"
                . "- Tu peux aussi **exporter en PDF** l'emploi du temps",
            'inscriptions.create' => "Pour créer une nouvelle inscription :\n"
                . "1. **Remplir les infos personnelles** : nom, prénoms, genre, date/lieu de naissance, nationalité, téléphone, email, photo, matricule\n"
                . "2. **Sélectionner la classe** (la filière, niveau et année s'associent automatiquement)\n"
                . "3. **Définir le statut d'affectation** : affecté, réaffecté ou non affecté\n"
                . "4. **Ajouter les parents/tuteurs** (optionnel)\n"
                . "5. Les **frais obligatoires** se chargent automatiquement avec les pré-sélectionnés",
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
            'deep_link' => $url,
            'page_guide' => $pageGuides[$page] ?? null,
            'display_type' => 'text',
        ];
    }
}
