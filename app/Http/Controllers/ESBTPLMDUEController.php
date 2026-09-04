<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Http\Requests\LMD\UniteEnseignementRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDJournalMaquette;
use App\Models\ESBTPUniteEnseignement;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Services\LMD\JournalMaquette;
use App\Services\LMD\LectureMaquettes;
use App\Services\LMD\ParcoursUeSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ESBTPLMDUEController extends Controller
{
    public function __construct(
        private ParcoursUeSyncService $parcoursUeSync,
        private LectureMaquettes $lectureMaquettes,
        private JournalMaquette $journal,
    ) {}

    /**
     * Afficher la liste des Unités d'Enseignement avec filtres.
     */
    public function index(Request $request)
    {
        $query = ESBTPUniteEnseignement::query()
            ->withCount('matieres')
            ->with(['filiere', 'niveau', 'parcours', 'parcoursMultiple', 'ecues', 'matieres']);

        // La maquette de travail se lit sur le pivot, jamais sur la colonne
        // `esbtp_unites_enseignement.parcours_id` : une unite partagee entre
        // deux parcours n'a qu'une seule valeur dans cette colonne, filtrer
        // dessus ferait disparaitre l'unite de l'un des deux parcours. Le
        // filtre par le pivot est applique plus bas.

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

        // Maquette de travail : ne montrer que les unites que ce parcours porte.
        $parcoursTravailId = $request->filled('parcours_id') ? (int) $request->parcours_id : null;
        if ($parcoursTravailId !== null) {
            $query->whereHas('parcoursMultiple', fn($q) => $q->where('esbtp_lmd_parcours.id', $parcoursTravailId));
        }

        if ($request->filled('type_ue')) {
            $query->where('type_ue', $request->type_ue);
        }

        $perPage = $request->integer('per_page', 20);
        $ues = $query->orderBy('code')->orderBy('name')->paginate($perPage)->withQueryString();

        // JSON response for AJAX
        if ($request->ajax() || $request->wantsJson() || $request->format === 'json') {
            return response()->json([
                'ues' => $ues->map(fn ($ue) => $this->unitePourLaListe($ue, $parcoursTravailId)),
                'pagination' => [
                    'current_page' => $ues->currentPage(),
                    'last_page' => $ues->lastPage(),
                    'per_page' => $ues->perPage(),
                    'total' => $ues->total(),
                ],
                'maquette_travail' => $parcoursTravailId,
            ]);
        }

        // Données pour les filtres
        $parcours = ESBTPLMDParcours::orderBy('name')->get();
        $filieres = ESBTPFiliere::orderBy('name')->get();
        $niveaux = ESBTPNiveauEtude::orderBy('name')->get();

        return view('esbtp.lmd.ue.index', [
            'ues' => $ues,
            'parcours' => $parcours,
            'filieres' => $filieres,
            'niveaux' => $niveaux,
            'reglagesMaquette' => $this->reglagesMaquette(),
        ]);
    }

    /**
     * Reglages d'instance qui pilotent l'ecran des maquettes.
     *
     * Chaque valeur par defaut reproduit ce que l'ecran fait aujourd'hui, sauf
     * `reserver_par_defaut` : la directrice des etudes a tranche que la case
     * « cette maquette uniquement » doit etre cochee d'avance, parce que les
     * deux erreurs n'ont pas le meme cout. Reserver par erreur se voit — le
     * parcours voisin le constate, la somme des credits le dit. Mettre par
     * erreur dans les deux maquettes ne se voit pas. Une ecole qui prefere
     * l'inverse pose le reglage a faux.
     */
    private function reglagesMaquette(): array
    {
        return [
            'reserver_par_defaut' => (bool) SettingsHelper::get('lmd.maquette.reserver_par_defaut', true),
            'masquer_jauge_sans_reference' => (bool) SettingsHelper::get('lmd.maquette.masquer_jauge_sans_reference', true),
            'partage_disponible' => $this->lectureMaquettes->pivotPorteLeParcours(),
        ];
    }

    /**
     * Serialise une unite pour la liste, du point de vue de la maquette de
     * travail choisie.
     *
     * Deux nombres comptent pour qui saisit : combien d'elements CETTE maquette
     * voit, et combien d'entre eux lui sont propres. `matieres_count` compte
     * toutes les matieres de l'unite, tous parcours confondus : il mentirait des
     * qu'un element est reserve.
     */
    private function unitePourLaListe(ESBTPUniteEnseignement $ue, ?int $parcoursTravailId): array
    {
        $parcoursDeLUnite = $ue->parcoursMultiple->groupBy('id')->map(fn ($pivots) => [
            'id' => (int) $pivots->first()->id,
            'code' => $pivots->first()->code,
            'name' => $pivots->first()->name,
            'semestres' => $pivots->pluck('pivot.semestre')->unique()->sort()->values(),
        ])->values();

        $parcoursParEcue = $this->lectureMaquettes->parcoursParEcue($ue);
        $tousLesEcues = $ue->getEcuesEffectifs();

        $ecuesVisibles = $tousLesEcues->filter(fn ($e) => LectureMaquettes::visibleDepuis(
            $parcoursParEcue[(int) $e->id] ?? [],
            $parcoursTravailId
        ))->values();

        $compte = LectureMaquettes::compter(
            $parcoursParEcue,
            $tousLesEcues->pluck('id')->all(),
            $parcoursTravailId
        );

        $reference = $this->creditDeReference($ue, $parcoursTravailId);

        // Ce qui entrerait dans une maquette nouvellement cochee : un element
        // sans ligne propre appartient a toutes les maquettes de son unite. Le
        // modal de liaison en donne le nombre AVANT de faire le geste.
        $partages = $tousLesEcues
            ->filter(fn ($e) => ($parcoursParEcue[(int) $e->id] ?? []) === [])
            ->map(fn ($e) => [
                'id' => $e->id,
                'code' => $e->code,
                'name' => $e->name,
                // Nullsafe : un élément tenu par la seule clé étrangère n'a pas
                // de ligne de pivot, donc pas de `pivot`.
                'credit' => (int) ($e->pivot?->credit_ecue ?? $e->credit_ecue ?? 0),
            ])->values();

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
            'elements_visibles' => $compte['visibles'],
            'elements_reserves' => $compte['reserves'],
            'credit_reference' => $reference['valeur'],
            'credit_reference_source' => $reference['source'],
            'elements_partages' => $partages,
            'credits_partages' => $partages->sum('credit'),
            'parcours' => $parcoursDeLUnite,
            'ecues' => $ecuesVisibles->map(function ($e) use ($parcoursParEcue, $parcoursDeLUnite) {
                $reserveA = $parcoursParEcue[(int) $e->id] ?? [];

                return [
                    'id' => $e->id,
                    'code' => $e->code,
                    'name' => $e->name,
                    'coefficient' => $e->pivot?->coefficient_ecue ?? $e->coefficient_ecue ?? null,
                    'credit' => $e->pivot?->credit_ecue ?? $e->credit_ecue ?? null,
                    'ordre' => $e->pivot?->ordre_bulletin ?? $e->ordre_bulletin ?? 0,
                    'reserve' => $reserveA !== [],
                    // Les memes pastilles que la ligne d'unite : l'element dit
                    // dans quelles maquettes il se trouve, pas une categorie.
                    'maquettes' => $reserveA === []
                        ? $parcoursDeLUnite
                        : $parcoursDeLUnite->whereIn('id', $reserveA)->values(),
                ];
            })->values(),
        ];
    }

    /**
     * Credit qui sert de reference a la jauge du modal, et d'ou il vient.
     *
     * La jauge se calculait sur `esbtp_unites_enseignement.credit`. Le jour ou
     * le credit devient propre au parcours, ce nombre est celui d'UNE AUTRE
     * maquette et rien ne le signale. Une jauge fausse est pire que pas de
     * jauge : quand la reference de la maquette de travail est introuvable, on
     * renvoie null et l'ecran masque la jauge en le disant.
     *
     * @return array{valeur: ?int, source: string}
     */
    private function creditDeReference(ESBTPUniteEnseignement $ue, ?int $parcoursTravailId): array
    {
        $creditParMaquette = Schema::hasColumn('esbtp_lmd_parcours_ue', 'credit');

        if ($parcoursTravailId === null || ! $creditParMaquette) {
            return ['valeur' => $ue->credit !== null ? (int) $ue->credit : null, 'source' => 'unite'];
        }

        $valeur = DB::table('esbtp_lmd_parcours_ue')
            ->where('unite_enseignement_id', $ue->id)
            ->where('parcours_id', $parcoursTravailId)
            ->value('credit');

        return [
            'valeur' => $valeur === null ? null : (int) $valeur,
            'source' => 'maquette',
        ];
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
     *  - la clé étrangère esbtp_matieres.unite_enseignement_id (rétro-compat),
     *    uniquement si elle est libre ou déjà la nôtre : reprendre le code d'un
     *    élément constitutif appartenant à une autre UE le partage, ne le déplace
     *    pas ;
     *  - le pivot esbtp_ue_matiere, qui porte coefficient / crédit / ordre
     *    propres à CETTE UE et permet le partage d'un ECUE entre deux UE.
     *
     * Le refus d'absorber une matière du cursus BTS est porté d'abord par
     * UniteEnseignementRequest, puis rejoué ici par refuserAbsorptionMatiereBts()
     * : storeECUE() valide en ligne, sans ce FormRequest, et doit donc appeler
     * la même garde.
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
            $existait = $matiere !== null;
            if ($matiere && $matiere->trashed()) {
                $matiere->restore();
            }

            // Reprendre le code d'un element deja rattache a une AUTRE unite ne
            // doit pas le lui retirer. Sans ligne de pivot, cette unite-la lit
            // ses elements par la cle etrangere (getEcuesEffectifs retombe sur
            // le hasMany) : lui reecrire la cle la depouillerait de l'element et
            // de ses credits, sans message ni trace. On partage par le pivot.
            // Defense en profondeur : le FormRequest a deja refuse un code du
            // cursus BTS, mais la garde est rejouee ici pour que tout appelant
            // futur de cette methode soit couvert.
            $this->refuserAbsorptionMatiereBts($matiere);

            $proprietaireId = $matiere?->unite_enseignement_id;
            $appartientAUneAutreUe = $proprietaireId !== null
                && (int) $proprietaireId !== (int) $ue->id;

            // Avant d'ecrire quoi que ce soit, on affranchit l'unite proprietaire
            // du repli par cle etrangere : sinon la ligne de pivot que nous
            // ecrivons plus bas resterait sa seule protection, et les valeurs que
            // nous posons sur la matiere deviendraient les siennes.
            if ($appartientAUneAutreUe) {
                $this->materialiserPivotDepuisCleEtrangere((int) $proprietaireId);
            }

            $matiere = $matiere ?: new ESBTPMatiere();

            $matiere->fill([
                'name' => $ligne['name'],
                'code' => $code,
                'credit_ecue' => $credit,
                'coefficient_ecue' => $coefficient,
                'ordre_bulletin' => $ordre,
            ]);
            if (! $appartientAUneAutreUe) {
                $matiere->unite_enseignement_id = $ue->id;
            }
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
     * Refuse d'absorber dans le LMD une matière du cursus BTS.
     *
     * Réutiliser le code d'une matière BTS écrirait `unite_enseignement_id` sur
     * elle : elle deviendrait un ECUE et disparaîtrait de tous les sélecteurs
     * BTS, qui filtrent précisément sur `unite_enseignement_id IS NULL` — en
     * emportant ses évaluations et ses notes. `esbtp_matieres` étant partagée
     * par les deux cursus, l'effet porte sur les instances BTS en service.
     *
     * Une matière déjà rattachée à une UE — par la colonne ou par le pivot
     * `esbtp_ue_matiere`, le partage d'un ECUE entre deux UE étant légitime —
     * n'est pas une matière BTS : elle passe.
     */
    private function refuserAbsorptionMatiereBts(?ESBTPMatiere $matiere): void
    {
        if (! $matiere || $matiere->unite_enseignement_id !== null) {
            return;
        }

        if (DB::table('esbtp_ue_matiere')->where('matiere_id', $matiere->id)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'ecues' => sprintf(
                'Le code « %s » est déjà celui d\'une matière du cursus BTS (« %s »). Choisissez un autre code : réutiliser celui-ci retirerait cette matière des écrans BTS.',
                (string) $matiere->code,
                (string) $matiere->name
            ),
        ]);
    }

    /**
     * Matérialise dans le pivot les éléments constitutifs qu'une UE ne tient que
     * par la clé étrangère `esbtp_matieres.unite_enseignement_id`.
     *
     * C'est l'état des maquettes importées : l'import ne renseigne que la clé
     * étrangère. Avant de partager un de ces éléments avec une autre UE, on fige
     * pour l'unité propriétaire le coefficient, le crédit et l'ordre que la
     * matière portait — sans quoi les valeurs que la seconde UE écrira sur la
     * matière deviendraient aussi les siennes. On recopie exactement ce que la
     * lecture affichait déjà : l'écran ne change pas.
     *
     * (`getEcuesEffectifs()` retourne désormais l'union du pivot et de la clé
     * étrangère : cette matérialisation ne masque plus rien.)
     */
    private function materialiserPivotDepuisCleEtrangere(int $uniteEnseignementId): void
    {
        $unite = ESBTPUniteEnseignement::find($uniteEnseignementId);

        // Pivot déjà renseigné : c'est lui qui fait foi, rien à reprendre.
        if (! $unite || $unite->ecues()->exists()) {
            return;
        }

        $liens = [];
        // Même périmètre que le repli de getEcuesEffectifs() : les actives.
        foreach ($unite->matieres()->where('is_active', true)->get() as $ecue) {
            $liens[$ecue->id] = [
                'coefficient_ecue' => $ecue->coefficient_ecue,
                'credit_ecue' => $ecue->credit_ecue,
                'ordre_bulletin' => (int) ($ecue->ordre_bulletin ?? 0),
            ];
        }

        if ($liens !== []) {
            $unite->ecues()->syncWithoutDetaching($liens);
        }
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
            // Maquette de travail : a quelle maquette l'element est destine.
            'parcours_travail_id' => 'nullable|integer|exists:esbtp_lmd_parcours,id',
            'reserve'          => 'nullable|boolean',
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

            // Cette route valide en ligne, elle ne passe pas par
            // UniteEnseignementRequest : la garde anti-absorption BTS doit être
            // rejouée ici, sinon un clic dans « Lier une matière existante »
            // sortirait une matière BTS de tous les sélecteurs BTS.
            $this->refuserAbsorptionMatiereBts($matiere);
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

        // Clé étrangère (rétro-compat) : on ne l'écrit que si elle est libre ou
        // déjà la nôtre. La reprendre à l'unité voisine qui ne tient ses
        // éléments que par elle la dépouillerait, en silence — même règle que
        // synchroniserEcues(). Le partage passe par le pivot, écrit juste après.
        $proprietaireId = $matiere->unite_enseignement_id;
        $appartientAUneAutreUe = $proprietaireId !== null
            && (int) $proprietaireId !== (int) $ue->id;

        if ($appartientAUneAutreUe) {
            $this->materialiserPivotDepuisCleEtrangere((int) $proprietaireId);
        } elseif ($proprietaireId === null) {
            $matiere->update(['unite_enseignement_id' => $ue->id, 'updated_by' => auth()->id()]);
        }

        // Écrire dans le pivot (many-to-many) avec coeff/credit contextuels,
        // dans la maquette voulue. Trace au journal : la question « pourquoi cet
        // élément est-il là ? » se pose autant que l'inverse.
        $maquetteCible = $this->maquetteCible(
            $validated['parcours_travail_id'] ?? null,
            $request->boolean('reserve')
        );

        $this->journal->enregistrer(
            $ue,
            'ajout_element',
            sprintf('« %s » ajouté à « %s »%s.', $matiere->name, $ue->name, $this->suffixeMaquette($maquetteCible)),
            fn () => $this->ecrirePivotEcue($ue, (int) $matiere->id, $maquetteCible, $coeffEcue, $creditEcue, $ordreBulletin)
        );

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

        // Mettre à jour le pivot avec les valeurs contextuelles à cette UE, sur
        // la seule ligne visée : viser la matière seule réécrirait la ligne
        // partagée quand on croit modifier une ligne réservée.
        $this->ecrirePivotEcue(
            $ue,
            (int) $ecue->id,
            $this->maquetteExistantePourEcriture($ue, (int) $ecue->id, $request->input('parcours_travail_id')),
            $validated['coefficient_ecue'] ?? null,
            $validated['credit_ecue'] ?? null,
            $validated['ordre_bulletin'] ?? 0
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE mis à jour.']);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE mis à jour avec succès.');
    }

    /**
     * Maquette visee par une creation d'element.
     *
     * Sans maquette de travail choisie, ou tant que le pivot ne sait pas porter
     * un parcours, l'element reste partage : c'est le comportement actuel.
     */
    private function maquetteCible(?int $parcoursTravailId, bool $reserve): int
    {
        if (! $reserve || $parcoursTravailId === null || ! $this->lectureMaquettes->pivotPorteLeParcours()) {
            return LectureMaquettes::MAQUETTE_PARTAGEE;
        }

        return $parcoursTravailId;
    }

    /**
     * Ligne de pivot a modifier pour un element deja present.
     *
     * On ne deplace jamais un element d'une maquette a l'autre au detour d'une
     * modification de coefficient : si l'element a une ligne propre a la
     * maquette de travail, c'est elle ; sinon c'est sa ligne partagee.
     */
    private function maquetteExistantePourEcriture(ESBTPUniteEnseignement $ue, int $matiereId, $parcoursTravailId): int
    {
        if (! $this->lectureMaquettes->pivotPorteLeParcours() || $parcoursTravailId === null) {
            return LectureMaquettes::MAQUETTE_PARTAGEE;
        }

        $existe = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->where('matiere_id', $matiereId)
            ->where(LectureMaquettes::COLONNE_PARCOURS, (int) $parcoursTravailId)
            ->exists();

        return $existe ? (int) $parcoursTravailId : LectureMaquettes::MAQUETTE_PARTAGEE;
    }

    private function suffixeMaquette(int $maquetteId): string
    {
        if ($maquetteId === LectureMaquettes::MAQUETTE_PARTAGEE) {
            return '';
        }

        $code = ESBTPLMDParcours::whereKey($maquetteId)->value('code');

        return $code ? sprintf(' (maquette %s)', $code) : '';
    }

    /**
     * Ecrit une ligne de pivot en visant explicitement (unite, matiere,
     * maquette).
     *
     * La relation Eloquent se cale sur la seule matiere : avec une cle a trois
     * colonnes, `syncWithoutDetaching` retrouverait une ligne d'une AUTRE
     * maquette et la reecrirait au lieu d'ajouter la sienne. Tant que la colonne
     * de parcours n'existe pas, on garde le chemin Eloquent d'origine.
     */
    private function ecrirePivotEcue(
        ESBTPUniteEnseignement $ue,
        int $matiereId,
        int $maquetteId,
        $coefficient,
        $credit,
        $ordre
    ): void {
        if (! $this->lectureMaquettes->pivotPorteLeParcours()) {
            $ue->ecues()->syncWithoutDetaching([
                $matiereId => [
                    'coefficient_ecue' => $coefficient,
                    'credit_ecue' => $credit,
                    'ordre_bulletin' => $ordre,
                ],
            ]);

            return;
        }

        $cle = [
            'unite_enseignement_id' => $ue->id,
            'matiere_id' => $matiereId,
            LectureMaquettes::COLONNE_PARCOURS => $maquetteId,
        ];

        $valeurs = [
            'coefficient_ecue' => $coefficient,
            'credit_ecue' => $credit,
            'ordre_bulletin' => $ordre,
            'updated_at' => now(),
        ];

        $existante = DB::table('esbtp_ue_matiere')->where($cle)->first();

        if ($existante) {
            DB::table('esbtp_ue_matiere')->where('id', $existante->id)->update($valeurs);

            return;
        }

        DB::table('esbtp_ue_matiere')->insert($cle + $valeurs + ['created_at' => now()]);
    }

    /**
     * Détacher un ECUE de l'UE (ne supprime pas la matière).
     */
    public function destroyECUE(Request $request, ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue)
    {
        $maquetteId = $request->filled('parcours_travail_id')
            ? (int) $request->input('parcours_travail_id')
            : null;

        $retraitCiblé = $maquetteId !== null && $this->lectureMaquettes->pivotPorteLeParcours();

        $libelle = $retraitCiblé
            ? sprintf(
                '« %s » retiré de la maquette %s.',
                $ecue->name,
                (string) (ESBTPLMDParcours::whereKey($maquetteId)->value('code') ?? $maquetteId)
            )
            : sprintf('« %s » retiré de l\'unité « %s ».', $ecue->name, $ue->name);

        $this->journal->enregistrer($ue, 'retrait_element', $libelle, function () use ($ue, $ecue, $maquetteId, $retraitCiblé) {
            if ($retraitCiblé) {
                $this->retirerDUneMaquette($ue, $ecue, $maquetteId);
            } else {
                $ue->ecues()->detach($ecue->id);
            }

            // La matiere n'est plus dans AUCUNE ligne de pivot de cette unite :
            // la cle etrangere doit suivre, sinon l'union de getEcuesEffectifs()
            // la reafficherait. Elle n'est pas supprimee de l'ecole pour autant.
            $resteDansLUnite = DB::table('esbtp_ue_matiere')
                ->where('unite_enseignement_id', $ue->id)
                ->where('matiere_id', $ecue->id)
                ->exists();

            if (! $resteDansLUnite && (int) $ecue->unite_enseignement_id === (int) $ue->id) {
                $ecue->update(['unite_enseignement_id' => null, 'updated_by' => auth()->id()]);
            }
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $retraitCiblé
                    ? 'Élément retiré de la maquette.'
                    : 'Élément retiré de l\'unité.',
                'journal_id' => $this->journal->derniereEntree?->id,
            ]);
        }
        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'Élément retiré de l\'unité avec succès.');
    }

    /**
     * Retire un element d'UNE maquette sans le retirer des autres.
     *
     * Un element partage n'a qu'une ligne, sans parcours : la supprimer le
     * retirerait de toutes les maquettes d'un coup. On la remplace donc par une
     * ligne par maquette restante, en recopiant ses valeurs — les autres
     * parcours ne voient aucune difference.
     */
    private function retirerDUneMaquette(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue, int $maquetteId): void
    {
        $this->materialiserPivotDepuisCleEtrangere($ue->id);

        $colonne = LectureMaquettes::COLONNE_PARCOURS;

        $partagee = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->where('matiere_id', $ecue->id)
            ->where($colonne, LectureMaquettes::MAQUETTE_PARTAGEE)
            ->first();

        if ($partagee) {
            $restantes = $ue->parcoursMultiple()->pluck('esbtp_lmd_parcours.id')
                ->unique()->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === $maquetteId)
                ->values();

            DB::table('esbtp_ue_matiere')->where('id', $partagee->id)->delete();

            foreach ($restantes as $parcoursId) {
                DB::table('esbtp_ue_matiere')->updateOrInsert(
                    [
                        'unite_enseignement_id' => $ue->id,
                        'matiere_id' => $ecue->id,
                        $colonne => $parcoursId,
                    ],
                    [
                        'coefficient_ecue' => $partagee->coefficient_ecue,
                        'credit_ecue' => $partagee->credit_ecue,
                        'ordre_bulletin' => $partagee->ordre_bulletin,
                        'created_at' => $partagee->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            return;
        }

        DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->where('matiere_id', $ecue->id)
            ->where($colonne, $maquetteId)
            ->delete();
    }

    /**
     * Liste des matières disponibles pour rattachement à une UE (non déjà liées).
     */
    public function matieresDisponibles(ESBTPUniteEnseignement $ue)
    {
        // Ne proposer que des éléments constitutifs déjà LMD — par la colonne ou
        // par le pivot. Sans ce filtre, la liste offre l'intégralité du catalogue
        // BTS de l'établissement, et un seul clic sortirait une matière BTS de
        // tous les écrans BTS (ils filtrent sur `unite_enseignement_id IS NULL`).
        $matieres = ESBTPMatiere::where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('unite_enseignement_id')
                    ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                        ->from('esbtp_ue_matiere')
                        ->whereColumn('esbtp_ue_matiere.matiere_id', 'esbtp_matieres.id'));
            })
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
     *
     * C'est le geste le plus lourd de l'ecran : cocher un parcours fait entrer
     * d'un coup tous les elements de l'unite dans une maquette et y deplace ses
     * credits. Il demande donc QUELS elements entrent, il laisse une trace, et
     * il s'annule.
     */
    public function syncParcours(Request $request, ESBTPUniteEnseignement $ue)
    {
        $request->validate([
            'parcours' => 'present|array',
            'parcours.*.id' => 'required|exists:esbtp_lmd_parcours,id',
            'parcours.*.semestres' => 'required|array|min:1',
            'parcours.*.semestres.*' => 'integer|between:1,10',
            'elements' => 'nullable|in:tous,aucun,choisis',
            'element_ids' => 'nullable|array',
            'element_ids.*' => 'integer',
        ]);

        $modeElements = $request->input('elements', 'tous');
        $elementsEntrants = array_map('intval', $request->input('element_ids', []));

        if ($modeElements !== 'tous' && ! $this->lectureMaquettes->pivotPorteLeParcours()) {
            // Repondre « c'est fait » alors que les elements entrent quand meme
            // serait le pire des deux mondes : la personne croirait avoir
            // restreint la maquette.
            return response()->json([
                'success' => false,
                'message' => "Sur cette instance, un élément appartient encore à toutes les maquettes de son unité : il n'est pas possible de n'en faire entrer qu'une partie. Liez le parcours, puis retirez ce qui ne doit pas y figurer.",
            ], 422);
        }

        $libelle = $this->libelleLiaison($ue, $request->input('parcours', []));

        $count = $this->journal->enregistrer($ue, 'liaison_parcours', $libelle, function () use ($request, $ue, $modeElements, $elementsEntrants) {
            $avant = $ue->parcoursMultiple()->pluck('esbtp_lmd_parcours.id')
                ->unique()->map(fn ($id) => (int) $id)->values()->all();

            $ue->parcoursMultiple()->detach();
            $count = 0;
            foreach ($request->input('parcours', []) as $item) {
                foreach ($item['semestres'] as $sem) {
                    $ue->parcoursMultiple()->attach($item['id'], ['semestre' => $sem]);
                    $count++;
                }
            }

            if ($modeElements !== 'tous') {
                $this->reserverAuxMaquettesExistantes($ue, $avant, $elementsEntrants);
            }

            return $count;
        });

        return response()->json([
            'success' => true,
            'message' => $count . ' lien(s) parcours-semestre créé(s).',
            'journal_id' => $this->journal->derniereEntree?->id,
        ]);
    }

    /**
     * Empeche les elements non choisis d'entrer dans les maquettes nouvellement
     * liees, en les reservant explicitement a celles qui les voyaient deja.
     *
     * Un element sans ligne de pivot propre appartient a toutes les maquettes de
     * son unite : le laisser tel quel le ferait entrer partout. On ecrit donc
     * une ligne par maquette d'origine, en recopiant le coefficient, le credit
     * et l'ordre qu'il portait — l'affichage ne bouge pas pour les parcours qui
     * l'avaient deja.
     *
     * Les ecritures visent le triplet (unite, matiere, parcours) explicitement.
     * Passer par la relation Eloquent viserait la seule matiere : creer une
     * ligne reservee reecrirait la ligne partagee au lieu de s'ajouter.
     *
     * @param  int[]  $maquettesOrigine  parcours qui portaient l'unite avant le geste
     * @param  int[]  $elementsEntrants  elements autorises a rejoindre les nouvelles maquettes
     */
    private function reserverAuxMaquettesExistantes(ESBTPUniteEnseignement $ue, array $maquettesOrigine, array $elementsEntrants): void
    {
        if ($maquettesOrigine === []) {
            // Aucune maquette d'origine : reserver a rien reviendrait a rendre
            // l'element invisible partout. On laisse le partage.
            return;
        }

        $this->materialiserPivotDepuisCleEtrangere($ue->id);

        $colonne = LectureMaquettes::COLONNE_PARCOURS;
        $partagees = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->where($colonne, LectureMaquettes::MAQUETTE_PARTAGEE)
            ->get();

        foreach ($partagees as $ligne) {
            if (in_array((int) $ligne->matiere_id, $elementsEntrants, true)) {
                continue;
            }

            DB::table('esbtp_ue_matiere')->where('id', $ligne->id)->delete();

            foreach ($maquettesOrigine as $parcoursId) {
                DB::table('esbtp_ue_matiere')->updateOrInsert(
                    [
                        'unite_enseignement_id' => $ue->id,
                        'matiere_id' => $ligne->matiere_id,
                        $colonne => $parcoursId,
                    ],
                    [
                        'coefficient_ecue' => $ligne->coefficient_ecue,
                        'credit_ecue' => $ligne->credit_ecue,
                        'ordre_bulletin' => $ligne->ordre_bulletin,
                        'updated_at' => now(),
                        'created_at' => $ligne->created_at ?? now(),
                    ]
                );
            }
        }
    }

    /**
     * Phrase du journal : ce que la personne lira trois mois plus tard.
     */
    private function libelleLiaison(ESBTPUniteEnseignement $ue, array $parcours): string
    {
        $ids = array_map(fn ($item) => (int) $item['id'], $parcours);
        $codes = ESBTPLMDParcours::whereIn('id', $ids)->orderBy('code')->pluck('code')->all();

        return $codes === []
            ? sprintf('« %s » n\'est plus rattachée à aucune maquette.', $ue->name)
            : sprintf('Maquettes de « %s » : %s.', $ue->name, implode(', ', $codes));
    }

    /**
     * Journal des gestes de maquette d'une unite : qui, quand, quoi.
     */
    public function journal(ESBTPUniteEnseignement $ue)
    {
        $entrees = ESBTPLMDJournalMaquette::where('unite_enseignement_id', $ue->id)
            ->with('auteur:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'entrees' => $entrees->map(fn ($e) => [
                'id' => $e->id,
                'action' => $e->action,
                'libelle' => $e->libelle,
                'auteur' => $e->auteur?->name ?? 'Utilisateur supprimé',
                'date' => $e->created_at?->format('d/m/Y à H:i'),
                'annulee' => $e->estAnnulee(),
                'annulable' => ! $e->estAnnulee()
                    && in_array($e->action, JournalMaquette::ACTIONS_ANNULABLES, true),
            ]),
        ]);
    }

    /**
     * Annuler un geste : reposer l'etat d'avant, tel quel.
     */
    public function annulerJournal(ESBTPUniteEnseignement $ue, ESBTPLMDJournalMaquette $entree)
    {
        abort_unless((int) $entree->unite_enseignement_id === (int) $ue->id, 404);

        try {
            $this->journal->annuler($entree);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json(['success' => true, 'message' => 'Geste annulé : l\'état précédent est rétabli.']);
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
