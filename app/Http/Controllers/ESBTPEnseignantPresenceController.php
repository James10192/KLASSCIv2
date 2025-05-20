<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEnseignantPresence;
use App\Models\User;
use App\Models\ESBTPMatiere;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ESBTPEnseignantPresenceController extends Controller
{
    public function index()
    {
        $date = request('date', now()->toDateString());
        $presences = ESBTPEnseignantPresence::with(['enseignant', 'matiere'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $enseignants = User::role('enseignant')->get();
        $matieres = ESBTPMatiere::all();

        return view('esbtp.admin.presence.index', compact('presences', 'date', 'enseignants', 'matieres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'enseignant_id' => 'required|exists:users,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'statut' => 'required|in:present,absent,retard',
            'remarques' => 'nullable|string',
        ]);

        $presence = ESBTPEnseignantPresence::create([
            'enseignant_id' => $validated['enseignant_id'],
            'matiere_id' => $validated['matiere_id'],
            'date' => now()->toDateString(),
            'heure_arrivee' => now()->toTimeString(),
            'statut' => $validated['statut'],
            'remarques' => $validated['remarques'],
            'adresse_ip' => $request->ip(),
            'info_appareil' => $request->userAgent()
        ]);

        return redirect()->back()->with('success', 'Présence enregistrée avec succès');
    }

    public function rapport()
    {
        $dateDebut = request('date_debut', now()->startOfMonth()->toDateString());
        $dateFin = request('date_fin', now()->endOfMonth()->toDateString());

        $presences = ESBTPEnseignantPresence::with(['enseignant', 'matiere'])
            ->whereBetween('date', [$dateDebut, $dateFin])
            ->orderBy('date', 'desc')
            ->get();

        return view('esbtp.admin.presence.rapport', compact('presences', 'dateDebut', 'dateFin'));
    }

    public function update(Request $request, ESBTPEnseignantPresence $presence)
    {
        $validated = $request->validate([
            'statut' => 'required|in:present,absent,retard',
            'remarques' => 'nullable|string',
        ]);

        $presence->update($validated);

        return redirect()->back()->with('success', 'Présence mise à jour avec succès');
    }
}
