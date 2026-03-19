<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ESBTPLMDNoteController extends Controller
{
    /**
     * Liste des classes LMD avec leurs UEs/ECUEs pour la saisie de notes.
     */
    public function index(Request $request)
    {
        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->where('is_active', true)
            ->withCount('inscriptions')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20);

        // Charger les evaluations recentes pour les classes LMD
        $evaluationsRecentes = ESBTPEvaluation::whereHas('classe', fn($q) => $q->where('systeme_academique', 'LMD'))
            ->with(['classe', 'matiere.uniteEnseignement'])
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->orderByDesc('date_evaluation')
            ->limit(20)
            ->get();

        return view('esbtp.lmd.notes.index', compact('classes', 'evaluationsRecentes'));
    }

    /**
     * Saisie rapide de notes pour une evaluation (meme pattern que BTS).
     */
    public function saisieRapide(ESBTPEvaluation $evaluation)
    {
        $evaluation->load(['classe.inscriptions.etudiant', 'matiere.uniteEnseignement']);

        $etudiants = $evaluation->classe->inscriptions
            ->where('status', 'active')
            ->map(fn($i) => $i->etudiant)
            ->filter()
            ->sortBy('nom');

        // Charger les notes existantes (1 seule requete)
        $existingNotes = ESBTPNote::where('evaluation_id', $evaluation->id)
            ->get(['etudiant_id', 'note', 'is_absent']);
        $notesExistantes = $existingNotes->pluck('note', 'etudiant_id')->toArray();
        $absencesExistantes = $existingNotes->pluck('is_absent', 'etudiant_id')->toArray();

        return view('esbtp.lmd.notes.saisie-rapide', compact(
            'evaluation', 'etudiants', 'notesExistantes', 'absencesExistantes'
        ));
    }

    /**
     * Enregistrer les notes en masse.
     */
    public function saveBulk(Request $request)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:esbtp_evaluations,id',
            'notes' => 'required|array',
            'notes.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'notes.*.note' => 'nullable|numeric|min:0',
            'notes.*.is_absent' => 'nullable|boolean',
        ]);

        $evaluation = ESBTPEvaluation::findOrFail($request->evaluation_id);

        DB::transaction(function () use ($request, $evaluation) {
            $rows = collect($request->notes)
                ->filter(fn($n) => ($n['note'] ?? null) !== null || !empty($n['is_absent']))
                ->map(fn($n) => [
                    'evaluation_id' => $evaluation->id,
                    'etudiant_id'   => $n['etudiant_id'],
                    'matiere_id'    => $evaluation->matiere_id,
                    'classe_id'     => $evaluation->classe_id,
                    'note'          => ($n['is_absent'] ?? false) ? 0 : ($n['note'] ?? 0),
                    'is_absent'     => $n['is_absent'] ?? false,
                    'semestre'      => $evaluation->periode,
                    'commentaire'   => $n['commentaire'] ?? null,
                    'created_by'    => auth()->id(),
                    'updated_by'    => auth()->id(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ])->values()->toArray();

            if (!empty($rows)) {
                ESBTPNote::upsert(
                    $rows,
                    ['evaluation_id', 'etudiant_id'],
                    ['note', 'is_absent', 'semestre', 'commentaire', 'updated_by', 'updated_at']
                );
            }
        });

        return redirect()->route('esbtp.lmd.notes.index')
            ->with('success', 'Notes enregistrées avec succès pour ' . count($request->notes) . ' étudiants.');
    }
}
