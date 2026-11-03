<?php

namespace App\Console\Commands;

use App\Jobs\Annonces\EnvoyerAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use App\Services\Annonces\ReferenceCourrielAnnonce;
use App\Services\Annonces\SuppressionCourrielAnnonce;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reprise conservatrice des jobs perdus par QUEUE_CONNECTION=sync.
 *
 * Jamais de nouvelle soumission dans cette commande : seul un worker asynchrone
 * peut franchir la frontière HTTP, et les preuves fournisseurs priment toujours.
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
        if ($operation && ! $this->environnementPret($tenant)) {
            return self::FAILURE;
        }

        $base = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->id)
            ->where('status', 'retrying')
            ->where('error_code', 'local_rate_limit')
            ->whereNull('mailpulse_message_id')
            ->whereNull('submitted_at')
            ->whereNull('provider_failed_at')
            ->whereNull('provider_delivered_at');

        [$ids, $candidats, $stricts, $invalides, $supprimes] =
            $this->selectionnerCandidats($base, $tenant, (int) $limite);

        $this->info(sprintf(
            'Annonce %d · tenant %s · %d candidats locaux · %d strictement récupérables · %s.',
            $annonce->id, $tenant ?: '?', $candidats, $stricts, $operation ? 'EXECUTION' : 'SIMULATION'
        ));
        $this->line(sprintf(
            'Références v2 invalides/absentes : %d ; adresses factices, déjà rebondies, supprimées ou en plainte : %d.',
            $invalides, $supprimes
        ));
        if (! $operation) {
            $this->line('Vérifiez QUEUE_CONNECTION=database, le worker, le quota MailPulse et les sauvegardes AVANT --execute.');
            $this->line('Les autres motifs (bounce, failed, awaiting_dispatch, submitted, delayed) ne sont jamais repris ici.');
            return self::SUCCESS;
        }

        $cadence = max(1, (int) config('services.mailpulse.mail_per_minute', 30));
        $reprogrammes = 0;
        $ignores = 0;
        foreach ($ids as $idLivraison) {
            if ($this->reenfiler($idLivraison, $annonce, $tenant, $cadence, $reprogrammes)) {
                $reprogrammes++;
            } else {
                $ignores++;
            }
        }
        $this->info("Réenfilés : {$reprogrammes} · ignorés (modifiés ou non prouvés) : {$ignores}.");
        $this->warn('Aucun courriel émis par cette commande. Seul le worker --queue=annonces réalisera les soumissions.');
        return self::SUCCESS;
    }

    private function environnementPret(string $tenant): bool
    {
        if (config('queue.default') !== 'database' || ! Schema::hasTable('jobs')) {
            $this->error('ARRÊT : QUEUE_CONNECTION=database et table jobs requises. Aucun envoi.');
            return false;
        }
        // Statut et job doivent participer à une même transaction SQL. Un
        // after_commit=true introduirait une fenêtre de perte en cas de crash.
        $connexionQueue = config('queue.connections.database.connection');
        if (config('queue.connections.database.after_commit') !== false
            || ($connexionQueue && $connexionQueue !== DB::getDefaultConnection())) {
            $this->error('ARRÊT : la queue database doit utiliser la connexion DB courante avec after_commit=false.');
            return false;
        }
        if ($tenant === '' || $tenant === 'default'
            || ! preg_match('/^[a-z0-9][a-z0-9_-]{1,62}$/D', $tenant)
            || ! hash_equals($tenant, (string) $this->option('tenant'))) {
            $this->error('ARRÊT : --tenant doit être égal au TENANT_CODE réel de cette instance.');
            return false;
        }

        return true;
    }

    /** @return array{0:list<int>,1:int,2:int,3:int,4:int} */
    private function selectionnerCandidats(Builder $base, string $tenant, int $limite): array
    {
        $candidats = (clone $base)->count();
        $stricts = 0;
        $invalides = 0;
        $supprimes = 0;
        $ids = [];
        $bloquees = SuppressionCourrielAnnonce::adressesBloquees();

        // La simulation applique exactement les mêmes critères que l'exécution
        // et ne laisse pas les références invalides monopoliser un petit lot.
        (clone $base)->orderBy('id')->chunkById(200, function ($lignes) use (
            $tenant, $limite, &$stricts, &$invalides, &$supprimes, &$ids, $bloquees
        ): void {
            foreach ($lignes as $ligne) {
                if (! $this->referenceValide($ligne, $tenant)) {
                    $invalides++;
                    continue;
                }
                $email = strtolower(trim((string) $ligne->email));
                if (isset($bloquees[$email]) || SuppressionCourrielAnnonce::estFactice($email)) {
                    $supprimes++;
                    continue;
                }
                $stricts++;
                if (count($ids) < $limite) {
                    $ids[] = (int) $ligne->id;
                }
            }
        });

        return [$ids, $candidats, $stricts, $invalides, $supprimes];
    }

    private function reenfiler(
        int $idLivraison,
        ESBTPAnnonce $annonce,
        string $tenant,
        int $cadence,
        int $numero
    ): bool {
        return DB::transaction(function () use ($idLivraison, $annonce, $tenant, $cadence, $numero): bool {
            $ligne = DB::table('esbtp_annonce_email_deliveries')
                ->where('id', $idLivraison)->where('annonce_id', $annonce->id)
                ->lockForUpdate()->first();

            if (! $ligne || $ligne->status !== 'retrying'
                || $ligne->error_code !== 'local_rate_limit'
                || $ligne->submitted_at !== null || $ligne->mailpulse_message_id !== null
                || $ligne->provider_delivered_at !== null || $ligne->provider_failed_at !== null
                || ! $this->referenceValide($ligne, $tenant)
                || SuppressionCourrielAnnonce::estBloquee((string) $ligne->email)) {
                return false;
            }

            // Le commit ou le rollback porte sur le statut ET sur le job SQL.
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
            if ($changed !== 1) {
                return false;
            }

            EnvoyerAnnonceCourriel::dispatch((int) $ligne->id)
                ->onConnection('database')->onQueue('annonces')
                ->delay(now()->addSeconds(intdiv($numero, $cadence) * 60));

            return true;
        }, 3);
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
