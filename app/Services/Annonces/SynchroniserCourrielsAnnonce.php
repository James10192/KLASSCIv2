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

        foreach ($cibles as $cible) {
            if (microtime(true) - $debut >= max(1, $budget)) {
                $bilan['bloque'] = 'budget_epuise';
                break;
            }
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

            if (in_array($status, ['delivered', 'read'], true) && !empty($etat['delivered_at'])) {
                $correction += ['status' => 'delivered', 'provider_delivered_at' => $etat['delivered_at']];
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
                    'provider_delayed_at' => is_string($horodatage) ? $horodatage : now(),
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

        return $bilan;
    }
}
