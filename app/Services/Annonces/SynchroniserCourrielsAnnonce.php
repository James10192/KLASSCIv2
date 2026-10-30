<?php

namespace App\Services\Annonces;

use App\Services\MailPulse\MailPulseStatutsMessages;
use App\Services\MailPulse\RefusMailPulse;
use Illuminate\Support\Facades\DB;

/**
 * MailPulse est la source de verite des confirmations de remise.
 * Cette lecture ne soumet JAMAIS de nouveaux messages.
 */
final class SynchroniserCourrielsAnnonce
{
    private const TERMINAUX = ['delivered', 'failed'];
    private const FENETRE_JOURS = 30;

    public function __construct(private readonly MailPulseStatutsMessages $statuts)
    {
    }

    /** @return array<string,int|string|null> */
    public function synchroniser(int $max = 60, int $budget = 45): array
    {
        $debut = microtime(true);
        $bilan = ['lues' => 0, 'delivrees' => 0, 'retardees' => 0,
            'echecs' => 0, 'en_transit' => 0, 'erreurs' => 0, 'bloque' => null];
        $cibles = DB::table('esbtp_annonce_email_deliveries')
            ->whereNotNull('mailpulse_message_id')
            ->whereIn('status', ['submitted', 'awaiting_dispatch', 'delayed'])
            ->where('created_at', '>=', now()->subDays(self::FENETRE_JOURS))
            ->orderBy('updated_at')->orderBy('id')
            ->limit(max(1, $max))->get();

        $traitees = 0;
        foreach ($cibles as $cible) {
            if (microtime(true) - $debut >= max(1, $budget)) {
                $bilan['bloque'] = 'budget_epuise';
                break;
            }
            $traitees++;
            $etat = $this->statuts->lire((string) $cible->mailpulse_message_id);
            if (is_string($etat)) {
                if (RefusMailPulse::bloquant($etat)) {
                    $bilan['bloque'] = $etat;
                    break;
                }
                $bilan['erreurs']++;
                continue;
            }
            $bilan['lues']++;
            $status = $etat['status'];
            // Le rapprochement borne par id, message et etat ne peut pas
            // effacer un resultat concurrent venant d'un webhook.
            $ligne = DB::table('esbtp_annonce_email_deliveries')
                ->where('id', $cible->id)
                ->where('mailpulse_message_id', $cible->mailpulse_message_id)
                ->whereIn('status', ['submitted', 'awaiting_dispatch', 'delayed']);
            $correction = ['updated_at' => now()];

            $dateRemise = $this->normaliserDate($etat['delivered_at'] ?? null);
            if (in_array($status, ['delivered', 'read'], true) && $dateRemise !== null) {
                $correction += ['status' => 'delivered', 'provider_delivered_at' => $dateRemise];
                $bilan['delivrees']++;
            } elseif (in_array($status, ['failed', 'cancelled', 'template_required'], true)) {
                $correction += [
                    'status' => 'failed', 'error_code' => mb_substr($etat['error_code'] ?: $status, 0, 64),
                    'provider_failed_at' => now(),
                ];
                $bilan['echecs']++;
            } elseif (!empty($etat['delivery_delays'])) {
                $horodatage = $etat['delivery_delays'][0]['occurred_at'] ?? null;
                $correction += [
                    'status' => 'delayed',
                    'provider_delayed_at' => $this->normaliserDate($horodatage) ?? now(),
                ];
                $bilan['retardees']++;
            } else {
                if ($status === 'sent' && $cible->status === 'awaiting_dispatch') {
                    $correction += ['status' => 'submitted', 'submitted_at' => now()];
                }
                $bilan['en_transit']++;
            }
            $ligne->update($correction);
        }

        // A crashed worker can strand 'processing' forever. Its outcome is
        // UNKNOWN, including when the provider accepted the email just before
        // the crash. Quarantine old leases; NEVER turn these back into pending.
        // Compare-and-set and the worker's pre-HTTP transition prevent a late
        // worker from sending after losing ownership of this row.
        $minutes = max(15, (int) config('services.mailpulse.announcement_stale_minutes', 30));
        $cutoff = now()->subMinutes($minutes);
        // Never lock or rewrite an unbounded historic campaign in one cron.
        // Read a small, indexed candidate page, then CAS every selected row.
        $idsBloques = DB::table('esbtp_annonce_email_deliveries')
            ->where('status', 'processing')
            ->whereNotNull('processing_at')
            ->where('processing_at', '<=', $cutoff)
            ->whereNull('submitted_at')
            ->whereNull('mailpulse_message_id')
            ->orderBy('processing_at')->orderBy('id')
            ->limit(max(1, min(60, $max)))
            ->pluck('id');
        $bilan['quarantaines'] = $idsBloques->isEmpty() ? 0
            : DB::table('esbtp_annonce_email_deliveries')
                ->whereIn('id', $idsBloques)
                ->where('status', 'processing')
                ->where('processing_at', '<=', $cutoff)
                ->whereNull('submitted_at')
                ->whereNull('mailpulse_message_id')
                ->update([
                    'status' => 'awaiting_dispatch',
                    'error_code' => 'queue_outcome_unknown',
                    'processing_at' => null,
                    'updated_at' => now(),
                ]);

        // Second passage EN LECTURE SEULE : retrouver par clé initiale les
        // soumissions HTTP dont le message_id s'est perdu. Ne jamais faire POST.
        $reste = max(0, max(1, $max) - $traitees);
        if ($bilan['bloque'] === null && $reste > 0) {
            $incertaines = DB::table('esbtp_annonce_email_deliveries')
                ->whereNull('mailpulse_message_id')
                ->where('status', 'awaiting_dispatch')
                ->whereIn('error_code', ['submission_outcome_unknown', 'queue_outcome_unknown'])
                ->where('created_at', '>=', now()->subDays(self::FENETRE_JOURS))
                ->orderBy('updated_at')->orderBy('id')
                ->limit($reste)->get();

            $tenant = trim((string) config('app.tenant_code', ''));
            foreach ($incertaines as $cible) {
                if ($tenant === '' || $tenant === 'default') {
                    $bilan['bloque'] = 'tenant_unconfigured';
                    break;
                }
                if (microtime(true) - $debut >= max(1, $budget)) {
                    $bilan['bloque'] = 'budget_epuise';
                    break;
                }
                $reference = 'klassci-annonce-'.$cible->annonce_id.'-courriel-'.$cible->id;
                $etat = $this->statuts->lireParReference($reference, $tenant);
                if (is_string($etat)) {
                    if ($etat === 'introuvable') {
                        // Une absence de résultat est incertaine : NE PAS relancer.
                        $bilan['sans_preuve'] = ($bilan['sans_preuve'] ?? 0) + 1;
                        continue;
                    }
                    if (RefusMailPulse::bloquant($etat)) {
                        $bilan['bloque'] = $etat;
                        break;
                    }
                    $bilan['erreurs']++;
                    continue;
                }
                if (! is_string($etat['id'] ?? null) || trim($etat['id']) === ''
                    || ($etat['recipient'] ?? null) !== strtolower(trim((string) $cible->email))) {
                    $bilan['erreurs']++;
                    continue;
                }

                $correction = ['mailpulse_message_id' => $etat['id'], 'updated_at' => now()];
                $statut = (string) ($etat['status'] ?? '');
                $dateRemise = $this->normaliserDate($etat['delivered_at'] ?? null);
                if (in_array($statut, ['delivered', 'read'], true) && $dateRemise !== null) {
                    $correction += ['status' => 'delivered', 'provider_delivered_at' => $dateRemise, 'error_code' => null];
                } elseif (in_array($statut, ['failed', 'cancelled', 'template_required'], true)) {
                    $correction += [
                        'status' => 'failed',
                        'error_code' => mb_substr((string) (($etat['error_code'] ?? null) ?: $statut), 0, 64),
                        'provider_failed_at' => now(),
                    ];
                } elseif (in_array($statut, ['sent', 'delivered', 'read'], true)) {
                    $correction += ['status' => 'submitted', 'submitted_at' => now(), 'error_code' => null];
                }
                // Les autres états demeurent en quarantaine même avec un ID.
                $ecrit = DB::table('esbtp_annonce_email_deliveries')
                    ->where('id', $cible->id)
                    ->where('annonce_id', $cible->annonce_id)
                    ->whereNull('mailpulse_message_id')
                    ->where('status', 'awaiting_dispatch')
                    ->update($correction);
                if ($ecrit === 1) {
                    $bilan['lues']++;
                    if (($correction['status'] ?? null) === 'delivered') {
                        $bilan['delivrees']++;
                    } elseif (($correction['status'] ?? null) === 'failed') {
                        $bilan['echecs']++;
                    } else {
                        $bilan['en_transit']++;
                    }
                }
            }
        }

        return $bilan;
    }

    private function normaliserDate(mixed $valeur): ?string
    {
        if (! is_string($valeur) || trim($valeur) === '') {
            return null;
        }
        try {
            // DATETIME MySQL/MariaDB n'accepte pas les ISO8601 avec T et +00:00.
            return \Illuminate\Support\Carbon::parse($valeur)
                ->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            // Ne jamais affirmer une livraison sur une date fournisseur illisible.
            return null;
        }
    }

}
