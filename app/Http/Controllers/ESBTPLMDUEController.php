<?php

namespace App\Http\Controllers;

use App\Http\Requests\LMD\UniteEnseignementRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPUniteEnseignement;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Services\LMD\ParcoursUeSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ESBTPLMDUEController extends Controller
{
    public function __construct(private ParcoursUeSyncService $parcoursUeSync) {}

    /**
     * Afficher la liste des Unités d'Enseignement avec filtres.
     */
    public function index(Request $request)
    {
        $query = ESBTPUniteEnseignement::query()
            ->withCount('matieres')
            ->with(['filiere', 'niveau', 'parcours', 'parcoursMultiple', 'ecues', 'matieres']);

        // Filtres optionnels
        if ($request->filled('parcours_id')) {
            $query->where('parcours_id', $request->parcours_id);
        }

        if ($request->filled('filiere_id')) {
            $query->where('filiere_id', $request->filiere_id);
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->filled('semestre')) {
            $query->where('semestre', $request->semestre);
        }

        // Also filter by search (name or code)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
        }

        // Also filter by parcours via pivot
        if ($request->filled('parcours_id')) {
            $pId = $request->parcours_id;
            $query->whereHas('parcoursMultiple', fn($q) => $q->where('esbtp_lmd_parcours.id', $pId));
        }

        if ($request->filled('type_ue')) {
            $query->where('type_ue', $request->type_ue);
        }

        $perPage = $request->integer('per_page', 20);
        $ues = $query->orderBy('code')->orderBy('name')->paginate($perPage)->withQueryString();

        // JSON response for AJAX
        if ($request->ajax() || $request->wantsJson() || $request->format === 'json') {
            return response()->json([
                'ues' => $ues->map(function ($ue) {
                    $ecues = $ue->getEcuesEffectifs();
                    return [
                        'id' => $ue->id,
                        'code' => $ue->code,
                        'name' => $ue->name,
                        'type_ue' => $ue->type_ue,
                        'credit' => $ue->credit,
                        'description' => $ue->description,
                        'filiere_id' => $ue->filiere_id,
                        'niveau_id' => $ue->niveau_id,
                        'matieres_count' => $ue->matieres_count,
                        'parcours' => $ue->parcoursMultiple->groupBy('id')->map(fn($pivots) => [
                            'id' => $pivots->first()->id,
                            'code' => $pivots->first()->code,
                            'name' => $pivots->first()->name,
                            'semestres' => $pivots->pluck('pivot.semestre')->sort()->values(),
                        ])->values(),
                        'ecues' => $ecues->map(fn($e) => [
                            'id' => $e->id,
                            'code' => $e->code,
                            'name' => $e->name,
                            'coefficient' => $e->pivot->coefficient_ecue ?? $e->coefficient_ecue ?? null,
                            'credit' => $e->pivot->credit_ecue ?? $e->credit_ecue ?? null,
                            'ordre' => $e->pivot->ordre_bulletin ?? $e->ordre_bulletin ?? 0,
                        ]),
                    ];
                }),
                'pagination' => [
                    'current_page' => $ues->currentPage(),
                    'last_page' => $ues->lastPage(),
                    'per_page' => $ues->perPage(),
                    'total' => $ues->total(),
                ],
            ]);
        }

        // Données pour les filtres
        $parcours = ESBTPLMDParcours::orderBy('name')->get();
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        return view('esbtp.lmd.ue.index', compact('ues', 'parcours', 'filieres', 'niveaux'));
    }

    /**
     * Formulaire de création d'une UE.
     */
    public function create()
    {
        $parcours = ESBTPLMDParcours::orderBy('name')->get();
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        return view('esbtp.lmd.ue.create', compact('parcours', 'filieres', 'niveaux'));
    }

    /**
     * Retourner les données d'une UE en JSON (pour modal edit).
     */
    public function getJson(ESBTPUniteEnseignement $ue)
    {
        $ue->load('matieres', 'parcoursMultiple');

        $data = $ue->toArray();

        // Ajouter l'ordre du pivot (premier parcours lié)
        $pivot = $ue->parcoursMultiple->first();
        $data['ordre'] = $pivot?->pivot?->ordre ?? 0;

        return response()->json($data);
    }

    /**
     * Enregistrer une nouvelle UE, son rattachement au parcours et ses ECUEs.
     */
    public function store(UniteEnseignementRequest $request)
    {
        $donnees = $request->validated();

        $ue = DB::transaction(function () use ($donnees, $request) {
            $ue = new ESBTPUniteEnseignement();
            $ue->fill($this->attributsUe($donnees));
            $ue->created_by = auth()->id();
            $ue->updated_by = auth()->id();
            $ue->is_active = true;
            $ue->save();

            $this->rattacherAuParcours($ue, $donnees);
            $this->synchroniserEcues($ue, $donnees['ecues'] ?? [], $request->boolean('sync_ecues'));

            return $ue;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'UE créée avec succès.', 'ue' => $ue]);
        }

        return redirect()->route('esbtp.lmd.ue.show', $ue)
            ->with('success', 'Unité d\'Enseignement créée avec succès.');
    }

    /**
     * Afficher le détail d'une UE avec ses ECUEs (matières).
     */
    public function show(ESBTPUniteEnseignement $ue)
    {
        $ue->load([
            'matieres', 'ecues', 'filiere', 'niveau', 'parcours',
            'parcoursMultiple', 'responsableUe', 'createdBy', 'updatedBy',
        ]);

        // Tri sur une clé composite (ordre bulletin, puis intitulé) : une seule
        // fermeture, compatible avec toutes les versions de Collection::sortBy.
        $ecues = $ue->getEcuesEffectifs()
            ->sortBy(fn ($m) => sprintf(
                '%06d|%s',
                (int) ($m->pivot?->ordre_bulletin ?? $m->ordre_bulletin ?? 0),
                mb_strtolower((string) $m->name)
            ))
            ->values();

        return view('esbtp.lmd.ue.show', [
            'ue' => $ue,
            'ecues' => $ecues,
            'volumesHoraires' => $this->volumesHorairesParEcue($ue, $ecues),
        ]);
    }

    /**
     * Formulaire d'édition d'une UE.
     */
    public function edit(ESBTPUniteEnseignement $ue)
    {
        $ue->load(['matieres', 'ecues', 'parcoursMultiple']);

        $parcours = ESBTPLMDParcours::orderBy('name')->get();
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        return view('esbtp.lmd.ue.edit', compact('ue', 'parcours', 'filieres', 'niveaux'));
    }

    /**
     * Mettre à jour une UE existante, son rattachement et ses ECUEs.
     */
    public function update(UniteEnseignementRequest $request, ESBTPUniteEnseignement $ue)
    {
        $donnees = $request->validated();

        DB::transaction(function () use ($donnees, $request, $ue) {
            $ue->fill($this->attributsUe($donnees));
            $ue->updated_by = auth()->id();
            $ue->save();

            $this->rattacherAuParcours($ue, $donnees);
            $this->synchroniserEcues($ue, $donnees['ecues'] ?? [], $request->boolean('sync_ecues'));
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'UE mise à jour avec succès.', 'ue' => $ue]);
        }

        return redirect()->route('esbtp.lmd.ue.show', $ue)
            ->with('success', 'Unité d\'Enseignement mise à jour avec succès.');
    }

    /**
     * Colonnes de l'UE alimentées par le formulaire.
     *
     * `semestre`, `filiere_id`, `niveau_id` et `parcours_id` étaient auparavant
     * absents de la validation : ils étaient postés par le formulaire puis jetés,
     * et l'UE se retrouvait orpheline (invisible des calculs et du bulletin).
     */
    private function attributsUe(array $donnees): array
    {
        return [
            'name' => $donnees['name'],
            'code' => $donnees['code'] ?? null,
            'description' => $donnees['description'] ?? null,
            'credit' => $donnees['credit'] ?? null,
            'type_ue' => $donnees['type_ue'],
            'semestre' => $donnees['semestre'] ?? null,
            'filiere_id' => $donnees['filiere_id'] ?? null,
            'niveau_id' => $donnees['niveau_id'] ?? null,
            'parcours_id' => $donnees['parcours_id'] ?? null,
        ];
    }

    /**
     * Rattache l'UE au parcours choisi, via le pivot esbtp_lmd_parcours_ue.
     *
     * Une UE est partageable entre plusieurs parcours et plusieurs semestres :
     * on ajoute donc le lien sans jamais détacher les autres (detachMissing:
     * false, comme l'import en ligne de commande). Retirer un rattachement reste
     * le rôle de l'écran dédié (`syncParcours`), seul à connaître la liste
     * complète voulue par l'utilisateur.
     */
    private function rattacherAuParcours(ESBTPUniteEnseignement $ue, array $donnees): void
    {
        $parcoursId = $donnees['parcours_id'] ?? null;
        $semestre = $donnees['semestre'] ?? null;

        // Le pivot exige un semestre (colonne NOT NULL) ; la validation impose
        // déjà « semestre requis avec parcours », ce test est une sécurité.
        if (!$parcoursId || !$semestre) {
            return;
        }

        $parcours = ESBTPLMDParcours::find($parcoursId);
        if (!$parcours) {
            return;
        }

        $this->parcoursUeSync->sync($parcours, [[
            'id' => $ue->id,
            'semestres' => [(int) $semestre],
            'is_optional' => false,
            'ordre' => (int) ($donnees['ordre'] ?? 0),
        ]], detachMissing: false);
    }

    /**
     * Crée ou met à jour les ECUEs saisis dans le formulaire.
     *
     * Deux liens sont écrits, comme le fait l'ajout d'un ECUE isolé :
     *  - la clé étrangère esbtp_matieres.unite_enseignement_id (rétro-compat) ;
     *  - le pivot esbtp_ue_matiere, qui porte coefficient / crédit / ordre
     *    propres à CETTE UE et permet le partage d'un ECUE entre deux UE.
     *
     * $detacherAbsents n'est vrai que si le formulaire a explicitement envoyé la
     * liste complète (champ caché `sync_ecues`) : un appel partiel ne doit jamais
     * détacher en silence des ECUEs qu'il ne connaissait pas.
     */
    private function synchroniserEcues(ESBTPUniteEnseignement $ue, array $ecues, bool $detacherAbsents): void
    {
        $idsConserves = [];

        foreach ($ecues as $ligne) {
            $code = isset($ligne['code']) && $ligne['code'] !== '' ? $ligne['code'] : null;
            $credit = isset($ligne['credit_ecue']) && $ligne['credit_ecue'] !== '' ? (int) $ligne['credit_ecue'] : null;
            $coefficient = isset($ligne['coefficient_ecue']) && $ligne['coefficient_ecue'] !== '' ? (float) $ligne['coefficient_ecue'] : null;
            $ordre = (int) ($ligne['ordre_bulletin'] ?? 0);

            // Réutilisation par code, comme l'import : les codes ECUE sont uniques
            // au niveau de l'établissement, deux saisies du même code désignent
            // la même matière.
            // withTrashed : la colonne `code` porte un index unique, une matière
            // archivée occupe donc toujours son code. Sans cela, ressaisir ce code
            // ferait échouer l'enregistrement sur une violation d'unicité.
            $matiere = $code ? ESBTPMatiere::withTrashed()->where('code', $code)->first() : null;
            $this->refuserAbsorptionMatiereBts($matiere);
            $existait = $matiere !== null;
            if ($matiere && $matiere->trashed()) {
                $matiere->restore();
            }
            $matiere = $matiere ?: new ESBTPMatiere();

            $matiere->fill([
                'name' => $ligne['name'],
                'code' => $code,
                'unite_enseignement_id' => $ue->id,
                'credit_ecue' => $credit,
                'coefficient_ecue' => $coefficient,
                'ordre_bulletin' => $ordre,
            ]);
            if (!$existait) {
                $matiere->is_active = true;
                $matiere->created_by = auth()->id();
                if ($ue->niveau_id) {
                    $matiere->niveau_etude_id = $ue->niveau_id;
                }
            }
            $matiere->updated_by = auth()->id();
            $matiere->save();

            $ue->ecues()->syncWithoutDetaching([
                $matiere->id => [
                    'coefficient_ecue' => $coefficient,
                    'credit_ecue' => $credit,
                    'ordre_bulletin' => $ordre,
                ],
            ]);

            $idsConserves[] = $matiere->id;
        }

        if (!$detacherAbsents) {
            return;
        }

        $idsActuels = $ue->ecues()->pluck('esbtp_matieres.id')
            ->merge($ue->matieres()->pluck('esbtp_matieres.id'))
            ->unique();
        $aDetacher = $idsActuels->diff($idsConserves)->values();

        if ($aDetacher->isEmpty()) {
            return;
        }

        // Détacher, jamais supprimer : la matière peut porter des évaluations
        // et des notes. Même comportement que le retrait d'un ECUE isolé.
        $ue->ecues()->detach($aDetacher->all());
        ESBTPMatiere::whereIn('id', $aDetacher->all())
            ->where('unite_enseignement_id', $ue->id)
            ->update(['unite_enseignement_id' => null, 'updated_by' => auth()->id()]);
    }

    /**
     * Garde-fou : ne jamais transformer une matière du BTS en ECUE.
     *
     * La réutilisation par code ci-dessus écrit `unite_enseignement_id` sur la
     * matière trouvée. `esbtp_matieres` étant partagée par les deux cursus avec
     * un `code` unique global, une collision ferait basculer une matière BTS
     * côté LMD : elle disparaîtrait de tous les sélecteurs BTS, qui filtrent sur
     * `unite_enseignement_id IS NULL`, en emportant ses évaluations et ses notes.
     *
     * Le formulaire est déjà arrêté en amont par UniteEnseignementRequest, qui
     * nomme le code en conflit. Cette seconde barrière protège les appels qui ne
     * passeraient pas par ce FormRequest.
     */
    private function refuserAbsorptionMatiereBts(?ESBTPMatiere $matiere): void
    {
        if (! $matiere || $matiere->unite_enseignement_id !== null) {
            return;
        }

        // Un ECUE partagé entre deux UE n'est rattaché que par le pivot.
        if (DB::table('esbtp_ue_matiere')->where('matiere_id', $matiere->id)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'ecues' => sprintf(
                'Le code « %s » est déjà celui d\'une matière du cursus BTS (« %s »). Choisissez un autre code.',
                (string) $matiere->code,
                (string) $matiere->name
            ),
        ]);
    }

    /**
     * Volumes horaires de chaque ECUE, lus sur la planification académique
     * (source canonique) et complétés par les heures portées par la matière.
     *
     * @return array<int, array{cm:int, td:int, tp:int, total:int, source:string}>
     */
    private function volumesHorairesParEcue(ESBTPUniteEnseignement $ue, $ecues): array
    {
        $volumes = [];
        foreach ($ecues as $ecue) {
            $volumes[$ecue->id] = [
                'cm' => (int) ($ecue->heures_cm ?? 0),
                'td' => (int) ($ecue->heures_td ?? 0),
                'tp' => (int) ($ecue->heures_tp ?? 0),
                'total' => (int) ($ecue->heures_cm ?? 0) + (int) ($ecue->heures_td ?? 0) + (int) ($ecue->heures_tp ?? 0),
                'source' => 'matiere',
            ];
        }

        if (!$ue->filiere_id || !$ue->niveau_id || !$ue->semestre || empty($volumes)) {
            return $volumes;
        }

        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first()
            ?? ESBTPAnneeUniversitaire::where('is_active', true)->orderByDesc('start_date')->first();
        if (!$annee) {
            return $volumes;
        }

        $planifications = ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
            ->where('filiere_id', $ue->filiere_id)
            ->where('niveau_etude_id', $ue->niveau_id)
            ->where('semestre', $ue->semestre)
            ->whereIn('matiere_id', array_keys($volumes))
            ->get();

        foreach ($planifications as $planification) {
            $volumes[$planification->matiere_id] = [
                'cm' => (int) $planification->volume_horaire_cm,
                'td' => (int) $planification->volume_horaire_td,
                'tp' => (int) $planification->volume_horaire_tp,
                'total' => (int) $planification->volume_horaire_total,
                'source' => 'planification',
            ];
        }

        return $volumes;
    }

    /**
     * Supprimer une UE (si aucun résultat attaché).
     */
    public function destroy(Request $request, ESBTPUniteEnseignement $ue)
    {
        // Vérifier qu'aucun résultat LMD n'est attaché
        if ($ue->resultatsLMD()->exists()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Impossible de supprimer cette UE : des résultats y sont rattachés.'], 422);
            }
            return redirect()->route('esbtp.lmd.ue.index')
                ->with('error', 'Impossible de supprimer cette UE : des résultats y sont rattachés.');
        }

        // Détacher les ECUEs (matières) avant suppression
        $ue->matieres()->update(['unite_enseignement_id' => null]);
        $ue->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'UE supprimée avec succès.']);
        }
        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'Unité d\'Enseignement supprimée avec succès.');
    }

    // -------------------------------------------------------------------------
    //  Gestion des ECUEs (matières rattachées à une UE)
    // -------------------------------------------------------------------------

    /**
     * Ajouter un ECUE à une UE.
     *
     * Soit on rattache une matière existante (matiere_id fourni),
     * soit on crée une nouvelle matière directement.
     */
    public function storeECUE(Request $request, ESBTPUniteEnseignement $ue)
    {
        $validated = $request->validate([
            'matiere_id'       => 'nullable|exists:esbtp_matieres,id',
            // Champs pour création d'une nouvelle matière
            'name'             => 'required_without:matiere_id|nullable|string|max:255',
            'code'             => 'required_without:matiere_id|nullable|string|max:50',
            'credit_ecue'     => 'nullable|integer|min:1',
            'coefficient_ecue' => 'nullable|numeric|min:0',
            'ordre_bulletin'  => 'nullable|integer|min:0',
        ]);

        $coeffEcue = $validated['coefficient_ecue'] ?? null;
        $creditEcue = $validated['credit_ecue'] ?? null;
        $ordreBulletin = $validated['ordre_bulletin'] ?? 0;

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE
        if ($error = $this->checkCreditOverflow($ue, $creditEcue, null, $request)) {
            return $error;
        }

        if (!empty($validated['matiere_id'])) {
            $matiere = ESBTPMatiere::findOrFail($validated['matiere_id']);
        } else {
            // Créer une nouvelle matière
            $matiere = ESBTPMatiere::create([
                'name'                  => $validated['name'],
                'code'                  => $validated['code'],
                'unite_enseignement_id' => $ue->id, // FK direct (rétro-compat)
                'credit_ecue'           => $creditEcue,
                'coefficient_ecue'      => $coeffEcue,
                'ordre_bulletin'        => $ordreBulletin,
                'is_active'             => true,
                'created_by'            => auth()->id(),
                'updated_by'            => auth()->id(),
            ]);
        }

        // Toujours garder le FK direct (rétro-compat)
        if ($matiere->unite_enseignement_id !== $ue->id) {
            $matiere->update(['unite_enseignement_id' => $ue->id, 'updated_by' => auth()->id()]);
        }

        // Écrire dans le pivot (many-to-many) avec coeff/credit contextuels
        $ue->ecues()->syncWithoutDetaching([
            $matiere->id => [
                'coefficient_ecue' => $coeffEcue,
                'credit_ecue' => $creditEcue,
                'ordre_bulletin' => $ordreBulletin,
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE ajouté avec succès.']);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE ajouté avec succès à l\'UE.');
    }

    /**
     * Mettre à jour un ECUE rattaché à une UE.
     */
    public function updateECUE(Request $request, ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue)
    {
        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'code'             => 'sometimes|required|string|max:50',
            'credit_ecue'     => 'nullable|integer|min:1',
            'coefficient_ecue' => 'nullable|numeric|min:0',
            'ordre_bulletin'  => 'nullable|integer|min:0',
        ]);

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE
        if ($error = $this->checkCreditOverflow($ue, $validated['credit_ecue'] ?? null, $ecue->id, $request)) {
            return $error;
        }

        // Mettre à jour la matière en un seul UPDATE (nom, code, coeff, credit, ordre)
        $ecue->update([
            'name' => $validated['name'] ?? $ecue->name,
            'code' => $validated['code'] ?? $ecue->code,
            'coefficient_ecue' => $validated['coefficient_ecue'] ?? $ecue->coefficient_ecue,
            'credit_ecue' => $validated['credit_ecue'] ?? $ecue->credit_ecue,
            'ordre_bulletin' => $validated['ordre_bulletin'] ?? $ecue->ordre_bulletin,
            'updated_by' => auth()->id(),
        ]);

        // Mettre à jour le pivot avec les valeurs contextuelles à cette UE
        $ue->ecues()->syncWithoutDetaching([
            $ecue->id => [
                'coefficient_ecue' => $validated['coefficient_ecue'] ?? null,
                'credit_ecue' => $validated['credit_ecue'] ?? null,
                'ordre_bulletin' => $validated['ordre_bulletin'] ?? 0,
            ],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE mis à jour.']);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE mis à jour avec succès.');
    }

    /**
     * Détacher un ECUE de l'UE (ne supprime pas la matière).
     */
    public function destroyECUE(Request $request, ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue)
    {
        // Détacher du pivot many-to-many
        $ue->ecues()->detach($ecue->id);

        // Aussi nettoyer le FK direct si c'est cette UE
        if ($ecue->unite_enseignement_id === $ue->id) {
            $ecue->update(['unite_enseignement_id' => null, 'updated_by' => auth()->id()]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE détaché avec succès.']);
        }
        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE détaché de l\'UE avec succès.');
    }

    /**
     * Liste des matières disponibles pour rattachement à une UE (non déjà liées).
     */
    public function matieresDisponibles(ESBTPUniteEnseignement $ue)
    {
        $matieres = ESBTPMatiere::where('is_active', true)
            ->whereDoesntHave('unitesEnseignementMultiple', fn($q) => $q->where('esbtp_ue_matiere.unite_enseignement_id', $ue->id))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'coefficient_ecue', 'credit_ecue']);

        return response()->json($matieres);
    }

    /**
     * Liste des parcours disponibles pour une UE (liés + non liés).
     */
    public function parcoursDisponibles(ESBTPUniteEnseignement $ue)
    {
        $pivotRows = $ue->parcoursMultiple()
            ->select('esbtp_lmd_parcours.id', 'esbtp_lmd_parcours.code', 'esbtp_lmd_parcours.name')
            ->get();

        // Group by parcours id → collect semestres
        $liesMap = [];
        foreach ($pivotRows as $p) {
            if (!isset($liesMap[$p->id])) {
                $liesMap[$p->id] = [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'semestres' => [],
                ];
            }
            $liesMap[$p->id]['semestres'][] = $p->pivot->semestre;
        }
        $lies = array_values($liesMap);
        $liesIds = array_keys($liesMap);

        $disponibles = ESBTPLMDParcours::whereNotIn('id', $liesIds)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'semestres' => [],
            ])->values();

        return response()->json(['lies' => $lies, 'disponibles' => $disponibles]);
    }

    /**
     * Synchroniser les parcours d'une UE (multi-semestres via pivot).
     */
    public function syncParcours(Request $request, ESBTPUniteEnseignement $ue)
    {
        $request->validate([
            'parcours' => 'present|array',
            'parcours.*.id' => 'required|exists:esbtp_lmd_parcours,id',
            'parcours.*.semestres' => 'required|array|min:1',
            'parcours.*.semestres.*' => 'integer|between:1,10',
        ]);

        $count = DB::transaction(function () use ($request, $ue) {
            $ue->parcoursMultiple()->detach();
            $count = 0;
            foreach ($request->input('parcours', []) as $item) {
                foreach ($item['semestres'] as $sem) {
                    $ue->parcoursMultiple()->attach($item['id'], ['semestre' => $sem]);
                    $count++;
                }
            }
            return $count;
        });

        return response()->json(['success' => true, 'message' => $count . ' lien(s) parcours-semestre créé(s).']);
    }

    /**
     * Vérifier que l'ajout/modification d'un crédit ECUE ne dépasse pas le crédit de l'UE.
     * Retourne une response d'erreur si dépassement, null sinon.
     */
    private function checkCreditOverflow(ESBTPUniteEnseignement $ue, $creditEcue, ?int $excludeMatiereId, Request $request)
    {
        if (!$ue->credit || !$creditEcue) {
            return null;
        }

        $query = DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id);
        if ($excludeMatiereId) {
            $query->where('matiere_id', '!=', $excludeMatiereId);
        }
        $creditsAutres = (int) $query->sum('credit_ecue');

        if ($creditsAutres + (int) $creditEcue <= (int) $ue->credit) {
            return null;
        }

        $restant = (int) $ue->credit - $creditsAutres;
        $message = "La somme des crédits ECUE ({$creditsAutres} + {$creditEcue} = " . ($creditsAutres + (int) $creditEcue) . ") "
            . "dépasse le crédit de l'UE ({$ue->credit}). Il reste {$restant} crédit(s) disponible(s).";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message);
    }
}
