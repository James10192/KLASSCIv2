<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superAdmin|secretaire|coordinateur');
    }

    /**
     * Affiche le profil de l'administrateur ou du secrétaire.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        return view('admin.profile', compact('user'));
    }

    /**
     * Met à jour le profil de l'administrateur ou du secrétaire.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        \Log::info('Début de la mise à jour du profil admin', [
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('profile_photo'),
            'all_data' => $request->all()
        ]);

        try {
            $user = auth()->user();

            // Validation des données
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            ]);

            \Log::info('Validation passée avec succès');

            // Mise à jour du nom et de l'email
            $user->name = $request->name;
            $user->email = $request->email;

            // Traitement de la photo de profil
            if ($request->hasFile('profile_photo')) {
                \Log::info('Photo de profil détectée', [
                    'original_name' => $request->file('profile_photo')->getClientOriginalName(),
                    'mime_type' => $request->file('profile_photo')->getMimeType(),
                    'size' => $request->file('profile_photo')->getSize()
                ]);

                // Supprimer l'ancienne photo si elle existe
                if ($user->profile_photo_path) {
                    \Log::info('Suppression de l\'ancienne photo', [
                        'old_path' => $user->profile_photo_path
                    ]);
                    Storage::disk('public')->delete($user->profile_photo_path);
                }

                // Stocker la nouvelle photo
                $path = $request->file('profile_photo')->store('profile-photos', 'public');
                \Log::info('Nouvelle photo stockée', [
                    'new_path' => $path,
                    'full_url' => Storage::disk('public')->url($path)
                ]);

                $user->profile_photo_path = $path;
            }

            $user->save();
            \Log::info('Profil mis à jour avec succès', [
                'user_id' => $user->id,
                'profile_photo_path' => $user->profile_photo_path
            ]);

            // Pour les requêtes AJAX, renvoyer une réponse JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profil mis à jour avec succès',
                    'profile_photo_url' => $user->profile_photo_path ? Storage::url($user->profile_photo_path) : null
                ]);
            }

            return redirect()->back()->with('success', 'Profil mis à jour avec succès');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du profil', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Pour les requêtes AJAX, renvoyer une erreur JSON
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la mise à jour du profil'
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la mise à jour du profil');
        }
    }

    /**
     * Met à jour les informations professionnelles de l'administrateur.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfessionalInfo(Request $request)
    {
        $request->validate([
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'office_location' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'appointment_date' => 'nullable|date',
        ]);

        $user = Auth::user();
        $user->position = $request->position;
        $user->department = $request->department;
        $user->office_location = $request->office_location;
        $user->employee_id = $request->employee_id;
        $user->appointment_date = $request->appointment_date;

        $user->save();

        return redirect()->route('admin.profile')->with('success', 'Informations professionnelles mises à jour avec succès');
    }

    /**
     * Met à jour le mot de passe de l'administrateur ou du secrétaire.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Vérifier si le mot de passe actuel est correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('admin.profile')->with('success', 'Mot de passe mis à jour avec succès');
    }
}
