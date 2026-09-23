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
use App\Services\LMD\CompositionUe;
use App\Services\LMD\ParcoursUeSyncService;
use App\Services\LMD\SuppressionUeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ESBTPLMDUEController extends Controller
{
    public function __construct(
        private ParcoursUeSyncService $parcoursUeSync,
        private SuppressionUeService $suppressionUe,
        private CompositionUe $composition,
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
                        'matieres_count' => $ue->matieres_count,
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
                                'coefficient' => $e->pivot->coefficient_ecue ?? $e->coefficient_ecue ?? null,
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
    private function synchroniserEcues(
        ESBTPUniteEnseignement $ue,
        array $ecues,
        bool $detacherAbsents,
        int $parcoursId = CompositionUe::COMMUN
    ): void {
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
     * `esbtp_matieres.code` est unique en base, matières supprimées comprises :
     * sans ce contrôle, un code déjà pris remontait en erreur serveur, sans
     * dire à qui il appartient (USAT, septembre 2026 : « Génétique animale »
     * saisie avec le code de « Génétique végétale »).
     *
     * Une matière ACTIVE garde son code : on refuse, en la nommant. Une
     * matière SUPPRIMÉE ne devrait plus rien bloquer : on lui retire le code
     * (suffixé de son id, donc toujours retrouvable dans l'audit et en base)
     * et on rend un message qui dit ce qui a été fait. À appeler dans la même
     * transaction que l'écriture qui réutilise le code.
     *
     * @return string|null Le message à joindre à la réponse si un code a été libéré.
     */
    private function libererCodeOuRefuser(string $code, ?int $saufId = null): ?string
    {
        $existante = ESBTPMatiere::withTrashed()
            ->where('code', $code)
            ->when($saufId, fn ($q) => $q->where('id', '!=', $saufId))
            ->first();

        if (! $existante) {
            return null;
        }

        if (! $existante->trashed()) {
            throw ValidationException::withMessages(['code' => sprintf(
                'Le code « %s » est déjà celui de la matière « %s ». Choisissez un autre code, ou utilisez l\'onglet « Lier un existant » s\'il s\'agit bien de la même matière.',
                $existante->code,
                $existante->name
            )]);
        }

        $codeArchive = $code . '~suppr-' . $existante->id;
        $existante->code = $codeArchive;
        $existante->save();

        return sprintf(
            'Le code « %s » était encore réservé par « %s », supprimée le %s. Il a été libéré : l\'ancienne matière reste archivée sous « %s ».',
            $code,
            $existante->name,
            $existante->deleted_at->format('d/m/Y'),
            $codeArchive
        );
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
        // Le service porte la garde : elle doit tester la composition COMMUNE et
        // non l'existence de n'importe quelle ligne, sinon un seul élément
        // réservé rendrait cette matérialisation impossible — et laisserait
        // l'unité exposée au dépouillement qu'elle prévient.
        $this->composition->materialiserDepuisCleEtrangere($uniteEnseignementId);
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

        $coeffEcue = $validated['coefficient_ecue'] ?? null;
        $creditEcue = $validated['credit_ecue'] ?? null;
        $ordreBulletin = $validated['ordre_bulletin'] ?? 0;
        // Un parcours qui n'utilise pas cette unité retombe sur « commun » :
        // sinon on créerait une composition rattachée à une maquette qui ignore
        // l'unité, invisible partout et impossible à corriger.
        $portee = $this->composition->porteeValide($ue, $validated['parcours_id'] ?? null);

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE.
        // Le plafond se compte PAR MAQUETTE : additionner deux compositions le
        // ferait dépasser mécaniquement, et plus rien ne pourrait être ajouté.
        if ($error = $this->checkCreditOverflow($ue, $creditEcue, null, $request, $portee)) {
            return $error;
        }

        $codeLibere = null;

        if (!empty($validated['matiere_id'])) {
            $matiere = ESBTPMatiere::findOrFail($validated['matiere_id']);

            // Cette route valide en ligne, elle ne passe pas par
            // UniteEnseignementRequest : la garde anti-absorption BTS doit être
            // rejouée ici, sinon un clic dans « Lier une matière existante »
            // sortirait une matière BTS de tous les sélecteurs BTS.
            $this->refuserAbsorptionMatiereBts($matiere);
        } else {
            // Créer une nouvelle matière. Libérer le code d'une matière
            // supprimée et créer se font ensemble, ou pas du tout.
            [$matiere, $codeLibere] = DB::transaction(function () use ($validated, $ue, $creditEcue, $coeffEcue, $ordreBulletin) {
                $codeLibere = $this->libererCodeOuRefuser($validated['code']);

                return [ESBTPMatiere::create([
                    'name'                  => $validated['name'],
                    'code'                  => $validated['code'],
                    'unite_enseignement_id' => $ue->id, // FK direct (rétro-compat)
                    'credit_ecue'           => $creditEcue,
                    'coefficient_ecue'      => $coeffEcue,
                    'ordre_bulletin'        => $ordreBulletin,
                    'is_active'             => true,
                    'created_by'            => auth()->id(),
                    'updated_by'            => auth()->id(),
                ]), $codeLibere];
            });
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

        // Écrire dans le pivot, pour LA maquette visée. `syncWithoutDetaching`
        // se cale sur le seul `matiere_id` : poser un élément commun sur une
        // unité qui en a déjà une version réservée réécrirait cette réservation.
        $this->composition->poser($ue, (int) $matiere->id, [
            'coefficient_ecue' => $coeffEcue,
            'credit_ecue' => $creditEcue,
            'ordre_bulletin' => $ordreBulletin,
        ], $portee);

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
        ]);

        $portee = $this->composition->porteeValide($ue, $validated['parcours_id'] ?? null);

        // Vérifier que la somme des crédits ECUE ne dépasse pas le crédit de l'UE,
        // dans CETTE maquette.
        if ($error = $this->checkCreditOverflow($ue, $validated['credit_ecue'] ?? null, $ecue->id, $request, $portee)) {
            return $error;
        }

        // Mettre à jour la matière en un seul UPDATE (nom, code, coeff, credit, ordre).
        // Libérer le code d'une matière supprimée et l'écrire ici se font ensemble.
        $codeLibere = DB::transaction(function () use ($validated, $ecue) {
            $codeLibere = isset($validated['code'])
                ? $this->libererCodeOuRefuser($validated['code'], (int) $ecue->id)
                : null;

            $ecue->update([
                'name' => $validated['name'] ?? $ecue->name,
                'code' => $validated['code'] ?? $ecue->code,
                'coefficient_ecue' => $validated['coefficient_ecue'] ?? $ecue->coefficient_ecue,
                'credit_ecue' => $validated['credit_ecue'] ?? $ecue->credit_ecue,
                'ordre_bulletin' => $validated['ordre_bulletin'] ?? $ecue->ordre_bulletin,
                'updated_by' => auth()->id(),
            ]);

            return $codeLibere;
        });

        // Mettre à jour le pivot de CETTE maquette. Sans la portée, modifier le
        // coefficient commun réécrivait la ligne qu'un parcours avait surchargée,
        // et sa maquette changeait sans que personne l'ait demandé.
        $this->composition->poser($ue, (int) $ecue->id, [
            'coefficient_ecue' => $validated['coefficient_ecue'] ?? null,
            'credit_ecue' => $validated['credit_ecue'] ?? null,
            'ordre_bulletin' => $validated['ordre_bulletin'] ?? 0,
        ], $portee);

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
