<?php

namespace App\Domain\Comptabilite\Paiements\Actions;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Models\NotificationReminder;
use App\Models\User;
use App\Notifications\PaiementHighAmountValidatedNotification;
use App\Services\GroupCacheInvalidator;
use App\Services\NotificationService;
use App\Support\WorkflowFlash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ValiderPaiement
{
    public function execute(ESBTPPaiement $paiement, ?User $validateur): ESBTPPaiement
    {
        DB::transaction(function () use ($paiement, $validateur) {
            $paiement->update([
                'status' => 'validé',
                'date_validation' => now(),
                'validateur_id' => $validateur?->id,
            ]);

            if ($paiement->type_paiement === 'reliquat' && $paiement->reliquat_detail_id) {
                $reliquat = ESBTPReliquatDetail::find($paiement->reliquat_detail_id);
                if ($reliquat) {
                    $nouveauMontantRegle = (float) $reliquat->montant_regle + (float) $paiement->montant;
                    $nouveauSolde = (float) $reliquat->montant_reliquat - $nouveauMontantRegle;

                    $reliquat->update([
                        'montant_regle' => $nouveauMontantRegle,
                        'statut' => $nouveauSolde <= 0 ? 'totalement_regle' : 'partiellement_regle',
                        'date_derniere_maj' => now(),
                    ]);
                }
            }
        });

        $paiement->refresh();

        try {
            app(GroupCacheInvalidator::class)->invalidate('paiement_validated');
        } catch (\Throwable $e) {
            Log::warning('Invalidation cache après validation paiement impossible', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->notifierMontantEleve($paiement, $validateur);

        try {
            $notifications = app(NotificationService::class);
            $notifications->notifyPaiementValide($paiement, $validateur);
            $notifications->notifyParentsPaiementValide($paiement);
        } catch (\Throwable $e) {
            Log::error('Erreur envoi notification paiement validé: '.$e->getMessage(), [
                'paiement_id' => $paiement->id,
            ]);
        }

        try {
            NotificationReminder::query()
                ->where('remindable_type', ESBTPPaiement::class)
                ->where('remindable_id', $paiement->id)
                ->first()?->deactivate();
        } catch (\Throwable $e) {
            Log::warning('Erreur désactivation reminder paiement', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            WorkflowFlash::dispatch(
                'paiement.validated',
                $validateur,
                ['inscription' => $paiement->inscription_id, 'paiement' => $paiement->id],
            );
        } catch (\Throwable $e) {
            Log::warning('WorkflowFlash après validation paiement impossible', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Paiement validé', [
            'paiement_id' => $paiement->id,
            'inscription_id' => $paiement->inscription_id,
            'montant' => $paiement->montant,
            'user_id' => $validateur?->id,
        ]);

        return $paiement;
    }

    private function notifierMontantEleve(ESBTPPaiement $paiement, ?User $validateur): void
    {
        try {
            $seuil = (int) SettingsHelper::get('comptabilite.notify_high_amount_threshold', 5000000);
            if ($seuil <= 0 || (float) $paiement->montant < $seuil) {
                return;
            }

            $destinataires = User::permission('comptabilite.notifications.high_amount')->get();
            if ($destinataires->isEmpty()) {
                return;
            }

            Notification::send(
                $destinataires,
                new PaiementHighAmountValidatedNotification($paiement, $validateur, $seuil),
            );

            Log::info('[S1.6] Notification gros paiement envoyée', [
                'paiement_id' => $paiement->id,
                'montant' => $paiement->montant,
                'threshold' => $seuil,
                'recipients_count' => $destinataires->count(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[S1.6] Échec notification gros paiement (non bloquant)', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
