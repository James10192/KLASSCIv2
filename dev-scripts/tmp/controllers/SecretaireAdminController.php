<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SecretaireAdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    /**
     * Afficher la liste des secrétaires.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $role = Role::where('name', 'secretaire')->first();
        
        if (!$role) {
            return redirect()->back()->with('error', 'Rôle secrétaire non trouvé.');
        }
        
        $secretaires = User::role('secretaire')->paginate(10);
        
        return view('esbtp.secretaires.index', compact('secretaires'));
    }

    /**
     * Afficher le formulaire de création d'un secrétaire.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('esbtp.secretaires.create');
    }

    /**
     * Enregistrer un nouveau secrétaire.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Créer l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);
            
            // Assigner le rôle secrétaire
            $role = Role::where('name', 'secretaire')->first();
            if (!$role) {
                // Créer le rôle s'il n'existe pas
                $role = Role::create(['name' => 'secretaire']);
                
                // Assigner les permissions requises
                $permissions = [
                    'filieres.view',
                    'formations.view',
                    'niveaux.view',
                    'classes.view',
                    'etudiants.create', 'etudiants.view',
                    'examens.view',
                    'matieres.view',
                    'notes.create', 'notes.view',
                    'bulletins.generate', 'bulletins.view',
                    'timetables.create', 'timetables.view',
                    'messages.send',
                    'attendances.create', 'attendances.view'
                ];
                
                foreach ($permissions as $permission) {
                    $role->givePermissionTo($permission);
                }
            }
            
            $user->assignRole($role);
            
            DB::commit();
            
            return redirect()
                ->route('secretaires.index')
                ->with('success', 'Compte secrétaire créé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la création du compte : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Afficher les détails d'un secrétaire.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $secretaire = User::findOrFail($id);
        
        if (!$secretaire->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Utilisateur non autorisé.');
        }
        
        return view('esbtp.secretaires.show', compact('secretaire'));
    }

    /**
     * Afficher le formulaire de modification d'un secrétaire.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $secretaire = User::findOrFail($id);
        
        if (!$secretaire->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Utilisateur non autorisé.');
        }
        
        return view('esbtp.secretaires.edit', compact('secretaire'));
    }

    /**
     * Mettre à jour un secrétaire.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $secretaire = User::findOrFail($id);
        
        if (!$secretaire->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Utilisateur non autorisé.');
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($secretaire->id),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($secretaire->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Mettre à jour les données de l'utilisateur
            $secretaire->name = $request->name;
            $secretaire->email = $request->email;
            $secretaire->username = $request->username;
            
            if ($request->filled('password')) {
                $secretaire->password = Hash::make($request->password);
            }
            
            // Vérifier si le statut est défini dans la requête
            if ($request->has('is_active')) {
                $secretaire->is_active = $request->is_active;
            }
            
            $secretaire->save();
            
            DB::commit();
            
            return redirect()
                ->route('secretaires.show', $secretaire->id)
                ->with('success', 'Compte secrétaire mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour du compte : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprimer un secrétaire.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $secretaire = User::findOrFail($id);
        
        if (!$secretaire->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Utilisateur non autorisé.');
        }
        
        try {
            DB::beginTransaction();
            
            // Marquer l'utilisateur comme inactif plutôt que de le supprimer réellement
            $secretaire->is_active = false;
            $secretaire->save();
            
            // Alternative : supprimer réellement l'utilisateur
            // $secretaire->delete();
            
            DB::commit();
            
            return redirect()
                ->route('secretaires.index')
                ->with('success', 'Compte secrétaire désactivé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la désactivation du compte : ' . $e->getMessage());
        }
    }
    
    /**
     * Activer/désactiver un secrétaire.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus($id)
    {
        $secretaire = User::findOrFail($id);
        
        if (!$secretaire->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Utilisateur non autorisé.');
        }
        
        try {
            $secretaire->is_active = !$secretaire->is_active;
            $secretaire->save();
            
            $status = $secretaire->is_active ? 'activé' : 'désactivé';
            
            return redirect()
                ->back()
                ->with('success', "Compte secrétaire $status avec succès.");
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la modification du statut : ' . $e->getMessage());
        }
    }
    
    /**
     * Réinitialiser le mot de passe d'un secrétaire.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function resetPassword($id)
    {
        $secretaire = User::findOrFail($id);
        
        if (!$secretaire->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Utilisateur non autorisé.');
        }
        
        try {
            // Générer un nouveau mot de passe aléatoire
            $newPassword = Str::random(8);
            
            // Mettre à jour le mot de passe
            $secretaire->password = Hash::make($newPassword);
            $secretaire->save();
            
            return redirect()
                ->back()
                ->with('success', "Mot de passe réinitialisé avec succès. Nouveau mot de passe : $newPassword");
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la réinitialisation du mot de passe : ' . $e->getMessage());
        }
    }
} 