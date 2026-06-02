<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Helpers\RoleHelper;

/**
 * Contrôleur d'authentification pour les APIs LMS
 *
 * Gère l'authentification unifiée entre KLASSCI et le LMS.
 * Utilise Laravel Sanctum pour la gestion des tokens.
 *
 * @package App\Http\Controllers\API
 * @author KLASSCI Team
 * @version 1.0
 */
class AuthController extends BaseApiController
{
    public function __construct()
    {
        // Ne pas appliquer l'auth sur les méthodes publiques LMS
        $this->middleware('auth:sanctum')->except([
            'login', 'documentation', 'checkUser', 'checkAvailability', 'tenantInfo'
        ]);
    }

    /**
     * Connexion utilisateur (réutilise la logique KLASSCI)
     *
     * Endpoint: POST /api/auth/login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:6',
            'remember' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Données de connexion invalides',
                $validator->errors()->toArray(),
                422
            );
        }

        // Déterminer si c'est un email ou un nom d'utilisateur (comme KLASSCI)
        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Tentative de connexion
        $credentials = [
            $field => $request->username,
            'password' => $request->password
        ];

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse(
                'Identifiants incorrects',
                [],
                401
            );
        }

        /** @var User $user */
        $user = Auth::user();

        // Vérifier que l'utilisateur est actif
        if (!$user->is_active) {
            Auth::logout();
            return $this->errorResponse(
                'Compte désactivé. Contactez l\'administration.',
                [],
                403
            );
        }

        // Vérifier que l'utilisateur a un rôle autorisé pour le LMS
        $userRole = $user->getRoleNames()->first();

        if (!RoleHelper::hasAnyRole($userRole, RoleHelper::LMS_ALLOWED_ROLES)) {
            Auth::logout();
            return $this->errorResponse(
                'Accès non autorisé au LMS. Rôles requis: ' . implode(', ', RoleHelper::LMS_ALLOWED_ROLES),
                [],
                403
            );
        }

        // Générer un token Sanctum pour l'API
        $tokenName = 'LMS-' . $user->email . '-' . now()->timestamp;
        $token = $user->createToken($tokenName, ['lms:access'])->plainTextToken;

        // Données utilisateur pour le LMS
        $userRole = $user->getRoleNames()->first();
        $userData = [
            'id' => $user->id,
            'nom' => $user->name,
            'email' => $user->email,
            'role' => $userRole,
            'roles' => $user->getRoleNames()->toArray(),
            'avatar' => $user->profile_photo_url ?? null,
            'role_display_name' => RoleHelper::getRoleDisplayName($userRole),
            'permissions' => RoleHelper::getRolePermissions($userRole),
            'is_admin' => RoleHelper::isAdmin($userRole),
            'is_coordinator_equivalent' => RoleHelper::isCoordinatorEquivalent($userRole),
            'preferences' => [
                'langue' => $user->langue ?? 'fr',
                'timezone' => $user->timezone ?? 'Africa/Douala'
            ]
        ];

        // Ajouter des données spécifiques selon le rôle
        if ($user->can('identity.teach')) {
            $userData['enseignant_data'] = $this->getEnseignantData($user);
        } elseif ($user->can('identity.student')) {
            // VÉRIFIER que l'étudiant a une inscription active pour l'année courante
            $etudiantData = $this->getEtudiantData($user);

            if (empty($etudiantData)) {
                Auth::logout();
                return $this->errorResponse(
                    'Vous n\'êtes pas encore réinscrit pour l\'année universitaire en cours. Veuillez contacter le secrétariat pour procéder à votre réinscription.',
                    ['code' => 'NO_ACTIVE_ENROLLMENT'],
                    403
                );
            }

            $userData['etudiant_data'] = $etudiantData;
        } elseif (RoleHelper::isCoordinatorEquivalent($userRole)) {
            // Les coordinateurs et superAdmin ont accès aux mêmes données d'administration
            $userData['admin_data'] = $this->getAdminData($user);
        }

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $userData
        ], 'Connexion réussie');
    }

    /**
     * Informations de l'utilisateur connecté
     *
     * Endpoint: GET /api/auth/me
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Utilisateur non authentifié', [], 401);
        }

        $userData = [
            'id' => $user->id,
            'nom' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'roles' => $user->getRoleNames()->toArray(),
            'avatar' => $user->profile_photo_url ?? null,
            'derniere_connexion' => $user->last_login_at,
            'preferences' => [
                'langue' => $user->langue ?? 'fr',
                'timezone' => $user->timezone ?? 'Africa/Douala'
            ]
        ];

        // Ajouter des données contextuelles selon le rôle
        if ($user->can('identity.teach')) {
            $userData['contexte_enseignant'] = $this->getEnseignantData($user);
        } elseif ($user->can('identity.student')) {
            $userData['contexte_etudiant'] = $this->getEtudiantData($user);
        }

        return $this->successResponse($userData, 'Profil utilisateur récupéré');
    }

    /**
     * Déconnexion (révoque le token actuel)
     *
     * Endpoint: POST /api/auth/logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        // Révoquer le token actuel
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Déconnexion réussie');
    }

    /**
     * Déconnexion de tous les appareils (révoque tous les tokens)
     *
     * Endpoint: POST /api/auth/logout-all
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Révoquer tous les tokens de l'utilisateur
        $request->user()->tokens()->delete();

        return $this->successResponse(null, 'Déconnexion de tous les appareils réussie');
    }

    /**
     * Vérification de validité du token
     *
     * Endpoint: GET /api/auth/check
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Token invalide ou expiré', [], 401);
        }

        return $this->successResponse([
            'valid' => true,
            'user_id' => $user->id,
            'expires_in' => null // Sanctum tokens don't expire by default
        ], 'Token valide');
    }

    /**
     * Récupère les données spécifiques à un enseignant
     *
     * @param User $user
     * @return array
     */
    private function getEnseignantData(User $user): array
    {
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return [];
        }

        // Récupérer les matières enseignées cette année
        $matieres = \App\Models\ESBTPMatiere::whereHas('enseignants', function ($q) use ($user, $annee) {
            $q->where('enseignant_id', $user->id)
              ->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
              ->where('esbtp_enseignant_matiere.is_active', true);
        })->with(['classes', 'niveauEtude', 'filiere'])->get();

        // Récupérer les classes enseignées
        $classes = collect();
        foreach ($matieres as $matiere) {
            $classes = $classes->merge($matiere->classes);
        }
        $classes = $classes->unique('id');

        return [
            'nb_matieres' => $matieres->count(),
            'nb_classes' => $classes->count(),
            'matieres_principales' => $matieres->map(function ($matiere) {
                return [
                    'id' => $matiere->id,
                    'nom' => $matiere->nom,
                    'code' => $matiere->code
                ];
            })->toArray(),
            'classes_enseignees' => $classes->map(function ($classe) {
                return [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                    'filiere' => $classe->filiere->nom ?? null,
                    'niveau' => $classe->niveau->nom ?? null
                ];
            })->toArray()
        ];
    }

    /**
     * Récupère les données spécifiques à un étudiant
     *
     * @param User $user
     * @return array
     */
    private function getEtudiantData(User $user): array
    {
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return [];
        }

        // Récupérer l'étudiant associé
        $etudiant = $user->etudiant;
        if (!$etudiant) {
            return [];
        }

        // Récupérer l'inscription courante
        $inscription = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->with(['classe.filiere', 'classe.niveau'])
            ->first();

        if (!$inscription) {
            return [];
        }

        return [
            'etudiant_id' => $etudiant->id,
            'matricule' => $etudiant->matricule,
            'inscription_id' => $inscription->id,
            'classe' => [
                'id' => $inscription->classe->id,
                'nom' => $inscription->classe->nom,
                'filiere' => $inscription->classe->filiere->nom ?? null,
                'niveau' => $inscription->classe->niveau->nom ?? null
            ],
            'statut_inscription' => $inscription->status
        ];
    }

    /**
     * Récupère les données spécifiques aux administrateurs
     * Utilisé pour coordinateur et superAdmin (rôles équivalents)
     *
     * @param User $user
     * @return array
     */
    private function getAdminData(User $user): array
    {
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return [];
        }

        // Statistiques générales — distinguer étudiants inscrits année courante (canonique)
        // de étudiants en base (tous statuts confondus) pour éviter l'écart trompeur.
        $studentCounts = app(\App\Domain\Students\StudentCountService::class)->counts();
        $stats = [
            'nb_enseignants' => \App\Models\User::role('enseignant')->count(),
            'nb_etudiants' => $studentCounts['inscrits_annee_courante'],
            'nb_etudiants_base' => $studentCounts['total_base'],
            'annee_label' => $studentCounts['annee_courante_label'],
            'nb_classes_actives' => \App\Models\ESBTPClasse::where('is_active', true)->count(),
            'nb_matieres_actives' => \App\Models\ESBTPMatiere::where('is_active', true)->count(),
        ];

        // Informations sur l'année universitaire courante
        $anneeInfo = [
            'id' => $annee->id,
            'nom' => $annee->nom,
            'date_debut' => $annee->date_debut,
            'date_fin' => $annee->date_fin,
            'is_current' => $annee->is_current
        ];

        return [
            'role_equivalent' => 'coordinateur', // Normalisation pour l'interface
            'access_level' => 'full_admin',
            'permissions' => RoleHelper::getRolePermissions($user->getRoleNames()->first()),
            'statistics' => $stats,
            'annee_universitaire' => $anneeInfo,
            'can_manage_users' => true,
            'can_view_all_data' => true,
            'can_generate_reports' => true
        ];
    }

    /**
     * Retourne le contexte tenant (code, nom, URL) depuis la config
     */
    private function getTenantContext(): array
    {
        return [
            'code' => config('app.tenant_code', 'default'),
            'name' => config('app.name', 'KLASSCI'),
            'url' => config('app.url'),
        ];
    }

    /**
     * Recherche un utilisateur par email, username ou matricule
     * Utilisé par le LMS pour détecter le tenant d'un utilisateur (login unifié)
     *
     * Endpoint: POST /api/lms/auth/check-user
     * Rate-limited: 10 requêtes/minute par IP
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string|min:3|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Identifiant invalide', $validator->errors()->toArray(), 422);
        }

        $identifier = trim($request->input('identifier'));
        $tenant = $this->getTenantContext();

        // Chercher par email ou username dans users (avec roles eager-loaded)
        $user = User::with('roles')
            ->where('is_active', true)
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                  ->orWhere('username', $identifier);
            })
            ->first();

        // Si pas trouvé, chercher par matricule dans esbtp_etudiants
        if (!$user) {
            $etudiant = ESBTPEtudiant::where('matricule', $identifier)
                ->whereNotNull('user_id')
                ->with(['user' => fn ($q) => $q->with('roles')->where('is_active', true)])
                ->first();

            $user = $etudiant?->user;
        }

        if (!$user) {
            return $this->successResponse([
                'found' => false,
                'tenant_code' => $tenant['code'],
            ], 'Utilisateur non trouvé sur ce tenant');
        }

        // Vérifier que l'utilisateur a un rôle LMS
        $userRole = $user->getRoleNames()->first();

        if (!$userRole || !RoleHelper::hasAnyRole($userRole, RoleHelper::LMS_ALLOWED_ROLES)) {
            return $this->successResponse([
                'found' => false,
                'tenant_code' => $tenant['code'],
            ], 'Utilisateur non trouvé sur ce tenant');
        }

        // Retour minimal pour limiter la fuite d'info (pas de rôle exact)
        $displayName = $user->first_name;
        if ($user->last_name) {
            $displayName .= ' ' . mb_strtoupper(mb_substr($user->last_name, 0, 1)) . '.';
        }

        return $this->successResponse([
            'found' => true,
            'tenant_code' => $tenant['code'],
            'tenant_name' => $tenant['name'],
            'tenant_url' => $tenant['url'],
            'user_hint' => [
                'display_name' => $displayName,
                'role_display' => RoleHelper::getRoleDisplayName($userRole),
            ],
        ], 'Utilisateur trouvé');
    }

    /**
     * Vérifie si un email ou username existe sur ce tenant
     *
     * Endpoint: POST /api/lms/auth/check-availability
     * Rate-limited: 10 requêtes/minute par IP
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|string|email|max:255',
            'username' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Données invalides', $validator->errors()->toArray(), 422);
        }

        if (!$request->email && !$request->username) {
            return $this->errorResponse('Au moins un champ (email ou username) est requis', [], 422);
        }

        $result = [
            'tenant_code' => $this->getTenantContext()['code'],
        ];

        if ($request->email) {
            $result['email_exists'] = User::where('email', $request->email)->where('is_active', true)->exists();
        }

        if ($request->username) {
            $result['username_exists'] = User::where('username', $request->username)->where('is_active', true)->exists();
        }

        return $this->successResponse($result, 'Vérification effectuée');
    }

    /**
     * Informations publiques du tenant pour le LMS
     *
     * Endpoint: GET /api/lms/tenant-info
     *
     * @return JsonResponse
     */
    public function tenantInfo(): JsonResponse
    {
        $tenant = $this->getTenantContext();
        $annee = $this->getAnneeCouraante();

        return $this->successResponse([
            'tenant_code' => $tenant['code'],
            'tenant_name' => $tenant['name'],
            'tenant_url' => $tenant['url'],
            'api_base_url' => url('/api/lms'),
            'api_version' => '1.0',
            'annee_universitaire' => $annee ? [
                'id' => $annee->id,
                'nom' => $annee->nom ?? "{$annee->annee_debut}-{$annee->annee_fin}",
            ] : null,
            'features' => [
                'login' => true,
                'classes' => true,
                'matieres' => true,
                'enseignants' => true,
                'evaluations' => true,
                'emploi_temps' => true,
                'notes_write' => true,
                'presences_write' => true,
                'visio_support' => true,
            ],
        ], 'Informations tenant');
    }

    /**
     * Documentation de l'API d'authentification
     *
     * Endpoint: GET /api/auth/documentation
     *
     * @return JsonResponse
     */
    public function documentation(): JsonResponse
    {
        return response()->json([
            'title' => 'API d\'authentification KLASSCI-LMS',
            'version' => '1.0',
            'description' => 'Authentification unifiée entre KLASSCI et le LMS',
            'endpoints' => [
                [
                    'method' => 'POST',
                    'path' => '/api/auth/login',
                    'description' => 'Connexion utilisateur',
                    'parameters' => [
                        'username' => 'string (required) - Email ou nom d\'utilisateur',
                        'password' => 'string (required) - Mot de passe',
                        'remember' => 'boolean (optional) - Se souvenir de moi'
                    ],
                    'response' => 'Token d\'accès + données utilisateur'
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/auth/me',
                    'description' => 'Profil utilisateur connecté',
                    'headers' => ['Authorization: Bearer {token}'],
                    'response' => 'Données utilisateur avec contexte métier'
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/auth/logout',
                    'description' => 'Déconnexion (révoque token actuel)',
                    'headers' => ['Authorization: Bearer {token}']
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/auth/check',
                    'description' => 'Vérification validité token',
                    'headers' => ['Authorization: Bearer {token}']
                ]
            ],
            'authentication' => [
                'type' => 'Bearer Token (Laravel Sanctum)',
                'header' => 'Authorization: Bearer {your_token}',
                'scopes' => ['lms:access']
            ],
            'roles_autorises' => RoleHelper::LMS_ALLOWED_ROLES
        ]);
    }
}