<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Services\TroncCommunService;
use Illuminate\Http\Request;

class ESBTPSpecialisationController extends Controller
{
    protected TroncCommunService $troncCommunService;

    public function __construct(TroncCommunService $troncCommunService)
    {
        $this->troncCommunService = $troncCommunService;
        $this->middleware(['auth', 'role:superAdmin|secretaire']);
    }

    /**
     * Affiche le formulaire de spécialisation pour une inscription tronc commun.
     */
    public function show(ESBTPInscription $inscription)
    {
        if (!$this->troncCommunService->isTroncCommunEnabled()) {
            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('error', 'Le mode tronc commun n\'est pas activé.');
        }

        $inscription->load(['etudiant', 'filiere', 'classe', 'niveau', 'anneeUniversitaire']);

        if (!$inscription->filiere || !$inscription->filiere->isTroncCommun()) {
            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('error', 'Cette inscription n\'est pas sur une filière tronc commun.');
        }

        if ($inscription->hasSpecialisation()) {
            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('error', 'Cette inscription a déjà une spécialisation.');
        }

        $specialisations = $this->troncCommunService->getSpecialisationsDisponibles($inscription->filiere);
        $totalPaye = $inscription->paiements()->where('status', 'validé')->sum('montant');

        return view('esbtp.inscriptions.specialisation', compact(
            'inscription',
            'specialisations',
            'totalPaye'
        ));
    }

    /**
     * Retourne les classes disponibles pour une spécialisation (AJAX).
     */
    public function getClasses(ESBTPInscription $inscription, Request $request)
    {
        $filiereId = $request->query('filiere_id');

        $classes = ESBTPClasse::where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $inscription->niveau_id)
            ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
            ->where('is_active', true)
            ->get()
            ->map(function ($classe) {
                return [
                    'id' => $classe->id,
                    'name' => $classe->name,
                    'code' => $classe->code,
                    'places_totales' => $classe->places_totales,
                    'nombre_etudiants' => $classe->nombre_etudiants,
                    'places_disponibles' => $classe->places_disponibles,
                ];
            });

        return response()->json(['classes' => $classes]);
    }

    /**
     * Crée l'inscription de spécialisation.
     */
    public function store(ESBTPInscription $inscription, Request $request)
    {
        $request->validate([
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
        ]);

        try {
            $inscriptionSpec = $this->troncCommunService->creerInscriptionSpecialisation(
                $inscription,
                $request->classe_id
            );

            return redirect()->route('esbtp.inscriptions.show', $inscriptionSpec)
                ->with('success', 'Spécialisation effectuée avec succès. L\'étudiant est maintenant inscrit en ' . $inscriptionSpec->filiere->name . '.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la spécialisation : ' . $e->getMessage());
        }
    }
}
