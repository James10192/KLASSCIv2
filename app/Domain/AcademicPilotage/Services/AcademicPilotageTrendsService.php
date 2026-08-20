<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Tendances mensuelles de l'annee universitaire courante.
 *
 * Quatre series seulement, celles qui racontent la marche de l'annee :
 * les inscriptions qui entrent, l'assiduite relevee, les notes saisies et
 * les bulletins produits. Chacune est bornee a l'annee universitaire, pas
 * aux douze derniers mois : une rentree de septembre n'a aucun sens
 * comparee au mois de juillet precedent, qui appartient a une autre cohorte.
 */
final class AcademicPilotageTrendsService
{
    private const TTL = 600;

    /**
     * @return array{ok: bool, message: ?string, labels: array<int, string>, series: array<int, array{key: string, label: string, unit: string, hint: string, values: array<int, float|int|null>}>}
     */
    public function forYear(?int $yearId): array
    {
        $annee = $yearId
            ? ESBTPAnneeUniversitaire::find($yearId)
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $annee || ! $annee->start_date || ! $annee->end_date) {
            return [
                'ok' => false,
                'message' => "L'annee universitaire n'a pas de dates de debut et de fin : impossible de borner les tendances.",
                'labels' => [],
                'series' => [],
            ];
        }

        return Cache::remember(
            'pilotage.tendances.'.$annee->id,
            self::TTL,
            fn (): array => $this->calculer($annee),
        );
    }

    /**
     * @return array{ok: bool, message: ?string, labels: array<int, string>, series: array<int, array{key: string, label: string, unit: string, hint: string, values: array<int, float|int|null>}>}
     */
    private function calculer(ESBTPAnneeUniversitaire $annee): array
    {
        $mois = $this->moisDeLAnnee($annee);
        $cles = array_column($mois, 'cle');

        return [
            'ok' => true,
            'message' => null,
            'labels' => array_column($mois, 'label'),
            'series' => [
                [
                    'key' => 'inscriptions',
                    'label' => 'Inscriptions validées',
                    'unit' => '',
                    'hint' => 'Dossiers passés au statut actif, par mois de dépôt.',
                    'values' => $this->inscriptions($annee, $cles),
                ],
                [
                    'key' => 'presence',
                    'label' => 'Taux de présence',
                    'unit' => '%',
                    'hint' => 'Présences rapportées aux appels effectués. Les mois sans appel restent vides.',
                    'values' => $this->presence($annee, $cles),
                ],
                [
                    'key' => 'couverture_notes',
                    'label' => 'Couverture de saisie des notes',
                    'unit' => '%',
                    'hint' => 'Part des évaluations passées qui portent au moins une note.',
                    'values' => $this->couvertureNotes($annee, $cles),
                ],
                [
                    'key' => 'bulletins',
                    'label' => 'Bulletins générés',
                    'unit' => '',
                    'hint' => 'Bulletins produits dans le mois, toutes classes confondues.',
                    'values' => $this->bulletins($annee, $cles),
                ],
            ],
        ];
    }

    /**
     * Mois couverts par l'annee universitaire, du debut a la fin ou a
     * aujourd'hui si l'annee est encore en cours : tracer des mois futurs
     * dessinerait une chute jusqu'a zero qui n'est pas une baisse.
     *
     * @return array<int, array{cle: string, label: string}>
     */
    private function moisDeLAnnee(ESBTPAnneeUniversitaire $annee): array
    {
        $debut = CarbonImmutable::parse($annee->start_date)->startOfMonth();
        $fin = CarbonImmutable::parse($annee->end_date)->startOfMonth();
        $aujourdhui = CarbonImmutable::now()->startOfMonth();
        if ($fin->greaterThan($aujourdhui)) {
            $fin = $aujourdhui;
        }
        if ($fin->lessThan($debut)) {
            $fin = $debut;
        }

        $mois = [];
        for ($curseur = $debut; $curseur->lessThanOrEqualTo($fin); $curseur = $curseur->addMonth()) {
            $mois[] = [
                'cle' => $curseur->format('Y-m'),
                'label' => ucfirst($curseur->locale('fr')->isoFormat('MMM YY')),
            ];
        }

        return $mois;
    }

    /**
     * @param  array<int, string>  $cles
     * @return array<int, int>
     */
    private function inscriptions(ESBTPAnneeUniversitaire $annee, array $cles): array
    {
        $comptes = ESBTPInscription::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->whereNotNull('date_inscription')
            ->selectRaw("DATE_FORMAT(date_inscription, '%Y-%m') as mois, COUNT(*) as total")
            ->groupBy('mois')
            ->pluck('total', 'mois');

        return array_map(static fn (string $cle): int => (int) ($comptes[$cle] ?? 0), $cles);
    }

    /**
     * @param  array<int, string>  $cles
     * @return array<int, float|null>
     */
    private function presence(ESBTPAnneeUniversitaire $annee, array $cles): array
    {
        $lignes = ESBTPAttendance::query()
            ->where('annee_universitaire_id', $annee->id)
            ->whereIn('status', ['present', 'absent'])
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as mois")
            ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as presents")
            ->selectRaw('COUNT(*) as appels')
            ->groupBy('mois')
            ->get()
            ->keyBy('mois');

        return array_map(static function (string $cle) use ($lignes): ?float {
            $ligne = $lignes[$cle] ?? null;
            if (! $ligne || (int) $ligne->appels === 0) {
                return null;
            }

            return round((int) $ligne->presents / (int) $ligne->appels * 100, 1);
        }, $cles);
    }

    /**
     * @param  array<int, string>  $cles
     * @return array<int, float|null>
     */
    private function couvertureNotes(ESBTPAnneeUniversitaire $annee, array $cles): array
    {
        $base = ESBTPEvaluation::query()
            ->whereNotNull('date_evaluation')
            ->whereDate('date_evaluation', '>=', $annee->start_date)
            ->whereDate('date_evaluation', '<=', $annee->end_date)
            ->whereDate('date_evaluation', '<', today());

        $passees = (clone $base)
            ->selectRaw("DATE_FORMAT(date_evaluation, '%Y-%m') as mois, COUNT(*) as total")
            ->groupBy('mois')
            ->pluck('total', 'mois');

        $notees = (clone $base)
            ->whereHas('notes')
            ->selectRaw("DATE_FORMAT(date_evaluation, '%Y-%m') as mois, COUNT(*) as total")
            ->groupBy('mois')
            ->pluck('total', 'mois');

        return array_map(static function (string $cle) use ($passees, $notees): ?float {
            $total = (int) ($passees[$cle] ?? 0);
            if ($total === 0) {
                return null;
            }

            return round((int) ($notees[$cle] ?? 0) / $total * 100, 1);
        }, $cles);
    }

    /**
     * @param  array<int, string>  $cles
     * @return array<int, int>
     */
    private function bulletins(ESBTPAnneeUniversitaire $annee, array $cles): array
    {
        $comptes = ESBTPBulletin::query()
            ->where('annee_universitaire_id', $annee->id)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as total")
            ->groupBy('mois')
            ->pluck('total', 'mois');

        return array_map(static fn (string $cle): int => (int) ($comptes[$cle] ?? 0), $cles);
    }
}
