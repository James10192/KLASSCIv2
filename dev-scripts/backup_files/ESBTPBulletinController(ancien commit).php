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
use App\Models\ESBTPProfesseur;

class ESBTPBulletinController extends Controller
{
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

            // Grouper les résultats par type d'enseignement (général ou technique)
            try {
                // S'assurer que les résultats sont chargés
                if ($bulletin->resultats->isEmpty()) {
                    Log::warning('Aucun résultat trouvé pour le bulletin #' . $bulletin->id);
                }

                // Vérifier que chaque résultat a une matière associée
                foreach ($bulletin->resultats as $resultat) {
                    if (!$resultat->matiere) {
                        Log::warning('Résultat #' . $resultat->id . ' sans matière associée pour le bulletin #' . $bulletin->id);
                    }
                }

                $resultatsGeneraux = $bulletin->resultats->filter(function($resultat) {
                    return $resultat->matiere && $resultat->matiere->type_formation == 'generale';
                });

                $resultatsTechniques = $bulletin->resultats->filter(function($resultat) {
                    return $resultat->matiere && $resultat->matiere->type_formation == 'technologique_professionnelle';
                });

                // Vérifier si des résultats ont été trouvés après filtrage
                if ($resultatsGeneraux->isEmpty() && $resultatsTechniques->isEmpty()) {
                    Log::warning('Aucun résultat trouvé après filtrage par type de formation pour le bulletin #' . $bulletin->id);
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors du filtrage des résultats: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $resultatsGeneraux = collect();
                $resultatsTechniques = collect();
            }

            // Calculer les moyennes par type d'enseignement
            try {
                $moyenneGenerale = $bulletin->calculerMoyenneParType('generale');
                $moyenneTechnique = $bulletin->calculerMoyenneParType('technologique_professionnelle');
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des moyennes par type: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $moyenneGenerale = 0;
                $moyenneTechnique = 0;
            }

            // Générer le PDF avec les configurations de l'école
            $data = [
                'bulletin' => $bulletin,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneTechnique' => $moyenneTechnique,
                'config' => [
                    'school_name' => config('school.name', 'École Spéciale du Bâtiment et des Travaux Publics'),
                    'school_type' => config('school.type', 'Enseignement Supérieur Technique'),
                    'school_authorization' => config('school.authorization', ''),
                    'school_address' => config('school.address', 'BP 2541 Yamoussoukro'),
                    'school_phone' => config('school.phone', 'Tél/Fax: 30 64 39 93 - Cel: 05 93 34 26 : 07 72 88 56'),
                    'school_email' => config('school.email', 'esbtp@aviso.ci'),
                    'school_logo' => config('school.logo', 'images/esbtp_logo.png'),
                ]
            ];

            // Debugging des chemins pour le logo
            Log::info('Chemin du logo configuré: ' . $data['config']['school_logo']);
            Log::info('Chemin absolu du logo: ' . public_path($data['config']['school_logo']));
            Log::info('Le fichier existe: ' . (file_exists(public_path($data['config']['school_logo'])) ? 'Oui' : 'Non'));

            // Conversion du logo en base64 pour DomPDF
            $logoPath = public_path($data['config']['school_logo']);
            if (file_exists($logoPath)) {
                $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
                $logoData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
                $data['logoBase64'] = $logoBase64;
                Log::info('Logo converti en base64');
            } else {
                $data['logoBase64'] = null;
                Log::warning('Logo non trouvé: ' . $logoPath);
            }

            try {
                Log::info('Chargement de la vue PDF pour le bulletin #' . $bulletin->id);
                $pdf = PDF::loadView('esbtp.bulletins.pdf', $data);
                $pdf->setPaper('a4', 'portrait');
                $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

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
            Log::info('Début du calcul des absences détaillées pour le bulletin #' . $bulletin->id);

            // Vérifier que les relations nécessaires sont chargées
            if (!$bulletin->etudiant || !$bulletin->classe || !$bulletin->anneeUniversitaire) {
                Log::error('Relations essentielles manquantes pour le calcul des absences du bulletin #' . $bulletin->id);
                throw new \Exception("Données incomplètes pour calculer les absences. Veuillez vérifier que l'étudiant, la classe et l'année universitaire sont correctement définis.");
            }

            // Vérifier que les dates de l'année universitaire sont définies
            if (!$bulletin->anneeUniversitaire->date_debut || !$bulletin->anneeUniversitaire->date_fin) {
                Log::error('Dates de l\'année universitaire non définies pour le bulletin #' . $bulletin->id);
                throw new \Exception("Les dates de début et de fin de l'année universitaire ne sont pas définies.");
            }

            // Absences justifiées
            try {
                $absencesJustifiees = ESBTPAbsence::where('etudiant_id', $bulletin->etudiant_id)
                    ->whereHas('cours', function ($query) use ($bulletin) {
                        $query->whereHas('matiere', function ($q) use ($bulletin) {
                            $q->whereHas('classes', function ($c) use ($bulletin) {
                                $c->where('classe_id', $bulletin->classe_id);
                            });
                        });
                    })
                    ->where('date', '>=', $bulletin->anneeUniversitaire->date_debut)
                    ->where('date', '<=', $bulletin->anneeUniversitaire->date_fin)
                    ->where('justified', true)
                    ->sum('hours');

                Log::info('Absences justifiées calculées: ' . $absencesJustifiees . ' heures');
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des absences justifiées: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $absencesJustifiees = 0;
            }

            // Absences non justifiées
            try {
                $absencesNonJustifiees = ESBTPAbsence::where('etudiant_id', $bulletin->etudiant_id)
                    ->whereHas('cours', function ($query) use ($bulletin) {
                        $query->whereHas('matiere', function ($q) use ($bulletin) {
                            $q->whereHas('classes', function ($c) use ($bulletin) {
                                $c->where('classe_id', $bulletin->classe_id);
                            });
                        });
                    })
                    ->where('date', '>=', $bulletin->anneeUniversitaire->date_debut)
                    ->where('date', '<=', $bulletin->anneeUniversitaire->date_fin)
                    ->where('justified', false)
                    ->sum('hours');

                Log::info('Absences non justifiées calculées: ' . $absencesNonJustifiees . ' heures');
            } catch (\Exception $e) {
                Log::error('Erreur lors du calcul des absences non justifiées: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                $absencesNonJustifiees = 0;
            }

            $total = $absencesJustifiees + $absencesNonJustifiees;
            Log::info('Total des absences calculées: ' . $total . ' heures');

            return [
                'justifiees' => $absencesJustifiees,
                'non_justifiees' => $absencesNonJustifiees,
                'total' => $total
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des absences: ' . $e->getMessage());
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

            // Retourner des valeurs par défaut en cas d'erreur
            return [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Calcule le total des heures d'absence pour un bulletin.
     *
     * @param  \App\Models\ESBTPBulletin  $bulletin
     * @return int
     */
    private function calculerTotalAbsences($bulletin)
    {
        return ESBTPAbsence::where('etudiant_id', $bulletin->etudiant_id)
            ->whereHas('cours', function ($query) use ($bulletin) {
                $query->whereHas('matiere', function ($q) use ($bulletin) {
                    $q->whereHas('classes', function ($c) use ($bulletin) {
                        $c->where('classe_id', $bulletin->classe_id);
                    });
                });
            })
            ->where('date', '>=', $bulletin->anneeUniversitaire->date_debut)
            ->where('date', '<=', $bulletin->anneeUniversitaire->date_fin)
            ->sum('hours');
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
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
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
        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Get selected classe information
        $classeObj = null;
        $classe = null;
        if ($classe_id) {
            $classeObj = ESBTPClasse::with('filiere')->find($classe_id);
            $classe = $classeObj; // Alias for view compatibility
        }

        // Get students and notes
        $etudiants = []; // Renamed from $students for view compatibility
        $notes = [];
        $moyennes = []; // For storing student averages
        $rangs = []; // For storing student ranks
        $bulletins = []; // For storing student bulletins

        if ($classe_id) {
            // Get students through inscriptions for the selected class and year
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id);

                // CORRECTION : Inversion de la logique du filtre
                if (!$include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions.classe.filiere', 'inscriptions.classe.niveau'])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            Log::info('Étudiants récupérés pour la classe', [
                'classe_id' => $classe_id,
                'annee_universitaire_id' => $annee_universitaire_id,
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, also get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spécifié, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                Log::info('Notes récupérées pour les étudiants', [
                    'etudiants_count' => $etudiants->count(),
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les périodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins
                $this->getStudentBulletins($etudiants, $classe_id, $annee_universitaire_id, $semestre, $bulletins);
            }
        } else if ($annee_universitaire_id) {
            // If no class selected but academic year is set, get all students enrolled in that year
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($annee_universitaire_id, $include_all_statuses) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);

                // Inverser la condition pour inclure tous les statuts par défaut
                if ($include_all_statuses) {
                    $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions' => function ($query) use ($annee_universitaire_id) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);
            }])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            \Log::info('Étudiants récupérés par année', [
                'annee_universitaire_id' => $annee_universitaire_id,
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->whereHas('evaluation', function ($query) use ($annee_universitaire_id) {
                        $query->where('annee_universitaire_id', $annee_universitaire_id);
                    })
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spécifié, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                \Log::info('Notes récupérées par année', [
                    'annee_id' => $annee_universitaire_id,
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les périodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins - we don't have a specific class so use individual student inscriptions
                $this->getStudentBulletins($etudiants, null, $annee_universitaire_id, $semestre, $bulletins);
            }
        } else {
            // If no filters are applied, get all active students
            $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($include_all_statuses) {
                // Ne filtrer sur le statut 'active' que si include_all_statuses est false
                if (!$include_all_statuses) {
                $query->where('status', 'active');
                }
            })
            ->with(['user', 'inscriptions'])
            ->orderBy('nom')
            ->orderBy('prenoms');

            $etudiants = $studentsQuery->get();

            \Log::info('Étudiants récupérés sans filtres', [
                'etudiants_count' => $etudiants->count(),
                'include_all_statuses' => $include_all_statuses
            ]);

            // If we have students, get their notes
            if ($etudiants->count() > 0) {
                $student_ids = $etudiants->pluck('id')->toArray();

                // Modification pour inclure toutes les notes quand "Toutes les périodes" est sélectionné
                $notesQuery = ESBTPNote::whereIn('etudiant_id', $student_ids)
                    ->with(['etudiant', 'etudiant.user', 'evaluation', 'evaluation.classe', 'evaluation.matiere']);

                // Si un semestre est spécifié, filtrer par ce semestre
                if ($semestre) {
                    $notesQuery->whereHas('evaluation', function ($query) use ($semestre) {
                        $query->where('periode', 'like', 'semestre'.$semestre.'%');
                    });
                }

                $notes = $notesQuery->get();

                \Log::info('Notes récupérées sans filtres', [
                    'notes_count' => $notes->count(),
                    'semestre' => $semestre ? $semestre : 'Toutes les périodes'
                ]);

                // Calculate moyennes and ranks
                $this->calculateStudentStats($etudiants, $notes, $moyennes, $rangs);

                // Get bulletins - we don't have a specific class so use individual student inscriptions
                $this->getStudentBulletins($etudiants, null, $annee_universitaire_id, $semestre, $bulletins);
            }
        }

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
            'bulletins'
        ));
    }

    /**
     * Helper method to calculate student statistics (averages and ranks)
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

        $bulletins = ESBTPBulletin::where('etudiant_id', $etudiant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('etudiants.bulletins', compact('bulletins', 'etudiant'));
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
            $bulletin->is_published = !$bulletin->is_published;
            $bulletin->save();

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

        // Get current academic year if not specified
        if (!$annee_universitaire_id) {
                $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
        }

        $classe_id = $id;
        $classe = ESBTPClasse::with(['matieres' => function($query) {
            $query->withPivot('coefficient');
        }])->findOrFail($classe_id);

        // Get students through inscriptions for the selected class and year
        $studentsQuery = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($classe_id, $annee_universitaire_id, $include_all_statuses) {
            $query->where('classe_id', $classe_id)
                ->where('annee_universitaire_id', $annee_universitaire_id);

            // Inverser la condition pour inclure tous les statuts par défaut
            if ($include_all_statuses) {
                $query->where('status', 'active');
            }
        })
        ->with(['user']);

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

        // Changed from associative array to array of objects for view compatibility
        $periodes = [
            (object)['id' => '1', 'nom' => 'Semestre 1'],
            (object)['id' => '2', 'nom' => 'Semestre 2']
        ];

        $annees_universitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

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

        return view('esbtp.resultats.classe', compact(
            'classe',
            'students',
            'notes',
            'semestre',
            'periode',
            'periodes',
            'annee_universitaire_id',
            'annee_id',
            'annees_universitaires',
            'resultats',
            'include_all_statuses' // Ajouter cette ligne
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
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        $semestre = $request->semestre;
        $annee_universitaire_id = $request->annee_universitaire_id;

        // CORRECTION: Conversion du format du semestre pour compatibilité avec le format attendu
        // pour la génération de PDF (convertir '1' en 'semestre1')
        $periode = $semestre ? 'semestre' . $semestre : 'semestre1';

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
            $notesQuery->where(function ($q) use ($semestre) {
                    $q->where('semestre', $semestre)
                        ->whereHas('evaluation', function ($query) use ($semestre) {
                            $query->where('periode', 'semestre'.$semestre);
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

        \Log::info('Student Result Calculations', [
            'student_id' => $id,
            'matieres_count' => count($notesByMatiere),
            'moyenne_generale' => $moyenneGenerale,
            'non_numeric_notes' => $nonNumericNotes
        ]);

        return view('esbtp.resultats.etudiant', compact(
            'etudiant',
            'classe',
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
     * Génère un PDF à partir des paramètres fournis (étudiant, classe, période, année universitaire)
     * sans nécessiter un bulletin existant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function genererPDFParParams(Request $request)
    {
        try {
            // Récupérer les paramètres
            $classe_id = $request->classe_id;
            $etudiant_id = $request->etudiant_id;
            $periode = $request->periode;
            $annee_universitaire_id = $request->annee_universitaire_id;

            // Vérifier que les paramètres essentiels sont présents
            if (!$classe_id || !$etudiant_id || !$periode || !$annee_universitaire_id) {
                return back()->with('error', 'Tous les paramètres sont requis pour générer le bulletin');
            }

            // Rechercher le bulletin existant ou créer un objet temporaire
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            if (!$bulletin) {
                $bulletin = new \stdClass();
                $bulletin->etudiant_id = $etudiant_id;
                $bulletin->classe_id = $classe_id;
                $bulletin->periode = $periode;
                $bulletin->annee_universitaire_id = $annee_universitaire_id;
                $bulletin->rang = 'N/A'; // Initialiser avec une valeur par défaut
                $bulletin->appreciation = 'N/A'; // Initialiser avec une valeur par défaut
            }

            // Récupérer les entités liées
            $etudiant = ESBTPEtudiant::findOrFail($etudiant_id);
            $classe = ESBTPClasse::findOrFail($classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($annee_universitaire_id);

            // Assigner les entités au bulletin (si c'est un objet stdClass)
            if (!isset($bulletin->id)) {
                $bulletin->etudiant = $etudiant;
                $bulletin->classe = $classe;
                $bulletin->anneeUniversitaire = $anneeUniversitaire;
            }

            // Encoder le logo en base64 pour l'intégrer dans le PDF
            $logoPath = public_path('img/logo_esbtp.png');
            $logoBase64 = null;
            if (file_exists($logoPath)) {
                $logoContent = file_get_contents($logoPath);
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);
            }

            // Récupérer les résultats pour l'étudiant
            $resultats = ESBTPResultat::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->with('matiere')
                ->get();

            // Séparer les résultats par type de matière (généraux et techniques)
            $resultatsGeneraux = collect();
            $resultatsTechniques = collect();

            foreach ($resultats as $resultat) {
                if ($resultat->matiere && $resultat->matiere->type == 'general') {
                    $resultatsGeneraux->push($resultat);
                } elseif ($resultat->matiere) {
                    $resultatsTechniques->push($resultat);
                }
            }

            // Calculer les moyennes
            $moyenneGeneraux = $resultatsGeneraux->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultatsGeneraux);
            $moyenneTechnique = $resultatsTechniques->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultatsTechniques);
            $moyenneGenerale = $resultats->isEmpty() ? 0 : $this->calculerMoyennePonderee($resultats);

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

            // Ajouter les professeurs aux résultats et les valeurs par défaut pour rang et appréciation
            $resultats->each(function($resultat) use ($professeursMatiere) {
                // Ajouter le professeur
                if ($resultat->matiere) {
                    $nomMatiere = $resultat->matiere->nom;
                    $resultat->professeur = $professeursMatiere[$nomMatiere] ?? 'N/A';
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
            $nbEtudiants = count($etudiantsClasse);

            // Calculer les statistiques si des étudiants sont inscrits
            if ($nbEtudiants > 0) {
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

                // S'assurer que nous avons au moins un étudiant avec une moyenne valide
                if ($sommeMoyennes > 0) {
                    $moyenneClasse = $sommeMoyennes / $nbEtudiants;
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

            // Assigner les valeurs calculées au bulletin
            $bulletin->plus_forte_moyenne = number_format($plusForteMoyenne, 2);
            $bulletin->plus_faible_moyenne = number_format($plusFaibleMoyenne, 2);
            $bulletin->moyenne_classe = number_format($moyenneClasse, 2);

            // Effectif de la classe
            $effectifClasse = $nbEtudiants;
            $bulletin->effectif_classe = $effectifClasse;

            // Calcul des absences
            $absencesJustifiees = ESBTPAbsence::where('etudiant_id', $etudiant_id)
                ->where('justified', true)
                ->sum('hours');

            $absencesNonJustifiees = ESBTPAbsence::where('etudiant_id', $etudiant_id)
                ->where('justified', false)
                ->sum('hours');

            // Note d'assiduité (peut être ajustée selon vos règles)
            $noteAssiduite = $this->calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees);

            // Préparation des données pour la vue
            $data = [
                'bulletin' => $bulletin,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'moyenneGeneraux' => $moyenneGeneraux,
                'moyenneTechnique' => $moyenneTechnique,
                'moyenneGenerale' => $moyenneGenerale,
                'absencesJustifiees' => $absencesJustifiees,
                'absencesNonJustifiees' => $absencesNonJustifiees,
                'noteAssiduite' => $noteAssiduite,
                'moyenneSemestre1' => null, // À implémenter si nécessaire
                'plusForteMoyenne' => $bulletin->plus_forte_moyenne ?? number_format($plusForteMoyenne, 2),
                'plusFaibleMoyenne' => $bulletin->plus_faible_moyenne ?? number_format($plusFaibleMoyenne, 2),
                'moyenneClasse' => $bulletin->moyenne_classe ?? number_format($moyenneClasse, 2),
                'effectifClasse' => $effectifClasse,
                'logoBase64' => $logoBase64
            ];

            // Générer le PDF
            $pdf = PDF::loadView('esbtp.bulletins.pdf', $data);
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
     * Calcule une moyenne pondérée pour un ensemble de résultats
     *
     * @param \Illuminate\Support\Collection $resultats
     * @return float
     */
    private function calculerMoyennePonderee($resultats)
    {
        $sommeNotes = 0;
        $sommeCoefficients = 0;

        foreach ($resultats as $resultat) {
            $sommeNotes += $resultat->moyenne * $resultat->coefficient;
            $sommeCoefficients += $resultat->coefficient;
        }

        if ($sommeCoefficients > 0) {
            return $sommeNotes / $sommeCoefficients;
        }

        return 0;
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
        $totalPenalite = ($absencesJustifiees * 0.1) + ($absencesNonJustifiees * 0.5);

        // La note de base est 20, on soustrait les pénalités
        $note = 20 - $totalPenalite;

        // La note ne peut pas être négative
        if ($note < 0) $note = 0;

        return number_format($note, 2);
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
        if (!in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
            // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
            $periodePourBDD = 'semestre1';
            // Mais conserver la période originale pour l'affichage
        } else {
            $periodePourBDD = $periode;
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

            // CORRECTION: Problème d'affichage des notes incorrectes
            // Vérifier si matiere_id est défini directement dans la note
            // sinon utiliser celui de l'évaluation comme fallback
            // Cela garantit l'association correcte des notes à leurs matières respectives
            $matiereId = $note->matiere_id;
            if (!$matiereId && $note->evaluation && $note->evaluation->matiere_id) {
                $matiereId = $note->evaluation->matiere_id;
            }

            // Log pour déboguer
            \Log::debug("Processing note ID {$note->id} with value {$note->note} for matiere_id {$matiereId} (evaluation matiere_id: {$note->evaluation->matiere_id})");

            // CORRECTION AMÉLIORÉE 2: Forcer la récupération de la matière directement
            // depuis la base de données, mais ajouter une vérification de l'ID de matière
            // pour s'assurer qu'il s'agit de la bonne matière pour cette note.
            $matiere = \App\Models\ESBTPMatiere::find($matiereId);

            if (!$matiere) {
                \Log::warning("Matiere with ID {$matiereId} not found for note ID {$note->id} - skipping note");
                continue; // Ignorer cette note si la matière n'existe pas
            }

            \Log::debug("Found matiere: {$matiere->name} (ID: {$matiere->id}, code: {$matiere->code})");

            // CORRECTION AMÉLIORÉE 2: Création et initialisation explicite de la structure pour éviter
            // les problèmes de référence mémoire ou de partage d'objets
            if (!isset($notesByMatiere[$matiereId])) {
                $notesByMatiere[$matiereId] = [
                    'matiere' => $matiere,
                    'notes' => [],
                    'total_points' => 0,
                    'total_coefficients' => 0,
                    'moyenne' => 0,
                ];
                \Log::debug("Initialized new entry in notesByMatiere for matiere {$matiere->name} (ID: {$matiere->id})");
            }

            // CORRECTION AMÉLIORÉE 2: Vérification supplémentaire pour s'assurer que nous traitons la bonne note
            \Log::debug("Note {$note->id} VALUE CHECK: note field = {$note->note}, valeur field = {$note->valeur}");

            // CORRECTION AMÉLIORÉE 2: Accès direct aux valeurs numériques pour éviter tout problème de
            // conversion ou de référence. Utiliser la fonction floatval pour s'assurer que nous avons une valeur numérique.
            $noteValue = is_numeric($note->note) ? floatval($note->note) : (is_numeric($note->valeur) ? floatval($note->valeur) : 0);
            $coefficient = $note->evaluation->coefficient ? floatval($note->evaluation->coefficient) : 1;
            $bareme = $note->evaluation->bareme > 0 ? floatval($note->evaluation->bareme) : 20;

            $ponderation = ($noteValue / $bareme) * 20 * $coefficient;

            \Log::debug("CALCULATION for note {$note->id}: noteValue={$noteValue}, coefficient={$coefficient}, bareme={$bareme} => ponderation={$ponderation}");

            // CORRECTION AMÉLIORÉE 2: Ajouter explicitement les valeurs aux tableaux en utilisant des structures claires
            // Cela évite tout problème de référence ou de partage d'objets en mémoire
            $noteRef = [
                'id' => $note->id,
                'value' => $noteValue,
                'coefficient' => $coefficient,
                'ponderation' => $ponderation
            ];
            $notesByMatiere[$matiereId]['notes'][] = $noteRef;
            $notesByMatiere[$matiereId]['total_points'] += $ponderation;
            $notesByMatiere[$matiereId]['total_coefficients'] += $coefficient;

            \Log::debug("Added note to matiere {$matiere->name}: total_points now = {$notesByMatiere[$matiereId]['total_points']}, total_coefficients now = {$notesByMatiere[$matiereId]['total_coefficients']}");
        }

        // Calculer les moyennes par matière et les logger
        foreach ($notesByMatiere as $matiereId => &$matiereData) {
            $matiereData['moyenne'] = $matiereData['total_coefficients'] > 0
                ? $matiereData['total_points'] / $matiereData['total_coefficients']
                : 0;

            \Log::debug("Calculated average for matiere {$matiereId}: {$matiereData['moyenne']} (total points: {$matiereData['total_points']}, total coefficients: {$matiereData['total_coefficients']})");
        }

        // Récupérer les résultats existants pour l'étudiant
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

        // Si des moyennes calculées n'ont pas de résultat correspondant, les ajouter
        foreach ($notesByMatiere as $matiereId => $matiereData) {
            if (!isset($resultatsData[$matiereId])) {
                // CORRECTION AMÉLIORÉE: Récupérer systématiquement l'objet matière directement
                // depuis la base de données en utilisant l'ID
                $matiere = \App\Models\ESBTPMatiere::find($matiereId);

                if (!$matiere) {
                    \Log::warning("Matiere with ID {$matiereId} not found when adding calculated averages - skipping");
                    continue; // Ignorer cette entrée si la matière n'existe pas
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
     * Met à jour les moyennes d'un étudiant pour une classe, période et année universitaire données
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateMoyennes(Request $request)
    {
        // Vérifier les permissions et les rôles
        if (!auth()->user()->hasRole('superAdmin') && !auth()->user()->hasRole('secretaire')) {
            return redirect()->back()->with('error', 'Vous n\'avez pas les permissions nécessaires pour modifier les moyennes.');
        }

        $request->validate([
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
            'periode' => 'required',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'resultats' => 'required|array',
            'resultats.*.matiere_id' => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne' => 'required|numeric|min:0|max:20',
            'resultats.*.coefficient' => 'required|numeric|min:0',
            'resultats.*.appreciation' => 'nullable|string|max:255'
        ]);

        $etudiantId = $request->etudiant_id;
        $classeId = $request->classe_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        // Normaliser la période si nécessaire
        if (!in_array($periode, ['semestre1', 'semestre2', 'annuel'])) {
            // Utiliser semestre1 comme valeur par défaut si la période n'est pas reconnue
            $periodePourBDD = 'semestre1';
        } else {
            $periodePourBDD = $periode;
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = \App\Models\ESBTPEtudiant::findOrFail($etudiantId);
        $classe = \App\Models\ESBTPClasse::findOrFail($classeId);
        $anneeUniversitaire = \App\Models\ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

        // Traiter chaque résultat
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

        // Rediriger vers la page des résultats de l'étudiant
        return redirect()->route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiantId,
            'classe_id' => $classeId,
            'periode' => $periode, // Utiliser la période originale pour la redirection
            'annee_universitaire_id' => $anneeUniversitaireId
        ])->with('success', 'Les moyennes ont été mises à jour avec succès.');
    }

    /**
     * Calcule la moyenne générale d'un bulletin
     *
     * @param \App\Models\ESBTPBulletin $bulletin
     * @return float
     */
    private function calculerMoyenneBulletin(\App\Models\ESBTPBulletin $bulletin)
    {
        if ($bulletin->resultats->isEmpty()) {
            return 0;
        }

        $totalPoints = 0;
        $totalCoef = 0;

        foreach ($bulletin->resultats as $resultat) {
            if ($resultat->matiere) {
                $totalPoints += $resultat->moyenne * $resultat->coefficient;
                $totalCoef += $resultat->coefficient;
            }
        }

        return $totalCoef > 0 ? $totalPoints / $totalCoef : 0;
    }

    /**
     * Calcule la mention d'un bulletin en fonction de la moyenne générale
     *
     * @param ESBTPBulletin $bulletin
     * @return string
     */
    private function calculerMention(ESBTPBulletin $bulletin)
    {
        $moyenne = $bulletin->moyenne_generale;

        if ($moyenne >= 16) {
            return 'Très Bien';
        } elseif ($moyenne >= 14) {
            return 'Bien';
        } elseif ($moyenne >= 12) {
            return 'Assez Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }

    /**
     * Calcule le rang d'un bulletin en fonction de la classe et de la période
     *
     * @param \App\Models\ESBTPBulletin $bulletin
     * @return int
     */
    private function calculerRangEleve(\App\Models\ESBTPBulletin $bulletin)
    {
        // Récupérer tous les bulletins de la même classe pour la même période
        $autresBulletins = ESBTPBulletin::where('classe_id', $bulletin->classe_id)
            ->where('periode', $bulletin->periode)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->get();

        // Si aucun autre bulletin, le rang est 1
        if ($autresBulletins->isEmpty()) {
            return 1;
        }

        // Créer un tableau de moyennes
        $moyennes = [];
        foreach ($autresBulletins as $autreBulletin) {
            if ($autreBulletin->id != $bulletin->id) {
                $moyennes[] = $autreBulletin->moyenne_generale;
            }
        }

        // Ajouter la moyenne du bulletin courant
        $moyennes[] = $bulletin->moyenne_generale;

        // Trier les moyennes dans l'ordre décroissant
        rsort($moyennes);

        // Trouver la position de la moyenne du bulletin courant
        return array_search($bulletin->moyenne_generale, $moyennes) + 1;
    }

    /**
     * Calcule les absences justifiées et non justifiées d'un étudiant
     *
     * @param \App\Models\ESBTPBulletin $bulletin
     * @return array
     */
    private function calculerAbsencesEtudiant(\App\Models\ESBTPBulletin $bulletin)
    {
        $absencesJustifiees = ESBTPAbsence::where('etudiant_id', $bulletin->etudiant_id)
            ->where('classe_id', $bulletin->classe_id)
            ->where('periode', $bulletin->periode)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->where('est_justifiee', true)
            ->sum('nombre_heures');

        $absencesNonJustifiees = ESBTPAbsence::where('etudiant_id', $bulletin->etudiant_id)
            ->where('classe_id', $bulletin->classe_id)
            ->where('periode', $bulletin->periode)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->where('est_justifiee', false)
            ->sum('nombre_heures');

        return [
            'justifiees' => $absencesJustifiees,
            'non_justifiees' => $absencesNonJustifiees,
            'total' => $absencesJustifiees + $absencesNonJustifiees
        ];
    }

    /**
     * Calcule la moyenne générale à partir d'une collection de résultats
     *
     * @param \Illuminate\Database\Eloquent\Collection $resultats Collection de résultats
     * @return float La moyenne calculée
     */
    private function calculerMoyenneGeneraleCollection($resultats)
    {
        // Si la collection est vide, retourner 0
        if ($resultats->isEmpty()) {
            return 0;
        }

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($resultats as $resultat) {
            // Vérifier le type d'objet et extraire le coefficient et la note en conséquence
            if ($resultat instanceof \App\Models\ESBTPResultat) {
                // Pour les objets ESBTPResultat
                $coefficient = $resultat->coefficient ?? 1;
                $note = $resultat->moyenne ?? 0;
            } else {
                // Pour d'autres types d'objets - tenter d'extraire les propriétés génériques
                $coefficient = $resultat->coefficient ?? ($resultat->matiere->coefficient ?? 1);
                $note = $resultat->moyenne ?? ($resultat->note ?? 0);
            }

            // S'assurer que les valeurs sont numériques
            $coefficient = floatval($coefficient);
            $note = floatval($note);

            // Journaliser les valeurs pour le débogage
            \Illuminate\Support\Facades\Log::debug("Calcul de moyenne: resultat[" . ($resultat->id ?? 'unknown') . "] - note: {$note}, coefficient: {$coefficient}");

            if ($coefficient > 0) {
                $totalPoints += $note * $coefficient;
                $totalCoefficients += $coefficient;
            }
        }

        // Éviter la division par zéro
        if ($totalCoefficients == 0) {
            return 0;
        }

        $moyenne = $totalPoints / $totalCoefficients;
        \Illuminate\Support\Facades\Log::debug("Moyenne calculée: {$moyenne} (points: {$totalPoints}, coefficients: {$totalCoefficients})");

        return $moyenne;
    }

    /**
     * Déterminer la mention en fonction de la moyenne générale
     * Méthode simplifiée pour les objets qui ne sont pas des instances de ESBTPBulletin
     *
     * @param float $moyenne Moyenne générale
     * @return string Mention correspondante
     */
    private function getMentionSimplifiee($moyenne)
    {
        if ($moyenne >= 16) {
            return 'Très Bien';
        } elseif ($moyenne >= 14) {
            return 'Bien';
        } elseif ($moyenne >= 12) {
            return 'Assez Bien';
        } elseif ($moyenne >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }

    /**
     * Affiche l'interface de configuration des matières par type d'enseignement
     * pour la génération du bulletin PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function configMatieresTypeFormation(Request $request)
    {
        try {
            $return_url = redirect()->route('esbtp.resultats.historique.classes');

            // Convertir explicitement les IDs en entiers
            $classe_id = (int) $request->input('classe_id');
            $bulletin = $request->input('bulletin');
            $etudiant_id = (int) ($bulletin ?: $request->input('etudiant_id'));
            $annee_universitaire_id = (int) $request->input('annee_universitaire_id');
            $periode = $request->input('periode');

            // Validation des entrées
            if (!$classe_id || !$etudiant_id || !$annee_universitaire_id || !$periode) {
                Log::warning('Paramètres manquants pour configMatieresTypeFormation', [
                    'classe_id' => $classe_id,
                    'etudiant_id' => $etudiant_id,
                    'annee_universitaire_id' => $annee_universitaire_id,
                    'periode' => $periode
                ]);
                return $return_url->with('error', 'Paramètres manquants pour la configuration des matières');
            }

            // Récupération des données
            $classe = ESBTPClasse::find($classe_id);
            $etudiant = ESBTPEtudiant::find($etudiant_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

            if (!$classe || !$etudiant || !$anneeUniversitaire) {
                Log::warning('Données non trouvées pour configMatieresTypeFormation', [
                    'classe' => $classe ? 'trouvé' : 'non trouvé',
                    'etudiant' => $etudiant ? 'trouvé' : 'non trouvé',
                    'anneeUniversitaire' => $anneeUniversitaire ? 'trouvé' : 'non trouvé',
                ]);
                return $return_url->with('error', 'Impossible de trouver les données nécessaires');
            }

            // Tentative de récupération des matières
            $matieres = collect();

            // Récupération des résultats pour cet étudiant
            try {
                $resultats = ESBTPResultat::where('etudiant_id', $etudiant_id)
                    ->where('classe_id', $classe_id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->get();

                if ($resultats->isNotEmpty()) {
                    // Extraire les IDs des matières directement des résultats
                    $matieresIds = $resultats->pluck('matiere_id')->unique()->toArray();
                    if (!empty($matieresIds)) {
                        $matieres = ESBTPMatiere::whereIn('id', $matieresIds)->get();
                        Log::info('Matières récupérées depuis les résultats', [
                            'count' => $matieres->count(),
                            'matieres_ids' => $matieresIds
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des matières via les résultats', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Si aucune matière n'est trouvée, récupérer toutes les matières associées à la classe
            if ($matieres->isEmpty()) {
                try {
                    // Vérifier si la classe est liée à des matières
                    $classeMatieresRelation = new \ReflectionMethod($classe, 'matieres');
                    if ($classeMatieresRelation) {
                        $matieres = $classe->matieres()->get();
                        Log::info('Matières récupérées depuis la classe', ['count' => $matieres->count()]);
                    }
                } catch (\Exception $e) {
                    // La relation n'existe pas ou une autre erreur s'est produite
                    Log::warning('Impossible de récupérer les matières via la relation classe->matieres()', [
                        'error' => $e->getMessage()
                    ]);

                    // Essayer de récupérer via une table pivot si elle existe
                    try {
                        $matieres = DB::table('esbtp_classe_matiere')
                            ->where('classe_id', $classe_id)
                            ->join('esbtp_matieres', 'esbtp_classe_matiere.matiere_id', '=', 'esbtp_matieres.id')
                            ->select('esbtp_matieres.*')
                            ->get();

                        Log::info('Matières récupérées depuis la table pivot', ['count' => $matieres->count()]);
                    } catch (\Exception $e2) {
                        Log::warning('Impossible de récupérer les matières via la table pivot', [
                            'error' => $e2->getMessage()
                        ]);
                    }
                }
            }

            // Si toujours aucune matière, récupérer toutes les matières de la filière
            if ($matieres->isEmpty() && $classe->filiere_id) {
                try {
                    $matieres = ESBTPMatiere::where('filiere_id', $classe->filiere_id)->get();
                    Log::info('Matières récupérées depuis la filière', ['count' => $matieres->count()]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la récupération des matières par filière', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Si toujours aucune matière, récupérer toutes les matières
            if ($matieres->isEmpty()) {
                $matieres = ESBTPMatiere::all();
                Log::info('Aucune matière spécifique trouvée, récupération de toutes les matières', ['count' => $matieres->count()]);
            }

            // Récupération de la configuration existante
            $configMatieres = null;
            try {
                $configMatieres = ESBTPConfigMatiere::where('classe_id', $classe_id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->first();
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération de la configuration des matières', [
                    'classe_id' => $classe_id,
                    'annee_universitaire_id' => $annee_universitaire_id,
                    'error' => $e->getMessage()
                ]);
            }

            // Initialisation des catégories de matières
            $general = [];
            $technique = [];

            // Si une configuration existe, utiliser celle-ci
            if ($configMatieres) {
                $config = json_decode($configMatieres->config, true);
                $general = $config['generales'] ?? [];
                $technique = $config['techniques'] ?? [];
            } else {
                // Sinon, classifier automatiquement selon le nom
                foreach ($matieres as $matiere) {
                    // Vérification que $matiere est bien un objet
                    if (!is_object($matiere)) {
                        Log::warning('Une matière non-objet a été détectée', [
                            'matiere' => $matiere
                        ]);
                        continue;
                    }

                    // Traitement seulement si c'est un objet valide
                    $nomMatiere = strtolower($matiere->name ?? $matiere->nom ?? '');

                    // Classification automatique basée sur le nom
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

            // Filtrer les matières non-objets avant de les passer à la vue
            $matieresCollection = collect();
            foreach ($matieres as $matiere) {
                if (is_object($matiere)) {
                    $matieresCollection->push($matiere);
                } else {
                    Log::warning('Matière ignorée car ce n\'est pas un objet', [
                        'matiere' => $matiere
                    ]);
                }
            }

            // Préparation des données pour la vue
            $data = [
                'classe' => $classe,
                'etudiant' => $etudiant,
                'anneeUniversitaire' => $anneeUniversitaire,
                'matieres' => $matieresCollection,
                'general' => $general,
                'technique' => $technique,
                'bulletin' => $bulletin,
                'periode' => $periode
            ];

            return view('esbtp.bulletins.config-matieres', $data);
        } catch (\Exception $e) {
            Log::error('Erreur non gérée dans configMatieresTypeFormation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return redirect()->route('esbtp.resultats.historique.classes')
                ->with('error', 'Une erreur est survenue lors de la configuration des matières: ' . $e->getMessage());
        }
    }

    /**
     * Enregistre la configuration des matières par type de formation
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function saveConfigMatieresTypeFormation(Request $request)
    {
        try {
            // Vérifier les permissions
            if (!Auth::check() || !Auth::user()->hasRole(['superAdmin', 'secretaire'])) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
            }

            // Log complet des données reçues
            Log::info('🔍 Début de saveConfigMatieresTypeFormation', [
                'all_data' => $request->all(),
                'matiere_type' => $request->input('matiere_type', []),
                'action' => $request->input('action')
            ]);

            // Récupérer et valider les paramètres requis
            $classe_id = (int) $request->input('classe_id');
            $etudiant_id = (int) $request->input('etudiant_id');
            $periode = $request->input('periode');
            $annee_universitaire_id = (int) $request->input('annee_universitaire_id');
            $action = $request->input('action', 'save');
            $bulletin_id = $request->input('bulletin');

            // Validation des paramètres obligatoires
            $validator = Validator::make($request->all(), [
                'classe_id' => 'required|integer|exists:esbtp_classes,id',
                'etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
                'periode' => 'required|string|in:semestre1,semestre2,annuel',
                'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
                'matiere_type' => 'required|array',
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Validation échouée pour les paramètres', ['errors' => $validator->errors()->toArray()]);
                return back()->withErrors($validator)->with('error', 'Veuillez vérifier les informations saisies.');
            }

            // Vérification supplémentaire des paramètres
            if (!$classe_id || !$etudiant_id || !$periode || !$annee_universitaire_id) {
                Log::warning('❌ Paramètres manquants pour la configuration des matières', [
                    'classe_id' => $classe_id,
                    'etudiant_id' => $etudiant_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return back()->with('error', 'Paramètres incomplets pour enregistrer la configuration des matières.');
            }

            // Récupérer les objets associés
            $classe = ESBTPClasse::find($classe_id);
            $etudiant = ESBTPEtudiant::find($etudiant_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

            if (!$classe || !$etudiant || !$anneeUniversitaire) {
                $error = '';
                if (!$classe) $error .= 'Classe introuvable. ';
                if (!$etudiant) $error .= 'Étudiant introuvable. ';
                if (!$anneeUniversitaire) $error .= 'Année universitaire introuvable.';

                Log::warning('❌ Objets requis non trouvés', [
                    'classe' => $classe ? 'OK' : 'Non trouvée',
                    'etudiant' => $etudiant ? 'OK' : 'Non trouvé',
                    'anneeUniversitaire' => $anneeUniversitaire ? 'OK' : 'Non trouvée'
                ]);

                return back()->with('error', 'Erreur: ' . $error);
            }

            // Récupérer les types de matières du formulaire
            $matiere_types = $request->input('matiere_type', []);

            // Initialiser les tableaux pour suivre les matières générales et techniques
            $general = [];
            $technique = [];
            $configChanges = [];

            // Début de la transaction pour s'assurer que toutes les opérations réussissent
            DB::beginTransaction();

            try {
                // Traiter chaque matière
                $countGeneral = 0;
                $countTechnique = 0;
                $countTotal = 0;

                // 1. Créer/mettre à jour les configurations des matières
            foreach ($matiere_types as $matiere_id => $type) {
                    $matiere_id = (int) $matiere_id;

                    if ($type !== 'none') {
                        $countTotal++;

                        // Chercher une configuration existante
                        $existingConfig = ESBTPConfigMatiere::where([
                            'matiere_id' => $matiere_id,
                            'classe_id' => $classe_id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $annee_universitaire_id
                        ])->first();

                        // Si elle existe, mettre à jour
                        if ($existingConfig) {
                            $oldType = isset(json_decode($existingConfig->config, true)['type']) ? json_decode($existingConfig->config, true)['type'] : null;

                            // Vérifier si le type a changé
                            if ($oldType !== $type) {
                                $configChanges[$matiere_id] = [
                                    'from' => $oldType,
                                    'to' => $type
                                ];

                                Log::info('⚙️ Type modifié pour la matière', [
                                    'matiere_id' => $matiere_id,
                                    'old_type' => $oldType,
                                    'new_type' => $type
                                ]);
                            }

                            $existingConfig->config = json_encode(['type' => $type]);
                            $existingConfig->updated_by = Auth::id();
                            $existingConfig->save();

                            Log::info('✅ Configuration mise à jour avec succès', [
                                'config_id' => $existingConfig->id,
                                'matiere_id' => $matiere_id,
                                'type' => $type
                            ]);
                        } else {
                            // Créer une nouvelle configuration
                            $newConfig = ESBTPConfigMatiere::create([
                                'matiere_id' => $matiere_id,
                                'classe_id' => $classe_id,
                                'periode' => $periode,
                                'annee_universitaire_id' => $annee_universitaire_id,
                                'config' => json_encode(['type' => $type]),
                                'is_active' => 1,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id()
                            ]);

                            if (!$newConfig) {
                                Log::error('❌ Échec de création de la configuration pour la matière', [
                                    'matiere_id' => $matiere_id,
                                    'type' => $type
                                ]);
                                throw new \Exception("Échec de création de la configuration pour la matière ID: $matiere_id");
                            }

                            Log::info('✅ Configuration créée avec succès', [
                                'config_id' => $newConfig->id,
                                'matiere_id' => $matiere_id,
                                'type' => $type
                            ]);
                        }

                        // Mettre à jour les compteurs
                        if ($type === 'general') {
                            $countGeneral++;
                            $general[] = $matiere_id;
                        } elseif ($type === 'technique') {
                            $countTechnique++;
                            $technique[] = $matiere_id;
                        }

                        // 2. Créer ou mettre à jour les entrées dans ESBTPResultat
                        try {
                            $resultat = ESBTPResultat::withTrashed()->firstOrNew([
                                'etudiant_id' => $etudiant_id,
                        'classe_id' => $classe_id,
                                'matiere_id' => $matiere_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id
                            ]);

                            if ($resultat->trashed()) {
                                $resultat->restore();
                                Log::info('✅ Résultat restauré pour la matière', [
                                    'matiere_id' => $matiere_id,
                                    'etudiant_id' => $etudiant_id
                                ]);
                            }

                            if (!$resultat->exists) {
                                // Nouvelle entrée, initialiser les valeurs par défaut
                                $matiere = ESBTPMatiere::find($matiere_id);
                                $coefficient = $matiere ? ($matiere->coefficient_default ?? 1) : 1;

                                $resultat->moyenne = 0; // Initialiser à 0 ou null selon votre logique
                                $resultat->coefficient = $coefficient;
                                $resultat->created_by = Auth::id();
                            }

                            $resultat->updated_by = Auth::id();
                            $resultat->save();

                            Log::info('✅ Résultat créé/mis à jour avec succès', [
                                'resultat_id' => $resultat->id,
                                'matiere_id' => $matiere_id,
                                'etudiant_id' => $etudiant_id
                ]);
            } catch (\Exception $e) {
                            Log::error('❌ Erreur lors de la création/mise à jour du résultat', [
                                'matiere_id' => $matiere_id,
                                'etudiant_id' => $etudiant_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
                        }
                    }
                }

                // Journaliser le récapitulatif
                Log::info('📊 Récapitulatif de la configuration', [
                    'total' => $countTotal,
                    'general' => $countGeneral,
                    'technique' => $countTechnique,
                    'type_changes' => count($configChanges)
                ]);

                // 3. Créer ou mettre à jour le bulletin si nécessaire
                $bulletin = ESBTPBulletin::firstOrNew([
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);

                if (!$bulletin->exists) {
                    $bulletin->created_by = Auth::id();
                }

                // Mettre à jour la configuration des matières dans le bulletin
                $configData = [
                    'techniques' => $technique,
                    'generales' => $general
                ];

                $bulletin->config_matieres = json_encode($configData);
                $bulletin->updated_by = Auth::id();
                $bulletin->save();

                Log::info('✅ Bulletin mis à jour avec succès', [
                    'bulletin_id' => $bulletin->id,
                    'config' => $configData
                ]);

                // Valider toutes les opérations
                DB::commit();

                // Préparer les paramètres pour les redirections
                $queryParams = [
                    'bulletin' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
                ];

                // Rediriger en fonction de l'action
                if ($action === 'save_and_edit_profs') {
                    return redirect()
                        ->route('esbtp.bulletins.edit-professeurs')
                        ->with('success', 'Configuration des matières enregistrée avec succès. Vous pouvez maintenant éditer les professeurs.')
                        ->with('params', $queryParams)
                        ->withInput(['query_params' => http_build_query($queryParams)]);
                } elseif ($action === 'save_and_return') {
                    return redirect()
                        ->route('esbtp.resultats.etudiant', $etudiant_id)
                        ->with('success', 'Configuration des matières enregistrée avec succès.')
                        ->with('params', $queryParams);
                } else {
                    // Action par défaut (save)
                    return back()->with('success', 'Configuration des matières enregistrée avec succès.');
                }
            } catch (\Exception $e) {
                // En cas d'erreur, annuler toutes les opérations
                DB::rollBack();

                Log::error('❌ Erreur lors de la configuration des matières', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->with('error', 'Erreur lors de la configuration des matières: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('❌ Erreur non gérée dans saveConfigMatieresTypeFormation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return back()->with('error', 'Erreur inattendue lors de la configuration des matières: ' . $e->getMessage());
        }
    }

    /**
     * Affiche un formulaire pour éditer les noms des professeurs avant la génération du bulletin PDF
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function editProfesseurs(Request $request)
    {
        try {
            // Vérifier les permissions
            if (!Auth::check() || !Auth::user()->hasRole(['superAdmin', 'secretaire'])) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
            }

            // Log des données reçues pour le débogage
            Log::info('🔍 Début de editProfesseurs', [
                'request_all' => $request->all(),
                'session_params' => session('params'),
                'query_string' => $request->getQueryString()
            ]);

            // Récupérer les paramètres (d'abord depuis la requête, puis depuis la session)
            $etudiant_id = $request->input('bulletin') ?: $request->input('etudiant_id');
        $classe_id = $request->input('classe_id');
        $periode = $request->input('periode');
        $annee_universitaire_id = $request->input('annee_universitaire_id');

            // Si pas dans la requête, essayer depuis la session
            if (!$etudiant_id && session('params') && isset(session('params')['bulletin'])) {
                $etudiant_id = session('params')['bulletin'];
            }
            if (!$classe_id && session('params') && isset(session('params')['classe_id'])) {
                $classe_id = session('params')['classe_id'];
            }
            if (!$periode && session('params') && isset(session('params')['periode'])) {
                $periode = session('params')['periode'];
            }
            if (!$annee_universitaire_id && session('params') && isset(session('params')['annee_universitaire_id'])) {
                $annee_universitaire_id = session('params')['annee_universitaire_id'];
            }

            // Validation des paramètres
            $validator = Validator::make([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ], [
                'etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
                'classe_id' => 'required|integer|exists:esbtp_classes,id',
                'periode' => 'required|string|in:semestre1,semestre2,annuel',
                'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id'
            ]);

            if ($validator->fails()) {
                Log::warning('❌ Paramètres invalides pour editProfesseurs', ['errors' => $validator->errors()->toArray()]);
                return redirect()->route('dashboard')->with('error', 'Paramètres incomplets pour éditer les professeurs.');
            }

            // Log des paramètres validés
            Log::info('✅ Paramètres validés pour editProfesseurs', [
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

        // Récupérer les objets associés
            $etudiant = ESBTPEtudiant::find($etudiant_id);
            $classe = ESBTPClasse::find($classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

            if (!$etudiant || !$classe || !$anneeUniversitaire) {
                $errorMessage = '';
                if (!$etudiant) $errorMessage .= 'Étudiant introuvable. ';
                if (!$classe) $errorMessage .= 'Classe introuvable. ';
                if (!$anneeUniversitaire) $errorMessage .= 'Année universitaire introuvable.';

                Log::error('❌ Objets introuvables pour editProfesseurs', [
                    'etudiant' => $etudiant ? 'OK' : 'Non trouvé',
                    'classe' => $classe ? 'OK' : 'Non trouvée',
                    'anneeUniversitaire' => $anneeUniversitaire ? 'OK' : 'Non trouvée'
                ]);

                return redirect()->route('dashboard')->with('error', $errorMessage);
            }

            // Récupérer les résultats pour cet étudiant, avec withTrashed pour inclure les enregistrements soft-deleted
            $resultats = ESBTPResultat::withTrashed()
            ->where('etudiant_id', $etudiant_id)
            ->where('classe_id', $classe_id)
            ->where('periode', $periode)
            ->where('annee_universitaire_id', $annee_universitaire_id)
            ->get();

            Log::info('🔍 Résultats trouvés pour editProfesseurs', [
                'count' => $resultats->count(),
                'ids' => $resultats->pluck('id')->toArray(),
                'matieres' => $resultats->pluck('matiere_id')->toArray()
            ]);

            // Si aucun résultat trouvé, vérifier s'il y a des configurations de matières
        if ($resultats->isEmpty()) {
                Log::warning('⚠️ Aucun résultat trouvé pour editProfesseurs, vérification des configurations de matières');

                // Rechercher les configurations de matières existantes
                $configMatieres = ESBTPConfigMatiere::withTrashed()
                    ->where('classe_id', $classe_id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->get();

                if ($configMatieres->isEmpty()) {
                    Log::error('❌ Aucune configuration de matière trouvée pour cet étudiant');
                    return redirect()->route('esbtp.bulletins.config-matieres', [
                        'bulletin' => $etudiant_id,
                        'classe_id' => $classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id
                    ])->with('error', 'Aucune matière n\'a été configurée pour cet étudiant. Veuillez d\'abord configurer les matières.');
                } else {
                    // Tenter de créer automatiquement des résultats à partir des configurations existantes
                    Log::info('🔄 Tentative de création automatique des résultats à partir des configurations', [
                        'total_configs' => $configMatieres->count(),
                        'matiere_ids' => $configMatieres->pluck('matiere_id')->toArray()
                    ]);

                    try {
                        DB::beginTransaction();

                        foreach ($configMatieres as $config) {
                            // Vérifier que le type n'est pas "none"
                            $configData = json_decode($config->config, true);
                            if (isset($configData['type']) && $configData['type'] !== 'none') {
                                // Créer un résultat
                                $resultat = new ESBTPResultat();
                                $resultat->etudiant_id = $etudiant_id;
                                $resultat->classe_id = $classe_id;
                                $resultat->matiere_id = $config->matiere_id;
                                $resultat->periode = $periode;
                                $resultat->annee_universitaire_id = $annee_universitaire_id;
                                $resultat->moyenne = 0;

                                // Récupérer le coefficient depuis la matière
                                $matiere = ESBTPMatiere::find($config->matiere_id);
                                $resultat->coefficient = $matiere ? ($matiere->coefficient_default ?? 1) : 1;

                                $resultat->created_by = Auth::id();
                                $resultat->updated_by = Auth::id();
                                $resultat->save();

                                Log::info('✅ Résultat créé automatiquement', [
                                    'matiere_id' => $config->matiere_id,
                                    'resultat_id' => $resultat->id
                                ]);
                            }
                        }

                        DB::commit();

                        // Récupérer les résultats nouvellement créés
                        $resultats = ESBTPResultat::withTrashed()
                            ->where('etudiant_id', $etudiant_id)
                            ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                            ->get();

                        Log::info('✅ Résultats créés avec succès à partir des configurations', [
                            'count' => $resultats->count()
                        ]);

        } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('❌ Erreur lors de la création automatique des résultats', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        return redirect()->route('esbtp.bulletins.config-matieres', [
                            'bulletin' => $etudiant_id,
                            'classe_id' => $classe_id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $annee_universitaire_id
                        ])->with('error', 'Erreur lors de la création des résultats: ' . $e->getMessage());
                    }
                }
            }

            // Si toujours aucun résultat, retourner une erreur
            if ($resultats->isEmpty()) {
                Log::error('❌ Impossible de créer des résultats pour cet étudiant');
                return redirect()->route('esbtp.bulletins.config-matieres', [
                    'bulletin' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('error', 'Aucune matière n\'a été configurée pour cet étudiant. Veuillez d\'abord configurer les matières.');
            }

            // Séparer les matières par type (général et technique)
            $matieresGenerales = collect();
            $matieresTechniques = collect();

            foreach ($resultats as $resultat) {
                // Récupérer la configuration de la matière pour déterminer son type
                $configMatiere = ESBTPConfigMatiere::withTrashed()
                    ->where('matiere_id', $resultat->matiere_id)
                    ->where('classe_id', $classe_id)
                    ->where('periode', $periode)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->first();

                if ($configMatiere) {
                    $config = json_decode($configMatiere->config, true);
                    $type = $config['type'] ?? null;

                    if ($type === 'general') {
                        $matieresGenerales->push($resultat);
                    } elseif ($type === 'technique') {
                        $matieresTechniques->push($resultat);
                    }
                }
            }

            Log::info('✅ Matières séparées par type', [
                'generales' => $matieresGenerales->count(),
                'techniques' => $matieresTechniques->count()
            ]);

            // Récupérer les noms des professeurs depuis le bulletin s'ils existent déjà
            $bulletin = ESBTPBulletin::where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

        $professeurs = [];
            if ($bulletin && $bulletin->professeurs) {
                $professeurs = json_decode($bulletin->professeurs, true) ?? [];
            }

            // Ajouter des noms de professeurs par défaut pour les matières courantes
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

            // Compléter les noms de professeurs manquants avec les valeurs par défaut
            foreach ($resultats as $resultat) {
                if (!isset($professeurs[$resultat->matiere_id]) && $resultat->matiere) {
                    $matiereName = $resultat->matiere->nom ?? $resultat->matiere->name ?? '';
                    if (isset($professeursMatiere[$matiereName])) {
                        $professeurs[$resultat->matiere_id] = $professeursMatiere[$matiereName];
                    }
                }
            }

            Log::info('✅ Préparation des données pour la vue edit-professeurs', [
                'resultats_generaux' => $matieresGenerales->count(),
                'resultats_techniques' => $matieresTechniques->count(),
                'professeurs' => count($professeurs)
            ]);

        return view('esbtp.bulletins.edit-professeurs', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'periode' => $periode,
            'anneeUniversitaire' => $anneeUniversitaire,
                'resultatsGeneraux' => $matieresGenerales,
                'resultatsTechniques' => $matieresTechniques,
            'professeurs' => $professeurs
        ]);
        } catch (\Exception $e) {
            Log::error('❌ Erreur non gérée dans editProfesseurs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard')->with('error', 'Erreur lors de l\'édition des professeurs: ' . $e->getMessage());
        }
    }

    /**
     * Affiche l'interface pour configurer les matières par type d'enseignement
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function configMatieresTypeFormationEdit(Request $request)
    {
        // Vérifier si l'utilisateur est superAdmin
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        // Récupérer les paramètres de la requête
        $etudiant_id = $request->input('bulletin');
        $classe_id = $request->input('classe_id');
        $periode = $request->input('periode');
        $annee_universitaire_id = $request->input('annee_universitaire_id');

        // Vérifier les données requises
        if (!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) {
            return back()->with('error', 'Paramètres incomplets pour configurer les matières.');
        }

        // Récupérer les objets associés
        $etudiant = ESBTPEtudiant::findOrFail($etudiant_id);
        $classe = ESBTPClasse::findOrFail($classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($annee_universitaire_id);

        // Récupérer toutes les matières associées à cette classe
        $matieres = ESBTPMatiere::whereHas('classes', function ($query) use ($classe_id) {
            $query->where('esbtp_classes.id', $classe_id);
        })->orderBy('nom')->get();

        // Récupérer la configuration existante, s'il y en a une
        $config = [];
        try {
            $configMatiere = ESBTPConfigMatiere::where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            if ($configMatiere) {
                $config = json_decode($configMatiere->config, true) ?? [];
            }
        } catch (\Exception $e) {
            \Log::warning('Erreur lors de la récupération de la configuration: ' . $e->getMessage());
        }

        // Organiser les matières par type
        $matieresTechniques = [];
        $matieresGenerales = [];

        if (!empty($config)) {
            // Utiliser la configuration existante
            $techniqueIds = $config['techniques'] ?? [];
            $generaleIds = $config['generales'] ?? [];

            foreach ($matieres as $matiere) {
                if (in_array($matiere->id, $techniqueIds)) {
                    $matieresTechniques[] = $matiere;
                } elseif (in_array($matiere->id, $generaleIds)) {
                    $matieresGenerales[] = $matiere;
                } else {
                    // Classification par défaut si la matière n'est pas dans la config
                    $nom = strtolower($matiere->nom);
                    if (strpos($nom, 'mathématique') !== false ||
                        strpos($nom, 'français') !== false ||
                        strpos($nom, 'anglais') !== false ||
                        strpos($nom, 'communication') !== false ||
                        strpos($nom, 'eco') !== false ||
                        strpos($nom, 'droit') !== false) {
                        $matieresGenerales[] = $matiere;
                    } else {
                        $matieresTechniques[] = $matiere;
                    }
                }
            }
        } else {
            // Classification par défaut si pas de configuration
            foreach ($matieres as $matiere) {
                $nom = strtolower($matiere->nom);
                if (strpos($nom, 'mathématique') !== false ||
                    strpos($nom, 'français') !== false ||
                    strpos($nom, 'anglais') !== false ||
                    strpos($nom, 'communication') !== false ||
                    strpos($nom, 'eco') !== false ||
                    strpos($nom, 'droit') !== false) {
                    $matieresGenerales[] = $matiere;
                } else {
                    $matieresTechniques[] = $matiere;
                }
            }
        }

        return view('esbtp.bulletins.config-matieres', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'periode' => $periode,
            'anneeUniversitaire' => $anneeUniversitaire,
            'matieresTechniques' => $matieresTechniques,
            'matieresGenerales' => $matieresGenerales
        ]);
    }

    /**
     * Enregistre la configuration des matières par type d'enseignement
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeConfigMatieresTypeFormation(Request $request)
    {
        // Vérifier si l'utilisateur est superAdmin
        if (!Auth::check() || !Auth::user()->hasRole('superAdmin')) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        // Récupérer les paramètres de la requête
        $etudiant_id = $request->input('etudiant_id');
        $classe_id = $request->input('classe_id');
        $periode = $request->input('periode');
        $annee_universitaire_id = $request->input('annee_universitaire_id');
        $matieresTechniques = $request->input('matieres_techniques', []);
        $matieresGenerales = $request->input('matieres_generales', []);

        // Vérifier les données requises
        if (!$classe_id || !$periode || !$annee_universitaire_id) {
            return back()->with('error', 'Paramètres incomplets pour sauvegarder la configuration des matières.');
        }

        // Créer ou mettre à jour la configuration
        try {
            $config = [
                'techniques' => $matieresTechniques,
                'generales' => $matieresGenerales
            ];

            $configMatiere = ESBTPConfigMatiere::updateOrCreate(
                [
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ],
                [
                    'config' => json_encode($config)
                ]
            );

            // Si l'étudiant est spécifié, rediriger vers la page d'édition des professeurs
            if ($etudiant_id) {
                return redirect()->route('esbtp.bulletins.edit-professeurs', [
                    'bulletin' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->with('success', 'Configuration des matières enregistrée avec succès');
            }

            // Sinon, rediriger vers la page des résultats d'étudiant ou la liste des bulletins
            return redirect()->route('esbtp.bulletins.index')
                ->with('success', 'Configuration des matières enregistrée avec succès');
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'enregistrement de la configuration: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue lors de l\'enregistrement de la configuration: ' . $e->getMessage());
        }
    }

    /**
     * Sauvegarde les noms des professeurs pour le bulletin
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
                'user_role' => Auth::user()->roles
            ]);

            // Valider les données d'entrée
            $validated = $request->validate([
                'professeurs' => 'sometimes|array',
                'etudiant_id' => 'required|exists:esbtp_etudiants,id',
                'classe_id' => 'required|exists:esbtp_classes,id',
                'periode' => 'required|in:semestre1,semestre2,annuel',
                'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
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
            if ($action === 'save_and_back') {
                return redirect()->route('esbtp.resultats.etudiant', [
                    'etudiant' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->with('success', 'Les noms des professeurs ont été enregistrés avec succès.');
            } elseif ($action === 'generate') {
                // Stocker tous les paramètres nécessaires dans la session
                session(['etudiant_id' => $etudiant_id]);
                session(['classe_id' => $classe_id]);
                session(['periode' => $periode]);
                session(['annee_universitaire_id' => $annee_universitaire_id]);
                session(['params' => $queryParams]);

                // Redirection vers la route de génération du bulletin avec l'ID de l'étudiant
                // comme paramètre principal et le reste dans la session
                return redirect()->route('esbtp.bulletins.generate', [
                    'etudiant_id' => $etudiant_id
                ])->with('success', 'Les noms des professeurs ont été enregistrés. Génération du bulletin en cours...');
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
     * Affiche la page de configuration des matières par type de formation (général/technique)
     * pour un bulletin spécifique
     */
    public function configMatieresTypeFormation2(Request $request)
    {
        // Vérification des permissions
        if (!in_array(auth()->user()->role, ['superAdmin', 'secretaire'])) {
            return back()->with('error', 'Vous n\'avez pas les droits pour effectuer cette action.');
        }

        // Récupération et validation des paramètres
        $classeId = $request->classe_id;
        $etudiantId = $request->etudiant_id;
        $periode = $request->periode;
        $anneeUniversitaireId = $request->annee_universitaire_id;

        if (!$classeId || !$etudiantId || !$periode || !$anneeUniversitaireId) {
            return back()->with('error', 'Paramètres manquants pour configurer les matières.');
        }

        try {
            // Récupération des objets nécessaires
            $classe = ESBTPClasse::findOrFail($classeId);
            $etudiant = ESBTPEtudiant::findOrFail($etudiantId);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($anneeUniversitaireId);

            // Récupération ou création du bulletin
            $bulletin = ESBTPBulletin::where([
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'periode' => $periode,
                'annee_universitaire_id' => $anneeUniversitaireId
            ])->first();

            // Recherche des résultats de l'étudiant pour récupérer les matières associées
            $resultats = ESBTPResultat::where([
                'etudiant_id' => $etudiantId,
                'classe_id' => $classeId,
                'periode' => $periode,
                'annee_universitaire_id' => $anneeUniversitaireId
            ])->get();

            // Extraction des IDs de matières à partir des résultats
            $matiereIds = $resultats->pluck('matiere_id')->unique()->toArray();

            // Récupération des objets matières complets à partir des IDs trouvés
            $matieres = ESBTPMatiere::whereIn('id', $matiereIds)
                ->with(['filiere', 'niveauEtude'])
                ->get();

            // Log pour déboguer
            Log::info('Configuration des matières - Nombre de matières trouvées: ' . count($matieres), [
                'etudiantId' => $etudiantId,
                'classeId' => $classeId,
                'matieresIds' => $matiereIds
            ]);

            // Récupération des configurations existantes si le bulletin existe
            $matieresGenerales = [];
            $matieresTechniques = [];

            if ($bulletin) {
                $configMatieres = json_decode($bulletin->config_matieres, true) ?? [];

                $matieresGenerales = $configMatieres['generales'] ?? [];
                $matieresTechniques = $configMatieres['techniques'] ?? [];

                // Log pour déboguer
                Log::info('Configuration existante trouvée', [
                    'generales' => $matieresGenerales,
                    'techniques' => $matieresTechniques
                ]);
            }

            // Préparation des données pour la vue
            $viewData = [
                'classe' => $classe,
                'etudiant' => $etudiant,
                'etudiant_id' => $etudiantId,
                'periode' => $periode,
                'anneeUniversitaire' => $anneeUniversitaire,
                'matieres' => $matieres,
                'matieresGenerales' => $matieresGenerales,
                'matieresTechniques' => $matieresTechniques
            ];

            if ($bulletin) {
                $viewData['bulletin_id'] = $bulletin->id;
            }

            return view('esbtp.bulletins.config-matieres', $viewData);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la configuration des matières', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la configuration des matières: ' . $e->getMessage());
        }
    }

    /**
     * Sauvegarde la configuration des matières par type de formation
     */
    public function saveConfigMatieres(Request $request)
    {
        // Vérification des permissions
        if (!in_array(auth()->user()->role, ['superAdmin', 'secretaire'])) {
            return back()->with('error', 'Vous n\'avez pas les droits pour effectuer cette action.');
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'classe_id' => 'required|exists:esbtp_classes,id',
            'periode' => 'required|in:semestre1,semestre2,annuel',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Récupération des paramètres
            $classeId = $request->classe_id;
            $etudiantId = $request->etudiant_id;
            $periode = $request->periode;
            $anneeUniversitaireId = $request->annee_universitaire_id;
            $bulletinId = $request->bulletin_id;

            // Récupération des matières sélectionnées
            $matieresGenerales = $request->matieres_generales ?? [];
            $matieresTechniques = $request->matieres_techniques ?? [];

            // Préparation des données à sauvegarder
            $configMatieres = [
                'generales' => $matieresGenerales,
                'techniques' => $matieresTechniques
            ];

            // Récupération ou création du bulletin
            if ($bulletinId) {
                $bulletin = ESBTPBulletin::find($bulletinId);
            } else {
                $bulletin = ESBTPBulletin::where([
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ])->first();
            }

            // Si aucun bulletin n'existe, le créer
            if (!$bulletin) {
                $bulletin = new ESBTPBulletin();
                $bulletin->etudiant_id = $etudiantId;
                $bulletin->classe_id = $classeId;
                $bulletin->periode = $periode;
                $bulletin->annee_universitaire_id = $annee_universitaire_id;
            }

            // Mise à jour de la configuration des matières
            $bulletin->config_matieres = json_encode($configMatieres);
            $bulletin->save();

            // Log pour déboguer
            Log::info('Configuration des matières enregistrée', [
                'bulletin_id' => $bulletin->id,
                'config' => $configMatieres
            ]);

            return redirect()
                ->route('esbtp.resultats.etudiant', $etudiantId)
                ->with('params', [
                    'classe_id' => $classeId,
                    'periode' => $periode,
                    'annee_universitaire_id' => $anneeUniversitaireId
                ])
                ->with('success', 'Configuration des matières enregistrée avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement de la configuration des matières', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Une erreur est survenue lors de l\'enregistrement de la configuration: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la page de configuration des matières par type de formation (général/technique)
     * pour un bulletin spécifique
     */
    public function configMatieresTypeFormation1(Request $request)
    {
        // Vérification des permissions
        if (!Auth::check() || !Auth::user()->hasRole(['superAdmin', 'secretaire'])) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        // Récupération et validation des paramètres
        $classe_id = $request->input('classe_id');
        $bulletin = $request->input('bulletin'); // Pour la compatibilité avec les anciennes routes
        $etudiant_id = (int) ($bulletin ?: $request->input('etudiant_id'));
        $periode = $request->input('periode');
        $annee_universitaire_id = (int) $request->input('annee_universitaire_id');

        if (!$classe_id || !$etudiant_id || !$periode || !$annee_universitaire_id) {
            return redirect()->route('esbtp.bulletins.index')
                ->with('error', 'Paramètres manquants pour la configuration des matières');
        }

        try {
            // Récupérer les objets nécessaires
            $classe = ESBTPClasse::with(['filiere', 'niveau'])->findOrFail($classe_id);
            $etudiant = ESBTPEtudiant::findOrFail($etudiant_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($annee_universitaire_id);

            // Récupérer les matières spécifiquement pour les résultats de cet étudiant
            $matieres = $this->getMatieresForBulletin($etudiant_id, $classe_id, $periode, $annee_universitaire_id);

            // Log pour déboguer
            Log::info('Configuration des matières - Matières trouvées pour bulletin', [
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'nombre_matieres' => count($matieres)
            ]);

            // Récupérer la configuration existante du bulletin
            $bulletin = ESBTPBulletin::where([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->first();

            $matieresGenerales = [];
            $matieresTechniques = [];

            if ($bulletin && $bulletin->config_matieres) {
                $config = json_decode($bulletin->config_matieres, true) ?? [];
                $matieresGenerales = $config['generales'] ?? [];
                $matieresTechniques = $config['techniques'] ?? [];

                Log::info('Configuration existante récupérée', [
                    'bulletin_id' => $bulletin->id,
                    'generales' => count($matieresGenerales),
                    'techniques' => count($matieresTechniques)
                ]);
            } else {
                // Classification automatique des matières
                foreach ($matieres as $matiere) {
                    $nomMatiere = strtolower($matiere->name ?? $matiere->nom ?? '');

                    if (preg_match('/(mathématique|français|anglais|communication|eco|droit)/i', $nomMatiere)) {
                        $matieresGenerales[] = $matiere->id;
                    } else {
                        $matieresTechniques[] = $matiere->id;
                    }
                }
            }

            return view('esbtp.bulletins.config-matieres', [
                'classe' => $classe,
                'etudiant' => $etudiant,
                'etudiant_id' => $etudiant_id,
                'matieres' => $matieres,
                'periode' => $periode,
                'anneeUniversitaire' => $anneeUniversitaire,
                'matieresGenerales' => $matieresGenerales,
                'matieresTechniques' => $matieresTechniques,
                'bulletin_id' => $bulletin ? $bulletin->id : null
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la configuration des matières', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('esbtp.bulletins.index')
                ->with('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }

    /**
     * Récupère les matières liées aux résultats d'un étudiant pour configurer le bulletin
     *
     * @param int $etudiant_id
     * @param int $classe_id
     * @param string $periode
     * @param int $annee_universitaire_id
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getMatieresForBulletin($etudiant_id, $classe_id, $periode, $annee_universitaire_id)
    {
        // Récupérer les résultats de l'étudiant
        $resultats = ESBTPResultat::where([
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id
        ])->get();

        // Si aucun résultat trouvé, essayer avec la période seulement
        if ($resultats->isEmpty()) {
            $resultats = ESBTPResultat::where([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode
            ])->get();
        }

        // Si toujours aucun résultat, essayer sans la période (tous les résultats)
        if ($resultats->isEmpty()) {
            $resultats = ESBTPResultat::where([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id
            ])->get();
        }

        // Collecter les IDs des matières
        $matiereIds = $resultats->pluck('matiere_id')->unique()->toArray();

        // Journaliser les résultats trouvés pour déboguer
        Log::info('Récupération des matières pour bulletin', [
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'resultats_trouves' => count($resultats),
            'matiere_ids' => $matiereIds
        ]);

        // Récupérer les matières correspondantes avec leurs détails
        $matieres = ESBTPMatiere::whereIn('id', $matiereIds)->get();

        // Si aucune matière trouvée, essayer de récupérer toutes les matières de la classe
        if ($matieres->isEmpty()) {
            $matieres = ESBTPMatiere::whereHas('classes', function ($query) use ($classe_id) {
                $query->where('esbtp_classes.id', $classe_id);
            })->get();

            Log::warning('Aucune matière trouvée via résultats, utilisation des matières de la classe', [
                'classe_id' => $classe_id,
                'nombre_matieres' => count($matieres)
            ]);
        }

        return $matieres;
    }

    /**
     * Sauvegarde la configuration des matières par type de formation
     */
    public function saveConfigMatieresTypeFormation1(Request $request)
    {
        // Vérification des permissions
        if (!Auth::check() || !Auth::user()->hasRole(['superAdmin', 'secretaire'])) {
            return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
        }

        // Récupérer les paramètres de la requête
        $etudiant_id = $request->input('etudiant_id');
        $classe_id = $request->input('classe_id');
        $periode = $request->input('periode');
        $annee_universitaire_id = $request->input('annee_universitaire_id');
        $matieresTechniques = $request->input('matieres_techniques', []);
        $matieresGenerales = $request->input('matieres_generales', []);

        // Vérifier les données requises
        if (!$classe_id || !$periode || !$annee_universitaire_id || !$etudiant_id) {
            return back()->with('error', 'Paramètres incomplets pour sauvegarder la configuration des matières.');
        }

        // Créer ou mettre à jour le bulletin
        try {
            // Rechercher ou créer le bulletin
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Sauvegarder la configuration
            $config = [
                'techniques' => array_values(array_filter($matieresTechniques)),
                'generales' => array_values(array_filter($matieresGenerales))
            ];

            $bulletin->config_matieres = json_encode($config);
            $bulletin->save();

            Log::info('Configuration des matières sauvegardée', [
                'bulletin_id' => $bulletin->id,
                'matieres_generales' => count($config['generales']),
                'matieres_techniques' => count($config['techniques'])
            ]);

            // Rediriger vers la page d'édition des professeurs
            return redirect()->route('esbtp.bulletins.edit-professeurs', [
                'bulletin' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ])->with('success', 'Configuration des matières enregistrée avec succès. Vous pouvez maintenant configurer les professeurs.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement de la configuration des matières', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Une erreur est survenue: ' . $e->getMessage());
        }
    }

    /**
     * Génère un bulletin PDF pour un étudiant
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generate(Request $request)
    {
        try {
            // Vérifier que l'utilisateur est autorisé
            if (!Auth::check() || !Auth::user()->hasAnyRole(['superAdmin', 'secretaire'])) {
                return redirect()->route('dashboard')->with('error', 'Accès non autorisé');
            }

            // Récupérer les paramètres
            $etudiant_id = (int) $request->input('etudiant_id');
            $classe_id = (int) $request->input('classe_id');
            $periode = $request->input('periode');
            $annee_universitaire_id = (int) $request->input('annee_universitaire_id');
            $note_assiduite = $request->input('note_assiduite', null);

            // Si les paramètres ne sont pas dans la requête, essayer de les récupérer depuis la session
            if (!$etudiant_id && session('etudiant_id')) {
                $etudiant_id = session('etudiant_id');
            }
            if (!$classe_id && session('classe_id')) {
                $classe_id = session('classe_id');
            }
            if (!$periode && session('periode')) {
                $periode = session('periode');
            }
            if (!$annee_universitaire_id && session('annee_universitaire_id')) {
                $annee_universitaire_id = session('annee_universitaire_id');
            }

            // Essayer de récupérer à partir de params si les autres méthodes ont échoué
            if ((!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) && session('params')) {
                $params = session('params');
                $etudiant_id = $etudiant_id ?: ($params['etudiant_id'] ?? ($params['bulletin'] ?? 0));
                $classe_id = $classe_id ?: ($params['classe_id'] ?? 0);
                $periode = $periode ?: ($params['periode'] ?? '');
                $annee_universitaire_id = $annee_universitaire_id ?: ($params['annee_universitaire_id'] ?? 0);
            }

            // NOUVELLE ÉTAPE: Si on a l'ID étudiant mais pas les autres paramètres, vérifier si un bulletin existe déjà
            if ($etudiant_id && (!$classe_id || !$periode || !$annee_universitaire_id)) {
                // Rechercher le bulletin le plus récent pour cet étudiant
                $existingBulletin = ESBTPBulletin::where('etudiant_id', $etudiant_id)
                    ->orderBy('updated_at', 'desc')
                    ->first();

                if ($existingBulletin) {
                    // Si un bulletin existe, utiliser ses paramètres
                    $classe_id = $classe_id ?: $existingBulletin->classe_id;
                    $periode = $periode ?: $existingBulletin->periode;
                    $annee_universitaire_id = $annee_universitaire_id ?: $existingBulletin->annee_universitaire_id;

                    Log::info('Paramètres récupérés depuis un bulletin existant', [
                        'bulletin_id' => $existingBulletin->id,
                        'classe_id' => $classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id
                    ]);
                } else {
                    // Si aucun bulletin n'existe, chercher dans les résultats de l'étudiant
                    $latestResult = ESBTPResultat::where('etudiant_id', $etudiant_id)
                        ->orderBy('updated_at', 'desc')
                        ->first();

                    if ($latestResult) {
                        $classe_id = $classe_id ?: $latestResult->classe_id;
                        $periode = $periode ?: $latestResult->periode;
                        $annee_universitaire_id = $annee_universitaire_id ?: $latestResult->annee_universitaire_id;

                        Log::info('Paramètres récupérés depuis les résultats', [
                            'result_id' => $latestResult->id,
                            'classe_id' => $classe_id,
                            'periode' => $periode,
                            'annee_universitaire_id' => $annee_universitaire_id
                        ]);
                    }
                }
            }

            // Log des paramètres reçus pour le débogage
            Log::info('Paramètres reçus pour la génération du bulletin', [
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id,
                'note_assiduite' => $note_assiduite
            ]);

            // Vérifier les données requises
            if (!$etudiant_id || !$classe_id || !$periode || !$annee_universitaire_id) {
                Log::warning('Paramètres incomplets pour générer le bulletin', [
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id
                ]);
                return back()->with('error', 'Tous les paramètres sont requis pour générer le bulletin.');
            }

            // Récupérer les objets requis pour s'assurer qu'ils existent
            $etudiant = ESBTPEtudiant::with(['inscriptions' => function($query) use ($annee_universitaire_id) {
                $query->where('annee_universitaire_id', $annee_universitaire_id);
            }])->findOrFail($etudiant_id);

            $classe = ESBTPClasse::with(['filiere', 'niveau'])->findOrFail($classe_id);
            $anneeUniversitaire = ESBTPAnneeUniversitaire::findOrFail($annee_universitaire_id);

            // Récupérer les résultats de l'étudiant pour cette classe, période et année universitaire
            $resultats = ESBTPResultat::with(['matiere'])
                ->where('etudiant_id', $etudiant_id)
                ->where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->get();

            if ($resultats->isEmpty()) {
                return back()->with('error', 'Aucun résultat trouvé pour cet étudiant dans cette période.');
            }

            // Récupérer tous les résultats de la classe pour calculer les statistiques
            $tousResultatsClasse = ESBTPResultat::where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->get();

            // Obtenir tous les étudiants de la classe
            $etudiantsClasse = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($classe_id, $annee_universitaire_id) {
                $query->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('status', 'active');
            })->get();

            $effectif = $etudiantsClasse->count();

            // Calculer les moyennes par étudiant - Amélioration du calcul
            $moyennesEtudiants = [];
            $etudiantsAvecMoyenne = 0;

            foreach ($etudiantsClasse as $etud) {
                $resultatsEtudiant = $tousResultatsClasse->filter(function($resultat) use ($etud) {
                    return $resultat->etudiant_id == $etud->id;
                });

                if ($resultatsEtudiant->isNotEmpty()) {
                    $moyenne = $this->calculerMoyenneGeneraleCollection($resultatsEtudiant);
                    if ($moyenne > 0) {
                        $moyennesEtudiants[$etud->id] = $moyenne;
                        $etudiantsAvecMoyenne++;
                    }
                }
            }

            // Calculer les statistiques de la classe de manière plus robuste
            $moyenneClasse = $etudiantsAvecMoyenne > 0 ? array_sum($moyennesEtudiants) / $etudiantsAvecMoyenne : 0;
            $meilleureClasse = !empty($moyennesEtudiants) ? max($moyennesEtudiants) : 0;
            $plusFaibleClasse = !empty($moyennesEtudiants) ? min($moyennesEtudiants) : 0;

            // Déterminer le rang de l'étudiant plus précisément
            // Tri des moyennes dans l'ordre décroissant
            arsort($moyennesEtudiants);
            $rang = 1;
            foreach ($moyennesEtudiants as $eid => $moyenne) {
                if ($eid == $etudiant_id) {
                    break;
                }
                $rang++;
            }

            // Récupérer la configuration des matières si elle existe
            $configMatieres = ESBTPConfigMatiere::where('classe_id', $classe_id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->first();

            $matieresGenerales = [];
            $matieresTechniques = [];

            if ($configMatieres) {
                $config = json_decode($configMatieres->config, true);
                $matieresGenerales = $config['generales'] ?? [];
                $matieresTechniques = $config['techniques'] ?? [];
            } else {
                // Classification par défaut basée sur le nom de la matière
                foreach ($resultats as $resultat) {
                    if (!isset($resultat->matiere) || !$resultat->matiere) {
                        continue;
                    }
                    $nom = strtolower($resultat->matiere->nom ?? $resultat->matiere->name ?? '');
                    if (strpos($nom, 'mathématique') !== false ||
                        strpos($nom, 'français') !== false ||
                        strpos($nom, 'anglais') !== false ||
                        strpos($nom, 'communication') !== false ||
                        strpos($nom, 'eco') !== false ||
                        strpos($nom, 'droit') !== false) {
                        $matieresGenerales[] = $resultat->matiere_id;
                    } else {
                        $matieresTechniques[] = $resultat->matiere_id;
                    }
                }
            }

            // Récupérer le bulletin existant ou en créer un nouveau
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id
            ]);

            // Récupérer ou initialiser les professeurs
            $professeurs = [];
            if ($bulletin->exists && $bulletin->professeurs) {
                $professeurs = json_decode($bulletin->professeurs, true) ?? [];
            }

            // Récupérer les absences de l'étudiant de manière plus détaillée
            $absencesJustifiees = 0;
            $absencesNonJustifiees = 0;

            // Tentative de récupération des absences de l'étudiant
            try {
                $absences = \App\Models\ESBTPAttendance::where('etudiant_id', $etudiant_id)
                    ->where('classe_id', $classe_id)
                    ->where('annee_universitaire_id', $annee_universitaire_id)
                    ->get();

                foreach ($absences as $absence) {
                    if ($absence->justified) {
                        $absencesJustifiees += $absence->hours;
                    } else {
                        $absencesNonJustifiees += $absence->hours;
                    }
                }

                Log::info('Absences de l\'étudiant', [
                    'etudiant_id' => $etudiant_id,
                    'justifiees' => $absencesJustifiees,
                    'non_justifiees' => $absencesNonJustifiees
                ]);
            } catch (\Exception $e) {
                Log::warning('Impossible de récupérer les absences: ' . $e->getMessage());
            }

            // Récupérer ou calculer la note d'assiduité
            $noteAssiduite = $bulletin->note_assiduite ?? $this->calculerNoteAssiduite($absencesJustifiees, $absencesNonJustifiees);

            // Si une note d'assiduité a été fournie manuellement, l'utiliser
            if ($note_assiduite !== null) {
                $noteAssiduite = floatval($note_assiduite);
                Log::info('Note d\'assiduité fournie manuellement', ['note' => $noteAssiduite]);
            }

            // Filtrer les résultats par type d'enseignement
            $resultatsGeneraux = $resultats->filter(function ($resultat) use ($matieresGenerales) {
                return in_array($resultat->matiere_id, $matieresGenerales);
            })->sortBy(function ($resultat) {
                return $resultat->matiere ? ($resultat->matiere->nom ?? $resultat->matiere->name ?? '') : '';
            });

            $resultatsTechniques = $resultats->filter(function ($resultat) use ($matieresTechniques) {
                return in_array($resultat->matiere_id, $matieresTechniques);
            })->sortBy(function ($resultat) {
                return $resultat->matiere ? ($resultat->matiere->nom ?? $resultat->matiere->name ?? '') : '';
            });

            // Calculer les moyennes d'enseignement général et technique
            $moyenneGenerale = $this->calculerMoyenneGeneraleCollection($resultatsGeneraux);
            $moyenneTechnique = $this->calculerMoyenneGeneraleCollection($resultatsTechniques);

            // Calculer la moyenne globale pondérée
            $moyenneGlobale = $this->calculerMoyenneGeneraleCollection($resultats);

            // Ajouter la note d'assiduité si elle existe
            $moyenneAvecAssiduite = $moyenneGlobale;
            if ($noteAssiduite > 0) {
                // Considérer que la note d'assiduité représente un petit pourcentage de la note finale
                // Ajustable selon les besoins de l'école
                $moyenneAvecAssiduite = ($moyenneGlobale * 0.95) + ($noteAssiduite * 0.05);
                Log::info('Calcul moyenne avec assiduité', [
                    'moyenneGlobale' => $moyenneGlobale,
                    'noteAssiduite' => $noteAssiduite,
                    'resultat' => $moyenneAvecAssiduite
                ]);
            }

            // Déterminer l'appréciation en fonction de la moyenne
            $appreciation = 'Travail Insuffisant';
            if ($moyenneAvecAssiduite >= 16) {
                $appreciation = 'Excellent';
            } elseif ($moyenneAvecAssiduite >= 14) {
                $appreciation = 'Très Bien';
            } elseif ($moyenneAvecAssiduite >= 12) {
                $appreciation = 'Bien';
            } elseif ($moyenneAvecAssiduite >= 10) {
                $appreciation = 'Assez Bien';
            } elseif ($moyenneAvecAssiduite >= 8) {
                $appreciation = 'Passable';
            }

            // Préparation des données pour la vue
            $data = [
                'etudiant' => $etudiant,
                'classe' => $classe,
                'periode' => $periode,
                'anneeUniversitaire' => $anneeUniversitaire,
                'resultatsGeneraux' => $resultatsGeneraux,
                'resultatsTechniques' => $resultatsTechniques,
                'professeurs' => $professeurs,
                'moyenneGenerale' => $moyenneGenerale,
                'moyenneTechnique' => $moyenneTechnique,
                'moyenneGlobale' => $moyenneGlobale,
                'moyenneAvecAssiduite' => $moyenneAvecAssiduite,
                'date_edition' => now()->format('d/m/Y'),
                // Statistiques de classe calculées
                'meilleure_moyenne' => $meilleureClasse,
                'plus_faible_moyenne' => $plusFaibleClasse,
                'moyenne_classe' => $moyenneClasse,
                // Informations sur les absences
                'absences_justifiees' => $absencesJustifiees,
                'absences_non_justifiees' => $absencesNonJustifiees,
                'note_assiduite' => $noteAssiduite,
                // Rang et mentions
                'rang' => $rang,
                'effectif' => $effectif,
                'mention' => $this->getMentionSimplifiee($moyenneAvecAssiduite),
                'appreciation' => $appreciation
            ];

            // Mise à jour du bulletin si nécessaire
            if (!$bulletin->exists || !$bulletin->moyenne_generale || $bulletin->moyenne_generale != $moyenneGlobale) {
                $bulletin->moyenne_generale = $moyenneGlobale;
                $bulletin->moyenne_avec_assiduite = $moyenneAvecAssiduite;
                $bulletin->note_assiduite = $noteAssiduite;
                $bulletin->rang = $rang;
                $bulletin->mention = $this->getMentionSimplifiee($moyenneAvecAssiduite);
                $bulletin->appreciation = $appreciation;
                $bulletin->updated_by = Auth::id();
                $bulletin->save();

                Log::info('Bulletin mis à jour', [
                    'id' => $bulletin->id,
                    'moyenne' => $moyenneGlobale,
                    'rang' => $rang
                ]);
            }

            // Retourner la vue du bulletin
            return view('esbtp.bulletins.bulletin-pdf', $data);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du bulletin', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la génération du bulletin: ' . $e->getMessage());
        }
    }

    /**
     * Affiche les résultats pour une classe spécifique.
     *
     * @param  Request  $request
     * @param  ESBTPClasse  $classe
     * @return \Illuminate\Http\Response
     */
    public function resultatClasse2(Request $request, $classe)
    {
        // Validation des paramètres
        $this->validate($request, [
            'periode' => 'nullable',
            'annee_universitaire_id' => 'nullable|exists:esbtp_annee_universitaires,id',
            'include_all_statuses' => 'nullable|boolean',
        ]);

        // Récupération des paramètres
        $periode = $request->periode ?? 'semestre1';
        $annee_universitaire_id = $request->annee_universitaire_id;
        $include_all_statuses = $request->has('include_all_statuses') ? $request->include_all_statuses : false;

        // Pour compatibilité avec la vue
        $annee_id = $annee_universitaire_id;
        $semestre = $periode;

        // Récupérer l'objet classe
        $classe = ESBTPClasse::findOrFail($classe);

        // Récupérer l'année universitaire si elle n'est pas spécifiée
        if (!$annee_universitaire_id) {
            $annee_universitaire_id = ESBTPAnneeUniversitaire::where('is_active', true)->first()->id ?? null;
            $annee_id = $annee_universitaire_id;
        }

        // Récupérer les années universitaires pour le filtre
        $anneesUniversitaires = ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();

        // Récupérer les périodes pour le filtre
        $periodes = [
            'semestre1' => (object) ['id' => 'semestre1', 'nom' => 'Semestre 1'],
            'semestre2' => (object) ['id' => 'semestre2', 'nom' => 'Semestre 2'],
            'annuel' => (object) ['id' => 'annuel', 'nom' => 'Annuel']
        ];

        // Récupérer les étudiants de la classe
        $query = ESBTPEtudiant::whereHas('inscriptions', function($query) use ($classe, $annee_universitaire_id, $include_all_statuses) {
            $query->where('classe_id', $classe->id)
                  ->where('annee_universitaire_id', $annee_universitaire_id);

            // Si on n'inclut pas tous les statuts, filtrer sur les inscriptions actives
            if (!$include_all_statuses) {
                $query->where('status', 'active');
            }
        });

        $students = $query->with(['inscriptions' => function($query) use ($classe, $annee_universitaire_id) {
            $query->where('classe_id', $classe->id)
                  ->where('annee_universitaire_id', $annee_universitaire_id);
        }])->get();

        // Récupérer les résultats pour chaque étudiant
        $resultats = [];
        $notes = [];
        $rangs = [];

        // Calculer la moyenne pour chaque étudiant
        foreach ($students as $student) {
            // Récupérer les résultats de l'étudiant
            $etudiantResultats = ESBTPResultat::where('etudiant_id', $student->id)
                ->where('classe_id', $classe->id)
                ->where('periode', $periode)
                ->where('annee_universitaire_id', $annee_universitaire_id)
                ->with('matiere')
                ->get();

            // Calculer la moyenne générale
            if ($etudiantResultats->isNotEmpty()) {
                $sommePoints = 0;
                $sommeCoefficients = 0;

                foreach ($etudiantResultats as $resultat) {
                    if ($resultat->moyenne !== null && $resultat->coefficient > 0) {
                        $sommePoints += $resultat->moyenne * $resultat->coefficient;
                        $sommeCoefficients += $resultat->coefficient;
                    }
                }

                $moyenne = $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : 0;
                $resultats[$student->id] = [
                    'moyenne' => $moyenne,
                    'details' => $etudiantResultats
                ];

                // Ajouter la moyenne pour le calcul des rangs
                $notes[$student->id] = $moyenne;
            }
        }

        // Calculer les rangs si des notes existent
        if (!empty($notes)) {
            // Trier les notes par ordre décroissant
            arsort($notes);

            // Attribuer les rangs
            $rang = 1;
            $lastNote = null;
            $lastRang = 1;

            foreach ($notes as $studentId => $note) {
                if ($lastNote !== null && $note < $lastNote) {
                    $rang = $lastRang + 1;
                }

                $rangs[$studentId] = $rang;
                $lastNote = $note;
                $lastRang = $rang;
                $rang++;
            }
        }

        // Retourner la vue avec les données
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
            'resultats',
            'rangs',
            'include_all_statuses'
        ));
    }

    /**
     * Migre les données des résultats vers les détails du bulletin.
     * Utile pour la transition vers la nouvelle structure.
     *
     * @param ESBTPBulletin $bulletin
     * @return void
     */
    public function migrateResultatsToDetails(ESBTPBulletin $bulletin)
    {
        // Chargement des résultats s'ils ne sont pas déjà chargés
        if (!$bulletin->relationLoaded('resultats')) {
            $bulletin->load('resultats.matiere');
        }

        foreach ($bulletin->resultats as $resultat) {
            // Vérifier si un détail existe déjà pour cette matière
            $detailExists = $bulletin->details()
                ->where('matiere_id', $resultat->matiere_id)
                ->exists();

            if (!$detailExists && $resultat->matiere) {
                // Créer un nouveau détail basé sur le résultat
                $bulletin->details()->create([
                    'matiere_id' => $resultat->matiere_id,
                    'note_cc' => null, // À déterminer en fonction de vos besoins
                    'note_examen' => null, // À déterminer en fonction de vos besoins
                    'moyenne' => $resultat->moyenne,
                    'moyenne_classe' => null, // À calculer si nécessaire
                    'coefficient' => $resultat->coefficient,
                    'credits' => null, // À déterminer en fonction de vos besoins
                    'credits_valides' => $resultat->moyenne >= 10 ? 1 : 0,
                    'rang' => null, // À calculer si nécessaire
                    'effectif' => $bulletin->effectif_classe,
                    'appreciation' => $resultat->commentaire,
                    'observations' => null
                ]);
            }
        }
    }
}
