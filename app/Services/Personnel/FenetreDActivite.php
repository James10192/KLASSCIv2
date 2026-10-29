<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * La période sur laquelle on lit l'activité du personnel.
 *
 * « Année » veut dire l'ANNÉE UNIVERSITAIRE en cours, jamais l'année civile :
 * l'ancien score lisait du 1er janvier au 31 décembre et coupait chaque année
 * scolaire en deux. La fin est toujours ramenée à aujourd'hui : une séance de
 * demain n'est pas encore une séance manquée.
 */
final class FenetreDActivite
{
    public const PERIODES = [
        'annee' => 'Année universitaire',
        'mois' => 'Ce mois-ci',
        'mois_precedent' => 'Mois précédent',
    ];

    public function __construct(
        public readonly string $periode,
        public readonly Carbon $debut,
        public readonly Carbon $fin,
        public readonly string $libelle,
    ) {}

    public static function pour(?string $periode): self
    {
        $periode = isset(self::PERIODES[$periode ?? '']) ? $periode : 'annee';
        $aujourdhui = now()->endOfDay();

        if ($periode === 'mois') {
            return new self($periode, now()->startOfMonth(), $aujourdhui, self::PERIODES[$periode]);
        }

        if ($periode === 'mois_precedent') {
            $debut = now()->subMonthNoOverflow()->startOfMonth();

            return new self($periode, $debut, $debut->copy()->endOfMonth(), ucfirst($debut->locale('fr')->translatedFormat('F Y')));
        }

        $annee = DB::table('esbtp_annee_universitaires')->where('is_current', true)->first(['name', 'start_date', 'end_date'])
            ?? DB::table('esbtp_annee_universitaires')->orderByDesc('start_date')->first(['name', 'start_date', 'end_date']);

        $debut = $annee?->start_date ? Carbon::parse($annee->start_date)->startOfDay() : now()->startOfYear();
        $fin = $annee?->end_date ? Carbon::parse($annee->end_date)->endOfDay()->min($aujourdhui) : $aujourdhui;

        return new self($periode, $debut, $fin, 'Année '.($annee->name ?? $debut->format('Y')));
    }

    public function du(): string
    {
        return $this->debut->toDateString();
    }

    public function au(): string
    {
        return $this->fin->toDateString();
    }
}
