<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Encaissements validés mois par mois, avec la période précédente de même
 * durée pour comparer. Renvoie directement le graphique : c'est la question
 * que l'on pose à cet outil.
 */
class EvolutionEncaissementsTool extends ChatbotTool
{
    public function name(): string
    {
        return 'evolution_encaissements';
    }

    public function description(): string
    {
        return "Encaissements validés (paiements des étudiants, avoirs déduits) mois par mois sur les N derniers mois, "
            . "mois en cours compris, avec le total, le nombre de versements et la comparaison avec les N mois précédents. "
            . "Affiche le graphique à l'utilisateur. À utiliser pour « évolution des paiements », « combien encaissé ce mois-ci », "
            . "« tendance des encaissements ». Pour le détail des versements, utilise search_payments.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'mois' => ['type' => 'integer', 'description' => 'Nombre de mois, de 2 à 24. Par défaut 6.'],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $n = min(max((int) ($args['mois'] ?? 6), 2), 24);
        $debut = Carbon::now()->startOfMonth()->subMonths($n - 1);
        $debutPrecedent = $debut->copy()->subMonths($n);

        $parMois = DB::table('esbtp_paiements')
            ->where('status', 'validé')
            ->whereNull('deleted_at')
            ->where('date_paiement', '>=', $debutPrecedent->toDateString())
            ->selectRaw("DATE_FORMAT(date_paiement, '%Y-%m') as mois, SUM(" . ESBTPPaiement::sqlCashCase() . ") as montant, COUNT(*) as nombre")
            ->groupBy('mois')
            ->pluck('montant', 'mois');
        $nombres = DB::table('esbtp_paiements')
            ->where('status', 'validé')
            ->whereNull('deleted_at')
            ->where('date_paiement', '>=', $debut->toDateString())
            ->selectRaw("DATE_FORMAT(date_paiement, '%Y-%m') as mois, COUNT(*) as nombre")
            ->groupBy('mois')
            ->pluck('nombre', 'mois');

        $lignes = [];
        $libelles = [];
        $valeurs = [];
        $total = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $mois = $debut->copy()->addMonths($i);
            $cle = $mois->format('Y-m');
            $montant = (float) ($parMois[$cle] ?? 0);
            $total += $montant;
            $libelle = ucfirst($mois->locale('fr')->isoFormat('MMM YY'));
            $libelles[] = $libelle;
            $valeurs[] = round($montant);
            $lignes[] = [
                'mois' => $libelle,
                'montant' => $this->formatFCFA($montant),
                'montant_brut' => round($montant),
                'versements' => (int) ($nombres[$cle] ?? 0),
                'en_cours' => $i === $n - 1,
            ];
        }

        $totalPrecedent = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $totalPrecedent += (float) ($parMois[$debutPrecedent->copy()->addMonths($i)->format('Y-m')] ?? 0);
        }
        $variation = $totalPrecedent > 0 ? round((($total - $totalPrecedent) / $totalPrecedent) * 100, 1) : null;

        return [
            'results' => $lignes,
            'count' => $n,
            'totaux' => [
                'total_periode' => $this->formatFCFA($total),
                'total_periode_precedente' => $this->formatFCFA($totalPrecedent),
                'variation_pourcent' => $variation,
                'note' => 'Le dernier mois est en cours : il n\'est pas complet.',
            ],
            'deep_link' => route('esbtp.paiements.index', [], false),
            'widget' => [
                'kind' => 'graphique',
                'type' => 'barres',
                'titre' => "Encaissements des {$n} derniers mois",
                'libelles' => $libelles,
                'series' => [['nom' => 'Encaissé', 'valeurs' => $valeurs]],
                'unite' => 'FCFA',
                'lien' => ['url' => route('esbtp.paiements.index', [], false), 'libelle' => 'Voir les paiements'],
            ],
        ];
    }
}
