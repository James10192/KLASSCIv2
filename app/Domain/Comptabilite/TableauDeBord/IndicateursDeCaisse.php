<?php

namespace App\Domain\Comptabilite\TableauDeBord;

use App\Enums\ModePaiement;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPCashSession;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Les chiffres du tableau de bord de la caisse.
 *
 * Portée « guichet » : ce qu'UN agent a saisi, daté par `created_at` — c'est
 * le geste au guichet qui compte, pas la date de valeur. Les chiffres de
 * l'école entière (accueil comptable, analyse financière) viennent d'un seul
 * calcul, {@see \App\Actions\Comptabilite\BuildDashboardDataAction}.
 *
 * Tout est agrégé en base (SUM, COUNT, GROUP BY) : rien n'hydrate les
 * versements d'une période entière (rule premium-dashboard, exigence 9).
 * Le montant net retire les remboursements, comme partout ailleurs
 * ({@see ESBTPPaiement::sqlCashCase()}).
 */
class IndicateursDeCaisse
{
    /**
     * La journée d'un agent au guichet : par famille de mode, ce qui attend,
     * ce qu'il peut encore annuler, et sa session de caisse.
     *
     * @return array{
     *   session: array{statut: string|null, ouverte_a: string|null, fermee_a: string|null},
     *   especes: array{count: int, total: float},
     *   mobile: array{count: int, total: float},
     *   autres: array{count: int, total: float},
     *   total: float, count: int, hier: float,
     *   a_valider: int, a_valider_total: float, annulables: int,
     *   fenetre_annulation_minutes: int, peut_annuler: bool
     * }
     */
    public function journeeDuGuichet(User $user, Carbon $jour): array
    {
        $donnees = self::journeeVide($user);

        $session = ESBTPCashSession::query()
            ->where('cashier_user_id', $user->id)
            ->whereDate('business_date', $jour->toDateString())
            ->first();
        if ($session) {
            $donnees['session'] = [
                'statut' => $session->status?->value,
                'ouverte_a' => $session->opened_at?->format('H:i'),
                'fermee_a' => $session->closed_at?->format('H:i'),
            ];
        }

        $parMode = ESBTPPaiement::query()
            ->ownedBy($user->id)
            ->whereDate('created_at', $jour)
            ->where('status', 'validé')
            ->encaissements()
            ->groupBy('mode_paiement')
            ->select('mode_paiement', DB::raw('COUNT(*) as n'), DB::raw('SUM(montant) as total'))
            ->get();

        foreach ($parMode as $ligne) {
            $famille = self::familleDeMode((string) $ligne->mode_paiement);
            $donnees[$famille]['count'] += (int) $ligne->n;
            $donnees[$famille]['total'] += (float) $ligne->total;
        }

        $donnees['total'] = $this->netDu($this->guichet($user)->whereDate('created_at', $jour));
        $donnees['hier'] = $this->netDu($this->guichet($user)->whereDate('created_at', $jour->copy()->subDay()));
        $donnees['count'] = $donnees['especes']['count'] + $donnees['mobile']['count'] + $donnees['autres']['count'];

        $attente = ESBTPPaiement::query()
            ->ownedBy($user->id)
            ->whereDate('created_at', $jour)
            ->where('status', 'en_attente')
            ->encaissements()
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(montant), 0) as total')
            ->first();
        $donnees['a_valider'] = (int) ($attente->n ?? 0);
        $donnees['a_valider_total'] = round((float) ($attente->total ?? 0), 2);

        // Seuls les versements de la fenêtre peuvent encore s'annuler : on ne
        // passe par la policy que pour ceux-là, jamais pour la journée entière.
        if ($donnees['peut_annuler'] && $donnees['fenetre_annulation_minutes'] > 0) {
            $donnees['annulables'] = ESBTPPaiement::query()
                ->ownedBy($user->id)
                ->where('created_at', '>', now()->subMinutes($donnees['fenetre_annulation_minutes']))
                ->whereIn('status', ['en_attente', 'validé'])
                ->encaissements()
                ->get()
                ->filter(fn (ESBTPPaiement $p) => $user->can('cancelOwnRecent', $p))
                ->count();
        }

        foreach (['especes', 'mobile', 'autres'] as $famille) {
            $donnees[$famille]['total'] = round($donnees[$famille]['total'], 2);
        }

        return $donnees;
    }

    /**
     * Encaissé net par jour, sur les N derniers jours (aujourd'hui compris).
     * Portée guichet si un agent est donné, école sinon.
     *
     * @return array<int, array{jour: string, libelle: string, total: float}>
     */
    public function serieJournaliere(int $jours, ?User $agent = null): array
    {
        $fin = now()->startOfDay();
        $debut = $fin->copy()->subDays($jours - 1);
        $colonne = $agent ? 'created_at' : 'date_paiement';

        $base = $agent ? $this->guichet($agent) : $this->ecole();
        $totaux = $base
            ->whereDate($colonne, '>=', $debut)
            ->whereDate($colonne, '<=', $fin)
            ->groupBy(DB::raw("DATE($colonne)"))
            ->select(DB::raw("DATE($colonne) as jour"), DB::raw('SUM('.ESBTPPaiement::sqlCashCase().') as total'))
            ->pluck('total', 'jour');

        $serie = [];
        for ($d = $debut->copy(); $d->lte($fin); $d->addDay()) {
            $cle = $d->toDateString();
            $serie[] = [
                'jour' => $cle,
                'libelle' => $d->isoFormat('ddd D'),
                'total' => round((float) ($totaux[$cle] ?? 0), 2),
            ];
        }

        return $serie;
    }

    /**
     * Saisies de l'agent par heure sur la journée (validées ou en attente),
     * de 7 h à 18 h au moins, élargi si l'agent a saisi en dehors.
     *
     * @return array<int, array{heure: int, count: int}>
     */
    public function affluenceDuJour(User $user, Carbon $jour): array
    {
        $parHeure = ESBTPPaiement::query()
            ->ownedBy($user->id)
            ->whereDate('created_at', $jour)
            ->whereIn('status', ['validé', 'en_attente'])
            ->encaissements()
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->select(DB::raw('HOUR(created_at) as h'), DB::raw('COUNT(*) as n'))
            ->pluck('n', 'h');

        $debut = min(7, (int) ($parHeure->keys()->min() ?? 7));
        $fin = max(18, (int) ($parHeure->keys()->max() ?? 18));
        $serie = [];
        for ($h = $debut; $h <= $fin; $h++) {
            $serie[] = ['heure' => $h, 'count' => (int) ($parHeure[$h] ?? 0)];
        }

        return $serie;
    }

    /**
     * Variation en pourcentage, null quand la référence est nulle (un « +∞ % »
     * n'apprend rien à personne).
     */
    public static function variation(float $actuel, float $reference): ?float
    {
        if (abs($reference) < 0.01) {
            return null;
        }

        return round((($actuel - $reference) / abs($reference)) * 100, 1);
    }

    public static function journeeVide(?User $user): array
    {
        return [
            'session' => ['statut' => null, 'ouverte_a' => null, 'fermee_a' => null],
            'especes' => ['count' => 0, 'total' => 0.0],
            'mobile' => ['count' => 0, 'total' => 0.0],
            'autres' => ['count' => 0, 'total' => 0.0],
            'total' => 0.0,
            'count' => 0,
            'hier' => 0.0,
            'a_valider' => 0,
            'a_valider_total' => 0.0,
            'annulables' => 0,
            'fenetre_annulation_minutes' => (int) SettingsHelper::get('comptabilite.cancel_own_window_minutes', 5),
            'peut_annuler' => $user ? $user->can('paiements.cancel_own') : false,
        ];
    }

    /**
     * Espèces au tiroir ; portefeuilles mobiles ensemble ; le reste (virement,
     * chèque, valeur inconnue) à part, pour ne pas le faire passer pour du
     * mobile money.
     */
    public static function familleDeMode(string $mode): string
    {
        $canon = ModePaiement::fromLegacy($mode);
        if ($canon === null) {
            return 'autres';
        }
        if ($canon->isDrawer()) {
            return 'especes';
        }

        return $canon->estMobile() ? 'mobile' : 'autres';
    }

    private function guichet(User $agent): Builder
    {
        return ESBTPPaiement::query()->ownedBy($agent->id)->where('status', 'validé');
    }

    private function ecole(): Builder
    {
        return ESBTPPaiement::query()->where('status', 'validé');
    }

    private function netDu(Builder $requete): float
    {
        return round(ESBTPPaiement::netCashSum($requete), 2);
    }
}
