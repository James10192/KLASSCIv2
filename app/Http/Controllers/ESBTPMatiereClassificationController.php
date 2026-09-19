<?php

namespace App\Http\Controllers;

use App\Domain\BtsTroncCommun\RetraitDeMaquette;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Affectation Tronc Commun / Spécialité des matières, au grain (matière, filière, niveau).
 *
 * Permet à l'école de marquer, pour un combo (filière, niveau), quelles matières
 * sont du tronc commun et lesquelles sont de spécialité. Le bulletin de tronc commun
 * n'affiche alors que les matières TC (ou non classées). BTS uniquement, LMD intouché.
 *
 * @see \App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver
 * @see .claude/rules/classe-lmd-filiere-as-mention.md
 */
class ESBTPMatiereClassificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:matieres.edit']);
    }

    /**
     * Page d'affectation TC / Spécialité (pickers filière + niveau).
     */
    public function index()
    {
        $filieres = ESBTPFiliere::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_tronc_commun', 'parent_id']);

        // Niveaux BTS uniquement (les niveaux LMD gèrent leurs matières via MatiereTreeBuilder).
        $niveaux = ESBTPNiveauEtude::query()
            ->whereNotIn('type', ESBTPNiveauEtude::CYCLES_LMD)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type']);

        return view('esbtp.matieres.classification', compact('filieres', 'niveaux'));
    }

    /**
     * Matières d'un combo (filière, niveau) avec leur classification + suggestion.
     */
    public function combo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filiere_id' => 'required|exists:esbtp_filieres,id',
            'niveau_id' => 'required|exists:esbtp_niveau_etudes,id',
        ]);

        $filiereId = (int) $validated['filiere_id'];
        $niveauId = (int) $validated['niveau_id'];

        $filiere = ESBTPFiliere::find($filiereId);
        $isTroncCommun = $filiere && $filiere->isTroncCommun();

        $rows = $this->lignesDuCombo($filiereId, $niveauId, $filiere, $isTroncCommun);

        // Le COUPLE est renseigne des qu'une de ses lignes a ete validee —
        // c'est la regle du domaine (`BtsMaquette::isRenseignee`), et c'est
        // elle qui decide si les semestres comptent. La poser ici, une fois,
        // evite que le decompte affiche et l'etat annonce divergent.
        $comboRenseigne = $rows->contains(fn ($ligne) => $ligne['semestre_renseigne']);

        return response()->json([
            'success' => true,
            'is_tronc_commun' => $isTroncCommun,
            // La maquette ne s'applique au bulletin que lorsque TOUS les
            // combos de la classe sont valides (`BtsMaquette::etatPourClasse`).
            // Pour une filiere de specialite rattachee a un tronc commun, ce
            // sont deux combos : valider celui-ci seul ne declenche rien.
            //
            // Le predicat est `troncCommunUnionFiliereIds()`, PAS
            // `is_tronc_commun` : une filiere normale SANS parent tronc commun
            // — la forme la plus courante — n'a qu'un seul combo et s'applique
            // donc immediatement. L'ecran a annonce le contraire, c'est-a-dire
            // « rien ne s'appliquera » au moment precis ou tout s'applique.
            'depend_du_tronc_commun' => $filiere
                ? count($filiere->troncCommunUnionFiliereIds()) > 1
                : false,
            'filiere' => $filiere?->name,
            'matieres' => $rows,
            'maquette' => [
                'renseignee' => $comboRenseigne,
                // Meme regle que le domaine, et par la MEME methode : une
                // ligne non validee vaut « les deux », donc tant qu'aucune ne
                // l'est, ces deux nombres valent le total. Les compter
                // autrement ferait dire a l'ecran « aucune matiere au semestre
                // 2 » alors que le bulletin en portera dix-huit.
                'semestre_1' => $rows->filter(fn ($l) => $this->prevueAu($l, 1))->count(),
                'semestre_2' => $rows->filter(fn ($l) => $this->prevueAu($l, 2))->count(),
            ],
            // Les ECUE LMD posees par erreur sur cette maquette BTS. Elles ne
            // sont PAS dans `matieres` : les y mettre fausserait les
            // decomptes, l'ordre et l'enregistrement, qui sont tous des
            // notions BTS. Mais ne les montrer nulle part est ce qui a rendu
            // le defaut incorrigible — la ligne sortait sur le bulletin et
            // aucun ecran ne permettait de l'enlever.
            'intrus_lmd' => $this->intrusLmdDuCombo($filiereId, $niveauId),
            'planning' => $this->apercuDuPlanning($filiereId, $niveauId, $request),
            'kpis' => [
                'total' => $rows->count(),
                'tronc_commun' => $rows->where('classification', ESBTPMatiereFilierNiveau::TRONC_COMMUN)->count(),
                'specialite' => $rows->where('classification', ESBTPMatiereFilierNiveau::SPECIALITE)->count(),
                'non_classe' => $rows->whereNull('classification')->count(),
            ],
        ]);
    }

    /**
     * Les lignes de pivot de ce couple, avant tout tri BTS / LMD.
     *
     * Une seule definition, parce que `lignesDuCombo()` et
     * `intrusLmdDuCombo()` lisent la MEME chose avec le filtre inverse : les
     * ecrire deux fois aurait suffi a ce qu'une condition ajoutee demain ne
     * soit appliquee qu'a l'une des deux moities, et que des lignes
     * disparaissent des DEUX listes sans que personne ne le voie.
     *
     * @return \Illuminate\Support\Collection<int, ESBTPMatiereFilierNiveau>
     */
    private function lignesBrutesDuCombo(int $filiereId, int $niveauId)
    {
        return ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->with('matiere:id,name,code,unite_enseignement_id,is_active,ordre_bulletin')
            ->get();
    }

    /**
     * Les ECUE LMD presentes dans la maquette BTS de ce couple.
     *
     * Normalement : aucune. Quand il y en a, c'est qu'un ecran BTS a pose la
     * ligne sans garde — cas mesure sur esbtp-abidjan, ou `TPOH243`
     * « Alimentation en eau et QTE » portait (TRAVAUX_PUBLICS, 2A) et sortait
     * donc sur les bulletins de Travaux Publics 2e annee.
     *
     * On ne les rend ni classables ni ordonnables : la seule action qui a du
     * sens sur elles est le retrait.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function intrusLmdDuCombo(int $filiereId, int $niveauId)
    {
        return $this->lignesBrutesDuCombo($filiereId, $niveauId)
            ->filter(fn ($row) => $row->matiere && $row->matiere->unite_enseignement_id !== null)
            ->map(fn ($row) => [
                'matiere_id' => (int) $row->matiere_id,
                'name' => $row->matiere->name,
                'code' => $row->matiere->code,
            ])
            ->sortBy(fn ($l) => mb_strtolower((string) $l['name'], 'UTF-8'))
            ->values();
    }

    /**
     * Les matieres de ce combo, dans l'ordre ou le bulletin les sortira.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function lignesDuCombo(int $filiereId, int $niveauId, ?ESBTPFiliere $filiere, bool $isTroncCommun)
    {
        // Suggestion : sur un combo TC, une matière aussi rattachée à une filière fille
        // (spécialité) du même niveau est probablement de spécialité mal rattachée.
        $specialiteSuggestionIds = [];
        if ($isTroncCommun) {
            $childFiliereIds = ESBTPFiliere::where('parent_id', $filiereId)->pluck('id');
            if ($childFiliereIds->isNotEmpty()) {
                $specialiteSuggestionIds = ESBTPMatiereFilierNiveau::query()
                    ->whereIn('filiere_id', $childFiliereIds)
                    ->where('niveau_etude_id', $niveauId)
                    ->pluck('matiere_id')
                    ->unique()
                    ->all();
            }
        }
        $specialiteSuggestionIds = array_flip($specialiteSuggestionIds);

        $rangsEffectifs = app(\App\Domain\BtsTroncCommun\BulletinSubjectOrder::class)
            ->rankMapForFiliereNiveau($filiereId, $niveauId, $filiere?->troncCommunUnionFiliereIds());

        return $this->lignesBrutesDuCombo($filiereId, $niveauId)
            ->filter(fn ($row) => $row->matiere && $row->matiere->unite_enseignement_id === null) // BTS only
            ->map(function ($row) use ($specialiteSuggestionIds, $rangsEffectifs) {
                $rangPropre = \App\Domain\BtsTroncCommun\BulletinSubjectOrder::rang($row->ordre_bulletin);
                $rangGeneral = \App\Domain\BtsTroncCommun\BulletinSubjectOrder::rang($row->matiere->ordre_bulletin ?? null);
                // Rang du bulletin : il tient compte du tronc commun parent.
                // Le calculer ici ferait diverger l'ecran du PDF pour une
                // filiere de specialite qui herite d'un rang du tronc commun.
                $rangEffectif = $rangsEffectifs[(int) $row->matiere_id] ?? ($rangPropre ?? $rangGeneral);

                return [
                    'matiere_id' => $row->matiere_id,
                    'name' => $row->matiere->name,
                    'code' => $row->matiere->code,
                    'is_active' => (bool) $row->matiere->is_active,
                    'classification' => $row->classification,
                    'suggested' => $row->classification === null && isset($specialiteSuggestionIds[$row->matiere_id])
                        ? ESBTPMatiereFilierNiveau::SPECIALITE
                        : null,
                    'ordre_bulletin' => $rangPropre,
                    'ordre_general' => $rangGeneral,
                    'ordre_effectif' => $rangEffectif,
                    // Dit a l'ecran d'ou vient la place affichee, pour que
                    // « herite » et « propre a cette filiere » se distinguent.
                    'ordre_source' => $rangPropre !== null ? 'combo' : ($rangGeneral !== null ? 'general' : 'aucun'),
                    'semestre' => $row->semestre === null ? null : (int) $row->semestre,
                    'semestre_renseigne' => (bool) $row->semestre_renseigne,
                ];
            })
            // Meme ordre qu'au bulletin : rangs definis d'abord, puis le nom.
            ->sortBy(fn ($ligne) => [
                $ligne['ordre_effectif'] === null ? 1 : 0,
                $ligne['ordre_effectif'] ?? 0,
                mb_strtolower((string) $ligne['name'], 'UTF-8'),
            ])
            ->values();
    }

    /**
     * Ce que le planning general de l'annee courante dirait de ce combo.
     *
     * Purement informatif : rien n'est ecrit tant que l'utilisateur n'a pas
     * demande l'import. `null` quand aucune ligne de planning n'existe.
     */
    private function apercuDuPlanning(int $filiereId, int $niveauId, Request $request): ?array
    {
        $annee = $request->filled('annee_universitaire_id')
            ? \App\Models\ESBTPAnneeUniversitaire::find($request->integer('annee_universitaire_id'))
            : \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $annee) {
            return null;
        }

        $diff = app(\App\Domain\BtsTroncCommun\PlanningToMaquetteImporter::class)
            ->diff($filiereId, $niveauId, (int) $annee->id);

        if ($diff['planning_lignes'] === 0) {
            return null;
        }

        return [
            'annee_id' => (int) $annee->id,
            'annee_libelle' => $annee->name ?? $annee->libelle ?? null,
            'lignes' => $diff['planning_lignes'],
            'changements' => $diff['changements'],
            'hors_maquette' => count($diff['hors_maquette']),
        ];
    }

    /**
     * Une matiere est-elle prevue a ce semestre ?
     *
     * LIGNE PAR LIGNE, comme le domaine — et non par couple. Une version
     * precedente remplacait `semestre_renseigne` par une garde sur le couple
     * entier (« si aucune ligne n'est validee, tout vaut les deux »). Elle
     * etait juste tant qu'AUCUNE ligne n'etait validee, et fausse des qu'une
     * seule l'etait : les lignes non validees du meme couple etaient alors
     * lues a leur semestre brut, donc retirees d'un semestre que le bulletin,
     * lui, leur laissait. Le commentaire qui l'accompagnait affirmait « le
     * domaine la pose par couple » — `BtsMaquette::semestresParMatiere()` la
     * pose ligne par ligne, et c'est verifiable en trois lignes de lecture.
     *
     * C'est le cas mesure : `ChargementDeMaquette` pose un semestre sans
     * valider, et la maquette d'ESBTP Abidjan en compte.
     *
     * @param  array<string, mixed>  $ligne
     */
    private function prevueAu(array $ligne, int $semestre): bool
    {
        $declare = \App\Domain\BtsTroncCommun\SemestreDeMaquette::declarationEffective(
            $ligne['semestre'] === null ? null : (int) $ligne['semestre'],
            (bool) $ligne['semestre_renseigne'],
        );

        return \App\Domain\BtsTroncCommun\SemestreDeMaquette::estPrevueAu($declare, $semestre);
    }

    /**
     * Enregistre en masse la classification des matières d'un combo.
     */
    /**
     * Retire une matière de la maquette d'un combo.
     *
     * Cet écran savait régler ce qui est déjà là — la place, le semestre, le
     * statut — mais ni ajouter ni retirer : son enregistrement fait un
     * `update`, jamais un `insert` ni un `delete`. Retirer une matière
     * obligeait donc à quitter la maquette pour le modal des liaisons de la
     * matière, qui supprimait au passage les réglages de ses autres combos.
     *
     * Refuse une matière qui porte des évaluations sur ce combo tant que
     * l'utilisateur ne l'a pas confirmé : la note resterait en base sans plus
     * apparaître nulle part.
     */
    public function retirer(Request $request, RetraitDeMaquette $retrait): JsonResponse
    {
        $valide = $request->validate([
            'filiere_id' => ['required', 'integer', 'exists:esbtp_filieres,id'],
            'niveau_id' => ['required', 'integer', 'exists:esbtp_niveau_etudes,id'],
            'matiere_id' => ['required', 'integer', 'exists:esbtp_matieres,id'],
            'malgre_les_notes' => ['sometimes', 'boolean'],
        ]);

        $filiere = ESBTPFiliere::find((int) $valide['filiere_id']);
        $niveau = ESBTPNiveauEtude::find((int) $valide['niveau_id']);

        if (! $filiere || ! $niveau) {
            return response()->json(['success' => false, 'message' => 'Filière ou niveau introuvable.'], 404);
        }

        // Le même constat que l'endpoint CLI, par le même code : il résout la
        // matière, dit si elle est dans la maquette et compte ses évaluations
        // sur ce couple. Cet écran en refaisait une copie, et les deux
        // pouvaient répondre différemment de la même question.
        $plan = $retrait->preparer($filiere, $niveau, [['id' => (int) $valide['matiere_id']]]);
        $ligne = $plan['lignes'][0] ?? null;

        if ($ligne === null) {
            return response()->json(['success' => false, 'message' => 'Matière introuvable.'], 404);
        }

        // D'abord : est-elle seulement là ? Compter les évaluations avant de
        // le savoir fait demander une confirmation pour un retrait qui n'aura
        // rien à retirer — l'utilisateur confirme, et reçoit ensuite « elle
        // n'est pas dans la maquette ».
        if (! $ligne['dans_la_maquette']) {
            return response()->json([
                'success' => false,
                'message' => 'Cette matière n\'est pas dans la maquette de ce niveau.',
            ], 422);
        }

        if ($ligne['evaluations_sur_ce_couple'] > 0 && ! $request->boolean('malgre_les_notes')) {
            return response()->json([
                'success' => false,
                'evaluations' => $ligne['evaluations_sur_ce_couple'],
                'confirmation_requise' => true,
                'message' => $ligne['matiere'].' porte '.$ligne['evaluations_sur_ce_couple']
                    .' évaluation(s) sur ce niveau. '
                    .'Les retirer de la maquette les fera disparaître du bulletin.',
            ], 422);
        }

        try {
            $ecrit = $retrait->appliquer($filiere, $niveau, $plan['lignes']);

            return response()->json([
                'success' => true,
                'message' => $ligne['matiere'].' a été retirée de la maquette.',
                'retire' => $ecrit['lignes'][0]['retire'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur retrait d\'une matière de la maquette', [
                'filiere_id' => $filiere->id,
                'niveau_id' => $niveau->id,
                'matiere_id' => $valide['matiere_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait de la matière.',
            ], 500);
        }
    }

    public function save(\App\Http\Requests\Matiere\ClassificationSaveRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filiereId = (int) $validated['filiere_id'];
        $niveauId = (int) $validated['niveau_id'];
        // Valider les semestres est un geste EXPLICITE. Enregistrer un ordre ou
        // une classification ne doit jamais activer une maquette au passage.
        $validerSemestres = (bool) ($validated['valider_semestres'] ?? false);

        try {
            $updated = 0;
            DB::transaction(function () use ($validated, $filiereId, $niveauId, $validerSemestres, &$updated) {
                foreach ($validated['classifications'] as $item) {
                    // Un champ ABSENT du payload n'est pas touche ; un champ
                    // present a null efface la valeur.
                    $changements = [];

                    if (array_key_exists('classification', $item)) {
                        $changements['classification'] = $item['classification'];
                    }
                    if (array_key_exists('ordre_bulletin', $item)) {
                        $changements['ordre_bulletin'] = $item['ordre_bulletin'];
                    }
                    if (array_key_exists('semestre', $item)) {
                        $changements['semestre'] = $item['semestre'];
                    }
                    if ($validerSemestres) {
                        $changements['semestre_renseigne'] = true;
                    }

                    if ($changements === []) {
                        continue;
                    }

                    $affected = ESBTPMatiereFilierNiveau::query()
                        ->where('filiere_id', $filiereId)
                        ->where('niveau_etude_id', $niveauId)
                        ->where('matiere_id', (int) $item['matiere_id'])
                        ->update($changements);
                    $updated += $affected;
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Maquette enregistrée pour {$updated} matière(s).",
                'updated' => $updated,
                'maquette_renseignee' => app(\App\Domain\BtsTroncCommun\BtsMaquette::class)
                    ->isRenseignee($filiereId, $niveauId),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur enregistrement classification TC/Spécialité', [
                'filiere_id' => $filiereId,
                'niveau_id' => $niveauId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la classification.',
            ], 500);
        }
    }
}
