<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

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
        // Ne pas appliquer l'auth sur les méthodes de connexion
        $this->middleware('auth:sanctum')->except(['login', 'documentation']);
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
            'email' => 'required|email',
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

        // Tentative de connexion
        $credentials = $request->only('email', 'password');

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
        $rolesAutorises = ['enseignant', 'coordinateur', 'etudiant'];
        if (!$user->hasAnyRole($rolesAutorises)) {
            Auth::logout();
            return $this->errorResponse(
                'Accès non autorisé au LMS. Rôles requis: ' . implode(', ', $rolesAutorises),
                [],
                403
            );
        }

        // Générer un token Sanctum pour l'API
        $tokenName = 'LMS-' . $user->email . '-' . now()->timestamp;
        $token = $user->createToken($tokenName, ['lms:access'])->plainTextToken;

        // Données utilisateur pour le LMS
        $userData = [
            'id' => $user->id,
            'nom' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'roles' => $user->getRoleNames()->toArray(),
            'avatar' => $user->profile_photo_url ?? null,
            'preferences' => [
                'langue' => $user->langue ?? 'fr',
                'timezone' => $user->timezone ?? 'Africa/Douala'
            ]
        ];

        // Ajouter des données spécifiques selon le rôle
        if ($user->hasRole('enseignant')) {
            $userData['enseignant_data'] = $this->getEnseignantData($user);
        } elseif ($user->hasRole('etudiant')) {
            $userData['etudiant_data'] = $this->getEtudiantData($user);
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
        if ($user->hasRole('enseignant')) {
            $userData['contexte_enseignant'] = $this->getEnseignantData($user);
        } elseif ($user->hasRole('etudiant')) {
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
                        'email' => 'string (required) - Email de l\'utilisateur',
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
            'roles_autorises' => ['enseignant', 'coordinateur', 'etudiant']
        ]);
    }
}