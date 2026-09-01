<?php

namespace App\Services\Caisse;

use App\Enums\CashSessionStatus;
use App\Enums\ModePaiement;
use App\Exceptions\CaisseCloturee;
use App\Models\ESBTPCashSession;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashSessionService
{
    public function snapshot(User $user, ?Carbon $date = null): array
    {
        $this->autoCloseStale($user);
        $jour = ($date ?? now())->toDateString();
        $session = $this->lazyOpen($user, $jour);
        $aggregat = $this->aggregat($user->id, $jour);

        return [
            'session' => $session,
            'aggregat' => $aggregat,
            'paiements' => $this->lignes($user->id, $jour),
        ];
    }

    public function assertEspecesAutorisees(User $user, ?string $mode): void
    {
        $canon = ModePaiement::fromLegacy($mode);
        if ($canon === null || ! $canon->isDrawer()) {
            return;
        }

        $this->ensureOpen($user);
    }

    public function ensureOpen(User $user, ?string $jour = null): ESBTPCashSession
    {
        $this->autoCloseStale($user);
        $session = $this->lazyOpen($user, $jour ?? now()->toDateString());

        if ($session->isLocked()) {
            throw new CaisseCloturee(
                'Votre caisse du jour est déjà clôturée. Contactez la comptabilité pour enregistrer ce versement espèces.'
            );
        }

        return $session;
    }

    public function close(User $user, float $countedAmount, ?string $notes = null, ?string $jour = null): ESBTPCashSession
    {
        $session = $this->ensureOpen($user, $jour);
        $aggregat = $this->aggregat($user->id, $session->business_date->toDateString());
        $expected = (float) $aggregat['especes'];

        $session->fill([
            'status' => CashSessionStatus::CLOSED,
            'closed_at' => now(),
            'counted_amount' => round($countedAmount, 2),
            'expected_amount' => round($expected, 2),
            'variance' => round($countedAmount - $expected, 2),
            'notes' => $notes,
        ]);
        $session->save();

        return $session;
    }

    public function autoCloseStale(User $user): void
    {
        $stale = ESBTPCashSession::query()
            ->where('cashier_user_id', $user->id)
            ->where('status', CashSessionStatus::OPEN)
            ->whereDate('business_date', '<', now()->toDateString())
            ->get();

        foreach ($stale as $session) {
            $aggregat = $this->aggregat($user->id, $session->business_date->toDateString());
            $session->fill([
                'status' => CashSessionStatus::AUTO_CLOSED,
                'closed_at' => now(),
                'expected_amount' => round((float) $aggregat['especes'], 2),
            ]);
            $session->save();
        }
    }

    /**
     * @return array{total: float, especes: float, count: int, annules: int, par_mode: array<string, array{label: string, total: float, count: int}>}
     */
    public function aggregat(int $userId, string $jour): array
    {
        $lignes = $this->queryJour($userId, $jour)->encaissements()->get();
        $parMode = [];
        $total = 0.0;
        $especes = 0.0;
        $valides = 0;

        foreach ($lignes as $paiement) {
            if ($paiement->status !== 'validé') {
                continue;
            }
            $montant = (float) $paiement->montant;
            $total += $montant;
            $valides++;
            $canon = ModePaiement::fromLegacy((string) $paiement->mode_paiement) ?? ModePaiement::MOBILE_MONEY;
            $cle = $canon->value;
            if (! isset($parMode[$cle])) {
                $parMode[$cle] = ['label' => $canon->label(), 'total' => 0.0, 'count' => 0];
            }
            $parMode[$cle]['total'] += $montant;
            $parMode[$cle]['count']++;
            if ($canon->isDrawer()) {
                $especes += $montant;
            }
        }

        $annules = $this->queryJour($userId, $jour)->where('status', 'rejeté')->count();

        return [
            'total' => round($total, 2),
            'especes' => round($especes, 2),
            'count' => $valides,
            'annules' => $annules,
            'par_mode' => $parMode,
        ];
    }

    public function lignes(int $userId, string $jour): Collection
    {
        return $this->queryJour($userId, $jour)
            ->with(['etudiant', 'inscription'])
            ->orderBy('created_at')
            ->get();
    }

    private function lazyOpen(User $user, string $jour): ESBTPCashSession
    {
        return ESBTPCashSession::query()->firstOrCreate(
            [
                'cashier_user_id' => $user->id,
                'business_date' => $jour,
            ],
            [
                'status' => CashSessionStatus::OPEN,
                'opened_at' => now(),
            ]
        );
    }

    private function queryJour(int $userId, string $jour)
    {
        return ESBTPPaiement::query()
            ->ownedBy($userId)
            ->whereDate('created_at', $jour);
    }
}
