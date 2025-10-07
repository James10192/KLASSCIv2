<?php

namespace App\Http\Controllers;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPDepartment;
use App\Models\ESBTPCycle;
use App\Models\ESBTPClass;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PDF;

class ESBTPStudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view_students', ['only' => ['index', 'show', 'genererCertificat']]);
        $this->middleware('permission:create_students', ['only' => ['create', 'store']]);
        $this->middleware('permission:edit_students', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete_students', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        // Récupérer les filtres de recherche
        $search = $request->input('search');
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $status = $request->input('status');

        // Construire la requête avec les filtres
        $query = ESBTPEtudiant::query()
            ->with(['user', 'inscriptions' => function($q) {
                $q->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire']);
            }]);

        // Appliquer les filtres
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('statut', $status);
        }

        if ($filiere || $niveau || $annee) {
            $query->whereHas('inscriptions', function($q) use ($filiere, $niveau, $annee) {
                if ($filiere) {
                    $q->where('filiere_id', $filiere);
                }
                if ($niveau) {
                    $q->where('niveau_id', $niveau);
                }
                if ($annee) {
                    $q->where('annee_universitaire_id', $annee);
                }
            });
        }

        // Récupérer les étudiants paginés
        $etudiants = $query->latest()->paginate(15);

        // Récupérer les listes pour les filtres
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        return view('esbtp.etudiants.index', compact(
            'etudiants',
            'filieres',
            'niveaux',
            'annees',
            'search',
            'filiere',
            'niveau',
            'annee',
            'status'
        ));
    }

    public function create()
    {
        return redirect()->route('esbtp.inscriptions.create')
            ->with('info', 'Veuillez utiliser le formulaire d\'inscription pour ajouter un nouvel étudiant.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule' => 'required|string|unique:esbtp_etudiants,matricule',
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'sexe' => 'required|in:M,F',
            'date_naissance' => 'required|date',
            'lieu_naissance' => 'required|string|max:255',
            'nationalite' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'email_personnel' => 'required|email|max:255',
            'statut' => 'required|in:actif,inactif'
        ]);

        ESBTPEtudiant::create($validated);

        return redirect()->route('esbtp.etudiants.index')->with('success', 'Étudiant créé avec succès.');
    }

    public function show(ESBTPEtudiant $etudiant)
    {
        // Charger les relations nécessaires
        $etudiant->load([
            'user',
            'parents',
            'inscriptions' => function($q) {
                $q->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire'])
                  ->orderBy('date_inscription', 'desc');
            },
            'inscriptions.paiements' => function($q) {
                $q->orderBy('date_paiement', 'desc');
            }
        ]);

        return view('esbtp.etudiants.show', compact('etudiant'));
    }

    public function edit(ESBTPEtudiant $etudiant)
    {
        // Charger les relations nécessaires
        $etudiant->load(['user', 'parents', 'inscriptions.filiere', 'inscriptions.niveau', 'inscriptions.classe']);

        // Récupérer les données pour les selects
        $filieres = ESBTPFiliere::where('is_active', true)->get();
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->get();
        $classes = ESBTPClasse::where('is_active', true)->get();
        $annees = ESBTPAnneeUniversitaire::orderBy('start_date', 'desc')->get();

        return view('esbtp.etudiants.edit', compact(
            'etudiant',
            'filieres',
            'niveaux',
            'classes',
            'annees'
        ));
    }

    public function update(Request $request, ESBTPEtudiant $etudiant)
    {
        // Déléguer à la version enrichie du contrôleur pour conserver l'unique flux (parents, photo, logs, etc.)
        return app(ESBTPEtudiantController::class)->update($request, $etudiant);
    }

    public function destroy(ESBTPEtudiant $etudiant)
    {
        // Vérifier les permissions
        if (!auth()->user()->can('delete_students')) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour supprimer des étudiants.'
            ], 403);
        }

        try {
            $keepUser = request()->input('keep_user', false);

            // Utiliser la commande Artisan pour une suppression complète et sécurisée
            $exitCode = \Artisan::call('esbtp:delete-student', [
                'identifier' => $etudiant->id,
                '--force' => true,
                '--keep-user' => $keepUser
            ]);

            if ($exitCode === 0) {
                // Succès
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Étudiant supprimé avec succès.',
                        'redirect' => route('esbtp.etudiants.index')
                    ]);
                }

                return redirect()->route('esbtp.etudiants.index')
                    ->with('success', 'Étudiant supprimé avec succès.');
            } else {
                throw new \Exception('La commande de suppression a échoué.');
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression d\'étudiant', [
                'etudiant_id' => $etudiant->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            ESBTPEtudiant::withTrashed()->findOrFail($id)->restore();
            return redirect()->route('esbtp.etudiants.index')
                ->with('success', 'Étudiant restauré avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la restauration: ' . $e->getMessage());
        }
    }

    public function genererCertificat(ESBTPEtudiant $etudiant)
    {
        // Charger les relations nécessaires
        $etudiant->load([
            'inscriptions' => function($q) {
                $q->with(['filiere', 'niveau', 'classe', 'anneeUniversitaire'])
                  ->orderBy('date_inscription', 'desc')
                  ->first();
            }
        ]);

        // Vérifier si l'étudiant a une inscription active
        if (!$etudiant->inscriptions->count()) {
            return back()->with('error', 'Aucune inscription trouvée pour cet étudiant.');
        }

        $inscription = $etudiant->inscriptions->first();

        // Générer le PDF
        $pdf = PDF::loadView('esbtp.etudiants.certificat', compact('etudiant', 'inscription'));

        // Retourner le PDF pour téléchargement
        return $pdf->download('certificat_scolarite_' . Str::slug($etudiant->nom_complet) . '.pdf');
    }
}
