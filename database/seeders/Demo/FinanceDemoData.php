<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Étape 4 — paiements avec distribution réaliste + 5 outliers analytics.
 *
 * Mix cible :
 *  - 60% étudiants à jour (toutes tranches échues payées)
 *  - 25% étudiants partiels (tranche 1 OK, suite incomplète)
 *  - 15% étudiants en retard (rien payé après la 1ère tranche)
 *  +  5 paiements aberrants ≥ 5× la moyenne pour faire briller
 *     l'AnomalyDetector::payment_outlier
 */
class FinanceDemoData
{
    private const PROFILES = [
        ['key' => 'a_jour',  'weight' => 60, 'tranches_paid' => 3],
        ['key' => 'partiel', 'weight' => 25, 'tranches_paid' => 1],
        ['key' => 'retard',  'weight' => 15, 'tranches_paid' => 0],
    ];

    private const MODES_PAIEMENT = ['Espèces', 'Mobile Money', 'Virement', 'Chèque'];

    public function __construct(private readonly ?Command $command = null) {}

    /**
     * @param array $academic
     * @param array{categories: Collection, configurations: Collection, rules: Collection} $frais
     * @param array{etudiants: Collection, inscriptions: Collection} $students
     */
    public function run(array $academic, array $frais, array $students): void
    {
        $scolarite  = $frais['categories']->firstWhere('code', 'SCOLARITE');
        $inscription = $frais['categories']->firstWhere('code', 'INSCRIPTION');
        $configs    = $frais['configurations']->keyBy(fn ($c) => $c->frais_category_id . ':' . $c->filiere_id . ':' . $c->niveau_id);

        $count = 0;

        foreach ($students['inscriptions'] as $insc) {
            $profile = $this->pickProfile();

            $count += $this->seedInscriptionFee($insc, $inscription, $configs);
            $count += $this->seedScolaritePayments($insc, $scolarite, $configs, $profile);
        }

        $count += $this->seedAnalyticsOutliers($students['inscriptions'], $scolarite, $configs);

        $this->command?->line(sprintf('   • %d paiements créés (mix profils + outliers)', $count));
    }

    private function pickProfile(): array
    {
        $roll = mt_rand(1, 100);
        $cum = 0;
        foreach (self::PROFILES as $p) {
            $cum += $p['weight'];
            if ($roll <= $cum) {
                return $p;
            }
        }
        return self::PROFILES[0];
    }

    /** Paie systématiquement les frais d'inscription (toujours réglés à l'arrivée). */
    private function seedInscriptionFee(ESBTPInscription $insc, ESBTPFraisCategory $cat, Collection $configs): int
    {
        $config = $configs->get($cat->id . ':' . $insc->filiere_id . ':' . $insc->niveau_id);
        if (! $config) {
            return 0;
        }
        $amount = (float) $config->amount;
        $date = Carbon::parse($insc->date_inscription)->addDays(mt_rand(0, 5));

        $created = ESBTPPaiement::firstOrCreate(
            [
                'inscription_id'    => $insc->id,
                'frais_category_id' => $cat->id,
                'motif'             => 'Frais d\'inscription',
            ],
            [
                'etudiant_id'            => $insc->etudiant_id,
                'annee_universitaire_id' => $insc->annee_universitaire_id,
                'type_paiement'          => 'inscription',
                'montant'                => $amount,
                'mode_paiement'          => self::MODES_PAIEMENT[array_rand(self::MODES_PAIEMENT)],
                'date_paiement'          => $date,
                'statut'                 => 'validé',
                'status'                 => 'validé',
                'numero_recu'            => $this->makeReceiptNumber(),
                'reference_paiement'     => 'PAY-' . strtoupper(\Illuminate\Support\Str::random(8)),
            ]
        );

        return $created->wasRecentlyCreated ? 1 : 0;
    }

    /** Paie 0, 1, 2 ou 3 tranches de scolarité selon le profil. */
    private function seedScolaritePayments(ESBTPInscription $insc, ESBTPFraisCategory $cat, Collection $configs, array $profile): int
    {
        $config = $configs->get($cat->id . ':' . $insc->filiere_id . ':' . $insc->niveau_id);
        if (! $config) {
            return 0;
        }

        $amount = (float) $config->amount;
        $tranches = [
            ['pct' => 30, 'days' => 15],
            ['pct' => 30, 'days' => 120],
            ['pct' => 40, 'days' => 240],
        ];

        $count = 0;
        $base = Carbon::parse($insc->date_inscription);

        for ($i = 0; $i < $profile['tranches_paid']; $i++) {
            $tranche = $tranches[$i];
            $trancheAmount = round($amount * $tranche['pct'] / 100, 2);
            $payDate = $base->copy()->addDays($tranche['days'] - mt_rand(0, 10));

            if ($payDate->isFuture()) {
                continue; // une tranche future n'est pas encore payée
            }

            $created = ESBTPPaiement::firstOrCreate(
                [
                    'inscription_id'    => $insc->id,
                    'frais_category_id' => $cat->id,
                    'motif'             => sprintf('Scolarité — tranche %d/3', $i + 1),
                ],
                [
                    'etudiant_id'            => $insc->etudiant_id,
                    'annee_universitaire_id' => $insc->annee_universitaire_id,
                    'type_paiement'          => 'scolarite',
                    'montant'                => $trancheAmount,
                    'mode_paiement'          => self::MODES_PAIEMENT[array_rand(self::MODES_PAIEMENT)],
                    'date_paiement'          => $payDate,
                    'statut'                 => 'validé',
                    'status'                 => 'validé',
                    'numero_recu'            => $this->makeReceiptNumber(),
                    'reference_paiement'     => 'PAY-' . strtoupper(\Illuminate\Support\Str::random(8)),
                ]
            );

            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Injecte 5 paiements anormalement gros (≥ 5× moyenne) pour que
     * AnomalyDetector::detectPaymentOutliers ait des cibles à signaler.
     */
    private function seedAnalyticsOutliers(Collection $inscriptions, ESBTPFraisCategory $scolarite, Collection $configs): int
    {
        $sample = $inscriptions->random(min(5, $inscriptions->count()));
        $count = 0;

        foreach ($sample as $idx => $insc) {
            $config = $configs->get($scolarite->id . ':' . $insc->filiere_id . ':' . $insc->niveau_id);
            if (! $config) {
                continue;
            }

            $bigAmount = (float) ($config->amount); // 1× annual fee = ~6× a tranche
            $created = ESBTPPaiement::firstOrCreate(
                [
                    'inscription_id'    => $insc->id,
                    'frais_category_id' => $scolarite->id,
                    'motif'             => 'Règlement anticipé total — démo outlier',
                ],
                [
                    'etudiant_id'            => $insc->etudiant_id,
                    'annee_universitaire_id' => $insc->annee_universitaire_id,
                    'type_paiement'          => 'scolarite',
                    'montant'                => $bigAmount,
                    'mode_paiement'          => 'Virement',
                    'date_paiement'          => Carbon::now()->subDays(mt_rand(2, 25)),
                    'statut'                 => 'validé',
                    'status'                 => 'validé',
                    'numero_recu'            => $this->makeReceiptNumber(),
                    'reference_paiement'     => 'OUTLIER-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'commentaire'            => 'Paiement aberrant injecté par PresentationDemoSeeder pour analytics.',
                ]
            );

            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    private function makeReceiptNumber(): string
    {
        return 'REC-DEMO-' . str_pad((string) mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
