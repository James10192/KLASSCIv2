<?php

namespace App\Mail\Equipe;

use App\Domain\Analytics\DTOs\AnomalyAlert;
use App\Helpers\MontantFcfa;
use App\Helpers\SettingsHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

/**
 * Alerte envoyée à la comptabilité quand le contrôle des encaissements
 * (`DetectAnalyticsAnomaliesJob`, toutes les six heures) relève un écart.
 *
 * Elle prend le gabarit « Reçu » des avis aux parents plutôt que celui de
 * Laravel : la version d'avant affichait « ESBTP » en tête, des lignes
 * « • [CRITICAL] … » et un score Z, illisibles pour un comptable.
 *
 * Les écarts au recouvrement (attendu par les échéanciers / encaissé) sont
 * regroupés en un tableau de mois ; les autres signaux (mois hors de la
 * moyenne, paiement inhabituel) sont rédigés un par un à partir de leur
 * contexte, jamais recopiés de `$alert->message`, qui porte le score Z.
 */
class AlerteEncaissementsMail extends Mailable
{
    use Queueable;

    public const MAX_SIGNAUX = 10;

    /** @param array<int, AnomalyAlert> $alertes */
    public function __construct(
        public readonly array $alertes,
        public readonly string $prenom = '',
        public readonly ?string $lien = null,
    ) {}

    public function envelope(): Envelope
    {
        $ecarts = count($this->ecarts());
        $sujet = $ecarts > 0
            ? sprintf('Encaissements en retard sur %d mois', $ecarts)
            : sprintf('%d point%s à vérifier dans les encaissements', count($this->alertes), count($this->alertes) > 1 ? 's' : '');

        return new Envelope(subject: $sujet.' · '.$this->nomEcole());
    }

    public function content(): Content
    {
        $ecarts = $this->ecarts();

        return new Content(
            view: 'esbtp.emails.equipe.alerte-encaissements',
            with: [
                'prenom' => $this->prenom,
                'lien' => $this->lien,
                'ecarts' => $ecarts,
                'totalAttendu' => array_sum(array_column($ecarts, 'attendu')),
                'totalEncaisse' => array_sum(array_column($ecarts, 'encaisse')),
                'autres' => $this->autresSignaux(),
                'restants' => max(0, count($this->alertes) - self::MAX_SIGNAUX),
            ],
        );
    }

    /** @return array<int, array{cle: string, mois: string, attendu: float, encaisse: float, ecart: float, part: int}> */
    private function ecarts(): array
    {
        $lignes = [];
        foreach (array_slice($this->alertes, 0, self::MAX_SIGNAUX) as $alerte) {
            if ($alerte->type !== 'recouvrement_gap') {
                continue;
            }
            $c = $alerte->context;
            $attendu = (float) ($c['expected'] ?? 0);
            $encaisse = (float) ($c['paid'] ?? 0);
            $lignes[] = [
                'cle' => sprintf('%04d-%02d', $c['year'] ?? 0, $c['month'] ?? 0),
                'mois' => $this->mois($c),
                'attendu' => $attendu,
                'encaisse' => $encaisse,
                'ecart' => (float) ($c['gap'] ?? max(0, $attendu - $encaisse)),
                'part' => $attendu > 0 ? (int) round(100 * $encaisse / $attendu) : 0,
            ];
        }
        usort($lignes, fn ($a, $b) => strcmp($a['cle'], $b['cle']));

        return $lignes;
    }

    /** @return array<int, array{titre: string, texte: string}> */
    private function autresSignaux(): array
    {
        $signaux = [];
        foreach (array_slice($this->alertes, 0, self::MAX_SIGNAUX) as $alerte) {
            $c = $alerte->context;
            $signal = match ($alerte->type) {
                'recouvrement_gap' => null,
                'revenue_drop', 'revenue_spike' => [
                    'titre' => $this->mois($c).($alerte->type === 'revenue_drop' ? ' · encaissements très bas' : ' · encaissements très hauts'),
                    'texte' => sprintf(
                        '%s encaissés, quand un mois ordinaire en compte %s.',
                        self::fcfa($c['value'] ?? 0),
                        isset($c['mean']) ? self::fcfa($c['mean']).' en moyenne' : 'nettement '.($alerte->type === 'revenue_drop' ? 'plus' : 'moins'),
                    ),
                ],
                'payment_outlier' => [
                    'titre' => 'Paiement inhabituel',
                    'texte' => sprintf(
                        '%s%s, soit %s fois le montant moyen des trente derniers jours (%s). Vérifiez qu\'il ne s\'agit pas d\'une erreur de saisie.',
                        self::fcfa($c['montant'] ?? 0),
                        ! empty($c['date_paiement']) ? ' le '.Carbon::parse($c['date_paiement'])->translatedFormat('j F Y') : '',
                        number_format((float) ($c['ratio'] ?? 0), 1, ',', ' '),
                        self::fcfa($c['mean'] ?? 0),
                    ),
                ],
                default => ['titre' => 'À vérifier', 'texte' => $alerte->message],
            };
            if ($signal !== null) {
                $signaux[] = $signal;
            }
        }

        return $signaux;
    }

    private function mois(array $contexte): string
    {
        if (empty($contexte['year']) || empty($contexte['month'])) {
            return 'Mois inconnu';
        }

        return ucfirst(Carbon::create((int) $contexte['year'], (int) $contexte['month'], 1)->translatedFormat('F Y'));
    }

    /** « 150 000 FCFA », unité attachée : un texte rapporté, pas une valeur HTML. */
    private static function fcfa(mixed $montant): string
    {
        return MontantFcfa::nombre($montant)."\u{00A0}FCFA";
    }

    private function nomEcole(): string
    {
        return trim((string) (SettingsHelper::getSchoolInfo()['name'] ?? '')) ?: 'KLASSCI';
    }
}
