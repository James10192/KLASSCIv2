<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\NoteCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Resout les couples (etudiant, matiere, classe, annee, periode) a recalculer
 * pour un perimetre donne.
 *
 * ## Pourquoi cette classe existe
 *
 * La question « quels agregats ce perimetre concerne-t-il ? » avait DEUX
 * reponses dans le depot : la commande `notes:recompute` et l'endpoint
 * `POST /api/cli/notes/recompute`, ecrits separement. Elles divergeaient deja
 * sur quatre points, dont un qui etait un defaut : **l'endpoint validait
 * `etudiant_id`, le conseillait dans son refus 422, et ne filtrait jamais
 * dessus** — un operateur qui visait un eleve recalculait toute sa classe, et
 * un recalcul ECRASE `esbtp_resultats.moyenne`, y compris une valeur saisie a
 * la main.
 *
 * Reimplementer avait donc cree le defaut que deleguer evitait. Les deux
 * appelants lisent desormais la meme selection ; ce qui les distingue (la
 * presentation, le plafond, la mesure avant/apres) leur reste propre.
 *
 * ## Ce que la selection prend, et d'ou
 *
 * Les coordonnees viennent de l'EVALUATION, jamais des colonnes denormalisees
 * de `esbtp_notes` : ce sont ces colonnes-la qui peuvent etre en retard, et
 * c'est tout le sujet de {@see RecalculApresDeplacement}. S'y ajoutent les
 * moyennes deja enregistrees du perimetre : voir {@see self::couples()}.
 */
final class PerimetreDeRecalcul
{
    /** Statuts rendus par {@see self::recalculerUnCouple()}. */
    public const RECALCULE = 'recalcule';

    public const LAISSEE = 'laissee';

    public const ECHEC = 'echec';

    public const RIEN_A_ECRIRE = 'rien_a_ecrire';

    public function __construct(
        public readonly ?int $classeId = null,
        public readonly ?int $matiereId = null,
        public readonly ?int $etudiantId = null,
        public readonly ?string $periode = null,
        public readonly ?int $anneeUniversitaireId = null,
    ) {}

    /**
     * @param  array<string,mixed>  $valeurs  cles : classe_id, matiere_id, etudiant_id, periode, annee_universitaire_id
     */
    public static function depuis(array $valeurs): self
    {
        $entier = static fn ($v) => ($v === null || $v === '') ? null : (int) $v;

        return new self(
            classeId: $entier($valeurs['classe_id'] ?? null),
            matiereId: $entier($valeurs['matiere_id'] ?? null),
            etudiantId: $entier($valeurs['etudiant_id'] ?? null),
            periode: ($valeurs['periode'] ?? null) ?: null,
            anneeUniversitaireId: $entier($valeurs['annee_universitaire_id'] ?? null),
        );
    }

    /**
     * Les couples du perimetre : ceux que portent des notes, ET ceux que porte
     * une moyenne deja enregistree.
     *
     * La seconde source n'est pas une redondance. Apres un deplacement, la
     * moyenne perimee vit precisement sur une coordonnee qui n'a plus aucune
     * evaluation : partir des seules notes ne la voyait jamais, et le
     * rattrapage conseille repondait « rien a recalculer » en la laissant en
     * place. Relue ici, elle passe par {@see self::diagnostic()}, qui la classe
     * `laissee` — jamais remise a zero.
     *
     * @return Collection<int, array{etudiant_id:int, classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}>
     */
    public function couples(): Collection
    {
        return $this->couplesDesNotes()
            ->concat($this->couplesDesMoyennesEnregistrees())
            ->unique(fn ($couple) => implode('|', $couple))
            ->values();
    }

    /**
     * Les moyennes deja enregistrees dans le perimetre, hors lignes annuelles :
     * aucune evaluation ne porte cette periode, elles ne relevent pas de ce
     * recalcul.
     *
     * @return Collection<int, array<string,mixed>>
     */
    private function couplesDesMoyennesEnregistrees(): Collection
    {
        return ESBTPResultat::query()
            ->whereNotNull('etudiant_id')
            ->whereNotNull('classe_id')
            ->whereNotNull('matiere_id')
            ->whereNotNull('annee_universitaire_id')
            ->when($this->classeId, fn ($q, $v) => $q->where('classe_id', $v))
            ->when($this->matiereId, fn ($q, $v) => $q->where('matiere_id', $v))
            ->when($this->etudiantId, fn ($q, $v) => $q->where('etudiant_id', $v))
            ->when($this->periode, fn ($q, $v) => $q->whereIn('periode', ESBTPEvaluation::aliasDePeriode($v)))
            ->when($this->anneeUniversitaireId, fn ($q, $v) => $q->where('annee_universitaire_id', $v))
            ->where('periode', '!=', 'annuel')
            ->get(['etudiant_id', 'classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'])
            ->map(fn (ESBTPResultat $r) => [
                'etudiant_id' => (int) $r->etudiant_id,
                'classe_id' => (int) $r->classe_id,
                'matiere_id' => (int) $r->matiere_id,
                'annee_universitaire_id' => (int) $r->annee_universitaire_id,
                'periode' => ESBTPEvaluation::periodeCanonique((string) $r->periode),
            ])
            ->values();
    }

    /**
     * @return Collection<int, array<string,mixed>>
     */
    private function couplesDesNotes(): Collection
    {
        $evaluations = ESBTPEvaluation::query()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('classe_id')
            ->whereNotNull('matiere_id')
            ->whereNotNull('annee_universitaire_id')
            ->whereNotNull('periode')
            ->when($this->classeId, fn ($q, $v) => $q->where('classe_id', $v))
            ->when($this->matiereId, fn ($q, $v) => $q->where('matiere_id', $v))
            // `whereIn` et non `where` : `esbtp_evaluations.periode` accepte
            // `'1'` autant que `'semestre1'`, et `ESBTPEvaluation::aliasDePeriode()`
            // existe precisement pour que cette connaissance vive sur le modele
            // « et non recopiee chez chaque appelant ». Un `where` nu rendait
            // zero couple sur une evaluation encodee en `'1'` — et l'endpoint
            // repondait alors « Aucune note ne correspond a ce perimetre : rien
            // a recalculer », un SUCCES rassurant sur un perimetre explicitement
            // signale comme a recalculer. C'est le mode d'echec que tout ce
            // chantier existe pour supprimer, reintroduit dans l'echappatoire.
            ->when($this->periode, fn ($q, $v) => $q->whereIn('periode', ESBTPEvaluation::aliasDePeriode($v)))
            ->when($this->anneeUniversitaireId, fn ($q, $v) => $q->where('annee_universitaire_id', $v))
            ->get(['id', 'classe_id', 'matiere_id', 'annee_universitaire_id', 'periode'])
            ->keyBy('id');

        if ($evaluations->isEmpty()) {
            return collect();
        }

        return ESBTPNote::query()
            ->whereIn('evaluation_id', $evaluations->keys())
            ->whereNotNull('etudiant_id')
            ->when($this->etudiantId, fn ($q, $v) => $q->where('etudiant_id', $v))
            ->get(['etudiant_id', 'evaluation_id'])
            ->map(function (ESBTPNote $note) use ($evaluations) {
                $evaluation = $evaluations[$note->evaluation_id] ?? null;

                if (! $evaluation) {
                    return null;
                }

                return [
                    'etudiant_id' => (int) $note->etudiant_id,
                    'classe_id' => (int) $evaluation->classe_id,
                    'matiere_id' => (int) $evaluation->matiere_id,
                    'annee_universitaire_id' => (int) $evaluation->annee_universitaire_id,
                    // La forme CANONIQUE, pas la valeur brute : c'est sous
                    // celle-la que l'agregat est enregistre, donc c'est celle
                    // qu'il faut pour le relire (`moyenneEnregistree()`) et pour
                    // la rendre a l'appelant. Publier `'1'` faisait lire
                    // l'agregat au mauvais endroit : avant/apres a `null`,
                    // `change` a `false` — la reponse annoncait « 0 moyenne
                    // modifiee » pendant qu'une moyenne etait ecrasee.
                    'periode' => ESBTPEvaluation::periodeCanonique((string) $evaluation->periode),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Recalcule chaque couple du perimetre, et rend pour chacun la moyenne
     * d'avant et celle d'apres.
     *
     * Chaque couple passe par {@see self::recalculerUnCouple()}. Le garde
     * contre le 0/20 ecrit « a partir de rien » vit dans {@see self::diagnostic()},
     * que lisent `recalculerUnCouple()` (commande synchrone, endpoint
     * `notes/recompute`, recalcul apres deplacement) ET la mise en file de
     * `notes:recompute --queue`, qui dispatche elle-meme. Tout nouveau
     * dispatcheur de rattrapage doit le lire aussi : rien ne l'y oblige.
     *
     * Chaque ligne porte son `issue` (`recalcule`, `laissee`, `rien_a_ecrire`),
     * et le bilan les compte a part : un couple laisse ou sans rien a ecrire
     * n'a pas ete recalcule, et un message qui l'annoncerait mentirait.
     *
     * Les moyennes sont relues avant et apres plutot que predites : predire
     * demanderait de reecrire la formule a cote de celle du job. L'appelant
     * borne le nombre de couples AVANT d'appeler.
     *
     * `$apresChaqueCouple` sert a la barre de progression de la commande
     * artisan : le service ne connait pas la console.
     *
     * @param  Collection<int, array<string,mixed>>  $couples
     * @return array{lignes:array<int,array<string,mixed>>, recalcules:int, rien_a_ecrire:int, echecs:int, laissees:array<int,array<string,mixed>>}
     */
    public function recalculer(
        Collection $couples,
        string $source,
        ?int $declencheur = null,
        ?callable $apresChaqueCouple = null
    ): array {
        $lignes = [];
        $laissees = [];
        $compte = [self::RECALCULE => 0, self::RIEN_A_ECRIRE => 0, self::ECHEC => 0, self::LAISSEE => 0];

        foreach ($couples as $couple) {
            $resultat = self::recalculerUnCouple($couple, $source, $declencheur);
            $compte[$resultat['statut']]++;

            if ($resultat['statut'] !== self::ECHEC) {
                $lignes[] = [
                    'etudiant_id' => $couple['etudiant_id'],
                    'matiere_id' => $couple['matiere_id'],
                    'periode' => $couple['periode'],
                    'moyenne_avant' => $resultat['avant'],
                    'moyenne_apres' => $resultat['apres'],
                    'change' => $resultat['avant'] !== $resultat['apres'],
                    'laissee' => $resultat['statut'] === self::LAISSEE,
                    'issue' => $resultat['statut'],
                ];
            }

            if ($resultat['laissee'] !== null) {
                $laissees[] = $resultat['laissee'];
            }

            if ($apresChaqueCouple) {
                $apresChaqueCouple();
            }
        }

        return [
            'lignes' => $lignes,
            'recalcules' => $compte[self::RECALCULE],
            'rien_a_ecrire' => $compte[self::RIEN_A_ECRIRE],
            'echecs' => $compte[self::ECHEC],
            'laissees' => $laissees,
        ];
    }

    /**
     * Recalcule UN couple — sauf s'il n'y reste rien a moyenner alors qu'une
     * moyenne est enregistree.
     *
     * ## Le piege du zero
     *
     * `NoteCalculationService` ecarte les absences, les baremes nuls et les
     * coefficients nuls. Sur une coordonnee ou il ne reste rien de tout cela
     * — aucune note, ou seulement des absences —, le job ecrirait **0 sur 20**
     * par-dessus la moyenne enregistree. Apres un deplacement, c'est une
     * moyenne que l'eleve n'a plus ; apres un recalcul en masse, c'est peut-etre
     * une valeur saisie a la main. Dans les deux cas, 0 est strictement pire
     * que la valeur d'avant.
     *
     * La ligne est donc **laissee et signalee**, jamais touchee. Son sort est
     * une decision d'ecole (`.claude/rules/rien-en-dur.md`, « le cas
     * particulier du zero »). Et quand il n'y a pas de ligne, rien n'est
     * cree : voir {@see self::diagnostic()}.
     *
     * **Ce garde ne vaut pas pour l'observateur de note**, et c'est delibere :
     * quand un enseignant marque une note absente, c'est son geste qui fixe la
     * moyenne, et l'observateur la recalcule comme il l'a toujours fait. Ici,
     * personne n'a touche aux notes de ce couple : le recalcul est un
     * rattrapage, et un rattrapage n'invente pas de zero.
     *
     * Une seule exception cote observateur, et elle est dans le job : quand la
     * DERNIERE note est supprimee, il ne reste rien du tout, et le job n'ecrit
     * rien (`RecomputeStudentResultatJob`, etape 4). La ligne, alors sans aucune
     * note, est de celles que le pre-controle des bulletins liste.
     *
     * Le job tourne **sur place** (`dispatchSync`), jamais sur la file : rien
     * ne prouve qu'un worker tourne sur les instances mutualisees.
     *
     * @param  array{etudiant_id:int, classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}  $couple
     * @return array{statut:string, avant:?float, apres:?float, laissee:?array<string,mixed>}
     */
    public static function recalculerUnCouple(array $couple, string $source, ?int $declencheur = null): array
    {
        $avant = null;

        try {
            $diagnostic = self::diagnostic($couple);
            $avant = $diagnostic['avant'];

            if ($diagnostic['statut'] !== self::RECALCULE) {
                return $diagnostic + ['apres' => $avant];
            }

            RecomputeStudentResultatJob::dispatchSync(
                etudiantId: (int) $couple['etudiant_id'],
                classeId: (int) $couple['classe_id'],
                matiereId: (int) $couple['matiere_id'],
                anneeUniversitaireId: (int) $couple['annee_universitaire_id'],
                periode: (string) $couple['periode'],
                source: $source,
                triggeredBy: $declencheur,
            );
        } catch (\Throwable $e) {
            // Les lectures du diagnostic sont DANS le `try` : une erreur de
            // base sur un couple ne doit pas interrompre tout un lot — ni,
            // pour le deplacement en lot, emporter `perimetres_reportes`.
            Log::error('Recalcul de resultat en echec', [
                'couple' => $couple,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return ['statut' => self::ECHEC, 'avant' => $avant, 'apres' => $avant, 'laissee' => null];
        }

        return [
            'statut' => self::RECALCULE,
            'avant' => $avant,
            'apres' => self::moyenneEnregistree($couple),
            'laissee' => null,
        ];
    }

    /**
     * Ce que `recalculerUnCouple()` FERAIT, sans rien ecrire : la meme
     * decision, pour la simulation (`dry_run`) et pour la mise en file
     * (`notes:recompute --queue`), qui doivent appliquer le garde elles aussi.
     *
     * Rien a moyenner et aucune ligne : `RIEN_A_ECRIRE`. Le job, lui, aurait
     * CREE une ligne a 0/20 depuis de simples absences — un zero invente, que
     * la preseance de la ligne enregistree aurait ensuite impose a l'ecran et
     * au bulletin.
     *
     * @param  array<string,mixed>  $couple
     * @return array{statut:string, avant:?float, laissee:?array<string,mixed>}
     */
    public static function diagnostic(array $couple): array
    {
        $ligne = self::ligneEnregistree($couple);
        $avant = $ligne?->moyenne === null ? null : (float) $ligne->moyenne;
        $notes = self::notesComptables($couple);

        if ($notes['moyenne'] !== null) {
            return ['statut' => self::RECALCULE, 'avant' => $avant, 'laissee' => null];
        }

        if ($ligne === null) {
            return ['statut' => self::RIEN_A_ECRIRE, 'avant' => null, 'laissee' => null];
        }

        // Des notes, aucune comptable (absences seulement), et l'etablissement
        // a choisi de les ecarter : la decision est prise, ce n'est plus a un
        // humain de trancher. Le job retire la ligne. Sans ceci, desactiver le
        // reglage ne valait que pour les saisies suivantes : les zeros deja
        // enregistres l'emportaient toujours sur les notes.
        if ($notes['lignes'] > 0 && app(NoteCalculationService::class)->moyenneSansNoteComptable() === null) {
            return ['statut' => self::RECALCULE, 'avant' => $avant, 'laissee' => null];
        }

        return [
            'statut' => self::LAISSEE,
            'avant' => $avant,
            'laissee' => [
                'resultat_id' => (int) $ligne->id,
                'etudiant_id' => (int) $couple['etudiant_id'],
                'classe_id' => (int) $couple['classe_id'],
                'matiere_id' => (int) $couple['matiere_id'],
                'annee_universitaire_id' => (int) $couple['annee_universitaire_id'],
                'periode' => ESBTPEvaluation::periodeCanonique((string) $couple['periode']),
                'moyenne' => $avant,
                // Le nettoyage deja livre (pre-controle de la generation des
                // bulletins) ne voit que les lignes SANS AUCUNE note : une
                // ligne dont il ne reste que des absences lui echappe.
                'reste' => $notes['lignes'] === 0 ? 'aucune_note' : 'notes_non_comptees',
            ],
        ];
    }

    /**
     * Ce que le calcul COMPTERAIT sur ce couple, lu sur la requete meme du
     * job et tranche par `NoteCalculationService` lui-meme : les exclusions
     * (absence, bareme nul, coefficient nul) ne sont recopiees nulle part.
     *
     * @param  array<string,mixed>  $couple
     * @return array{lignes:int, moyenne:?float}
     */
    private static function notesComptables(array $couple): array
    {
        $notes = ESBTPNote::deLaCoordonnee((int) $couple['etudiant_id'], $couple)
            ->with('evaluation:id,bareme,coefficient,periode,classe_id,matiere_id,annee_universitaire_id,status')
            ->get();

        return [
            'lignes' => $notes->count(),
            'moyenne' => app(NoteCalculationService::class)
                ->studentMatiereAverageOrNull(ESBTPNote::enChargeUtilePourLeCalcul($notes)),
        ];
    }

    /**
     * Moyenne actuellement enregistree pour un couple, `null` s'il n'y a pas
     * encore de ligne.
     *
     * @param  array<string,mixed>  $couple
     */
    public static function moyenneEnregistree(array $couple): ?float
    {
        $valeur = self::ligneEnregistree($couple)?->moyenne;

        return $valeur === null ? null : (float) $valeur;
    }

    /** @param array<string,mixed> $couple */
    private static function ligneEnregistree(array $couple): ?ESBTPResultat
    {
        return ESBTPResultat::query()
            ->where('etudiant_id', $couple['etudiant_id'])
            ->where('classe_id', $couple['classe_id'])
            ->where('matiere_id', $couple['matiere_id'])
            ->where('annee_universitaire_id', $couple['annee_universitaire_id'])
            // L'agregat vit sous la forme canonique : le chercher avec la
            // valeur brute (`'1'`) le manquait.
            ->where('periode', ESBTPEvaluation::periodeCanonique((string) $couple['periode']))
            ->first(['id', 'moyenne']);
    }
}
