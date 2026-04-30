<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPTeacher;

/**
 * Contrôleur de base pour toutes les APIs LMS
 *
 * Fournit des méthodes communes pour la gestion des réponses API
 * et la logique métier partagée entre toutes les APIs LMS.
 *
 * @package App\Http\Controllers\API
 * @author KLASSCI Team
 * @version 1.0
 */
class BaseApiController extends Controller
{
    /**
     * @var ESBTPAnneeUniversitaire|null Année universitaire courante mise en cache
     */
    protected $anneeCouraante = null;

    /**
     * Constructeur - Initialise l'année universitaire courante
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['documentation']);
    }

    /**
     * Retourne une réponse de succès standardisée
     *
     * @param mixed $data Données à retourner
     * @param string $message Message de succès (optionnel)
     * @param array $meta Métadonnées supplémentaires
     * @param int $statusCode Code de statut HTTP
     * @return JsonResponse
     */
    protected function successResponse($data = null, string $message = '', array $meta = [], int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge($this->getBaseMeta(), $meta)
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Retourne une réponse d'erreur standardisée
     *
     * @param string $message Message d'erreur
     * @param array $errors Détails des erreurs (optionnel)
     * @param int $statusCode Code de statut HTTP
     * @return JsonResponse
     */
    protected function errorResponse(string $message, array $errors = [], int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'meta' => $this->getBaseMeta()
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Récupère l'année universitaire courante (avec mise en cache)
     *
     * @return ESBTPAnneeUniversitaire|null
     */
    protected function getAnneeCouraante(): ?ESBTPAnneeUniversitaire
    {
        if ($this->anneeCouraante === null) {
            $this->anneeCouraante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        }

        return $this->anneeCouraante;
    }

    /**
     * Récupère les métadonnées de base pour toutes les réponses API
     *
     * @return array
     */
    protected function getBaseMeta(): array
    {
        $annee = $this->getAnneeCouraante();
        $user = auth()->user();

        return [
            'timestamp' => now()->toISOString(),
            'api_version' => '1.0',
            'lms_integration' => [
                'supported_features' => ['matieres', 'evaluations', 'notes', 'emploi_temps', 'presences'],
                'readonly_data' => ['classes', 'etudiants', 'enseignants', 'planning'],
                'writable_data' => ['notes', 'presences']
            ],
            'annee_universitaire_courante' => $annee ? [
                'id' => $annee->id,
                'nom' => $annee->nom ?? "{$annee->annee_debut}-{$annee->annee_fin}",
                'annee_debut' => $annee->annee_debut,
                'annee_fin' => $annee->annee_fin,
                'is_current' => true
            ] : null,
            'user_context' => $user ? [
                'id' => $user->id,
                'role' => $user->getRoleNames()->first(),
                'is_enseignant' => $user->can('identity.teach'),
                'is_coordinateur' => $user->can('identity.coordinate'),
                'is_etudiant' => $user->can('identity.student')
            ] : null
        ];
    }

    /**
     * Valide que l'utilisateur a le rôle requis
     *
     * @param array|string $roles Rôles autorisés
     * @return bool
     */
    protected function hasRequiredRole($roles): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        // Map roles to permissions for permission-based checks
        $roleToPermission = [
            'superAdmin' => 'admin.access',
            'secretaire' => 'identity.school_manager',
            'coordinateur' => 'identity.coordinate',
            'enseignant' => 'identity.teach',
            'teacher' => 'identity.teach',
            'etudiant' => 'identity.student',
            'serviceTechnique' => 'module.technical_support.access',
            'comptable' => 'comptabilite.access',
        ];

        $permissions = [];
        foreach ($roles as $role) {
            if (isset($roleToPermission[$role])) {
                $permissions[] = $roleToPermission[$role];
            }
        }

        $permissions = array_unique($permissions);

        if (empty($permissions)) {
            return false;
        }

        return $user->hasAnyPermission($permissions);
    }

    /**
     * Middleware pour vérifier les rôles avant l'accès à une méthode
     *
     * @param array|string $roles
     * @return JsonResponse|null
     */
    protected function checkRoleAccess($roles): ?JsonResponse
    {
        if (!$this->hasRequiredRole($roles)) {
            return $this->errorResponse(
                'Accès non autorisé. Rôles requis: ' . (is_array($roles) ? implode(', ', $roles) : $roles),
                [],
                403
            );
        }

        return null;
    }

    /**
     * Filtre les données selon le rôle de l'utilisateur
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $context Contexte du filtrage (matieres, classes, etc.)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyRoleFilters($query, string $context = '')
    {
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // Retourner une requête vide
        }

        // Les coordinateurs et superAdmin ont accès à tout
        if ($user->hasAnyPermission(['admin.access', 'identity.coordinate'])) {
            return $query;
        }

        // Filtres spécifiques par rôle et contexte
        if ($user->can('identity.teach')) {
            return $this->applyEnseignantFilters($query, $context);
        }

        if ($user->can('identity.student')) {
            return $this->applyEtudiantFilters($query, $context);
        }

        return $query->whereRaw('1 = 0'); // Aucun rôle reconnu
    }

    /**
     * Applique les filtres pour les enseignants
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $context
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyEnseignantFilters($query, string $context)
    {
        $user = auth()->user();
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return $query->whereRaw('1 = 0');
        }

        switch ($context) {
            case 'matieres':
                // Seulement les matières que l'enseignant enseigne cette année
                return $query->whereHas('enseignants', function ($q) use ($user, $annee) {
                    $q->where('enseignant_id', $user->id)
                      ->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
                      ->where('esbtp_enseignant_matiere.is_active', true);
                });

            case 'evaluations':
                // Autoriser plusieurs chemins pour retrouver les évaluations de l'enseignant
                $teacher = ESBTPTeacher::where('user_id', $user->id)->first();
                $teacherId = $teacher ? $teacher->id : null;

                return $query->where(function ($subQuery) use ($user, $annee, $teacherId) {
                    // 1) Assignation directe (colonne enseignant_id)
                    $subQuery->where('enseignant_id', $user->id);

                    // 2) Table pivot esbtp_enseignant_matiere (assignations officielles)
                    $subQuery->orWhereHas('matiere.enseignants', function ($q) use ($user, $annee) {
                        $q->where('enseignant_id', $user->id)
                          ->where('esbtp_enseignant_matiere.annee_universitaire_id', $annee->id)
                          ->where('esbtp_enseignant_matiere.is_active', true);
                    });

                    if ($teacherId) {
                        // 3) Séances planifiées (planning général) sur la même matière
                        $subQuery->orWhereExists(function ($q) use ($teacherId, $annee) {
                            $q->selectRaw('1')
                              ->from('esbtp_seance_cours as sc_matiere')
                              ->whereColumn('sc_matiere.matiere_id', 'esbtp_evaluations.matiere_id')
                              ->where('sc_matiere.teacher_id', $teacherId)
                              ->where('sc_matiere.annee_universitaire_id', $annee->id);
                        });

                        // 4) Séances planifiées sur la même classe
                        $subQuery->orWhereExists(function ($q) use ($teacherId, $annee) {
                            $q->selectRaw('1')
                              ->from('esbtp_seance_cours as sc_classe')
                              ->whereColumn('sc_classe.classe_id', 'esbtp_evaluations.classe_id')
                              ->where('sc_classe.teacher_id', $teacherId)
                              ->where('sc_classe.annee_universitaire_id', $annee->id);
                        });
                    }
                });

            default:
                return $query;
        }
    }

    /**
     * Applique les filtres pour les étudiants
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $context
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyEtudiantFilters($query, string $context)
    {
        $user = auth()->user();
        $annee = $this->getAnneeCouraante();

        if (!$annee) {
            return $query->whereRaw('1 = 0');
        }

        // Récupérer l'étudiant associé à l'utilisateur
        $etudiant = $user->etudiant;
        if (!$etudiant) {
            return $query->whereRaw('1 = 0');
        }

        // Récupérer l'inscription courante de l'étudiant
        $inscription = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->first();

        if (!$inscription) {
            return $query->whereRaw('1 = 0');
        }

        switch ($context) {
            case 'matieres':
                // Seulement les matières de sa classe
                return $query->whereHas('classes', function ($q) use ($inscription) {
                    $q->where('esbtp_classe.id', $inscription->classe_id);
                });

            case 'evaluations':
                // Seulement les évaluations de sa classe
                return $query->where('classe_id', $inscription->classe_id);

            default:
                return $query;
        }
    }
}
