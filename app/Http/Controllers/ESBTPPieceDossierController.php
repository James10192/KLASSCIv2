<?php

namespace App\Http\Controllers;

use App\Enums\AppartenancePieceDossier;
use App\Enums\EcheancePieceDossier;
use App\Enums\FormePieceDossier;
use App\Http\Requests\PiecesDossier\UpsertPieceDossierRequest;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPieceDossier;
use App\Services\CataloguePiecesDossier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Écran de configuration du catalogue des pièces à fournir.
 *
 * Orchestration seulement : la lecture du catalogue, les réglages et le jeu
 * proposé vivent dans CataloguePiecesDossier, la validation dans
 * UpsertPieceDossierRequest.
 */
class ESBTPPieceDossierController extends Controller
{
    public function __construct(private readonly CataloguePiecesDossier $catalogue)
    {
    }

    public function index()
    {
        return view('esbtp.pieces-dossier.index', [
            'pieces' => $this->catalogue->tout()->map(fn ($p) => $this->serialiser($p))->values(),
            'filieres' => $this->filieresPourLaPortee(),
            'niveaux' => ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'formes' => FormePieceDossier::selectOptions(),
            'echeances' => EcheancePieceDossier::selectOptions(),
            'appartenances' => AppartenancePieceDossier::selectOptions(),
            'nbProposees' => count($this->catalogue->jeuPropose()),
            'exemplairesMax' => $this->catalogue->exemplairesMax(),
            'formeDefaut' => $this->catalogue->formeParDefaut()->value,
            'echeanceDefaut' => $this->catalogue->echeanceParDefaut()->value,
            'peutConfigurer' => auth()->user()?->can('pieces_dossier.configure') ?? false,
        ]);
    }

    public function store(UpsertPieceDossierRequest $request): JsonResponse
    {
        $piece = DB::transaction(function () use ($request) {
            $attributs = $request->attributsPiece();
            $attributs['code'] = $this->catalogue->genererCode($attributs['libelle']);
            // Un bond de dix laisse la place d'intercaler une pièce sans
            // réécrire tout l'ordre. Le réordonnancement, lui, réécrit.
            $attributs['ordre'] = ((int) ESBTPPieceDossier::withTrashed()->max('ordre')) + 10;
            $attributs['created_by'] = auth()->id();
            $attributs['updated_by'] = auth()->id();

            $piece = ESBTPPieceDossier::create($attributs);
            $piece->filieres()->sync($request->filiereIds());
            $piece->niveaux()->sync($request->niveauIds());

            return $piece;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pièce ajoutée au catalogue.',
            'piece' => $this->serialiser($this->recharger($piece)),
        ], 201);
    }

    public function update(UpsertPieceDossierRequest $request, ESBTPPieceDossier $piece): JsonResponse
    {
        DB::transaction(function () use ($request, $piece) {
            $attributs = $request->attributsPiece();
            $attributs['updated_by'] = auth()->id();

            // Le code reste figé. Les états de dossier déjà saisis s'y
            // accrochent : le renommer romprait l'historique sans prévenir.
            unset($attributs['code']);

            $piece->update($attributs);
            $piece->filieres()->sync($request->filiereIds());
            $piece->niveaux()->sync($request->niveauIds());
        });

        return response()->json([
            'success' => true,
            'message' => 'Pièce mise à jour.',
            'piece' => $this->serialiser($this->recharger($piece)),
        ]);
    }

    /**
     * Archivage, jamais suppression définitive : les états de dossier saisis au
     * guichet continuent de pointer vers cette pièce, et l'école doit pouvoir
     * relire un dossier d'il y a trois ans.
     */
    public function destroy(ESBTPPieceDossier $piece): JsonResponse
    {
        $piece->forceFill(['updated_by' => auth()->id()])->save();
        $piece->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pièce retirée du catalogue.',
            'id' => $piece->id,
        ]);
    }

    public function toggle(ESBTPPieceDossier $piece): JsonResponse
    {
        $piece->update([
            'is_active' => ! $piece->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $piece->is_active ? 'Pièce réactivée.' : 'Pièce désactivée.',
            'piece' => $this->serialiser($this->recharger($piece)),
        ]);
    }

    /** Réordonnancement : on reçoit la liste complète des identifiants, dans l'ordre voulu. */
    public function reorder(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:esbtp_pieces_dossier,id'],
        ]);

        DB::transaction(function () use ($valide) {
            foreach (array_values($valide['ids']) as $rang => $id) {
                ESBTPPieceDossier::whereKey($id)->update(['ordre' => ($rang + 1) * 10]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Ordre enregistré.']);
    }

    public function installerJeuPropose(): JsonResponse
    {
        $creees = $this->catalogue->installerJeuPropose(auth()->id());

        return response()->json([
            'success' => true,
            'message' => $creees > 0
                ? $creees . ' pièce(s) ajoutée(s) au catalogue.'
                : 'Le catalogue contient déjà toutes les pièces proposées.',
            'pieces' => $this->catalogue->tout()->map(fn ($p) => $this->serialiser($p))->values(),
        ]);
    }

    /**
     * Les filières proposées comme portée, chacune sous sa nature.
     *
     * On n'écarte PAS les filières-reflets LMD. Sur une instance tout-LMD comme
     * USAT, chaque classe s'ancre précisément sur un reflet : les masquer
     * retirerait à l'école toute possibilité de restreindre une pièce. Mais les
     * offrir sans dire ce qu'elles sont est pire encore — un reflet porte le nom
     * de son parcours, une vraie filière BTS homonyme existe souvent à côté, et
     * une portée posée sur la mauvaise n'est satisfaite par AUCUNE inscription :
     * zéro pièce réclamée, aucune erreur, aucune trace. On les nomme donc
     * (« Parcours : … », « Mention : … ») et on les range après les vraies
     * filières, pour que le choix se fasse en connaissance de cause.
     */
    private function filieresPourLaPortee(): Collection
    {
        return ESBTPFiliere::where('is_active', true)
            ->get(['id', 'name', 'lmd_mention_id', 'lmd_parcours_id'])
            ->sortBy(fn (ESBTPFiliere $f) => [$f->estMiroirLmd() ? 1 : 0, mb_strtolower($f->name)])
            ->values();
    }

    /** Recharge la pièce ET sa portée : sans quoi la réponse renverrait l'ancienne. */
    private function recharger(ESBTPPieceDossier $piece): ESBTPPieceDossier
    {
        return $piece->refresh()->load(['filieres:id,name', 'niveaux:id,name']);
    }

    private function serialiser(ESBTPPieceDossier $piece): array
    {
        return [
            'id' => $piece->id,
            'code' => $piece->code,
            'libelle' => $piece->libelle,
            'description' => $piece->description,
            'is_obligatoire' => (bool) $piece->is_obligatoire,
            'forme_attendue' => $piece->forme_attendue->value,
            'forme_label' => $piece->forme_attendue->label(),
            'exemplaires_par_inscription' => (int) $piece->exemplaires_par_inscription,
            'echeance' => $piece->echeance->value,
            'echeance_label' => $piece->echeance->labelCourt(),
            'appartenance' => $piece->appartenance->value,
            'appartenance_label' => $piece->appartenance->labelCourt(),
            // Volontairement pas de repli à zéro : le nul VEUT dire « ne périme
            // jamais », et l'écran doit pouvoir le distinguer d'une durée.
            'duree_validite_mois' => $piece->duree_validite_mois,
            'filiere_ids' => $piece->filiereIds(),
            'niveau_ids' => $piece->niveauIds(),
            'libelle_scope' => $piece->libelleScope(),
            'is_active' => (bool) $piece->is_active,
            'ordre' => (int) $piece->ordre,
        ];
    }
}
