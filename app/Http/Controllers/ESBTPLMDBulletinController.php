<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
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

        // Vérifier que le semestre correspond au niveau de la classe
        $classe = ESBTPClasse::findOrFail($request->classe_id);
        $semestresAutorises = $classe->getSemestresLMD();
        if (!in_array((int) $request->semestre, $semestresAutorises)) {
            return redirect()->back()->with('error',
                "Le semestre S{$request->semestre} ne correspond pas au niveau {$classe->niveau->name}. Semestres autorisés : S" . implode(', S', $semestresAutorises) . "."
            );
        }

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

        // Vérifier que le semestre correspond au niveau de la classe
        $classe = ESBTPClasse::findOrFail($request->classe_id);
        $semestresAutorises = $classe->getSemestresLMD();
        if (!in_array((int) $request->semestre, $semestresAutorises)) {
            return redirect()->back()->with('error',
                "Le semestre S{$request->semestre} ne correspond pas au niveau {$classe->niveau->name}. Semestres autorisés : S" . implode(', S', $semestresAutorises) . "."
            );
        }

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

        // Etablissement depuis SettingsHelper (methode centralisee)
        $schoolInfo = SettingsHelper::getSchoolInfo();
        $data['etablissement'] = [
            'nom' => $schoolInfo['name'],
            'adresse' => $schoolInfo['address'],
            'telephone' => $schoolInfo['phone'],
            'email' => $schoolInfo['email'],
            'logo' => $schoolInfo['logo'],
            'ville' => $schoolInfo['city'] ?: 'Abidjan',
            'directeur' => $schoolInfo['director_name'],
        ];

        // Couleurs PDF depuis SettingsHelper
        $data['pdfCfg'] = SettingsHelper::getPdfSettings();

        // Settings bulletin LMD (clés propres lmd_bulletin_*)
        $data['bulletinCfg'] = [
            'show_republic_info' => SettingsHelper::get('lmd_bulletin_show_republic_info', '1') == '1',
            'show_ministry_info' => SettingsHelper::get('lmd_bulletin_show_ministry_info', '1') == '1',
            'republic_text' => SettingsHelper::get('lmd_bulletin_republic_text', 'REPUBLIQUE DE COTE D\'IVOIRE'),
            'union_text' => SettingsHelper::get('lmd_bulletin_union_text', 'Union - Discipline - Travail'),
            'ministry_text' => SettingsHelper::get('lmd_bulletin_ministry_text', 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE'),
            'show_etablissement_box' => SettingsHelper::get('lmd_bulletin_show_etablissement_box', '1') == '1',
            'code_etablissement' => SettingsHelper::get('lmd_bulletin_code_etablissement', ''),
            'statut' => SettingsHelper::get('lmd_bulletin_statut', 'Privé'),
            'direction' => SettingsHelper::get('lmd_bulletin_direction', ''),
            'notice_text' => SettingsHelper::get('lmd_bulletin_notice_text', 'Pour les UE non acquises il vous sera délivré une attestation de réussite après validation de celles-ci. Un ECUE n\'est ni transférable ni capitalisable.'),
            'bottom_text' => SettingsHelper::get('lmd_bulletin_bottom_text', 'Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.'),
        ];

        // Logo base64 pour DomPDF
        $data['logoBase64'] = $this->prepareLogoBase64($data['etablissement']['logo']);

        $pdf = Pdf::loadView('esbtp.lmd.bulletins.pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
            ]);

        $matricule = $bulletin->etudiant->matricule ?? 'unknown';
        $semestre = $bulletin->semestre;
        $filename = "bulletin_lmd_{$matricule}_S{$semestre}.pdf";

        return $pdf->stream($filename);
    }

    private function prepareLogoBase64(?string $logoPath): ?string
    {
        $paths = [
            $logoPath ? storage_path('app/public/' . $logoPath) : null,
            public_path('images/esbtp_logo.png'),
            public_path('images/logo.png'),
        ];

        foreach (array_filter($paths) as $path) {
            if (file_exists($path)) {
                $mime = mime_content_type($path);
                $data = base64_encode(file_get_contents($path));
                return "data:{$mime};base64,{$data}";
            }
        }
        return null;
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
