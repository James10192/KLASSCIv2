<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\BtsTroncCommun\BtsOrientationService;
use App\Http\Controllers\Controller;
use App\Models\ESBTPInscription;
use App\Services\TroncCommunService;
use Illuminate\Http\Request;

class ESBTPSpecialisationController extends Controller
{
    public function __construct(
        protected TroncCommunService $troncCommunService,
        protected BtsOrientationService $orientationService
    ) {
        $this->middleware(['auth', 'permission:inscriptions.specialisation.manage']);
    }

    public function show(ESBTPInscription $inscription)
    {
        if (! $this->troncCommunService->isTroncCommunEnabled()) {
            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('error', "Le mode tronc commun n'est pas activé.");
        }

        $inscription->load([
            'etudiant',
            'filiere',
            'classe.orientationTargets.targetClasse.filiere',
            'niveau',
            'anneeUniversitaire',
            'phases',
        ]);

        if (! $inscription->filiere || ! $inscription->filiere->isTroncCommun()) {
            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('error', "Cette inscription n'est pas sur une filière tronc commun.");
        }

        $this->orientationService->ensureInitialPhase($inscription);

        if ($inscription->phases->contains(fn ($phase) => $phase->type_phase === 'specialisation' && $phase->is_active)) {
            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('error', 'Cette inscription a déjà une spécialisation.');
        }

        // Source canonique : filières dérivées des classes cibles (ClasseOrientationTarget).
        // Si aucune target classe configurée, fallback sur le legacy filières-enfants (parent_id).
        $targetFilieres = collect($inscription->classe?->orientationTargets ?? [])
            ->where('is_active', true)
            ->pluck('targetClasse.filiere')
            ->filter()
            ->unique('id')
            ->values();

        $specialisations = $targetFilieres->isNotEmpty()
            ? $targetFilieres
            : $this->troncCommunService->getSpecialisationsDisponibles($inscription->filiere);

        $totalPaye = $inscription->paiements()->where('status', 'validé')->sum('montant');

        return view('esbtp.inscriptions.specialisation', compact(
            'inscription',
            'specialisations',
            'totalPaye'
        ));
    }

    public function getClasses(ESBTPInscription $inscription, Request $request)
    {
        $filiereId = $request->query('filiere_id');

        $inscription->loadMissing(['classe.orientationTargets.targetClasse']);

        // Source canonique : ClasseOrientationTarget (mapping manuel via admin)
        $classes = collect($inscription->classe?->orientationTargets ?? [])
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->map(fn ($target) => $target->targetClasse)
            ->filter()
            ->filter(fn ($classe) => $classe->is_active
                && (int) $classe->niveau_etude_id === (int) $inscription->niveau_id
                && (int) $classe->annee_universitaire_id === (int) $inscription->annee_universitaire_id)
            ->when($filiereId, fn ($items) => $items->where('filiere_id', (int) $filiereId))
            ->values();

        // Fallback legacy : pas de target configurée → classes actives de la filière
        // matching le niveau + année universitaire de l'inscription. Cohérent avec
        // le fallback parent_id utilisé dans show() pour lister les spécialisations.
        if ($classes->isEmpty() && $filiereId) {
            $classes = \App\Models\ESBTPClasse::where('is_active', true)
                ->where('filiere_id', (int) $filiereId)
                ->where('niveau_etude_id', $inscription->niveau_id)
                ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
                ->orderBy('name')
                ->get();
        }

        return response()->json([
            'message' => $classes->isEmpty()
                ? "Aucune classe disponible pour cette spécialisation. Vérifiez que des classes actives existent pour cette filière au niveau et à l'année courants."
                : null,
            'classes' => $classes->map(function ($classe) {
                return [
                    'id' => $classe->id,
                    'name' => $classe->name,
                    'code' => $classe->code,
                    'places_totales' => $classe->places_totales,
                    'nombre_etudiants' => $classe->nombre_etudiants,
                    'places_disponibles' => $classe->places_disponibles,
                ];
            })->values(),
        ]);
    }

    public function store(ESBTPInscription $inscription, Request $request)
    {
        $request->validate([
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'classe_id' => 'required|exists:esbtp_classes,id',
        ]);

        try {
            $inscription = $this->orientationService->orient($inscription, (int) $request->classe_id);

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'redirect_to' => route('esbtp.inscriptions.show', $inscription),
                    'message' => 'Orientation enregistrée avec succès.',
                ]);
            }

            return redirect()->route('esbtp.inscriptions.show', $inscription)
                ->with('success', "Spécialisation effectuée avec succès. L'étudiant est maintenant inscrit en {$inscription->filiere->name}.");
        } catch (\InvalidArgumentException $e) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => "Erreur lors de la spécialisation."], 500);
            }

            return redirect()->back()->with('error', 'Erreur lors de la spécialisation : ' . $e->getMessage());
        }
    }
}
