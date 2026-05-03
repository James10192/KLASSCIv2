<?php

namespace App\Jobs;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
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
 * IMPORTANT — Calcul indépendant de BulletinService.
 *
 * On ne ré-utilise PAS volontairement
 * {@see \App\Services\BulletinService::genererDonneesBulletin()} ici,
 * pour deux raisons :
 *  1. Cette méthode exige une configuration bulletin préexistante (matières
 *     générales/techniques + professeurs) et lève une exception sinon. Ce
 *     n'est pas adapté à un trigger en background.
 *  2. Une PR sœur retravaille en parallèle la formule de moyenne dans
 *     BulletinService — y appeler maintenant créerait un conflit garanti.
 *
 * Formule appliquée localement (5 lignes) :
 *   moyenne = SUM((note / bareme) * 20 * coef_evaluation) / SUM(coef_evaluation)
 * en excluant les absents et les barèmes invalides.
 */
class RecomputeStudentResultatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Nombre de tentatives en cas d'échec (transient DB error, lock, etc.). */
    public int $tries = 3;

    /** Délai (secondes) entre 2 tentatives. */
    public int $retryAfter = 30;

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

    public function handle(): void
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

            // 2. Calculer la moyenne pondérée normalisée /20
            $moyenneApres = $this->computeMoyenne($notes);

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
            DB::transaction(function () use ($moyenneApres, $moyenneAvant, $periode, $resultatExistant) {
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
                        'appreciation' => $this->getAppreciation($moyenneApres),
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
     * Calcule la moyenne /20 pondérée par coefficient d'évaluation,
     * en normalisant chaque note via son barème.
     *
     * Notes absentes (is_absent=1) et barèmes invalides (<=0) sont exclues.
     */
    private function computeMoyenne(\Illuminate\Support\Collection $notes): float
    {
        $totalPoints = 0.0;
        $totalCoeffs = 0.0;

        foreach ($notes as $note) {
            if ($note->is_absent) {
                continue;
            }

            $eval = $note->evaluation;
            if (! $eval) {
                continue;
            }

            $bareme = (float) ($eval->bareme ?? 0);
            $coef = (float) ($eval->coefficient ?? 0);
            if ($bareme <= 0 || $coef <= 0) {
                continue;
            }

            $rawNote = (float) ($note->note ?? 0);
            $note20 = ($rawNote / $bareme) * 20.0;

            $totalPoints += $note20 * $coef;
            $totalCoeffs += $coef;
        }

        if ($totalCoeffs <= 0) {
            return 0.0;
        }

        return round($totalPoints / $totalCoeffs, 2);
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

    /**
     * Appréciation simple (alignée sur BulletinService::getAppreciation).
     */
    private function getAppreciation(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Très Bien',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }
}
