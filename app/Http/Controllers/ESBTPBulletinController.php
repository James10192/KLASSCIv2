<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ESBTPParent;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPResultat;
use App\Models\User;
use App\Models\Classe;
use PDF;
use App\Models\ESBTPFiliere;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use App\Models\ESBTPConfigMatiere;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\ESBTPCategorie;
use App\Models\ESBTPCertificat;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPCycle;
use Carbon\Carbon;
use App\Services\ESBTP\ESBTPAbsenceService;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Collection;

class ESBTPBulletinController extends Controller
{
    protected $absenceService;
    protected $bulletinService;

    public function __construct(ESBTPAbsenceService $absenceService, \App\Services\BulletinService $bulletinService)
    {
        $this->absenceService = $absenceService;
        $this->bulletinService = $bulletinService;
    }

    /**
     * Récupère les configurations depuis les settings pour les PDF
     */
    private function getPDFConfig()
    {
        return [
            // Informations de l'établissement
            'school_name' => SettingsHelper::get('school_name', 'École Spéciale du Bâtiment et des Travaux Publics'),
            'school_address' => SettingsHelper::get('school_address', ''),
            'school_phone' => SettingsHelper::get('school_phone', ''),
            'school_email' => SettingsHelper::get('school_email', ''),
            'school_website' => SettingsHelper::get('school_website', ''),
            'school_country' => SettingsHelper::get('school_country', 'Côte d\'Ivoire'),
            'director_name' => SettingsHelper::get('director_name', ''),
            'director_title' => SettingsHelper::get('director_title', 'Directeur'),

            // Configuration PDF
            'pdf_margin_top' => SettingsHelper::get('pdf.margin_top', 15),
            'pdf_margin_bottom' => SettingsHelper::get('pdf.margin_bottom', 15),
            'pdf_margin_left' => SettingsHelper::get('pdf.margin_left', 10),
            'pdf_margin_right' => SettingsHelper::get('pdf.margin_right', 10),
            'pdf_font_size' => SettingsHelper::get('pdf.font_size', 12),
            'pdf_header_font_size' => SettingsHelper::get('pdf.header_font_size', 14),
            'pdf_title_font_size' => SettingsHelper::get('pdf.title_font_size', 16),
            'pdf_show_watermark' => SettingsHelper::get('pdf.show_watermark', false),
            'pdf_watermark_text' => SettingsHelper::get('pdf.watermark_text', 'CONFIDENTIEL'),
            'pdf_show_signature' => SettingsHelper::get('pdf.show_signature', true),
            'pdf_header_text' => SettingsHelper::get('pdf.header_text', ''),
            'pdf_footer_text' => SettingsHelper::get('pdf.footer_text', ''),

            // Logo
            'school_logo' => SettingsHelper::get('school_logo', ''),
            
            // Configuration bulletin spécifique
            'bulletin_font_size' => SettingsHelper::get('bulletin_font_size', '11'),
            'bulletin_show_logo' => SettingsHelper::get('bulletin_show_logo', '1'),
            'bulletin_school_name_custom' => SettingsHelper::get('bulletin_school_name_custom', ''),
            'bulletin_show_header' => SettingsHelper::get('bulletin_show_header', '1'),
            'bulletin_show_republic_info' => SettingsHelper::get('bulletin_show_republic_info', '1'),
            'bulletin_republic_text' => SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire'),
            'bulletin_union_text' => SettingsHelper::get('bulletin_union_text', 'Union-Discipline-Travail'),
            'bulletin_show_ministry_info' => SettingsHelper::get('bulletin_show_ministry_info', '1'),
            'bulletin_ministry_text' => SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur'),
            'bulletin_show_school_info' => SettingsHelper::get('bulletin_show_school_info', '1'),
            'bulletin_show_edition_date' => SettingsHelper::get('bulletin_show_edition_date', '1'),
            'bulletin_show_cycle_info' => SettingsHelper::get('bulletin_show_cycle_info', '1'),
            'bulletin_cycle_text' => SettingsHelper::get('bulletin_cycle_text', 'Brevet de Technicien Supérieur'),
            'bulletin_cycle_abbreviation' => SettingsHelper::get('bulletin_cycle_abbreviation', 'BTS'),
            
            // Informations étudiant
            'bulletin_show_student_info' => SettingsHelper::get('bulletin_show_student_info', '1'),
            'bulletin_show_matricule' => SettingsHelper::get('bulletin_show_matricule', '1'),
            'bulletin_show_birth_date' => SettingsHelper::get('bulletin_show_birth_date', '1'),
            'bulletin_show_redoublant' => SettingsHelper::get('bulletin_show_redoublant', '1'),
            'bulletin_show_class_info' => SettingsHelper::get('bulletin_show_class_info', '1'),
            'bulletin_show_effectif' => SettingsHelper::get('bulletin_show_effectif', '1'),
            
            // Tableau des matières
            'bulletin_show_subjects_table' => SettingsHelper::get('bulletin_show_subjects_table', '1'),
            'bulletin_show_subject_average' => SettingsHelper::get('bulletin_show_subject_average', '1'),
            'bulletin_show_coefficient' => SettingsHelper::get('bulletin_show_coefficient', '1'),
            'bulletin_show_teachers' => SettingsHelper::get('bulletin_show_teachers', '1'),
            'bulletin_show_appreciations' => SettingsHelper::get('bulletin_show_appreciations', '1'),
            
            // Moyennes et statistiques
            'bulletin_show_general_average' => SettingsHelper::get('bulletin_show_general_average', '1'),
            'bulletin_show_technical_average' => SettingsHelper::get('bulletin_show_technical_average', '1'),
            'bulletin_show_global_average' => SettingsHelper::get('bulletin_show_global_average', '1'),
            'bulletin_show_class_rank' => SettingsHelper::get('bulletin_show_class_rank', '1'),
            'bulletin_show_class_size' => SettingsHelper::get('bulletin_show_class_size', '1'),
            'bulletin_show_attendance' => SettingsHelper::get('bulletin_show_attendance', '1'),
            'bulletin_show_attendance_note' => SettingsHelper::get('bulletin_show_attendance_note', '1'),
            'bulletin_show_highest_average' => SettingsHelper::get('bulletin_show_highest_average', '1'),
            'bulletin_show_lowest_average' => SettingsHelper::get('bulletin_show_lowest_average', '1'),
            'bulletin_show_class_average' => SettingsHelper::get('bulletin_show_class_average', '1'),
            'bulletin_show_council_decision' => SettingsHelper::get('bulletin_show_council_decision', '1'),
            
            // Signatures et validation
            'bulletin_show_signatures' => SettingsHelper::get('bulletin_show_signatures', '1'),
            'bulletin_show_director_signature' => SettingsHelper::get('bulletin_show_director_signature', '1'),
        ];
    }

    /**
     * Prépare le logo en base64 pour l'intégration dans le PDF
     */
    private function prepareLogoBase64($logoPath)
    {
        // Essayer d'abord le chemin depuis storage (logos uploadés)
        if ($logoPath) {
            $storagePath = storage_path('app/public/' . $logoPath);
            if (file_exists($storagePath)) {
                $logoType = pathinfo($storagePath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($storagePath);
                Log::info('Logo uploadé chargé avec succès depuis: ' . $storagePath);
                return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
            }
            
            // Essayer aussi dans public/ pour compatibilité
            $publicPath = public_path($logoPath);
            if (file_exists($publicPath)) {
                $logoType = pathinfo($publicPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($publicPath);
                Log::info('Logo public chargé avec succès depuis: ' . $publicPath);
                return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
            }
        }

        // Essayer les chemins alternatifs
        $alternativePaths = [
            'images/esbtp_logo.png',
            'images/logo.jpeg',
            'images/esbtp_logo_white.png',
            'storage/logos/' . basename($logoPath)
        ];

        foreach ($alternativePaths as $altPath) {
            $fullPath = public_path($altPath);
            if (file_exists($fullPath)) {
                $logoType = pathinfo($fullPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($fullPath);
                Log::info('Logo alternatif chargé avec succès depuis: ' . $fullPath);
                return 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
            }
        }

        Log::warning('Aucun logo trouvé pour le chemin: ' . $logoPath . '. Chemins testés: storage et public + alternatives');
        return null;
    }

    /**
     * Affiche la liste des bulletins avec filtre par année et classe
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Périodes disponibles (définir les périodes pour la vue)
        $periodes = [
            (object)['id' => 'semestre1', 'nom' => 'Premier Semestre', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)],
            (object)['id' => 'semestre2', 'nom' => 'Deuxième Semestre', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)],
            (object)['id' => 'annuel', 'nom' => 'Annuel', 'annee_scolaire' => date('Y') . '-' . (date('Y') + 1)]
        ];

        // Statistiques pour les widgets
        $stats = [
            'total' => ESBTPBulletin::count(),
            'published' => ESBTPBulletin::where('is_published', true)->count(),
            'pending' => ESBTPBulletin::where('is_published', false)->count(),
            'periodes' => count($periodes)
        ];

        // Valeurs par défaut filtre
        $classe_id = $request->input('classe_id');
        $annee_id = $request->input('annee_universitaire_id',
            ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null);
        $periode_id = $request->input('periode_id');

        $query = ESBTPBulletin::with(['etudiant', 'classe', 'anneeUniversitaire']);

        // Application des filtres
        if ($classe_id) {
            $query->where('classe_id', $classe_id);
        }

        if ($annee_id) {
            $query->where('annee_universitaire_id', $annee_id);
        }

        if ($periode_id) {
            $query->where('periode', $periode_id);
        }

        // Utiliser paginate() au lieu de get() pour permettre l'utilisation de appends()
        $bulletins = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('esbtp.bulletins.index', compact(
            'bulletins',
            'classes',
            'anneesUniversitaires',
            'classe_id',
            'annee_id',
            'periodes',
            'periode_id',
            'stats'
        ));
    }

    /**
     * Affiche le formulaire de sélection d'étudiant pour créer un bulletin
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeActuelle = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        return view('esbtp.bulletins.create', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Enregistre un nouveau bulletin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
            'appreciation_generale' => 'nullable|string',
            'decision_conseil' => 'nullable|string',
        ], [
            'etudiant_id.required' => 'L\'étudiant est obligatoire',
            'classe_id.required' => 'La classe est obligatoire',
            'annee_universitaire_id.required' => 'L\'année universitaire est obligatoire',
            'periode.required' => 'La période est obligatoire',
        ]);

        DB::beginTransaction();
        try {
            // Vérifier si l'étudiant est bien inscrit dans cette classe pour cette année
            $etudiantInscrit = ESBTPEtudiant::findOrFail($request->etudiant_id)
                ->inscriptions()
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->exists();

            if (!$etudiantInscrit) {
                return redirect()->back()
                    ->with('error', 'L\'étudiant n\'est pas inscrit dans cette classe pour cette année universitaire')
                    ->withInput();
            }

            // Vérifier s'il existe déjà un bulletin pour cet étudiant, cette classe, cette année et cette période
            $bulletinExistant = ESBTPBulletin::where('etudiant_id', $request->etudiant_id)
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->where('periode', $request->periode)
                ->exists();

            if ($bulletinExistant) {
                return redirect()->back()
                    ->with('error', 'Un bulletin existe déjà pour cet étudiant pour cette période')
                    ->withInput();
            }

            // Créer le bulletin
            $bulletin = new ESBTPBulletin();
            $bulletin->etudiant_id = $request->etudiant_id;
            $bulletin->classe_id = $request->classe_id;
            $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
            $bulletin->periode = $request->periode;
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->user_id = Auth::id();
            $bulletin->save();

            // Récupérer toutes les matières de la classe
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $matieres = $classe->matieres;

            // Pour chaque matière, calculer la moyenne et créer un résultat
            foreach ($matieres as $matiere) {
                // Récupérer toutes les évaluations de cette matière pour cette classe
                $evaluations = $matiere ? $matiere->evaluations()
                    ->where('classe_id', $classe->id)
                    ->where('periode', $request->periode)
                    ->get() : collect();

                Log::info('Récupération des évaluations', [
                    'matiere_id' => $matiere->id,
                    'nombre_evaluations' => $evaluations->count(),
                    'classe_id' => $classe->id,
                    'periode' => $request->periode
                ]);

                if (!$evaluations || $evaluations->isEmpty()) {
                    continue; // Passer à la matière suivante s'il n'y a pas d'évaluations
                }

                // Récupérer les notes de l'étudiant pour ces évaluations
                $notes = ESBTPNote::whereIn('evaluation_id', $evaluations->pluck('id'))
                    ->where('etudiant_id', $request->etudiant_id)
                    ->get();

                if (!$notes || $notes->isEmpty()) {
                    continue; // Passer à la matière suivante s'il n'y a pas de notes
                }

                // Calculer la moyenne
                $sommeNotes = 0;
                $sommeCoefficients = 0;

                foreach ($notes as $note) {
                    $evaluation = $evaluations->where('id', $note->evaluation_id)->first();
                    $sommeNotes += ($note->valeur / $evaluation->bareme) * 20 * $evaluation->coefficient;
                    $sommeCoefficients += $evaluation->coefficient;
                }

                $moyenne = $sommeCoefficients > 0 ? $sommeNotes / $sommeCoefficients : null;

                // Récupérer le coefficient de la matière pour cette classe
                $pivotData = $classe->matieres()->where('matiere_id', $matiere->id)->first()->pivot;
                $coefficient = $pivotData->coefficient ?? 1;

                // Créer le résultat pour cette matière
                $resultat = new ESBTPResultatMatiere();
                $resultat->bulletin_id = $bulletin->id;
                $resultat->matiere_id = $matiere->id;
                $resultat->moyenne = $moyenne;
                $resultat->coefficient = $coefficient;
                $resultat->commentaire = null;
                $resultat->save();
            }

            // Calculer et mettre à jour la moyenne générale du bulletin
            $this->calculerMoyenneGenerale($bulletin);

            // Déterminer la période pour le calcul des absences
            // Par exemple: utiliser la date de début et de fin du semestre
            $anneeUniversitaire = ESBTPAnneeUniversitaire::find($request->annee_universitaire_id);
            if ($anneeUniversitaire) {
                // Exemple: si periode = 'S1' (1er semestre)
                if ($request->periode == 'S1') {
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = Carbon::parse($dateDebut)->addMonths(4)->format('Y-m-d'); // Environ 4 mois pour un semestre
                } else if ($request->periode == 'S2') {
                    $dateDebut = Carbon::parse($anneeUniversitaire->date_debut)->addMonths(4)->format('Y-m-d');
                    $dateFin = $anneeUniversitaire->date_fin;
                } else {
                    // Pour les périodes différentes ou périodes trimestrielles
                    // Adapter la logique selon vos besoins
                    $dateDebut = $anneeUniversitaire->date_debut;
                    $dateFin = $anneeUniversitaire->date_fin;
                }

                // Calculer les absences pour la période du bulletin
                $donneeAbsences = $this->calculerAbsencesPourBulletin(
                    $request->etudiant_id,
                    $request->classe_id,
                    $dateDebut,
                    $dateFin
                );

                // Intégrer les absences au bulletin
                $bulletin = $this->integrerAbsencesAuBulletin($bulletin, $donneeAbsences);
            }

            DB::commit();
            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a été créé avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la création du bulletin: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Calcule et met à jour la moyenne générale d'un bulletin
     */
    private function calculerMoyenneGenerale(ESBTPBulletin $bulletin)
    {
        Log::info('Calcul de la moyenne générale pour le bulletin ' . $bulletin->id);

        try {
            $resultats = $bulletin->resultats;
            Log::info('Nombre de résultats trouvés: ' . $resultats->count());

            if ($resultats->isEmpty()) {
                Log::info('Aucun résultat trouvé pour le bulletin ' . $bulletin->id);
                $bulletin->moyenne_generale = null;
                $bulletin->save();
                return;
            }

            $sommePoints = 0;
            $sommeCoefficients = 0;

            foreach ($resultats as $resultat) {
                if ($resultat->moyenne !== null) {
                    Log::info('Résultat pour matière ' . $resultat->matiere_id . ': moyenne=' . $resultat->moyenne . ', coefficient=' . $resultat->coefficient);
                    $sommePoints += $resultat->moyenne * $resultat->coefficient;
                    $sommeCoefficients += $resultat->coefficient;
                } else {
                    Log::info('Résultat ignoré pour matière ' . $resultat->matiere_id . ' (moyenne null)');
                }
            }

            Log::info('Somme des points: ' . $sommePoints . ', Somme des coefficients: ' . $sommeCoefficients);
            $moyenneGenerale = $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : null;
            Log::info('Moyenne générale calculée: ' . $moyenneGenerale);

            $bulletin->moyenne_generale = $moyenneGenerale;
            $bulletin->save();
            Log::info('Moyenne générale enregistrée pour le bulletin ' . $bulletin->id);

            // Calculer le rang si la moyenne a changé
            $this->calculerRang($bulletin);
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul de la moyenne générale: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Calcule et met à jour le rang de l'étudiant dans sa classe
     */
    private function calculerRang($bulletin)
    {
        // Récupérer tous les bulletins de la même classe pour la même période
        $bulletins = ESBTPBulletin::where('classe_id', $bulletin->classe_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->where('periode', $bulletin->periode)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        // Mettre à jour l'effectif de la classe
        $bulletin->effectif_classe = $bulletins->count();

        // Trouver le rang de l'étudiant
        foreach ($bulletins as $index => $b) {
            if ($b->id === $bulletin->id) {
                $bulletin->rang = $index + 1;
                break;
            }
        }

        $bulletin->save();
    }

    /**
     * Affiche un bulletin spécifique.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function show(ESBTPBulletin $bulletin)
    {
        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere', 'user']);
        return view('esbtp.bulletins.show', compact('bulletin'));
    }

    /**
     * Affiche le formulaire de modification d'un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function edit(ESBTPBulletin $bulletin)
    {
        $bulletin->load(['etudiant', 'classe', 'anneeUniversitaire', 'resultats.matiere']);
        return view('esbtp.bulletins.edit', compact('bulletin'));
    }

    /**
     * Met à jour un bulletin spécifique.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ESBTPBulletin $bulletin)
    {
        $request->validate([
            'resultats' => 'required|array',
            'resultats.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne' => 'nullable|numeric|min:0|max:20',
            'resultats.*.coefficient' => 'required|numeric|min:0',
            'resultats.*.commentaire' => 'nullable|string',
            'appreciation_generale' => 'nullable|string',
            'decision_conseil' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Mettre à jour les informations du bulletin
            $bulletin->appreciation_generale = $request->appreciation_generale;
            $bulletin->decision_conseil = $request->decision_conseil;
            $bulletin->save();

            // Mettre à jour les résultats par matière
            foreach ($request->resultats as $resultatData) {
                $matiereId = $resultatData['matiere_id'];
                $moyenne = $resultatData['moyenne'] !== null && $resultatData['moyenne'] !== ''
                    ? $resultatData['moyenne'] : null;

                $resultat = ESBTPResultatMatiere::where('bulletin_id', $bulletin->id)
                    ->where('matiere_id', $matiereId)
                    ->first();

                if ($resultat) {
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                } else {
                    $resultat = new ESBTPResultatMatiere();
                    $resultat->bulletin_id = $bulletin->id;
                    $resultat->matiere_id = $matiereId;
                    $resultat->moyenne = $moyenne;
                    $resultat->coefficient = $resultatData['coefficient'];
                    $resultat->commentaire = $resultatData['commentaire'] ?? null;
                    $resultat->save();
                }
            }

            // Recalculer la moyenne générale
            $this->calculerMoyenneGenerale($bulletin);

            DB::commit();
            return redirect()->route('bulletins.show', $bulletin)
                ->with('success', 'Le bulletin a été mis à jour avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du bulletin: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Supprime un bulletin spécifique.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function destroy(ESBTPBulletin $bulletin)
    {
        try {
            $bulletin->delete();
            return redirect()->route('esbtp.bulletins.index')->with('success', 'Bulletin supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Génère un PDF du bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function genererPDF(ESBTPBulletin $bulletin)
    {
        try {
            Log::info('Début de la génération du PDF pour le bulletin #' . $bulletin->id);

            // Charger toutes les relations nécessaires avec eager loading, y compris les relations imbriquées
            $bulletin->load([
                'etudiant',
                'classe.niveauEtude',
                'classe.filiere',
                'anneeUniversitaire',
                'resultats.matiere',
                'user'
            ]);

            // Vérifier que les relations essentielles sont chargées
            if (!$bulletin->etudiant) {
                Log::error('Relation etudiant manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("L'étudiant associé à ce bulletin n'a pas été trouvé. Veuillez vérifier que l'étudiant existe et est correctement associé au bulletin.");
            }

            if (!$bulletin->classe) {
                Log::error('Relation classe manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("La classe associée à ce bulletin n'a pas été trouvée. Veuillez vérifier que la classe existe et est correctement associée au bulletin.");
            }

            if (!$bulletin->anneeUniversitaire) {
                Log::error('Relation anneeUniversitaire manquante pour le bulletin #' . $bulletin->id);
                throw new \Exception("L'année universitaire associée à ce bulletin n'a pas été trouvée. Veuillez vérifier que l'année universitaire existe et est correctement associée au bulletin.");
            }

            // Calculer la moyenne générale si pas déjà fait
            if (!$bulletin->moyenne_generale) {
                try {
                    $bulletin->calculerMoyenneGenerale();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la moyenne générale: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->moyenne_generale = 0;
                }
            }

            // Calculer la mention si pas déjà fait
            if (!$bulletin->mention) {
                try {
                    $bulletin->calculerMention();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul de la mention: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->mention = 'Non calculée';
                }
            }

            // Calculer le rang si pas déjà fait
            if (!$bulletin->rang) {
                try {
                    $bulletin->calculerRang();
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul du rang: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                    $bulletin->rang = 0;
                }
            }

            // Calculer les absences justifiées et non justifiées
            try {
                $absences = $this->calculerAbsencesDetailees($bulletin);
                $bulletin->absences_justifiees = $absences['justifiees'];
                $bulletin->absences_non_justifiees = $absences['non_justifiees'];
                $bulletin->total_absences = $absences['total'];
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des absences: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $bulletin->absences_justifiees = 0;
                $bulletin->absences_non_justifiees = 0;
                $bulletin->total_absences = 0;
            }

            // Si les absences sont toujours à zéro, essayer la méthode basée sur l'attendance
            if ($bulletin->absences_justifiees == 0 && $bulletin->absences_non_justifiees == 0) {
                try {
                    Log::info('Tentative de calcul des absences via le service pour le bulletin #' . $bulletin->id);

                    $absencesAttendance = $this->absenceService->calculerDetailAbsences(
                        $bulletin->etudiant_id,
                        $bulletin->classe_id,
                        $bulletin->anneeUniversitaire->date_debut,
                        $bulletin->anneeUniversitaire->date_fin
                    );

                    $bulletin->absences_justifiees = $absencesAttendance['justifiees'];
                    $bulletin->absences_non_justifiees = $absencesAttendance['non_justifiees'];
                    $bulletin->total_absences = $absencesAttendance['total'];
                    Log::info('Calcul des absences via le service réussi: ' . json_encode($absencesAttendance));
                } catch (\Exception $e) {
                    Log::error('Erreur lors du calcul des absences via le service: ' . $e->getMessage());
                    Log::error('Trace: ' . $e->getTraceAsString());
                }
            }

            // Récupérer les vraies notes de l'étudiant depuis la table esbtp_notes
            try {
                Log::info('Récupération des vraies notes pour l\'étudiant #' . $bulletin->etudiant_id);
                
                // Récupérer toutes les notes de l'étudiant avec les matières et évaluations
                $notesEtudiant = ESBTPNote::where('etudiant_id', $bulletin->etudiant_id)
                    ->where('classe_id', $bulletin->classe_id)
                    ->with(['matiere', 'evaluation'])
                    ->get();
                
                Log::info('Notes trouvées: ' . $notesEtudiant->count());
                
                // Grouper les notes par matière et calculer les moyennes par matière
                $notesByMatiere = $notesEtudiant->groupBy('matiere_id');
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
                $totalGeneral = 0;
                $totalTechnique = 0;
                $countGeneral = 0;
                $countTechnique = 0;
                
                foreach ($notesByMatiere as $matiereId => $notes) {
                    $matiere = ESBTPMatiere::find($matiereId);
                    
                    if ($matiere && $notes->count() > 0) {
                        // Calculer la moyenne pondérée de la matière avec les coefficients des évaluations
                        $totalPondere = 0;
                        $totalCoefficients = 0;
                        $evaluationsDetail = [];
                        
                        foreach ($notes as $note) {
                            // Récupérer le coefficient de l'évaluation
                            $coefficientEval = 1; // Par défaut
                            if ($note->evaluation_id) {
                                $evaluation = ESBTPEvaluation::find($note->evaluation_id);
                                if ($evaluation) {
                                    $coefficientEval = $evaluation->coefficient;
                                }
                            }
                            
                            $totalPondere += $note->note * $coefficientEval;
                            $totalCoefficients += $coefficientEval;
                            
                            $evaluationsDetail[] = [
                                'note' => $note->note,
                                'coefficient' => $coefficientEval,
                                'pondere' => $note->note * $coefficientEval,
                                'type' => $note->type_evaluation,
                                'evaluation_id' => $note->evaluation_id
                            ];
                        }
                        
                        // Calculer la moyenne pondérée
                        $moyenneMatiere = $totalCoefficients > 0 ? $totalPondere / $totalCoefficients : 0;
                        $coefficient = $matiere->coefficient; // Coefficient de la matière pour le calcul général
                        
                        Log::info('Matière: ' . $matiere->name . ' - ' . $notes->count() . ' notes');
                        Log::info('Total pondéré: ' . $totalPondere . ', Total coefficients: ' . $totalCoefficients);
                        Log::info('Moyenne pondérée: ' . round($moyenneMatiere, 2));
                        
                        // Créer un objet résultat formaté pour le template
                        $resultatFormate = (object) [
                            'id' => $notes->first()->id,
                            'note' => round($moyenneMatiere, 2),
                            'matiere' => $matiere,
                            'moyenne_matiere' => round($moyenneMatiere, 2),
                            'coefficient' => $coefficient,
                            'rang' => 1, // À calculer si nécessaire
                            'evaluations_detail' => $evaluationsDetail,
                            'total_coefficients' => $totalCoefficients,
                        ];
                        
                        if ($matiere->type_formation === 'generale') {
                            $totalGeneral += $moyenneMatiere * $coefficient;
                            $countGeneral += $coefficient;
                            $resultatsGeneraux->push($resultatFormate);
                        } elseif (in_array($matiere->type_formation, ['technique', 'technologique_professionnelle'])) {
                            $totalTechnique += $moyenneMatiere * $coefficient;
                            $countTechnique += $coefficient;
                            $resultatsTechniques->push($resultatFormate);
                        }
                    }
                }
                
                // Calculer les moyennes correctement
                $moyenneGenerale = $countGeneral > 0 ? round($totalGeneral / $countGeneral, 2) : 0;
                $moyenneTechnique = $countTechnique > 0 ? round($totalTechnique / $countTechnique, 2) : 0;
                
                // Calculer la moyenne globale (général + technique)
                $moyenneGlobale = ($countGeneral + $countTechnique) > 0 ? 
                    round(($totalGeneral + $totalTechnique) / ($countGeneral + $countTechnique), 2) : 0;
                
                Log::info('Moyennes calculées - Général: ' . $moyenneGenerale . ', Technique: ' . $moyenneTechnique . ', Globale: ' . $moyenneGlobale);
                
                // Mettre à jour le bulletin avec les moyennes calculées
                if (!$bulletin->moyenne_generale || $bulletin->moyenne_generale != $moyenneGlobale) {
                    $bulletin->moyenne_generale = $moyenneGlobale;
                    $bulletin->save();
                    Log::info('Moyenne générale mise à jour: ' . $moyenneGlobale);
                }
                
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des notes: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
                $moyenneGenerale = 0;
                $moyenneTechnique = 0;
            }

            // Générer le PDF avec les configurations de l'école
            $config = $this->getPDFConfig();

            // Récupérer tous les paramètres de configuration pour le template pdf-configurable
            $settings = $this->getPDFConfig(); // Utiliser la méthode centralisée
            /*$settings = [
                // Configuration de base
                'bulletin_font_size' => \App\Helpers\SettingsHelper::get('bulletin_font_size', '11'),
                'bulletin_show_logo' => \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1'),
                'bulletin_school_name_custom' => \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', ''),
                'bulletin_show_header' => \App\Helpers\SettingsHelper::get('bulletin_show_header', '1'),
                'bulletin_show_republic_info' => \App\Helpers\SettingsHelper::get('bulletin_show_republic_info', '1'),
                'bulletin_republic_text' => \App\Helpers\SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire'),
                'bulletin_union_text' => \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union-Discipline-Travail'),
                'bulletin_show_ministry_info' => \App\Helpers\SettingsHelper::get('bulletin_show_ministry_info', '1'),
                'bulletin_ministry_text' => \App\Helpers\SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur'),
                'bulletin_show_school_info' => \App\Helpers\SettingsHelper::get('bulletin_show_school_info', '1'),
                'bulletin_show_edition_date' => \App\Helpers\SettingsHelper::get('bulletin_show_edition_date', '1'),
                'bulletin_show_cycle_info' => \App\Helpers\SettingsHelper::get('bulletin_show_cycle_info', '1'),
                'bulletin_cycle_text' => \App\Helpers\SettingsHelper::get('bulletin_cycle_text', 'Brevet de Technicien Supérieur'),
                'bulletin_cycle_abbreviation' => \App\Helpers\SettingsHelper::get('bulletin_cycle_abbreviation', 'BTS'),
                
                // Informations étudiant
                'bulletin_show_student_info' => \App\Helpers\SettingsHelper::get('bulletin_show_student_info', '1'),
                'bulletin_show_matricule' => \App\Helpers\SettingsHelper::get('bulletin_show_matricule', '1'),
                'bulletin_show_birth_date' => \App\Helpers\SettingsHelper::get('bulletin_show_birth_date', '1'),
                'bulletin_show_redoublant' => \App\Helpers\SettingsHelper::get('bulletin_show_redoublant', '1'),
                'bulletin_show_class_info' => \App\Helpers\SettingsHelper::get('bulletin_show_class_info', '1'),
                'bulletin_show_effectif' => \App\Helpers\SettingsHelper::get('bulletin_show_effectif', '1'),
                
                // Tableau des matières
                'bulletin_show_subjects_table' => \App\Helpers\SettingsHelper::get('bulletin_show_subjects_table', '1'),
                'bulletin_show_subject_average' => \App\Helpers\SettingsHelper::get('bulletin_show_subject_average', '1'),
                'bulletin_show_coefficient' => \App\Helpers\SettingsHelper::get('bulletin_show_coefficient', '1'),
                'bulletin_show_weighted_average' => \App\Helpers\SettingsHelper::get('bulletin_show_weighted_average', '1'),
                'bulletin_show_rank_per_subject' => \App\Helpers\SettingsHelper::get('bulletin_show_rank_per_subject', '1'),
                'bulletin_show_teachers' => \App\Helpers\SettingsHelper::get('bulletin_show_teachers', '1'),
                'bulletin_show_appreciations' => \App\Helpers\SettingsHelper::get('bulletin_show_appreciations', '1'),
                'bulletin_show_general_subjects' => \App\Helpers\SettingsHelper::get('bulletin_show_general_subjects', '1'),
                'bulletin_show_technical_subjects' => \App\Helpers\SettingsHelper::get('bulletin_show_technical_subjects', '1'),
                'bulletin_show_section_averages' => \App\Helpers\SettingsHelper::get('bulletin_show_section_averages', '1'),
                
                // Absences
                'bulletin_show_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_absences', '1'),
                'bulletin_show_justified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_justified_absences', '1'),
                'bulletin_show_unjustified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_unjustified_absences', '1'),
                
                // Section résultats
                'bulletin_show_results_section' => \App\Helpers\SettingsHelper::get('bulletin_show_results_section', '1'),
                'bulletin_show_raw_average' => \App\Helpers\SettingsHelper::get('bulletin_show_raw_average', '1'),
                'bulletin_show_attendance_note' => \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1'),
                'bulletin_show_semester_average' => \App\Helpers\SettingsHelper::get('bulletin_show_semester_average', '1'),
                'bulletin_show_student_rank' => \App\Helpers\SettingsHelper::get('bulletin_show_student_rank', '1'),
                
                // Mentions
                'bulletin_show_mentions' => \App\Helpers\SettingsHelper::get('bulletin_show_mentions', '1'),
                'bulletin_show_felicitation' => \App\Helpers\SettingsHelper::get('bulletin_show_felicitation', '1'),
                'bulletin_show_encouragement' => \App\Helpers\SettingsHelper::get('bulletin_show_encouragement', '1'),
                'bulletin_show_honor_roll' => \App\Helpers\SettingsHelper::get('bulletin_show_honor_roll', '1'),
                'bulletin_show_work_warning' => \App\Helpers\SettingsHelper::get('bulletin_show_work_warning', '1'),
                'bulletin_show_conduct_blame' => \App\Helpers\SettingsHelper::get('bulletin_show_conduct_blame', '1'),
                'bulletin_auto_calculate_mention' => \App\Helpers\SettingsHelper::get('bulletin_auto_calculate_mention', '1'),
                'bulletin_felicitation_threshold' => \App\Helpers\SettingsHelper::get('bulletin_felicitation_threshold', '16'),
                'bulletin_encouragement_threshold' => \App\Helpers\SettingsHelper::get('bulletin_encouragement_threshold', '14'),
                'bulletin_honor_roll_threshold' => \App\Helpers\SettingsHelper::get('bulletin_honor_roll_threshold', '12'),
                'bulletin_work_warning_threshold' => \App\Helpers\SettingsHelper::get('bulletin_work_warning_threshold', '8'),
                
                // Statistiques
                'bulletin_show_statistics' => \App\Helpers\SettingsHelper::get('bulletin_show_statistics', '1'),
                'bulletin_show_highest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_highest_average', '1'),
                'bulletin_show_lowest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_lowest_average', '1'),
                'bulletin_show_class_average' => \App\Helpers\SettingsHelper::get('bulletin_show_class_average', '1'),
                
                // Décision et signature
                'bulletin_show_council_decision' => \App\Helpers\SettingsHelper::get('bulletin_show_council_decision', '1'),
                'bulletin_show_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_signature', '1'),
                'bulletin_show_director_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_director_signature', '1'),
                'bulletin_show_print_button' => \App\Helpers\SettingsHelper::get('bulletin_show_print_button', '1'),
            ];*/

            $data = [
                'bulletin' => $bulletin,
                'etudiant' => $bulletin->etudiant, // Ajout explicite pour le template
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneTechnique' => $moyenneTechnique,
                'moyenneGlobale' => $moyenneGlobale, // Moyenne globale calculée
                'absencesJustifiees' => $bulletin->absences_justifiees,
                'absencesNonJustifiees' => $bulletin->absences_non_justifiees,
                'absences_justifiees' => $bulletin->absences_justifiees,
                'absences_non_justifiees' => $bulletin->absences_non_justifiees,
                'config' => $config,
                'settings' => $settings, // Ajouter tous les paramètres de configuration
            ];

            // Log des variables d'absences pour debugging
            Log::info('Variables d\'absence pour le PDF dans genererPDF:', [
                'bulletin_absences_justifiees' => $bulletin->absences_justifiees ?? 'Non défini',
                'bulletin_absences_non_justifiees' => $bulletin->absences_non_justifiees ?? 'Non défini',
                'data_absencesJustifiees' => $data['absencesJustifiees'] ?? 'Non défini',
                'data_absencesNonJustifiees' => $data['absencesNonJustifiees'] ?? 'Non défini',
                'data_absences_justifiees' => $data['absences_justifiees'] ?? 'Non défini',
                'data_absences_non_justifiees' => $data['absences_non_justifiees'] ?? 'Non défini',
            ]);

            // Préparer le logo en base64
            $data['logoBase64'] = $this->prepareLogoBase64($config['school_logo']);

            try {
                Log::info('Chargement de la vue PDF avec le template configurable pour le bulletin #' . $bulletin->id);
                $pdf = PDF::loadView('esbtp.bulletins.pdf-configurable', $data);
                
                // Configuration PDF avec format A4 et options optimisées
                $paperFormat = \App\Helpers\SettingsHelper::get('bulletin_paper_format', 'A4');
                $orientation = \App\Helpers\SettingsHelper::get('bulletin_orientation', 'portrait');
                $dpi = \App\Helpers\SettingsHelper::get('bulletin_dpi', '150');
                
                $pdf->setPaper(strtolower($paperFormat), $orientation);
                $pdf->setOptions([
                    'dpi' => intval($dpi), 
                    'defaultFont' => 'sans-serif',
                    'isRemoteEnabled' => false, // Pour éviter les problèmes de sécurité
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => false
                ]);

                // Nom du fichier PDF
                $filename = 'bulletin_' .
                            ($bulletin->etudiant ? $bulletin->etudiant->matricule : 'unknown') . '_' .
                            ($bulletin->classe ? $bulletin->classe->code : 'unknown') . '_' .
                            $bulletin->periode . '_' .
                            ($bulletin->anneeUniversitaire ? $bulletin->anneeUniversitaire->libelle : 'unknown') . '.pdf';

                Log::info('PDF généré avec succès pour le bulletin #' . $bulletin->id);
                // Télécharger le PDF
                return $pdf->download($filename);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la génération du PDF: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());

                // Enregistrer des informations supplémentaires pour le débogage
                Log::error('Données du bulletin: ' . json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));

                return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la préparation des données pour le PDF: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            // Enregistrer des informations supplémentaires pour le débogage
            if (isset($bulletin)) {
                Log::error('Données du bulletin: ' . json_encode([
                    'id' => $bulletin->id,
                    'etudiant_id' => $bulletin->etudiant_id,
                    'classe_id' => $bulletin->classe_id,
                    'annee_universitaire_id' => $bulletin->annee_universitaire_id,
                    'periode' => $bulletin->periode,
                ]));
            }

            return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Calcule les absences justifiées et non justifiées pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return array
     */
    private function calculerAbsencesDetailees($bulletin)
    {
        try {
            \Log::info('Début du calcul des absences détaillées pour le bulletin #' . $bulletin->id);

            // Vérifier que les relations nécessaires sont chargées
            if (!$bulletin->etudiant || !$bulletin->classe || !$bulletin->anneeUniversitaire) {
                \Log::error('Relations essentielles manquantes pour le calcul des absences du bulletin #' . $bulletin->id);
                throw new \Exception("Données incomplètes pour calculer les absences. Veuillez vérifier que l'étudiant, la classe et l'année universitaire sont correctement définis.");
            }

            // Vérifier que les dates de l'année universitaire sont définies
            if (!$bulletin->anneeUniversitaire->date_debut || !$bulletin->anneeUniversitaire->date_fin) {
                \Log::error('Dates de l\'année universitaire non définies pour le bulletin #' . $bulletin->id);
                throw new \Exception("Les dates de début et de fin de l'année universitaire ne sont pas définies.");
            }

            // Utiliser le service d'absences pour calculer les absences
            $absences = $this->absenceService->calculerDetailAbsences(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->anneeUniversitaire->date_debut,
                $bulletin->anneeUniversitaire->date_fin
            );

            \Log::info('Absences détaillées calculées avec succès pour le bulletin #' . $bulletin->id, $absences);

            return $absences;

            } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul des absences détaillées: ' . $e->getMessage(), [
                'bulletin_id' => $bulletin->id,
                'etudiant_id' => $bulletin->etudiant_id ?? 'non défini',
                'classe_id' => $bulletin->classe_id ?? 'non défini',
                'trace' => $e->getTraceAsString()
            ]);

            // Retourner des valeurs par défaut en cas d'erreur
            return [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0,
                'detail' => [
                    'justifiees' => [],
                    'non_justifiees' => []
                ]
            ];
        }
    }
/////////////////////
    /**
     * Calcule le total des heures d'absence pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return int
     */
    private function calculerTotalAbsences($bulletin)
    {
        \Log::info('Calcul du total des absences pour le bulletin #' . $bulletin->id);

        try {
            // Utiliser le service d'absences pour calculer les absences
            $absences = $this->absenceService->calculerDetailAbsences(
                $bulletin->etudiant_id,
                $bulletin->classe_id,
                $bulletin->anneeUniversitaire->date_debut,
                $bulletin->anneeUniversitaire->date_fin
            );

            \Log::info('Total des absences calculé: ' . $absences['total'] . ' heures');

            return $absences['total'];
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul du total des absences: ' . $e->getMessage(), [
                'bulletin_id' => $bulletin->id,
                'trace' => $e->getTraceAsString()
            ]);

            return 0;
        }
    }

    /**
     * Génère les bulletins pour une classe entière.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function genererClasseBulletins(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
        ]);

        try {
            Log::info('Début de la génération des bulletins', $request->all());
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($request->annee_universitaire_id);

            // Récupérer tous les étudiants inscrits dans cette classe pour cette année
            try {
                Log::info('Récupération des étudiants inscrits');

                // Utiliser une requête directe à la place de la relation 'inscriptions'
                $etudiantIds = DB::table('esbtp_inscriptions')
                    ->where('classe_id', $request->classe_id)
                    ->where('annee_universitaire_id', $request->annee_universitaire_id)
                    ->where('status', 'active')
                    ->pluck('etudiant_id');

                $etudiants = ESBTPEtudiant::whereIn('id', $etudiantIds)->get();

                // Si aucun étudiant n'est trouvé par cette méthode, essayer de récupérer tous les étudiants de la classe
                if ($etudiants->isEmpty()) {
                    Log::info('Aucun étudiant trouvé via les inscriptions, recherche alternative');
                    $etudiants = ESBTPEtudiant::where('classe_id', $request->classe_id)->get();
                }

                Log::info('Nombre d\'étudiants trouvés: ' . $etudiants->count());

                if ($etudiants->isEmpty()) {
                    Log::warning('Aucun étudiant trouvé pour la classe ' . $classe->name);
                    return redirect()->route('esbtp.bulletins.index')
                        ->with('warning', 'Aucun étudiant trouvé pour la classe sélectionnée.');
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des étudiants: ' . $e->getMessage());
                Log::error('SQL: ' . $e->getTraceAsString());
                throw $e;
            }

            $bulletinsGeneres = 0;

            foreach ($etudiants as $etudiant) {
                Log::info('Traitement de l\'étudiant: ' . $etudiant->id . ' - ' . $etudiant->nom . ' ' . $etudiant->prenoms);
                // Vérifier si un bulletin existe déjà pour cet étudiant
                try {
                    $bulletinExistant = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                        ->where('classe_id', $request->classe_id)
                        ->where('annee_universitaire_id', $request->annee_universitaire_id)
                        ->where('periode', $request->periode)
                        ->exists();

                    if ($bulletinExistant) {
                        Log::info('Bulletin existant pour l\'étudiant: ' . $etudiant->id);
                        continue; // Passer à l'étudiant suivant
                    }
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la vérification du bulletin existant: ' . $e->getMessage());
                    Log::error('SQL: ' . $e->getTraceAsString());
                    throw $e;
                }

                // Créer une requête simulée pour réutiliser la méthode store
                $bulletinRequest = new Request([
                    'etudiant_id' => $etudiant->id,
                    'classe_id' => $request->classe_id,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'periode' => $request->periode,
                    'appreciation_generale' => null,
                    'decision_conseil' => null,
                ]);

                // Appeler la méthode store mais sans rediriger
                try {
                    DB::beginTransaction();

                    // Créer le bulletin
                    $bulletin = new ESBTPBulletin();
                    $bulletin->etudiant_id = $etudiant->id;
                    $bulletin->classe_id = $request->classe_id;
                    $bulletin->annee_universitaire_id = $request->annee_universitaire_id;
                    $bulletin->periode = $request->periode;
                    $bulletin->appreciation_generale = null;
                    $bulletin->decision_conseil = null;
                    $bulletin->user_id = Auth::id();
                    $bulletin->save();
                    Log::info('Bulletin créé: ' . $bulletin->id);

                    // Récupérer toutes les matières de la classe
                    $matieres = $classe->matieres;
                    Log::info('Nombre de matières trouvées: ' . $matieres->count());

                    // Pour chaque matière, calculer la moyenne et créer un résultat
                    foreach ($matieres as $matiere) {
                        Log::info('Traitement de la matière: ' . $matiere->id . ' - ' . ($matiere->nom ?? $matiere->name ?? 'Nom inconnu'));

                        // Vérifier si la matière est valide
                        if (!$matiere || !$matiere->id) {
                            Log::warning('Matière invalide trouvée');
                            continue;
                        }

                        // Récupérer toutes les évaluations de cette matière pour cette classe
                        try {
                            $evaluations = $matiere->evaluations()
                                ->where('classe_id', $classe->id)
                                ->where('periode', $request->periode)
                                ->get();

                            Log::info('Nombre d\'évaluations trouvées: ' . $evaluations->count(), [
                                'matiere_id' => $matiere->id,
                                'classe_id' => $classe->id,
                                'periode' => $request->periode
                            ]);

                            if (!$evaluations || $evaluations->isEmpty()) {
                                Log::info('Pas d\'évaluations pour la matière et la période: ' . $matiere->id, [
                                    'periode' => $request->periode
                                ]);

                                // Créer un résultat vide pour cette matière
                                try {
                                    // Récupérer le coefficient de la matière pour cette classe
                                    $coefficient = 1; // Valeur par défaut
                                    try {
                                        $pivot = DB::table('esbtp_classe_matiere')
                                            ->where('classe_id', $classe->id)
                                            ->where('matiere_id', $matiere->id)
                                            ->first();

                                        if ($pivot && isset($pivot->coefficient)) {
                                            $coefficient = $pivot->coefficient;
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Erreur lors de la récupération du coefficient: ' . $e->getMessage());
                                    }

                                    $resultat = new ESBTPResultatMatiere();
                                    $resultat->bulletin_id = $bulletin->id;
                                    $resultat->matiere_id = $matiere->id;
                                    $resultat->moyenne = null; // Pas de moyenne car pas d'évaluations
                                    $resultat->coefficient = $coefficient;
                                    $resultat->commentaire = null;
                                    $resultat->save();
                                    Log::info('Résultat vide créé pour la matière: ' . $matiere->id);
                                } catch (\Exception $e) {
                                    Log::error('Erreur lors de la création du résultat vide: ' . $e->getMessage());
                                }

                                continue; // Passer à la matière suivante s'il n'y a pas d'évaluations
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération des évaluations: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            continue; // Passer à la matière suivante en cas d'erreur
                        }

                        // Récupérer les notes de l'étudiant pour ces évaluations
                        try {
                            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
                                ->where('classe_id', $request->classe_id)
                                ->whereHas('evaluation', function($query) use ($request) {
                                    $query->where('annee_universitaire_id', $request->annee_universitaire_id);
                                    if ($request->periode != 'annuel') {
                                        $query->where('periode', $request->periode);
                                    }
                                })
                                ->get();

                            Log::info('Nombre de notes trouvées: ' . $notes->count());

                            if (!$notes || $notes->isEmpty()) {
                                Log::info('Pas de notes pour l\'étudiant: ' . $etudiant->id . ' dans la matière: ' . $matiere->id);
                                continue; // Passer à la matière suivante s'il n'y a pas de notes
                            }
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération des notes: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            throw $e;
                        }

                        // Calculer la moyenne
                        $sommeNotes = 0;
                        $sommeCoefficients = 0;

                        foreach ($notes as $note) {
                            $evaluation = $notes->where('evaluation_id', $note->evaluation_id)->first();
                            $sommeNotes += ($note->valeur / $evaluation->bareme) * 20 * $evaluation->coefficient;
                            $sommeCoefficients += $evaluation->coefficient;
                        }

                        $moyenne = $sommeCoefficients > 0 ? $sommeNotes / $sommeCoefficients : null;

                        // Récupérer le coefficient de la matière pour cette classe
                        try {
                            $pivotData = $classe->matieres()->where('matiere_id', $matiere->id)->first()->pivot;
                            $coefficient = $pivotData->coefficient ?? 1;
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la récupération du coefficient: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            $coefficient = 1; // Valeur par défaut en cas d'erreur
                        }

                        // Créer le résultat pour cette matière
                        try {
                            $resultat = new ESBTPResultatMatiere();
                            $resultat->bulletin_id = $bulletin->id;
                            $resultat->matiere_id = $matiere->id;
                            $resultat->moyenne = $moyenne;
                            $resultat->coefficient = $coefficient;
                            $resultat->commentaire = null;
                            $resultat->save();
                            Log::info('Résultat créé pour la matière: ' . $matiere->id . ' avec moyenne: ' . $moyenne);
                        } catch (\Exception $e) {
                            Log::error('Erreur lors de la création du résultat: ' . $e->getMessage());
                            Log::error('SQL: ' . $e->getTraceAsString());
                            throw $e;
                        }
                    }

                    // Calculer et mettre à jour la moyenne générale du bulletin
                    try {
                        Log::info('Calcul de la moyenne générale pour le bulletin: ' . $bulletin->id);
                        $this->calculerMoyenneGenerale($bulletin);
                    } catch (\Exception $e) {
                        Log::error('Erreur lors du calcul de la moyenne générale: ' . $e->getMessage());
                        Log::error('SQL: ' . $e->getTraceAsString());
                        throw $e;
                    }

                    DB::commit();
                    $bulletinsGeneres++;
                    Log::info('Bulletin généré avec succès pour l\'étudiant: ' . $etudiant->id);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur lors de la génération du bulletin pour l\'étudiant: ' . $etudiant->id . ' - ' . $e->getMessage());
                    Log::error('SQL: ' . $e->getTraceAsString());
                    // Continuer avec l'étudiant suivant
                }
            }

            if ($bulletinsGeneres > 0) {
                Log::info('Bulletins générés avec succès: ' . $bulletinsGeneres);
                return redirect()->route('esbtp.bulletins.index')
                    ->with('success', $bulletinsGeneres . ' bulletins ont été générés avec succès');
            } else {
                Log::info('Aucun bulletin généré');
                return redirect()->route('esbtp.bulletins.index')
                    ->with('info', 'Aucun nouveau bulletin n\'a été généré. Tous les bulletins existent déjà ou il n\'y a pas de données suffisantes.');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des bulletins: ' . $e->getMessage());
            Log::error('SQL: ' . $e->getTraceAsString());

            return redirect()->route('esbtp.bulletins.index')
                ->with('error', 'Une erreur est survenue lors de la génération des bulletins: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page de sélection pour les bulletins
     *
     * @return \Illuminate\Http\Response
     */
    public function select()
    {
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeActuelle = ESBTPAnneeUniversitaire::where('is_active', true)->first();

        return view('esbtp.bulletins.select', compact('classes', 'anneesUniversitaires', 'anneeActuelle'));
    }

    /**
     * Affiche les résultats des étudiants
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resultatsClasses(Request $request)
    {
        $annee_universitaire_id = $request->get('annee_universitaire_id');

        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Récupérer l'année courante (is_current = true) pour les inscriptions
        $currentAnnee = $annees_universitaires->firstWhere('is_current', true);

        // Si aucune année n'est spécifiée pour le filtre, utiliser l'année courante
        if (!$annee_universitaire_id && $currentAnnee) {
            $annee_universitaire_id = $currentAnnee->id;
        }

        // Récupérer TOUTES les classes actives (peu importe leur année universitaire)
        $classesQuery = ESBTPClasse::with(['filiere', 'niveau', 'anneeUniversitaire'])
            ->where('is_active', true)
            ->withCount(['inscriptions as actifs_count' => function ($query) use ($annee_universitaire_id) {
                // Compter les étudiants avec inscription active pour l'année sélectionnée
                $query->where('status', 'active')
                      ->where('annee_universitaire_id', $annee_universitaire_id);
            }]);

        // NE PAS filtrer les classes par année universitaire, on les veut toutes
        $classes = $classesQuery->orderBy('name')->get();

        $totalClasses = $classes->count();
        $totalFilieres = $classes->pluck('filiere.name')->filter()->unique()->count();
        $totalNiveaux = $classes->pluck('niveau.name')->filter()->unique()->count();

        // Total des étudiants actifs pour l'année sélectionnée (toutes classes confondues)
        $totalEtudiants = $classes->sum('actifs_count');

        // L'année sélectionnée pour le filtre des inscriptions
        $selectedAnnee = $annee_universitaire_id
            ? $annees_universitaires->firstWhere('id', $annee_universitaire_id)
            : $currentAnnee;

        // Si requête AJAX, retourner JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('esbtp.resultats.partials.classes-grid', compact('classes', 'annee_universitaire_id'))->render(),
                'kpis' => [
                    'totalClasses' => $totalClasses,
                    'totalFilieres' => $totalFilieres,
                    'totalNiveaux' => $totalNiveaux,
                    'totalEtudiants' => $totalEtudiants,
                ],
                'selectedAnnee' => $selectedAnnee,
            ]);
        }

        return view('esbtp.resultats.classes', [
            'classes' => $classes,
            'annees_universitaires' => $annees_universitaires,
            'annee_universitaire_id' => $annee_universitaire_id,
            'totalClasses' => $totalClasses,
            'totalFilieres' => $totalFilieres,
            'totalNiveaux' => $totalNiveaux,
            'totalEtudiants' => $totalEtudiants,
            'currentAnnee' => $currentAnnee,
            'selectedAnnee' => $selectedAnnee,
        ]);
    }

    public function resultats(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'nullable|exists:esbtp_classes,id',
            'semestre' => 'nullable|in:1,2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $classe_id = $request->classe_id;
        $semestre = $request->semestre;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses') ? $request->include_all_statuses : true; // Par défaut, inclure tous les statuts
        $periode = $semestre; // Map semestre to periode for view compatibility

        Log::info('Resultats method called with params', [
            'classe_id' => $classe_id,
            'semestre' => $semestre,
            'annee_universitaire_id' => $annee_universitaire_id,
            'include_all_statuses' => $include_all_statuses
        ]);

        // If classe_id is provided, get the corresponding academic year
        if ($classe_id && !$annee_universitaire_id) {
            $classe = ESBTPClasse::find($classe_id);
            if ($classe && $classe->annee_universitaire_id) {
                $annee_universitaire_id = $classe->annee_universitaire_id;
            }
        }

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
            // First try to find an academic year that has some notes/data
            $anneeWithData = ESBTPAnneeUniversitaire::whereExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('esbtp_evaluations')
                      ->whereColumn('esbtp_evaluations.annee_universitaire_id', 'esbtp_annee_universitaires.id')
                      ->whereExists(function($subQuery) {
                          $subQuery->select(DB::raw(1))
                                   ->from('esbtp_notes')
                                   ->whereColumn('esbtp_notes.evaluation_id', 'esbtp_evaluations.id');
                      });
            })->orderBy('is_active', 'desc')->orderBy('annee_debut', 'desc')->first();
            
            if ($anneeWithData) {
                $annee_universitaire_id = $anneeWithData->id;
                Log::info('Année avec données trouvée: ' . $anneeWithData->name . ' (ID: ' . $anneeWithData->id . ')');
            } else {
                // Fallback to active academic year
                $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
            }
        }

        // For view compatibility
        $annee_id = $annee_universitaire_id;

        // Get annee object for view display
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // Always load all active classes with relationships, regardless of filters
        $classes = ESBTPClasse::with(['filiere', 'niveau'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $periodes = ['1' => 'Semestre 1', '2' => 'Semestre 2'];
        // Récupérer toutes les années universitaires (on affiche name dans la vue)
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('id', 'desc')->get();

        // Get selected classe information
        $classeObj = null;
        $classe = null;
        if ($classe_id) {
            $classeObj = ESBTPClasse::with('filiere')->find($classe_id);
            $classe = $classeObj; // Alias for view compatibility
        }

        // OPTIMISATION LAZY LOADING: Ne plus charger automatiquement tous les étudiants
        // Les étudiants seront chargés via AJAX par petits groupes pour optimiser les performances
        
        // Calculer seulement les KPIs généraux pour l'affichage initial
        $totalEtudiants = 0;
        $moyenneGenerale = null;
        $tauxReussite = null;
        $totalBulletins = 0;
        
        // Calculer rapidement le nombre total d'étudiants sans charger toutes les données
        if ($classe_id || $annee_universitaire_id) {
            $studentsCountQuery = $this->buildEtudiantsQuery($classe_id, $annee_universitaire_id, $include_all_statuses);
            $totalEtudiants = $studentsCountQuery->count();
            
            Log::info('KPIs calculés pour l\'affichage initial', [
                'total_etudiants' => $totalEtudiants,
                'classe_id' => $classe_id,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);
        }

        // Variables minimales pour la compatibilité de la vue
        $etudiants = collect(); // Collection vide pour la compatibilité
        $notes = collect([]); // Initialize as collection to work with isEmpty() in view
        $moyennes = []; // Empty for initial load
        $rangs = []; // Empty for initial load  
        $bulletins = []; // Empty for initial load

        // Les moyennes et rangs sont maintenant calculés uniquement à partir de vraies données
        
        \Log::info('Résultats calculés - etudiants: ' . count($etudiants) . ', moyennes: ' . count($moyennes) . ', notes: ' . count($notes));

        return view('esbtp.resultats.index', compact(
            'classes',
            'periodes',
            'annees_universitaires',
            'classe_id',
            'classeObj',
            'classe',
            'semestre',
            'periode',
            'annee_universitaire_id',
            'annee_id',
            'anneeUniversitaire',
            'etudiants',
            'notes',
            'moyennes',
            'rangs',
            'bulletins',
            'totalEtudiants',
            'include_all_statuses'
        ));
    }

    /**
     * Load students with lazy loading pagination for AJAX requests
     */
    public function loadEtudiants(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        $classe_id = $request->get('classe_id');
        $semestre = $request->get('semestre');
        $annee_universitaire_id = $request->get('annee_universitaire_id');
        $include_all_statuses = $request->get('include_all_statuses', true);

        try {
            // Get students query based on the same logic as resultats method
            $studentsQuery = $this->buildEtudiantsQuery($classe_id, $annee_universitaire_id, $include_all_statuses);
            $total = (clone $studentsQuery)->count();
            $etudiants = (clone $studentsQuery)->skip(($page - 1) * $perPage)->take($perPage)->get();
            $studentIds = (clone $studentsQuery)->pluck('id');
            $kpis = $this->computeResultatsKpis($studentIds, $classe_id, $annee_universitaire_id, $semestre);
            
            // Calculate moyennes, rangs, etc. for these students
            $moyennes = [];
            $rangs = [];
            $bulletins = [];
            $notes = collect([]);
            
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();
                
                // Get notes for these students
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                if ($classe_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($classe_id) {
                        $query->where('classe_id', $classe_id);
                    });
                }

                if ($annee_universitaire_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($annee_universitaire_id) {
                        $query->where('annee_universitaire_id', $annee_universitaire_id);
                    });
                }

                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();
                
                // Calculate stats for these students
                $this->calculateStudentStatsFixed($etudiants, $notes, $moyennes, $rangs);
                
                // Get bulletins
                $this->getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, $bulletins);
            }

            // Determine which template to use
            if ((int)$page === 1) {
                // Page 1: Full table structure with headers
                $html = view('esbtp.resultats.partials.liste-etudiants', [
                    'etudiants' => $etudiants,
                    'moyennes' => $moyennes,
                    'rangs' => $rangs,
                    'bulletins' => $bulletins,
                    'classe' => $classe_id ? ESBTPClasse::find($classe_id) : null,
                    'annee_id' => $annee_universitaire_id
                ])->render();
            } else {
                // Pages suivantes: Seulement les lignes TR
                $html = view('esbtp.resultats.partials.lignes-etudiants', [
                    'etudiants' => $etudiants,
                    'moyennes' => $moyennes,
                    'rangs' => $rangs,
                    'bulletins' => $bulletins,
                    'classe' => $classe_id ? ESBTPClasse::find($classe_id) : null,
                    'annee_id' => $annee_universitaire_id
                ])->render();
            }

            $hasMore = ($page * $perPage) < $total;

            return response()->json([
                'html' => $html,
                'total' => $total,
                'current_page' => (int)$page,
                'has_more' => $hasMore,
                'loaded_count' => $etudiants->count(),
                'kpis' => $kpis
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors du chargement lazy des étudiants', [
                'error' => $e->getMessage(),
                'page' => $page,
                'classe_id' => $classe_id
            ]);

            return response()->json([
                'error' => 'Erreur lors du chargement des étudiants',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcule les indicateurs clés affichés sur la page des résultats.
     */
    private function computeResultatsKpis(Collection $studentIds, $classe_id, $annee_universitaire_id, $semestre): array
    {
        $kpis = [
            'total_etudiants' => $studentIds->count(),
            'moyenne_generale' => null,
            'taux_reussite' => null,
            'bulletins_count' => 0,
        ];

        if ($studentIds->isEmpty()) {
            return $kpis;
        }

        $moyennes = [];
        $rangs = [];

        $this->getPreCalculatedResults(
            $studentIds->map(function ($id) {
                return (object) ['id' => $id];
            })->all(),
            $classe_id,
            $annee_universitaire_id,
            $semestre,
            $moyennes,
            $rangs
        );

        if (empty($moyennes)) {
            $students = ESBTPEtudiant::whereIn('id', $studentIds)->get();

            if ($students->isNotEmpty()) {
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $studentIds)
                    ->with(['evaluation', 'evaluation.classe', 'evaluation.matiere']);

                if ($classe_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($classe_id) {
                        $query->where('classe_id', $classe_id);
                    });
                }

                if ($annee_universitaire_id) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($annee_universitaire_id) {
                        $query->where('annee_universitaire_id', $annee_universitaire_id);
                    });
                }

                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre' . $semestre . '%');
                    });
                }

                $notes = $notesQuery->get();

                $this->calculateStudentStatsFixed($students, $notes, $moyennes, $rangs);
            }
        }

        if (!empty($moyennes)) {
            $values = array_values($moyennes);
            $kpis['moyenne_generale'] = round(array_sum($values) / max(count($values), 1), 2);

            $reussites = array_filter($values, function ($moyenne) {
                return $moyenne >= 10;
            });

            $kpis['taux_reussite'] = count($values) > 0
                ? round((count($reussites) / count($values)) * 100, 1)
                : null;
        }

        $bulletinsQuery = ESBTPBulletin::whereIn('etudiant_id', $studentIds);

        if ($classe_id) {
            $bulletinsQuery->where('classe_id', $classe_id);
        }

        if ($annee_universitaire_id) {
            $bulletinsQuery->where('annee_universitaire_id', $annee_universitaire_id);
        }

        if ($semestre) {
            $bulletinsQuery->where('periode', 'semestre' . $semestre);
        }

        $kpis['bulletins_count'] = $bulletinsQuery->count();

        return $kpis;
    }

    /**
     * Build the students query based on filters (extracted from resultats method)
     */
    private function buildEtudiantsQuery($classe_id, $annee_universitaire_id, $include_all_statuses)
    {
        if ($classe_id) {
            // Get students through inscriptions for the selected class and year
            return ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                if (!$include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions.classe.filiere', 'inscriptions.classe.niveau'])
            ->orderBy('nom')
            ->orderBy('prenoms');

        } else if ($annee_universitaire_id) {
            // If no class selected but academic year is set, get all students enrolled in that year
            return ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($annee_universitaire_id, $include_all_statuses) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);

                if (!$include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions' => function ($query) use ($annee_universitaire_id) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);
            }])
            ->orderBy('nom')
            ->orderBy('prenoms');

        } else {
            // If no filters are applied, get all students
            return ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($include_all_statuses) {
                if (!$include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions'])
            ->orderBy('nom')
            ->orderBy('prenoms');
        }
    }

    /**
     * Helper method to get pre-calculated results from ESBTPResultat table
     */
    private function getPreCalculatedResults($etudiants, $classe_id, $annee_universitaire_id, $semestre, &$moyennes, &$rangs)
    {
        \Log::info('Tentative de récupération des résultats pré-calculés', [
            'etudiants_count' => count($etudiants),
            'classe_id' => $classe_id,
            'annee_universitaire_id' => $annee_universitaire_id,
            'semestre' => $semestre
        ]);

        $student_ids = collect($etudiants)->pluck('id')->toArray();
        
        // Récupérer les résultats pré-calculés de la table ESBTPResultat
        $resultatsQuery = \App\Models\ESBTPResultat::whereIn('etudiant_id', $student_ids);
        
        if ($classe_id) {
            $resultatsQuery->where('classe_id', $classe_id);
        }
        
        if ($annee_universitaire_id) {
            $resultatsQuery->where('annee_universitaire_id', $annee_universitaire_id);
        }
        
        if ($semestre) {
            $resultatsQuery->where('periode', 'semestre' . $semestre);
        }
        
        $resultats = $resultatsQuery->get();
        
        \Log::info('Résultats pré-calculés trouvés', [
            'resultats_count' => $resultats->count()
        ]);
        
        // Extraire les moyennes et rangs
        foreach ($resultats as $resultat) {
            if ($resultat->moyenne !== null) {
                $moyennes[$resultat->etudiant_id] = $resultat->moyenne;
            }
            
            if ($resultat->rang !== null) {
                $rangs[$resultat->etudiant_id] = $resultat->rang;
            }
        }
        
        // Si pas de rangs pré-calculés mais on a des moyennes, calculer les rangs
        if (empty($rangs) && !empty($moyennes)) {
            arsort($moyennes);
            $rank = 1;
            foreach (array_keys($moyennes) as $etudiantId) {
                $rangs[$etudiantId] = $rank++;
            }
        }
        
        \Log::info('Résultats pré-calculés récupérés', [
            'moyennes_count' => count($moyennes),
            'rangs_count' => count($rangs)
        ]);
    }

    /**
     * Helper method to calculate student statistics using the same logic as resultatEtudiant
     */
    private function calculateStudentStatsFixed($etudiants, $notes, &$moyennes, &$rangs)
    {
        \Log::info('Calcul des statistiques étudiants - Étudiants: ' . count($etudiants) . ', Notes: ' . count($notes));
        \Log::info('Début du calcul des moyennes (logique corrigée) pour ' . count($etudiants) . ' étudiants avec ' . count($notes) . ' notes');

        // Group notes by student and matière - using the same logic as resultatEtudiant
        $notesByStudentMatiere = [];
        
        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                \Log::warning('Note without evaluation or matière', ['note_id' => $note->id]);
                continue;
            }

            $etudiantId = $note->etudiant_id;
            
            // CORRECTION: Use matiere_id from note directly, then from evaluation as fallback (same as resultatEtudiant)
            $matiere_id = $note->matiere_id;
            if (!$matiere_id && $note->evaluation && $note->evaluation->matiere) {
                $matiere_id = $note->evaluation->matiere->id;
            }
            
            if (!$matiere_id) {
                \Log::warning('Cannot determine matiere_id for note', ['note_id' => $note->id]);
                continue;
            }

            // Initialize student if not exists
            if (!isset($notesByStudentMatiere[$etudiantId])) {
                $notesByStudentMatiere[$etudiantId] = [];
            }

            // Initialize matière for this student if not exists (same structure as resultatEtudiant)
            if (!isset($notesByStudentMatiere[$etudiantId][$matiere_id])) {
                $notesByStudentMatiere[$etudiantId][$matiere_id] = [
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0
                ];
            }

            // Calculate weighted note using EXACT same logic as resultatEtudiant
            if ($note->evaluation->bareme > 0) {
                $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;
                
                if ($noteValue === "Absent" || !is_numeric($noteValue)) {
                    $normalized = 0;
                } else {
                    $normalized = ($noteValue / $bareme) * 20;
                }
                
                $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;
                $ponderation = $normalized * $coefficient;
                
                $notesByStudentMatiere[$etudiantId][$matiere_id]['total_points'] += $ponderation;
                $notesByStudentMatiere[$etudiantId][$matiere_id]['total_coefficients'] += $coefficient;
            }
        }

        // Calculate averages for each student using EXACT same logic as resultatEtudiant
        foreach ($etudiants as $etudiant) {
            if (!isset($notesByStudentMatiere[$etudiant->id])) {
                continue;
            }

            $moyenneGenerale = 0;
            $countValidMatieres = 0;

            // Calculate average for each matière (same as resultatEtudiant)
            foreach ($notesByStudentMatiere[$etudiant->id] as $matiere_id => &$matiereData) {
                if ($matiereData['total_coefficients'] > 0) {
                    $matiereData['moyenne'] = $matiereData['total_points'] / $matiereData['total_coefficients'];
                    // For overall average, treat each matière equally (same as resultatEtudiant)
                    $moyenneGenerale += $matiereData['moyenne'];
                    $countValidMatieres++;
                }
            }

            // Calculate the overall moyenne générale (same as resultatEtudiant)
            if ($countValidMatieres > 0) {
                $moyennes[$etudiant->id] = $moyenneGenerale / $countValidMatieres;
                \Log::debug('Moyenne calculée pour étudiant ' . $etudiant->matricule, [
                    'etudiant_id' => $etudiant->id,
                    'moyenne' => $moyennes[$etudiant->id],
                    'matieres_count' => $countValidMatieres
                ]);
            }
        }

        // Sort by average to calculate ranks
        if (count($moyennes) > 0) {
            arsort($moyennes);
            $rank = 1;
            foreach (array_keys($moyennes) as $etudiantId) {
                $rangs[$etudiantId] = $rank++;
            }
        }

        \Log::info('Calcul des moyennes terminé (logique corrigée):', [
            'moyennes_count' => count($moyennes),
            'rangs_count' => count($rangs)
        ]);
    }

    /**
     * Helper method to calculate student statistics (averages and ranks) - OLD METHOD
     */
    private function calculateStudentStats($etudiants, $notes, &$moyennes, &$rangs)
    {
        // Group notes by student and matière
        $notesByStudentMatiere = [];
        $nonNumericNotes = 0;
        $totalNotesProcessed = 0;

        \Log::info('Début du calcul des moyennes pour ' . count($etudiants) . ' étudiants avec ' . count($notes) . ' notes');

        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                \Log::warning('Note ignorée: absence d\'évaluation ou de matière', [
                    'note_id' => $note->id,
                    'etudiant_id' => $note->etudiant_id
                ]);
                continue; // Skip notes without evaluations or matières
            }

            $etudiantId = $note->etudiant_id;
            $matiereId = $note->evaluation->matiere_id;
            $totalNotesProcessed++;

            if (!isset($notesByStudentMatiere[$etudiantId])) {
                $notesByStudentMatiere[$etudiantId] = [];
            }

            if (!isset($notesByStudentMatiere[$etudiantId][$matiereId])) {
                $notesByStudentMatiere[$etudiantId][$matiereId] = [
                    'notes' => [],
                    'sum' => 0,
                    'coeffSum' => 0
                ];
            }

            // Add note to collection
            $notesByStudentMatiere[$etudiantId][$matiereId]['notes'][] = $note;

            // Calculate weighted note if evaluation has valid bareme
            if ($note->evaluation && $note->evaluation->bareme > 0) {
                // Utiliser note OU valeur (où que la note soit stockée)
                $noteValue = is_numeric($note->note) ? $note->note : $note->valeur;

                // Ajouter une gestion spéciale pour les notes "Absent" ou non numériques
                if ($noteValue === "Absent" || !is_numeric($noteValue)) {
                    // Comptabiliser une absence comme un zéro
                    $normalized = 0;
                    $nonNumericNotes++;
                    \Log::info('Note non numérique détectée', [
                        'etudiant_id' => $etudiantId,
                        'matiere_id' => $matiereId,
                        'note' => $note->note,
                        'valeur' => $note->valeur,
                        'noteValue' => $noteValue,
                        'evaluation_id' => $note->evaluation->id
                    ]);
                } else {
                    $normalized = ($noteValue / $note->evaluation->bareme) * 20;
                    \Log::debug('Note calculée', [
                        'etudiant_id' => $etudiantId,
                        'matiere_id' => $matiereId,
                        'noteValue' => $noteValue,
                        'bareme' => $note->evaluation->bareme,
                        'normalized' => $normalized
                    ]);
                }
                $coefficient = $note->evaluation->coefficient ?? 1;
                $notesByStudentMatiere[$etudiantId][$matiereId]['sum'] += $normalized * $coefficient;
                $notesByStudentMatiere[$etudiantId][$matiereId]['coeffSum'] += $coefficient;
            }
        }

        \Log::info('Notes traitées: ' . $totalNotesProcessed . ' sur ' . count($notes) . ' notes totales');
        \Log::info('Étudiants avec des notes: ' . count($notesByStudentMatiere) . ' sur ' . count($etudiants) . ' étudiants totaux');

        // Calculate average for each student
        foreach ($etudiants as $etudiant) {
            if (!isset($notesByStudentMatiere[$etudiant->id])) {
                \Log::warning('Aucune note trouvée pour l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' (ID: ' . $etudiant->id . ')');
                continue; // Skip if student has no notes
            }

            $totalSum = 0;
            $totalCoeff = 0;
            $matiereCount = 0;

            // Calculate average for each matière, then the overall average
            foreach ($notesByStudentMatiere[$etudiant->id] as $matiereId => $matiereData) {
                $matiereCount++;
                if ($matiereData['coeffSum'] > 0) {
                    $matiereAverage = $matiereData['sum'] / $matiereData['coeffSum'];
                    \Log::debug('Moyenne par matière', [
                        'etudiant_id' => $etudiant->id,
                        'matiere_id' => $matiereId,
                        'sum' => $matiereData['sum'],
                        'coeffSum' => $matiereData['coeffSum'],
                        'average' => $matiereAverage
                    ]);

                    // For overall average, we treat each matière equally for now
                    // You might want to adjust this to use matière coefficients if available
                    $matCoeff = 1; // Default coefficient for matière
                    $totalSum += $matiereAverage * $matCoeff;
                    $totalCoeff += $matCoeff;
                } else {
                    \Log::warning('Matière sans coefficient pour l\'étudiant ' . $etudiant->id, [
                        'matiere_id' => $matiereId,
                        'notes_count' => count($matiereData['notes'])
                    ]);
                }
            }

            if ($totalCoeff > 0) {
                $moyennes[$etudiant->id] = $totalSum / $totalCoeff;
                \Log::info('Moyenne calculée pour l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms, [
                    'etudiant_id' => $etudiant->id,
                    'moyenne' => $moyennes[$etudiant->id],
                    'matieres_count' => $matiereCount
                ]);
            } else {
                \Log::warning('Impossible de calculer la moyenne pour l\'étudiant ' . $etudiant->nom . ' ' . $etudiant->prenoms, [
                    'etudiant_id' => $etudiant->id,
                    'totalCoeff' => $totalCoeff,
                    'matieres_count' => $matiereCount
                ]);
            }
        }

        // Sort by average to calculate ranks
        arsort($moyennes);
        $rank = 1;
        foreach (array_keys($moyennes) as $etudiantId) {
            $rangs[$etudiantId] = $rank++;
        }

        // Log the calculated averages for debugging
        \Log::info('Calcul des moyennes terminé:', [
            'moyennes_count' => count($moyennes),
            'rangs_count' => count($rangs),
            'non_numeric_notes' => $nonNumericNotes,
            'total_notes_processed' => $totalNotesProcessed
        ]);
    }

    /**
     * Helper method to get bulletins for students
     */
    private function getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, &$bulletins)
    {
        $periodeMap = [
            '1' => 'semestre1',
            '2' => 'semestre2',
        ];

        // Si le semestre est spécifié, on récupère seulement ce semestre
        // Sinon, on récupère tous les semestres
        $periodes = [];
        if ($semestre && isset($periodeMap[$semestre])) {
            $periodes[] = $periodeMap[$semestre];
        } else {
            // Si aucun semestre n'est spécifié, on récupère tous les semestres
            $periodes = array_values($periodeMap);
        }

        \Log::info('Récupération des bulletins pour ' . count($etudiants) . ' étudiants', [
            'annee_universitaire_id' => $annee_universitaire_id,
            'semestre' => $semestre,
            'periodes' => $periodes
        ]);

        foreach ($etudiants as $etudiant) {
            // If no specific class is provided, get the student's class from inscriptions
            $studentClasseId = $classe_id;
            if (!$studentClasseId) {
                $inscription = $etudiant->inscriptions
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('status', 'active')
                    ->first();
                $studentClasseId = $inscription ? $inscription->classe_id : null;
            }

            if ($studentClasseId && $annee_universitaire_id && !empty($periodes)) {
                $query = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                    ->where('classe_id', $studentClasseId)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                // Si on a des périodes spécifiques, on les utilise
                // Sinon, on récupère tous les bulletins pour cet étudiant dans cette classe et cette année
                if (count($periodes) == 1) {
                    $query->where('periode', $periodes[0]);
                } else {
                    $query->whereIn('periode', $periodes);
                }

                $bulletin = $query->first();

                if ($bulletin) {
                    $bulletins[$etudiant->id] = $bulletin->id;
                    \Log::debug('Bulletin trouvé pour étudiant', [
                        'etudiant_id' => $etudiant->id,
                        'bulletin_id' => $bulletin->id,
                        'classe_id' => $studentClasseId,
                        'periode' => $bulletin->periode
                    ]);
                } else {
                    \Log::warning('Aucun bulletin trouvé pour étudiant', [
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $studentClasseId,
                        'periodes' => $periodes
                    ]);
                }
            } else {
                \Log::warning('Données insuffisantes pour récupérer le bulletin', [
                    'etudiant_id' => $etudiant->id,
                    'studentClasseId' => $studentClasseId,
                    'annee_universitaire_id' => $annee_universitaire_id,
                    'periodes' => $periodes
                ]);
            }
        }
    }

    /**
     * Affiche le bulletin de l'étudiant connecté.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function monBulletin(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Récupérer l'étudiant associé à l'utilisateur
        $etudiant = $user->etudiant;

        if (!$etudiant) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre compte utilisateur n\'est pas associé à un étudiant.');
        }

        // Récupérer les paramètres de filtre
        $anneeId = $request->input('annee_universitaire_id',
            ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null);
        $periode = $request->input('periode');

        // Récupérer l'inscription active de l'étudiant
        $inscription = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', 'active')
            ->first();

        if (!$inscription) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous n\'êtes pas inscrit pour l\'année universitaire sélectionnée.');
        }

        // Récupérer la classe de l'étudiant
        $classe = $inscription->classe;

        if (!$classe) {
            return redirect()->route('dashboard')
                ->with('error', 'Votre inscription n\'est associée à aucune classe.');
        }

        // Récupérer le bulletin de l'étudiant
        $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeId);

        if ($periode) {
            $bulletin = $bulletin->where('periode', $periode);
        }

        $bulletin = $bulletin->first();

        // Si le bulletin n'existe pas encore, on affiche un message
        if (!$bulletin) {
            // Récupérer toutes les années universitaires pour le filtre
            $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

            return view('esbtp.bulletin.mon-bulletin', compact(
                'etudiant',
                'classe',
                'anneeId',
                'periode',
                'anneesUniversitaires'
            ))->with('warning', 'Le bulletin n\'est pas encore disponible pour la période sélectionnée.');
        }

        // Récupérer les détails du bulletin
        $detailsBulletin = ESBTPBulletinDetail::where('bulletin_id', $bulletin->id)
            ->with(['matiere'])
            ->get();

        // Regrouper les détails par UE si nécessaire
        $detailsParUE = [];

        foreach ($detailsBulletin as $detail) {
            $ueId = $detail->matiere->ue_id ?? 'sans_ue';
            if (!isset($detailsParUE[$ueId])) {
                $detailsParUE[$ueId] = [
                    'ue' => $detail->matiere->ue ?? null,
                    'details' => []
                ];
            }
            $detailsParUE[$ueId]['details'][] = $detail;
        }

        // Calculer les statistiques globales
        $moyenneGenerale = $bulletin->moyenne_generale;
        $rangGeneral = $bulletin->rang;
        $effectifClasse = $bulletin->effectif_classe;
        $creditsTotaux = $detailsBulletin->sum('credits_valides');
        $decisionConseil = $bulletin->decision_conseil;

        // Récupérer toutes les années universitaires pour le filtre
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        return view('esbtp.bulletin.mon-bulletin', compact(
            'etudiant',
            'classe',
            'bulletin',
            'detailsBulletin',
            'detailsParUE',
            'moyenneGenerale',
            'rangGeneral',
            'effectifClasse',
            'creditsTotaux',
            'decisionConseil',
            'anneeId',
            'periode',
            'anneesUniversitaires'
        ));
    }

    /**
     * Affiche les bulletins de l'étudiant connecté.
     *
     * @return \Illuminate\Http\Response
     */
    public function studentBulletins()
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // 1. Récupérer l'année universitaire courante
        $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$anneeCourante) {
            return view('esbtp.bulletins.mon-bulletin', [
                'bulletins' => collect([]),
                'etudiant' => $etudiant,
                'anneeCourante' => null,
                'inscription' => null,
            ]);
        }

        // 2. Vérifier si l'étudiant a une inscription active pour l'année courante
        $inscription = $etudiant->inscriptions()
            ->where('status', 'active')
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
            ->first();

        if (!$inscription) {
            return view('esbtp.bulletins.mon-bulletin', [
                'bulletins' => collect([]),
                'etudiant' => $etudiant,
                'anneeCourante' => $anneeCourante,
                'inscription' => null,
            ])->with('warning', 'Vous n\'avez pas d\'inscription active pour l\'année en cours. Veuillez contacter l\'administration.');
        }

        // 3. Récupérer les bulletins de l'année courante uniquement
        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->with(['classe', 'anneeUniversitaire'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Pour chaque bulletin, calculer les données via le BulletinService si nécessaire
        foreach ($bulletins as $bulletin) {
            try {
                // Utiliser le BulletinService pour obtenir les vraies données
                $donnees = $this->bulletinService->genererDonneesBulletin(
                    $etudiant->id,
                    $bulletin->classe_id,
                    $bulletin->annee_universitaire_id,
                    $bulletin->periode
                );

                $bulletin->moyenne_generale = $donnees['moyenneAvecAssiduite'];
                $bulletin->rang = $donnees['rang'];
                $bulletin->effectif_classe = $donnees['effectif'];
                $bulletin->mention = $donnees['appreciation'];

            } catch (\Exception $e) {
                // Si le bulletin n'est pas encore configuré, on garde les données par défaut
                $bulletin->moyenne_generale = null;
                $bulletin->rang = null;
                $bulletin->effectif_classe = null;
                $bulletin->mention = 'Non disponible';
            }
        }

        return view('esbtp.bulletins.mon-bulletin', compact('bulletins', 'etudiant', 'anneeCourante', 'inscription'));
    }

    /**
     * Affiche un bulletin spécifique de l'étudiant connecté
     */
    public function showStudentBulletin($bulletinId)
    {
        $user = Auth::user();
        $etudiant = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$etudiant) {
            return redirect()->route('dashboard')->with('error', 'Profil étudiant non trouvé.');
        }

        // Récupérer le bulletin en s'assurant qu'il appartient à l'étudiant connecté
        $bulletin = ESBTPBulletin::where('id', $bulletinId)
            ->where('etudiant_id', $etudiant->id)
            ->with(['classe', 'anneeUniversitaire'])
            ->first();

        if (!$bulletin) {
            return redirect()->route('mon-bulletin.index')->with('error', 'Bulletin non trouvé ou non autorisé.');
        }

        try {
            // Utiliser le BulletinService unifié pour générer les données
            $donnees = $this->bulletinService->genererDonneesBulletin(
                $etudiant->id,
                $bulletin->classe_id,
                $bulletin->annee_universitaire_id,
                $bulletin->periode
            );

            // Ajouter le logo pour l'affichage (les settings sont déjà fournis par le service)
            $logoBase64 = $this->prepareLogoBase64($donnees['settings']['school_logo'] ?? null);
            $donnees['logoBase64'] = $logoBase64;

            // Utiliser exactement le même template que la preview admin
            return view('esbtp.bulletins.pdf-configurable', $donnees);

        } catch (\Exception $e) {
            // Gestion des erreurs de configuration
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                return redirect()->route('mon-bulletin.index')
                    ->with('error', 'Ce bulletin n\'est pas encore configuré ou disponible.');
            }
            
            return redirect()->route('mon-bulletin.index')
                ->with('error', 'Erreur lors de l\'affichage du bulletin : ' . $e->getMessage());
        }
    }

    /**
     * Signe un bulletin par un responsable
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @param  string  $role
     * @return \Illuminate\Http\Response
     */
    public function signer(ESBTPBulletin $bulletin, $role)
    {
        if (!in_array($role, ['directeur', 'responsable', 'parent'])) {
            return back()->with('error', 'Rôle de signature invalide.');
        }

        try {
            $bulletin->signer($role);
            return back()->with('success', 'Bulletin signé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la signature: ' . $e->getMessage());
        }
    }

    /**
     * Bascule l'état de publication d'un bulletin
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return \Illuminate\Http\Response
     */
    public function togglePublication(ESBTPBulletin $bulletin)
    {
        try {
            $wasPublished = $bulletin->is_published;
            $bulletin->is_published = !$bulletin->is_published;
            $bulletin->save();

            // Si le bulletin vient d'être publié, notifier les parents
            if (!$wasPublished && $bulletin->is_published) {
                try {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->notifyParentsBulletinPublished($bulletin);

                    // Vérifier si l'étudiant a des notes faibles et envoyer une alerte si nécessaire
                    $notificationService->notifyParentsLowGrades($bulletin);
                } catch (\Exception $e) {
                    \Log::error('Erreur envoi notification bulletin aux parents: ' . $e->getMessage());
                }
            }

            $message = $bulletin->is_published
                ? 'Le bulletin a été publié avec succès.'
                : 'Le bulletin a été dépublié avec succès.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du changement de statut: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les bulletins en attente (non publiés ou non signés)
     *
     * @return \Illuminate\Http\Response
     */
    public function pending()
    {
        // Récupérer les bulletins qui ne sont pas publiés ou qui n'ont pas toutes les signatures
        $bulletins = ESBTPBulletin::where('is_published', false)
            ->orWhere(function($query) {
                $query->where('signature_responsable', false)
                      ->orWhere('signature_directeur', false);
            })
            ->with(['etudiant', 'classe', 'anneeUniversitaire'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Statistiques
        $totalPending = ESBTPBulletin::where('is_published', false)->count();
        $totalNonSigned = ESBTPBulletin::where('is_published', true)
            ->where(function($query) {
                $query->where('signature_responsable', false)
                      ->orWhere('signature_directeur', false);
            })->count();

        return view('esbtp.bulletins.pending', compact('bulletins', 'totalPending', 'totalNonSigned'));
    }

    /**
     * Affiche les résultats des étudiants d'une classe spécifique
     *
     * @param ESBTPClasse $classe
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function resultatClasse(Request $request, $id)
    {
        $this->validate($request, [
            'semestre' => 'nullable|in:1,2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $semestre = $request->semestre;
        $periode = $semestre; // Map semestre to periode for view compatibility
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses');

        // Get current academic year if not specified (utiliser is_current au lieu de is_active)
        if (!$annee_universitaire_id) {
                $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_current', true)->first()->id ?? null;
        }

        $classe_id = $id;
        $classe = ESBTPClasse::with(['matieres' => function($query) {
            $query->withPivot('coefficient');
        }])->findOrFail($classe_id);

        // Get students through inscriptions for the selected class and year
        $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
            $query->where('classe_id', $classe_id)
                ->where('annee_universitaire_id', $annee_universitaire_id);

            // CORRECTION: Si include_all_statuses = false, alors filtrer sur 'active' uniquement
            if (!$include_all_statuses) {
                $query->where('status', 'active');
            }
        });

        $students = $studentsQuery->get();

        \Log::info('Classe Results Query', [
            'classe_id' => $classe_id,
            'semestre' => $semestre,
            'annee_universitaire_id' => $annee_universitaire_id,
            'include_all_statuses' => $include_all_statuses,
            'students_count' => $students->count()
        ]);

        // Get all notes for these students
        $notes = [];
        if ($students->count() > 0) {
            $student_ids = $students->pluck('id')->toArray();

            // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
            $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

            // Si un semestre est spécifié, filtrer par ce semestre
            if ($semestre) {
                $notesQuery->where(function ($q) use ($semestre) {
                        $q->where('semestre', $semestre)
                            ->whereHas('evaluation', function ($query) use ($semestre) {
                                $query->where('periode', 'semestre'.$semestre);
                            });
                    });
            }

            $notes = $notesQuery->get();

            \Log::info('Notes récupérées pour la classe', [
                'classe_id' => $classe_id,
                'notes_count' => $notes->count(),
                'semestre' => $semestre ? $semestre : 'Toutes les périodes'
            ]);
        }

        // Périodes disponibles (format compatible avec la vue)
        $periodes = [
            'semestre1' => 'Premier Semestre',
            'semestre2' => 'Deuxième Semestre'
        ];

        // Récupérer toutes les années universitaires (on affiche libelle ou name dans la vue)
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('id', 'desc')->get();

        // Group notes by student and then by matière
        $notesByStudentMatiere = [];

        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                continue; // Skip notes without evaluation or matière
            }

            $etudiantId = $note->etudiant_id;
            $matiereId = $note->evaluation->matiere_id;

            if (!isset($notesByStudentMatiere[$etudiantId])) {
                $notesByStudentMatiere[$etudiantId] = [];
            }

            if (!isset($notesByStudentMatiere[$etudiantId][$matiereId])) {
                $notesByStudentMatiere[$etudiantId][$matiereId] = [
                    'sum' => 0,
                    'coeffSum' => 0,
                    'matiere' => $note->evaluation->matiere
                ];
            }

            // CORRECTION : Ajout du coefficient de l'évaluation
            $evaluationCoefficient = $note->evaluation->coefficient ?? 1;
            $normalized = ($note->valeur / $note->evaluation->bareme) * 20;

            $notesByStudentMatiere[$etudiantId][$matiereId]['sum'] += $normalized * $evaluationCoefficient;
            $notesByStudentMatiere[$etudiantId][$matiereId]['coeffSum'] += $evaluationCoefficient;
        }

        // Calculate averages for each student and matière
        $resultats = [];

        foreach ($students as $student) {
            $moyenne = 0;
            $totalPoints = 0;
            $totalCoefficients = 0;

            if (isset($notesByStudentMatiere[$student->id])) {
                $studentMatieres = $notesByStudentMatiere[$student->id];

                foreach ($studentMatieres as $matiereId => $matiereData) {
                    if ($matiereData['coeffSum'] > 0) {
                        // Calcul de la moyenne de la matière
                        $matiereMoyenne = $matiereData['sum'] / $matiereData['coeffSum'];

                        // Récupération du coefficient de la matière dans la classe
                        $matiereClasse = $classe->matieres()->where('matiere_id', $matiereId)->first();
                        $matiereCoefficient = $matiereClasse ? $matiereClasse->pivot->coefficient : 1;

                        // Application du coefficient de la matière
                        $totalPoints += $matiereMoyenne * $matiereCoefficient;
                        $totalCoefficients += $matiereCoefficient;
                    }
                }

                if ($totalCoefficients > 0) {
                    $moyenne = $totalPoints / $totalCoefficients;
                }
            }

            $resultats[] = [
                'etudiant' => $student,
                'moyenne' => $moyenne,
                'total_coefficients' => $totalCoefficients,
                'notes_count' => $notes->where('etudiant_id', $student->id)->count()
            ];
        }

        // Sort resultats by moyenne in descending order
        usort($resultats, function ($a, $b) {
            return $b['moyenne'] <=> $a['moyenne'];
        });

        // Define annee_id for view consistency
        $annee_id = $annee_universitaire_id;

        // Récupérer l'objet année universitaire pour la vue
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // Utiliser le bon nom de variable pour compatibilité avec la vue
        $anneesUniversitaires = $annees_universitaires;

        return view('esbtp.resultats.classe', compact(
            'classe',
            'students',
            'notes',
            'semestre',
            'periode',
            'periodes',
            'annee_universitaire_id',
            'annee_id',
            'anneesUniversitaires',
            'anneeUniversitaire',
            'resultats',
            'include_all_statuses'
        ));
    }

    /**
     * Affiche les résultats détaillés d'un étudiant spécifique
     *
     * @param ESBTPEtudiant $etudiant
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function resultatEtudiant(Request $request, $id)
    {
        $this->validate($request, [
            'semestre' => 'nullable|in:1,2',
            'periode' => 'nullable|in:1,2,semestre1,semestre2', // Support des deux formats
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        // Gérer les deux paramètres: semestre et periode (compatibilité)
        $semestreRaw = $request->semestre ?? $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // CORRECTION: Conversion du format du semestre pour compatibilité avec le format attendu
        // Gérer les formats : 1, 2, semestre1, semestre2
        if ($semestreRaw == '1') {
            $periode = 'semestre1';
            $semestre = '1';
        } elseif ($semestreRaw == '2') {
            $periode = 'semestre2';
            $semestre = '2';
        } elseif ($semestreRaw == 'semestre1') {
            $periode = 'semestre1';
            $semestre = '1';
        } elseif ($semestreRaw == 'semestre2') {
            $periode = 'semestre2';
            $semestre = '2';
        } else {
            $periode = 'semestre1';
            $semestre = '1';
        }

        \Log::debug('Valeurs des variables pour la génération de PDF:', [
            'semestre' => $semestre,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        $include_all_statuses = $request->has('include_all_statuses');

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
        }

        // For view compatibility
        $annee_id = $annee_universitaire_id;

        $etudiant = ESBTPEtudiant::with('user')->findOrFail($id);

        // Get inscription for the student in the specified academic year
        $inscriptionQuery = $etudiant->inscriptions()
            ->where('annee_universitaire_id', $annee_universitaire_id);

        if (!$include_all_statuses) {
            $inscriptionQuery->where('status', 'active');
        }

        $inscription = $inscriptionQuery->first();

        $classe_id = $inscription->classe_id ?? $request->classe_id ?? null;
        $classe = $classe_id ? ESBTPClasse::with('filiere')->find($classe_id) : null;
        // Get the academic year object for display
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);
        // Get all active classes for the filter dropdown
        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get();
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $periodes = [
            (object)['id' => '1', 'nom' => 'Semestre 1'],
            (object)['id' => '2', 'nom' => 'Semestre 2']
        ];

        // Get notes for the student
        $notesQuery = ESBTPNote::where('etudiant_id', $id)
            ->with(['evaluation', 'evaluation.matiere']);

        // Si un semestre est spécifié, filtrer par ce semestre
        if ($semestre) {
            \Log::info('Filtrage par semestre:', ['semestre' => $semestre]);
            $notesQuery->where(function ($q) use ($semestre) {
                $q->where('semestre', $semestre)
                  ->orWhereHas('evaluation', function ($query) use ($semestre) {
                      $query->where('periode', 'semestre'.$semestre)
                            ->orWhere('periode', $semestre);
                  });
            });
        }

        $notes = $notesQuery->get();

        \Log::info('Student Result Notes', [
            'student_id' => $id,
            'semestre' => $semestre ? $semestre : 'Toutes les périodes',
            'include_all_statuses' => $include_all_statuses,
            'notes_count' => $notes->count()
        ]);

        // Group notes by matière (subject)
        $notesByMatiere = [];
        $totalPoints = 0;
        $totalCoefficients = 0;
        $nonNumericNotes = 0;

        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                \Log::warning('Note without evaluation or matière', ['note_id' => $note->id]);
                continue; // Skip notes without evaluation or matière
            }

            // CORRECTION: Prioriser l'ID de matière stocké directement sur la note si disponible
            $matiere_id = $note->matiere_id;
            if (!$matiere_id && $note->evaluation && $note->evaluation->matiere) {
            $matiere_id = $note->evaluation->matiere->id;
            }

            // Skip if we still can't determine the matiere_id
            if (!$matiere_id) {
                \Log::warning('Cannot determine matiere_id for note', ['note_id' => $note->id]);
                continue;
            }

            // Récupérer la matière directement depuis la base de données pour éviter toute confusion
            $matiere = \App\Models\ESBTPMatiere::find($matiere_id);
            if (!$matiere) {
                \Log::warning("Matiere with ID {$matiere_id} not found for note ID {$note->id} - skipping note");
                continue;
            }

            // Initialize if this is the first note for this matière
            if (!isset($notesByMatiere[$matiere_id])) {
                $notesByMatiere[$matiere_id] = [
                    'matiere' => $matiere, // Use the freshly retrieved matiere
                    'notes' => [],
                    'calculations' => [], // Add storage for calculations
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0
                ];
                \Log::debug("Initialized new entry in notesByMatiere for matiere {$matiere->name} (ID: {$matiere->id})");
            }

            // CORRECTION AMÉLIORÉE: Vérification supplémentaire pour s'assurer que nous traitons la bonne note
            \Log::debug("Note {$note->id} VALUE CHECK: note field = {$note->note}, valeur field = {$note->valeur}");

            // Only use notes with evaluations that have a valid bareme
            if ($note->evaluation->bareme > 0) {
                // CORRECTION AMÉLIORÉE: Accès direct aux valeurs numériques pour éviter tout problème de
                // conversion ou de référence. Utiliser la fonction floatval pour s'assurer que nous avons une valeur numérique.
                $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;

                if ($noteValue === "Absent" || !is_numeric($noteValue)) {
                    $normalized = 0;
                    $nonNumericNotes++;
                } else {
                    $normalized = ($noteValue / $bareme) * 20;
                }

                $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;
                $ponderation = $normalized * $coefficient;

                \Log::debug("CALCULATION for note {$note->id}: noteValue={$noteValue}, coefficient={$coefficient}, bareme={$bareme} => ponderation={$ponderation}");

                // CORRECTION AMÉLIORÉE: Ajouter explicitement les valeurs aux tableaux en utilisant des structures claires
                // Cela évite tout problème de référence ou de partage d'objets en mémoire
                $noteRef = [
                    'id' => $note->id,
                    'value' => $noteValue,
                    'coefficient' => $coefficient,
                    'ponderation' => $ponderation,
                    'normalized' => $normalized
                ];

                // Store both the calculation structure AND the original note object to maintain view compatibility
                $notesByMatiere[$matiere_id]['notes'][] = $note; // Keep the full note object for the view
                $notesByMatiere[$matiere_id]['calculations'][] = $noteRef; // Store calculations separately
                $notesByMatiere[$matiere_id]['total_points'] += $ponderation;
                $notesByMatiere[$matiere_id]['total_coefficients'] += $coefficient;
            }
        }

        // Calculate average for each matière and overall weighted average
        $moyenneGenerale = 0;
        $countValidMatieres = 0;

        foreach ($notesByMatiere as $matiere_id => &$matiereData) {
            if ($matiereData['total_coefficients'] > 0) {
                $matiereData['moyenne'] = $matiereData['total_points'] / $matiereData['total_coefficients'];

                // For overall average, we treat each matière equally
                // You might want to adjust this to use matière coefficients
                $moyenneGenerale += $matiereData['moyenne'];
                $countValidMatieres++;
            }
        }

        // Calculate the overall moyenne générale
        $moyenneGenerale = $countValidMatieres > 0 ? $moyenneGenerale / $countValidMatieres : 0;

        // NOUVELLE LOGIQUE: Intégrer les moyennes manuelles depuis esbtp_resultats (même logique que previewMoyennes)
        $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $id)
            ->when($classe_id, function($query) use ($classe_id) {
                return $query->where('classe_id', $classe_id);
            })
            ->when($periode, function($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->with('matiere')
            ->get();

        // Intégrer les moyennes manuelles (elles l'emportent sur les calculées - priorité Manuel)
        $moyenneGeneraleRecalculee = 0;
        $countMatieresFinales = 0;
        
        foreach ($resultats as $resultat) {
            if (!$resultat->matiere) continue;
            
            $matiere_id = $resultat->matiere_id;
            
            // Si la matière n'existe pas encore dans notesByMatiere, la créer
            if (!isset($notesByMatiere[$matiere_id])) {
                $notesByMatiere[$matiere_id] = [
                    'matiere' => $resultat->matiere,
                    'notes' => [],
                    'calculations' => [],
                    'total_points' => 0,
                    'total_coefficients' => $resultat->coefficient,
                    'moyenne' => 0
                ];
            }
            
            // Les moyennes manuelles écrasent toujours les calculées
            $notesByMatiere[$matiere_id]['moyenne'] = $resultat->moyenne;
            $notesByMatiere[$matiere_id]['source'] = 'manuelle';
            $notesByMatiere[$matiere_id]['total_coefficients'] = $resultat->coefficient;
        }
        
        // Marquer les moyennes calculées qui n'ont pas été écrasées
        foreach ($notesByMatiere as $matiere_id => &$matiereData) {
            if (!isset($matiereData['source'])) {
                $matiereData['source'] = 'calculee';
            }
        }
        
        // Recalculer la moyenne générale avec toutes les matières (Auto + Manuel)
        $moyenneGeneraleRecalculee = 0;
        $countMatieresFinales = 0;
        foreach ($notesByMatiere as $matiere_id => $matiereData) {
            if ($matiereData['moyenne'] > 0) {
                $moyenneGeneraleRecalculee += $matiereData['moyenne'];
                $countMatieresFinales++;
            }
        }
        $moyenneGenerale = $countMatieresFinales > 0 ? $moyenneGeneraleRecalculee / $countMatieresFinales : 0;

        \Log::info('Student Result Calculations', [
            'student_id' => $id,
            'matieres_count' => count($notesByMatiere),
            'moyenne_generale' => $moyenneGenerale,
            'non_numeric_notes' => $nonNumericNotes
        ]);

        return view('esbtp.resultats.etudiant', compact(
            'etudiant',
            'classe',
            'anneeUniversitaire',
            'notes',
            'notesByMatiere',
            'moyenneGenerale',
            'semestre',
            'periode',
            'annee_universitaire_id',
            'annee_id',
            'classes',
            'anneesUniversitaires',
            'periodes'
        ));
    }

    /**
     * Affiche l'interface d'édition groupée des résultats d'une classe
     *
     * @param ESBTPClasse $classe
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function editResultatsClasse(Request $request, $id)
    {
        $this->validate($request, [
            'semestre' => 'nullable|in:1,2',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $semestre = $request->semestre;
        $periode = $semestre ? 'semestre'.$semestre : null;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses');

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_current', true)->first()->id ?? null;
        }

        $classe_id = $id;
        $classe = ESBTPClasse::with(['matieres' => function($query) {
            $query->withPivot('coefficient');
        }, 'filiere', 'niveau'])->findOrFail($classe_id);

        // Get students through inscriptions
        $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
            $query->where('classe_id', $classe_id)
                ->where('annee_universitaire_id', $annee_universitaire_id);

            if (!$include_all_statuses) {
                $query->where('status', 'active');
            }
        })->with('user');

        $students = $studentsQuery->get()->sortBy(function($student) {
            return $student->nom . ' ' . $student->prenoms;
        })->values();

        // Get enseignants from planning général based on class filiere + niveau combination
        // Récupérer les planifications pour la combinaison filière + niveau de cette classe
        $planifications = \DB::table('esbtp_planifications_academiques')
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('annee_universitaire_id', $annee_universitaire_id)
            ->pluck('id');

        // Récupérer les enseignants assignés dans ces planifications (via planning général)
        $enseignantIdsFromPlanning = \DB::table('esbtp_planification_teachers')
            ->whereIn('planification_id', $planifications)
            ->pluck('teacher_id')
            ->unique();

        // Récupérer les enseignants avec leurs infos complètes
        $enseignants = \App\Models\ESBTPTeacher::with('user')
            ->whereIn('id', $enseignantIdsFromPlanning)
            ->where('is_active', true)
            ->get();

        // Fallback: si aucun enseignant trouvé dans planning général, prendre tous les actifs
        if ($enseignants->isEmpty()) {
            \Log::warning('Aucun enseignant trouvé dans planning général pour classe', [
                'classe_id' => $classe_id,
                'filiere_id' => $classe->filiere_id,
                'niveau_id' => $classe->niveau_etude_id
            ]);
            $enseignants = \App\Models\ESBTPTeacher::with('user')->where('is_active', true)->get();
        }

        // Get all matieres for the class based on filiere + niveau combination
        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $matieres = ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use ($classeFiliereId, $classeNiveauId) {
                if (!$classeFiliereId || !$classeNiveauId) {
                    return false;
                }
                return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                    && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
            })
            ->values()
            ->map(function (ESBTPMatiere $matiere) use ($classe) {
                // Get coefficient from pivot table if exists, otherwise use default
                $pivot = $classe->matieres()->where('matiere_id', $matiere->id)->first();
                $matiere->pivot = (object)[
                    'coefficient' => $pivot ? $pivot->pivot->coefficient : ($matiere->coefficient ?? $matiere->coefficient_default ?? 1)
                ];
                return $matiere;
            });

        // Get existing resultats for this class/periode/annee
        $resultats = \App\Models\ESBTPResultat::where('classe_id', $classe_id)
            ->when($periode, function($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->with(['etudiant', 'matiere'])
            ->get()
            ->groupBy('etudiant_id');

        // NOUVELLE LOGIQUE: Calculer les moyennes automatiques depuis les évaluations pour chaque étudiant
        $moyennesCalculees = [];
        foreach ($students as $student) {
            $moyennesCalculees[$student->id] = $this->calculateMoyennesForStudent(
                $student->id,
                $classe_id,
                $periode,
                $annee_universitaire_id,
                $matieres
            );
        }

        // Get absences from bulletins table (not esbtp_absences which is for cours tracking)
        $absences = ESBTPBulletin::whereIn('etudiant_id', $students->pluck('id'))
            ->where('classe_id', $classe_id)
            ->when($periode, function($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->get()
            ->keyBy('etudiant_id');

        // Périodes disponibles
        $periodes = [
            'semestre1' => 'Premier Semestre',
            'semestre2' => 'Deuxième Semestre'
        ];

        // Récupérer toutes les années universitaires
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // KPIs
        $kpis = [
            'total_students' => $students->count(),
            'total_matieres' => $matieres->count(),
            'total_resultats' => $resultats->sum(function($group) { return $group->count(); }),
            'completion_rate' => $students->count() > 0 && $matieres->count() > 0
                ? round(($resultats->sum(function($group) { return $group->count(); }) / ($students->count() * $matieres->count())) * 100, 1)
                : 0
        ];

        // Récupérer les enseignants spécifiques à chaque matière depuis planning général
        $enseignantsParMatiere = [];
        foreach ($matieres as $matiere) {
            // Récupérer la planification pour cette matière + combinaison classe
            $planification = \DB::table('esbtp_planifications_academiques')
                ->where('matiere_id', $matiere->id)
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            if ($planification) {
                // Récupérer les enseignants assignés dans cette planification
                $enseignantIds = \DB::table('esbtp_planification_teachers')
                    ->where('planification_id', $planification->id)
                    ->pluck('teacher_id');

                // Récupérer les enseignants avec leurs infos complètes
                $enseignantsMatiere = \App\Models\ESBTPTeacher::with('user')
                    ->whereIn('id', $enseignantIds)
                    ->where('is_active', true)
                    ->get();

                $enseignantsParMatiere[$matiere->id] = $enseignantsMatiere;
            } else {
                // Fallback: utiliser tous les enseignants si planning non configuré
                $enseignantsParMatiere[$matiere->id] = $enseignants;
            }
        }

        // Récupérer les professeurs déjà assignés depuis les bulletins pour pré-remplir le modal
        $professeursGroupes = [];

        // Prendre un bulletin exemple pour cette classe/période (peu importe l'étudiant car professeurs identiques)
        $sampleBulletin = ESBTPBulletin::where('classe_id', $classe_id)
            ->when($periode, function($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->when($annee_universitaire_id, function($query) use ($annee_universitaire_id) {
                return $query->where('annee_universitaire_id', $annee_universitaire_id);
            })
            ->whereNotNull('professeurs')
            ->first();

        if ($sampleBulletin && $sampleBulletin->professeurs) {
            // Décode le JSON: {"matiere_id": "Teacher Name", ...}
            $professeursJson = json_decode($sampleBulletin->professeurs, true) ?: [];

            \Log::info('📋 Professeurs déjà assignés récupérés depuis bulletin', [
                'bulletin_id' => $sampleBulletin->id,
                'professeurs_json' => $professeursJson
            ]);

            // Mapper les noms vers les IDs des enseignants
            foreach ($professeursJson as $matiereId => $teacherName) {
                // Ignorer les valeurs null ou vides
                if (empty($teacherName)) {
                    continue;
                }

                // Chercher l'enseignant par nom dans la liste des enseignants disponibles pour cette matière
                $enseignantsDeMatiere = $enseignantsParMatiere[$matiereId] ?? collect();

                $foundTeacher = $enseignantsDeMatiere->first(function($enseignant) use ($teacherName) {
                    return $enseignant->user && $enseignant->user->name === $teacherName;
                });

                if ($foundTeacher) {
                    $professeursGroupes[$matiereId] = $foundTeacher->id;
                    \Log::debug("✅ Mapped matiere {$matiereId}: '{$teacherName}' → teacher_id {$foundTeacher->id}");
                } else {
                    \Log::warning("⚠️ Could not find teacher_id for matiere {$matiereId}: '{$teacherName}'");
                }
            }

            \Log::info('🔄 Mapping professeurs groupés final', [
                'professeursGroupes' => $professeursGroupes
            ]);
        } else {
            \Log::info('ℹ️ Aucun bulletin avec professeurs trouvé pour pré-remplir le modal', [
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);
        }

        return view('esbtp.resultats.classe-edit', compact(
            'classe',
            'students',
            'matieres',
            'enseignants',
            'enseignantsParMatiere',
            'professeursGroupes',
            'resultats',
            'absences',
            'semestre',
            'periode',
            'periodes',
            'annee_universitaire_id',
            'annees_universitaires',
            'anneeUniversitaire',
            'include_all_statuses',
            'kpis',
            'moyennesCalculees'
        ));
    }

    /**
     * Récupérer les moyennes existantes pour les étudiants sélectionnés
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMoyennes(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'nullable|exists:esbtp_matieres,id',
            'matiere_ids' => 'nullable|array',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|in:1,2', // OBLIGATOIRE
            'etudiant_ids' => 'required|array'
        ]);

        $periode = $request->semestre ? 'semestre' . $request->semestre : null;

        $query = ESBTPResultat::where('classe_id', $request->classe_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->when($periode, function($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->whereIn('etudiant_id', $request->etudiant_ids);

        // Handle single matiere_id or multiple matiere_ids
        if ($request->has('matiere_id') && $request->matiere_id) {
            $query->where('matiere_id', $request->matiere_id);
        } elseif ($request->has('matiere_ids') && !empty($request->matiere_ids)) {
            $query->whereIn('matiere_id', $request->matiere_ids);
        }

        $resultats = $query->get();

        // NOUVELLE LOGIQUE: Calculer les moyennes automatiques pour enrichir les résultats
        // Récupérer les matières concernées
        $matiereIds = $request->has('matiere_id') && $request->matiere_id
            ? [$request->matiere_id]
            : ($request->has('matiere_ids') ? $request->matiere_ids : []);

        $matieres = !empty($matiereIds)
            ? ESBTPMatiere::whereIn('id', $matiereIds)->get()
            : ESBTPMatiere::where('is_active', true)->get();

        // Calculer les moyennes pour chaque étudiant
        $moyennesCalculees = [];
        foreach ($request->etudiant_ids as $etudiantId) {
            $moyennesCalculees[$etudiantId] = $this->calculateMoyennesForStudent(
                $etudiantId,
                $request->classe_id,
                $periode,
                $request->annee_universitaire_id,
                $matieres
            );
        }

        // Enrichir les résultats avec les moyennes calculées et la source
        $resultatsEnriched = [];
        foreach ($resultats as $resultat) {
            $resultatArray = $resultat->toArray();
            $matiereId = $resultat->matiere_id;
            $etudiantId = $resultat->etudiant_id;

            // Ajouter la moyenne calculée et la source
            if (isset($moyennesCalculees[$etudiantId][$matiereId])) {
                $resultatArray['moyenne_calculee'] = $moyennesCalculees[$etudiantId][$matiereId]['moyenne'];
                $resultatArray['source'] = $moyennesCalculees[$etudiantId][$matiereId]['source'];
            } else {
                $resultatArray['moyenne_calculee'] = null;
                $resultatArray['source'] = 'manuelle';
            }

            $resultatsEnriched[] = $resultatArray;
        }

        // Ajouter les moyennes calculées pour les matières sans résultat existant
        foreach ($request->etudiant_ids as $etudiantId) {
            if (!isset($moyennesCalculees[$etudiantId])) {
                continue;
            }

            foreach ($moyennesCalculees[$etudiantId] as $matiereId => $moyenneData) {
                // Vérifier si ce couple (etudiant, matiere) existe déjà dans les résultats
                $exists = collect($resultatsEnriched)->contains(function($r) use ($etudiantId, $matiereId) {
                    return $r['etudiant_id'] == $etudiantId && $r['matiere_id'] == $matiereId;
                });

                if (!$exists && $moyenneData['moyenne'] !== null) {
                    // Ajouter un résultat virtuel avec moyenne calculée
                    $resultatsEnriched[] = [
                        'id' => null,
                        'etudiant_id' => $etudiantId,
                        'matiere_id' => $matiereId,
                        'classe_id' => $request->classe_id,
                        'annee_universitaire_id' => $request->annee_universitaire_id,
                        'periode' => $periode,
                        'moyenne' => $moyenneData['moyenne'], // Pré-remplir avec moyenne calculée
                        'moyenne_calculee' => $moyenneData['moyenne'],
                        'source' => $moyenneData['source'],
                        'coefficient' => null,
                        'rang' => null,
                        'appreciation' => null
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'resultats' => $resultatsEnriched
        ]);
    }

    /**
     * Récupérer les absences existantes pour les étudiants sélectionnés
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAbsences(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|in:1,2', // OBLIGATOIRE
            'etudiant_ids' => 'required|array'
        ]);

        $periode = $request->semestre ? 'semestre' . $request->semestre : null;

        // Sélectionner seulement les colonnes de la table, pas les accessors
        // pour éviter l'erreur "Call to a member function getRelationExistenceQuery() on null"
        $bulletins = ESBTPBulletin::select([
                'id',
                'etudiant_id',
                'classe_id',
                'annee_universitaire_id',
                'periode',
                'absences_justifiees',
                'absences_non_justifiees',
                'total_absences'
            ])
            ->where('classe_id', $request->classe_id)
            ->where('annee_universitaire_id', $request->annee_universitaire_id)
            ->when($periode, function($query) use ($periode) {
                return $query->where('periode', $periode);
            })
            ->whereIn('etudiant_id', $request->etudiant_ids)
            ->get();

        return response()->json([
            'success' => true,
            'bulletins' => $bulletins
        ]);
    }

    /**
     * Mise à jour groupée des moyennes par matière
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateMoyennes(Request $request)
    {
        // Validation stricte : le semestre est OBLIGATOIRE pour éviter les erreurs
        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|in:1,2', // OBLIGATOIRE
            'moyennes' => 'required|array',
            'moyennes.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'moyennes.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'moyennes.*.moyenne' => 'nullable|numeric|min:0|max:20'
        ]);

        \DB::beginTransaction();
        try {
            $updated = 0;
            $created = 0;

            // Convertir semestre en periode
            $periode = 'semestre' . $request->semestre;

            foreach ($request->moyennes as $moyenneData) {
                if (!isset($moyenneData['moyenne']) || $moyenneData['moyenne'] === null || $moyenneData['moyenne'] === '') {
                    continue;
                }

                // Construire les conditions de recherche (toujours avec periode)
                $conditions = [
                    'etudiant_id' => $moyenneData['etudiant_id'],
                    'classe_id' => $request->classe_id,
                    'matiere_id' => $moyenneData['matiere_id'],
                    'periode' => $periode,
                    'annee_universitaire_id' => $request->annee_universitaire_id
                ];

                // Pas besoin de champ 'type' - la présence dans esbtp_resultats suffit
                // Le système détecte automatiquement "manuel" si le résultat existe en BDD
                $resultat = \App\Models\ESBTPResultat::updateOrCreate(
                    $conditions,
                    [
                        'moyenne' => $moyenneData['moyenne'],
                        'coefficient' => $moyenneData['coefficient'] ?? 1
                    ]
                );

                if ($resultat->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Moyennes mises à jour avec succès. ($created créées, $updated modifiées)",
                'stats' => [
                    'created' => $created,
                    'updated' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update moyennes: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des moyennes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des professeurs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateProfesseurs(Request $request)
    {
        \Log::info('🔵 bulkUpdateProfesseurs - START', [
            'request_data' => $request->all()
        ]);

        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'periode' => 'required|in:semestre1,semestre2',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'professeurs' => 'required|array',
            'professeurs.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'professeurs.*.enseignant_id' => 'nullable|exists:esbtp_teachers,id'  // Table correcte: esbtp_teachers
        ]);

        \DB::beginTransaction();
        try {
            // Créer un tableau associatif matiere_id => teacher_name
            $professeursMap = [];
            foreach ($request->professeurs as $profData) {
                if (isset($profData['enseignant_id']) && $profData['enseignant_id']) {
                    // Récupérer le nom du professeur depuis esbtp_teachers
                    $teacher = \App\Models\ESBTPTeacher::with('user')->find($profData['enseignant_id']);
                    if ($teacher && $teacher->user) {
                        $professeursMap[$profData['matiere_id']] = $teacher->user->name;
                        \Log::info('✅ Teacher found', [
                            'matiere_id' => $profData['matiere_id'],
                            'teacher_id' => $profData['enseignant_id'],
                            'teacher_name' => $teacher->user->name
                        ]);
                    }
                }
            }

            \Log::info('📋 professeursMap created', [
                'count' => count($professeursMap),
                'data' => $professeursMap
            ]);


            // Récupérer TOUS les étudiants actifs de la classe
            $etudiants = \App\Models\ESBTPEtudiant::whereHas('inscriptions', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id)
                  ->where('annee_universitaire_id', $request->annee_universitaire_id)
                  ->where('status', 'active');
            })->get();

            $updated = 0;
            $created = 0;

            foreach ($etudiants as $etudiant) {
                // Récupérer ou créer le bulletin pour cet étudiant
                $bulletin = \App\Models\ESBTPBulletin::firstOrCreate(
                    [
                        'etudiant_id' => $etudiant->id,
                        'classe_id' => $request->classe_id,
                        'periode' => $request->periode,
                        'annee_universitaire_id' => $request->annee_universitaire_id
                    ],
                    [
                        'professeurs' => json_encode($professeursMap),
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]
                );

                // Si le bulletin existait déjà, fusionner les professeurs
                if (!$bulletin->wasRecentlyCreated) {
                    $professeursExistants = json_decode($bulletin->professeurs, true) ?: [];

                    // FIX: S'assurer que c'est un tableau associatif (objet JSON), pas un tableau indexé
                    // Si $professeursExistants est un tableau indexé [0=>val1, 1=>val2], le convertir en objet vide
                    if (array_keys($professeursExistants) === range(0, count($professeursExistants) - 1)) {
                        // C'est un tableau indexé, réinitialiser
                        \Log::warning('⚠️ Professeurs existants était un tableau indexé, réinitialisation', [
                            'bulletin_id' => $bulletin->id,
                            'before' => $professeursExistants
                        ]);
                        $professeursExistants = [];
                    }

                    // Fusion en préservant les clés matiere_id
                    $professeursFusionnes = $professeursExistants + $professeursMap;
                    // array_replace pour que les nouvelles valeurs écrasent les anciennes
                    $professeursFusionnes = array_replace($professeursExistants, $professeursMap);

                    // Encoder en JSON - préserver format objet même si toutes les clés sont nulles
                    $professeursJson = json_encode($professeursFusionnes);
                    // Si c'est un tableau vide [], le forcer en objet {}
                    if ($professeursJson === '[]') {
                        $professeursJson = '{}';
                    }

                    \Log::info('📝 Updating bulletin', [
                        'bulletin_id' => $bulletin->id,
                        'etudiant_id' => $etudiant->id,
                        'before' => $bulletin->professeurs,
                        'after' => $professeursJson,
                        'before_type' => gettype(json_decode($bulletin->professeurs)),
                        'after_type' => gettype(json_decode($professeursJson))
                    ]);

                    // FORCE update avec Query Builder au lieu d'Eloquent pour éviter le cache
                    $affected = \DB::table('esbtp_bulletins')
                        ->where('id', $bulletin->id)
                        ->update([
                            'professeurs' => $professeursJson,
                            'updated_by' => auth()->id(),
                            'updated_at' => now()
                        ]);

                    \Log::info('📝 Update result', [
                        'bulletin_id' => $bulletin->id,
                        'affected_rows' => $affected,
                        'professeurs_json_length' => strlen($professeursJson)
                    ]);

                    $updated++;
                } else {
                    \Log::info('🆕 Created bulletin', [
                        'bulletin_id' => $bulletin->id,
                        'etudiant_id' => $etudiant->id,
                        'professeurs' => $bulletin->professeurs
                    ]);
                    $created++;
                }
            }

            \DB::commit();

            \Log::info('✅ bulkUpdateProfesseurs - SUCCESS', [
                'created' => $created,
                'updated' => $updated,
                'total_students' => $etudiants->count(),
                'professeurs_count' => count($professeursMap)
            ]);

            return response()->json([
                'success' => true,
                'message' => "✅ Professeurs assignés avec succès pour " . count($professeursMap) . " matière(s) - $created bulletin(s) créé(s), $updated bulletin(s) mis à jour",
                'stats' => [
                    'created_bulletins' => $created,
                    'updated_bulletins' => $updated,
                    'total_students' => $etudiants->count(),
                    'updated_matieres' => count($professeursMap)
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update professeurs: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des professeurs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des absences
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateAbsences(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'semestre' => 'required|in:1,2',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'absences' => 'required|array',
            'absences.*.etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'absences.*.absences_justifiees' => 'nullable|numeric|min:0',
            'absences.*.absences_non_justifiees' => 'nullable|numeric|min:0'
        ]);

        \DB::beginTransaction();
        try {
            $updated = 0;
            $created = 0;
            $periode = 'semestre' . $request->semestre;

            foreach ($request->absences as $absenceData) {
                $justifiees = floatval($absenceData['absences_justifiees'] ?? 0);
                $nonJustifiees = floatval($absenceData['absences_non_justifiees'] ?? 0);

                if ($justifiees == 0 && $nonJustifiees == 0) {
                    continue;
                }

                // Calculer la note d'assiduité selon le barème
                $noteAssiduite = $this->calculerNoteAssiduite($justifiees, $nonJustifiees);

                // Create or update bulletin avec les absences
                $bulletin = ESBTPBulletin::updateOrCreate(
                    [
                        'etudiant_id' => $absenceData['etudiant_id'],
                        'classe_id' => $request->classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $request->annee_universitaire_id
                    ],
                    [
                        'absences_justifiees' => $justifiees,
                        'absences_non_justifiees' => $nonJustifiees,
                        'total_absences' => $justifiees + $nonJustifiees,
                        'note_assiduite' => $noteAssiduite,
                        'absences_type' => 'manuel',
                        'updated_by' => \Auth::id()
                    ]
                );

                if ($bulletin->wasRecentlyCreated) {
                    $bulletin->created_by = \Auth::id();
                    $bulletin->save();
                    $created++;
                } else {
                    $updated++;
                }
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Absences mises à jour avec succès. ($created bulletins créés, $updated modifiés)",
                'stats' => [
                    'created' => $created,
                    'updated' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update absences: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des absences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des matières (configuration)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateMatieres(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matieres' => 'required|array',
            'matieres.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'matieres.*.coefficient' => 'required|numeric|min:0'
        ]);

        \DB::beginTransaction();
        try {
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $updated = 0;

            foreach ($request->matieres as $matiereData) {
                // Update pivot table coefficient
                $classe->matieres()->updateExistingPivot(
                    $matiereData['matiere_id'],
                    ['coefficient' => $matiereData['coefficient']]
                );
                $updated++;
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Configuration des matières mise à jour avec succès. ($updated matières modifiées)",
                'stats' => [
                    'updated' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update matières: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des matières: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise à jour groupée des coefficients des matières
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateCoefficients(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'coefficients' => 'required|array'
        ]);

        \DB::beginTransaction();
        try {
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $updated = 0;

            foreach ($request->coefficients as $matiereId => $coefficient) {
                // Update pivot table coefficient
                $classe->matieres()->updateExistingPivot(
                    $matiereId,
                    ['coefficient' => floatval($coefficient)]
                );
                $updated++;
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Coefficients mis à jour avec succès. ($updated matières modifiées)",
                'stats' => [
                    'updated' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update coefficients: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des coefficients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise à jour groupée de la configuration des matières (coefficients + types d'enseignement)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateMatieresConfig(Request $request)
    {
        $this->validate($request, [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'semestre' => 'required|in:1,2',
            'coefficients' => 'nullable|array',
            'matiere_types' => 'nullable|array'
        ]);

        \DB::beginTransaction();
        try {
            $classe = ESBTPClasse::findOrFail($request->classe_id);
            $periode = 'semestre' . $request->semestre;
            $updatedCoeff = 0;
            $updatedTypes = 0;

            // Mise à jour des coefficients dans la table pivot esbtp_classe_matiere
            if ($request->has('coefficients') && is_array($request->coefficients)) {
                foreach ($request->coefficients as $matiereId => $coefficient) {
                    $classe->matieres()->updateExistingPivot(
                        $matiereId,
                        ['coefficient' => floatval($coefficient)]
                    );
                    $updatedCoeff++;
                }
            }

            // Mise à jour des types d'enseignement dans esbtp_config_matiere (même logique que saveConfigMatieresTypeFormation)
            if ($request->has('matiere_types') && is_array($request->matiere_types)) {
                foreach ($request->matiere_types as $matiereId => $type) {
                    if ($type === 'none') {
                        // Si "none", on supprime la config
                        ESBTPConfigMatiere::where([
                            'matiere_id' => $matiereId,
                            'classe_id' => $request->classe_id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $request->annee_universitaire_id
                        ])->forceDelete();
                        continue;
                    }

                    // Sinon on crée/met à jour la config avec type général ou technique
                    ESBTPConfigMatiere::withTrashed()->updateOrCreate(
                        [
                            'matiere_id' => $matiereId,
                            'classe_id' => $request->classe_id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $request->annee_universitaire_id
                        ],
                        [
                            'config' => json_encode(['type' => $type]),
                            'created_by' => \Auth::id(),
                            'updated_by' => \Auth::id(),
                            'deleted_at' => null
                        ]
                    );
                    $updatedTypes++;
                }
            }

            \DB::commit();

            $messages = [];
            if ($updatedCoeff > 0) {
                $messages[] = "$updatedCoeff coefficient(s) modifié(s)";
            }
            if ($updatedTypes > 0) {
                $messages[] = "$updatedTypes type(s) d'enseignement configuré(s)";
            }

            $message = count($messages) > 0
                ? "✅ Configuration mise à jour : " . implode(', ', $messages)
                : "✅ Configuration mise à jour avec succès";

            return response()->json([
                'success' => true,
                'message' => $message,
                'stats' => [
                    'coefficients_updated' => $updatedCoeff,
                    'types_updated' => $updatedTypes
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('❌ Erreur bulk update config matières: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère un PDF à partir des paramètres fournis (étudiant, classe, période, année universitaire)
     * sans nécessiter un bulletin existant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    /**
     * Prévisualise le bulletin avant génération PDF
     */
    public function previewBulletin(Request $request)
    {
        try {
            // Validation des paramètres
            $validator = Validator::make($request->all(), [
                'etudiant' => 'required|exists:esbtp_etudiants,id',
                'classe' => 'required|exists:esbtp_classes,id',
                'annee' => 'required|exists:esbtp_annee_universitaires,id'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Récupérer les données
            $etudiant = ESBTPEtudiant::findOrFail($request->etudiant);
            $classe = ESBTPClasse::with(['filiere', 'niveauEtude'])->findOrFail($request->classe);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($request->annee);

            // Essayer de récupérer un bulletin existant pour avoir les configurations
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant->id)
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->first();

            // Récupérer les matières de la classe via la relation pivot
            $matieres = $classe->matieres()
                ->where('esbtp_matieres.is_active', true)
                ->orderBy('esbtp_matieres.name')
                ->get();

            // Grouper les matières par filière
            $matieresByFiliere = $matieres->groupBy(function ($matiere) use ($classe) {
                return $classe->filiere->name ?? 'Non défini';
            });

            // Récupérer les évaluations pour ces matières
            $evaluations = ESBTPEvaluation::whereIn('matiere_id', $matieres->pluck('id'))
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->where('status', '!=', 'cancelled')
                ->orderBy('titre')
                ->get();

            // Grouper les évaluations par matière
            $evaluationsParMatiere = $evaluations->groupBy('matiere_id');

            // Récupérer les notes de l'étudiant
            $notes = ESBTPNote::where('etudiant_id', $etudiant->id)
                ->whereIn('evaluation_id', $evaluations->pluck('id'))
                ->get()
                ->keyBy('evaluation_id');

            // Convertir en tableau simple pour la vue
            $notesParEvaluation = [];
            foreach ($notes as $evaluationId => $note) {
                $notesParEvaluation[$evaluationId] = $note->note;
            }

            // Calculer les moyennes par matière avec pondération (automatiques)
            $moyennesAutomatiques = [];
            foreach ($matieres as $matiere) {
                if (isset($evaluationsParMatiere[$matiere->id])) {
                    $evaluationsMatiere = $evaluationsParMatiere[$matiere->id];
                    $totalPoints = 0;
                    $totalCoeffs = 0;
                    
                    foreach ($evaluationsMatiere as $evaluation) {
                        if (isset($notesParEvaluation[$evaluation->id])) {
                            $totalPoints += $notesParEvaluation[$evaluation->id] * $evaluation->coefficient;
                            $totalCoeffs += $evaluation->coefficient;
                        }
                    }
                    
                    $moyennesAutomatiques[$matiere->id] = $totalCoeffs > 0 ? $totalPoints / $totalCoeffs : 0;
                } else {
                    $moyennesAutomatiques[$matiere->id] = 0;
                }
            }

            // NOUVELLE LOGIQUE: Intégrer les moyennes manuelles (priorité Manuel l'emporte)
            $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $anneeUniversitaire->id)
                ->with('matiere')
                ->get();

            // Commencer avec les moyennes automatiques
            $moyennesMatiere = $moyennesAutomatiques;
            
            // Écraser avec les moyennes manuelles (elles l'emportent toujours)
            foreach ($resultats as $resultat) {
                if ($resultat->matiere) {
                    $moyennesMatiere[$resultat->matiere_id] = $resultat->moyenne;
                }
            }

            // Récupérer les configurations si le bulletin existe
            $configMatieres = [];
            $professeurs = [];
            if ($bulletin) {
                $configMatieres = json_decode($bulletin->config_matieres, true) ?: [];
                $professeurs = json_decode($bulletin->professeurs, true) ?: [];
            }

            // Préparer le logo et configuration PDF
            $logoBase64 = null;
            $config = $this->getPDFConfig();
            $schoolInfo = [
                'name' => $config['school_name'],
                'address' => $config['school_address'],
                'phone' => $config['school_phone'],
                'email' => $config['school_email'],
                'city' => $config['school_city'],
                'country' => $config['school_country'],
                'logo' => null
            ];
            if (!empty($config['school_logo'])) {
                $logoPath = $config['school_logo'];
                $fullPath = public_path($logoPath);
                
                if (file_exists($fullPath)) {
                    $logoType = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $logoData = file_get_contents($fullPath);
                    $logoBase64 = base64_encode($logoData);
                    $schoolInfo['logo'] = $config['school_logo'];
                }
            }

            // Statistiques de la classe
            $totalEtudiants = ESBTPEtudiant::where('classe_id', $classe->id)->count();

            return view('esbtp.bulletins.preview', compact(
                'etudiant',
                'classe',
                'anneeUniversitaire',
                'matieres',
                'matieresByFiliere',
                'evaluationsParMatiere',
                'notesParEvaluation',
                'moyennesMatiere',
                'configMatieres',
                'professeurs',
                'bulletin',
                'config',
                'schoolInfo',
                'logoBase64',
                'totalEtudiants'
            ));

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la prévisualisation du bulletin: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la prévisualisation du bulletin.');
        }
    }

    /**
     * Prévisualise le bulletin depuis la page résultats étudiant
     */
    public function previewBulletinEtudiantNew(Request $request, $etudiantId)
    {
        try {
            // Validation des paramètres
            $validator = Validator::make($request->all(), [
                'classe_id' => 'required|exists:esbtp_classes,id',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
                'periode' => 'nullable|in:semestre1,semestre2,1,2'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Déterminer la période
            $periode = $request->periode ?? 'semestre1';
            if (in_array($periode, ['1', '2'])) {
                $periode = 'semestre' . $periode;
            }

            // Utiliser le service pour générer les données
            $donnees = $this->bulletinService->genererDonneesBulletin(
                $etudiantId,
                $request->classe_id,
                $request->annee_universitaire_id,
                $periode
            );

            // Préparer le logo pour l'affichage (les settings sont déjà fournis par le service)
            $logoBase64 = $this->prepareLogoBase64($donnees['settings']['school_logo'] ?? null);
            $donnees['logoBase64'] = $logoBase64;

            return view('esbtp.bulletins.pdf-configurable', $donnees);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $request->classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'bulletin' => $etudiantId
                ]);

                return redirect($configMatieresUrl)->with('error', $e->getMessage());
            }
            
            return redirect()->back()->with('error', 'Erreur lors de la génération de la preview : ' . $e->getMessage());
        }
    }

    public function previewBulletinEtudiantOld(Request $request, $etudiantId)
    {
        try {
            // Validation des paramètres
            $validator = Validator::make($request->all(), [
                'classe_id' => 'required|exists:esbtp_classes,id',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Récupérer les données
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
            $classe = ESBTPClasse::with(['filiere', 'niveauEtude'])->findOrFail($request->classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($request->annee_universitaire_id);
            $periode = 'semestre1'; // Par défaut
            
            // Récupérer le bulletin pour obtenir les professeurs configurés
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiantId)
                ->where('classe_id', $request->classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->first();

            // VÉRIFICATION OBLIGATOIRE : S'assurer que la configuration existe
            if (!$bulletin || !$bulletin->config_matieres || !$bulletin->professeurs) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $request->classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'bulletin' => $etudiantId
                ]);
                
                $message = 'Configuration bulletin manquante. ';
                if (!$bulletin || !$bulletin->config_matieres) {
                    $message .= 'Veuillez d\'abord configurer les matières et ';
                }
                if (!$bulletin || !$bulletin->professeurs) {
                    $message .= 'les professeurs ';
                }
                $message .= 'avant de prévisualiser le bulletin.';
                
                return redirect($configMatieresUrl)->with('error', $message);
            }

            // Vérifier que la configuration n'est pas vide
            $configMatieres = json_decode($bulletin->config_matieres, true);
            $professeursConfigures = json_decode($bulletin->professeurs, true);
            
            if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $request->classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'bulletin' => $etudiantId
                ]);
                
                return redirect($configMatieresUrl)->with('error', 'Aucune matière n\'est configurée pour ce bulletin. Veuillez configurer au moins une matière.');
            }

            // Récupérer seulement les matières qui ont des évaluations avec notes pour cet étudiant
            $notesAvecEvaluations = ESBTPNote::where('etudiant_id', $etudiant->id)
                ->with(['evaluation.matiere'])
                ->whereHas('evaluation', function($q) use ($anneeUniversitaire) {
                    $q->where('annee_universitaire_id', $anneeUniversitaire->id)
                      ->where('status', '!=', 'cancelled');
                })
                ->get();

            // Créer des résultats par matière (comme dans l'ancien système)
            $resultatsParMatiere = [];
            
            // Récupérer les professeurs et configuration matières depuis le bulletin
            // (On sait maintenant qu'ils existent grâce à la vérification ci-dessus)
            $professeursConfigures = json_decode($bulletin->professeurs, true) ?: [];
            $configMatieres = json_decode($bulletin->config_matieres, true) ?: ['generales' => [], 'techniques' => []];
            
            $professeurs = [];

            foreach ($notesAvecEvaluations as $note) {
                if ($note->evaluation && $note->evaluation->matiere) {
                    $matiere = $note->evaluation->matiere;
                    $matiereId = $matiere->id;

                    if (!isset($resultatsParMatiere[$matiereId])) {
                        // Déterminer le type de formation selon la configuration du bulletin uniquement
                        if (in_array($matiereId, $configMatieres['generales'] ?? [])) {
                            $typeFormation = 'generale';
                        } elseif (in_array($matiereId, $configMatieres['techniques'] ?? [])) {
                            $typeFormation = 'technologique_professionnelle';
                        } else {
                            // Cette matière n'est pas configurée, on ne devrait pas arriver ici
                            // grâce aux vérifications précédentes
                            $typeFormation = 'generale';
                        }
                        
                        $resultatsParMatiere[$matiereId] = (object)[
                            'id' => $matiereId,
                            'matiere_id' => $matiereId,
                            'matiere' => $matiere,
                            'notes' => [],
                            'moyenne' => 0,
                            'coefficient' => $this->getCoefficient($matiere),
                            'rang' => '-',
                            'appreciation' => '',
                            'type_formation' => $typeFormation
                        ];
                    }

                    $resultatsParMatiere[$matiereId]->notes[] = [
                        'note' => $note->note,
                        'coefficient' => $note->evaluation->coefficient
                    ];

                    // Utiliser uniquement les professeurs configurés (pas de fallback par défaut)
                    $professeurs[$matiereId] = $professeursConfigures[$matiereId] ?? '';
                }
            }

            // Calculer les moyennes pondérées pour chaque matière (automatiques)
            foreach ($resultatsParMatiere as $matiereId => $resultat) {
                $totalPoints = 0;
                $totalCoeffs = 0;
                
                foreach ($resultat->notes as $noteData) {
                    $totalPoints += $noteData['note'] * $noteData['coefficient'];
                    $totalCoeffs += $noteData['coefficient'];
                }
                
                $resultat->moyenne = $totalCoeffs > 0 ? $totalPoints / $totalCoeffs : 0;
                $resultat->appreciation = $this->getAppreciation($resultat->moyenne);
            }

            // NOUVELLE LOGIQUE: Intégrer les moyennes manuelles (priorité Manuel l'emporte)
            $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiantId)
                ->where('classe_id', $request->classe_id)
                ->where('annee_universitaire_id', $request->annee_universitaire_id)
                ->with('matiere')
                ->get();

            // Ajouter les matières qui ont seulement des moyennes manuelles (sans évaluations)
            foreach ($resultats as $resultatManuel) {
                $matiereId = $resultatManuel->matiere_id;
                
                if ($resultatManuel->matiere) {
                    // Si la matière n'existe pas encore dans les résultats, l'ajouter
                    if (!isset($resultatsParMatiere[$matiereId])) {
                        // Déterminer le type selon la configuration du bulletin
                        if (in_array($matiereId, $configMatieres['generales'] ?? [])) {
                            $typeFormation = 'generale';
                        } elseif (in_array($matiereId, $configMatieres['techniques'] ?? [])) {
                            $typeFormation = 'technologique_professionnelle';
                        } else {
                            $typeFormation = 'generale'; // Par défaut
                        }
                        
                        $resultatsParMatiere[$matiereId] = (object)[
                            'id' => $matiereId,
                            'matiere_id' => $matiereId,
                            'matiere' => $resultatManuel->matiere,
                            'notes' => [],
                            'moyenne' => $resultatManuel->moyenne,
                            'coefficient' => $resultatManuel->coefficient ?: $this->getCoefficient($resultatManuel->matiere),
                            'rang' => '-',
                            'appreciation' => $resultatManuel->appreciation ?: $this->getAppreciation($resultatManuel->moyenne),
                            'type_formation' => $typeFormation
                        ];
                        
                        // Configurer le professeur si disponible
                        if (!isset($professeurs[$matiereId])) {
                            $professeurs[$matiereId] = $professeursConfigures[$matiereId] ?? '';
                        }
                    } else {
                        // Écraser avec les moyennes manuelles (elles l'emportent toujours)
                        $resultatsParMatiere[$matiereId]->moyenne = $resultatManuel->moyenne;
                        $resultatsParMatiere[$matiereId]->appreciation = $resultatManuel->appreciation ?: $this->getAppreciation($resultatManuel->moyenne);
                        if ($resultatManuel->coefficient) {
                            $resultatsParMatiere[$matiereId]->coefficient = $resultatManuel->coefficient;
                        }
                    }
                }
            }

            // Séparer par type d'enseignement
            $resultatsGeneraux = collect($resultatsParMatiere)->filter(function($resultat) {
                return $resultat->type_formation == 'generale';
            });

            $resultatsTechniques = collect($resultatsParMatiere)->filter(function($resultat) {
                return $resultat->type_formation == 'technologique_professionnelle';
            });

            // Calculer les moyennes par section
            $moyenneGenerale = $this->calculerMoyennePonderee($resultatsGeneraux);
            $moyenneTechnique = $this->calculerMoyennePonderee($resultatsTechniques);
            $moyenneGlobale = $this->calculerMoyennePonderee(collect($resultatsParMatiere));

            // Calcul des absences et note d'assiduité en utilisant le service
            $absences = $this->absenceService->calculerDetailAbsences(
                $etudiant->id, 
                $classe->id, 
                $anneeUniversitaire->date_debut, 
                $anneeUniversitaire->date_fin
            );
            $noteAssiduite = $this->calculerNoteAssiduite($absences['justifiees'], $absences['non_justifiees']);
            $moyenneAvecAssiduite = $moyenneGlobale + $noteAssiduite; // La note d'assiduité est un bonus/malus, pas une moyenne

            // Rang de l'étudiant (simplifié)
            $rang = '1'; // À calculer selon vos critères

            // Effectif de la classe
            $effectif = ESBTPEtudiant::whereHas('inscriptions', function($q) use ($classe, $anneeUniversitaire) {
                $q->where('classe_id', $classe->id)
                  ->where('annee_universitaire_id', $anneeUniversitaire->id);
            })->count();
            
            // Calculer les vraies statistiques de classe
            $statsClasse = $this->calculerStatistiquesClasse($classe->id, $anneeUniversitaire->id);
            $meilleure_moyenne = $statsClasse['meilleure_moyenne'];
            $plus_faible_moyenne = $statsClasse['plus_faible_moyenne']; 
            $moyenne_classe = $statsClasse['moyenne_classe'];
            
            // Déterminer l'appréciation selon la moyenne
            $appreciation = $this->getAppreciation($moyenneGlobale);

            // Préparer le logo en utilisant la méthode existante
            $config = $this->getPDFConfig();
            $logoBase64 = $this->prepareLogoBase64($config['school_logo']);

            // Préparer les données pour la vue (utiliser le template pdf-configurable)
            $settings = $this->getPDFConfig();
            
            return view('esbtp.bulletins.pdf-configurable', [
                'etudiant' => $etudiant,
                'classe' => $classe,
                'anneeUniversitaire' => $anneeUniversitaire,
                'periode' => $periode,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneTechnique' => $moyenneTechnique,
                'moyenneGlobale' => $moyenneGlobale,
                'moyenneAvecAssiduite' => $moyenneAvecAssiduite,
                'professeurs' => $professeurs,
                'absencesJustifiees' => $absences['justifiees'],
                'absencesNonJustifiees' => $absences['non_justifiees'],
                'note_assiduite' => $noteAssiduite,
                'rang' => $rang,
                'effectif' => $effectif,
                'logoBase64' => $logoBase64,
                'settings' => $settings,
                'date_edition' => now()->format('d/m/Y'),
                'absences_justifiees' => $absences['justifiees'],
                'absences_non_justifiees' => $absences['non_justifiees'],
                'meilleure_moyenne' => $meilleure_moyenne,
                'plus_faible_moyenne' => $plus_faible_moyenne,
                'moyenne_classe' => $moyenne_classe,
                'appreciation' => $appreciation
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la prévisualisation du bulletin étudiant: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la prévisualisation du bulletin.');
        }
    }

    /**
     * Récupère la liste des professeurs par défaut
     */
    private function getProfesseursParDefaut()
    {
        return [
            // Matières d'enseignement général
            'Anglais' => 'M.FOFANA Lassina',
            'Gestion' => 'M.YAO YAOBLE',
            'Informatique' => 'Mme MANDOUA Nadège',
            'Mathématiques' => 'M.BONE Oussama',
            'Physique' => 'M.KOFFI Bruno',
            'Technique d\'Expression Française' => 'M.DJE Charles',
            'Communication' => 'M.KOUADIO Paul',
            'Économie' => 'Mme KONAN Sarah',
            'Droit' => 'M.KOUAME Jean',
            // Matières techniques/professionnelles
            'Aménagement foncier cadastre' => 'M.ASSALE Arsène',
            'Calculs Topo' => 'M.YAO Niamba',
            'CAO-DAO' => 'M.KIGNELMAN Christian',
            'Architecture' => 'M.KOUADIO Serge',
            'Construction Métallique' => 'M.TRAORE Ibrahim',
            'Béton Armé' => 'M.KONE Moussa',
            'Topographie' => 'M.BROU Emmanuel',
            'Géotechnique' => 'M.N\'GUESSAN Paul'
        ];
    }

    /**
     * Récupère le coefficient d'une matière
     */
    private function getCoefficient($matiere)
    {
        // Retourner le coefficient configuré ou par défaut
        return $matiere->coefficient ?? 2;
    }

    /**
     * Génère l'appréciation selon la moyenne
     */
    private function getAppreciation($moyenne)
    {
        if ($moyenne >= 16) return 'Très Bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez Bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Insuffisant';
    }

    /**
     * Calcule la moyenne pondérée d'une collection de résultats
     */
    private function calculerMoyennePonderee($resultats)
    {
        if ($resultats->isEmpty()) return 0;

        $totalPoints = 0;
        $totalCoeffs = 0;

        foreach ($resultats as $resultat) {
            $totalPoints += $resultat->moyenne * $resultat->coefficient;
            $totalCoeffs += $resultat->coefficient;
        }

        return $totalCoeffs > 0 ? $totalPoints / $totalCoeffs : 0;
    }

    /**
     * Calcule les absences d'un étudiant
     */
    private function calculerAbsences($etudiantId, $classeId, $anneeUniversitaireId)
    {
        try {
            // Récupérer les absences depuis la table ESBTPAbsence
            $absences = ESBTPAbsence::where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->get();

            $justifiees = 0;
            $nonJustifiees = 0;

            foreach ($absences as $absence) {
                $heures = $absence->heures ?? 1;
                if ($absence->justifiee) {
                    $justifiees += $heures;
                } else {
                    $nonJustifiees += $heures;
                }
            }

            return [
                'justifiees' => $justifiees,
                'non_justifiees' => $nonJustifiees,
                'total' => $justifiees + $nonJustifiees
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur calcul absences: ' . $e->getMessage());
            return [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0
            ];
        }
    }
    
    public function genererPDFParParamsUnified(Request $request)
    {
        try {
            // Vérifier que l'utilisateur est autorisé
            if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
            }

            // Récupérer les paramètres
            $classe_id = $request->classe_id;
            $etudiant_id = $request->etudiant_id ?? $request->bulletin;
            $periode = $request->periode ?? 'semestre1';
            $annee_universitaire_id = $request->annee_universitaire_id;

            // Utiliser le BulletinService unifié pour générer les données
            $donnees = $this->bulletinService->genererDonneesBulletin(
                $etudiant_id,
                $classe_id,
                $annee_universitaire_id,
                $periode
            );

            // Ajouter le logo pour le PDF
            $config = $this->getPDFConfig();
            $logoBase64 = $this->prepareLogoBase64($config['school_logo']);
            $donnees['logoBase64'] = $logoBase64;
            
            // Indiquer que c'est un export PDF pour cacher les éléments web
            $donnees['isPdfExport'] = true;

            // Générer le PDF avec le template unifié
            $pdf = PDF::loadView('esbtp.bulletins.pdf-configurable', $donnees);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150, 
                'defaultFont' => 'DejaVu Sans',
                'isPhpEnabled' => true,
                'chroot' => public_path()
            ]);

            // Nom du fichier PDF
            $filename = 'bulletin_' .
                        ($donnees['etudiant']->matricule ?? 'unknown') . '_' .
                        ($donnees['classe']->code ?? 'unknown') . '_' .
                        $periode . '_' .
                        ($donnees['anneeUniversitaire']->libelle ?? 'unknown') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            // Gestion des erreurs de configuration
            if (str_contains($e->getMessage(), 'Configuration bulletin manquante')) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $request->classe_id,
                    'periode' => $request->periode ?? 'semestre1',
                    'annee_universitaire_id' => $request->annee_universitaire_id,
                    'bulletin' => $request->etudiant_id ?? $request->bulletin
                ]);
                
                return redirect($configMatieresUrl)->with('error', $e->getMessage());
            }
            
            return back()->with('error', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    public function genererPDFParParams(Request $request)
    {
        try {
            // Vérifier que l'utilisateur est autorisé - Restreindre aux superAdmin uniquement
            if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
            }

            // Récupérer les paramètres
            $classe_id = $request->classe_id;
            // Récupérer etudiant_id soit depuis etudiant_id, soit depuis bulletin
            $etudiant_id = $request->etudiant_id ?? $request->bulletin;
            $periode = $request->periode ?? 'semestre1';
            $annee_universitaire_id = $request->annee_universitaire_id;

            // Journaliser les paramètres pour le débogage
            \Log::info('Paramètres reçus pour genererPDFParParams:', [
                'classe_id' => $classe_id,
                'etudiant_id' => $etudiant_id,
                'bulletin' => $request->bulletin,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Vérifier l'existence de l'étudiant
            $etudiant = ESBTPEtudiant::find($etudiant_id);
            if (!$etudiant) {
                return back()->with('error', 'L\'étudiant spécifié n\'existe pas.');
            }

            // VÉRIFICATION 1: Vérifier si des moyennes dans esbtp_resultats sont nulles
            $resultatsNulls = ESBTPResultat::where([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->whereNull('moyenne')->exists();

            if ($resultatsNulls) {
                return back()->with('error', 'Certaines moyennes ne sont pas encore saisies. Veuillez d\'abord saisir toutes les moyennes.');
            }

            // Rechercher le bulletin existant
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            // VÉRIFICATION OBLIGATOIRE : S'assurer que la configuration bulletin existe
            if (!$bulletin || !$bulletin->config_matieres || !$bulletin->professeurs) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id,
                    'bulletin' => $etudiant_id
                ]);
                
                $message = 'Configuration bulletin manquante pour la génération PDF. ';
                if (!$bulletin || !$bulletin->config_matieres) {
                    $message .= 'Veuillez d\'abord configurer les matières et ';
                }
                if (!$bulletin || !$bulletin->professeurs) {
                    $message .= 'les professeurs ';
                }
                $message .= 'avant de générer le bulletin PDF.';
                
                return redirect($configMatieresUrl)->with('error', $message);
            }

            // Vérifier que la configuration n'est pas vide
            $configMatieres = json_decode($bulletin->config_matieres, true);
            $professeursConfigures = json_decode($bulletin->professeurs, true);
            
            if (empty($configMatieres['generales']) && empty($configMatieres['techniques'])) {
                $configMatieresUrl = route('esbtp.bulletins.config-matieres', [
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id,
                    'bulletin' => $etudiant_id
                ]);
                
                return redirect($configMatieresUrl)->with('error', 'Aucune matière n\'est configurée pour ce bulletin. Veuillez configurer au moins une matière avant la génération PDF.');
            }

            // Les vérifications OBLIGATOIRES ont été faites ci-dessus
            // Configuration matières et professeurs sont garantis d'exister
            // Le bulletin existe forcément grâce aux vérifications

            // Récupérer les entités liées
            $classe = ESBTPClasse::findOrFail($classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($annee_universitaire_id);

            // Assigner les entités au bulletin (si c'est un objet stdClass)
            if (!isset($bulletin->id)) {
                $bulletin->etudiant = $etudiant;
                $bulletin->classe = $classe;
                $bulletin->anneeUniversitaire = $anneeUniversitaire;
            }

            // Récupérer les configurations depuis les settings
            $config = $this->getPDFConfig();

            // Préparer le logo en base64
            $logoBase64 = $this->prepareLogoBase64($config['school_logo']);

            // Récupérer les résultats pour l'étudiant
            $resultats = ESBTPResultat::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->with('matiere')
                ->get();

            if ($resultats->isEmpty()) {
                return back()->with('error', 'Aucun résultat trouvé pour cet étudiant dans cette période.');
            }

            // Le reste du code reste inchangé
            // Séparer les résultats par type de matière (généraux et techniques)
            $resultatsGeneraux = collect();
            $resultatsTechniques = collect();

            foreach ($resultats as $resultat) {
                // Vérification et journalisation des données matière
                if ($resultat->matiere) {
                    \Log::info('Matière trouvée pour le résultat #' . $resultat->id, [
                        'matiere_id' => $resultat->matiere_id,
                        'matiere_nom' => $resultat->matiere->nom ?? 'Non défini',
                        'matiere_name' => $resultat->matiere->name ?? 'Non défini',
                        'type' => $resultat->matiere->type ?? 'Non défini'
                    ]);
                } else {
                    \Log::warning('Matière non trouvée pour le résultat #' . $resultat->id, [
                        'matiere_id' => $resultat->matiere_id
                    ]);
                }

                // S'assurer que chaque résultat a une matière valide avec un nom
                if (!$resultat->matiere) {
                    $resultat->matiere = ESBTPMatiere::find($resultat->matiere_id);
                    if (!$resultat->matiere) {
                        \Log::error('Impossible de récupérer la matière #' . $resultat->matiere_id . ' pour le résultat #' . $resultat->id);
                    }
                }

                // Classification améliorée - vérifier d'abord la configuration des matières
                $configMatiere = ESBTPConfigMatiere::where([
                    'matiere_id' => $resultat->matiere_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->first();

                $type = null;
                if ($configMatiere) {
                    $config_data = json_decode($configMatiere->config, true) ?? [];
                    $type = $config_data['type'] ?? null;
                }

                // Utiliser le type de la configuration ou les propriétés de la matière comme fallback
                if ($type === 'general') {
                    $resultatsGeneraux->push($resultat);
                } elseif ($type === 'technique') {
                    $resultatsTechniques->push($resultat);
                } elseif ($resultat->matiere && ($resultat->matiere->type == 'general' || (isset($resultat->matiere->type_formation) && $resultat->matiere->type_formation == 'generale'))) {
                    $resultatsGeneraux->push($resultat);
                } elseif ($resultat->matiere) {
                    $resultatsTechniques->push($resultat);
                }
            }

            // Calculer les moyennes
            $moyenneGeneraux = $resultatsGeneraux->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultatsGeneraux);
            $moyenneTechnique = $resultatsTechniques->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultatsTechniques);
            $moyenneGenerale = $resultats->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultats);

            // Calcul des rangs pour les résultats généraux
            if (!$resultatsGeneraux->isEmpty()) {
                // Trier par moyenne (décroissant)
                $resultatsGeneraux = $resultatsGeneraux->sortByDesc('moyenne')->values();

                // Assigner les rangs
                $previousMoyenne = null;
                $previousRank = 0;
                $sameRankCount = 0;

                foreach ($resultatsGeneraux as $index => $resultat) {
                    if ($previousMoyenne !== null && $resultat->moyenne == $previousMoyenne) {
                        // Même moyenne, même rang
                        $resultat->rang = $previousRank;
                        $sameRankCount++;
                    } else {
                        // Moyenne différente, nouveau rang
                        $resultat->rang = $index + 1;
                        $previousRank = $resultat->rang;
                        $previousMoyenne = $resultat->moyenne;
                        $sameRankCount = 0;
                    }
                }
            }

            // Calcul des rangs pour les résultats techniques
            if (!$resultatsTechniques->isEmpty()) {
                // Trier par moyenne (décroissant)
                $resultatsTechniques = $resultatsTechniques->sortByDesc('moyenne')->values();

                // Assigner les rangs
                $previousMoyenne = null;
                $previousRank = 0;
                $sameRankCount = 0;

                foreach ($resultatsTechniques as $index => $resultat) {
                    if ($previousMoyenne !== null && $resultat->moyenne == $previousMoyenne) {
                        // Même moyenne, même rang
                        $resultat->rang = $previousRank;
                        $sameRankCount++;
                    } else {
                        // Moyenne différente, nouveau rang
                        $resultat->rang = $index + 1;
                        $previousRank = $resultat->rang;
                        $previousMoyenne = $resultat->moyenne;
                        $sameRankCount = 0;
                    }
                }
            }

            // Liste des professeurs par matière (enrichie avec plus de matières possibles)
            $professeursMatiere = [
                // Matières d'enseignement général
                'Anglais' => 'M.FOFANA Lassina',
                'Gestion' => 'M.YAO YAOBLE',
                'Informatique' => 'Mme MANDOUA Nadège',
                'Mathématiques' => 'M.BONE Oussama',
                'Physique' => 'M.KOFFI Bruno',
                'Technique d\'Expression Française' => 'M.DJE Charles',
                'Communication' => 'M.KOUADIO Paul',
                'Économie' => 'Mme KONAN Sarah',
                'Droit' => 'M.KOUAME Jean',

                // Matières techniques/professionnelles
                'Aménagement foncier cadastre' => 'M.ASSALE Arsène',
                'Calculs Topo' => 'M.YAO Niamba',
                'CAO-DAO' => 'M.KIGNELMAN Christian',
                'Géodésie' => 'M.AKA Bleh',
                'Topométrie appliquée au génie civil' => 'M.ATTA Atta',
                'Topométrie générale' => 'M.KOUASSI Jean',
                'Photogrammétrie Analogique' => 'M.ANE Jean',
                'Traitement de données/Télédétection' => 'M.TRAORE Salim',
                'Dessin technique' => 'M.DIALLO Amadou',
                'Génie civil' => 'M.BAKAYOKO Ibrahim',
                'Résistance des matériaux' => 'M.TOURE Karim',
                'Béton armé' => 'Mme DIALLO Fatoumata',
                'Construction métallique' => 'M.CISSE Mohamed',
                'Mécanique des sols' => 'M.DIABATE Moussa',
                'Hydraulique' => 'M.TANOH Georges',
                'Routes et VRD' => 'M.KONE Adama',
                'Mathématiques appliquées' => 'M.COULIBALY Ali',
                'Physique appliquée' => 'Mme SYLLA Aminata',
                'Structures' => 'M.FOFANA Omar',
                'Matériaux de construction' => 'Mme BAH Mariam',
                'Architecture' => 'M.DOUMBIA Souleymane',
                'Gestion de projet' => 'M.CAMARA Issiaka',
                'BTP et environnement' => 'Mme KEITA Aissata'
            ];

            // Récupérer les professeurs du bulletin s'ils existent
            $professeursBulletin = [];
            if (isset($bulletin->id) && $bulletin->professeurs) {
                $professeursBulletin = json_decode($bulletin->professeurs, true) ?: [];
            }

            // Ajouter les professeurs aux résultats et les valeurs par défaut pour rang et appréciation
            $resultats->each(function($resultat) use ($professeursMatiere, $professeursBulletin) {
                // Vérification des propriétés matière
                if ($resultat->matiere) {
                    $nomMatiere = $resultat->matiere->nom ?? $resultat->matiere->name ?? '';

                    // Ajouter le professeur en priorité depuis le bulletin
                    if (isset($professeursBulletin[$resultat->matiere_id])) {
                        $resultat->professeur = $professeursBulletin[$resultat->matiere_id];
                    } else {
                        $resultat->professeur = $professeursMatiere[$nomMatiere] ?? 'N/A';
                    }
                } else {
                    $resultat->professeur = 'N/A';
                }

                // Ajouter le rang s'il n'existe pas
                if (!isset($resultat->rang)) {
                    $resultat->rang = 'N/A';
                }

                // Ajouter l'appréciation si elle n'existe pas
                if (!isset($resultat->appreciation)) {
                    // Déterminer l'appréciation en fonction de la moyenne
                    if ($resultat->moyenne >= 16) {
                        $resultat->appreciation = 'Excellent';
                    } elseif ($resultat->moyenne >= 14) {
                        $resultat->appreciation = 'Très Bien';
                    } elseif ($resultat->moyenne >= 12) {
                        $resultat->appreciation = 'Bien';
                    } elseif ($resultat->moyenne >= 10) {
                        $resultat->appreciation = 'Assez Bien';
                    } elseif ($resultat->moyenne >= 8) {
                        $resultat->appreciation = 'Passable';
                    } else {
                        $resultat->appreciation = 'Insuffisant';
                    }
                }
            });

            // Calcul des statistiques de classe
            $etudiantsClasse = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($classe_id, $annee_universitaire_id) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('status', 'active');
            })->get();

            // Initialiser les variables de statistiques
            $plusForteMoyenne = 0;
            $plusFaibleMoyenne = 20;
            $sommeMoyennes = 0;
            $effectifClasse = count($etudiantsClasse);

            // Calculer les statistiques si des étudiants sont inscrits
            if ($effectifClasse > 0) {
                foreach ($etudiantsClasse as $etud) {
                    $moyenneEtud = $this->calculerMoyenneEtudiant($etud->id, $classe_id, $periode, $annee_universitaire_id);

                    // Ignorer les moyennes nulles ou négatives pour les statistiques
                    if ($moyenneEtud > 0) {
                        if ($moyenneEtud > $plusForteMoyenne) {
                            $plusForteMoyenne = $moyenneEtud;
                        }

                        if ($moyenneEtud < $plusFaibleMoyenne) {
                            $plusFaibleMoyenne = $moyenneEtud;
                        }

                        $sommeMoyennes += $moyenneEtud;
                    }
                }

                // Créer un tableau avec les moyennes des étudiants
                $moyennesClasse = [];

                foreach ($etudiantsClasse as $etud) {
                    $moyenne = $this->calculerMoyenneEtudiant($etud->id, $classe_id, $periode, $annee_universitaire_id);
                    if ($moyenne > 0) {
                        $moyennesClasse[$etud->id] = $moyenne;
                    }
                }

                // Trier les moyennes dans l'ordre décroissant
                arsort($moyennesClasse);

                function formatRangAvecSuffix($rang){
                    if ($rang == 1) {
                        return '1er';
                    }
                    return $rang . 'ème';
                }
                // Déterminer le rang de l'étudiant courant
                $rang = 'N/A';
                $position = 1;

                foreach ($moyennesClasse as $id => $moyenne) {
                    if ($id == $etudiant_id) {
                        $rang = formatRangAvecSuffix($position);
                        break;
                    }
                    $position++;
                }



                // S'assurer que nous avons au moins un étudiant avec une moyenne valide
                if ($sommeMoyennes > 0) {
                    $moyenneClasse = $sommeMoyennes / $effectifClasse;
                } else {
                    $moyenneClasse = 0;
                    $plusFaibleMoyenne = 0;
                }
            } else {
                $plusForteMoyenne = $moyenneGenerale;
                $plusFaibleMoyenne = $moyenneGenerale;
                $moyenneClasse = $moyenneGenerale;
            }

            // Si aucun étudiant n'a de moyenne valide
            if ($plusFaibleMoyenne == 20 && $plusForteMoyenne == 0) {
                $plusFaibleMoyenne = 0;
            }

            // Calculer les absences depuis les enregistrements d'attendance
            $dateDebut = $anneeUniversitaire->date_debut;
            $dateFin = $anneeUniversitaire->date_fin;

            // Utilisation du service d'absences pour calculer les absences
            \Log::info("Calcul des absences pour l'étudiant ID: {$etudiant_id}, classe ID: {$classe_id}, période: du {$dateDebut} au {$dateFin}");
            $absences = $this->absenceService->calculerDetailAbsences($etudiant_id, $classe_id, $dateDebut, $dateFin);
            $absencesJustifiees = $absences['justifiees'];
            $absencesNonJustifiees = $absences['non_justifiees'];

            // Log pour le debugging
            \Log::info("Résultats du calcul des absences:", [
                'absencesJustifiees' => $absencesJustifiees,
                'absencesNonJustifiees' => $absencesNonJustifiees,
                'total' => $absencesJustifiees + $absencesNonJustifiees
            ]);

            // Note d'assiduité (peut être ajustée selon vos règles)
            $noteAssiduite = $this->calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees);

            // Récupérer tous les paramètres de configuration pour le template pdf-configurable
            $settings = $this->getPDFConfig(); // Utiliser la méthode centralisée
            /*$settings = [
                // Configuration de base
                'bulletin_font_size' => \App\Helpers\SettingsHelper::get('bulletin_font_size', '11'),
                'bulletin_show_logo' => \App\Helpers\SettingsHelper::get('bulletin_show_logo', '1'),
                'bulletin_school_name_custom' => \App\Helpers\SettingsHelper::get('bulletin_school_name_custom', ''),
                'bulletin_show_header' => \App\Helpers\SettingsHelper::get('bulletin_show_header', '1'),
                'bulletin_show_republic_info' => \App\Helpers\SettingsHelper::get('bulletin_show_republic_info', '1'),
                'bulletin_republic_text' => \App\Helpers\SettingsHelper::get('bulletin_republic_text', 'République de Côte d\'Ivoire'),
                'bulletin_union_text' => \App\Helpers\SettingsHelper::get('bulletin_union_text', 'Union-Discipline-Travail'),
                'bulletin_show_ministry_info' => \App\Helpers\SettingsHelper::get('bulletin_show_ministry_info', '1'),
                'bulletin_ministry_text' => \App\Helpers\SettingsHelper::get('bulletin_ministry_text', 'Ministère de l\'Enseignement Supérieur'),
                'bulletin_show_school_info' => \App\Helpers\SettingsHelper::get('bulletin_show_school_info', '1'),
                'bulletin_show_edition_date' => \App\Helpers\SettingsHelper::get('bulletin_show_edition_date', '1'),
                'bulletin_show_cycle_info' => \App\Helpers\SettingsHelper::get('bulletin_show_cycle_info', '1'),
                'bulletin_cycle_text' => \App\Helpers\SettingsHelper::get('bulletin_cycle_text', 'Brevet de Technicien Supérieur'),
                'bulletin_cycle_abbreviation' => \App\Helpers\SettingsHelper::get('bulletin_cycle_abbreviation', 'BTS'),
                
                // Informations étudiant
                'bulletin_show_student_info' => \App\Helpers\SettingsHelper::get('bulletin_show_student_info', '1'),
                'bulletin_show_matricule' => \App\Helpers\SettingsHelper::get('bulletin_show_matricule', '1'),
                'bulletin_show_birth_date' => \App\Helpers\SettingsHelper::get('bulletin_show_birth_date', '1'),
                'bulletin_show_redoublant' => \App\Helpers\SettingsHelper::get('bulletin_show_redoublant', '1'),
                'bulletin_show_class_info' => \App\Helpers\SettingsHelper::get('bulletin_show_class_info', '1'),
                'bulletin_show_effectif' => \App\Helpers\SettingsHelper::get('bulletin_show_effectif', '1'),
                
                // Tableau des matières
                'bulletin_show_subjects_table' => \App\Helpers\SettingsHelper::get('bulletin_show_subjects_table', '1'),
                'bulletin_show_subject_average' => \App\Helpers\SettingsHelper::get('bulletin_show_subject_average', '1'),
                'bulletin_show_coefficient' => \App\Helpers\SettingsHelper::get('bulletin_show_coefficient', '1'),
                'bulletin_show_weighted_average' => \App\Helpers\SettingsHelper::get('bulletin_show_weighted_average', '1'),
                'bulletin_show_rank_per_subject' => \App\Helpers\SettingsHelper::get('bulletin_show_rank_per_subject', '1'),
                'bulletin_show_teachers' => \App\Helpers\SettingsHelper::get('bulletin_show_teachers', '1'),
                'bulletin_show_appreciations' => \App\Helpers\SettingsHelper::get('bulletin_show_appreciations', '1'),
                'bulletin_show_general_subjects' => \App\Helpers\SettingsHelper::get('bulletin_show_general_subjects', '1'),
                'bulletin_show_technical_subjects' => \App\Helpers\SettingsHelper::get('bulletin_show_technical_subjects', '1'),
                'bulletin_show_section_averages' => \App\Helpers\SettingsHelper::get('bulletin_show_section_averages', '1'),
                
                // Absences
                'bulletin_show_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_absences', '1'),
                'bulletin_show_justified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_justified_absences', '1'),
                'bulletin_show_unjustified_absences' => \App\Helpers\SettingsHelper::get('bulletin_show_unjustified_absences', '1'),
                
                // Section résultats
                'bulletin_show_results_section' => \App\Helpers\SettingsHelper::get('bulletin_show_results_section', '1'),
                'bulletin_show_raw_average' => \App\Helpers\SettingsHelper::get('bulletin_show_raw_average', '1'),
                'bulletin_show_attendance_note' => \App\Helpers\SettingsHelper::get('bulletin_show_attendance_note', '1'),
                'bulletin_show_semester_average' => \App\Helpers\SettingsHelper::get('bulletin_show_semester_average', '1'),
                'bulletin_show_student_rank' => \App\Helpers\SettingsHelper::get('bulletin_show_student_rank', '1'),
                
                // Mentions
                'bulletin_show_mentions' => \App\Helpers\SettingsHelper::get('bulletin_show_mentions', '1'),
                'bulletin_show_felicitation' => \App\Helpers\SettingsHelper::get('bulletin_show_felicitation', '1'),
                'bulletin_show_encouragement' => \App\Helpers\SettingsHelper::get('bulletin_show_encouragement', '1'),
                'bulletin_show_honor_roll' => \App\Helpers\SettingsHelper::get('bulletin_show_honor_roll', '1'),
                'bulletin_show_work_warning' => \App\Helpers\SettingsHelper::get('bulletin_show_work_warning', '1'),
                'bulletin_show_conduct_blame' => \App\Helpers\SettingsHelper::get('bulletin_show_conduct_blame', '1'),
                'bulletin_auto_calculate_mention' => \App\Helpers\SettingsHelper::get('bulletin_auto_calculate_mention', '1'),
                'bulletin_felicitation_threshold' => \App\Helpers\SettingsHelper::get('bulletin_felicitation_threshold', '16'),
                'bulletin_encouragement_threshold' => \App\Helpers\SettingsHelper::get('bulletin_encouragement_threshold', '14'),
                'bulletin_honor_roll_threshold' => \App\Helpers\SettingsHelper::get('bulletin_honor_roll_threshold', '12'),
                'bulletin_work_warning_threshold' => \App\Helpers\SettingsHelper::get('bulletin_work_warning_threshold', '8'),
                
                // Statistiques
                'bulletin_show_statistics' => \App\Helpers\SettingsHelper::get('bulletin_show_statistics', '1'),
                'bulletin_show_highest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_highest_average', '1'),
                'bulletin_show_lowest_average' => \App\Helpers\SettingsHelper::get('bulletin_show_lowest_average', '1'),
                'bulletin_show_class_average' => \App\Helpers\SettingsHelper::get('bulletin_show_class_average', '1'),
                
                // Décision et signature
                'bulletin_show_council_decision' => \App\Helpers\SettingsHelper::get('bulletin_show_council_decision', '1'),
                'bulletin_show_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_signature', '1'),
                'bulletin_show_director_signature' => \App\Helpers\SettingsHelper::get('bulletin_show_director_signature', '1'),
                'bulletin_show_print_button' => \App\Helpers\SettingsHelper::get('bulletin_show_print_button', '1'),
            ];*/

            // Préparation des données pour la vue
            $data = [
                'bulletin' => $bulletin,
                'etudiant' => $etudiant,
                'classe' => $classe,
                'anneeUniversitaire' => $anneeUniversitaire,
                'periode' => $periode,
                'date_edition' => now()->format('d/m/Y'),
                'effectif' => $effectifClasse,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGeneraux' => $moyenneGeneraux,
                'moyenneTechnique' => $moyenneTechnique,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneGlobale' => $moyenneGenerale,
                'note_assiduite' => $noteAssiduite,
                'moyenneAvecAssiduite' => $moyenneGenerale + $noteAssiduite,
                'rang' => $rang,
                'professeurs' => json_decode($bulletin->professeurs ?? '{}', true),
                'absencesJustifiees' => $absencesJustifiees,
                'absencesNonJustifiees' => $absencesNonJustifiees,
                'noteAssiduite' => $noteAssiduite, // Utiliser la valeur calculée au lieu d'une chaîne vide
                'moyenneSemestre1' =>number_format($moyenneGenerale, 2), // À implémenter si nécessaire
                'plusForteMoyenne' => $bulletin->plus_forte_moyenne ?? number_format($plusForteMoyenne, 2),
                'plusFaibleMoyenne' => $bulletin->plus_faible_moyenne ?? number_format($plusFaibleMoyenne, 2),
                'moyenneClasse' => $bulletin->moyenne_classe ?? number_format($moyenneClasse, 2),
                'effectifClasse' => $effectifClasse,
                // Variables supplémentaires pour la vue pdf-configurable.blade.php
                'meilleure_moyenne' => $plusForteMoyenne,
                'plus_faible_moyenne' => $plusFaibleMoyenne,
                'moyenne_classe' => $moyenneClasse,
                'appreciation' => $this->getMention($moyenneGenerale),
                'logoBase64' => $logoBase64, // Ajouter le logo base64
                'settings' => $settings, // Ajouter tous les paramètres de configuration
            ];

            // Journaliser les données de debug avant génération du PDF
            \Log::info('Données préparées pour la génération du PDF:', [
                'nb_resultats_generaux' => $resultatsGeneraux->count(),
                'nb_resultats_techniques' => $resultatsTechniques->count(),
                'matiere_names_general' => $resultatsGeneraux->pluck('matiere.nom')->toArray(),
                'matiere_names_technique' => $resultatsTechniques->pluck('matiere.nom')->toArray(),
                'professeurs_general' => $resultatsGeneraux->pluck('professeur')->toArray(),
                'professeurs_technique' => $resultatsTechniques->pluck('professeur')->toArray(),
                'ranks_general' => $resultatsGeneraux->pluck('rang')->toArray(),
                'ranks_technique' => $resultatsTechniques->pluck('rang')->toArray(),
                'absences_justifiees' => $absencesJustifiees,
                'absences_non_justifiees' => $absencesNonJustifiees,
            ]);

            // S'assurer que les variables d'absence sont bien définies
            $data['absences_justifiees'] = $absencesJustifiees;
            $data['absences_non_justifiees'] = $absencesNonJustifiees;

            // Log supplémentaire pour vérifier que les variables sont bien définies
            \Log::info('Variables d\'absence définies dans $data:', [
                'absencesJustifiees' => $data['absencesJustifiees'] ?? 'Non défini',
                'absencesNonJustifiees' => $data['absencesNonJustifiees'] ?? 'Non défini',
                'absences_justifiees' => $data['absences_justifiees'] ?? 'Non défini',
                'absences_non_justifiees' => $data['absences_non_justifiees'] ?? 'Non défini'
            ]);

            // Générer le PDF avec le template configurable contenant les vraies infos ESBTP
            $pdf = PDF::loadView('esbtp.bulletins.pdf-configurable', $data);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

            // Nom du fichier PDF
            $filename = 'bulletin_' .
                        ($etudiant ? $etudiant->matricule : 'unknown') . '_' .
                        ($classe ? $classe->code : 'unknown') . '_' .
                        $periode . '_' .
                        ($anneeUniversitaire ? $anneeUniversitaire->libelle : 'unknown') . '.pdf';

            // Télécharger le PDF
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de la génération du PDF par paramètres: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Une erreur est survenue lors de la génération du PDF: ' . $e->getMessage());
        }
    }

    /**
     * Détermine la mention en fonction de la moyenne
     *
     * @param float $moyenne
     * @return string
     */
    private function getMention($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Félicitation';
        } elseif ($moyenne >= 14) {
            return 'Tableau d\'honneur';
        } elseif ($moyenne >= 12) {
            return 'Encouragement';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } elseif ($moyenne >= 8) {
            return 'Avertissement (Travail)';
        } else {
            return 'Blâme (Conduite)';
        }
    }


    /**
     * Calcule la note d'assiduité en fonction des absences
     *
     * @param int $absencesJustifiees
     * @param int $absencesNonJustifiees
     * @return float
     */
    private function calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees)
    {

        // Chaque heure d'absence non justifiée pénalise plus que les justifiées
        //$totalPenalite = ($absencesJustifiees * 0.1) + ($absencesNonJustifiees * 0.5);
         switch (true) {
                    case $absencesNonJustifiees == 0:
                        return 0.13;
                        break;
                    case $absencesNonJustifiees == 1:
                        return 0;
                        break;
                    case $absencesNonJustifiees == 2:
                        return -0.13;
                        break;
                    case $absencesNonJustifiees == 3:
                        return -0.39;
                        break;
                    case $absencesNonJustifiees == 4:
                        return -0.39;
                        break;
                    case $absencesNonJustifiees >= 5: // 5 ou plus
                        return -0.5;
                }

        // La note de base est 20, on soustrait les pénalités
        // $note = 20 + $totalPenalite;

        // // La note ne peut pas être négative
        // if ($note < 0) $note = 0;

        // //Une note ne peut pas être supérieur à 20
        // if ($note > 20 ) $note = 20;

        // return number_format($note,2);

    }

    /**
     * Calcule la moyenne générale d'un étudiant pour une classe, période et année universitaire données
     *
     * @param int $etudiant_id
     * @param int $classe_id
     * @param string $periode
     * @param int $annee_universitaire_id
     * @return float
     */
    private function calculerMoyenneEtudiant($etudiant_id, $classe_id, $periode, $annee_universitaire_id)
    {
        // Récupérer les résultats de l'étudiant pour les paramètres spécifiés
        $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant_id)
            ->where('classe_id', $classe_id)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $annee_universitaire_id)
            ->get();

        // Si aucun résultat n'est trouvé, retourner 0
        if ($resultats->isEmpty()) {
            return 0;
        }

        // Calculer la moyenne pondérée en utilisant la méthode existante
        return $this->calculerMoyennePonderee($resultats);
    }

    /**
     * Prévisualise les moyennes d'un étudiant pour une classe, période et année universitaire données
     * Permet de modifier les moyennes avant génération du bulletin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function previewMoyennes(Request $request)
    {
        // Vérifier les permissions et les rôles
        if (!auth()->user()->hasRole('superAdmin') && !auth()->user()->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les permissions nécessaires pour modifier les moyennes.');
        }

        // Valider les paramètres avec une validation plus stricte pour la période
        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
        ]);

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Si la période est vide, utiliser semestre1 comme valeur par défaut
        if (empty($periode)) {
            $periode = 'semestre1';
        }

        // Normaliser la période si nécessaire
        if ($periode == '1') {
            $periode = 'semestre1';
            $periodePourBDD = 'semestre1';
        } elseif ($periode == '2') {
            $periode = 'semestre2';
            $periodePourBDD = 'semestre2';
        } elseif (in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
            $periodePourBDD = $periode;
        } else {
            // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
            $periode = 'semestre1';
            $periodePourBDD = 'semestre1';
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
        $classe = \App\Models\ESBTPClasse::with('matieres')->findOrFail($classeId);
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

        // MODIFIÉ: Récupérer les notes de l'étudiant avec une requête plus flexible, similaire à resultatEtudiant
        // Récupérer toutes les notes de l'étudiant d'abord
        $notesQuery = \App\Models\ESBTPNote::where('etudiant_id', $etudiantId)
            ->with(['evaluation.matiere', 'matiere']);

        // Filtrer par période (semestre)
        $notesQuery->where(function ($q) use ($periodePourBDD) {
            $q->where('semestre', $periodePourBDD)
              ->orWhereHas('evaluation', function ($query) use ($periodePourBDD) {
                    $query->where('periode', $periodePourBDD);
                });
        });

        // MODIFIÉ: Utilisation du scope byClasse pour filtrer les notes par classe
        // Cela limite les notes aux évaluations de la classe spécifique demandée
        $notesQuery->byClasse($classeId);

        // MODIFIÉ: Filtrage par année universitaire pour inclure aussi l'année précédente
        // Utiliser le scope byAnneeUniversitaireWithPrevious qui permet de récupérer les notes
        // des évaluations de l'année courante (anneeUniversitaireId) ET de l'année précédente (anneeUniversitaireId-1)
        $notesQuery->byAnneeUniversitaireWithPrevious($anneeUniversitaireId);

        // Log pour le débogage - voir quelles notes sont récupérées
        \Log::debug("Notes query for student {$etudiantId}, class {$classeId}, period {$periodePourBDD}, year {$anneeUniversitaireId}");

        $notes = $notesQuery->get();

        // Log des notes récupérées
        foreach ($notes as $note) {
            \Log::debug("Note ID: {$note->id}, Value: {$note->note}, Evaluation ID: {$note->evaluation_id}, Evaluation Year: {$note->evaluation->annee_universitaire_id}, Matiere ID: {$note->evaluation->matiere_id}");
        }

        // Si aucune note n'est trouvée, vérifier s'il existe des notes dans l'année précédente uniquement
        if ($notes->isEmpty()) {
            \Log::debug("No notes found for current criteria. Checking previous year explicitly.");
            $prevYearId = $anneeUniversitaireId - 1;

            $prevNotesQuery = \App\Models\ESBTPNote::query()
        ->where('etudiant_id', $etudiantId)
                ->withValidEvaluation()
                ->whereHas('evaluation', function($query) use ($periodePourBDD, $classeId, $prevYearId) {
                    $query->where('classe_id', $classeId);
                    if ($periodePourBDD != 'annuel') {
                        $query->where('periode', $periodePourBDD);
                    }
                    $query->where('annee_universitaire_id', $prevYearId);
                });

            $prevNotes = $prevNotesQuery->get();

            if ($prevNotes->isNotEmpty()) {
                \Log::debug("Found notes in previous year {$prevYearId}");
                $notes = $prevNotes;
            }
        }

        // Organiser les notes par matière
        $notesByMatiere = [];
        foreach ($notes as $note) {
            if (!$note->evaluation) {
                \Log::debug("Skipping note ID {$note->id} - no evaluation");
                continue;
            }
            $matiere = $note->evaluation->matiere;
            if (!$matiere) {
                \Log::debug("Skipping note ID {$note->id} - no matiere for evaluation {$note->evaluation_id}");
                continue;
            }

            $matiereId = $matiere->id;
            if (!isset($notesByMatiere[$matiereId])) {
                $notesByMatiere[$matiereId] = [
                    'matiere' => $matiere,
                    'notes' => [],
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0
                ];
            }

            $notesByMatiere[$matiereId]['notes'][] = $note;
        }

        // Récupérer les résultats existants pour cet étudiant (exclure les soft-deleted)
        // Les soft-deleted doivent être définitivement supprimés avec forceDelete()
        $resultats = \App\Models\ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('periode', $periodePourBDD)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->with('matiere')
            ->get();

        // Préparer les données des résultats pour l'affichage et l'édition
        $resultatsData = [];
        foreach ($resultats as $resultat) {
            // Vérifier si la relation matiere existe
            if (!$resultat->matiere) {
                // Si la relation n'existe pas, essayer de récupérer la matière directement
                $matiere = \App\Models\ESBTPMatiere::find($resultat->matiere_id);

                // Si la matière n'existe toujours pas, ignorer ce résultat
                if (!$matiere) {
                    continue;
                }
            } else {
                $matiere = $resultat->matiere;
            }

            $resultatsData[$resultat->matiere_id] = [
                'id' => $resultat->id,
                'matiere' => $matiere,
                'moyenne' => $resultat->moyenne,
                'coefficient' => $resultat->coefficient,
                'rang' => $resultat->rang,
                'appreciation' => $resultat->appreciation
            ];
        }

        // Récupérer filière et niveau de la classe pour filtrer les matières
        $classeFiliereIdForNotes = $classe->filiere_id;
        $classeNiveauIdForNotes = $classe->niveau_etude_id;

        // Si des moyennes calculées n'ont pas de résultat correspondant, les ajouter
        // MAIS seulement si la matière correspond à la combinaison filière+niveau de la classe
        foreach ($notesByMatiere as $matiereId => $matiereData) {
            if (!isset($resultatsData[$matiereId])) {
                // CORRECTION AMÉLIORÉE: Récupérer systématiquement l'objet matière directement
                // depuis la base de données en utilisant l'ID
                $matiere = \App\Models\ESBTPMatiere::with(['filieres', 'niveaux'])->find($matiereId);

                if (!$matiere) {
                    \Log::warning("Matiere with ID {$matiereId} not found when adding calculated averages - skipping");
                    continue; // Ignorer cette entrée si la matière n'existe pas
                }

                // Vérifier que la matière correspond à la combinaison filière+niveau de la classe
                if (!$classeFiliereIdForNotes || !$classeNiveauIdForNotes) {
                    \Log::warning("Classe {$classeId} missing filiere_id or niveau_etude_id - skipping matiere {$matiereId}");
                    continue;
                }

                $matchesFiliere = $matiere->filieres->pluck('id')->contains($classeFiliereIdForNotes);
                $matchesNiveau = $matiere->niveaux->pluck('id')->contains($classeNiveauIdForNotes);

                if (!$matchesFiliere || !$matchesNiveau) {
                    \Log::debug("Matiere {$matiereId} ({$matiere->name}) skipped - does not match classe filiere/niveau combination");
                    continue; // Ignorer les matières qui ne correspondent pas à la combinaison
                }

                $resultatsData[$matiereId] = [
                    'id' => null,
                    'matiere' => $matiere, // Utiliser l'objet matière fraîchement récupéré
                    'moyenne' => $matiereData['moyenne'],
                    'coefficient' => $matiereData['total_coefficients'],
                    'rang' => null,
                    'appreciation' => null
                ];
            }
        }

        // Calculer la moyenne pour chaque matière
        foreach ($notesByMatiere as $matiereId => &$matiereData) {
            $totalPoints = 0;
            $totalCoefficients = 0;

            foreach ($matiereData['notes'] as $note) {
                if ($note->evaluation && $note->evaluation->bareme > 0) {
                    $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                    $bareme = floatval($note->evaluation->bareme);
                    $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;

                    $normalized = ($noteValue / $bareme) * 20;
                    $totalPoints += $normalized * $coefficient;
                    $totalCoefficients += $coefficient;
                }
            }

            $matiereData['total_points'] = $totalPoints;
            $matiereData['total_coefficients'] = $totalCoefficients;
            $matiereData['moyenne'] = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;

        }

        // NOUVELLE LOGIQUE: Récupérer les matières basées sur la combinaison filière + niveau de la classe
        // même si l'étudiant n'a aucune évaluation/note
        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $toutesLesMatieres = \App\Models\ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function ($matiere) use ($classeFiliereId, $classeNiveauId) {
                if (!$classeFiliereId || !$classeNiveauId) {
                    return false;
                }
                return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                    && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
            })
            ->values();
        
        // Ajouter les matières de la classe qui n'ont pas encore de résultats
        foreach ($toutesLesMatieres as $matiere) {
            if (!isset($resultatsData[$matiere->id])) {
                // Vérifier si cette matière a des moyennes calculées depuis les évaluations
                $moyenneCalculee = isset($notesByMatiere[$matiere->id]) ? $notesByMatiere[$matiere->id]['moyenne'] : null;
                $coefficientCalcule = isset($notesByMatiere[$matiere->id]) ? $notesByMatiere[$matiere->id]['total_coefficients'] : 1;
                
                $resultatsData[$matiere->id] = [
                    'id' => null, // Nouveau résultat à créer
                    'matiere' => $matiere,
                    'moyenne' => $moyenneCalculee, // null si pas d'évaluations
                    'coefficient' => $coefficientCalcule,
                    'rang' => null,
                    'appreciation' => null,
                    'source' => $moyenneCalculee !== null ? 'calculee' : 'manuelle'
                ];
            } else {
                // Marquer la source des résultats existants
                $moyenneCalculee = isset($notesByMatiere[$matiere->id]) ? $notesByMatiere[$matiere->id]['moyenne'] : null;
                $resultatsData[$matiere->id]['source'] = $moyenneCalculee !== null ? 'calculee' : 'manuelle';
            }
        }
        
        // Trier les matières par nom pour un affichage cohérent
        uasort($resultatsData, function($a, $b) {
            return strcasecmp($a['matiere']->name, $b['matiere']->name);
        });

        // Afficher la vue de prévisualisation des moyennes
        return view('esbtp.resultats.moyennes-preview', compact(
            'etudiant',
            'classe',
            'periode',
            'anneeUniversitaire',
            'notesByMatiere',
            'resultatsData'
        ));
    }

    /**
     * Met à jour les moyennes des étudiants
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateMoyennes(Request $request)
    {
        // Log détaillé pour diagnostiquer l'erreur de validation
        \Log::info('🔍 BULLETIN updateMoyennes - Données reçues', [
            'request_all' => $request->all(),
            'periode_value' => $request->periode,
            'periode_type' => gettype($request->periode),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id()
        ]);

        // Vérifier les permissions et les rôles
        if (!auth()->user()->hasRole('superAdmin') && !auth()->user()->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les permissions nécessaires pour modifier les moyennes.');
        }

        // Validation basique des paramètres requis
        try {
            $request->validate([
                'etudiant_id' => 'required|exists:esbtp_etudiants,id',
                'classe_id' => 'required|exists:esbtp_classes,id',
                'periode' => 'required|in:semestre1,semestre2,annuel,1,2',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            ]);
            \Log::info('✅ BULLETIN updateMoyennes - Validation basique réussie');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ BULLETIN updateMoyennes - Validation basique échouée:', [
                'errors' => $e->errors(),
                'periode_value' => $request->periode,
                'periode_type' => gettype($request->periode),
                'validation_rules' => 'semestre1,semestre2,annuel,1,2',
            ]);
            throw $e;
        }

        // Validation conditionnelle: au moins l'un des deux doit être présent
        if (!$request->has('resultats') && !$request->has('nouvelles_matieres')) {
            return redirect()->back()->with('error', 'Aucune donnée à traiter. Veuillez modifier au moins une moyenne ou ajouter une nouvelle matière.');
        }

        // Validation des résultats existants si présents
        if ($request->has('resultats') && is_array($request->resultats)) {
            try {
                $request->validate([
                    'resultats' => 'array',
                    'resultats.*.matiere_id' => 'required|exists:esbtp_matieres,id',
                    'resultats.*.moyenne' => 'required|numeric|min:0|max:20',
                    'resultats.*.coefficient' => 'required|numeric|min:0',
                    'resultats.*.appreciation' => 'nullable|string|max:255',
                ]);
                \Log::info('✅ BULLETIN updateMoyennes - Validation résultats réussie');
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('❌ BULLETIN updateMoyennes - Validation résultats échouée:', [
                    'errors' => $e->errors(),
                    'resultats_data' => $request->resultats,
                ]);
                throw $e;
            }
        }

        // Validation des nouvelles matières si présentes
        if ($request->has('nouvelles_matieres') && is_array($request->nouvelles_matieres)) {
            try {
                $request->validate([
                    'nouvelles_matieres' => 'array',
                    'nouvelles_matieres.*.matiere_type' => 'required|string|in:existante,nouvelle',
                    'nouvelles_matieres.*.matiere_existante_id' => 'required_if:nouvelles_matieres.*.matiere_type,existante|nullable|exists:esbtp_matieres,id',
                    'nouvelles_matieres.*.nom_nouvelle' => 'required_if:nouvelles_matieres.*.matiere_type,nouvelle|nullable|string|max:255',
                    'nouvelles_matieres.*.moyenne' => 'required|numeric|min:0|max:20',
                    'nouvelles_matieres.*.coefficient' => 'required|numeric|min:0',
                    'nouvelles_matieres.*.appreciation' => 'nullable|string|max:255'
                ]);
                \Log::info('✅ BULLETIN updateMoyennes - Validation nouvelles matières réussie');
            } catch (\Illuminate\Validation\ValidationException $e) {
                \Log::error('❌ BULLETIN updateMoyennes - Validation nouvelles matières échouée:', [
                    'errors' => $e->errors(),
                    'nouvelles_matieres_data' => $request->nouvelles_matieres,
                ]);
                throw $e;
            }
        }

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Normaliser la période si nécessaire
        if ($periode == '1') {
            $periodePourBDD = 'semestre1';
        } elseif ($periode == '2') {
            $periodePourBDD = 'semestre2';
        } elseif (in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
            $periodePourBDD = $periode;
        } else {
            // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
            $periodePourBDD = 'semestre1';
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
        $classe = \App\Models\ESBTPClasse::findOrFail($classeId);
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

        // Traiter chaque résultat (si présents)
        if ($request->has('resultats') && is_array($request->resultats)) {
            foreach ($request->resultats as $resultatData) {
            $matiereId = $resultatData['matiere_id'];
            $moyenne = $resultatData['moyenne'];
            $coefficient = $resultatData['coefficient'];
            $appreciation = $resultatData['appreciation'] ?? null;
            $resultatId = $resultatData['id'] ?? null;

            // Si un ID de résultat est fourni, mettre à jour le résultat existant
            if ($resultatId) {
                $resultat = \App\Models\ESBTPResultat::find($resultatId);
                if ($resultat) {
                    $resultat->update([
                        'moyenne' => $moyenne,
                        'coefficient' => $coefficient,
                        'appreciation' => $appreciation
                    ]);
                    continue;
                }
            }

            // Sinon, créer un nouveau résultat
            \App\Models\ESBTPResultat::create([
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'matiere_id' => $matiereId,
                'periode' => $periodePourBDD,
                'annee_universitaire_id' => $anneeUniversitaireId,
                'moyenne' => $moyenne,
                'coefficient' => $coefficient,
                'appreciation' => $appreciation
            ]);
            }
        }

        // NOUVELLE LOGIQUE: Traiter les nouvelles matières ajoutées dynamiquement
        if ($request->has('nouvelles_matieres') && is_array($request->nouvelles_matieres)) {
            foreach ($request->nouvelles_matieres as $nouvelleMatiereData) {
                $matiereType = $nouvelleMatiereData['matiere_type'];
                $moyenne = $nouvelleMatiereData['moyenne'];
                $coefficient = $nouvelleMatiereData['coefficient'];
                $appreciation = $nouvelleMatiereData['appreciation'] ?? null;

                if ($matiereType === 'existante') {
                    // Utiliser une matière existante
                    $matiereId = $nouvelleMatiereData['matiere_existante_id'];
                    $matiere = \App\Models\ESBTPMatiere::findOrFail($matiereId);
                    
                    // Associer la matière à la classe si ce n'est pas déjà fait
                    if (!$classe->matieres->contains($matiere->id)) {
                        $classe->matieres()->attach($matiere->id);
                    }
                } else if ($matiereType === 'nouvelle') {
                    // Créer une nouvelle matière
                    $nomMatiere = $nouvelleMatiereData['nom_nouvelle'];
                    $matiere = \App\Models\ESBTPMatiere::firstOrCreate(
                        ['name' => $nomMatiere],
                        [
                            'code' => strtoupper(substr($nomMatiere, 0, 3)) . '_' . time(),
                            'description' => 'Matière ajoutée manuellement via le bulletin',
                            'coefficient' => $coefficient,
                            'type_formation' => 'generale',
                            'is_active' => true
                        ]
                    );

                    // Associer la matière à la classe
                    if (!$classe->matieres->contains($matiere->id)) {
                        $classe->matieres()->attach($matiere->id);
                    }
                } else {
                    continue; // Type invalide, ignorer
                }

                // Créer le résultat pour cette matière
                \App\Models\ESBTPResultat::create([
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'matiere_id' => $matiere->id,
                    'periode' => $periodePourBDD,
                    'annee_universitaire_id' => $anneeUniversitaireId,
                    'moyenne' => $moyenne,
                    'coefficient' => $coefficient,
                    'appreciation' => $appreciation
                ]);
            }
        }

        // Rediriger vers la page des résultats de l'étudiant
        return redirect()->route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiantId,
            'classe_id' => $classeId,
            'periode' => $periode, // Utiliser la période originale pour la redirection
            'annee_universitaire_id' => $anneeUniversitaireId
        ])->with('success', 'Les moyennes ont été mises à jour avec succès.');
    }

    /**
     * Supprime une moyenne manuelle d'un étudiant
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deleteMoyenne(Request $request)
    {
        // Vérifier les permissions
        if (!auth()->user()->hasRole('superAdmin') && !auth()->user()->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer les moyennes.');
        }

        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'matiere_id' => 'required|exists:esbtp_matieres,id',
            'periode' => 'required',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
        ]);

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $matiereId = $request->matiere_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Normaliser la période
        $periodePourBDD = in_array($periode, ['semestre1', 'semestre2', 'annuel']) ? $periode : 'semestre1';

        try {
            // Rechercher le résultat (inclure les soft deletes au cas où)
            $resultat = \App\Models\ESBTPResultat::withTrashed()->where([
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'matiere_id' => $matiereId,
                'periode' => $periodePourBDD,
                'annee_universitaire_id' => $anneeUniversitaireId
            ])->first();

            if ($resultat) {
                $matiereName = $resultat->matiere->name ?? 'Inconnue';
                
                // Utiliser forceDelete() pour supprimer définitivement l'enregistrement
                // car nous utilisons SoftDeletes mais voulons une suppression permanente
                $resultat->forceDelete();
                
                return redirect()->back()->with('success', "La moyenne de la matière \"{$matiereName}\" a été supprimée définitivement.");
            } else {
                return redirect()->back()->with('error', 'Moyenne non trouvée ou déjà supprimée.');
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de la moyenne: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    /**
     * Affiche le formulaire de configuration des types de matières
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function configMatieresTypeFormation(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
        }

        // Récupérer les paramètres
        $etudiant_id = $request->etudiant_id ?? $request->bulletin;
        $classe_id = $request->classe_id;
        $periode = $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour configMatieresTypeFormation:', [
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) {
            return back()->with('error', 'Paramètres manquants pour la configuration des matières.');
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = ESBTPEtudiant::find($etudiant_id);
        $classe = ESBTPClasse::with(['filiere', 'niveau'])->find($classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        // S'assurer que $classe est un objet, pas un tableau
        if (is_array($classe)) {
            // Si $classe est un tableau, le convertir en objet ESBTPClasse
            $classeObj = ESBTPClasse::with(['filiere', 'niveau'])->find($classe_id);
            if (!$classeObj) {
                return back()->with('error', 'Classe introuvable.');
            }
            $classe = $classeObj;
        }

        if (!$etudiant || !$classe || !$anneeUniversitaire) {
            return back()->with('error', 'Données introuvables.');
        }

        // Récupérer les matières basées sur la combinaison filière + niveau de la classe
        // NOTE: On utilise UNIQUEMENT l'approche filière+niveau car c'est la source fiable.
        // L'approche basée sur esbtp_resultats peut être incomplète si toutes les notes n'ont pas été saisies.
        try {
            $classeFiliereId = $classe->filiere_id;
            $classeNiveauId = $classe->niveau_etude_id;

            $matieres = \App\Models\ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->filter(function ($matiere) use ($classeFiliereId, $classeNiveauId) {
                    if (!$classeFiliereId || !$classeNiveauId) {
                        return false;
                    }
                    return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                        && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
                })
                ->values();

            \Log::info('📚 Matières récupérées basées sur filière + niveau de la classe (config-matieres)', [
                'count' => $matieres->count(),
                'filiere_id' => $classeFiliereId,
                'niveau_id' => $classeNiveauId,
                'matiere_ids' => $matieres->pluck('id')->toArray()
            ]);

            if ($matieres->isEmpty()) {
                // Rediriger vers la page des résultats avec message explicatif
                return redirect()->route('esbtp.resultats.etudiant', [
                    'etudiant' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('error', 'Le bulletin ne peut pas être généré car aucune matière n\'a été trouvée pour cette classe. Veuillez configurer les matières de la classe (filière + niveau).');
            }
        } catch (\Exception $e) {
            \Log::error('❌ Erreur lors de la récupération des matières depuis la classe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                'annee_universitaire_id' => $annee_universitaire_id
            ])->with('error', 'Une erreur est survenue lors de la génération du bulletin : ' . $e->getMessage());
        }

        // Récupérer les configurations existantes
        $configsMatieres = ESBTPConfigMatiere::withTrashed()->where([
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ])->get()->keyBy('matiere_id');

        // Initialisation des catégories de matières
        $general = [];
        $technique = [];

        // Parcourir les matières pour les classer
        foreach ($matieres as $matiere) {
            $config = $configsMatieres->get($matiere->id);

            // Si une configuration existe pour cette matière
            if ($config && isset($config->config) && is_string($config->config)) {
                $configData = json_decode($config->config, true);
                // Utiliser la clé 'type' au lieu de 'type_formation'
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;

                if ($typeFormation === 'general' || $typeFormation === 'generale') {
                    $general[] = $matiere->id;
                } elseif ($typeFormation === 'technique' || $typeFormation === 'technologique_professionnelle') {
                    $technique[] = $matiere->id;
                }
            } else {
                // Classification automatique basée sur le nom
                $nomMatiere = strtolower($matiere->nom ?? $matiere->name ?? '');

                if (
                    str_contains($nomMatiere, 'math') ||
                    str_contains($nomMatiere, 'anglais') ||
                    str_contains($nomMatiere, 'français') ||
                    str_contains($nomMatiere, 'francais') ||
                    str_contains($nomMatiere, 'communication')
                ) {
                    $general[] = $matiere->id;
                } else {
                    $technique[] = $matiere->id;
                }
            }
        }

        // Préparer les données pour la vue
        $matieresData = [];
        foreach ($matieres as $matiere) {
            $config = $configsMatieres->get($matiere->id);
            $typeFormation = null;
            if ($config && isset($config->config) && is_string($config->config)) {
                $configData = json_decode($config->config, true);
                // Utiliser la clé 'type' au lieu de 'type_formation'
                $typeFormation = $configData['type'] ?? $configData['type_formation'] ?? null;
            }

            // Transformer en objet stdClass au lieu d'un tableau associatif
            $matiereObj = new \stdClass();
            $matiereObj->id = $matiere->id;
            $matiereObj->nom = $matiere->nom ?? $matiere->name ?? '';
            $matiereObj->name = $matiere->name ?? $matiere->nom ?? '';
            $matiereObj->type_formation = $typeFormation;

            $matieresData[] = $matiereObj;
        }

        // Correction du chemin de la vue
        return view('esbtp.bulletins.config-matieres', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'anneeUniversitaire' => $anneeUniversitaire,
            'periode' => $periode,
            'matieres' => $matieresData,
            'general' => $general,
            'technique' => $technique,
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);
    }

    /**
     * Enregistre la configuration des types de matières
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function saveConfigMatieresTypeFormation(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
        }

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour saveConfigMatieresTypeFormation:', [
            'request' => $request->all()
        ]);

        // Valider les données reçues
        $request->validate([
            'etudiant_id' => 'required',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'periode' => 'required',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'matiere_type' => 'required|array',
        ]);

        // Récupérer les paramètres
        $etudiant_id = $request->etudiant_id;
        $classe_id = $request->classe_id;
        $periode = $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;
        $matiere_types = $request->matiere_type;

        try {
            DB::beginTransaction();

            // Supprimer les configurations existantes qui ne sont plus dans la liste envoyée
            // Récupérer toutes les matières configurées précédemment pour cette classe/période/année
            $existingConfigs = ESBTPConfigMatiere::withTrashed()
                ->where([
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])
                ->pluck('matiere_id')
                ->toArray();

            // Trouver les matières qui ne sont plus dans la nouvelle configuration
            $removedMatieres = array_diff(
                $existingConfigs,
                array_keys(array_filter($matiere_types, function($type) { return $type !== 'none'; }))
            );

            // Supprimer définitivement ces configurations
            if (!empty($removedMatieres)) {
                ESBTPConfigMatiere::withTrashed()
                    ->where([
                        'classe_id' => $classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id
                    ])
                    ->whereIn('matiere_id', $removedMatieres)
                    ->forceDelete();
            }

            // Initialiser les tableaux pour stocker les matières par type de formation
            $matieresGenerales = [];
            $matieresTechniques = [];

            // Organiser les matières par type
            foreach ($matiere_types as $matiere_id => $type) {
                if ($type == 'general') {
                    $matieresGenerales[] = (int)$matiere_id;
                    // Utiliser le même type que dans le formulaire pour la cohérence
                    $type_value = 'general';
                } elseif ($type == 'technique') {
                    $matieresTechniques[] = (int)$matiere_id;
                    // Utiliser le même type que dans le formulaire pour la cohérence
                    $type_value = 'technique';
                } else {
                    // Si "none", ignorer cette matière
                    continue;
                }

                // Utiliser updateOrCreate au lieu de delete puis create
                ESBTPConfigMatiere::withTrashed()->updateOrCreate(
                    [
                        'matiere_id' => $matiere_id,
                        'classe_id' => $classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id
                    ],
                    [
                        'config' => json_encode(['type' => $type_value]),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'deleted_at' => null // Restaurer l'enregistrement s'il était soft-deleted
                    ]
                );
            }

            // Récupérer ou créer le bulletin pour cet étudiant
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Préparer la configuration des matières pour le bulletin
            $configMatieres = [
                'generales' => $matieresGenerales,
                'techniques' => $matieresTechniques
            ];

            // Sauvegarder la configuration dans le bulletin
            $bulletin->config_matieres = json_encode($configMatieres);
            $bulletin->save();

            \Log::info('Configuration des matières sauvegardée dans le bulletin', [
                'bulletin_id' => $bulletin->id,
                'config_matieres' => $bulletin->config_matieres,
                'matieres_generales' => count($matieresGenerales),
                'matieres_techniques' => count($matieresTechniques)
            ]);

            DB::commit();

            // Déterminer l'action suivante
            $action = $request->action ?? 'save';

            if ($action === 'edit_professeurs' || $action === 'save_and_edit_profs') {
                // Rediriger vers l'édition des professeurs
                $url = "/esbtp-special/bulletins/edit-professeurs?" . http_build_query([
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return redirect()->to($url)->with('success', 'Configuration des matières enregistrée avec succès.');
            } else if ($action === 'return_results' || $action === 'save_and_return') {
                // Rediriger vers les résultats de l'étudiant
                $url = "/esbtp/resultats/etudiant/{$etudiant_id}?" . http_build_query([
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return redirect()->to($url)->with('success', 'Configuration des matières enregistrée avec succès.');
            } else {
                // Rester sur la même page
                return back()->with('success', 'Configuration des matières enregistrée avec succès.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la sauvegarde de la configuration des matières : ' . $e->getMessage());
            \Log::error('Trace : ' . $e->getTraceAsString());
            return back()->with('error', 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }
    }

    /**
     * Affiche le formulaire d'édition des professeurs
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function editProfesseurs(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut générer des bulletins.');
        }

        // Récupérer les paramètres
        $etudiant_id = $request->etudiant_id ?? $request->bulletin;
        $classe_id = $request->classe_id;
        $periode = $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour editProfesseurs:', [
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) {
            return back()->with('error', 'Paramètres manquants pour l\'édition des professeurs.');
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = ESBTPEtudiant::find($etudiant_id);
        $classe = ESBTPClasse::find($classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        if (!$etudiant || !$classe || !$anneeUniversitaire) {
            return back()->with('error', 'Données introuvables.');
        }

        // Récupérer le bulletin s'il existe
        $bulletin = ESBTPBulletin::where([
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ])->first();

        // Récupérer les matières basées sur la combinaison filière + niveau de la classe
        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $matieresFiltrees = ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use ($classeFiliereId, $classeNiveauId) {
                if (!$classeFiliereId || !$classeNiveauId) {
                    return false;
                }
                return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                    && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
            })
            ->values();

        // Vérifier si la configuration des matières a été faite pour ces matières
        $configsMatieres = ESBTPConfigMatiere::where([
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ])->whereIn('matiere_id', $matieresFiltrees->pluck('id'))->get();

        if ($configsMatieres->isEmpty()) {
            // Rediriger vers la configuration des matières
            $url = "/esbtp-special/bulletins/config-matieres?" . http_build_query([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);
            return redirect()->to($url)->with('error', 'Vous devez d\'abord configurer les types de matières.');
        }

        // Récupérer les matières avec leur type de formation
        $matieres = [];
        foreach ($configsMatieres as $config) {
            if ($config->matiere) {
                // Récupérer le type depuis le config en décodant le JSON et en cherchant la clé 'type'
                $config_data = json_decode($config->config, true) ?? [];
                $typeFormation = $config_data['type'] ?? null;

                // Journaliser pour le débogage
                \Log::debug('Config matière trouvée:', [
                    'matiere_id' => $config->matiere_id,
                    'matiere_nom' => $config->matiere->nom ?? 'Non défini',
                    'config_raw' => $config->config,
                    'config_decoded' => $config_data,
                    'type_formation' => $typeFormation
                ]);

                // Récupérer le nom du professeur pour cette matière
                $professeurNom = '';
                if ($bulletin && $bulletin->professeurs) {
                    $professeurs = json_decode($bulletin->professeurs, true);
                    $professeurNom = $professeurs[$config->matiere_id] ?? '';
                }

                // Récupérer le nom de la matière avec vérification
                $matiereName = 'Matière non identifiée';
                if ($config->matiere) {
                    $matiereName = $config->matiere->nom ?? $config->matiere->name ?? 'Matière #' . $config->matiere_id;
                }

                // Journaliser pour vérifier le nom de la matière
                \Log::info('Matière ajoutée:', [
                    'id' => $config->matiere_id,
                    'nom_recupere' => $matiereName,
                    'matiere_object' => $config->matiere ? 'Existe' : 'Null',
                    'matiere_nom_property' => $config->matiere ? ($config->matiere->nom ?? 'Non défini') : 'N/A',
                    'matiere_name_property' => $config->matiere ? ($config->matiere->name ?? 'Non défini') : 'N/A'
                ]);

                $matieres[] = [
                    'id' => $config->matiere_id,
                    'nom' => $matiereName,
                    'type_formation' => $typeFormation,
                    'professeur_nom' => $professeurNom
                ];
            }
        }

        // Journaliser les matières trouvées
        \Log::info('Matières trouvées pour editProfesseurs:', [
            'nombre_matieres' => count($matieres),
            'matieres' => $matieres
        ]);

        // Grouper les matières par type de formation
        $matieresGenerales = array_filter($matieres, function($matiere) {
            return $matiere['type_formation'] === 'general';
        });

        $matieresProf = array_filter($matieres, function($matiere) {
            return $matiere['type_formation'] === 'technique';
        });

        // Journaliser les résultats du filtrage
        \Log::info('Résultats du filtrage des matières:', [
            'matieres_generales' => count($matieresGenerales),
            'matieres_techniques' => count($matieresProf)
        ]);

        // Récupérer les professeurs du bulletin
        $professeurs = [];
        if ($bulletin && $bulletin->professeurs) {
            $professeurs = json_decode($bulletin->professeurs, true) ?: [];
            \Log::info('📋 Professeurs from bulletin', [
                'bulletin_id' => $bulletin->id,
                'professeurs' => $professeurs
            ]);
        } else {
            \Log::warning('⚠️ No bulletin or professeurs found', [
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode
            ]);
        }

        // Récupérer les enseignants depuis planning général pour chaque matière
        // basé sur la combinaison filière + niveau de la classe
        $enseignantsParMatiere = [];
        foreach ($matieres as $matiere) {
            // Récupérer la planification pour cette matière + combinaison classe
            $planification = \DB::table('esbtp_planifications_academiques')
                ->where('matiere_id', $matiere['id'])
                ->where('filiere_id', $classe->filiere_id)
                ->where('niveau_etude_id', $classe->niveau_etude_id)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            if ($planification) {
                // Récupérer les enseignants assignés dans cette planification
                $enseignantIds = \DB::table('esbtp_planification_teachers')
                    ->where('planification_id', $planification->id)
                    ->pluck('teacher_id');

                // Récupérer les enseignants avec leurs infos (via users)
                $enseignants = \DB::table('esbtp_teachers')
                    ->join('users', 'esbtp_teachers.user_id', '=', 'users.id')
                    ->whereIn('esbtp_teachers.id', $enseignantIds)
                    ->where('esbtp_teachers.is_active', true)
                    ->select('users.id', 'users.name', 'users.email')
                    ->get();

                $enseignantsParMatiere[$matiere['id']] = $enseignants;
            } else {
                // Fallback: si pas de planification, essayer la relation globale
                $matiereModel = \App\Models\ESBTPMatiere::find($matiere['id']);
                if ($matiereModel) {
                    $enseignants = $matiereModel->enseignants()
                        ->wherePivot('annee_universitaire_id', $annee_universitaire_id)
                        ->get(['users.id', 'users.name', 'users.email']);

                    $enseignantsParMatiere[$matiere['id']] = $enseignants;
                } else {
                    $enseignantsParMatiere[$matiere['id']] = collect();
                }
            }
        }

        // Transformer les matières en objets compatibles avec la vue
        $resultatsGeneraux = collect($matieresGenerales)->map(function ($item) {
            // Vérifier et journaliser chaque élément
            \Log::debug('Transformation matière générale:', [
                'id' => $item['id'],
                'nom' => $item['nom']
            ]);

            return (object) [
                'matiere_id' => $item['id'],
                'matiere' => (object) [
                    'nom' => $item['nom'],
                    'name' => $item['nom']  // Adding both for compatibility
                ]
            ];
        });

        $resultatsTechniques = collect($matieresProf)->map(function ($item) {
            // Vérifier et journaliser chaque élément
            \Log::debug('Transformation matière technique:', [
                'id' => $item['id'],
                'nom' => $item['nom']
            ]);

            return (object) [
                'matiere_id' => $item['id'],
                'matiere' => (object) [
                    'nom' => $item['nom'],
                    'name' => $item['nom']  // Adding both for compatibility
                ]
            ];
        });

        return view('esbtp.bulletins.edit-professeurs', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'anneeUniversitaire' => $anneeUniversitaire,
            'periode' => $periode,
            'resultatsGeneraux' => $resultatsGeneraux,
            'resultatsTechniques' => $resultatsTechniques,
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'annee_universitaire_id' => $annee_universitaire_id,
            'professeurs' => $professeurs,
            'enseignantsParMatiere' => $enseignantsParMatiere
        ]);
    }

    /**
     * Sauvegarde les professeurs assignés aux matières pour un bulletin
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveProfesseurs(Request $request)
    {
        try {
            // Log au début de la méthode
            Log::info('🔍 Début de saveProfesseurs', [
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->roles,
                'all_request_data' => $request->all(),
                'professeurs_data' => $request->input('professeurs'),
                'action_value' => $request->input('action')
            ]);

            // Valider les données d'entrée
            $validated = $request->validate([
                'professeurs' => 'sometimes|array',
                'etudiant_id' => 'required|exists:esbtp_etudiants,id',
                'classe_id' => 'required|exists:esbtp_classes,id',
                'periode' => 'required|in:semestre1,semestre2,annuel',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
                'appliquer_a_classe' => 'sometimes|boolean',
            ]);

            $etudiant_id = $request->input('etudiant_id');
            $classe_id = $request->input('classe_id');
            $periode = $request->input('periode');
            $annee_universitaire_id = $request->input('annee_universitaire_id');

            $professeurs = [];
            if ($request->has('professeurs') && is_array($request->input('professeurs'))) {
                $professeurs = $request->input('professeurs');
            }

            // Récupérer le bulletin existant ou en créer un nouveau
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Si le bulletin n'existe pas encore, initialiser les propriétés de base
            if (!$bulletin->exists) {
                $bulletin->created_by = Auth::id();
                $bulletin->save();
            }

            // Mettre à jour le bulletin avec les données des professeurs
            $bulletin->professeurs = json_encode($professeurs);
            $bulletin->updated_by = Auth::id();
            $bulletin->save();

            Log::info('✅ Bulletin mis à jour avec succès', ['bulletin_id' => $bulletin->id, 'professeurs' => $professeurs]);

            // Gestion de la propagation à toute la classe
            $bulletinsPropages = 0;
            if ($request->has('appliquer_a_classe') && $request->input('appliquer_a_classe') == '1') {
                Log::info('🔄 Propagation des enseignants à toute la classe demandée');

                // Récupérer tous les bulletins de la même classe, période et année (sauf celui qu'on vient de sauver)
                $autresBulletins = ESBTPBulletin::where('classe_id', $classe_id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('id', '!=', $bulletin->id)
                    ->get();

                foreach ($autresBulletins as $autreBulletin) {
                    $autreBulletin->professeurs = json_encode($professeurs);
                    $autreBulletin->updated_by = Auth::id();
                    $autreBulletin->save();
                    $bulletinsPropages++;
                }

                Log::info("✅ Propagation terminée: {$bulletinsPropages} bulletins mis à jour");
            }

            // Vérifier quelle action a été choisie via le bouton submit
            $action = $request->input('action', '');

            // Préparer les paramètres communs pour les redirections
            $queryParams = [
                    'bulletin' => $etudiant_id,
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
            ];

            // Préparer le message de succès
            $successMessage = 'Les noms des professeurs ont été enregistrés avec succès.';
            if ($bulletinsPropages > 0) {
                $successMessage .= " Ces enseignants ont également été appliqués à {$bulletinsPropages} autre(s) bulletin(s) de la classe.";
            }

            // Redirection en fonction de l'action choisie
            if ($action === 'save_and_back' || $action === 'save_and_return') {
                return redirect()->route('esbtp.resultats.etudiant', [
                    'etudiant' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('success', $successMessage);
            } elseif ($action === 'edit') {
                // Rester sur la page d'édition des professeurs
                return redirect()->route('esbtp.bulletins.edit-professeurs', [
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('success', $successMessage);
            } elseif ($action === 'generate') {
                // Redirection vers la route de génération du bulletin PDF
                return redirect()->route('esbtp.bulletins.pdf-params', [
                    'bulletin' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('success', $successMessage . ' Génération du bulletin en cours...');
            }

            // Redirection par défaut vers la page des résultats de l'étudiant
            return redirect()->route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->with('success', 'Les noms des professeurs ont été enregistrés avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde des professeurs: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la sauvegarde: ' . $e->getMessage());
        }
    }

    /**
     * Cette méthode a été remplacée par le service ESBTPAbsenceService.
     * Voir la méthode calculerDetailAbsences dans ce service.
     * @deprecated
     */
    private function calculerAbsencesAttendance($etudiant_id, $classe_id, $date_debut, $date_fin)
    {
        \Log::warning("La méthode obsolète calculerAbsencesAttendance a été appelée. Utiliser le service ESBTPAbsenceService à la place.");
        return $this->absenceService->calculerDetailAbsences($etudiant_id, $classe_id, $date_debut, $date_fin);
    }

    /**
     * Cette méthode a été remplacée par le service ESBTPAbsenceService.
     * Voir la méthode calculerDetailAbsences dans ce service.
     * @deprecated
     */
    private function calculerAbsencesPourBulletin($etudiantId, $classeId, $dateDebut, $dateFin)
    {
        \Log::warning("La méthode obsolète calculerAbsencesPourBulletin a été appelée. Utiliser le service ESBTPAbsenceService à la place.");
        return $this->absenceService->calculerDetailAbsences($etudiantId, $classeId, $dateDebut, $dateFin);
    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function generateBulletin(Request $request)
    {
        // ... existing code ...

        // Code existant pour créer le bulletin
        $bulletin = ESBTPBulletin::create([
            // Champs existants...
        ]);

        // Déterminer la période pour le calcul des absences
        // Par exemple: utiliser la date de début et de fin du semestre
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($request->annee_universitaire_id);
        if ($anneeUniversitaire) {
            // Exemple: si periode = 'S1' (1er semestre)
            if ($request->periode == 'S1') {
                $dateDebut = $anneeUniversitaire->date_debut;
                $dateFin = Carbon::parse($dateDebut)->addMonths(4)->format('Y-m-d'); // Environ 4 mois pour un semestre
            } else if ($request->periode == 'S2') {
                $dateDebut = Carbon::parse($anneeUniversitaire->date_debut)->addMonths(4)->format('Y-m-d');
                $dateFin = $anneeUniversitaire->date_fin;
            } else {
                // Pour les périodes différentes ou périodes trimestrielles
                // Adapter la logique selon vos besoins
                $dateDebut = $anneeUniversitaire->date_debut;
                $dateFin = $anneeUniversitaire->date_fin;
            }

            \Log::info("Génération de bulletin - Étudiant ID: {$request->etudiant_id}, Classe ID: {$request->classe_id}, Période: du {$dateDebut} au {$dateFin}");

            // Calculer les absences pour la période du bulletin en utilisant le service
            $donneeAbsences = $this->absenceService->calculerDetailAbsences(
                $request->etudiant_id,
                $request->classe_id,
                $dateDebut,
                $dateFin
            );

            \Log::info("Absences calculées:", $donneeAbsences);

            // Intégrer les absences au bulletin
            $bulletin = $this->integrerAbsencesAuBulletin($bulletin, $donneeAbsences);
        }

        // Suite du code existant...

        return redirect()->route('bulletins.show', $bulletin->id)
            ->with('success', 'Bulletin créé avec succès.');
    }

    /**
     * Génère le bulletin pour un étudiant
     */
    public function genererBulletin(Request $request, $etudiantId)
    {
        $etudiant = ESBTPEtudiant::findOrFail($etudiantId);

        // Calculer les absences en utilisant le service
        $absences = $this->absenceService->calculerDetailAbsences(
            $etudiantId,
            $etudiant->classe_id
        );

        // ... rest of the bulletin generation code ...

        return view('esbtp.bulletins.show', [
            'etudiant' => $etudiant,
            'absences' => $absences,
            // ... other data ...
        ]);
    }

    /**
     * Intègre les données d'absences dans le bulletin
     *
     * @param ESBTPBulletin $bulletin Le bulletin à mettre à jour
     * @param array $donneeAbsences Les données d'absences calculées
     * @return ESBTPBulletin Le bulletin mis à jour
     */
    private function integrerAbsencesAuBulletin($bulletin, $donneeAbsences)
    {
        \Log::info("Intégration des absences au bulletin ID: " . $bulletin->id, $donneeAbsences);

        // Mettre à jour les champs d'absences du bulletin
        $bulletin->absences_justifiees = $donneeAbsences['justifiees'];
        $bulletin->absences_non_justifiees = $donneeAbsences['non_justifiees'];
        $bulletin->total_absences = $donneeAbsences['total'];

        // Calculer et définir la note d'assiduité
        $bulletin->note_assiduite = $this->calculerNoteAssiduite(
            $donneeAbsences['justifiees'],
            $donneeAbsences['non_justifiees']
        );

        $bulletin->save();

        \Log::info("Absences intégrées avec succès au bulletin ID: " . $bulletin->id);

        return $bulletin;
    }

    /**
     * Calcule les statistiques réelles de la classe
     */
    private function calculerStatistiquesClasse($classeId, $anneeUniversitaireId)
    {
        // Récupérer tous les étudiants de la classe
        $etudiants = ESBTPEtudiant::whereHas('inscriptions', function($q) use ($classeId, $anneeUniversitaireId) {
            $q->where('classe_id', $classeId)
              ->where('annee_universitaire_id', $anneeUniversitaireId);
        })->get();

        if ($etudiants->isEmpty()) {
            return [
                'meilleure_moyenne' => 0,
                'plus_faible_moyenne' => 0,
                'moyenne_classe' => 0
            ];
        }

        $moyennes = [];

        foreach ($etudiants as $etudiant) {
            // Récupérer les moyennes manuelles pour cet étudiant
            $resultatsManuelsSeulement = \App\Models\ESBTPResultat::where('etudiant_id', $etudiant->id)
                ->where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->with('matiere')
                ->get();

            // Si aucune moyenne manuelle, utiliser la méthode de calcul pour cet étudiant
            if ($resultatsManuelsSeulement->isEmpty()) {
                // Calculer comme avant
                $notesAvecEvaluations = ESBTPNote::where('etudiant_id', $etudiant->id)
                    ->whereHas('evaluation', function($q) use ($classeId, $anneeUniversitaireId) {
                        $q->where('classe_id', $classeId)
                          ->where('annee_universitaire_id', $anneeUniversitaireId);
                    })
                    ->with(['evaluation.matiere'])
                    ->get();

                if ($notesAvecEvaluations->isEmpty()) {
                    continue; // Ignorer les étudiants sans notes ni moyennes manuelles
                }
            }

            // Calculer la moyenne globale de cet étudiant
            $moyenneEtudiant = $this->calculerMoyenneGlobaleEtudiant($etudiant->id, $classeId, $anneeUniversitaireId);
            
            if ($moyenneEtudiant > 0) {
                $moyennes[] = $moyenneEtudiant;
            }
        }

        if (empty($moyennes)) {
            return [
                'meilleure_moyenne' => 0,
                'plus_faible_moyenne' => 0,
                'moyenne_classe' => 0
            ];
        }

        return [
            'meilleure_moyenne' => max($moyennes),
            'plus_faible_moyenne' => min($moyennes),
            'moyenne_classe' => array_sum($moyennes) / count($moyennes)
        ];
    }

    /**
     * Calculer la moyenne globale d'un étudiant (utilisé pour les statistiques)
     */
    private function calculerMoyenneGlobaleEtudiant($etudiantId, $classeId, $anneeUniversitaireId)
    {
        // Récupérer le bulletin pour la configuration
        $bulletin = ESBTPBulletin::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('periode', 'semestre1')
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->first();

        if (!$bulletin || !$bulletin->config_matieres) {
            return 0; // Pas de configuration
        }

        $configMatieres = json_decode($bulletin->config_matieres, true);
        
        // Récupérer les notes avec évaluations
        $notesAvecEvaluations = ESBTPNote::where('etudiant_id', $etudiantId)
            ->with(['evaluation.matiere'])
            ->whereHas('evaluation', function($q) use ($anneeUniversitaireId) {
                $q->where('annee_universitaire_id', $anneeUniversitaireId)
                  ->where('status', '!=', 'cancelled');
            })
            ->get();

        $resultatsParMatiere = [];

        // Calculer les moyennes automatiques
        foreach ($notesAvecEvaluations as $note) {
            if ($note->evaluation && $note->evaluation->matiere) {
                $matiere = $note->evaluation->matiere;
                $matiereId = $matiere->id;

                if (!isset($resultatsParMatiere[$matiereId])) {
                    $resultatsParMatiere[$matiereId] = (object)[
                        'matiere_id' => $matiereId,
                        'matiere' => $matiere,
                        'notes' => [],
                        'moyenne' => 0,
                        'coefficient' => $this->getCoefficient($matiere),
                    ];
                }

                $resultatsParMatiere[$matiereId]->notes[] = [
                    'note' => $note->note,
                    'coefficient' => $note->evaluation->coefficient
                ];
            }
        }

        // Calculer les moyennes pondérées
        foreach ($resultatsParMatiere as $matiereId => $resultat) {
            $totalPoints = 0;
            $totalCoeffs = 0;
            
            foreach ($resultat->notes as $noteData) {
                $totalPoints += $noteData['note'] * $noteData['coefficient'];
                $totalCoeffs += $noteData['coefficient'];
            }
            
            $resultat->moyenne = $totalCoeffs > 0 ? $totalPoints / $totalCoeffs : 0;
        }

        // Intégrer les moyennes manuelles (elles l'emportent)
        $resultatsManuelle = \App\Models\ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->with('matiere')
            ->get();

        foreach ($resultatsManuelle as $resultatManuel) {
            $matiereId = $resultatManuel->matiere_id;
            
            if ($resultatManuel->matiere) {
                if (!isset($resultatsParMatiere[$matiereId])) {
                    $resultatsParMatiere[$matiereId] = (object)[
                        'matiere_id' => $matiereId,
                        'matiere' => $resultatManuel->matiere,
                        'moyenne' => $resultatManuel->moyenne,
                        'coefficient' => $resultatManuel->coefficient ?: $this->getCoefficient($resultatManuel->matiere),
                    ];
                } else {
                    $resultatsParMatiere[$matiereId]->moyenne = $resultatManuel->moyenne;
                    if ($resultatManuel->coefficient) {
                        $resultatsParMatiere[$matiereId]->coefficient = $resultatManuel->coefficient;
                    }
                }
            }
        }

        // Calculer la moyenne globale pondérée
        if (empty($resultatsParMatiere)) {
            return 0;
        }

        return $this->calculerMoyennePonderee(collect($resultatsParMatiere));
    }

    /**
     * Affiche la page de configuration des bulletins
     */
    public function configuration()
    {
        $settings = $this->getPDFConfig();
        
        return view('esbtp.bulletins.configuration', compact('settings'));
    }

    /**
     * Sauvegarde la configuration des bulletins
     */
    public function saveConfiguration(Request $request)
    {
        try {
            \Log::info('Début de sauvegarde configuration', ['data' => $request->all()]);
            
            // Liste des paramètres checkbox (qui doivent être gérés différemment)
            $checkboxFields = [
                'bulletin_show_logo',
                'bulletin_show_header',
                'bulletin_show_republic_info',
                'bulletin_show_ministry_info',
                'bulletin_show_school_info',
                'bulletin_show_edition_date',
                'bulletin_show_cycle_info',
                'bulletin_show_student_info',
                'bulletin_show_matricule',
                'bulletin_show_birth_date',
                'bulletin_show_redoublant',
                'bulletin_show_class_info',
                'bulletin_show_effectif',
                'bulletin_show_subjects_table',
                'bulletin_show_subject_average',
                'bulletin_show_coefficient',
                'bulletin_show_teachers',
                'bulletin_show_appreciations',
                'bulletin_show_general_average',
                'bulletin_show_technical_average',
                'bulletin_show_global_average',
                'bulletin_show_class_rank',
                'bulletin_show_class_size',
                'bulletin_show_attendance',
                'bulletin_show_attendance_note',
                'bulletin_show_highest_average',
                'bulletin_show_lowest_average',
                'bulletin_show_class_average',
                'bulletin_show_council_decision',
                'bulletin_show_signatures',
                'bulletin_show_director_signature'
            ];

            // Liste de tous les paramètres de bulletin
            $allBulletinFields = array_merge($checkboxFields, [
                'bulletin_font_size',
                'bulletin_school_name_custom',
                'bulletin_republic_text',
                'bulletin_union_text',
                'bulletin_ministry_text',
                'bulletin_cycle_text',
                'bulletin_cycle_abbreviation',
                'bulletin_table_border_style'
            ]);

            // Récupérer tous les paramètres de bulletin avec gestion des checkboxes
            $bulletinSettings = $request->only($allBulletinFields);
            
            // Gérer les checkboxes décochées (les définir à '0' si non présentes)
            foreach ($checkboxFields as $field) {
                $bulletinSettings[$field] = $request->has($field) ? '1' : '0';
            }
            
            \Log::info('Paramètres bulletin après traitement checkboxes', ['settings' => $bulletinSettings]);

            // Récupérer les paramètres d'établissement
            $establishmentSettings = $request->only([
                'school_name',
                'school_address',
                'school_phone',
                'school_email',
                'school_website',
                'school_country',
                'director_name',
                'director_title'
            ]);

            // Sauvegarder les paramètres de bulletin
            foreach ($bulletinSettings as $key => $value) {
                SettingsHelper::setOrCreate($key, $value ?? '', 'bulletin');
            }

            // Sauvegarder les paramètres d'établissement avec préfixe
            foreach ($establishmentSettings as $key => $value) {
                SettingsHelper::setOrCreate("establishment.{$key}", $value ?? '', 'establishment');
            }

            return redirect()->back()->with('success', 'Configuration sauvegardée avec succès.');

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la sauvegarde de la configuration: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Erreur lors de la sauvegarde de la configuration: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page d'édition des absences pour un bulletin
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function editAbsences(Request $request)
    {
        // Vérifier les permissions
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé. Seul un SuperAdmin peut éditer les absences.');
        }

        // Récupérer les paramètres
        $etudiant_id = $request->etudiant_id ?? $request->bulletin;
        $classe_id = $request->classe_id;
        $periode = $request->periode;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour editAbsences:', [
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) {
            return back()->with('error', 'Paramètres manquants pour l\'édition des absences.');
        }

        // Normaliser la période
        if ($periode == '1') {
            $periode = 'semestre1';
        } elseif ($periode == '2') {
            $periode = 'semestre2';
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = ESBTPEtudiant::find($etudiant_id);
        $classe = ESBTPClasse::find($classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        if (!$etudiant || !$classe || !$anneeUniversitaire) {
            return back()->with('error', 'Données introuvables.');
        }

        // Récupérer ou créer le bulletin
        $bulletin = ESBTPBulletin::firstOrNew([
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ]);

        // Si le bulletin n'existe pas encore, initialiser les propriétés de base
        if (!$bulletin->exists) {
            $bulletin->created_by = Auth::id();
            $bulletin->save();
        }

        // Calculer les absences automatiquement via le système existant
        try {
            $absencesCalculees = $this->calculerAbsencesDetailes($bulletin);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul automatique des absences: ' . $e->getMessage());
            $absencesCalculees = [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0
            ];
        }

        // Récupérer les valeurs brutes des absences depuis la base de données (éviter les accesseurs)
        $absencesJustifieesDB = $bulletin->getAttributes()['absences_justifiees'] ?? null;
        $absencesNonJustifieesDB = $bulletin->getAttributes()['absences_non_justifiees'] ?? null;

        // Si le bulletin n'a pas encore d'absences manuelles, utiliser les valeurs calculées
        if ($absencesJustifieesDB === null && $absencesNonJustifieesDB === null) {
            $bulletin->absences_justifiees = $absencesCalculees['justifiees'] ?? 0;
            $bulletin->absences_non_justifiees = $absencesCalculees['non_justifiees'] ?? 0;
            $bulletin->total_absences = $absencesCalculees['total'] ?? 0;
            $bulletin->save();

            // Mettre à jour les variables locales
            $absencesJustifieesDB = $absencesCalculees['justifiees'] ?? 0;
            $absencesNonJustifieesDB = $absencesCalculees['non_justifiees'] ?? 0;
        }

        // Déterminer la source des données (auto ou manuelle)
        $source = 'auto';
        if ($absencesJustifieesDB != $absencesCalculees['justifiees'] ||
            $absencesNonJustifieesDB != $absencesCalculees['non_justifiees']) {
            $source = 'manuelle';
        }

        // Calculer la note d'assiduité actuelle
        $noteAssiduite = $this->calculerNoteAssiduite(
            $absencesJustifieesDB ?? 0,
            $absencesNonJustifieesDB ?? 0
        );

        return view('esbtp.bulletins.edit-absences', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'anneeUniversitaire' => $anneeUniversitaire,
            'periode' => $periode,
            'bulletin' => $bulletin,
            'absencesCalculees' => $absencesCalculees,
            'noteAssiduite' => $noteAssiduite,
            'source' => $source,
            // Passer les valeurs directement pour éviter les accesseurs
            'absencesJustifiees' => $absencesJustifieesDB ?? 0,
            'absencesNonJustifiees' => $absencesNonJustifieesDB ?? 0,
            'totalAbsences' => ($absencesJustifieesDB ?? 0) + ($absencesNonJustifieesDB ?? 0)
        ]);
    }

    /**
     * Sauvegarde les absences modifiées pour un bulletin
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveAbsences(Request $request)
    {
        try {
            // Log au début de la méthode
            Log::info('🔍 Début de saveAbsences', [
                'request_path' => $request->path(),
                'request_method' => $request->method(),
                'user_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'all_request_data' => $request->all()
            ]);

            // Valider les données d'entrée
            $validated = $request->validate([
                'absences_justifiees' => 'required|numeric|min:0',
                'absences_non_justifiees' => 'required|numeric|min:0',
                'etudiant_id' => 'required|exists:esbtp_etudiants,id',
                'classe_id' => 'required|exists:esbtp_classes,id',
                'periode' => 'required|in:semestre1,semestre2,annuel',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            ]);

            $etudiant_id = $request->input('etudiant_id');
            $classe_id = $request->input('classe_id');
            $periode = $request->input('periode');
            $annee_universitaire_id = $request->input('annee_universitaire_id');

            $absencesJustifiees = (float) $request->input('absences_justifiees');
            $absencesNonJustifiees = (float) $request->input('absences_non_justifiees');

            // Récupérer le bulletin existant ou en créer un nouveau
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Si le bulletin n'existe pas encore, initialiser les propriétés de base
            if (!$bulletin->exists) {
                $bulletin->created_by = Auth::id();
            }

            // Mettre à jour les absences
            $bulletin->absences_justifiees = $absencesJustifiees;
            $bulletin->absences_non_justifiees = $absencesNonJustifiees;
            $bulletin->total_absences = $absencesJustifiees + $absencesNonJustifiees;

            // Calculer et mettre à jour la note d'assiduité
            $bulletin->note_assiduite = $this->calculerNoteAssiduite(
                $absencesJustifiees,
                $absencesNonJustifiees
            );

            $bulletin->updated_by = Auth::id();
            $bulletin->save();

            Log::info('✅ Bulletin mis à jour avec succès', [
                'bulletin_id' => $bulletin->id,
                'absences_justifiees' => $absencesJustifiees,
                'absences_non_justifiees' => $absencesNonJustifiees,
                'total_absences' => $bulletin->total_absences,
                'note_assiduite' => $bulletin->note_assiduite
            ]);

            // Vérifier quelle action a été choisie via le bouton submit
            $action = $request->input('action', '');

            // Préparer les paramètres communs pour les redirections
            $queryParams = [
                'bulletin' => $etudiant_id,
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ];

            // Redirection en fonction de l'action choisie
            if ($action === 'save_and_back' || $action === 'save_and_return') {
                return redirect()->route('esbtp.resultats.etudiant', [
                    'etudiant' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('success', 'Les absences ont été enregistrées avec succès.');
            } elseif ($action === 'edit') {
                // Rester sur la page d'édition des absences
                return redirect()->route('esbtp.bulletins.edit-absences', $queryParams)
                    ->with('success', 'Les absences ont été enregistrées avec succès.');
            } elseif ($action === 'generate') {
                // Redirection vers la route de génération du bulletin PDF
                return redirect()->route('esbtp.bulletins.pdf-params', [
                    'bulletin' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('success', 'Les absences ont été enregistrées. Génération du bulletin en cours...');
            }

            // Par défaut, retourner aux résultats de l'étudiant
            return redirect()->route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                'annee_universitaire_id' => $annee_universitaire_id
            ])->with('success', 'Les absences ont été enregistrées avec succès.');

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans saveAbsences: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement des absences: ' . $e->getMessage());
        }
    }

    /**
     * Calculer les moyennes automatiques depuis les évaluations pour un étudiant
     * Logique identique à previewMoyennes() mais pour un seul étudiant
     *
     * @param int $etudiantId
     * @param int $classeId
     * @param string|null $periode
     * @param int $anneeUniversitaireId
     * @param \Illuminate\Support\Collection $matieres
     * @return array
     */
    private function calculateMoyennesForStudent($etudiantId, $classeId, $periode, $anneeUniversitaireId, $matieres)
    {
        // Normaliser la période
        $periodePourBDD = $periode;
        if ($periode == '1') {
            $periodePourBDD = 'semestre1';
        } elseif ($periode == '2') {
            $periodePourBDD = 'semestre2';
        }

        // Récupérer toutes les notes de l'étudiant
        $notesQuery = \App\Models\ESBTPNote::where('etudiant_id', $etudiantId)
            ->with(['evaluation.matiere', 'matiere']);

        // Filtrer par période (semestre)
        if ($periodePourBDD) {
            $notesQuery->where(function ($q) use ($periodePourBDD) {
                $q->where('semestre', $periodePourBDD)
                  ->orWhereHas('evaluation', function ($query) use ($periodePourBDD) {
                        $query->where('periode', $periodePourBDD);
                    });
            });
        }

        // Filtrer par classe
        $notesQuery->byClasse($classeId);

        // Filtrer par année universitaire (avec année précédente)
        $notesQuery->byAnneeUniversitaireWithPrevious($anneeUniversitaireId);

        $notes = $notesQuery->get();

        // Organiser les notes par matière
        $notesByMatiere = [];
        foreach ($notes as $note) {
            if (!$note->evaluation || !$note->evaluation->matiere) {
                continue;
            }

            $matiereId = $note->evaluation->matiere->id;
            if (!isset($notesByMatiere[$matiereId])) {
                $notesByMatiere[$matiereId] = [
                    'notes' => [],
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0
                ];
            }

            $notesByMatiere[$matiereId]['notes'][] = $note;
        }

        // Calculer la moyenne pour chaque matière
        foreach ($notesByMatiere as $matiereId => &$matiereData) {
            $totalPoints = 0;
            $totalCoefficients = 0;

            foreach ($matiereData['notes'] as $note) {
                if ($note->evaluation && $note->evaluation->bareme > 0) {
                    $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
                    $bareme = floatval($note->evaluation->bareme);
                    $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;

                    $normalized = ($noteValue / $bareme) * 20;
                    $totalPoints += $normalized * $coefficient;
                    $totalCoefficients += $coefficient;
                }
            }

            $matiereData['total_points'] = $totalPoints;
            $matiereData['total_coefficients'] = $totalCoefficients;
            $matiereData['moyenne'] = $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : null;
        }

        // Retourner les moyennes calculées indexées par matiere_id
        $result = [];
        foreach ($matieres as $matiere) {
            $result[$matiere->id] = [
                'moyenne' => isset($notesByMatiere[$matiere->id]) ? $notesByMatiere[$matiere->id]['moyenne'] : null,
                'source' => isset($notesByMatiere[$matiere->id]) && $notesByMatiere[$matiere->id]['moyenne'] !== null ? 'calculee' : 'manuelle'
            ];
        }

        return $result;
    }
}
