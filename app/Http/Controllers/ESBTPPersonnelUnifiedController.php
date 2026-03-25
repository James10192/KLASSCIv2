<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ESBTPTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ESBTPPersonnelUnifiedController extends Controller
{
    /**
     * Display a listing of all personnel with sliders.
     */
    public function index(Request $request)
    {
        // Vérifier les permissions via toggle (manage_personnel)
        if (!auth()->user()->can('manage_personnel')) {
            abort(403, 'Accès non autorisé');
        }

        // Rôle principal de l'utilisateur connecté — on cache le tab de son propre rôle
        // (un secretaire ne gère pas d'autres secretaires, un coordinateur ne gère pas d'autres coordinateurs)
        $userRole = auth()->user()->getRoleNames()->first();

        // Récupérer tous les types de personnel avec vérification des rôles
        $coordinateurs = collect();
        $secretaires = collect();

        try {
            // Ne récupérer les coordinateurs que si l'utilisateur n'est pas coordinateur lui-même
            if ($userRole !== 'coordinateur' && Role::where('name', 'coordinateur')->exists()) {
                $coordinateurs = User::role('coordinateur')
                    ->with(['roles'])
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Exception $e) {
            // Si le rôle n'existe pas, garder une collection vide
            $coordinateurs = collect();
        }
            
        $enseignants = ESBTPTeacher::with(['user'])
            ->whereHas('user', function($query) {
                $query->where('is_active', true);
            })
            ->orderBy('created_at', 'desc')
            ->get();
            
        try {
            // Ne récupérer les secrétaires que si l'utilisateur n'est pas secrétaire lui-même
            if ($userRole !== 'secretaire' && Role::where('name', 'secretaire')->exists()) {
                $secretaires = User::role('secretaire')
                    ->with(['roles'])
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Exception $e) {
            // Si le rôle n'existe pas, garder une collection vide
            $secretaires = collect();
        }

        // Récupérer les comptables
        $comptables = collect();
        try {
            if (Role::where('name', 'comptable')->exists()) {
                $comptables = User::role('comptable')
                    ->with(['roles'])
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Exception $e) {
            $comptables = collect();
        }

        // Récupérer les caissiers
        $caissiers = collect();
        try {
            if (Role::where('name', 'caissier')->exists()) {
                $caissiers = User::role('caissier')
                    ->with(['roles'])
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            }
        } catch (\Exception $e) {
            $caissiers = collect();
        }

        // Calculer les statistiques
        $stats = [
            'coordinateurs' => $coordinateurs->count(),
            'enseignants' => $enseignants->count(),
            'secretaires' => $secretaires->count(),
            'comptables' => $comptables->count(),
            'caissiers' => $caissiers->count(),
            'total' => $coordinateurs->count() + $enseignants->count() + $secretaires->count() + $comptables->count() + $caissiers->count(),
        ];

        // Rétro-compatibilité : $isCoordinateur est dérivé de $userRole
        $isCoordinateur = ($userRole === 'coordinateur');

        return view('esbtp.personnel.unified-index', compact(
            'coordinateurs',
            'enseignants',
            'secretaires',
            'comptables',
            'caissiers',
            'stats',
            'isCoordinateur',
            'userRole'
        ));
    }

    /**
     * Get personnel data via AJAX for dynamic loading
     */
    public function getData(Request $request)
    {
        try {
            $this->authorize('manage-users');
        } catch (\Exception $e) {
            if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                abort(403, 'Accès non autorisé');
            }
        }
        
        $type = $request->get('type'); // coordinateur, enseignant, secretaire
        $search = $request->get('search');
        $status = $request->get('status');
        $filter = $request->get('filter'); // spécialité, matière, service
        
        $data = [];
        
        switch($type) {
            case 'coordinateur':
                $data = collect();
                try {
                    if (Role::where('name', 'coordinateur')->exists()) {
                        $query = User::role('coordinateur')->with(['roles']);
                        
                        if ($status) {
                            $query->where('is_active', $status === 'active');
                        }
                        
                        if ($search) {
                            $query->where(function($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%")
                                  ->orWhere('telephone', 'like', "%{$search}%")
                                  ->orWhere('specialite', 'like', "%{$search}%");
                            });
                        }
                        
                        $data = $query->orderBy('name')->get();
                    }
                } catch (\Exception $e) {
                    $data = collect();
                }
                break;
                
            case 'enseignant':
                $query = ESBTPTeacher::with(['user']);
                
                if ($status) {
                    $query->where('status', $status);
                }
                
                if ($search) {
                    $query->whereHas('user', function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    })->orWhere('specialization', 'like', "%{$search}%");
                }
                
                $data = $query->orderBy('created_at', 'desc')->get();
                break;
                
            case 'secretaire':
                $data = collect();
                try {
                    if (Role::where('name', 'secretaire')->exists()) {
                        $query = User::role('secretaire')->with(['roles']);

                        if ($status) {
                            $query->where('is_active', $status === 'active');
                        }

                        if ($search) {
                            $query->where(function($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%")
                                  ->orWhere('telephone', 'like', "%{$search}%");
                            });
                        }

                        $data = $query->orderBy('name')->get();
                    }
                } catch (\Exception $e) {
                    $data = collect();
                }
                break;

            case 'comptable':
                $data = collect();
                try {
                    if (Role::where('name', 'comptable')->exists()) {
                        $query = User::role('comptable')->with(['roles']);

                        if ($status) {
                            $query->where('is_active', $status === 'active');
                        }

                        if ($search) {
                            $query->where(function($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%")
                                  ->orWhere('telephone', 'like', "%{$search}%")
                                  ->orWhere('department', 'like', "%{$search}%");
                            });
                        }

                        $data = $query->orderBy('name')->get();
                    }
                } catch (\Exception $e) {
                    $data = collect();
                }
                break;

            case 'caissier':
                $data = collect();
                try {
                    if (Role::where('name', 'caissier')->exists()) {
                        $query = User::role('caissier')->with(['roles']);

                        if ($status) {
                            $query->where('is_active', $status === 'active');
                        }

                        if ($search) {
                            $query->where(function($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%")
                                  ->orWhere('telephone', 'like', "%{$search}%");
                            });
                        }

                        $data = $query->orderBy('name')->get();
                    }
                } catch (\Exception $e) {
                    $data = collect();
                }
                break;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count()
        ]);
    }

    /**
     * Store a newly created personnel in storage.
     */
    public function store(Request $request)
    {
        try {
            $this->authorize('manage-users');
        } catch (\Exception $e) {
            if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                abort(403, 'Accès non autorisé');
            }
        }
        
        $type = $request->get('type'); // coordinateur, enseignant, secretaire
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'type' => 'required|in:coordinateur,enseignant,secretaire,comptable,caissier',
        ];

        // Règles spécifiques selon le type
        if ($type === 'coordinateur') {
            $rules['specialite'] = 'nullable|string|max:255';
        } elseif ($type === 'enseignant') {
            $rules['specialization'] = 'nullable|string|max:255';
            $rules['qualification'] = 'nullable|string|max:255';
        } elseif ($type === 'secretaire') {
            $rules['service'] = 'nullable|string|max:255';
        } elseif ($type === 'comptable') {
            $rules['department'] = 'nullable|string|max:255';
        }
        
        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'service' => $validated['service'] ?? null,
                'department' => $validated['department'] ?? null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assigner le rôle approprié
            $user->assignRole($validated['type']);
            
            // Si c'est un enseignant, créer aussi dans la table ESBTPTeacher
            if ($type === 'enseignant') {
                ESBTPTeacher::create([
                    'user_id' => $user->id,
                    'specialization' => $validated['specialization'] ?? null,
                    'qualification' => $validated['qualification'] ?? null,
                    'status' => 'active',
                ]);
            }

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
    public function update(Request $request, $type, $id)
    {
        try {
            $this->authorize('manage-users');
        } catch (\Exception $e) {
            if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                abort(403, 'Accès non autorisé');
            }
        }
        
        if ($type === 'enseignant') {
            $teacher = ESBTPTeacher::findOrFail($id);
            $user = $teacher->user;
        } else {
            $user = User::findOrFail($id);
        }
        
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
        ];
        
        // Règles spécifiques selon le type
        if ($type === 'coordinateur') {
            $rules['specialite'] = 'nullable|string|max:255';
        } elseif ($type === 'enseignant') {
            $rules['specialization'] = 'nullable|string|max:255';
            $rules['qualification'] = 'nullable|string|max:255';
        } elseif ($type === 'secretaire') {
            $rules['service'] = 'nullable|string|max:255';
        } elseif ($type === 'comptable') {
            $rules['department'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'is_active' => $validated['is_active'],
            ];

            // Champs spécifiques selon le type
            if ($type === 'coordinateur') {
                $updateData['specialite'] = $validated['specialite'] ?? null;
            } elseif ($type === 'secretaire') {
                $updateData['service'] = $validated['service'] ?? null;
            } elseif ($type === 'comptable') {
                $updateData['department'] = $validated['department'] ?? null;
            }

            // Si un nouveau mot de passe est fourni
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);
            
            // Mise à jour spécifique pour les enseignants
            if ($type === 'enseignant') {
                $teacher->update([
                    'specialization' => $validated['specialization'] ?? null,
                    'qualification' => $validated['qualification'] ?? null,
                    'status' => $validated['is_active'] ? 'active' : 'inactive',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Personnel mis à jour avec succès.',
                'data' => $user
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
    public function destroy($type, $id)
    {
        try {
            $this->authorize('manage-users');
        } catch (\Exception $e) {
            if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                abort(403, 'Accès non autorisé');
            }
        }
        
        if ($type === 'enseignant') {
            $teacher = ESBTPTeacher::findOrFail($id);
            $user = $teacher->user;
        } else {
            $user = User::findOrFail($id);
        }
        
        // Empêcher la suppression de son propre compte
        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Marquer comme inactif au lieu de supprimer complètement
            $user->update([
                'is_active' => false,
                'email' => $user->email . '_deleted_' . time(),
            ]);
            
            // Mise à jour spécifique pour les enseignants
            if ($type === 'enseignant') {
                $teacher->update(['status' => 'inactive']);
            }

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
    public function toggleStatus($type, $id)
    {
        try {
            $this->authorize('manage-users');
        } catch (\Exception $e) {
            if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                abort(403, 'Accès non autorisé');
            }
        }
        
        if ($type === 'enseignant') {
            $teacher = ESBTPTeacher::findOrFail($id);
            $user = $teacher->user;
        } else {
            $user = User::findOrFail($id);
        }
        
        $user->update([
            'is_active' => !$user->is_active
        ]);
        
        // Mise à jour spécifique pour les enseignants
        if ($type === 'enseignant') {
            $teacher->update([
                'status' => $user->is_active ? 'active' : 'inactive'
            ]);
        }

        $status = $user->is_active ? 'activé' : 'désactivé';
        
        return response()->json([
            'success' => true,
            'message' => "Personnel {$status} avec succès.",
            'is_active' => $user->is_active
        ]);
    }

    /**
     * Get personnel statistics
     */
    public function getStats()
    {
        try {
            $this->authorize('manage-users');
        } catch (\Exception $e) {
            if (!auth()->user()->hasAnyRole(['superAdmin', 'admin'])) {
                abort(403, 'Accès non autorisé');
            }
        }
        
        $stats = [
            'coordinateurs' => [
                'total' => User::role('coordinateur')->count(),
                'actifs' => User::role('coordinateur')->where('is_active', true)->count(),
                'inactifs' => User::role('coordinateur')->where('is_active', false)->count(),
                'nouveau_ce_mois' => User::role('coordinateur')->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'enseignants' => [
                'total' => ESBTPTeacher::count(),
                'actifs' => ESBTPTeacher::where('status', 'active')->count(),
                'inactifs' => ESBTPTeacher::where('status', 'inactive')->count(),
                'nouveau_ce_mois' => ESBTPTeacher::where('created_at', '>=', now()->startOfMonth())->count(),
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
}