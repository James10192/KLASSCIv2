<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ESBTPCoordinateurController extends Controller
{
    /**
     * Display a listing of the coordinators.
     */
    public function index()
    {
        // Vérifier les permissions
        $this->authorize('manage-users');
        
        $coordinateurs = User::role('coordinateur')
            ->with(['roles'])
            ->orderBy('name')
            ->paginate(10);

        return view('esbtp.coordinateurs.index', compact('coordinateurs'));
    }

    /**
     * Show the form for creating a new coordinator.
     */
    public function create()
    {
        $this->authorize('manage-users');
        
        return view('esbtp.coordinateurs.create');
    }

    /**
     * Store a newly created coordinator in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('manage-users');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date|before:today',
            'adresse' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assigner le rôle coordinateur
            $user->assignRole('coordinateur');

            DB::commit();

            return redirect()->route('esbtp.coordinateurs.index')
                           ->with('success', 'Coordinateur créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la création du coordinateur: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Display the specified coordinator.
     */
    public function show(User $coordinateur)
    {
        $this->authorize('manage-users');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        // Statistiques du coordinateur
        $statistiques = $this->getCoordinateurStatistiques($coordinateur->id);

        return view('esbtp.coordinateurs.show', compact('coordinateur', 'statistiques'));
    }

    /**
     * Show the form for editing the specified coordinator.
     */
    public function edit(User $coordinateur)
    {
        $this->authorize('manage-users');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        return view('esbtp.coordinateurs.edit', compact('coordinateur'));
    }

    /**
     * Update the specified coordinator in storage.
     */
    public function update(Request $request, User $coordinateur)
    {
        $this->authorize('manage-users');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $coordinateur->id,
            'password' => 'nullable|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
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
                'date_naissance' => $validated['date_naissance'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'is_active' => $validated['is_active'],
            ];

            // Si un nouveau mot de passe est fourni
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $coordinateur->update($updateData);

            DB::commit();

            return redirect()->route('esbtp.coordinateurs.index')
                           ->with('success', 'Coordinateur mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage())
                        ->withInput();
        }
    }

    /**
     * Remove the specified coordinator from storage.
     */
    public function destroy(User $coordinateur)
    {
        $this->authorize('manage-users');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        // Empêcher la suppression de son propre compte
        if ($coordinateur->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            DB::beginTransaction();

            // Retirer le rôle coordinateur
            $coordinateur->removeRole('coordinateur');
            
            // Marquer comme inactif au lieu de supprimer complètement
            $coordinateur->update([
                'is_active' => false,
                'email' => $coordinateur->email . '_deleted_' . time(),
            ]);

            DB::commit();

            return redirect()->route('esbtp.coordinateurs.index')
                           ->with('success', 'Coordinateur supprimé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Calculer les statistiques d'un coordinateur
     */
    private function getCoordinateurStatistiques($coordinateurId)
    {
        // Ici on peut ajouter des statistiques spécifiques au coordinateur
        // Pour l'instant, on retourne des statistiques de base
        
        return [
            'date_creation' => User::find($coordinateurId)->created_at,
            'derniere_connexion' => User::find($coordinateurId)->last_login_at ?? 'Jamais',
            'statut' => User::find($coordinateurId)->is_active ? 'Actif' : 'Inactif',
            'nb_classes_gerees' => 0, // À implémenter selon la logique métier
            'nb_enseignants_supervises' => 0, // À implémenter selon la logique métier
        ];
    }

    /**
     * Toggle active status of coordinator
     */
    public function toggleStatus(User $coordinateur)
    {
        $this->authorize('manage-users');
        
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        $coordinateur->update([
            'is_active' => !$coordinateur->is_active
        ]);

        $status = $coordinateur->is_active ? 'activé' : 'désactivé';
        
        return back()->with('success', "Coordinateur {$status} avec succès.");
    }
}