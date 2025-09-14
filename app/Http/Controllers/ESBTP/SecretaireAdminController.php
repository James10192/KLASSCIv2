<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SecretaireAdminController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Récupérer le rôle "secrétaire"
        $roleSecretaire = Role::where('name', 'secretaire')->first();

        if (!$roleSecretaire) {
            return redirect()->back()->with('error', 'Le rôle de secrétaire n\'existe pas dans le système.');
        }

        // Récupérer tous les utilisateurs avec le rôle "secrétaire"
        $secretaires = User::role('secretaire')->latest()->paginate(10);

        return view('esbtp.secretaires.index', compact('secretaires'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('esbtp.secretaires.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Créer l'utilisateur
            $secretaire = new User();
            $secretaire->first_name = $validated['first_name'];
            $secretaire->last_name = $validated['last_name'];
            $secretaire->name = $validated['first_name'] . ' ' . $validated['last_name'];
            $secretaire->email = $validated['email'];
            $secretaire->username = $validated['username'];
            $secretaire->password = Hash::make($validated['password']);
            $secretaire->phone = $validated['phone'] ?? null;
            $secretaire->address = $validated['address'] ?? null;
            $secretaire->city = $validated['city'] ?? null;
            $secretaire->is_active = $validated['is_active'];

            // Traitement de la photo de profil
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('profile-photos', 'public');
                $secretaire->profile_photo_path = $path;
            }

            $secretaire->save();

            // Assigner le rôle de secrétaire
            $secretaire->assignRole('secretaire');

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Le compte secrétaire a été créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création du compte : ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);

        // Vous pouvez ajouter ici la récupération des activités récentes si vous avez un système de journalisation
        // $activites = Activity::where('causer_id', $id)->latest()->take(10)->get();

        return view('esbtp.secretaires.show', compact('secretaire'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);

        return view('esbtp.secretaires.edit', compact('secretaire'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);

        // Validation des données
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($secretaire->id)],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($secretaire->id)],
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Mettre à jour les informations de l'utilisateur
            $secretaire->first_name = $validated['first_name'];
            $secretaire->last_name = $validated['last_name'];
            $secretaire->name = $validated['first_name'] . ' ' . $validated['last_name'];
            $secretaire->email = $validated['email'];
            $secretaire->username = $validated['username'];

            // Mettre à jour le mot de passe uniquement s'il est fourni
            if (!empty($validated['password'])) {
                $secretaire->password = Hash::make($validated['password']);
            }

            $secretaire->phone = $validated['phone'] ?? null;
            $secretaire->address = $validated['address'] ?? null;
            $secretaire->city = $validated['city'] ?? null;
            $secretaire->is_active = $validated['is_active'];

            // Traitement de la photo de profil
            if ($request->hasFile('profile_photo')) {
                // Supprimer l'ancienne photo si elle existe
                if ($secretaire->profile_photo_path) {
                    Storage::disk('public')->delete($secretaire->profile_photo_path);
                }

                $path = $request->file('profile_photo')->store('profile-photos', 'public');
                $secretaire->profile_photo_path = $path;
            }

            $secretaire->save();

            DB::commit();

            return redirect()->route('esbtp.secretaires.show', $secretaire->id)
                ->with('success', 'Les informations du secrétaire ont été mises à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du compte : ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $secretaire = User::role('secretaire')->findOrFail($id);

            // Supprimer la photo de profil si elle existe
            if ($secretaire->profile_photo_path) {
                Storage::disk('public')->delete($secretaire->profile_photo_path);
            }

            // Supprimer l'utilisateur
            $secretaire->delete();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Le compte secrétaire a été supprimé avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la suppression du compte : ' . $e->getMessage());
        }
    }
}
