<?php

namespace App\Services\Annonces;

use App\Jobs\Annonces\EnvoyerAnnonceCourriel;
use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A hard bounce is NEVER retried to its original address. The school must
 * correct an eligible student's address first, then check Resend suppressions.
 * The bounced delivery and its reference remain untouched; a NEW delivery
 * and a NEW immutable attempt identity are recorded in one DB transaction.
 */
final class CorrigerCourrielAnnonce
{
    public function __construct(
        private readonly CiblageCourrielsAnnonce $ciblage,
        private readonly VerifierDestinataireMailPulse $suppression
    ) {}

    /** @return array{delivery_id:int,email:string,reference:string} */
    public function executer(ESBTPAnnonce $annonce, int $ancienneId, int $etudiantId,
        string $confirmation, string $motif, int $acteurId): array
    {
        $tenant = trim((string) config('app.tenant_code', ''));
        if (! config('services.mailpulse.announcement_retries_enabled', false)
            || config('queue.default') !== 'database'
            || $tenant === '' || $tenant === 'default') {
            throw ValidationException::withMessages(['relance' =>
                'Les relances corrigées exigent MailPulse v3 et une file database activée.']);
        }
        if (! $annonce->is_published || ! $annonce->email_notification_enabled
            || $annonce->isExpired() || $annonce->date_publication?->isFuture()) {
            throw ValidationException::withMessages(['relance' => 'Cette annonce ne peut plus être diffusée.']);
        }

        $etudiant = $this->ciblage->etudiants($annonce)
            ->where('esbtp_etudiants.id', $etudiantId)->first();
        if (! $etudiant) {
            throw ValidationException::withMessages(['etudiant_id' => 'Étudiant hors du public autorisé.']);
        }
        $nouvelle = CiblageCourrielsAnnonce::adresse($etudiant);
        if ($nouvelle === null || ! hash_equals($nouvelle, strtolower(trim($confirmation)))) {
            throw ValidationException::withMessages(['confirmation_email' =>
                "Confirmez l'adresse actuelle de l'étudiant dans KLASSCI."]);
        }

        $ancien = DB::table('esbtp_annonce_email_deliveries')
            ->where('annonce_id', $annonce->getKey())->where('id', $ancienneId)->first();
        if (! $ancien || $ancien->status !== 'failed'
            || ! in_array($ancien->error_code, ['email_bounced', 'bounced'], true)
            || $ancien->provider_delivered_at !== null || $nouvelle === strtolower($ancien->email)) {
            throw ValidationException::withMessages(['relance' =>
                'Seule une adresse réellement corrigée après rebond est éligible.']);
        }

        // Bind the old address to the same eligible student: the administrator
        // may update email_personnel while the old institutional address remains.
        $anciensChamps = array_map(
            fn ($v) => strtolower(trim((string) $v)),
            [$etudiant->email, $etudiant->email_personnel, $etudiant->user?->email]
        );
        if (! in_array(strtolower($ancien->email), $anciensChamps, true)) {
            throw ValidationException::withMessages(['relance' =>
                "L'ancienne adresse n'est plus liée à cet étudiant : rapprochement manuel nécessaire."]);
        }

        // This GET is read-only; 404 from the official Resend suppression API
        // means absent from the suppression list. Any uncertainty fails closed.
        if (! $this->suppression->estAutorise($nouvelle, $tenant)) {
            throw ValidationException::withMessages(['confirmation_email' =>
                "Adresse supprimée chez le fournisseur ou vérification indisponible. Aucun envoi."]);
        }

        return DB::transaction(function () use ($annonce, $ancienneId, $nouvelle, $motif, $acteurId, $tenant) {
            $source = DB::table('esbtp_annonce_email_deliveries')
                ->where('annonce_id', $annonce->id)->where('id', $ancienneId)
                ->lockForUpdate()->first();
            if (! $source || $source->status !== 'failed'
                || ! in_array($source->error_code, ['email_bounced','bounced'], true)
                || $source->provider_delivered_at !== null) {
                throw ValidationException::withMessages(['relance' => 'Le statut vient de changer.']);
            }
            if (DB::table('esbtp_annonce_email_attempts')
                ->where('replaces_delivery_id', $ancienneId)->exists()) {
                throw ValidationException::withMessages(['relance' => 'Correction déjà enregistrée.']);
            }
            if (DB::table('esbtp_annonce_email_deliveries')
                ->where('annonce_id', $annonce->id)->where('email', $nouvelle)->exists()) {
                throw ValidationException::withMessages(['confirmation_email' =>
                    'Cette adresse figure déjà parmi les destinataires de l’annonce.']);
            }

            $now = now();
            $id = DB::table('esbtp_annonce_email_deliveries')->insertGetId([
                'annonce_id' => $annonce->id,
                'email' => $nouvelle,
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $key = ReferenceCourrielAnnonce::tentative($tenant, $annonce->id, $id, 2);
            if ($key === null) {
                throw ValidationException::withMessages(['relance' => 'Identité MailPulse invalide.']);
            }
            DB::table('esbtp_annonce_email_deliveries')->where('id', $id)->update([
                'mailpulse_request_id' => $key,
            ]);

            $originalKey = ReferenceCourrielAnnonce::pourRapprochement($source, $tenant);
            if (! $originalKey) {
                throw ValidationException::withMessages(['relance' => 'Référence historique inconnue.']);
            }
            DB::table('esbtp_annonce_email_attempts')->insertOrIgnore([
                'annonce_id' => $annonce->id, 'delivery_id' => $source->id,
                'attempt_number' => 1, 'tenant_code' => $tenant,
                'recipient_email' => $source->email,
                'idempotency_key' => $originalKey, 'state' => 'failed',
                'provider_message_id' => $source->mailpulse_message_id,
                'failure_code' => $source->error_code,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('esbtp_annonce_email_attempts')->insert([
                'annonce_id' => $annonce->id, 'delivery_id' => $id,
                'replaces_delivery_id' => $source->id,
                'attempt_number' => 2, 'tenant_code' => $tenant,
                'recipient_email' => $nouvelle,
                'idempotency_key' => $key, 'state' => 'queued',
                'requested_by' => $acteurId, 'reason' => mb_substr($motif, 0, 500),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            // The database queue writes to the SAME DB transaction. No new
            // message if either insert fails. The worker checks suppressions again.
            EnvoyerAnnonceCourriel::dispatch($id)->onConnection('database')
                ->onQueue('annonces')->delay(now()->addSeconds(5));

            return ['delivery_id' => (int) $id, 'email' => $nouvelle, 'reference' => $key];
        }, 3);
    }
}
