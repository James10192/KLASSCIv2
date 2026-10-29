<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\BtsTroncCommun\BulletinSubjectOrder;
use App\Domain\Dispenses\DispenseService;
use App\Domain\Dispenses\Models\ESBTPDispense;
use App\Http\Requests\Dispense\AccorderDispenseRequest;
use App\Http\Requests\Dispense\RevoquerDispenseRequest;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Les dispenses de matiere d'un etudiant.
 *
 * BTS uniquement : le LMD attend la validation des regles de jury, et une
 * dispense y toucherait aux credits capitalises — un autre calcul, une autre
 * decision. Le garde ci-dessous le dit a l'ecran plutot que de laisser
 * l'utilisateur decouvrir un bulletin faux.
 */
class ESBTPDispenseController extends Controller
{
    public function __construct(
        private readonly DispenseService $service,
        private readonly BulletinSubjectOrder $ordre,
    ) {}

    /** Les dispenses de l'etudiant pour une annee, actives et revoquees. */
    public function index(Request $request, ESBTPEtudiant $etudiant): JsonResponse
    {
        abort_unless($request->user()?->can('dispenses.view'), 403);

        $anneeId = (int) $request->query('annee_universitaire_id');

        if ($anneeId <= 0) {
            return response()->json(['message' => "L'année universitaire est requise."], 422);
        }

        $dispenses = ESBTPDispense::query()
            ->with(['matiere:id,name,code', 'accordeePar:id,name', 'revoqueePar:id,name'])
            ->where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeId)
            ->orderByDesc('accordee_le')
            ->get();

        $classe = $this->classeDeLAnnee($etudiant, $anneeId);

        return response()->json([
            'dispenses' => $dispenses->map(fn (ESBTPDispense $d) => $this->presenter($d))->all(),
            // Le catalogue vient du serveur : l'ecran de l'etudiant ne fait
            // aucune requete, et la liste proposee est exactement celle du
            // bulletin — on ne peut pas dispenser d'une matiere qui n'y figure pas.
            'matieres' => $classe
                ? $this->ordre->orderedSubjectsForClasse($classe)
                    ->map(fn ($m) => ['value' => (int) $m->id, 'label' => $m->name])
                    ->values()->all()
                : [],
            'lmd' => $classe?->systeme_academique === 'LMD',
        ]);
    }

    public function store(AccorderDispenseRequest $request, ESBTPEtudiant $etudiant): JsonResponse
    {
        $anneeId = (int) $request->validated('annee_universitaire_id');

        $this->refuserLeLmd($etudiant, $anneeId);

        try {
            $dispense = $this->service->accorder(
                (int) $etudiant->id,
                (int) $request->validated('matiere_id'),
                $anneeId,
                $request->validated('periode'),
                (string) $request->validated('motif'),
                $request->user(),
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Dispense accordée. Elle apparaîtra au prochain calcul du bulletin.',
            'dispense' => $this->presenter($dispense->load(['matiere:id,name,code', 'accordeePar:id,name'])),
        ], 201);
    }

    public function revoquer(RevoquerDispenseRequest $request, ESBTPDispense $dispense): JsonResponse
    {
        if (! $dispense->estActive()) {
            return response()->json(['message' => 'Cette dispense est déjà révoquée.'], 422);
        }

        $dispense = $this->service->revoquer(
            $dispense,
            (string) $request->validated('motif'),
            $request->user(),
        );

        return response()->json([
            'message' => 'Dispense révoquée. La matière redeviendra notée au prochain calcul du bulletin.',
            'dispense' => $this->presenter($dispense->load(['matiere:id,name,code', 'accordeePar:id,name', 'revoqueePar:id,name'])),
        ]);
    }

    /**
     * Une classe LMD n'accepte pas de dispense.
     *
     * On regarde l'inscription de l'annee demandee, pas la derniere connue :
     * un etudiant passe du BTS au LMD garde ses inscriptions anciennes, et
     * c'est bien l'annee visee qui decide.
     */
    private function refuserLeLmd(ESBTPEtudiant $etudiant, int $anneeId): void
    {
        abort_if(
            $this->classeDeLAnnee($etudiant, $anneeId)?->systeme_academique === 'LMD',
            422,
            'Les dispenses ne sont pas disponibles en LMD : les règles de jury doivent d\'abord être validées.'
        );
    }

    /** La classe ou l'etudiant etait inscrit cette annee-la, s'il l'etait. */
    private function classeDeLAnnee(ESBTPEtudiant $etudiant, int $anneeId): ?ESBTPClasse
    {
        $classeId = ESBTPInscription::query()
            ->where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeId)
            ->value('classe_id');

        if (! $classeId) {
            return null;
        }

        return ESBTPClasse::with('filiere')->find($classeId);
    }

    /** @return array<string, mixed> */
    private function presenter(ESBTPDispense $dispense): array
    {
        return [
            'id' => (int) $dispense->id,
            'matiere_id' => (int) $dispense->matiere_id,
            'matiere' => $dispense->matiere?->name,
            'code' => $dispense->matiere?->code,
            'periode' => $dispense->periode,
            'portee' => $dispense->porteeLisible(),
            'motif' => $dispense->motif,
            'active' => $dispense->estActive(),
            'accordee_par' => $dispense->accordeePar?->name,
            'accordee_le' => $dispense->accordee_le?->format('d/m/Y'),
            'revoquee_par' => $dispense->revoqueePar?->name,
            'revoquee_le' => $dispense->revoquee_le?->format('d/m/Y'),
            'motif_revocation' => $dispense->motif_revocation,
        ];
    }
}
