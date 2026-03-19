<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPLMDBulletin;
use App\Services\LMDBulletinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ESBTPLMDBulletinController extends Controller
{
    protected LMDBulletinService $service;

    public function __construct(LMDBulletinService $service)
    {
        $this->service = $service;
    }

    /**
     * Liste des bulletins LMD avec filtres.
     */
    public function index(Request $request)
    {
        $query = ESBTPLMDBulletin::with(['etudiant', 'classe', 'anneeUniversitaire']);

        if ($request->filled('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        if ($request->filled('annee_universitaire_id')) {
            $query->where('annee_universitaire_id', $request->annee_universitaire_id);
        }

        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('etudiant', function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%");
            });
        }

        $bulletins = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->orderBy('name')
            ->get();

        $annees = ESBTPAnneeUniversitaire::orderByDesc('annee_debut')->get();

        return view('esbtp.lmd.bulletins.index', compact('bulletins', 'classes', 'annees'));
    }

    /**
     * Formulaire de selection classe + semestre pour generation.
     */
    public function select()
    {
        $classes = ESBTPClasse::where('systeme_academique', 'LMD')
            ->orderBy('name')
            ->get();

        $annees = ESBTPAnneeUniversitaire::orderByDesc('annee_debut')->get();

        return view('esbtp.lmd.bulletins.select', compact('classes', 'annees'));
    }

    /**
     * Generer le bulletin pour un etudiant individuel.
     */
    public function generer(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
        ]);

        $bulletin = $this->service->genererBulletinLMD(
            $request->etudiant_id,
            $request->classe_id,
            $request->annee_universitaire_id,
            $request->semestre
        );

        return redirect()
            ->route('esbtp.lmd.bulletins.show', $bulletin)
            ->with('success', 'Bulletin LMD généré avec succès.');
    }

    /**
     * Generer les bulletins pour toute une classe.
     */
    public function genererClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|integer|min:1|max:10',
        ]);

        $bulletins = $this->service->genererBulletinsClasse(
            $request->classe_id,
            $request->annee_universitaire_id,
            $request->semestre
        );

        $count = count($bulletins);

        return redirect()
            ->route('esbtp.lmd.bulletins.index', [
                'classe_id' => $request->classe_id,
                'annee_universitaire_id' => $request->annee_universitaire_id,
                'semestre' => $request->semestre,
            ])
            ->with('success', "{$count} bulletin(s) LMD généré(s) avec succès.");
    }

    /**
     * Apercu du bulletin (preview HTML).
     */
    public function show(ESBTPLMDBulletin $bulletin)
    {
        $data = $this->service->preparerDonneesBulletin($bulletin);

        return view('esbtp.lmd.bulletins.preview', $data);
    }

    /**
     * Telecharger le bulletin en PDF.
     */
    public function pdf(ESBTPLMDBulletin $bulletin)
    {
        $data = $this->service->preparerDonneesBulletin($bulletin);

        $pdf = Pdf::loadView('esbtp.lmd.bulletins.pdf', $data)
            ->setPaper('a4', 'portrait');

        $matricule = $bulletin->etudiant->matricule ?? 'unknown';
        $semestre = $bulletin->semestre;
        $filename = "bulletin_lmd_{$matricule}_S{$semestre}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Basculer la publication du bulletin.
     */
    public function togglePublication(ESBTPLMDBulletin $bulletin)
    {
        $bulletin->update([
            'is_published' => !$bulletin->is_published,
        ]);

        $status = $bulletin->is_published ? 'publié' : 'dépublié';

        return redirect()->back()->with('success', "Bulletin {$status} avec succès.");
    }

    /**
     * Supprimer un bulletin (soft delete) avec ses resultats.
     */
    public function destroy(ESBTPLMDBulletin $bulletin)
    {
        // Supprimer les resultats ECUEs lies
        $bulletin->resultatsECUEs()->delete();

        // Supprimer les resultats UEs lies
        $bulletin->resultatsUEs()->delete();

        // Supprimer le bulletin
        $bulletin->delete();

        return redirect()
            ->route('esbtp.lmd.bulletins.index')
            ->with('success', 'Bulletin LMD supprimé avec succès.');
    }
}
