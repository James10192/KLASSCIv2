<?php

namespace Tests\Unit\LMD;

use App\Services\LMDBulletinService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Le vocabulaire de la structure LMD atteint les documents : releves (gele a
 * l'emission) et bulletin (sauf libelle personnalise par l'ecole).
 */
class VocabulaireSurLesDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
        Cache::put('setting_school_logo', '', 60);
        Cache::put('setting_ui.mobile_shell.enabled', false, 60);
    }

    /** @dataProvider gabaritsDeReleve */
    public function test_le_releve_porte_le_vocabulaire_gele_a_l_emission(string $gabarit): void
    {
        $html = html_entity_decode($this->rendre($gabarit, [
            'vocabulary' => ['domaine' => 'Composante', 'mention' => 'Département', 'parcours' => 'Spécialité'],
            'domain_nature' => 'École',
        ]), ENT_QUOTES);

        $this->assertStringContainsString('École', $html);
        $this->assertStringContainsString('Département', $html);
        $this->assertStringContainsString('Spécialité', $html);
    }

    /** @dataProvider gabaritsDeReleve */
    public function test_un_releve_anterieur_garde_le_vocabulaire_d_origine(string $gabarit): void
    {
        $html = $this->rendre($gabarit, []);

        $this->assertMatchesRegularExpression('/Domaine\s*(<\/td>|:)/u', $html);
        $this->assertMatchesRegularExpression('/Parcours\s*(<\/td>|:)/u', $html);
    }

    public static function gabaritsDeReleve(): array
    {
        // Le modele klassci passe par le composant PDF commun, qui lit une
        // quarantaine de reglages en base : il n'est pas rendu ici, sans base.
        return ['modele MESRS' => ['pdf.lmd-releve-notes-mesrs']];
    }

    public function test_le_bulletin_suit_le_vocabulaire_sauf_libelle_personnalise(): void
    {
        $service = (new \ReflectionClass(LMDBulletinService::class))->newInstanceWithoutConstructor();
        $libelle = new \ReflectionMethod($service, 'libelleOuVocabulaire');
        $reglages = new \ReflectionProperty($service, 'settings');

        $reglages->setValue($service, ['lmd_bulletin_label_mention' => '']);
        $this->assertSame('DÉPARTEMENT', $libelle->invoke($service, 'lmd_bulletin_label_mention', 'Département'));

        $reglages->setValue($service, ['lmd_bulletin_label_mention' => 'FILIÈRE DE FORMATION']);
        $this->assertSame('FILIÈRE DE FORMATION', $libelle->invoke($service, 'lmd_bulletin_label_mention', 'Département'));
    }

    private function rendre(string $gabarit, array $scopeEnPlus): string
    {
        return view($gabarit, [
            'snapshot' => [
                'document' => ['reference' => 'REL-TEST', 'version' => 1, 'number' => 1],
                'institution' => ['name' => 'École test', 'city' => 'Cotonou'],
                'student' => [
                    'matricule' => 'T001', 'last_name' => 'Test', 'first_names' => 'Étudiant',
                    'birth_date' => '2004-01-01', 'birth_place' => 'Cotonou', 'sexe' => 'M', 'is_redoublant' => false,
                ],
                'scope' => $scopeEnPlus + [
                    'year' => ['id' => 1, 'label' => '2026-2027'], 'level' => 'Master 1',
                    'domain' => 'EGEI', 'mention' => 'Gestion', 'parcours' => ['id' => 1, 'label' => 'Finance', 'code' => 'FIN'],
                ],
                'semesters' => [],
                'totals' => ['credits_earned' => 0, 'credits_expected' => 60, 'average' => null],
                'rules' => [],
                'issuance' => [],
            ],
            'verificationCode' => 'TEST',
        ])->render();
    }
}
