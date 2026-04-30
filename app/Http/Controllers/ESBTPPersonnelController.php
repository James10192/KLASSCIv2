<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ESBTPPersonnelController extends Controller
{
    /**
     * Display a listing of all personnel with sliders.
     */
    public function index()
    {
        // Vérifier les permissions
        $this->authorize('users.manage');
        
        // Récupérer tous les types de personnel
        $coordinateurs = User::role('coordinateur')
            ->with(['roles'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $enseignants = User::role('enseignant')
            ->with(['roles'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        $secretaires = User::role('secretaire')
            ->with(['roles'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
            
        // Calculer les statistiques
        $stats = [
            'coordinateurs' => $coordinateurs->count(),
            'enseignants' => $enseignants->count(),
            'secretaires' => $secretaires->count(),
        ];
        
        return view('esbtp.personnel.index', compact(
            'coordinateurs',
            'enseignants', 
            'secretaires',
            'stats'
        ));
    }

    /**
     * Get personnel data via AJAX for dynamic loading
     */
    public function getData(Request $request)
    {
        $this->authorize('users.manage');
        
        $type = $request->get('type'); // coordinateur, enseignant, secretaire
        $search = $request->get('search');
        $status = $request->get('status');
        $filter = $request->get('filter'); // spécialité, matière, service
        
        $query = User::role($type)->with(['roles']);
        
        // Filtrer par statut
        if ($status) {
            $query->where('is_active', $status === 'active');
        }
        
        // Filtrer par recherche
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%")
                  ->orWhere('specialite', 'like', "%{$search}%");
            });
        }
        
        // Filtrer par critère spécifique
        if ($filter) {
            switch($type) {
                case 'coordinateur':
                    $query->where('specialite', 'like', "%{$filter}%");
                    break;
                case 'enseignant':
                    // Vous pouvez ajouter une relation vers les matières enseignées
                    $query->where('specialite', 'like', "%{$filter}%");
                    break;
                case 'secretaire':
                    // Vous pouvez ajouter un champ service
                    $query->where('service', 'like', "%{$filter}%");
                    break;
            }
        }
        
        $personnel = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $personnel,
            'count' => $personnel->count()
        ]);
    }

    /**
     * Store a newly created personnel in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('users.manage');
        
        $type = $request->get('type'); // coordinateur, enseignant, secretaire
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'service' => 'nullable|string|max:255', // Pour les secrétaires
            'matiere' => 'nullable|string|max:255', // Pour les enseignants
            'date_naissance' => 'nullable|date|before:today',
            'adresse' => 'nullable|string|max:500',
            'type' => 'required|in:coordinateur,enseignant,secretaire',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'service' => $validated['service'] ?? null,
                'matiere' => $validated['matiere'] ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assigner le rôle approprié
            $user->assignRole($validated['type']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($validated['type']) . ' créé avec succès.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified personnel in storage.
     */
    public function update(Request $request, User $personnel)
    {
        $this->authorize('users.manage');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $personnel->id,
            'password' => 'nullable|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'service' => 'nullable|string|max:255',
            'matiere' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date|before:today',
            'adresse' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'service' => $validated['service'] ?? null,
                'matiere' => $validated['matiere'] ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'is_active' => $validated['is_active'],
            ];

            // Si un nouveau mot de passe est fourni
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $personnel->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Personnel mis à jour avec succès.',
                'data' => $personnel
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified personnel from storage.
     */
    public function destroy(User $personnel)
    {
        $this->authorize('users.manage');
        
        // Empêcher la suppression de son propre compte
        if ($personnel->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Marquer comme inactif au lieu de supprimer complètement
            $personnel->update([
                'is_active' => false,
                'email' => $personnel->email . '_deleted_' . time(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Personnel supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle active status of personnel
     */
    public function toggleStatus(User $personnel)
    {
        $this->authorize('users.manage');
        
        $personnel->update([
            'is_active' => !$personnel->is_active
        ]);

        $status = $personnel->is_active ? 'activé' : 'désactivé';
        
        return response()->json([
            'success' => true,
            'message' => "Personnel {$status} avec succès.",
            'is_active' => $personnel->is_active
        ]);
    }

    /**
     * Get personnel statistics
     */
    public function getStats()
    {
        $this->authorize('users.manage');
        
        $stats = [
            'coordinateurs' => [
                'total' => User::role('coordinateur')->count(),
                'actifs' => User::role('coordinateur')->where('is_active', true)->count(),
                'inactifs' => User::role('coordinateur')->where('is_active', false)->count(),
                'nouveau_ce_mois' => User::role('coordinateur')->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'enseignants' => [
                'total' => User::role('enseignant')->count(),
                'actifs' => User::role('enseignant')->where('is_active', true)->count(),
                'inactifs' => User::role('enseignant')->where('is_active', false)->count(),
                'nouveau_ce_mois' => User::role('enseignant')->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'secretaires' => [
                'total' => User::role('secretaire')->count(),
                'actifs' => User::role('secretaire')->where('is_active', true)->count(),
                'inactifs' => User::role('secretaire')->where('is_active', false)->count(),
                'nouveau_ce_mois' => User::role('secretaire')->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ];
        
        return response()->json($stats);
    }

    /**
     * Export personnel data
     */
    public function export(Request $request)
    {
        $this->authorize('users.manage');
        
        $type = $request->get('type');
        $format = $request->get('format', 'csv'); // csv, xlsx, pdf
        
        // Logique d'export à implémenter selon vos besoins
        // Vous pouvez utiliser des packages comme Laravel Excel
        
        return response()->json([
            'success' => true,
            'message' => 'Export en cours de développement...'
        ]);
    }

    /**
     * Bulk actions on personnel
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('users.manage');
        
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id'
        ]);
        
        $action = $validated['action'];
        $ids = $validated['ids'];
        
        // Empêcher l'action sur son propre compte
        if (in_array(Auth::id(), $ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas effectuer cette action sur votre propre compte.'
            ], 403);
        }
        
        try {
            DB::beginTransaction();
            
            $users = User::whereIn('id', $ids)->get();
            
            foreach ($users as $user) {
                switch ($action) {
                    case 'activate':
                        $user->update(['is_active' => true]);
                        break;
                    case 'deactivate':
                        $user->update(['is_active' => false]);
                        break;
                    case 'delete':
                        $user->update([
                            'is_active' => false,
                            'email' => $user->email . '_deleted_' . time(),
                        ]);
                        break;
                }
            }
            
            DB::commit();
            
            $actionLabel = [
                'activate' => 'activés',
                'deactivate' => 'désactivés',
                'delete' => 'supprimés'
            ];
            
            return response()->json([
                'success' => true,
                'message' => count($ids) . ' utilisateur(s) ' . $actionLabel[$action] . ' avec succès.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'action groupée : ' . $e->getMessage()
            ], 500);
        }
    }
}