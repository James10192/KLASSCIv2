<?php

namespace App\Services\Annonces;

use App\Jobs\Annonces\EnvoyerTentativeAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use App\Models\Setting;
use App\Services\MailPulse\MailPulseStatutsMessages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One guarded entry point for an operator-initiated attempt.
 * No recipient is sent from the HTTP request.
 */
final class RelanceCourrielsAnnonce
{
    private const REFUS_AVANT_FOURNISSEUR = ['template_not_configured', 'channel_not_configured'];
    private const SUPPRESSIONS = ['email_bounced', 'email_suppressed', 'email_complained'];

    public function __construct(private readonly MailPulseStatutsMessages $mailpulse) {}

    public function autorisee(): bool
    {
        return config('queue.default') === 'database'
            && Schema::hasTable('esbtp_annonce_email_attempts')
            && Schema::hasTable('esbtp_annonce_email_attempt_events')
            && filter_var(Setting::get('annonces_email_retry_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function limite(): int
    {
        return max(1, min(50, (int) Setting::get('annonces_email_retry_max_batch', '10')));
    }

    /** @return array{acceptes:int,exclus:int,dupliques:int,motif:string|null} */
    public function programmer(ESBTPAnnonce $annonce, array $ids, int $auteur, string $requete): array
    {
        $resultat = ['acceptes' => 0, 'exclus' => 0, 'dupliques' => 0, 'motif' => null];
        if (! $this->autorisee() || ! $annonce->is_published
            || ! $annonce->email_notification_enabled || $annonce->isExpired()) {
            $resultat['motif'] = 'relance_non_disponible';
            return $resultat;
        }

        $tenant = (string) config('app.tenant_code', 'default');
        if ($tenant === 'default' || ! preg_match('/^[a-z0-9][a-z0-9_-]{1,62}$/D', $tenant)) {
            $resultat['motif'] = 'tenant_invalide';
            return $resultat;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === [] || count($ids) > $this->limite() || min($ids) < 1) {
            $resultat['motif'] = 'limite_lot';
            return $resultat;
        }

        foreach ($ids as $id) {
            $ligne = DB::table('esbtp_annonce_email_deliveries')
                ->where('annonce_id', $annonce->id)->where('id', $id)->first();
            if (! $ligne) { $resultat['exclus']++; continue; }

            // A repeated click has the same UUID, and cannot create attempt #3.
            if (DB::table('esbtp_annonce_email_attempts')
                ->where('delivery_id', $id)->where('request_token', $requete)->exists()) {
                $resultat['dupliques']++;
                continue;
            }

            if ($ligne->status !== 'failed'
                || ! in_array($ligne->error_code, self::REFUS_AVANT_FOURNISSEUR, true)
                || $ligne->provider_delivered_at !== null
                || DB::table('esbtp_annonce_email_deliveries')
                    ->where('email', $ligne->email)
                    ->where('status', 'failed')
                    ->whereIn('error_code', self::SUPPRESSIONS)->exists()) {
                $resultat['exclus']++;
                continue;
            }

            $last = DB::table('esbtp_annonce_email_attempts')
                ->where('delivery_id', $id)->orderByDesc('attempt_number')->first();
            if ($last && ($last->status !== 'failed'
                || ! in_array($last->error_code, self::REFUS_AVANT_FOURNISSEUR, true))) {
                $resultat['exclus']++;
                continue;
            }
            $precedente = $last?->external_reference ?? (string) $ligne->mailpulse_request_id;
            $numero = $last ? (int) $last->attempt_number + 1 : 2;
            // The previous attempt must be tenant-qualified; never retrofit
            // the historical key during an ambiguous provider outcome.
            if (($numero === 2 && $precedente !== ReferenceCourrielAnnonce::nouvelle($tenant, $annonce->id, $id))
                || ($numero > 2 && $precedente !== ReferenceCourrielAnnonce::tentative($tenant, $annonce->id, $id, $numero - 1))) {
                $resultat['exclus']++;
                continue;
            }
            $preuve = $this->mailpulse->lireParReference($precedente, $tenant);
            if (! is_array($preuve)
                || ($preuve['recipient'] ?? null) !== strtolower(trim((string) $ligne->email))
                || ($preuve['status'] ?? null) !== 'failed'
                || ! in_array($preuve['error_code'] ?? null, self::REFUS_AVANT_FOURNISSEUR, true)
                || ($preuve['id'] ?? null) !== ($last?->provider_message_id ?? $ligne->mailpulse_message_id)) {
                $resultat['exclus']++;
                continue;
            }

            $reference = ReferenceCourrielAnnonce::tentative($tenant, $annonce->id, $id, $numero);
            if ($reference === null) { $resultat['exclus']++; continue; }

            // The proof is rechecked against the locked snapshot. MailPulse
            // does a second authoritative gate immediately before its POST.
            $cree = DB::transaction(function () use ($annonce, $id, $auteur, $requete, $ligne, $last, $numero, $precedente, $reference): bool {
                $maintenant = now();
                $courant = DB::table('esbtp_annonce_email_deliveries')
                    ->where('id', $id)->where('annonce_id', $annonce->id)
                    ->lockForUpdate()->first();
                if (! $courant || $courant->status !== 'failed'
                    || $courant->email !== $ligne->email
                    || $courant->mailpulse_request_id !== $ligne->mailpulse_request_id
                    || $courant->mailpulse_message_id !== $ligne->mailpulse_message_id
                    || $courant->error_code !== $ligne->error_code
                    || DB::table('esbtp_annonce_email_attempts')
                        ->where('delivery_id', $id)->where('request_token', $requete)->exists()) {
                    return false;
                }
                $dernierActuel = DB::table('esbtp_annonce_email_attempts')
                    ->where('delivery_id', $id)->orderByDesc('attempt_number')->first();
                if (($dernierActuel?->id ?? null) !== ($last?->id ?? null)) return false;

                if (! $last) {
                    DB::table('esbtp_annonce_email_attempts')->insert([
                        'annonce_id' => $annonce->id, 'delivery_id' => $id,
                        'attempt_number' => 1,
                        'external_reference' => $precedente,
                        'previous_reference' => null,
                        'recipient_email' => $courant->email,
                        'status' => 'failed',
                        'provider_message_id' => $courant->mailpulse_message_id,
                        'error_code' => $courant->error_code,
                        'requested_by' => null,
                        'created_at' => $maintenant, 'updated_at' => $maintenant,
                    ]);
                }
                $tentativeId = DB::table('esbtp_annonce_email_attempts')->insertGetId([
                    'annonce_id' => $annonce->id, 'delivery_id' => $id,
                    'attempt_number' => $numero,
                    'external_reference' => $reference, 'previous_reference' => $precedente,
                    'recipient_email' => $courant->email, 'status' => 'queued',
                    'requested_by' => $auteur, 'request_token' => $requete,
                    'created_at' => $maintenant, 'updated_at' => $maintenant,
                ]);
                DB::table('esbtp_annonce_email_attempt_events')->insert([
                    'attempt_id' => $tentativeId, 'event' => 'requested',
                    'actor_id' => $auteur, 'created_at' => $maintenant,
                ]);
                // The database queue shares the tenant DB transaction: no
                // committed journal without its corresponding queued job.
                EnvoyerTentativeAnnonceCourriel::dispatch($tentativeId)
                    ->onConnection('database')->onQueue('annonces');
                return true;
            }, 3);

            $resultat[$cree ? 'acceptes' : 'dupliques']++;
        }
        return $resultat;
    }
}
