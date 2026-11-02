<?php

namespace App\Console\Commands;

use App\Jobs\Annonces\EnvoyerAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use App\Services\Annonces\ReferenceCourrielAnnonce;
use App\Services\Annonces\SuppressionCourrielAnnonce;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recovery for jobs lost with QUEUE_CONNECTION=sync.
 *
 * Strictly limited to the local_rate_limit decision, which occurs in
 * EnvoyerAnnonceCourriel BEFORE the HTTP submission boundary.
 * The existing immutable reference is retained. All other statuses require
 * provider reconciliation or a separate audited attempt workflow.
 */
final class RecupererFileCourrielsAnnonce extends Command
{
    protected $signature = 'annonces:recuperer-file
        {--annonce= : Identifiant de la campagne}
        {--tenant= : Code de l\'établissement, confirmation obligatoire lors de l\'exécution}
        {--limit=10 : Nombre maximal de livraisons à réenfiler par passage, 1 à 60}
        {--execute : Autoriser la réinsertion contrôlée en file database (sinon simulation)}';

    protected $description = 'Diagnostiquer et récupérer par petits lots les relances locales perdues par la queue sync.';

    public function handle(): int
    {
        $id = filter_var($this->option('annonce'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $this->error('Précisez --annonce=<id> (entier positif).');
            return self::FAILURE;
        }

        $limite = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => [
            'min_range' => 1, 'max_range' => 60,
        ]]);
        if ($limite === false) {
            $this->error('--limit doit être compris entre 1 et 60.');
            return self::FAILURE;
        }

        $annonce = ESBTPAnnonce::find((int) $id);
        if (! $annonce || ! $annonce->is_published || ! $annonce->email_notification_enabled
            || $annonce->date_publication?->isFuture() || $annonce->isExpired()) {
            $this->error('Annonce absente, non publiée, non éligible aux e-mails ou expirée.');
            return self::FAILURE;
        }

        $tenant = (string) config('app.tenant_code', '');
        $operation = (bool) $this->option('execute');

        if ($operation) {
            if (config('queue.default') !== 'database' || ! Schema::hasTable('jobs')) {
                $this->error('ARRÊT : QUEUE_CONNECTION=database et table jobs requises. Aucun envoi.');
                return self::FAILURE;
            }
            // Cette reprise exige que changement de statut et insertion dans
            // jobs partagent la MEME transaction et le MEME moteur de DB.
            if (config('queue.connections.database.after_commit') !== false
                || ($connexionQueue = config('queue.connections.database.connection'))
                    && $connexionQueue !== DB::getDefaultConnection()) {
                $this->error('ARRÊT : la queue database doit utiliser la connexion DB courante avec after_commit=false.');
                return self::FAILURE;
            }
            if ($tenant === '' || $tenant === 'default'
                || ! preg_match('/^[a-z0-9][a-z0-9_-]{1,62}$/D', $tenant)
                || ! hash_equals($tenant, (string) $this->option('tenant'))) {
                $this->error('ARRÊT : --tenant doit être égal au TENANT_CODE réel de cette instance.');
                return self::FAILURE;
            }
        }

        $base = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)
            ->where('status', 'retrying')
            ->where('error_code', 'local_rate_limit')
            ->whereNull('mailpulse_message_id')
            ->whereNull('submitted_at')
            ->whereNull('provider_failed_at')
            ->whereNull('provider_delivered_at');

        $candidats = (clone $base)->count();
        $referencementsValides = 0;
        $sansReferenceValide = 0;
        $supprimes = 0;
        $ids = [];
        $bloquees = SuppressionCourrielAnnonce::adressesBloquees();
        // La simulation et la sélection du lot appliquent LES MEMES règles
        // exactes ; un premier candidat invalide ne bloque plus les suivants.
        (clone $base)->orderBy('id')->chunkById(200, function ($lignes) use (
            $tenant, $limite, &$referencementsValides, &$sansReferenceValide, &$supprimes,
            &$ids, $bloquees
        ): void {
            foreach ($lignes as $ligne) {
                if (! $this->referenceValide($ligne, $tenant)) {
                    $sansReferenceValide++;
                    continue;
                }
                if (isset($bloquees[strtolower(trim((string) $ligne->email))])) {
                    $supprimes++;
                    continue;
                }
                $referencementsValides++;
                if (count($ids) < $limite) {
                    $ids[] = (int) $ligne->id;
                }
            }
        });
        $this->info(sprintf(
            'Annonce %d · tenant %s · %d candidats locaux · %d strictement récupérables · %s.',
            $annonce->id, $tenant ?: '?', $candidats,
            $referencementsValides, $operation ? 'EXECUTION' : 'SIMULATION'
        ));
        $this->line(sprintf(
            'Références v2 invalides/absentes : %d ; adresses déjà rebondies, supprimées ou en plainte : %d.',
            $sansReferenceValide, $supprimes
        ));

        if (! $operation) {
            $this->line('Vérifiez QUEUE_CONNECTION=database, le worker, le quota MailPulse et les sauvegardes AVANT --execute.');
            $this->line('Les autres motifs (bounce, failed, awaiting_dispatch, submitted, delayed) ne sont jamais repris ici.');
            return self::SUCCESS;
        }

        // Aucun nouveau destinataire. Sélection bornée sur les références
        // réellement valides, même si les premiers IDs sont en quarantaine.
        $cadence = max(1, (int) config('services.mailpulse.mail_per_minute', 30));
        $reprogrammes = 0;
        $ignores = 0;
        foreach ($ids as $idLivraison) {
            $ok = DB::transaction(function () use ($idLivraison, $annonce, $tenant, $cadence, &$reprogrammes): bool {
                $ligne = DB::table('esbtp_annonce_email_deliveries')
                    ->where('id', $idLivraison)
                    ->where('annonce_id', $annonce->id)
                    ->lockForUpdate()->first();

                if (! $ligne || $ligne->status !== 'retrying'
                    || $ligne->error_code !== 'local_rate_limit'
                    || $ligne->submitted_at !== null || $ligne->mailpulse_message_id !== null
                    || $ligne->provider_delivered_at !== null || $ligne->provider_failed_at !== null
                    || ! $this->referenceValide($ligne, $tenant)
                    || SuppressionCourrielAnnonce::estBloquee((string) $ligne->email)) {
                    return false;
                }

                // Atomic with inserting the database queue job on the SAME DB
                // connection. On failure, rollback BOTH the state and the job.
                $changed = DB::table('esbtp_annonce_email_deliveries')
                    ->where('id', $ligne->id)
                    ->where('status', 'retrying')
                    ->where('error_code', 'local_rate_limit')
                    ->whereNull('submitted_at')
                    ->whereNull('mailpulse_message_id')
                    ->update([
                        'status' => 'pending',
                        'error_code' => 'recovered_from_sync',
                        'processing_at' => null,
                        'updated_at' => now(),
                    ]);
                if ($changed !== 1) return false;

                EnvoyerAnnonceCourriel::dispatch((int) $ligne->id)
                    ->onConnection('database')
                    ->onQueue('annonces')
                    ->delay(now()->addSeconds(intdiv($reprogrammes, $cadence) * 60));
                $reprogrammes++;
                return true;
            }, 3);
            if (! $ok) $ignores++;
        }

        $this->info("Réenfilés : {$reprogrammes} · ignorés (modifiés ou non prouvés) : {$ignores}.");
        $this->warn('Aucun courriel émis par cette commande. Seul le worker --queue=annonces réalisera les soumissions.');
        return self::SUCCESS;
    }

    private function referenceValide(object $ligne, string $tenant): bool
    {
        $attendue = ReferenceCourrielAnnonce::nouvelle(
            $tenant, (int) $ligne->annonce_id, (int) $ligne->id
        );

        return $attendue !== null
            && is_string($ligne->mailpulse_request_id)
            && hash_equals($attendue, $ligne->mailpulse_request_id);
    }
}
