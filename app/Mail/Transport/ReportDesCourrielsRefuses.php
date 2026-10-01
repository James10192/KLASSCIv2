<?php

namespace App\Mail\Transport;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ce que devient un courriel refusé pour débit (`DebitMailPulseAtteint`).
 *
 * Deux questions, une seule classe parce qu'elles n'ont de sens qu'ensemble.
 *
 * 1. **Faut-il laisser le refus remonter ?** Oui dans un job d'une vraie file :
 *    le worker le reporte. Non partout ailleurs (requête web, commande, job
 *    `sync`) : là, l'appelant garde sa conduite habituelle, et la personne
 *    devant l'écran n'est pas punie d'une limite de débit. D'où
 *    `remonterSiDansUneFile()`, à poser en tête des `catch` larges des
 *    services qu'un job appelle.
 *
 * 2. **Comment reporter sans brûler un essai ?** `release()` garde le compteur
 *    d'essais : trois refus de débit et le job finissait dans `failed_jobs`,
 *    alors que rien n'était cassé. Le job refusé est donc supprimé et une copie
 *    neuve est remise en file, compteur à zéro, avec le délai du refus. Le
 *    nombre de reports est porté par la charge du job (`mailpulse_reports`) et
 *    plafonné à `PLAFOND_REPORTS` : au-delà, on retombe sur `release()`, qui
 *    compte, et le job finit par échouer comme les autres au lieu de tourner
 *    sans fin.
 *
 * Un job qui a déjà échoué pour sa dernière tentative (`hasFailed()`), ou que
 * quelqu'un a déjà supprimé ou relâché, n'est jamais touché : le worker a
 * tranché avant cet écouteur.
 */
final class ReportDesCourrielsRefuses
{
    /** 120 reports à 60 s : un peu plus de deux heures de patience. */
    public const PLAFOND_REPORTS = 120;

    public const CLE_CHARGE = 'mailpulse_reports';

    /** Identifiant du job de file en cours, null hors d'un job (ou dans un job `sync`). */
    private static ?string $jobEnCours = null;

    /** Valeur à écrire dans la charge du prochain job mis en file, le temps d'un report. */
    private static ?int $reportsAEcrire = null;

    public static function brancher(): void
    {
        Event::listen(JobProcessing::class, static function (JobProcessing $e): void {
            if ($e->connectionName !== 'sync') {
                self::entrer(self::identifiant($e->job));
            }
        });
        Event::listen(JobProcessed::class, static fn (JobProcessed $e) => self::sortir(self::identifiant($e->job)));
        Event::listen(JobExceptionOccurred::class, static function (JobExceptionOccurred $e): void {
            self::sortir(self::identifiant($e->job));
            self::reporter($e);
        });

        Queue::createPayloadUsing(static fn () => self::$reportsAEcrire === null
            ? []
            : [self::CLE_CHARGE => self::$reportsAEcrire]);
    }

    public static function entrer(string $job): void
    {
        self::$jobEnCours = $job;
    }

    public static function sortir(string $job): void
    {
        if (self::$jobEnCours === $job) {
            self::$jobEnCours = null;
        }
    }

    public static function dansUneFile(): bool
    {
        return self::$jobEnCours !== null;
    }

    /**
     * À poser en tête d'un `catch` large : relance le refus de débit quand on
     * tourne dans un job de file, ne fait rien sinon.
     */
    public static function remonterSiDansUneFile(Throwable $e): void
    {
        if ($e instanceof DebitMailPulseAtteint && self::dansUneFile()) {
            throw $e;
        }
    }

    public static function reporter(JobExceptionOccurred $evenement): void
    {
        $job = $evenement->job;
        $refus = $evenement->exception;
        if (! $refus instanceof DebitMailPulseAtteint
            || $job->isDeleted() || $job->isReleased() || $job->hasFailed()) {
            return;
        }

        $reports = (int) ($job->payload()[self::CLE_CHARGE] ?? 0);
        $commande = $reports < self::PLAFOND_REPORTS ? self::commande($job) : null;

        if ($commande === null) {
            // Plafond atteint, ou job sans commande sérialisée : le report compte un essai.
            Log::warning('Courriel par MailPulse : report qui compte un essai', [
                'job' => $job->resolveName(),
                'reports' => $reports,
                'essai' => $job->attempts(),
            ]);
            $job->release($refus->reessayerDans);

            return;
        }

        self::$reportsAEcrire = $reports + 1;
        try {
            app('queue')->connection($evenement->connectionName)
                ->later($refus->reessayerDans, $commande, '', $job->getQueue());
        } finally {
            self::$reportsAEcrire = null;
        }
        $job->delete();
    }

    private static function commande(Job $job): ?object
    {
        $donnees = $job->payload()['data'] ?? [];
        $serialisee = $donnees['command'] ?? null;
        if (! is_string($serialisee)) {
            return null;
        }

        try {
            $commande = str_starts_with($serialisee, 'O:')
                ? unserialize($serialisee)
                : unserialize(app(Encrypter::class)->decrypt($serialisee));
        } catch (Throwable $e) {
            Log::warning('Courriel par MailPulse : job refusé impossible à recopier', [
                'job' => $job->resolveName(),
                'erreur' => $e->getMessage(),
            ]);

            return null;
        }

        return is_object($commande) ? $commande : null;
    }

    private static function identifiant(Job $job): string
    {
        return (string) ($job->uuid() ?? $job->getJobId());
    }
}
