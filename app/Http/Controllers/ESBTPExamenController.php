<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ESBTPExamen;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use Illuminate\Support\Facades\Auth;

class ESBTPExamenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $examens = ESBTPExamen::with(['classe', 'matiere'])->orderBy('date', 'desc')->paginate(10);
        return view('examens.index', compact('examens'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $classes = ESBTPClasse::all();
        $matieres = collect();
        return view('examens.create', compact('classes', 'matieres'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:e_s_b_t_p_classes,id',
            'matiere_id' => 'required|exists:e_s_b_t_p_matieres,id',
            'type' => 'required|string',
            'date' => 'required|date',
            'heure_debut' => 'required',
            'heure_fin' => 'required',
            'description' => 'nullable|string',
        ]);

        ESBTPExamen::create($validated);

        return redirect()->route('examens.index')
            ->with('success', 'Examen créé avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $examen = ESBTPExamen::with(['classe', 'matiere'])->findOrFail($id);
        return view('examens.show', compact('examen'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $examen = ESBTPExamen::findOrFail($id);
        $classes = ESBTPClasse::all();
        $matieres = collect(); // À adapter selon votre logique d'affichage des matières
        return view('examens.edit', compact('examen', 'classes', 'matieres'));
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
        $validated = $request->validate([
            'classe_id' => 'required|exists:e_s_b_t_p_classes,id',
            'matiere_id' => 'required|exists:e_s_b_t_p_matieres,id',
            'type' => 'required|string',
            'date' => 'required|date',
            'heure_debut' => 'required',
            'heure_fin' => 'required',
            'description' => 'nullable|string',
        ]);

        $examen = ESBTPExamen::findOrFail($id);
        $examen->update($validated);

        return redirect()->route('examens.index')
            ->with('success', 'Examen mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $examen = ESBTPExamen::findOrFail($id);
        $examen->delete();

        return redirect()->route('examens.index')
            ->with('success', 'Examen supprimé avec succès.');
    }

    /**
     * Affiche les examens de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function studentExams()
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        $examens = ESBTPExamen::where('classe_id', $etudiant->classe_id)
            ->where('date', '>=', now()->subDays(1))
            ->orderBy('date', 'asc')
            ->get();

        $examensTermines = ESBTPExamen::where('classe_id', $etudiant->classe_id)
            ->where('date', '<', now()->subDays(1))
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('etudiants.examens', compact('examens', 'examensTermines', 'etudiant'));
    }
}
