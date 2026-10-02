<?php

namespace Tests\Unit\Mail;

use App\Domain\Analytics\DTOs\AnomalyAlert;
use App\Mail\Equipe\AlerteEncaissementsMail;
use App\Models\User;
use App\Notifications\AnalyticsAnomalyNotification;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * L'alerte des encaissements envoyée à la comptabilité ne passe plus par le
 * `MailMessage` de Laravel : plus de « [CRITICAL] », plus de score Z, et le
 * nom de l'école en tête au lieu du nom d'application.
 */
class AlerteEncaissementsMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Cache::put('setting_school_name', 'Institut Supérieur KLASSCI', 3600);
        Cache::put('setting_school_logo', '', 3600);
    }

    public static function alertes(): array
    {
        $ecart = fn (int $mois, float $attendu, float $paye) => new AnomalyAlert(
            type: 'recouvrement_gap',
            severity: AnomalyAlert::SEVERITY_CRITICAL,
            entityType: 'period',
            entityId: 202600 + $mois,
            score: 1 - $paye / $attendu,
            message: 'brut',
            context: ['year' => 2026, 'month' => $mois, 'expected' => $attendu, 'paid' => $paye, 'gap' => $attendu - $paye, 'gap_ratio' => 1 - $paye / $attendu],
        );

        return [
            $ecart(9, 3_200_000, 725_000),
            $ecart(7, 1_200_000, 130_000),
            $ecart(8, 100_000, 0),
            new AnomalyAlert('revenue_drop', AnomalyAlert::SEVERITY_CRITICAL, 'period', 202606, 3.4, 'Juin 2026 : … (Z=3.4)', ['year' => 2026, 'month' => 6, 'value' => 40_000, 'mean' => 900_000, 'z_score' => -3.4]),
            new AnomalyAlert('payment_outlier', AnomalyAlert::SEVERITY_CRITICAL, 'paiement', 12, 6.2, 'brut', ['paiement_id' => 12, 'numero_recu' => 'REC-2026-0042', 'montant' => 2_500_000, 'mean' => 400_000, 'ratio' => 6.25, 'date_paiement' => '2026-09-12']),
        ];
    }

    public function test_le_courriel_est_redige_pour_un_comptable(): void
    {
        $mail = new AlerteEncaissementsMail(self::alertes(), 'Marcel', 'https://ecole.test/analytics');
        $html = $mail->render();

        $this->assertStringContainsString('Institut Supérieur KLASSCI', $html);
        $this->assertStringContainsString('Les encaissements sont en retard sur 3 mois', $html);
        $this->assertStringContainsString('Bonjour Marcel', $html);
        // Les mois dans l'ordre du calendrier, pas dans celui du détecteur.
        $this->assertLessThan(strpos($html, 'Septembre 2026'), strpos($html, 'Juillet 2026'));
        $this->assertStringContainsString('Paiement inhabituel · reçu n° REC-2026-0042', $html);
        $this->assertStringContainsString('des 30 derniers jours', $html);
        $this->assertStringContainsString('Juin 2026 · encaissements très bas', $html);
        foreach (['[CRITICAL]', 'CRITICAL', 'Z=', 'Voir les analytics', 'brut'] as $interdit) {
            $this->assertStringNotContainsString($interdit, $html, $interdit);
        }
        $this->assertDoesNotMatchRegularExpression('/@(if|endif|else|endsection|section|include)\b/', $html);
        $this->assertSame('Encaissements en retard sur 3 mois · Institut Supérieur KLASSCI', $mail->envelope()->subject);
    }

    public function test_sans_ecart_de_recouvrement_le_titre_parle_des_points_a_verifier(): void
    {
        $html = (new AlerteEncaissementsMail([self::alertes()[4]], '', null))->render();

        $this->assertStringContainsString('1 point à vérifier dans les encaissements', $html);
        $this->assertStringNotContainsString('Reste à encaisser', $html);
    }

    public function test_la_notification_rend_ce_courriel_a_l_adresse_du_destinataire(): void
    {
        $user = new User(['name' => 'Awa Koné', 'email' => 'awa@ecole.test']);
        $mail = (new AnalyticsAnomalyNotification(self::alertes()))->toMail($user);

        $this->assertInstanceOf(AlerteEncaissementsMail::class, $mail);
        $this->assertTrue($mail->hasTo('awa@ecole.test'));
    }

    public function test_sans_adresse_la_notification_reste_en_base(): void
    {
        $notification = new AnalyticsAnomalyNotification(self::alertes());

        $this->assertSame(['database'], $notification->via(new User(['name' => 'Sans adresse', 'email' => ''])));
        $this->assertSame(['mail', 'database'], $notification->via(new User(['email' => 'awa@ecole.test'])));
    }
}
