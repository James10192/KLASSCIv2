<?php

namespace App\Http\Controllers;

use App\Enums\EcheanceDocumentRequis;
use App\Enums\FormeDocumentRequis;
use App\Http\Requests\DocumentsRequis\UpsertDocumentRequisRequest;
use App\Models\ESBTPDocumentRequis;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Services\CatalogueDocumentsRequis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ecran de configuration du catalogue des pieces a fournir.
 *
 * Orchestration seulement : la lecture du catalogue et le jeu par defaut vivent
 * dans CatalogueDocumentsRequis, la validation dans UpsertDocumentRequisRequest.
 */
class ESBTPDocumentRequisController extends Controller
{
    public function __construct(private readonly CatalogueDocumentsRequis $catalogue)
    {
    }

    public function index()
    {
        $filieres = ESBTPFiliere::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $niveaux = ESBTPNiveauEtude::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('esbtp.documents-requis.index', [
            'pieces'          => $this->catalogue->tout()->map(fn ($p) => $this->serialiser($p))->values(),
            'filieres'        => $filieres,
            'niveaux'         => $niveaux,
            'formes'          => FormeDocumentRequis::selectOptions(),
            'echeances'       => EcheanceDocumentRequis::selectOptions(),
            'jeuParDefaut'    => $this->catalogue->jeuParDefaut(),
            'peutConfigurer'  => auth()->user()?->can('documents_requis.configure') ?? false,
        ]);
    }

    public function store(UpsertDocumentRequisRequest $request): JsonResponse
    {
        $donnees = $request->pourPersistance();
        $donnees['code'] = $this->catalogue->genererCode($donnees['libelle']);
        $donnees['ordre'] = $donnees['ordre'] ?? ((int) ESBTPDocumentRequis::withTrashed()->max('ordre') + 10);
        $donnees['created_by'] = auth()->id();
        $donnees['updated_by'] = auth()->id();

        $piece = ESBTPDocumentRequis::create($donnees);

        return response()->json([
            'success' => true,
            'message' => 'Piece ajoutee au catalogue.',
            'piece'   => $this->serialiser($piece),
        ], 201);
    }

    public function update(UpsertDocumentRequisRequest $request, ESBTPDocumentRequis $piece): JsonResponse
    {
        $donnees = $request->pourPersistance();
        $donnees['updated_by'] = auth()->id();

        // Le code reste fige : les lots suivants s'y accrochent, et renommer une
        // piece ne doit pas rompre l'historique des dossiers deja constitues.
        unset($donnees['code']);

        $piece->update($donnees);

        return response()->json([
            'success' => true,
            'message' => 'Piece mise a jour.',
            'piece'   => $this->serialiser($piece->refresh()),
        ]);
    }

    /**
     * Archivage, jamais suppression definitive : les etats de dossier saisis
     * par le secretariat (lot 2) continuent de pointer vers cette piece.
     */
    public function destroy(ESBTPDocumentRequis $piece): JsonResponse
    {
        $piece->forceFill(['updated_by' => auth()->id()])->save();
        $piece->delete();

        return response()->json([
            'success' => true,
            'message' => 'Piece retiree du catalogue.',
            'id'      => $piece->id,
        ]);
    }

    public function toggle(ESBTPDocumentRequis $piece): JsonResponse
    {
        $piece->update([
            'is_active'  => ! $piece->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $piece->is_active ? 'Piece reactivee.' : 'Piece desactivee.',
            'piece'   => $this->serialiser($piece),
        ]);
    }

    /** Reordonnancement : on recoit la liste complete des ids dans l'ordre voulu. */
    public function reorder(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:esbtp_documents_requis,id'],
        ]);

        DB::transaction(function () use ($valide) {
            foreach (array_values($valide['ids']) as $rang => $id) {
                // Pas de bond de 10 ici : l'ordre vient d'etre reecrit en entier,
                // il n'y a plus d'insertion a intercaler.
                ESBTPDocumentRequis::whereKey($id)->update(['ordre' => ($rang + 1) * 10]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Ordre enregistre.']);
    }

    public function installerJeuParDefaut(): JsonResponse
    {
        $creees = $this->catalogue->installerJeuParDefaut(auth()->id());

        return response()->json([
            'success' => true,
            'message' => $creees > 0
                ? $creees . ' piece(s) ajoutee(s) au catalogue.'
                : 'Le catalogue contient deja toutes les pieces proposees.',
            'pieces'  => $this->catalogue->tout()->map(fn ($p) => $this->serialiser($p))->values(),
        ]);
    }

    private function serialiser(ESBTPDocumentRequis $piece): array
    {
        return [
            'id'                 => $piece->id,
            'code'               => $piece->code,
            'libelle'            => $piece->libelle,
            'description'        => $piece->description,
            'is_obligatoire'     => (bool) $piece->is_obligatoire,
            'forme_attendue'     => $piece->forme_attendue->value,
            'forme_label'        => $piece->forme_attendue->label(),
            'nombre_exemplaires' => (int) $piece->nombre_exemplaires,
            'echeance'           => $piece->echeance->value,
            'echeance_label'     => $piece->echeance->labelCourt(),
            'filiere_ids'        => $piece->filiere_ids ?? [],
            'niveau_ids'         => $piece->niveau_ids ?? [],
            'is_active'          => (bool) $piece->is_active,
            'ordre'              => (int) $piece->ordre,
        ];
    }
}
