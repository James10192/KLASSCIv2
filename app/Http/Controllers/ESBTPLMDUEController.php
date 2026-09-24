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
use App\Services\LMD\CodeDeMatiere;
use App\Services\LMD\CompositionUe;
use App\Services\LMD\EcritureEcue;
use App\Services\LMD\ParcoursUeSyncService;
use App\Services\LMD\SuppressionUeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ESBTPLMDUEController extends Controller
{
    /** Codes libérés par synchroniserEcues(), à dire dans la réponse. */
    private array $codesLiberes = [];

    public function __construct(
        private ParcoursUeSyncService $parcoursUeSync,
        private SuppressionUeService $suppressionUe,
        private CompositionUe $composition,
        private CodeDeMatiere $codes,
        private EcritureEcue $ecritures,
    ) {}

    /**
     * Afficher la liste des Unités d'Enseignement avec filtres.
     */
    public function index(Request $request)
    {
        $query = ESBTPUniteEnseignement::query()
            ->withCount('matieres')
            ->with(['filiere', 'niveau', 'parcours', 'parcoursMultiple', 'ecues', 'matieres']);

        // Filtres optionnels
        //
        // Le parcours se filtre PAR LE PIVOT, plus bas, et par lui seul. La
        // colonne `parcours_id` de l'unite est heritee : elle ne peut designer
        // qu'UNE maquette, alors qu'une unite en sert plusieurs. Cumuler les deux
        // conditions revenait a exiger que l'unite soit liee au parcours ET que
        // sa colonne le nomme — donc a faire disparaitre de la liste filtree
        // exactement les unites PARTAGEES, chacune manquant au parcours qui n'est
        // pas celui de sa colonne. Sur presentation, trois unites sur cent huit,
        // et ce sont les seules qui comptent pour ce chantier.

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

        // Le parcours, par le pivot : la seule voie qui sache dire qu'une unite
        // sert plusieurs maquettes. La colonne heritee est reconnue en plus,
        // sinon une unite importee dont le pivot n'a jamais ete ecrit
        // disparaitrait de la liste de son propre parcours.
        if ($request->filled('parcours_id')) {
            $pId = (int) $request->parcours_id;
            $query->where(function ($q) use ($pId) {
                $q->whereHas('parcoursMultiple', fn ($sub) => $sub->where('esbtp_lmd_parcours.id', $pId))
                    ->orWhere('esbtp_unites_enseignement.parcours_id', $pId);
            });
        }

        if ($request->filled('type_ue')) {
            $query->where('type_ue', $request->type_ue);
        }

        $perPage = $request->integer('per_page', 20);
        $ues = $query->orderBy('code')->orderBy('name')->paginate($perPage)->withQueryString();

        // JSON response for AJAX
        if ($request->ajax() || $request->wantsJson() || $request->format === 'json') {
            // Quand l'ecran est filtre sur une maquette, la composition affichee
            // est celle de CETTE maquette : la commune, plus ce que le parcours
            // surcharge. Sans filtre, on montre tout, un element une seule fois.
            $parcoursFiltre = $request->filled('parcours_id') ? (int) $request->parcours_id : null;

            return response()->json([
                'ues' => $ues->map(function ($ue) use ($parcoursFiltre) {
                    $ecues = $ue->getEcuesEffectifs($parcoursFiltre);
                    return [
                        'id' => $ue->id,
                        'code' => $ue->code,
                        'name' => $ue->name,
                        'type_ue' => $ue->type_ue,
                        'credit' => $ue->credit,
                        'description' => $ue->description,
                        'filiere_id' => $ue->filiere_id,
                        'niveau_id' => $ue->niveau_id,
                        // Les elements que CETTE vue montre : filtree sur un
                        // parcours, ceux de sa maquette. Le compte par cle
                        // etrangere affichait « 2 » a cote de « Aucun ECUE
                        // rattache » (USAT).
                        'matieres_count' => $ecues->pluck('id')->unique()->count(),
                        // Elements a la fois communs et reserves a un parcours :
                        // la ligne commune les montre a TOUS les parcours, ce
                        // que la reservation laisse croire impossible. Calcule
                        // sur le pivot entier, quel que soit le filtre.
                        'communs_et_reserves' => $this->communsEtReserves($ue),
                        'parcours' => $ue->parcoursMultiple->groupBy('id')->map(fn($pivots) => [
                            'id' => $pivots->first()->id,
                            'code' => $pivots->first()->code,
                            'name' => $pivots->first()->name,
                            'semestres' => $pivots->pluck('pivot.semestre')->sort()->values(),
                        ])->values(),
                        // La maquette que porte chaque ligne : 0 pour la composition
                        // commune, l'identifiant du parcours pour une reservation.
                        // Sans elle, l'ecran ne peut ni dire a qui appartient un
                        // element, ni viser la bonne ligne pour le modifier ou le
                        // retirer.
                        'ecues' => $ecues->map(function ($e) use ($ue) {
                            $portee = (int) ($e->pivot->parcours_id ?? 0);
                            $parcours = $portee > 0 ? $ue->parcoursMultiple->firstWhere('id', $portee) : null;

                            return [
                                'id' => $e->id,
                                'code' => $e->code,
                                'name' => $e->name,
                                // Le coefficient que les bulletins utilisent vraiment
                                // (meme repli que LMDBulletinService) : afficher
                                // « — » laissait croire a un element sans poids.
                                'coefficient' => $e->pivot->coefficient_ecue ?? $e->coefficient_ecue ?? $e->coefficient ?? 1,
                                'credit' => $e->pivot->credit_ecue ?? $e->credit_ecue ?? null,
                                'ordre' => $e->pivot->ordre_bulletin ?? $e->ordre_bulletin ?? 0,
                                'portee' => $portee,
                                'portee_code' => $parcours?->code,
                                'portee_label' => $parcours ? ($parcours->name ?? $parcours->code) : null,
                            ];
                        }),
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
     * @return array<int, array{id:int, name:string, reserve_a:array<int,string>}>
     */
    private function communsEtReserves(ESBTPUniteEnseignement $ue): array
    {
        $codes = $ue->parcoursMultiple->pluck('code', 'id');

        return $ue->ecues->groupBy('id')
            ->map(function ($lignes) use ($codes) {
                $portees = $lignes->map(fn ($l) => (int) ($l->pivot->parcours_id ?? 0));
                if (! $portees->contains(0) || $portees->filter()->isEmpty()) {
                    return null;
                }

                return [
                    'id' => (int) $lignes->first()->id,
                    'name' => (string) $lignes->first()->name,
                    'reserve_a' => $portees->filter()->map(fn ($id) => $codes[$id] ?? ('#' . $id))->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
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
            return response()->json(['success' => true, 'message' => $this->avecCodesLiberes('UE créée avec succès.'), 'ue' => $ue]);
        }

        return redirect()->route('esbtp.lmd.ue.show', $ue)
            ->with('success', $this->avecCodesLiberes('Unité d\'Enseignement créée avec succès.'));
    }

    /**
     * Afficher le détail d'une UE avec ses ECUEs (matières).
     */
    public function show(ESBTPUniteEnseignement $ue)
    {
        $ue->load([
            'matieres', 'ecues', 'filiere', 'niveau', 'parcours',
            'parcoursMultiple.filiere', 'responsableUe', 'createdBy', 'updatedBy',
        ]);

        $maquettes = $this->maquettesDeLaFiche($ue);

        return view('esbtp.lmd.ue.show', [
            'ue' => $ue,
            'maquettes' => $maquettes,
            'nbEcues' => collect($maquettes)->flatMap(fn ($m) => $m['ecues']->pluck('id'))->unique()->count(),
            'rattachement' => $this->rattachementDeLaFiche($ue),
        ]);
    }

    /**
     * Où l'UE est rattachée, lu sur les liens parcours-UE et non sur les
     * colonnes de la fiche : celles-ci ne gardent que le parcours, la filière
     * et le semestre du premier import, faux pour une UE partagée.
     *
     * @return array{parcours: array<int, array{nom: string, code: ?string, semestres: list<int>}>, est_partagee: bool, filieres: list<string>, parcours_sans_filiere: int, semestres: list<int>}
     */
    private function rattachementDeLaFiche(ESBTPUniteEnseignement $ue): array
    {
        $parcours = $ue->parcoursMultiple
            ->groupBy('id')
            ->map(fn ($liens) => [
                'nom' => $liens->first()->name ?? $liens->first()->code,
                'code' => $liens->first()->code,
                'semestres' => $liens->pluck('pivot.semestre')->filter()->map(fn ($s) => (int) $s)->unique()->sort()->values()->all(),
            ])
            ->values()
            ->all();

        $semestres = collect($parcours)->flatMap(fn ($p) => $p['semestres'])->unique()->sort()->values()->all();
        if ($semestres === [] && $ue->semestre) {
            $semestres = [(int) $ue->semestre];
        }

        $distincts = $ue->parcoursMultiple->unique('id');

        return [
            'parcours' => $parcours,
            'est_partagee' => count($parcours) > 1,
            'filieres' => $distincts->map(fn ($p) => $p->filiere?->name)->filter()->unique()->sort()->values()->all(),
            'parcours_sans_filiere' => $distincts->filter(fn ($p) => ! $p->filiere)->count(),
            'semestres' => $semestres,
        ];
    }

    /**
     * Une maquette par parcours qui utilise l'unite : ses elements et leurs
     * heures. Une UE partagee n'a pas UNE composition ni UNE masse horaire :
     * la fiche montrait tous les elements melanges et les heures de la filiere
     * du premier parcours importe, que l'autre parcours n'avait jamais saisies.
     *
     * @return array<int, array{parcours: ?ESBTPLMDParcours, semestre: ?int, credit_ue: ?int, ecues: \Illuminate\Support\Collection, volumes: array, credits: int, heures: int}>
     */
    private function maquettesDeLaFiche(ESBTPUniteEnseignement $ue): array
    {
        // La meme annee que le planning, qui ecrit ces heures : sans annee en
        // cours, il n'affiche rien, la fiche non plus.
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Un onglet par couple parcours × semestre : une UE peut servir un meme
        // parcours sur deux semestres, avec deux masses horaires.
        $parcours = $ue->parcoursMultiple
            ->unique(fn ($p) => $p->id . ':' . $p->pivot->semestre)
            ->sortBy(fn ($p) => $p->code . ':' . $p->pivot->semestre)
            ->values();
        $vues = $parcours->isEmpty() ? [null] : $parcours->all();

        return array_map(function (?ESBTPLMDParcours $p) use ($ue, $annee) {
            $semestre = $p ? ((int) $p->pivot->semestre ?: null) : null;
            $semestre ??= $ue->semestre ? (int) $ue->semestre : null;

            // Tri sur une cle composite (ordre bulletin, puis intitule).
            $ecues = $ue->getEcuesEffectifs($p?->id)
                ->sortBy(fn ($m) => sprintf(
                    '%06d|%s',
                    (int) ($m->pivot?->ordre_bulletin ?? $m->ordre_bulletin ?? 0),
                    mb_strtolower((string) $m->name)
                ))
                ->values();

            $volumes = $this->volumesHorairesParEcue($ecues, $annee, $p?->filiere_id ?? $ue->filiere_id, $ue->niveau_id, $semestre);

            return [
                'parcours' => $p,
                'semestre' => $semestre,
                // Chaque maquette peut graver son propre credit sur le lien
                // parcours-UE ; la fiche garde celui du premier import.
                'credit_ue' => $p && $p->pivot->credit !== null ? (int) $p->pivot->credit : ($ue->credit !== null ? (int) $ue->credit : null),
                'ecues' => $ecues,
                'volumes' => $volumes,
                'credits' => (int) $ecues->sum(fn ($e) => (int) ($e->pivot?->credit_ecue ?? $e->credit_ecue ?? 0)),
                'heures' => (int) collect($volumes)->sum('total'),
            ];
        }, $vues);
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
            return response()->json(['success' => true, 'message' => $this->avecCodesLiberes('UE mise à jour avec succès.'), 'ue' => $ue]);
        }

        return redirect()->route('esbtp.lmd.ue.show', $ue)
            ->with('success', $this->avecCodesLiberes('Unité d\'Enseignement mise à jour avec succès.'));
    }

    private function avecCodesLiberes(string $message): string
    {
        return trim($message . ' ' . implode(' ', $this->codesLiberes));
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
        // Seuls les champs ENVOYES : une modification partielle remettait a
        // vide le code, le semestre, la filiere et le parcours qu'elle ne
        // mentionnait pas.
        $attributs = array_intersect_key($donnees, array_flip([
            'name', 'code', 'description', 'credit', 'type_ue', 'semestre', 'filiere_id', 'niveau_id', 'parcours_id',
        ]));

        // La colonne refuse le vide (0 par defaut) : un credit laisse vide
        // faisait echouer l'enregistrement sur une erreur serveur.
        if (array_key_exists('credit', $attributs) && $attributs['credit'] === null) {
            $attributs['credit'] = 0;
        }

        return $attributs;
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
     * UniteEnseignementRequest, puis rejoué ici par EcritureEcue::refuserAbsorptionMatiereBts()
     * : storeECUE() valide en ligne, sans ce FormRequest, et doit donc appeler
     * la même garde.
     *
     * $detacherAbsents n'est vrai que si le formulaire a explicitement envoyé la
     * liste complète (champ caché `sync_ecues`) : un appel partiel ne doit jamais
     * détacher en silence des ECUEs qu'il ne connaissait pas.
     */
    private function synchroniserEcues(
        ESBTPUniteEnseignement $ue,
        array $ecues,
        bool $detacherAbsents,
        int $parcoursId = CompositionUe::COMMUN
    ): void {
        $idsConserves = [];

        foreach ($ecues as $index => $ligne) {
            $code = isset($ligne['code']) && $ligne['code'] !== '' ? $ligne['code'] : null;
            $credit = isset($ligne['credit_ecue']) && $ligne['credit_ecue'] !== '' ? (int) $ligne['credit_ecue'] : null;
            $coefficient = isset($ligne['coefficient_ecue']) && $ligne['coefficient_ecue'] !== '' ? (float) $ligne['coefficient_ecue'] : null;
            $ordre = (int) ($ligne['ordre_bulletin'] ?? 0);

            // Réutilisation par code, comme l'import : les codes ECUE sont uniques
            // au niveau de l'établissement, deux saisies du même code désignent
            // la même matière.
            // Une matière supprimée occupe toujours son code (l'index unique la
            // compte) : on le lui libère plutôt que de la ressusciter sous le nom
            // saisi, avec ses notes. Même règle que le modal et l'import.
            if ($message = $this->codes->libererSiArchive($code)) {
                $this->codesLiberes[] = $message;
            }
            $matiere = $code ? ESBTPMatiere::where('code', $code)->first() : null;
            $existait = $matiere !== null;

            // Reprendre le code d'un element deja rattache a une AUTRE unite ne
            // doit pas le lui retirer. Sans ligne de pivot, cette unite-la lit
            // ses elements par la cle etrangere (getEcuesEffectifs retombe sur
            // le hasMany) : lui reecrire la cle la depouillerait de l'element et
            // de ses credits, sans message ni trace. On partage par le pivot.
            // Defense en profondeur : le FormRequest a deja refuse un code du
            // cursus BTS, mais la garde est rejouee ici pour que tout appelant
            // futur de cette methode soit couvert.
            $this->ecritures->refuserAbsorptionMatiereBts($matiere);

            $proprietaireId = $matiere?->unite_enseignement_id;
            $appartientAUneAutreUe = $proprietaireId !== null
                && (int) $proprietaireId !== (int) $ue->id;

            // Avant d'ecrire quoi que ce soit, on affranchit l'unite proprietaire
            // du repli par cle etrangere : sinon la ligne de pivot que nous
            // ecrivons plus bas resterait sa seule protection, et les valeurs que
            // nous posons sur la matiere deviendraient les siennes.
            if ($appartientAUneAutreUe) {
                $this->composition->materialiserDepuisCleEtrangere((int) $proprietaireId);
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
            $this->codes->sousUnicite("ecues.{$index}.code", $code, fn () => $matiere->save());

            // Par le service, jamais par `syncWithoutDetaching` : celui-ci retrouve
            // la ligne par le seul `matiere_id` et reecrirait une composition
            // reservee a une autre maquette.
            $this->composition->poser($ue, (int) $matiere->id, [
                'coefficient_ecue' => $coefficient,
                'credit_ecue' => $credit,
                'ordre_bulletin' => $ordre,
            ], $parcoursId);

            $idsConserves[] = (int) $matiere->id;
        }

        if (!$detacherAbsents) {
            return;
        }

        // On ne compare qu'a CETTE maquette : sans ce scope, enregistrer la
        // composition commune detacherait tout ce qu'un parcours a reserve, et
        // enregistrer celle d'un parcours effacerait la commune.
        $idsActuels = $this->composition->idsDe(
            $ue,
            $parcoursId === CompositionUe::COMMUN ? null : $parcoursId
        );

        if ($parcoursId === CompositionUe::COMMUN) {
            // La cle etrangere ne connait pas les maquettes : ce qu'elle porte
            // appartient a la composition commune.
            $idsActuels = $idsActuels->merge($ue->matieres()->pluck('esbtp_matieres.id'))->unique();
        }

        $aDetacher = $idsActuels->map(fn ($id) => (int) $id)->diff($idsConserves)->values();

        if ($aDetacher->isEmpty()) {
            return;
        }

        // Détacher, jamais supprimer : la matière peut porter des évaluations
        // et des notes. Même comportement que le retrait d'un ECUE isolé.
        $this->composition->retirer($ue, $aDetacher->all(), $parcoursId);
        $this->composition->libererCleEtrangere($ue, $aDetacher->all());
    }

    /**
     * Volumes horaires de chaque ECUE, lus sur la planification académique
     * (source canonique) et complétés par les heures portées par la matière.
     *
     * @return array<int, array{cm:int, td:int, tp:int, total:int, source:string}>
     */
    private function volumesHorairesParEcue($ecues, ?ESBTPAnneeUniversitaire $annee, ?int $filiereId, ?int $niveauId, ?int $semestre): array
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

        if (! $annee || ! $filiereId || ! $semestre || empty($volumes)) {
            return $volumes;
        }

        // Pas de niveau obligatoire dans la recherche : la fiche d'une UE
        // partagee garde le niveau du premier parcours importe. On prefere la
        // ligne du niveau de la fiche quand il en existe plusieurs.
        $planifications = ESBTPPlanificationAcademique::where('annee_universitaire_id', $annee->id)
            ->where('filiere_id', $filiereId)
            ->where('semestre', $semestre)
            ->whereIn('matiere_id', array_keys($volumes))
            ->get()
            ->sortBy(fn ($pl) => (int) $pl->niveau_etude_id === (int) $niveauId ? 1 : 0);

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
     * Supprimer une UE (si aucun résultat attaché, et si elle n'appartient
     * qu'à une seule maquette).
     */
    public function destroy(Request $request, ESBTPUniteEnseignement $ue)
    {
        // Vérifier qu'aucun résultat LMD n'est attaché
        if ($ue->resultatsLMD()->exists()) {
            return $this->refuserSuppressionUe(
                $request,
                'Impossible de supprimer cette UE : des résultats y sont rattachés.'
            );
        }

        // Le code d'une UE étant unique dans l'école, la même unité sert
        // plusieurs parcours. Ce geste-ci la retirait de TOUS d'un coup, sans
        // que rien ne le dise : la garde ci-dessus ne couvrait que le cas où
        // des résultats existaient déjà, donc pas une maquette saisie et pas
        // encore notée — l'état exact d'une maquette en cours de saisie.
        if ($refus = $this->suppressionUe->refusSiPartagee($ue)) {
            return $this->refuserSuppressionUe($request, $refus);
        }

        $this->suppressionUe->supprimer($ue);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'UE supprimée avec succès.']);
        }
        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'Unité d\'Enseignement supprimée avec succès.');
    }

    /**
     * Même refus pour l'appel AJAX de la liste et pour la navigation classique.
     */
    private function refuserSuppressionUe(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return redirect()->route('esbtp.lmd.ue.index')->with('error', $message);
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
            // Absent ou zéro : la composition commune, valable pour toutes les
            // maquettes. Un parcours : cet élément n'appartient qu'à la sienne.
            'parcours_id'     => 'nullable|integer',
        ]);

        // Un parcours qui n'utilise pas cette unité retombe sur « commun » :
        // sinon on créerait une composition rattachée à une maquette qui ignore
        // l'unité, invisible partout et impossible à corriger.
        $portee = $this->composition->porteeValide($ue, $validated['parcours_id'] ?? null);

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE.
        // Le plafond se compte PAR MAQUETTE : additionner deux compositions le
        // ferait dépasser mécaniquement, et plus rien ne pourrait être ajouté.
        if ($error = $this->checkCreditOverflow($ue, $validated['credit_ecue'] ?? null, null, $request, $portee)) {
            return $error;
        }

        $codeLibere = $this->ecritures->ajouter($ue, $portee, $validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => trim('ECUE ajouté avec succès. ' . ($codeLibere ?? '')),
            ]);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', trim('ECUE ajouté avec succès à l\'UE. ' . ($codeLibere ?? '')));
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
            'parcours_id'     => 'nullable|integer',
            // La maquette de la ligne ouverte dans le modal. Quand elle differe
            // de `parcours_id`, l'ecole a change la maquette : on DEPLACE la
            // ligne. Sans cela, passer un element commun en « Reservee a LPV »
            // posait une ligne de plus et laissait la commune — l'element
            // restait visible chez les autres parcours alors que l'ecran
            // affirmait le contraire (USAT, septembre 2026).
            'portee_origine'  => 'nullable|integer',
            // Garder aussi l'ancienne ligne : la surcharge voulue, ou un
            // parcours pose ses propres valeurs sur un element commun.
            'garder_origine'  => 'nullable|boolean',
        ]);

        $portee = $this->composition->porteeValide($ue, $validated['parcours_id'] ?? null);

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE,
        // dans CETTE maquette.
        if ($error = $this->checkCreditOverflow($ue, $validated['credit_ecue'] ?? null, $ecue->id, $request, $portee)) {
            return $error;
        }

        $codeLibere = DB::transaction(function () use ($ue, $ecue, $portee, $validated) {
            $code = $this->ecritures->modifier($ue, $ecue, $portee, $validated);

            if (array_key_exists('portee_origine', $validated) && $validated['portee_origine'] !== null
                && empty($validated['garder_origine'])) {
                // Pas porteeValide() : la ligne ouverte peut etre reservee a un
                // parcours detache depuis de l'unite, et il faut pouvoir la
                // deplacer. retirer() reste borne a CETTE unite.
                $origine = (int) $validated['portee_origine'];
                if ($origine !== $portee) {
                    $this->composition->retirer($ue, [(int) $ecue->id], $origine);
                }
            }

            return $code;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => trim('ECUE mis à jour. ' . ($codeLibere ?? '')),
            ]);
        }

        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', trim('ECUE mis à jour avec succès. ' . ($codeLibere ?? '')));
    }

    /**
     * Détacher un ECUE de l'UE (ne supprime pas la matière).
     */
    public function destroyECUE(Request $request, ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue)
    {
        $portee = $this->composition->porteeValide($ue, $request->input('parcours_id'));

        // Retirer la DERNIERE ligne d'un element le fait sortir du LMD : sa cle
        // etrangere est liberee, il rejoint le catalogue BTS, et on ne peut plus
        // ni le relier ni le recreer sous le meme code. On le dit avant, et on
        // n'agit que sur confirmation explicite.
        if (! $request->boolean('confirmer_sortie') && $this->sortiraitDuLmd($ue, $ecue, $portee)) {
            return response()->json([
                'success' => false,
                'confirmation_requise' => true,
                'message' => sprintf(
                    "« %s » n'est dans aucune autre maquette de cette UE : le retirer le détache du LMD, et il repassera dans les listes de matières BTS. "
                    . "Pour le changer de parcours, utilisez plutôt le crayon. Le retirer quand même ?",
                    $ecue->name ?? $ecue->code
                ),
            ], 409);
        }

        // Retirer de CETTE maquette, et d'elle seule. `detach($id)` supprimait
        // toutes les lignes de cet élément, toutes maquettes confondues : retirer
        // un élément de Bâtiment le retirait aussi de Travaux Publics.
        $retires = $this->composition->retirer($ue, [(int) $ecue->id], $portee);

        // Rien retiré alors que l'élément figure dans une AUTRE maquette de
        // l'unité : on répondait « ECUE détaché » à vide, et l'élément restait.
        // Le refus nomme la maquette qui le tient, pour qu'on sache où aller.
        if ($retires === 0 && ($refus = $this->refusRetraitHorsMaquette($ue, $ecue, $portee))) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $refus], 422);
            }
            return redirect()->route('esbtp.lmd.ue.index')->with('error', $refus);
        }

        // La clé étrangère est globale : on ne la libère que si l'élément ne
        // figure plus dans AUCUNE maquette de cette unité.
        $this->composition->libererCleEtrangere($ue, [(int) $ecue->id]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'ECUE détaché avec succès.']);
        }
        return redirect()->route('esbtp.lmd.ue.index')
            ->with('success', 'ECUE détaché de l\'UE avec succès.');
    }

    /**
     * Vrai si retirer cette ligne fait sortir l'element du LMD.
     *
     * Miroir de CompositionUe::libererCleEtrangere() : la cle etrangere est
     * coupee des qu'il ne reste plus de ligne dans CETTE unite, meme si une
     * autre unite en porte encore. L'element retombe alors dans les listes de
     * matieres BTS (whereNull).
     */
    private function sortiraitDuLmd(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue, int $portee): bool
    {
        // Sans cle sur cette unite, rien n'est libere : un element sans cle du
        // tout est deja dans les listes BTS, la seconde question mentirait.
        return (int) $ecue->unite_enseignement_id === (int) $ue->id
            && DB::table('esbtp_ue_matiere')
                ->where('unite_enseignement_id', $ue->id)
                ->where('matiere_id', $ecue->id)
                ->where('parcours_id', '!=', $portee)
                ->doesntExist();
    }

    /**
     * Pourquoi le retrait n'a rien retiré, quand l'élément tient à l'unité par
     * une autre maquette que celle visée. Null si l'élément n'est dans aucune
     * ligne de pivot : c'est alors un rattachement hérité, par clé étrangère,
     * que l'appelant libère lui-même.
     */
    private function refusRetraitHorsMaquette(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue, int $portee): ?string
    {
        $portees = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->where('matiere_id', $ecue->id)
            ->pluck('parcours_id')
            ->map(fn ($id) => (int) $id);

        if ($portees->isEmpty()) {
            return null;
        }

        $nom = $ecue->name ?? $ecue->code;

        if ($portee === CompositionUe::COMMUN) {
            $noms = ESBTPLMDParcours::whereIn('id', $portees->filter()->all())
                ->pluck('name')
                ->implode(', ');

            return sprintf(
                "« %s » n'est pas dans la composition commune : il est réservé à %s. "
                . 'Filtrez la liste sur ce parcours pour le retirer de sa maquette.',
                $nom,
                $noms !== '' ? $noms : 'une autre maquette'
            );
        }

        return sprintf(
            "« %s » est commun à tous les parcours de l'unité : il ne se retire pas d'une seule maquette. "
            . 'Retirez-le sans filtre de parcours, ou réservez à ce parcours les éléments qui lui sont propres.',
            $nom
        );
    }

    /**
     * Liste des matières disponibles pour rattachement à une UE (non déjà liées).
     */
    public function matieresDisponibles(Request $request, ESBTPUniteEnseignement $ue)
    {
        // La maquette visée. Sans elle, un élément déjà posé en commun sortait de
        // la liste, et il devenait impossible d'en réserver une version propre à
        // un parcours : la fonctionnalité était inatteignable depuis l'écran.
        $portee = $this->composition->porteeValide($ue, $request->input('parcours_id'));
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
            ->whereDoesntHave('unitesEnseignementMultiple', fn ($q) => $q
                ->where('esbtp_ue_matiere.unite_enseignement_id', $ue->id)
                ->where('esbtp_ue_matiere.parcours_id', $portee))
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

        // `detach()` sans argument effaçait TOUS les liens de l'unité avant de
        // les recréer. Trois conséquences, aucune signalée : le crédit propre à
        // une maquette, le caractère optionnel et l'ordre étaient reposés à leur
        // valeur par défaut à chaque enregistrement — donc perdus. Le service de
        // synchronisation ne touche que ce qui change réellement.
        $count = DB::transaction(function () use ($request, $ue) {
            $liens = [];
            foreach ($request->input('parcours', []) as $item) {
                foreach ($item['semestres'] as $sem) {
                    $liens[] = ['parcours_id' => (int) $item['id'], 'semestre' => (int) $sem];
                }
            }

            $this->parcoursUeSync->syncPourUnite($ue, $liens);

            return count($liens);
        });

        return response()->json(['success' => true, 'message' => $count . ' lien(s) parcours-semestre créé(s).']);
    }

    /**
     * Vérifier que l'ajout/modification d'un crédit ECUE ne dépasse pas le crédit de l'UE.
     * Retourne une response d'erreur si dépassement, null sinon.
     *
     * Le budget se compte PAR MAQUETTE, pas sur l'unité entière. Une unité
     * partagée peut porter, pour un même total de crédits, une composition en
     * Bâtiment et une autre en Travaux Publics : les additionner ferait dépasser
     * le plafond mécaniquement, et plus aucun élément ne pourrait être ajouté
     * nulle part. Le refus serait permanent et sans explication utile.
     *
     * Le compte porte sur l'UNION DEDUPLIQUEE de la composition commune et de
     * celle du parcours, un élément une seule fois, la réservée primant. Ne
     * compter que les réservées laisserait réserver à l'infini sur une unité
     * déjà pourvue en commun : le plafond ne mordrait jamais.
     */
    private function checkCreditOverflow(
        ESBTPUniteEnseignement $ue,
        $creditEcue,
        ?int $excludeMatiereId,
        Request $request,
        ?int $parcoursId = null
    ) {
        if (!$ue->credit || !$creditEcue) {
            return null;
        }

        $creditsAutres = $this->composition->creditsDe(
            $ue,
            $parcoursId ?? CompositionUe::COMMUN,
            $excludeMatiereId ? [(int) $excludeMatiereId] : []
        );

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
