<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPTeacher;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Recherche globale AJAX
     */
    public function globalSearch(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 5);

        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'La recherche doit contenir au moins 2 caractères',
                'results' => []
            ]);
        }

        $user = Auth::user();
        $results = [];

        try {
            // Recherche de pages/navigation
            $navigationResults = $this->searchNavigation($query, $user, $limit);
            $results = array_merge($results, $navigationResults);

            // Recherche d'actions rapides
            $actionResults = $this->searchQuickActions($query, $user, $limit);
            $results = array_merge($results, $actionResults);

            // Recherche de personnel
            $personnelResults = $this->searchPersonnel($query, $user, $limit);
            $results = array_merge($results, $personnelResults);

            // Recherche d'étudiants (pour tous les utilisateurs authentifiés)
            $etudiants = ESBTPEtudiant::where('nom', 'LIKE', "%{$query}%")
                ->orWhere('prenoms', 'LIKE', "%{$query}%")
                ->orWhere('matricule', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->with(['classe.filiere', 'classe.niveauEtude'])
                ->limit($limit)
                ->get();

            foreach ($etudiants as $etudiant) {
                // Si c'est un étudiant, ne montrer que son propre profil
                if ($user->can('identity.student') && $user->etudiant && $user->etudiant->id !== $etudiant->id) {
                    continue;
                }

                $results[] = [
                    'category' => 'Étudiants',
                    'type' => 'etudiant',
                    'id' => $etudiant->id,
                    'title' => $etudiant->nom . ' ' . ($etudiant->prenoms ?? ''),
                    'description' => $etudiant->matricule . ' - ' . ($etudiant->classe ? $etudiant->classe->nom : 'Aucune classe'),
                    'url' => route('esbtp.etudiants.show', $etudiant->id),
                    'icon' => 'fas fa-user-graduate',
                    'color' => 'primary'
                ];
            }

            // Recherche de classes (pour admin et secrétaire)
            if ($user->hasAnyPermission(['admin.access', 'identity.school_manager'])) {
                $classes = ESBTPClasse::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('libelle', 'LIKE', "%{$query}%")
                    ->orWhere('code', 'LIKE', "%{$query}%")
                    ->with(['filiere', 'niveauEtude'])
                    ->limit($limit)
                    ->get();

                foreach ($classes as $classe) {
                    $results[] = [
                        'category' => 'Classes',
                        'type' => 'classe',
                        'id' => $classe->id,
                        'title' => $classe->name ?? $classe->libelle,
                        'description' => ($classe->filiere ? $classe->filiere->nom : '') . ' - ' . ($classe->niveauEtude ? $classe->niveauEtude->nom : ''),
                        'url' => route('esbtp.classes.show', $classe->id),
                        'icon' => 'fas fa-users',
                        'color' => 'info'
                    ];
                }
            }

            // Recherche de filières (pour admin et secrétaire)
            if ($user->hasAnyPermission(['admin.access', 'identity.school_manager'])) {
                $filieres = ESBTPFiliere::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('libelle', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->limit($limit)
                    ->get();

                foreach ($filieres as $filiere) {
                    $results[] = [
                        'category' => 'Filières',
                        'type' => 'filiere',
                        'id' => $filiere->id,
                        'title' => $filiere->name ?? $filiere->libelle,
                        'description' => $filiere->description ?? 'Aucune description',
                        'url' => route('esbtp.filieres.show', $filiere->id),
                        'icon' => 'fas fa-graduation-cap',
                        'color' => 'success'
                    ];
                }
            }

            // Recherche de matières (pour admin et secrétaire)
            if ($user->hasAnyPermission(['admin.access', 'identity.school_manager'])) {
                $matieres = ESBTPMatiere::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('code', 'LIKE', "%{$query}%")
                    ->limit($limit)
                    ->get();

                foreach ($matieres as $matiere) {
                    $results[] = [
                        'category' => 'Matières',
                        'type' => 'matiere',
                        'id' => $matiere->id,
                        'title' => $matiere->name,
                        'description' => $matiere->description ?? 'Aucune description',
                        'url' => route('esbtp.matieres.show', $matiere->id),
                        'icon' => 'fas fa-book',
                        'color' => 'info'
                    ];
                }
            }

            // Recherche d'enseignants (pour admin et secrétaire)
            if ($user->hasAnyPermission(['admin.access', 'identity.school_manager'])) {
                $enseignants = ESBTPTeacher::where('matricule', 'LIKE', "%{$query}%")
                    ->orWhere('specialization', 'LIKE', "%{$query}%")
                    ->orWhereHas('user', function($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('email', 'LIKE', "%{$query}%");
                    })
                    ->with(['user', 'department'])
                    ->limit($limit)
                    ->get();

                foreach ($enseignants as $enseignant) {
                    $results[] = [
                        'category' => 'Enseignants',
                        'type' => 'enseignant',
                        'id' => $enseignant->id,
                        'title' => $enseignant->user ? $enseignant->user->name : 'Nom non disponible',
                        'description' => $enseignant->matricule . ' - ' . ($enseignant->specialization ?? 'Spécialisation non définie'),
                        'url' => route('esbtp.teachers.show', $enseignant->id),
                        'icon' => 'fas fa-chalkboard-teacher',
                        'color' => 'warning'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'total' => count($results)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche: ' . $e->getMessage(),
                'results' => []
            ], 500);
        }
    }

    /**
     * Page de résultats de recherche détaillée
     */
    public function searchResults(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');
        $page = $request->get('page', 1);
        $perPage = 20;

        if (strlen($query) < 2) {
            return redirect()->back()->with('error', 'La recherche doit contenir au moins 2 caractères');
        }

        $user = Auth::user();
        $results = [];

        try {
            // Recherche d'étudiants
            if (($type === 'all' || $type === 'etudiants') && ($user->can('students.view') || $user->can('identity.student'))) {
                $etudiantsQuery = ESBTPEtudiant::where('nom', 'LIKE', "%{$query}%")
                    ->orWhere('prenoms', 'LIKE', "%{$query}%")
                    ->orWhere('matricule', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->with(['classe.filiere', 'classe.niveauEtude']);

                // Si c'est un étudiant, ne montrer que son propre profil
                if ($user->can('identity.student') && $user->etudiant) {
                    $etudiantsQuery->where('id', $user->etudiant->id);
                }

                $results['etudiants'] = $etudiantsQuery->paginate($perPage, ['*'], 'etudiants_page');
            }

            // Recherche de classes
            if (($type === 'all' || $type === 'classes') && $user->can('classes.view')) {
                $results['classes'] = ESBTPClasse::where('nom', 'LIKE', "%{$query}%")
                    ->with(['filiere', 'niveauEtude'])
                    ->paginate($perPage, ['*'], 'classes_page');
            }

            // Recherche de filières
            if (($type === 'all' || $type === 'filieres') && $user->can('filieres.view')) {
                $results['filieres'] = ESBTPFiliere::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('libelle', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->paginate($perPage, ['*'], 'filieres_page');
            }

            // Recherche de matières
            if (($type === 'all' || $type === 'matieres') && $user->can('matieres.view')) {
                $results['matieres'] = ESBTPMatiere::where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->paginate($perPage, ['*'], 'matieres_page');
            }

            // Recherche d'enseignants
            if (($type === 'all' || $type === 'enseignants') && ($user->can('teachers.view') || $user->hasAnyPermission(['admin.access', 'identity.school_manager']))) {
                $results['enseignants'] = ESBTPTeacher::where('matricule', 'LIKE', "%{$query}%")
                    ->orWhere('specialization', 'LIKE', "%{$query}%")
                    ->orWhereHas('user', function($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('email', 'LIKE', "%{$query}%");
                    })
                    ->with(['user', 'department'])
                    ->paginate($perPage, ['*'], 'enseignants_page');
            }

            return view('search.results', compact('results', 'query', 'type'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la recherche: ' . $e->getMessage());
        }
    }

    /**
     * Recherche de pages/navigation selon le rôle
     */
    private function searchNavigation($query, $user, $limit)
    {
        $results = [];
        $navigationItems = $this->getNavigationItems($user);

        foreach ($navigationItems as $item) {
            if (stripos($item['title'], $query) !== false || stripos($item['description'], $query) !== false) {
                $results[] = [
                    'category' => 'Navigation',
                    'type' => 'navigation',
                    'id' => $item['route'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'url' => $item['url'],
                    'icon' => $item['icon'],
                    'color' => 'secondary'
                ];
            }
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Recherche d'actions rapides selon le rôle
     */
    private function searchQuickActions($query, $user, $limit)
    {
        $results = [];
        $quickActions = $this->getQuickActions($user);

        foreach ($quickActions as $action) {
            if (stripos($action['title'], $query) !== false || stripos($action['description'], $query) !== false) {
                $results[] = [
                    'category' => 'Actions Rapides',
                    'type' => 'action',
                    'id' => $action['route'],
                    'title' => $action['title'],
                    'description' => $action['description'],
                    'url' => $action['url'],
                    'icon' => $action['icon'],
                    'color' => 'warning'
                ];
            }
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Recherche de personnel selon le rôle
     */
    private function searchPersonnel($query, $user, $limit)
    {
        $results = [];

        if ($user->hasAnyPermission(['admin.access', 'identity.school_manager'])) {
            // Recherche des secrétaires
            $secretaires = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'secretaire');
            })
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();

            foreach ($secretaires as $secretaire) {
                $results[] = [
                    'category' => 'Personnel',
                    'type' => 'secretaire',
                    'id' => $secretaire->id,
                    'title' => $secretaire->name,
                    'description' => 'Secrétaire - ' . $secretaire->email,
                    'url' => route('admin.profile'), // Ou une route spécifique si elle existe
                    'icon' => 'fas fa-user-tie',
                    'color' => 'info'
                ];
            }

            // Recherche des administrateurs
            $admins = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'superAdmin');
            })
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit($limit)
            ->get();

            foreach ($admins as $admin) {
                $results[] = [
                    'category' => 'Personnel',
                    'type' => 'admin',
                    'id' => $admin->id,
                    'title' => $admin->name,
                    'description' => 'Administrateur - ' . $admin->email,
                    'url' => route('admin.profile'),
                    'icon' => 'fas fa-user-shield',
                    'color' => 'danger'
                ];
            }
        }

        return $results;
    }

    /**
     * Obtenir les éléments de navigation selon le rôle
     */
    private function getNavigationItems($user)
    {
        $items = [];

        // Navigation commune
        $items[] = [
            'title' => 'Tableau de bord',
            'description' => 'Accueil et statistiques principales',
            'route' => 'dashboard',
            'url' => route('dashboard'),
            'icon' => 'fas fa-tachometer-alt'
        ];

        if ($user->hasAnyPermission(['admin.access', 'identity.school_manager'])) {
            $items = array_merge($items, [
                [
                    'title' => 'Gestion des étudiants',
                    'description' => 'Inscription, modification et suivi des étudiants',
                    'route' => 'esbtp.etudiants.index',
                    'url' => route('esbtp.etudiants.index'),
                    'icon' => 'fas fa-user-graduate'
                ],
                [
                    'title' => 'Gestion des classes',
                    'description' => 'Organisation et gestion des classes',
                    'route' => 'esbtp.classes.index',
                    'url' => route('esbtp.classes.index'),
                    'icon' => 'fas fa-users'
                ],
                [
                    'title' => 'Gestion des filières',
                    'description' => 'Configuration des filières d\'études',
                    'route' => 'esbtp.filieres.index',
                    'url' => route('esbtp.filieres.index'),
                    'icon' => 'fas fa-graduation-cap'
                ],
                [
                    'title' => 'Gestion des matières',
                    'description' => 'Configuration des matières enseignées',
                    'route' => 'esbtp.matieres.index',
                    'url' => route('esbtp.matieres.index'),
                    'icon' => 'fas fa-book'
                ],
                [
                    'title' => 'Bulletins de notes',
                    'description' => 'Génération et gestion des bulletins',
                    'route' => 'esbtp.resultats.index',
                    'url' => route('esbtp.resultats.index'),
                    'icon' => 'fas fa-file-alt'
                ]
            ]);
        }

        if ($user->can('admin.access')) {
            $items = array_merge($items, [
                [
                    'title' => 'Gestion des enseignants',
                    'description' => 'Administration du personnel enseignant',
                    'route' => 'esbtp.teachers.index',
                    'url' => route('esbtp.teachers.index'),
                    'icon' => 'fas fa-chalkboard-teacher'
                ],
                [
                    'title' => 'Paramètres système',
                    'description' => 'Configuration générale de l\'application',
                    'route' => 'settings.index',
                    'url' => route('settings.index'),
                    'icon' => 'fas fa-cogs'
                ]
            ]);
        }

        if ($user->can('identity.teach')) {
            $items = array_merge($items, [
                [
                    'title' => 'Mes cours',
                    'description' => 'Emploi du temps et cours assignés',
                    'route' => 'teacher.timetable',
                    'url' => route('teacher.timetable'),
                    'icon' => 'fas fa-calendar-alt'
                ],
                [
                    'title' => 'Saisie des notes',
                    'description' => 'Évaluation et notation des étudiants',
                    'route' => 'teacher.grades',
                    'url' => route('teacher.grades'),
                    'icon' => 'fas fa-edit'
                ],
                [
                    'title' => 'Présences',
                    'description' => 'Gestion des présences étudiants',
                    'route' => 'teacher.attendance',
                    'url' => route('teacher.attendance'),
                    'icon' => 'fas fa-check-circle'
                ]
            ]);
        }

        if ($user->can('identity.student')) {
            $items = array_merge($items, [
                [
                    'title' => 'Mon profil',
                    'description' => 'Informations personnelles et académiques',
                    'route' => 'dashboard.etudiant',
                    'url' => route('dashboard.etudiant'),
                    'icon' => 'fas fa-user'
                ],
                [
                    'title' => 'Mes bulletins',
                    'description' => 'Consultation des bulletins de notes',
                    'route' => 'dashboard.etudiant',
                    'url' => route('dashboard.etudiant') . '#bulletins',
                    'icon' => 'fas fa-file-alt'
                ],
                [
                    'title' => 'Mon emploi du temps',
                    'description' => 'Planning des cours et examens',
                    'route' => 'dashboard.etudiant',
                    'url' => route('dashboard.etudiant') . '#emploi-temps',
                    'icon' => 'fas fa-calendar'
                ]
            ]);
        }

        return $items;
    }

    /**
     * Obtenir les actions rapides selon le rôle
     */
    private function getQuickActions($user)
    {
        $actions = [];

        if ($user->hasAnyPermission(['admin.access', 'identity.school_manager'])) {
            $actions = array_merge($actions, [
                [
                    'title' => 'Inscrire un étudiant',
                    'description' => 'Ajouter un nouvel étudiant au système',
                    'route' => 'esbtp.inscriptions.create',
                    'url' => route('esbtp.inscriptions.create'),
                    'icon' => 'fas fa-user-plus'
                ],
                [
                    'title' => 'Créer une classe',
                    'description' => 'Ajouter une nouvelle classe',
                    'route' => 'esbtp.classes.create',
                    'url' => route('esbtp.classes.create'),
                    'icon' => 'fas fa-plus-circle'
                ],
                [
                    'title' => 'Nouvelle annonce',
                    'description' => 'Publier une annonce',
                    'route' => 'esbtp.annonces.create',
                    'url' => route('esbtp.annonces.create'),
                    'icon' => 'fas fa-bullhorn'
                ]
            ]);
        }

        if ($user->can('admin.access')) {
            $actions = array_merge($actions, [
                [
                    'title' => 'Ajouter un enseignant',
                    'description' => 'Recruter un nouveau professeur',
                    'route' => 'esbtp.teachers.create',
                    'url' => route('esbtp.teachers.create'),
                    'icon' => 'fas fa-user-tie'
                ],
                [
                    'title' => 'Créer une filière',
                    'description' => 'Ajouter une nouvelle filière d\'études',
                    'route' => 'esbtp.filieres.create',
                    'url' => route('esbtp.filieres.create'),
                    'icon' => 'fas fa-graduation-cap'
                ],
                [
                    'title' => 'Ajouter une matière',
                    'description' => 'Créer une nouvelle matière',
                    'route' => 'esbtp.matieres.create',
                    'url' => route('esbtp.matieres.create'),
                    'icon' => 'fas fa-book-open'
                ]
            ]);
        }

        if ($user->can('identity.teach')) {
            $actions = array_merge($actions, [
                [
                    'title' => 'Saisir les présences',
                    'description' => 'Marquer les présences du jour',
                    'route' => 'teacher.attendance',
                    'url' => route('teacher.attendance'),
                    'icon' => 'fas fa-check'
                ],
                [
                    'title' => 'Ajouter des notes',
                    'description' => 'Saisir les notes d\'évaluation',
                    'route' => 'teacher.grades',
                    'url' => route('teacher.grades'),
                    'icon' => 'fas fa-edit'
                ],
                [
                    'title' => 'Programmer un examen',
                    'description' => 'Planifier une évaluation',
                    'route' => 'esbtp.evaluations.create',
                    'url' => route('esbtp.evaluations.create'),
                    'icon' => 'fas fa-calendar-plus'
                ]
            ]);
        }

        return $actions;
    }
}
