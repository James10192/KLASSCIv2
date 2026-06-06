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

        $inscription->loadMissing([
            'classe.orientationTargets.targetClasse',
            'niveau:id,name',
        ]);

        // Source canonique : ClasseOrientationTarget (mapping manuel via admin).
        // Rule classes-universelles-pas-annee : on NE filtre PAS par annee_universitaire_id
        // sur esbtp_classes — c'est l'inscription qui porte l'année, pas la classe.
        $classes = collect($inscription->classe?->orientationTargets ?? [])
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->map(fn ($target) => $target->targetClasse)
            ->filter()
            ->filter(fn ($classe) => $classe->is_active
                && (int) $classe->niveau_etude_id === (int) $inscription->niveau_id)
            ->when($filiereId, fn ($items) => $items->where('filiere_id', (int) $filiereId))
            ->values();

        // Fallback legacy : pas de target configurée → classes actives de la filière
        // matching le niveau de l'inscription. Sans filtre annee_universitaire_id
        // (cohérent rule classes-universelles-pas-annee).
        if ($classes->isEmpty() && $filiereId) {
            $classes = \App\Models\ESBTPClasse::where('is_active', true)
                ->where('filiere_id', (int) $filiereId)
                ->where('niveau_etude_id', $inscription->niveau_id)
                ->orderBy('name')
                ->get();
        }

        $payload = [
            'message' => null,
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
        ];

        if ($classes->isEmpty() && $filiereId) {
            $filiere = \App\Models\ESBTPFiliere::find((int) $filiereId);
            $niveauName = $inscription->niveau?->name;
            $filiereName = $filiere?->name;

            $payload['message'] = sprintf(
                "Aucune classe de spécialité %s n'est encore créée au niveau %s.",
                $filiereName ?? '—',
                $niveauName ?? '—',
            );
            $payload['empty_context'] = [
                'filiere_id' => (int) $filiereId,
                'filiere_name' => $filiereName,
                'niveau_id' => $inscription->niveau_id,
                'niveau_name' => $niveauName,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'can_create_classe' => auth()->check() && auth()->user()->can('classes.create'),
                'create_classe_url' => route('esbtp.classes.create', array_filter([
                    'filiere_id' => (int) $filiereId,
                    'niveau_etude_id' => $inscription->niveau_id,
                    'annee_universitaire_id' => $inscription->annee_universitaire_id,
                ])),
            ];
        }

        return response()->json($payload);
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
