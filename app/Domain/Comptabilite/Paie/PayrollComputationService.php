<?php

namespace App\Domain\Comptabilite\Paie;

use App\Enums\TypeSeance;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPTeacher;
use App\Services\TeacherHoursService;
use Carbon\Carbon;

/**
 * Calcule un bulletin de paie enseignant : gains (heures réelles × taux par type)
 * − retenues (impôt ITS via barème configurable + CNPS + retenues manuelles).
 *
 * On NE paie QUE les heures réellement effectuées (jamais le planifié — grill
 * juin 2026). Les taux viennent de la fiche enseignant (taux par type, fallback
 * sur le taux par défaut). Le barème ITS et le taux CNPS sont des settings tenant
 * (« barème configurable » — l'impôt calculé reste une suggestion modifiable).
 *
 * @see App\Services\TeacherHoursService — source des heures précises
 */
class PayrollComputationService
{
    public function __construct(private TeacherHoursService $hours)
    {
    }

    /**
     * Barème ITS du tenant : liste de tranches [from, to(null=∞), taux(%)].
     * Valeur par défaut indicative (Côte d'Ivoire, mensuel) — éditable par l'école.
     *
     * @return array<int, array{from:float,to:float|null,taux:float}>
     */
    public function baremeIts(): array
    {
        $raw = SettingsHelper::get('paie.its_bareme');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded)) {
                return array_map(fn ($t) => [
                    'from' => (float) ($t['from'] ?? 0),
                    'to'   => isset($t['to']) && $t['to'] !== null && $t['to'] !== '' ? (float) $t['to'] : null,
                    'taux' => (float) ($t['taux'] ?? 0),
                ], $decoded);
            }
        }

        return [
            ['from' => 0,       'to' => 75000,  'taux' => 0],
            ['from' => 75000,   'to' => 240000, 'taux' => 16],
            ['from' => 240000,  'to' => 800000, 'taux' => 21],
            ['from' => 800000,  'to' => null,   'taux' => 32],
        ];
    }

    /** Taux CNPS (part salariale) en %, configurable. */
    public function tauxCnps(): float
    {
        return (float) SettingsHelper::get('paie.cnps_taux', 6.3);
    }

    /** Impôt ITS progressif sur le brut imposable, selon le barème tenant. */
    public function computeIts(float $brut): float
    {
        $impot = 0.0;
        foreach ($this->baremeIts() as $tranche) {
            $from = $tranche['from'];
            $to   = $tranche['to'];
            if ($brut <= $from) {
                continue;
            }
            $plafond = $to === null ? $brut : min($brut, $to);
            $assiette = max(0, $plafond - $from);
            $impot += $assiette * ($tranche['taux'] / 100);
        }

        return round($impot, 2);
    }

    /**
     * Calcule un aperçu de bulletin pour un enseignant sur une période mensuelle.
     *
     * @param  array{primes?:array<int,array{libelle:string,montant:float}>,
     *               retenues?:array<int,array{type?:string,libelle:string,montant:float}>,
     *               impot_its?:float|null, cnps?:float|null}  $options
     * @return array<string,mixed>
     */
    public function computePreview(ESBTPTeacher $teacher, Carbon $from, Carbon $to, array $options = []): array
    {
        $teacher->loadMissing('tauxSeances');
        $summary = $this->hours->summary($teacher, $from, $to);

        // Gains : heures réalisées facturables (CM/TD/TP) × taux du type.
        $gains = [];
        $base = 0.0;
        $heuresTotal = 0.0;
        $ordre = 0;
        foreach ($summary['par_type'] as $pt) {
            if (!$pt['facturable'] || $pt['heures_realisees'] <= 0) {
                continue;
            }
            $type = TypeSeance::tryFrom($pt['type']) ?? TypeSeance::AUTRE;
            $taux = $teacher->tauxPour($type);
            $montant = round($pt['heures_realisees'] * $taux, 2);
            $base += $montant;
            $heuresTotal += $pt['heures_realisees'];
            $gains[] = [
                'categorie' => 'gain',
                'type'      => $pt['type'],
                'libelle'   => $pt['label'],
                'heures'    => $pt['heures_realisees'],
                'taux'      => $taux,
                'montant'   => $montant,
                'ordre'     => $ordre++,
            ];
        }

        // Primes manuelles (gains supplémentaires).
        $primesTotal = 0.0;
        foreach ($options['primes'] ?? [] as $p) {
            $montant = round((float) ($p['montant'] ?? 0), 2);
            if ($montant === 0.0) {
                continue;
            }
            $primesTotal += $montant;
            $gains[] = [
                'categorie' => 'gain',
                'type'      => 'prime',
                'libelle'   => $p['libelle'] ?? 'Prime',
                'heures'    => null,
                'taux'      => null,
                'montant'   => $montant,
                'ordre'     => $ordre++,
            ];
        }

        $brut = round($base + $primesTotal, 2);

        // Retenues : impôt ITS (auto, modifiable) + CNPS (auto) + retenues manuelles.
        $impotIts = isset($options['impot_its']) && $options['impot_its'] !== null
            ? round((float) $options['impot_its'], 2)
            : $this->computeIts($brut);
        $cnps = isset($options['cnps']) && $options['cnps'] !== null
            ? round((float) $options['cnps'], 2)
            : round($brut * $this->tauxCnps() / 100, 2);

        $retenues = [];
        $ordreR = 0;
        if ($impotIts > 0) {
            $retenues[] = ['categorie' => 'retenue', 'type' => 'impot', 'libelle' => 'Impôt sur salaire (ITS)', 'heures' => null, 'taux' => null, 'montant' => $impotIts, 'ordre' => $ordreR++];
        }
        if ($cnps > 0) {
            $retenues[] = ['categorie' => 'retenue', 'type' => 'cnps', 'libelle' => 'Cotisation CNPS', 'heures' => null, 'taux' => null, 'montant' => $cnps, 'ordre' => $ordreR++];
        }
        foreach ($options['retenues'] ?? [] as $r) {
            $montant = round((float) ($r['montant'] ?? 0), 2);
            if ($montant === 0.0) {
                continue;
            }
            $retenues[] = [
                'categorie' => 'retenue',
                'type'      => $r['type'] ?? 'autre',
                'libelle'   => $r['libelle'] ?? 'Retenue',
                'heures'    => null,
                'taux'      => null,
                'montant'   => $montant,
                'ordre'     => $ordreR++,
            ];
        }

        $totalRetenues = round(array_sum(array_column($retenues, 'montant')), 2);
        $net = round($brut - $totalRetenues, 2);

        return [
            'periode'          => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'heures_total'     => round($heuresTotal, 2),
            'taux_realisation' => $summary['taux_realisation'],
            'gains'            => $gains,
            'retenues'         => $retenues,
            'base'             => round($base, 2),
            'primes'           => $primesTotal,
            'brut'             => $brut,
            'impot_its'        => $impotIts,
            'cnps'             => $cnps,
            'total_retenues'   => $totalRetenues,
            'net'              => $net,
            'lignes'           => array_merge($gains, $retenues),
        ];
    }
}
