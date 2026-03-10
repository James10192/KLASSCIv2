<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ESBTPComptableController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superAdmin']);
    }

    public function index()
    {
        $comptables = User::role('comptable')->orderBy('name')->get();
        return view('esbtp.comptables.index', compact('comptables'));
    }

    public function create()
    {
        return view('esbtp.comptables.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'telephone'  => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
            'password'   => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'telephone'  => $validated['telephone'] ?? null,
                'department' => $validated['department'] ?? null,
                'password'   => Hash::make($validated['password']),
                'is_active'  => true,
            ]);

            $user->assignRole('comptable');

            DB::commit();

            return redirect()
                ->route('esbtp.comptables.show', $user)
                ->with('success', "Comptable {$user->name} créé avec succès.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function show(User $user)
    {
        abort_unless($user->hasRole('comptable'), 404);
        return view('esbtp.comptables.show', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasRole('comptable'), 404);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'telephone'  => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Informations mises à jour.',
                'user'    => $user->fresh(),
            ]);
        }

        return redirect()
            ->route('esbtp.comptables.show', $user)
            ->with('success', 'Informations mises à jour.');
    }

    public function toggleStatus(User $user)
    {
        abort_unless($user->hasRole('comptable'), 404);

        $user->update(['is_active' => !$user->is_active]);

        $label = $user->is_active ? 'activé' : 'désactivé';

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Comptable {$label}.",
                'is_active' => $user->is_active,
            ]);
        }

        return redirect()->back()->with('success', "Comptable {$label}.");
    }
}
