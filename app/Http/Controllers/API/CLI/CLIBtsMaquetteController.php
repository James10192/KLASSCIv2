<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\BtsTroncCommun\BtsMaquette;
use App\Domain\BtsTroncCommun\ChargementDeMaquette;
use App\Domain\BtsTroncCommun\ResolutionDeMatiere;
use App\Domain\BtsTroncCommun\RetraitDeMaquette;
use App\Domain\BtsTroncCommun\SemestreDeMaquette;
use App\Http\Controllers\Controller;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * La maquette BTS d'un couple filiere x niveau : la lire, la charger, en
 * retirer une matiere.
 *
 * L'ecran /esbtp/matieres/classification sait poser une place et un semestre,
 * mais seulement sur une liaison qui EXISTE deja. Or l'ecole arrive avec un
 * bulletin papier dont la moitie des matieres n'est rattachee a rien.
 * Rattacher a la main se fait une fois, matiere par matiere, et ne se rejoue
 * sur aucune autre instance.
 *
 * CE CONTROLEUR N'ORCHESTRE QUE. Il authentifie, valide, resout la filiere et
 * le niveau, appelle le domaine et met en forme la reponse. La resolution des
 * libelles, les conflits de semestre et les ecritures vivent dans
 * `App\Domain\BtsTroncCommun` : `charger()` y faisait deux cents lignes, et
 * une regle enfouie dans un controleur n'est ni relisible ni testable sans
 * base.
 *
 * @see ChargementDeMaquette
 * @see RetraitDeMaquette
 * @see ResolutionDeMatiere
 * @see docs/api/CLI_BTS_MAQUETTE.md
 */
class CLIBtsMaquetteController extends Controller
{
    public function __construct(private readonly ResolutionDeMatiere $resolution) {}

    public function charger(Request $request, ChargementDeMaquette $chargement): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return response()->json(['success' => false, 'message' => 'Token missing cli:admin ability'], 403);
        }

        $valide = $request->validate([
            'filiere' => 'required',
            'niveau' => 'required',
            // « les_deux » manquait, et c'est ce qui rendait l'endpoint
            // incapable de tenir deux maquettes : une matiere enseignee aux
            // deux semestres ne pouvait pas se declarer comme telle.
            'semestre' => ['required', Rule::in([1, 2, '1', '2', SemestreDeMaquette::MOT_LES_DEUX])],
            'matieres' => 'required|array|min:1',
            'matieres.*' => 'required',
            // Le semestre se pose aussi ligne par ligne, et c'est ainsi qu'on
            // tranche un conflit sans que le code ait a deviner.
            'matieres.*.semestre' => ['sometimes', 'nullable', Rule::in([1, 2, '1', '2', SemestreDeMaquette::MOT_LES_DEUX])],
            'valider' => 'sometimes|boolean',
            // Une ecriture ne part jamais sans avoir ete demandee : le defaut
            // est la simulation, pour qu'on lise ce qui sera fait avant.
            'appliquer' => 'sometimes|boolean',
        ]);

        $couple = $this->resoudreLeCouple($valide);
        if ($couple instanceof JsonResponse) {
            return $couple;
        }
        [$filiere, $niveau] = $couple;

        $valider = (bool) ($valide['valider'] ?? false);
        $semestre = SemestreDeMaquette::depuisLaSaisie($valide['semestre']);

        $plan = $chargement->preparer($filiere, $niveau, $semestre, $valide['matieres']);

        if ($refus = $this->refusDeChargement($filiere, $niveau, $plan)) {
            return $refus;
        }

        if (! (bool) ($valide['appliquer'] ?? false)) {
            return response()->json([
                'success' => true,
                'message' => 'Simulation : rien n a ete ecrit. Renvoyez avec appliquer=true pour ecrire.',
                'data' => $this->rapport($filiere, $niveau, $semestre, $valider, $plan['lignes'], false),
            ]);
        }

        $chargement->appliquer($filiere, $niveau, $plan['lignes'], $valider);

        return response()->json([
            'success' => true,
            'message' => 'Maquette chargee pour '.count($plan['lignes']).' matiere(s).',
            'data' => $this->rapport($filiere, $niveau, $semestre, $valider, $plan['lignes'], true),
        ]);
    }

    /**
     * Ce que porte la maquette d'un couple, telle quelle.
     *
     * Il n'existait aucune facon de la relire : on ne pouvait en avoir le
     * contenu qu'en detournant la simulation du chargement, ce qui obligeait a
     * connaitre d'avance la liste des matieres qu'on cherchait justement a
     * decouvrir.
     */
    public function lire(Request $request, BtsMaquette $maquette): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return response()->json(['success' => false, 'message' => 'Token missing cli:read ability'], 403);
        }

        $valide = $request->validate(['filiere' => 'required', 'niveau' => 'required']);

        $couple = $this->resoudreLeCouple($valide);
        if ($couple instanceof JsonResponse) {
            return $couple;
        }
        [$filiere, $niveau] = $couple;

        $lignes = $this->lignesDeLaMaquette($filiere, $niveau);

        return response()->json([
            'success' => true,
            'message' => 'Maquette de '.$filiere->name.' / '.$niveau->name.'.',
            'data' => [
                'filiere' => $filiere->name,
                'filiere_id' => $filiere->id,
                'niveau' => $niveau->name,
                'niveau_id' => $niveau->id,
                // Tant que ce drapeau est faux, le bulletin ignore les
                // semestres et rend la liste entiere : c'est la premiere chose
                // a regarder quand une matiere apparait la ou on ne l'attend pas.
                //
                // ⚠ Il est au grain du COUPLE (« au moins une ligne validee »),
                // alors que la regle effective est au grain de la LIGNE. Sur un
                // couple mixte, le lire seul fait croire que les semestres
                // bruts s'appliquent partout : c'est `semestre_effectif`, ligne
                // par ligne, qui dit ce que le bulletin retient.
                'semestres_renseignes' => $maquette->isRenseignee($filiere->id, $niveau->id),
                // Les totaux comptent le semestre EFFECTIF, pas la colonne :
                // une ligne non validee compte dans « les deux », comme au
                // bulletin. Assis sur la colonne brute, ils contredisaient
                // le document qu'ils sont censes decrire.
                'totaux' => [
                    'matieres' => $lignes->count(),
                    'semestre_1' => $lignes->where('semestre_effectif', 1)->count(),
                    'semestre_2' => $lignes->where('semestre_effectif', 2)->count(),
                    'les_deux' => $lignes->whereNull('semestre_effectif')->count(),
                ],
                'matieres' => $lignes->all(),
            ],
        ]);
    }

    /**
     * Retire des matieres de la maquette d'un couple.
     *
     * Le retrait n'existait nulle part : ni ici, ni dans l'ecran Maquette, qui
     * ne sait que modifier une ligne existante. Le seul chemin passait par le
     * modal des liaisons d'une matiere, qui supprime TOUTES ses liaisons puis
     * les recree, et perd au passage la place et le semestre des couples
     * qu'on voulait garder.
     *
     * Par defaut il simule. Et il refuse de retirer une matiere qui porte des
     * notes sur ce couple, parce que la note resterait en base sans plus
     * apparaitre nulle part.
     */
    public function retirer(Request $request, RetraitDeMaquette $retrait): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return response()->json(['success' => false, 'message' => 'Token missing cli:admin ability'], 403);
        }

        $valide = $request->validate([
            'filiere' => 'required',
            'niveau' => 'required',
            'matieres' => 'required|array|min:1',
            'matieres.*' => 'required',
            'appliquer' => 'sometimes|boolean',
            // Retirer une matiere notee est parfois voulu (une matiere posee
            // par erreur sur laquelle une note d'essai traine). Il faut alors
            // le dire, et la reponse nomme ce qu'on perd de vue.
            'malgre_les_notes' => 'sometimes|boolean',
        ]);

        $couple = $this->resoudreLeCouple($valide);
        if ($couple instanceof JsonResponse) {
            return $couple;
        }
        [$filiere, $niveau] = $couple;

        $plan = $retrait->preparer($filiere, $niveau, $valide['matieres']);

        if ($refus = $this->refusDeRetrait($plan, (bool) ($valide['malgre_les_notes'] ?? false))) {
            return $refus;
        }

        if (! (bool) ($valide['appliquer'] ?? false)) {
            return response()->json([
                'success' => true,
                'message' => 'Simulation : rien n a ete retire. Renvoyez avec appliquer=true pour ecrire.',
                'data' => $this->rapportDeRetrait($filiere, $niveau, $plan['lignes'], false),
            ]);
        }

        $ecrit = $retrait->appliquer($filiere, $niveau, $plan['lignes']);

        return response()->json([
            'success' => true,
            'message' => $ecrit['retirees'].' matiere(s) retiree(s) de la maquette sur '
                .count($ecrit['lignes']).' demandee(s).',
            'data' => $this->rapportDeRetrait($filiere, $niveau, $ecrit['lignes'], true),
        ]);
    }

    /**
     * La filiere et le niveau, ou la reponse 404 qui dit lequel manque.
     *
     * Les trois endpoints commencent par la meme paire ; la recopier trois
     * fois faisait trois occasions d'oublier un des deux controles.
     *
     * @param  array<string, mixed>  $valide
     * @return array{0: ESBTPFiliere, 1: ESBTPNiveauEtude}|JsonResponse
     */
    private function resoudreLeCouple(array $valide): array|JsonResponse
    {
        $filiere = $this->resolution->filiere($valide['filiere']);
        if (! $filiere) {
            return response()->json(['success' => false, 'message' => "Filiere introuvable : {$valide['filiere']}"], 404);
        }

        $niveau = $this->resolution->niveau($valide['niveau']);
        if (! $niveau) {
            return response()->json(['success' => false, 'message' => "Niveau introuvable : {$valide['niveau']}"], 404);
        }

        return [$filiere, $niveau];
    }

    /**
     * Les deux raisons de refuser un chargement entier, ou `null`.
     *
     * @param  array<string, mixed>  $plan
     */
    private function refusDeChargement(ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, array $plan): ?JsonResponse
    {
        // Un chargement partiel poserait une maquette trouee, et « maquette
        // renseignee » vaudrait alors pour un bulletin incomplet.
        if ($plan['ambigus'] !== [] || $plan['introuvables'] !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Des libelles ne designent pas une matiere unique : rien n a ete ecrit.',
                'data' => [
                    'filiere' => $filiere->name,
                    'niveau' => $niveau->name,
                    'ambigus' => $plan['ambigus'],
                    'introuvables' => $plan['introuvables'],
                    'resolus' => count($plan['lignes']),
                ],
            ], 422);
        }

        // « Deja au semestre 2, chargee au semestre 1 » veut dire « elle est
        // aux deux » aussi souvent que « elle a change de semestre », et les
        // deux ne donnent pas le meme bulletin. On rend la main a l'appelant.
        if ($plan['conflits'] !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Des matieres sont deja declarees a un autre semestre : rien n a ete ecrit.',
                'data' => [
                    'filiere' => $filiere->name,
                    'niveau' => $niveau->name,
                    'conflits' => $plan['conflits'],
                    'resolus' => count($plan['lignes']),
                    'comment_trancher' => 'Posez le semestre sur la ligne : {"nom": "...", "semestre": 1 | 2 | "'
                        .SemestreDeMaquette::MOT_LES_DEUX.'"}.',
                ],
            ], 422);
        }

        return null;
    }

    /** @param  array<string, mixed>  $plan */
    private function refusDeRetrait(array $plan, bool $malgreLesNotes): ?JsonResponse
    {
        if ($plan['ambigus'] !== [] || $plan['introuvables'] !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Des libelles ne designent pas une matiere unique : rien n a ete retire.',
                'data' => ['ambigus' => $plan['ambigus'], 'introuvables' => $plan['introuvables']],
            ], 422);
        }

        if ($plan['notees'] !== [] && ! $malgreLesNotes) {
            return response()->json([
                'success' => false,
                'message' => 'Des matieres portent des evaluations sur ce couple : rien n a ete retire.',
                'data' => [
                    'notees' => $plan['notees'],
                    'comment_passer_outre' => 'Renvoyez avec malgre_les_notes=true si le retrait est bien voulu.',
                ],
            ], 422);
        }

        return null;
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function lignesDeLaMaquette(ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau)
    {
        $places = ESBTPMaquettePlaceSemestre::query()
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->get(['matiere_id', 'semestre', 'ordre_bulletin'])
            ->groupBy('matiere_id');

        return ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->with('matiere:id,name,code,is_active')
            ->get()
            ->map(fn (ESBTPMatiereFilierNiveau $ligne) => [
                'matiere_id' => $ligne->matiere_id,
                'matiere' => $ligne->matiere?->name,
                'code' => $ligne->matiere?->code,
                'active' => (bool) $ligne->matiere?->is_active,
                // DEUX champs, et ils peuvent differer. `semestre` est la
                // colonne brute, utile au diagnostic ; `semestre_effectif` est
                // ce que le BULLETIN retient, via la normalisation unique de
                // `SemestreDeMaquette::declarationEffective()`. Une ligne non
                // validee porte souvent un semestre — `ChargementDeMaquette`
                // en pose un sans valider — et vaut pourtant « les deux ».
                // Lire la colonne brute faisait annoncer « semestre 2 » a un
                // outil de diagnostic pour des matieres que le bulletin sort
                // aux DEUX semestres, sur le cas fondateur meme du chantier
                // (Batiment 2e annee, dix matieres figees au semestre 2).
                'semestre' => $ligne->semestre,
                'semestre_effectif' => SemestreDeMaquette::declarationEffective(
                    $ligne->semestre,
                    (bool) $ligne->semestre_renseigne
                ),
                'semestre_libelle' => SemestreDeMaquette::libelle(
                    SemestreDeMaquette::declarationEffective(
                        $ligne->semestre,
                        (bool) $ligne->semestre_renseigne
                    )
                ),
                'semestre_renseigne' => (bool) $ligne->semestre_renseigne,
                'classification' => $ligne->classification,
                'ordre_bulletin' => $ligne->ordre_bulletin,
                'places_par_semestre' => ($places[$ligne->matiere_id] ?? collect())
                    ->pluck('ordre_bulletin', 'semestre')
                    ->all(),
            ])
            ->sortBy([['ordre_bulletin', 'asc'], ['matiere', 'asc']])
            ->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $lignes
     * @return array<string, mixed>
     */
    private function rapportDeRetrait(ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, array $lignes, bool $ecrit): array
    {
        return [
            'filiere' => $filiere->name,
            'filiere_id' => $filiere->id,
            'niveau' => $niveau->name,
            'niveau_id' => $niveau->id,
            'ecrit' => $ecrit,
            'absentes_de_la_maquette' => count(array_filter($lignes, fn ($l) => ! $l['dans_la_maquette'])),
            'lignes' => $lignes,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lignes
     * @return array<string, mixed>
     */
    private function rapport(ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, ?int $semestre, bool $valider, array $lignes, bool $ecrit): array
    {
        return [
            'filiere' => $filiere->name,
            'filiere_id' => $filiere->id,
            'niveau' => $niveau->name,
            'niveau_id' => $niveau->id,
            'semestre' => $semestre,
            'semestre_libelle' => SemestreDeMaquette::libelle($semestre),
            'semestres_valides' => $valider,
            'ecrit' => $ecrit,
            'liaisons_a_creer' => count(array_filter($lignes, fn ($l) => $l['liaison'] === 'a_creer')),
            'liaisons_existantes' => count(array_filter($lignes, fn ($l) => $l['liaison'] === 'existante')),
            'lignes' => $lignes,
        ];
    }
}
