<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class ESBTPSecretaireController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superAdmin');
    }

    /**
     * Affiche la liste des secrétaires
     */
    public function index()
    {
        $secretaires = User::role('secretaire')->orderBy('name')->paginate(10);
        return view('esbtp.secretaires.index', compact('secretaires'));
    }

    /**
     * Affiche le formulaire de création d'un secrétaire
     */
    public function create()
    {
        return view('esbtp.secretaires.create');
    }

    /**
     * Enregistre un nouveau secrétaire
     */
    public function store(Request $request)
    {
        // Capturer les données validées pour éviter la vulnérabilité mass assignment
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Récupérer uniquement les données validées
        $validated = $validator->validated();

        // Créer l'utilisateur avec les données validées uniquement
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'telephone' => $validated['telephone'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'is_active' => true,
        ]);

        // Assigner le rôle secrétaire
        $role = Role::firstOrCreate(['name' => 'secretaire']);
        $user->assignRole($role);

        return redirect()->route('secretaires.index')
            ->with('success', 'Secrétaire créé avec succès');
    }

    /**
     * Affiche les détails d'un secrétaire
     */
    public function show($id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        return view('esbtp.secretaires.show', compact('secretaire'));
    }

    /**
     * Affiche le formulaire d'édition d'un secrétaire
     */
    public function edit($id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        return view('esbtp.secretaires.edit', compact('secretaire'));
    }

    /**
     * Met à jour un secrétaire
     */
    public function update(Request $request, $id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);

        // Capturer les données validées pour éviter la vulnérabilité mass assignment
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'password' => 'nullable|string|min:8',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Récupérer uniquement les données validées
        $validated = $validator->validated();

        // Mettre à jour l'utilisateur avec les données validées uniquement
        $secretaire->name = $validated['name'];
        $secretaire->email = $validated['email'];
        $secretaire->username = $validated['username'];
        if (isset($validated['password']) && !empty($validated['password'])) {
            $secretaire->password = Hash::make($validated['password']);
        }
        $secretaire->telephone = $validated['telephone'] ?? null;
        $secretaire->adresse = $validated['adresse'] ?? null;
        $secretaire->save();

        return redirect()->route('secretaires.index')
            ->with('success', 'Secrétaire mis à jour avec succès');
    }

    /**
     * Supprime un secrétaire
     */
    public function destroy($id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        $secretaire->delete();

        return redirect()->route('secretaires.index')
            ->with('success', 'Secrétaire supprimé avec succès');
    }

    /**
     * Toggle secretaire status (active/inactive).
     */
    public function toggleStatus(Request $request, $id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        $newStatus = $secretaire->is_active ? 0 : 1;

        $secretaire->update([
            'is_active' => $newStatus,
        ]);

        // Si c'est une requête AJAX, retourner du JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'new_status' => $newStatus ? 'active' : 'inactive'
            ]);
        }

        return redirect()->back()->with('success', 'Statut mis à jour avec succès');
    }

    /**
     * Reset password for a secretaire.
     */
    public function resetPassword(User $secretaire)
    {
        $this->authorize('manage-users');

        if (!$secretaire->hasRole('secretaire')) {
            abort(404, 'Secrétaire non trouvé.');
        }

        try {
            $defaultPassword = 'Bonjour@2025';

            $secretaire->password = Hash::make($defaultPassword);
            $secretaire->must_change_password = true;
            $secretaire->save();

            \Log::info('🔑 Password reset for secretaire to default', [
                'secretaire_id' => $secretaire->id,
                'secretaire_name' => $secretaire->name,
                'reset_by' => auth()->user()->name,
                'timestamp' => now(),
                'must_change_password' => true
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'password' => $defaultPassword,
                    'message' => 'Mot de passe réinitialisé avec succès!'
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Mot de passe réinitialisé à Bonjour@2025 avec succès! Le secrétaire devra changer son mot de passe à la première connexion.')
                ->with('new_password', $defaultPassword);
        } catch (\Exception $e) {
            \Log::error('❌ Password reset failed for secretaire', [
                'secretaire_id' => $secretaire->id,
                'error' => $e->getMessage()
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la réinitialisation du mot de passe: ' . $e->getMessage());
        }
    }
}
