<?php

namespace Tests\Unit\Services;

use App\Services\Audit\AuditStatsSnapshot;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Tests Unit isolés pour l'instantané des statistiques d'audit.
 *
 * Aucune base de données : l'instantané vit dans un fichier, et c'est
 * précisément ce qui permet de le tester ici. Le chemin est injecté, donc rien
 * n'a besoin du conteneur Laravel ni des réglages d'instance.
 */
class AuditStatsSnapshotTest extends TestCase
{
    private string $chemin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chemin = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR . 'klassci-audit-stats-' . uniqid()
            . DIRECTORY_SEPARATOR . 'statistiques.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->chemin)) {
            unlink($this->chemin);
            @rmdir(dirname($this->chemin));
        }

        parent::tearDown();
    }

    /** Rien de calculé : la page doit pouvoir dire « indisponible », pas deviner. */
    public function test_lire_renvoie_null_quand_aucun_instantane(): void
    {
        $instantane = new AuditStatsSnapshot($this->chemin);

        $this->assertNull($instantane->lire());
    }

    public function test_ecrire_puis_lire_restitue_les_compteurs_et_la_date(): void
    {
        $instantane = new AuditStatsSnapshot($this->chemin);
        $calculeLe = Carbon::parse('2026-09-04 08:00:00');

        $instantane->ecrire(['total_audits' => 12345, 'today_audits' => 7], $calculeLe);
        $relu = $instantane->lire();

        $this->assertNotNull($relu);
        $this->assertSame(12345, $relu['statistiques']['total_audits']);
        $this->assertSame(7, $relu['statistiques']['today_audits']);
        $this->assertTrue($calculeLe->equalTo($relu['calcule_le']));
    }

    /** Un fichier tronqué ou corrompu vaut « pas d'instantané », jamais une erreur. */
    public function test_lire_renvoie_null_sur_un_fichier_illisible(): void
    {
        mkdir(dirname($this->chemin), 0775, true);
        file_put_contents($this->chemin, '{ ceci n\'est pas du JSON');

        $this->assertNull((new AuditStatsSnapshot($this->chemin))->lire());
    }

    public function test_est_perime_seulement_au_dela_du_seuil(): void
    {
        $maintenant = Carbon::parse('2026-09-04 12:00:00');

        $this->assertFalse(AuditStatsSnapshot::estPerime(
            $maintenant->copy()->subMinutes(179),
            180,
            $maintenant
        ));

        $this->assertTrue(AuditStatsSnapshot::estPerime(
            $maintenant->copy()->subMinutes(180),
            180,
            $maintenant
        ));

        $this->assertTrue(AuditStatsSnapshot::estPerime(
            $maintenant->copy()->subMinutes(600),
            180,
            $maintenant
        ));
    }

    /** L'écriture passe par un fichier temporaire : rien ne doit rester derrière. */
    public function test_ecrire_ne_laisse_pas_de_fichier_temporaire(): void
    {
        $instantane = new AuditStatsSnapshot($this->chemin);
        $instantane->ecrire(['total_audits' => 1]);

        $restes = glob(dirname($this->chemin) . DIRECTORY_SEPARATOR . '*.tmp');

        $this->assertSame([], $restes);
    }
}
