<?php

namespace App\Domain\Notes;

use App\Jobs\RecomputeStudentResultatJob;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

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
 * c'est tout le sujet de {@see RecalculApresDeplacement}.
 */
final class PerimetreDeRecalcul
{
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
     * @return Collection<int, array{etudiant_id:int, classe_id:int, matiere_id:int, annee_universitaire_id:int, periode:string}>
     */
    public function couples(): Collection
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
            ->unique(fn ($couple) => implode('|', $couple))
            ->values();
    }

    /**
     * Recalcule chaque couple du perimetre, et rend pour chacun la moyenne
     * d'avant et celle d'apres.
     *
     * Le job tourne **sur place** (`dispatchSync`), jamais sur la file : rien
     * ne prouve qu'un worker tourne sur les instances mutualisees —
     * `config/queue.php` vaut `database` par defaut et `app/Console/Kernel.php`
     * ne planifie aucun `queue:work`. Dispatcher aurait donne un correctif qui
     * a l'air pose et ne s'execute jamais.
     *
     * Les moyennes sont relues avant et apres plutot que predites : predire
     * demanderait de reecrire la formule a cote de celle du job, et deux
     * formules qui derivent sont la famille de defauts que tout ce chantier
     * corrige. L'appelant borne le nombre de couples AVANT d'appeler.
     *
     * @param  Collection<int, array<string,mixed>>  $couples
     * @return array{lignes:array<int,array<string,mixed>>, echecs:int}
     */
    public function recalculer(Collection $couples, string $source, ?int $declencheur = null): array
    {
        $lignes = [];
        $echecs = 0;

        foreach ($couples as $couple) {
            $avant = $this->moyenneEnregistree($couple);

            try {
                RecomputeStudentResultatJob::dispatchSync(
                    etudiantId: $couple['etudiant_id'],
                    classeId: $couple['classe_id'],
                    matiereId: $couple['matiere_id'],
                    anneeUniversitaireId: $couple['annee_universitaire_id'],
                    periode: $couple['periode'],
                    source: $source,
                    triggeredBy: $declencheur,
                );
            } catch (\Throwable $e) {
                $echecs++;
                Log::error('Recalcul de resultat en echec', [
                    'couple' => $couple,
                    'source' => $source,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $apres = $this->moyenneEnregistree($couple);

            $lignes[] = [
                'etudiant_id' => $couple['etudiant_id'],
                'matiere_id' => $couple['matiere_id'],
                'periode' => $couple['periode'],
                'moyenne_avant' => $avant,
                'moyenne_apres' => $apres,
                'change' => $avant !== $apres,
            ];
        }

        return ['lignes' => $lignes, 'echecs' => $echecs];
    }

    /**
     * Moyenne actuellement enregistree pour un couple, `null` s'il n'y a pas
     * encore de ligne.
     *
     * @param  array<string,mixed>  $couple
     */
    public function moyenneEnregistree(array $couple): ?float
    {
        $valeur = ESBTPResultat::query()
            ->where('etudiant_id', $couple['etudiant_id'])
            ->where('classe_id', $couple['classe_id'])
            ->where('matiere_id', $couple['matiere_id'])
            ->where('annee_universitaire_id', $couple['annee_universitaire_id'])
            ->where('periode', $couple['periode'])
            ->value('moyenne');

        return $valeur === null ? null : (float) $valeur;
    }
}
