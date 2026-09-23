<?php

namespace App\Jobs;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use App\Services\NoteCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recalcule la moyenne d'un (étudiant, matière, période, année) à partir
 * des notes courantes en base, met à jour `esbtp_resultats`, touche le
 * bulletin éventuel et logge l'opération dans `esbtp_resultats_recompute_log`.
 *
 * Déclenché automatiquement par {@see \App\Observers\ESBTPNoteObserver}
 * (saved/deleted) et manuellement via `php artisan notes:recompute`.
 *
 * Le calcul est délégué à {@see NoteCalculationService::studentMatiereAverage()}
 * pour garantir l'unicité de la formule (même calcul que UI premier-ordre,
 * preview impact bulletin, et bulletins finaux). Voir le service pour les
 * garanties algorithmiques (inclusion notes 0, exclusion absents, etc.).
 *
 * NB : on ne passe PAS par {@see \App\Services\BulletinService::genererDonneesBulletin()}
 * car cette méthode exige une configuration bulletin préexistante (matières
 * générales/techniques + professeurs) et lève une exception sinon — pas
 * adapté à un trigger en background.
 */
class RecomputeStudentResultatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Nombre de tentatives en cas d'échec (transient DB error, lock, etc.). */
    public int $tries = 3;

    /**
     * Délai en secondes entre tentatives (Laravel queue convention).
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 60, 120];

    /** Timeout du job en secondes. */
    public int $timeout = 60;

    /**
     * @param  string  $source  observer | command | manual | cli | deplacement | ponderation
     * @param  int|null  $triggeredBy  user_id à l'origine du recalcul (peut être null en queue async)
     */
    public function __construct(
        public int $etudiantId,
        public int $classeId,
        public int $matiereId,
        public int $anneeUniversitaireId,
        public string $periode,
        public string $source = 'observer',
        public ?int $triggeredBy = null,
    ) {
        // File dédiée pour ne pas mélanger avec emails / relances critiques.
        $this->onQueue('default');
    }

    /**
     * Tags Horizon / queue:work pour faciliter le filtrage.
     *
     * @return array<int,string>
     */
    public function tags(): array
    {
        return [
            'notes',
            'recompute',
            "etudiant:{$this->etudiantId}",
            "matiere:{$this->matiereId}",
            "source:{$this->source}",
        ];
    }

    public function handle(NoteCalculationService $calc): void
    {
        try {
            // La forme sous laquelle l'agregat est ECRIT et relu.
            $periode = ESBTPEvaluation::periodeCanonique($this->periode);

            // 1. Les notes que le calcul comptera, par la requete PARTAGEE avec le
            //    garde du deplacement. Elles etaient chargees ici et recomptees
            //    ailleurs par un `exists()` qui ignorait les absences : voir
            //    `ESBTPNote::deLaCoordonnee()`.
            $notes = ESBTPNote::deLaCoordonnee($this->etudiantId, [
                'classe_id' => $this->classeId,
                'matiere_id' => $this->matiereId,
                'annee_universitaire_id' => $this->anneeUniversitaireId,
                'periode' => $periode,
            ])
                ->with('evaluation:id,bareme,coefficient,periode,classe_id,matiere_id,annee_universitaire_id,status')
                ->get();

            // 2. Calculer la moyenne pondérée normalisée /20 via le service unifié
            //    (même formule que l'UI temps réel et BulletinService — anti-divergence).
            $moyenneApres = $calc->studentMatiereAverage(ESBTPNote::enChargeUtilePourLeCalcul($notes));

            // 3. Récupérer la moyenne actuelle (avant) pour audit
            $resultatExistant = ESBTPResultat::query()
                ->where('etudiant_id', $this->etudiantId)
                ->where('classe_id', $this->classeId)
                ->where('matiere_id', $this->matiereId)
                ->where('annee_universitaire_id', $this->anneeUniversitaireId)
                ->where('periode', $periode)
                ->first();

            $moyenneAvant = $resultatExistant?->moyenne !== null
                ? (float) $resultatExistant->moyenne
                : null;

            // 4. Si aucune note valide ET aucun résultat existant : no-op
            if ($notes->isEmpty() && ! $resultatExistant) {
                Log::info('RecomputeStudentResultatJob: no notes & no existing resultat, skipping', [
                    'etudiant_id' => $this->etudiantId,
                    'matiere_id' => $this->matiereId,
                    'periode' => $periode,
                ]);

                return;
            }

            // 5. Persister le nouveau résultat (transactionnel)
            DB::transaction(function () use ($calc, $moyenneApres, $moyenneAvant, $periode, $resultatExistant) {
                ESBTPResultat::updateOrCreate(
                    [
                        'etudiant_id' => $this->etudiantId,
                        'classe_id' => $this->classeId,
                        'matiere_id' => $this->matiereId,
                        'periode' => $periode,
                        'annee_universitaire_id' => $this->anneeUniversitaireId,
                    ],
                    [
                        'moyenne' => $moyenneApres,
                        'coefficient' => $resultatExistant?->coefficient ?? $this->coefficientDeLaMaquette($periode),
                        'appreciation' => $calc->getMention($moyenneApres),
                        'updated_by' => $this->triggeredBy,
                        'created_by' => $resultatExistant?->created_by ?? $this->triggeredBy,
                    ]
                );

                // 6. Toucher le bulletin existant pour signaler "stale → à régénérer"
                $this->touchBulletinIfExists($periode);

                // 7. Logger dans la table d'audit dédiée
                $this->writeAuditLog($moyenneAvant, $moyenneApres, $periode);
            });

            Log::info('RecomputeStudentResultatJob: recompute done', [
                'etudiant_id' => $this->etudiantId,
                'matiere_id' => $this->matiereId,
                'periode' => $periode,
                'moyenne_avant' => $moyenneAvant,
                'moyenne_apres' => $moyenneApres,
                'source' => $this->source,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // REFUS DELIBERE, PAS UNE PANNE — donc pas de rejeu. Le garde de
            // `ESBTPResultat` refuse d'enregistrer une moyenne sur une matiere
            // etrangere au systeme academique de la classe. Ce refus est
            // DEFINITIF : le relancer trois fois (`$tries = 3`) puis l'empiler
            // dans `failed_jobs` ne le rendra jamais valide, et remplirait la
            // table a chaque enregistrement d'une des notes heritees — 34 sur la
            // seule `TPGC641` d'Abidjan. On le journalise et on rend la main.
            Log::warning('RecomputeStudentResultatJob: recalcul refuse — matiere etrangere au systeme academique de la classe', [
                'etudiant_id' => $this->etudiantId,
                'matiere_id' => $this->matiereId,
                'classe_id' => $this->classeId,
                'periode' => $this->periode,
                'error' => $e->getMessage(),
            ]);

            return;
        } catch (\Throwable $e) {
            Log::error('RecomputeStudentResultatJob: failed', [
                'etudiant_id' => $this->etudiantId,
                'matiere_id' => $this->matiereId,
                'periode' => $this->periode,
                'error' => $e->getMessage(),
            ]);

            throw $e; // laisse le mécanisme de retry agir
        }
    }


    /**
     * Touche updated_at du bulletin associé (s'il existe) pour signaler
     * "données sources modifiées → bulletin à régénérer".
     */
    private function touchBulletinIfExists(string $periode): void
    {
        $bulletin = ESBTPBulletin::query()
            ->where('etudiant_id', $this->etudiantId)
            ->where('classe_id', $this->classeId)
            ->where('annee_universitaire_id', $this->anneeUniversitaireId)
            ->where('periode', $periode)
            ->first();

        if ($bulletin) {
            $bulletin->touch();
        }
    }

    /**
     * Écrit la ligne d'audit. On wrap dans un try/catch pour ne JAMAIS
     * faire planter le recompute à cause d'un échec d'audit.
     */
    private function writeAuditLog(?float $moyenneAvant, float $moyenneApres, string $periode): void
    {
        try {
            DB::table('esbtp_resultats_recompute_log')->insert([
                'etudiant_id' => $this->etudiantId,
                'classe_id' => $this->classeId,
                'matiere_id' => $this->matiereId,
                'periode' => $periode,
                'annee_universitaire_id' => $this->anneeUniversitaireId,
                'moyenne_avant' => $moyenneAvant,
                'moyenne_apres' => $moyenneApres,
                'source' => $this->source,
                'triggered_by' => $this->triggeredBy,
                'recomputed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ce rattrapage a longtemps ete muet sur l'essentiel : il taisait la
            // `source`, qui est precisement ce qui faisait echouer l'INSERT quand
            // la colonne etait une enumeration fermee. Resultat, 53 echecs dans
            // une seule suite de tests sans que rien ne dise lesquels ni pourquoi.
            // Un rattrapage qui degrade doit nommer ce qu'il a rattrape.
            Log::warning('RecomputeStudentResultatJob: audit log write failed', [
                'error' => $e->getMessage(),
                'source' => $this->source,
                'etudiant_id' => $this->etudiantId,
                'classe_id' => $this->classeId,
                'matiere_id' => $this->matiereId,
                'periode' => $periode,
            ]);
        }
    }

    /**
     * Le coefficient que la maquette declare pour ce couple, et non `1`.
     *
     * CE `?? 1` COUTAIT PLUS CHER QU'IL N'EN AVAIT L'AIR, et ce n'etait pas
     * visible d'ici. La ligne qu'ecrit ce job est relue par
     * `BtsCurrentResultSnapshotService`, qui PREFERE le coefficient stocke au
     * coefficient configure — a juste titre quand une personne l'a saisi, mais
     * personne ne l'avait saisi : ce job l'avait seme. L'ecran « Modifier les
     * moyennes » affichait donc `1` sur un onglet de semestre quoi que la
     * maquette declare, et le reenregistrait.
     *
     * CE QUE CE CHANGEMENT NE TOUCHE PAS. Le `??` court-circuite des qu'une
     * ligne existe : seules changent les lignes NEUVES. Une premiere version de
     * ce chantier a reporte la correction en invoquant « elle change des valeurs
     * deja enregistrees » ; c'etait faux, et le report ne protegeait rien.
     *
     * ET CE QU'IL NE REPARE PAS. `esbtp_resultats.coefficient` est
     * `decimal(5,2) NOT NULL DEFAULT 1.00` : un `1,00` deja en base peut etre
     * un choix de l'ecole, l'ancien `?? 1` de ce job, ou le defaut SQL, et
     * rien ne les distingue de facon fiable. Dire « rien de ce qu'une ecole a
     * saisi n'est reecrit » serait donc flatteur : on ne sait pas ce qu'elle a
     * saisi. Le parc installe garde ses valeurs, justes ou fausses, jusqu'a la
     * regeneration d'un bulletin (`BulletinService::persistResultats()` les
     * reecrit depuis la maquette) ou une saisie manuelle.
     *
     * UN REPERE PARTIEL EXISTE QUAND MEME, et l'ignorer serait l'exces
     * inverse : `esbtp_resultats_recompute_log`, ecrite par ce job DANS LA
     * MEME TRANSACTION depuis mai 2026, dit quelles lignes il a touchees, avec
     * leur `source` et leur `triggered_by`. Elle ne stocke PAS le coefficient
     * et ne couvre pas l'anterieur a mai 2026 — elle ne tranche donc pas, mais
     * elle reduit le champ. Une phrase qui declare inconnaissable ce dont le
     * depot a l'outil partiel ferme l'enquete aussi surement qu'une phrase qui
     * absout.
     *
     * LE REPLI EST LARGE A DESSEIN. `coefficientOrDefault()` ne rattrape que
     * `CoefficientMissingException` ; `getCoefficientForCombination()` peut
     * aussi lever un `RuntimeException` nu (« Classe invalide ») pour une
     * classe sans filiere ni niveau. Ici, une exception ferait echouer un job
     * de file avec ses trois tentatives, pour une valeur dont `1` est un repli
     * acceptable : on rattrape donc tout, et on le DIT.
     */
    private function coefficientDeLaMaquette(string $periode): float
    {
        try {
            return app(BulletinService::class)->coefficientOrDefault(
                $this->matiereId,
                $this->classeId,
                $this->anneeUniversitaireId,
                $periode,
                $this->etudiantId,
            );
        } catch (\Throwable $e) {
            Log::warning('RecomputeStudentResultatJob : coefficient introuvable, repli sur 1.', [
                'matiere_id' => $this->matiereId,
                'classe_id' => $this->classeId,
                'periode' => $periode,
                'raison' => $e->getMessage(),
            ]);

            return 1.0;
        }
    }
}
