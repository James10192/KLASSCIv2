<?php

namespace App\Jobs;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
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
     * @param  string  $source  observer | command | manual
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
            $periode = $this->normalizePeriode($this->periode);

            // 1. Récupérer toutes les notes valides pour ce contexte
            $notes = ESBTPNote::query()
                ->where('etudiant_id', $this->etudiantId)
                ->whereHas('evaluation', function ($q) use ($periode) {
                    $q->where('classe_id', $this->classeId)
                        ->where('matiere_id', $this->matiereId)
                        ->where('annee_universitaire_id', $this->anneeUniversitaireId)
                        ->where('periode', $periode)
                        ->where('status', '!=', 'cancelled');
                })
                ->with('evaluation:id,bareme,coefficient,periode,classe_id,matiere_id,annee_universitaire_id,status')
                ->get();

            // 2. Calculer la moyenne pondérée normalisée /20 via le service unifié
            //    (même formule que l'UI temps réel et BulletinService — anti-divergence).
            $moyenneApres = $calc->studentMatiereAverage($this->shapeNotesForService($notes));

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
                        'coefficient' => $resultatExistant?->coefficient ?? 1,
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
     * Convertit la collection Eloquent en payload attendu par
     * {@see NoteCalculationService::studentMatiereAverage()}.
     *
     * Le service prend des arrays homogènes (note/bareme/coefficient/is_absent)
     * — on isole la conversion pour que le job reste découplé des accesseurs
     * du modèle.
     *
     * @return array<int, array{note: float, bareme: float, coefficient: float, is_absent: bool}>
     */
    private function shapeNotesForService(\Illuminate\Support\Collection $notes): array
    {
        return $notes->map(function (ESBTPNote $note) {
            $eval = $note->evaluation;

            return [
                'note' => (float) ($note->note ?? 0),
                'bareme' => $eval ? (float) ($eval->bareme ?? 0) : 0.0,
                'coefficient' => $eval ? (float) ($eval->coefficient ?? 0) : 0.0,
                'is_absent' => (bool) $note->is_absent,
            ];
        })->all();
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
            Log::warning('RecomputeStudentResultatJob: audit log write failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normalise la période — accepte 1/2/semestre1/semestre2/annuel.
     */
    private function normalizePeriode(string $periode): string
    {
        return match ($periode) {
            '1' => 'semestre1',
            '2' => 'semestre2',
            '' => 'semestre1',
            default => $periode,
        };
    }
}
