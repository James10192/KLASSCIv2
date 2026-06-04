<?php

namespace App\Domain\Comptabilite\Reconciliation\Services;

use App\Domain\Comptabilite\Reconciliation\Models\CashCount;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Enums\ReconciliationSessionStatus;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Service de base pour les sessions de réconciliation.
 *
 * PR1 = open / list / show / computeMontantSysteme.
 * PR2 ajoutera : review / approve / close / reopen avec checks OHADA.
 */
class ReconciliationSessionService
{
    /**
     * Ouvre une nouvelle session draft.
     *
     * @param User $user Comptable qui ouvre
     * @param string|null $frequency `daily|weekly|monthly` — défaut depuis settings
     * @param string|null $startDate ISO date — défaut = aujourd'hui (ou début période)
     */
    public function open(User $user, ?string $frequency = null, ?string $startDate = null): ReconciliationSession
    {
        $frequency = $frequency
            ?: SettingsHelper::get('comptabilite.reconciliation.frequency', 'daily');

        if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
            throw new \InvalidArgumentException("Fréquence invalide : {$frequency}");
        }

        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$annee) {
            throw new \DomainException('Aucune année universitaire courante configurée.');
        }

        [$periodStart, $periodEnd] = $this->resolvePeriod($frequency, $startDate);

        // Anti race-condition #2 : 1 draft max par (période, fréquence).
        $existing = ReconciliationSession::where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->where('frequency', $frequency)
            ->where('status', ReconciliationSessionStatus::DRAFT->value)
            ->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $frequency, $annee, $periodStart, $periodEnd) {
            $code = ReconciliationSession::reserveCode($annee->id);
            return ReconciliationSession::create([
                'code' => $code,
                'frequency' => $frequency,
                'annee_universitaire_id' => $annee->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => ReconciliationSessionStatus::DRAFT->value,
                'opened_by' => $user->id,
                'opened_at' => now(),
            ]);
        });
    }

    /**
     * Calcule le montant système (paiements validés non-soft-deleted) pour un mode
     * sur la fenêtre de la session. Snapshot = capturé au moment du save de cash_count.
     */
    public function computeMontantSysteme(ReconciliationSession $session, string $modePaiement): float
    {
        return (float) ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->where('status', 'validé')
            ->where('mode_paiement', $modePaiement)
            ->whereBetween('date_paiement', [$session->period_start, $session->period_end])
            ->sum('montant');
    }

    /**
     * Saisit un comptage caisse pour un mode donné. Crée ou update la ligne.
     */
    public function recordCashCount(
        ReconciliationSession $session,
        User $user,
        string $modePaiement,
        float $montantCompte,
        ?string $notes = null
    ): CashCount {
        if (!$session->isModifiable()) {
            throw new \DomainException("Session {$session->code} non modifiable (status {$session->status->value}).");
        }

        $montantSysteme = $this->computeMontantSysteme($session, $modePaiement);

        return CashCount::updateOrCreate(
            [
                'reconciliation_session_id' => $session->id,
                'mode_paiement' => $modePaiement,
            ],
            [
                'montant_compte' => $montantCompte,
                'montant_systeme' => $montantSysteme,
                'counted_by' => $user->id,
                'counted_at' => now(),
                'notes' => $notes,
            ],
        );
    }

    /**
     * Calcule [start, end] pour une fréquence + une date de référence.
     *
     * @return array{0:string,1:string}
     */
    public function resolvePeriod(string $frequency, ?string $refDate = null): array
    {
        $ref = $refDate ? CarbonImmutable::parse($refDate) : CarbonImmutable::today();

        return match ($frequency) {
            'daily' => [$ref->toDateString(), $ref->toDateString()],
            'weekly' => [
                $ref->startOfWeek()->toDateString(),
                $ref->endOfWeek()->toDateString(),
            ],
            'monthly' => [
                $ref->startOfMonth()->toDateString(),
                $ref->endOfMonth()->toDateString(),
            ],
            default => throw new \InvalidArgumentException("Fréquence inconnue : {$frequency}"),
        };
    }
}
