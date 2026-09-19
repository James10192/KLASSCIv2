<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\BtsTroncCommun\BtsMaquette;
use App\Domain\BtsTroncCommun\LiaisonsDeMatiere;
use App\Domain\BtsTroncCommun\SemestreDeMaquette;
use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Charger la maquette d'un couple filiere x niveau depuis le bulletin officiel
 * de l'ecole.
 *
 * L'ecran /esbtp/matieres/classification sait poser une place et un semestre,
 * mais seulement sur une liaison qui EXISTE deja : son enregistrement fait un
 * update, jamais un insert. Or l'ecole arrive avec un bulletin papier dont la
 * moitie des matieres n'est rattachee a rien. Rattacher a la main se fait une
 * fois, matiere par matiere, et ne se rejoue sur aucune autre instance.
 *
 * Cet endpoint fait les deux d'un coup et se rejoue : il cree la liaison
 * manquante, pose la place au bulletin et le semestre, et ne valide la maquette
 * que si on le lui demande.
 *
 * Il ne DEVINE jamais quelle matiere designe un libelle. Le catalogue d'Abidjan
 * compte quatre « Anglais » et quatre « Hydraulique appliquee » : resoudre au
 * plus proche poserait un bulletin faux que personne ne saurait relire. Un
 * libelle ambigu est refuse, avec ses candidats.
 */
class CLIBtsMaquetteController extends Controller
{
    public function charger(Request $request, LiaisonsDeMatiere $liaisons): JsonResponse
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

        $filiere = $this->resoudreFiliere($valide['filiere']);
        if (! $filiere) {
            return response()->json(['success' => false, 'message' => "Filiere introuvable : {$valide['filiere']}"], 404);
        }

        $niveau = $this->resoudreNiveau($valide['niveau']);
        if (! $niveau) {
            return response()->json(['success' => false, 'message' => "Niveau introuvable : {$valide['niveau']}"], 404);
        }

        $appliquer = (bool) ($valide['appliquer'] ?? false);
        $valider = (bool) ($valide['valider'] ?? false);
        $semestre = SemestreDeMaquette::depuisLaSaisie($valide['semestre']);

        $lignes = [];
        $ambigus = [];
        $introuvables = [];
        $conflits = [];

        foreach (array_values($valide['matieres']) as $index => $entree) {
            $place = $index + 1;
            $resolution = $this->resoudreMatiere($entree, $filiere->id, $niveau->id);

            if ($resolution['statut'] === 'ambigu') {
                $ambigus[] = ['libelle' => $resolution['libelle'], 'place' => $place, 'candidats' => $resolution['candidats']];
                continue;
            }
            if ($resolution['statut'] === 'introuvable') {
                $introuvables[] = ['libelle' => $resolution['libelle'], 'place' => $place];
                continue;
            }

            $matiere = $resolution['matiere'];
            $existante = ESBTPMatiereFilierNiveau::query()
                ->where('filiere_id', $filiere->id)
                ->where('niveau_etude_id', $niveau->id)
                ->where('matiere_id', $matiere->id)
                ->first();

            // Le semestre pose sur la ligne prime sur celui du lot : c'est la
            // seule facon de charger une maquette dont une matiere est aux
            // deux semestres quand les autres n'y sont qu'a un.
            $semestrePoseSurLaLigne = is_array($entree) && array_key_exists('semestre', $entree);
            $semestreVoulu = $semestrePoseSurLaLigne
                ? SemestreDeMaquette::depuisLaSaisie($entree['semestre'])
                : $semestre;

            if (! $semestrePoseSurLaLigne && SemestreDeMaquette::estUnConflit(
                $existante?->semestre,
                (bool) $existante?->semestre_renseigne,
                $semestreVoulu,
            )) {
                $conflits[] = [
                    'matiere_id' => $matiere->id,
                    'matiere' => $matiere->name,
                    'place' => $place,
                    'declare' => SemestreDeMaquette::libelle($existante?->semestre),
                    'demande' => SemestreDeMaquette::libelle($semestreVoulu),
                ];

                continue;
            }

            $placesSemestre = ESBTPMaquettePlaceSemestre::query()
                ->where('filiere_id', $filiere->id)
                ->where('niveau_etude_id', $niveau->id)
                ->where('matiere_id', $matiere->id)
                ->pluck('ordre_bulletin', 'semestre')
                ->all();

            $lignes[] = [
                'place' => $place,
                'matiere_id' => $matiere->id,
                'matiere' => $matiere->name,
                'liaison' => $existante ? 'existante' : 'a_creer',
                'ordre_avant' => $existante?->ordre_bulletin,
                'semestre_avant' => $existante?->semestre,
                'semestre_apres' => $semestreVoulu,
                'semestre_libelle' => SemestreDeMaquette::libelle($semestreVoulu),
                'places_semestre_avant' => $placesSemestre,
            ];
        }

        // Un chargement partiel poserait une maquette trouee, et « maquette
        // renseignee » vaudrait alors pour un bulletin incomplet. On refuse
        // l'ensemble tant qu'un libelle n'est pas resolu.
        if ($ambigus !== [] || $introuvables !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Des libelles ne designent pas une matiere unique : rien n a ete ecrit.',
                'data' => [
                    'filiere' => $filiere->name,
                    'niveau' => $niveau->name,
                    'ambigus' => $ambigus,
                    'introuvables' => $introuvables,
                    'resolus' => count($lignes),
                ],
            ], 422);
        }

        // Un chargement qui contredit un semestre deja valide ne se devine
        // pas : « deja au semestre 2, chargee au semestre 1 » veut dire « elle
        // est aux deux » aussi souvent que « elle a change de semestre », et
        // les deux ne donnent pas le meme bulletin. On refuse le lot entier et
        // on rend la main a l'appelant, qui tranche ligne par ligne.
        if ($conflits !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Des matieres sont deja declarees a un autre semestre : rien n a ete ecrit.',
                'data' => [
                    'filiere' => $filiere->name,
                    'niveau' => $niveau->name,
                    'conflits' => $conflits,
                    'resolus' => count($lignes),
                    'comment_trancher' => 'Posez le semestre sur la ligne : {"nom": "...", "semestre": 1 | 2 | "'
                        .SemestreDeMaquette::MOT_LES_DEUX.'"}.',
                ],
            ], 422);
        }

        if (! $appliquer) {
            return response()->json([
                'success' => true,
                'message' => 'Simulation : rien n a ete ecrit. Renvoyez avec appliquer=true pour ecrire.',
                'data' => $this->rapport($filiere, $niveau, $semestre, $valider, $lignes, false),
            ]);
        }

        DB::transaction(function () use ($lignes, $filiere, $niveau, $valider, $liaisons): void {
            foreach ($lignes as $ligne) {
                // Cree la liaison si elle manque, et ajoute le couple aux
                // pivots plats sans jamais en retirer.
                $liaisons->poser((int) $ligne['matiere_id'], (int) $filiere->id, (int) $niveau->id);

                $attributs = [
                    'ordre_bulletin' => $ligne['place'],
                    'semestre' => $ligne['semestre_apres'],
                ];
                if ($valider) {
                    $attributs['semestre_renseigne'] = true;
                }

                ESBTPMatiereFilierNiveau::updateOrCreate(
                    [
                        'filiere_id' => $filiere->id,
                        'niveau_etude_id' => $niveau->id,
                        'matiere_id' => $ligne['matiere_id'],
                    ],
                    $attributs
                );

                // La place PAR SEMESTRE, qui seule sait qu'une matiere
                // enseignee aux deux semestres n'y occupe pas le meme rang. Le
                // pivot, unique sur (matiere, filiere, niveau), ne peut en
                // retenir qu'une : charger le second semestre ecrasait le
                // premier.
                foreach (SemestreDeMaquette::semestresCouverts($ligne['semestre_apres']) as $semestreCouvert) {
                    ESBTPMaquettePlaceSemestre::updateOrCreate(
                        [
                            'filiere_id' => $filiere->id,
                            'niveau_etude_id' => $niveau->id,
                            'semestre' => $semestreCouvert,
                            'matiere_id' => $ligne['matiere_id'],
                        ],
                        ['ordre_bulletin' => $ligne['place']]
                    );
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Maquette chargee pour '.count($lignes).' matiere(s).',
            'data' => $this->rapport($filiere, $niveau, $semestre, $valider, $lignes, true),
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

        $valide = $request->validate([
            'filiere' => 'required',
            'niveau' => 'required',
        ]);

        $filiere = $this->resoudreFiliere($valide['filiere']);
        if (! $filiere) {
            return response()->json(['success' => false, 'message' => "Filiere introuvable : {$valide['filiere']}"], 404);
        }

        $niveau = $this->resoudreNiveau($valide['niveau']);
        if (! $niveau) {
            return response()->json(['success' => false, 'message' => "Niveau introuvable : {$valide['niveau']}"], 404);
        }

        $places = ESBTPMaquettePlaceSemestre::query()
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->get(['matiere_id', 'semestre', 'ordre_bulletin'])
            ->groupBy('matiere_id');

        $lignes = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->with('matiere:id,name,code,is_active')
            ->get()
            ->map(fn (ESBTPMatiereFilierNiveau $ligne) => [
                'matiere_id' => $ligne->matiere_id,
                'matiere' => $ligne->matiere?->name,
                'code' => $ligne->matiere?->code,
                'active' => (bool) $ligne->matiere?->is_active,
                'semestre' => $ligne->semestre,
                'semestre_libelle' => SemestreDeMaquette::libelle($ligne->semestre),
                'semestre_renseigne' => (bool) $ligne->semestre_renseigne,
                'classification' => $ligne->classification,
                'ordre_bulletin' => $ligne->ordre_bulletin,
                'places_par_semestre' => ($places[$ligne->matiere_id] ?? collect())
                    ->pluck('ordre_bulletin', 'semestre')
                    ->all(),
            ])
            ->sortBy([['ordre_bulletin', 'asc'], ['matiere', 'asc']])
            ->values();

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
                'semestres_renseignes' => $maquette->isRenseignee($filiere->id, $niveau->id),
                'totaux' => [
                    'matieres' => $lignes->count(),
                    'semestre_1' => $lignes->where('semestre', 1)->count(),
                    'semestre_2' => $lignes->where('semestre', 2)->count(),
                    'les_deux' => $lignes->whereNull('semestre')->count(),
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
    public function retirer(Request $request, LiaisonsDeMatiere $liaisons): JsonResponse
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

        $filiere = $this->resoudreFiliere($valide['filiere']);
        if (! $filiere) {
            return response()->json(['success' => false, 'message' => "Filiere introuvable : {$valide['filiere']}"], 404);
        }

        $niveau = $this->resoudreNiveau($valide['niveau']);
        if (! $niveau) {
            return response()->json(['success' => false, 'message' => "Niveau introuvable : {$valide['niveau']}"], 404);
        }

        $appliquer = (bool) ($valide['appliquer'] ?? false);
        $malgreLesNotes = (bool) ($valide['malgre_les_notes'] ?? false);

        $classeIds = ESBTPClasse::query()
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->pluck('id');

        $lignes = [];
        $ambigus = [];
        $introuvables = [];
        $notees = [];

        foreach (array_values($valide['matieres']) as $entree) {
            $resolution = $this->resoudreMatiere($entree, $filiere->id, $niveau->id);

            if ($resolution['statut'] === 'ambigu') {
                $ambigus[] = ['libelle' => $resolution['libelle'], 'candidats' => $resolution['candidats']];

                continue;
            }
            if ($resolution['statut'] === 'introuvable') {
                $introuvables[] = ['libelle' => $resolution['libelle']];

                continue;
            }

            $matiere = $resolution['matiere'];
            $existante = ESBTPMatiereFilierNiveau::query()
                ->where('filiere_id', $filiere->id)
                ->where('niveau_etude_id', $niveau->id)
                ->where('matiere_id', $matiere->id)
                ->exists();

            $evaluations = $classeIds->isEmpty() ? 0 : ESBTPEvaluation::query()
                ->where('matiere_id', $matiere->id)
                ->whereIn('classe_id', $classeIds)
                ->count();

            if ($evaluations > 0) {
                $notees[] = [
                    'matiere_id' => $matiere->id,
                    'matiere' => $matiere->name,
                    'evaluations' => $evaluations,
                ];
            }

            $lignes[] = [
                'matiere_id' => $matiere->id,
                'matiere' => $matiere->name,
                'code' => $matiere->code,
                'dans_la_maquette' => $existante,
                'evaluations_sur_ce_couple' => $evaluations,
            ];
        }

        if ($ambigus !== [] || $introuvables !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Des libelles ne designent pas une matiere unique : rien n a ete retire.',
                'data' => ['ambigus' => $ambigus, 'introuvables' => $introuvables],
            ], 422);
        }

        if ($notees !== [] && ! $malgreLesNotes) {
            return response()->json([
                'success' => false,
                'message' => 'Des matieres portent des evaluations sur ce couple : rien n a ete retire.',
                'data' => [
                    'notees' => $notees,
                    'comment_passer_outre' => 'Renvoyez avec malgre_les_notes=true si le retrait est bien voulu.',
                ],
            ], 422);
        }

        if (! $appliquer) {
            return response()->json([
                'success' => true,
                'message' => 'Simulation : rien n a ete retire. Renvoyez avec appliquer=true pour ecrire.',
                'data' => $this->rapportDeRetrait($filiere, $niveau, $lignes, false),
            ]);
        }

        $retirees = 0;

        foreach ($lignes as $index => $ligne) {
            // Une matiere absente de la maquette n'a rien a perdre : la
            // compter comme retiree ferait dire « 3 matiere(s) retiree(s) »
            // pour un lot dont une seule etait la.
            if (! $ligne['dans_la_maquette']) {
                $lignes[$index]['retire'] = ['canonique' => 0, 'places_semestre' => 0];

                continue;
            }

            $lignes[$index]['retire'] = $liaisons->retirer(
                (int) $ligne['matiere_id'],
                (int) $filiere->id,
                (int) $niveau->id,
            );
            $retirees++;
        }

        return response()->json([
            'success' => true,
            'message' => $retirees.' matiere(s) retiree(s) de la maquette sur '.count($lignes).' demandee(s).',
            'data' => $this->rapportDeRetrait($filiere, $niveau, $lignes, true),
        ]);
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

    private function resoudreFiliere(mixed $cle): ?ESBTPFiliere
    {
        if (is_numeric($cle)) {
            return ESBTPFiliere::find((int) $cle);
        }

        return ESBTPFiliere::where('code', $cle)->first() ?? ESBTPFiliere::where('name', $cle)->first();
    }

    private function resoudreNiveau(mixed $cle): ?ESBTPNiveauEtude
    {
        if (is_numeric($cle)) {
            return ESBTPNiveauEtude::find((int) $cle);
        }

        return ESBTPNiveauEtude::where('name', $cle)->first();
    }

    /**
     * Un identifiant est pris tel quel ; un libelle doit designer UNE matiere
     * BTS et une seule.
     *
     * @return array{statut: string, libelle: string, matiere?: ESBTPMatiere, candidats?: array<int, array<string, mixed>>}
     */
    private function resoudreMatiere(mixed $entree, int $filiereId, int $niveauId): array
    {
        $libelle = is_array($entree) ? (string) ($entree['nom'] ?? $entree['id'] ?? '') : (string) $entree;

        $id = is_array($entree) ? ($entree['id'] ?? null) : (is_numeric($entree) ? $entree : null);
        if ($id !== null) {
            // Meme garde BTS que la resolution par libelle, plus bas : une
            // ECUE LMD passee par son identifiant entrerait sinon dans la
            // maquette BTS, ou rien ne sait la lire.
            $matiere = ESBTPMatiere::query()
                ->whereNull('unite_enseignement_id')
                ->find((int) $id);

            return $matiere
                ? ['statut' => 'ok', 'libelle' => $matiere->name, 'matiere' => $matiere]
                : ['statut' => 'introuvable', 'libelle' => (string) $id];
        }

        $cible = $this->normaliser($libelle);
        $candidats = ESBTPMatiere::query()
            ->whereNull('unite_enseignement_id') // BTS : une ECUE LMD n a rien a faire ici
            ->get(['id', 'name', 'code'])
            ->filter(fn ($m) => $this->normaliser($m->name) === $cible)
            ->values();

        if ($candidats->isEmpty()) {
            return ['statut' => 'introuvable', 'libelle' => $libelle];
        }
        if ($candidats->count() === 1) {
            return ['statut' => 'ok', 'libelle' => $libelle, 'matiere' => $candidats->first()];
        }

        // Un doublon deja rattache a CE couple filiere x niveau n en est plus
        // un : l ecole a deja tranche, on suit sa decision.
        $dejaLiees = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->whereIn('matiere_id', $candidats->pluck('id'))
            ->pluck('matiere_id');

        if ($dejaLiees->count() === 1) {
            return ['statut' => 'ok', 'libelle' => $libelle, 'matiere' => $candidats->firstWhere('id', $dejaLiees->first())];
        }

        return [
            'statut' => 'ambigu',
            'libelle' => $libelle,
            'candidats' => $candidats->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->code])->all(),
        ];
    }

    private function normaliser(?string $valeur): string
    {
        $sansAccent = \Illuminate\Support\Str::ascii((string) $valeur);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($sansAccent, 'UTF-8')) ?? '';
    }
}
