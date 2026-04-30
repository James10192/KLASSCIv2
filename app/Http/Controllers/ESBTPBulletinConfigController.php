<?php

namespace App\Http\Controllers;

use App\Exceptions\CoefficientMissingException;
use App\Helpers\SettingsHelper;
use App\Models\Classe;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\ESBTP\ESBTPAbsenceService;
use Carbon\Carbon;
use App\Http\Requests\Bulletin\BulkUpdateMoyennesRequest;
use App\Http\Requests\Bulletin\GenerateClasseBulletinsRequest;
use App\Http\Requests\Bulletin\StoreBulletinRequest;
use App\Http\Requests\Bulletin\UpdateBulletinRequest;
use App\Http\Requests\Bulletin\UpdateMoyennesRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PDF;

class ESBTPBulletinConfigController extends Controller
{
    private $absenceService;
    private $bulletinService;

    public function __construct(\App\Services\ESBTP\ESBTPAbsenceService $absenceService, \App\Services\BulletinService $bulletinService)
    {
        $this->absenceService = $absenceService;
        $this->bulletinService = $bulletinService;
    }

    /**
     * Affiche le formulaire de configuration des types de matières
     *
     * @return \Illuminate\Http\Response
     */
    public function configMatieresTypeFormation(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (! Auth::check() || ! Auth::user()->can('bulletins.configure')) {
            abort(403, 'Accès non autorisé. Vous n\'avez pas la permission de configurer les bulletins.');
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
            'annee_universitaire_id' => $annee_universitaire_id,
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (! $etudiant_id || ! $classe_id || ! $periode || ! $annee_universitaire_id) {
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
            if (! $classeObj) {
                return back()->with('error', 'Classe introuvable.');
            }
            $classe = $classeObj;
        }

        if (! $etudiant || ! $classe || ! $anneeUniversitaire) {
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
                    if (! $classeFiliereId || ! $classeNiveauId) {
                        return false;
                    }

                    return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                        && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
                })
                ->values();
            $matieresClasseIds = $matieres->pluck('id')->all();

            $matieresFromNotes = ESBTPNote::where('etudiant_id', $etudiant_id)
                ->with(['evaluation.matiere'])
                ->whereHas('evaluation', function ($q) use ($annee_universitaire_id, $periode) {
                    $q->where('annee_universitaire_id', $annee_universitaire_id)
                        ->where('status', '!=', 'cancelled')
                        ->where('periode', $periode);
                })
                ->get()
                ->pluck('evaluation.matiere')
                ->filter()
                ->unique('id');
            $matieresNotesIds = $matieresFromNotes->pluck('id')->all();

            if ($matieresFromNotes->isNotEmpty()) {
                $matieres = $matieres
                    ->merge($matieresFromNotes)
                    ->unique('id')
                    ->values();
            }

            \Log::info('📚 Matières récupérées basées sur filière + niveau de la classe (config-matieres)', [
                'count' => $matieres->count(),
                'filiere_id' => $classeFiliereId,
                'niveau_id' => $classeNiveauId,
                'matiere_ids' => $matieres->pluck('id')->toArray(),
            ]);

            if ($matieres->isEmpty()) {
                // Rediriger vers la page des résultats avec message explicatif
                return redirect()->route('esbtp.resultats.etudiant', [
                    'etudiant' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                    'annee_universitaire_id' => $annee_universitaire_id,
                ])->with('error', 'Le bulletin ne peut pas être généré car aucune matière n\'a été trouvée pour cette classe. Veuillez configurer les matières de la classe (filière + niveau).');
            }
        } catch (\Exception $e) {
            \Log::error('❌ Erreur lors de la récupération des matières depuis la classe', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return redirect()->route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                'annee_universitaire_id' => $annee_universitaire_id,
            ])->with('error', 'Une erreur est survenue lors de la génération du bulletin : '.$e->getMessage());
        }

        // Récupérer les configurations existantes
        $configsMatieres = ESBTPConfigMatiere::withTrashed()->where([
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id,
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
            $matiereObj = new \stdClass;
            $matiereObj->id = $matiere->id;
            $matiereObj->nom = $matiere->nom ?? $matiere->name ?? '';
            $matiereObj->name = $matiere->name ?? $matiere->nom ?? '';
            $matiereObj->type_formation = $typeFormation;
            $matiereObj->sources = array_values(array_filter([
                in_array($matiere->id, $matieresClasseIds, true) ? 'classe' : null,
                in_array($matiere->id, $matieresNotesIds, true) ? 'notes' : null,
            ]));

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
            'annee_universitaire_id' => $annee_universitaire_id,
        ]);
    }

    /**
     * Enregistre la configuration des types de matières
     *
     * @return \Illuminate\Http\Response
     */
    public function saveConfigMatieresTypeFormation(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (! Auth::check() || ! Auth::user()->can('bulletins.configure')) {
            abort(403, 'Accès non autorisé. Vous n\'avez pas la permission de configurer les bulletins.');
        }

        // Journaliser les paramètres pour le débogage
        \Log::info('Paramètres reçus pour saveConfigMatieresTypeFormation:', [
            'request' => $request->all(),
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
                    'annee_universitaire_id' => $annee_universitaire_id,
                ])
                ->pluck('matiere_id')
                ->toArray();

            // Trouver les matières qui ne sont plus dans la nouvelle configuration
            $removedMatieres = array_diff(
                $existingConfigs,
                array_keys(array_filter($matiere_types, function ($type) {
                    return $type !== 'none';
                }))
            );

            // Supprimer définitivement ces configurations
            if (! empty($removedMatieres)) {
                ESBTPConfigMatiere::withTrashed()
                    ->where([
                        'classe_id' => $classe_id,
                        'periode' => $periode,
                        'annee_universitaire_id' => $annee_universitaire_id,
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
                    $matieresGenerales[] = (int) $matiere_id;
                    // Utiliser le même type que dans le formulaire pour la cohérence
                    $type_value = 'general';
                } elseif ($type == 'technique') {
                    $matieresTechniques[] = (int) $matiere_id;
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
                        'annee_universitaire_id' => $annee_universitaire_id,
                    ],
                    [
                        'config' => json_encode(['type' => $type_value]),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'deleted_at' => null, // Restaurer l'enregistrement s'il était soft-deleted
                    ]
                );
            }

            // Récupérer ou créer le bulletin pour cet étudiant
            $bulletin = ESBTPBulletin::firstOrNew([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id,
            ]);

            // Préparer la configuration des matières pour le bulletin
            $configMatieres = [
                'generales' => $matieresGenerales,
                'techniques' => $matieresTechniques,
            ];

            // Sauvegarder la configuration dans le bulletin
            $bulletin->config_matieres = json_encode($configMatieres);
            if (! $bulletin->professeurs) {
                $bulletin->professeurs = '{}';
            }
            $bulletin->save();

            \Log::info('Configuration des matières sauvegardée dans le bulletin', [
                'bulletin_id' => $bulletin->id,
                'config_matieres' => $bulletin->config_matieres,
                'matieres_generales' => count($matieresGenerales),
                'matieres_techniques' => count($matieresTechniques),
            ]);

            DB::commit();

            // Déterminer l'action suivante
            $action = $request->action ?? 'save';

            if ($action === 'edit_professeurs' || $action === 'save_and_edit_profs') {
                // Rediriger vers l'édition des professeurs
                $url = '/esbtp-special/bulletins/edit-professeurs?'.http_build_query([
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id,
                ]);

                return redirect()->to($url)->with('success', 'Configuration des matières enregistrée avec succès.');
            } elseif ($action === 'return_results' || $action === 'save_and_return' || $action === 'save') {
                // Rediriger vers les résultats de l'étudiant
                $url = "/esbtp/resultats/etudiant/{$etudiant_id}?".http_build_query([
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id,
                ]);

                return redirect()->to($url)->with('success', 'Configuration des matières enregistrée avec succès.');
            } else {
                // Rester sur la même page
                return back()->with('success', 'Configuration des matières enregistrée avec succès.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur lors de la sauvegarde de la configuration des matières : '.$e->getMessage());
            \Log::error('Trace : '.$e->getTraceAsString());

            return back()->with('error', 'Erreur lors de la sauvegarde : '.$e->getMessage());
        }
    }

    /**
     * Affiche le formulaire d'édition des professeurs
     *
     * @return \Illuminate\Http\Response
     */
    public function editProfesseurs(Request $request)
    {
        // Vérifier que l'utilisateur est autorisé
        if (! Auth::check() || ! Auth::user()->can('bulletins.configure')) {
            abort(403, 'Accès non autorisé. Vous n\'avez pas la permission de configurer les bulletins.');
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
            'annee_universitaire_id' => $annee_universitaire_id,
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (! $etudiant_id || ! $classe_id || ! $periode || ! $annee_universitaire_id) {
            return back()->with('error', 'Paramètres manquants pour l\'édition des professeurs.');
        }

        // Récupérer l'étudiant, la classe et l'année universitaire
        $etudiant = ESBTPEtudiant::find($etudiant_id);
        $classe = ESBTPClasse::find($classe_id);
        $anneeUniversitaire = ESBTPAnneeUniversitaire::find($annee_universitaire_id);

        if (! $etudiant || ! $classe || ! $anneeUniversitaire) {
            return back()->with('error', 'Données introuvables.');
        }

        // Récupérer le bulletin s'il existe
        $bulletin = ESBTPBulletin::where([
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id,
        ])->first();

        // Récupérer les matières basées sur la combinaison filière + niveau de la classe
        $classeFiliereId = $classe->filiere_id;
        $classeNiveauId = $classe->niveau_etude_id;

        $matieresFiltrees = ESBTPMatiere::with(['filieres:id,name,code', 'niveaux:id,name,code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(function (ESBTPMatiere $matiere) use ($classeFiliereId, $classeNiveauId) {
                if (! $classeFiliereId || ! $classeNiveauId) {
                    return false;
                }

                return $matiere->filieres->pluck('id')->contains($classeFiliereId)
                    && $matiere->niveaux->pluck('id')->contains($classeNiveauId);
            })
            ->values();
        $matieresClasseIds = $matieresFiltrees->pluck('id')->all();

        // Vérifier si la configuration des matières a été faite pour ces matières
        $configsMatieres = ESBTPConfigMatiere::where([
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id,
        ])->whereIn('matiere_id', $matieresFiltrees->pluck('id'))->get();

        if ($configsMatieres->isEmpty()) {
            // Rediriger vers la configuration des matières
            $url = '/esbtp-special/bulletins/config-matieres?'.http_build_query([
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id,
            ]);

            return redirect()->to($url)->with('error', 'Vous devez d\'abord configurer les types de matières.');
        }

        // Récupérer les matières avec leur type de formation
        $matieres = [];
        $notesMatieres = ESBTPNote::where('etudiant_id', $etudiant_id)
            ->with(['evaluation.matiere'])
            ->whereHas('evaluation', function ($q) use ($annee_universitaire_id, $periode) {
                $q->where('annee_universitaire_id', $annee_universitaire_id)
                    ->where('status', '!=', 'cancelled')
                    ->where('periode', $periode);
            })
            ->get()
            ->pluck('evaluation.matiere')
            ->filter()
            ->unique('id');
        $notesMatieresIds = $notesMatieres->pluck('id')->all();

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
                    'type_formation' => $typeFormation,
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
                    $matiereName = $config->matiere->nom ?? $config->matiere->name ?? 'Matière #'.$config->matiere_id;
                }

                // Journaliser pour vérifier le nom de la matière
                \Log::info('Matière ajoutée:', [
                    'id' => $config->matiere_id,
                    'nom_recupere' => $matiereName,
                    'matiere_object' => $config->matiere ? 'Existe' : 'Null',
                    'matiere_nom_property' => $config->matiere ? ($config->matiere->nom ?? 'Non défini') : 'N/A',
                    'matiere_name_property' => $config->matiere ? ($config->matiere->name ?? 'Non défini') : 'N/A',
                ]);

                $matieres[] = [
                    'id' => $config->matiere_id,
                    'nom' => $matiereName,
                    'type_formation' => $typeFormation,
                    'professeur_nom' => $professeurNom,
                    'sources' => array_values(array_filter([
                        in_array($config->matiere_id, $matieresClasseIds, true) ? 'classe' : null,
                        in_array($config->matiere_id, $notesMatieresIds, true) ? 'notes' : null,
                    ])),
                ];
            }
        }

        $matieresIds = collect($matieres)->pluck('id')->unique()->all();
        if ($notesMatieres->isNotEmpty()) {
            $configMatieres = $bulletin && $bulletin->config_matieres
                ? (json_decode($bulletin->config_matieres, true) ?: ['generales' => [], 'techniques' => []])
                : ['generales' => [], 'techniques' => []];
            $professeursExisting = $bulletin && $bulletin->professeurs
                ? (json_decode($bulletin->professeurs, true) ?: [])
                : [];

            foreach ($notesMatieres as $matiere) {
                if (in_array($matiere->id, $matieresIds, true)) {
                    continue;
                }

                $nomMatiere = strtolower($matiere->nom ?? $matiere->name ?? '');
                if (in_array($matiere->id, $configMatieres['generales'] ?? [], true)) {
                    $typeFormation = 'general';
                } elseif (in_array($matiere->id, $configMatieres['techniques'] ?? [], true)) {
                    $typeFormation = 'technique';
                } elseif (
                    str_contains($nomMatiere, 'math') ||
                    str_contains($nomMatiere, 'anglais') ||
                    str_contains($nomMatiere, 'français') ||
                    str_contains($nomMatiere, 'francais') ||
                    str_contains($nomMatiere, 'communication')
                ) {
                    $typeFormation = 'general';
                } else {
                    $typeFormation = 'technique';
                }

                $matieres[] = [
                    'id' => $matiere->id,
                    'nom' => $matiere->nom ?? $matiere->name ?? 'Matière #'.$matiere->id,
                    'type_formation' => $typeFormation,
                    'professeur_nom' => $professeursExisting[$matiere->id] ?? '',
                    'sources' => array_values(array_filter([
                        in_array($matiere->id, $matieresClasseIds, true) ? 'classe' : null,
                        in_array($matiere->id, $notesMatieresIds, true) ? 'notes' : null,
                    ])),
                ];
            }
        }

        // Journaliser les matières trouvées
        \Log::info('Matières trouvées pour editProfesseurs:', [
            'nombre_matieres' => count($matieres),
            'matieres' => $matieres,
        ]);

        // Grouper les matières par type de formation
        $matieresGenerales = array_filter($matieres, function ($matiere) {
            return $matiere['type_formation'] === 'general';
        });

        $matieresProf = array_filter($matieres, function ($matiere) {
            return $matiere['type_formation'] === 'technique';
        });

        // Journaliser les résultats du filtrage
        \Log::info('Résultats du filtrage des matières:', [
            'matieres_generales' => count($matieresGenerales),
            'matieres_techniques' => count($matieresProf),
        ]);

        // Récupérer les professeurs du bulletin
        $professeurs = [];
        if ($bulletin && $bulletin->professeurs) {
            $professeurs = json_decode($bulletin->professeurs, true) ?: [];
            \Log::info('📋 Professeurs from bulletin', [
                'bulletin_id' => $bulletin->id,
                'professeurs' => $professeurs,
            ]);
        } else {
            \Log::warning('⚠️ No bulletin or professeurs found', [
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
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
                'nom' => $item['nom'],
            ]);

            return (object) [
                'matiere_id' => $item['id'],
                'sources' => $item['sources'] ?? [],
                'matiere' => (object) [
                    'nom' => $item['nom'],
                    'name' => $item['nom'],  // Adding both for compatibility
                ],
            ];
        });

        $resultatsTechniques = collect($matieresProf)->map(function ($item) {
            // Vérifier et journaliser chaque élément
            \Log::debug('Transformation matière technique:', [
                'id' => $item['id'],
                'nom' => $item['nom'],
            ]);

            return (object) [
                'matiere_id' => $item['id'],
                'sources' => $item['sources'] ?? [],
                'matiere' => (object) [
                    'nom' => $item['nom'],
                    'name' => $item['nom'],  // Adding both for compatibility
                ],
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
            'enseignantsParMatiere' => $enseignantsParMatiere,
        ]);
    }

    /**
     * Sauvegarde les professeurs assignés aux matières pour un bulletin
     *
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
                'action_value' => $request->input('action'),
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
                'annee_universitaire_id' => $annee_universitaire_id,
            ]);

            // Si le bulletin n'existe pas encore, initialiser les propriétés de base
            if (! $bulletin->exists) {
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

                // Récupérer TOUS les étudiants inscrits dans cette classe/année
                $autresEtudiantIds = \App\Models\ESBTPEtudiant::whereHas('inscriptions', function ($q) use ($classe_id, $annee_universitaire_id) {
                    $q->where('classe_id', $classe_id)
                        ->where('annee_universitaire_id', $annee_universitaire_id);
                })
                ->where('id', '!=', $etudiant_id)
                ->pluck('id');

                \DB::beginTransaction();
                try {
                    foreach ($autresEtudiantIds as $autreEtudiantId) {
                        $autreBulletin = ESBTPBulletin::firstOrCreate(
                            [
                                'etudiant_id' => $autreEtudiantId,
                                'classe_id' => $classe_id,
                                'periode' => $periode,
                                'annee_universitaire_id' => $annee_universitaire_id,
                            ],
                            ['created_by' => Auth::id()]
                        );

                        $autreBulletin->professeurs = json_encode($professeurs);
                        $autreBulletin->updated_by = Auth::id();
                        $autreBulletin->save();
                        $bulletinsPropages++;
                    }
                    \DB::commit();
                } catch (\Exception $e) {
                    \DB::rollBack();
                    Log::error('Erreur propagation professeurs: ' . $e->getMessage());
                }

                Log::info("✅ Propagation terminée: {$bulletinsPropages} bulletins mis à jour/créés");
            }

            // Vérifier quelle action a été choisie via le bouton submit
            $action = $request->input('action', '');

            // Préparer les paramètres communs pour les redirections
            $queryParams = [
                'bulletin' => $etudiant_id,
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id,
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
                    'annee_universitaire_id' => $annee_universitaire_id,
                ])->with('success', $successMessage);
            } elseif ($action === 'edit') {
                // Rester sur la page d'édition des professeurs
                return redirect()->route('esbtp.bulletins.edit-professeurs', [
                    'etudiant_id' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id,
                ])->with('success', $successMessage);
            } elseif ($action === 'generate') {
                // Redirection vers la route de génération du bulletin PDF
                return redirect()->route('esbtp.bulletins.pdf-params', [
                    'bulletin' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode,
                    'annee_universitaire_id' => $annee_universitaire_id,
                ])->with('success', $successMessage.' Génération du bulletin en cours...');
            }

            // Redirection par défaut vers la page des résultats de l'étudiant
            return redirect()->route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id,
            ])->with('success', 'Les noms des professeurs ont été enregistrés avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde des professeurs: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la sauvegarde: '.$e->getMessage());
        }
    }

    /**
     * Affiche la page d'édition des absences pour un bulletin
     *
     * @return \Illuminate\View\View
     */
    public function editAbsences(Request $request)
    {
        // Vérifier les permissions
        if (! Auth::check() || ! Auth::user()->can('bulletins.configure')) {
            abort(403, 'Accès non autorisé. Vous n\'avez pas la permission de configurer les bulletins.');
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
            'annee_universitaire_id' => $annee_universitaire_id,
        ]);

        // Vérifier que tous les paramètres requis sont présents
        if (! $etudiant_id || ! $classe_id || ! $periode || ! $annee_universitaire_id) {
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

        if (! $etudiant || ! $classe || ! $anneeUniversitaire) {
            return back()->with('error', 'Données introuvables.');
        }

        // Récupérer ou créer le bulletin
        $bulletin = ESBTPBulletin::firstOrNew([
            'etudiant_id' => $etudiant_id,
            'classe_id' => $classe_id,
            'periode' => $periode,
            'annee_universitaire_id' => $annee_universitaire_id,
        ]);

        // Si le bulletin n'existe pas encore, initialiser les propriétés de base
        if (! $bulletin->exists) {
            $bulletin->created_by = Auth::id();
            $bulletin->save();
        }

        // Calculer les absences automatiquement via le système existant
        try {
            $absencesCalculees = $this->calculerAbsencesDetailes($bulletin);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du calcul automatique des absences: '.$e->getMessage());
            $absencesCalculees = [
                'justifiees' => 0,
                'non_justifiees' => 0,
                'total' => 0,
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
        $noteAssiduite = $this->bulletinService->calculerNoteAssiduite(
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
            'totalAbsences' => ($absencesJustifieesDB ?? 0) + ($absencesNonJustifieesDB ?? 0),
        ]);
    }

    /**
     * Sauvegarde les absences modifiées pour un bulletin
     *
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
                'all_request_data' => $request->all(),
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
                'annee_universitaire_id' => $annee_universitaire_id,
            ]);

            // Si le bulletin n'existe pas encore, initialiser les propriétés de base
            if (! $bulletin->exists) {
                $bulletin->created_by = Auth::id();
            }

            // Mettre à jour les absences
            $bulletin->absences_justifiees = $absencesJustifiees;
            $bulletin->absences_non_justifiees = $absencesNonJustifiees;
            $bulletin->total_absences = $absencesJustifiees + $absencesNonJustifiees;

            // Calculer et mettre à jour la note d'assiduité
            $bulletin->note_assiduite = $this->bulletinService->calculerNoteAssiduite(
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
                'note_assiduite' => $bulletin->note_assiduite,
            ]);

            // Vérifier quelle action a été choisie via le bouton submit
            $action = $request->input('action', '');

            // Préparer les paramètres communs pour les redirections
            $queryParams = [
                'bulletin' => $etudiant_id,
                'etudiant_id' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode,
                'annee_universitaire_id' => $annee_universitaire_id,
            ];

            // Redirection en fonction de l'action choisie
            if ($action === 'save_and_back' || $action === 'save_and_return') {
                return redirect()->route('esbtp.resultats.etudiant', [
                    'etudiant' => $etudiant_id,
                    'classe_id' => $classe_id,
                    'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                    'annee_universitaire_id' => $annee_universitaire_id,
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
                    'annee_universitaire_id' => $annee_universitaire_id,
                ])->with('success', 'Les absences ont été enregistrées. Génération du bulletin en cours...');
            }

            // Par défaut, retourner aux résultats de l'étudiant
            return redirect()->route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant_id,
                'classe_id' => $classe_id,
                'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                'annee_universitaire_id' => $annee_universitaire_id,
            ])->with('success', 'Les absences ont été enregistrées avec succès.');

        } catch (\Exception $e) {
            Log::error('❌ Erreur dans saveAbsences: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de l\'enregistrement des absences: '.$e->getMessage());
        }
    }
}
