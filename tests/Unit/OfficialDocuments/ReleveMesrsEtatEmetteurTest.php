<?php

namespace Tests\Unit\OfficialDocuments;

use App\Domain\OfficialDocuments\Services\LmdTranscriptSnapshotBuilder;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Le releve officiel est delivre au nom d'un Etat : republique, devise,
 * ministere. Ils etaient ecrits en dur (Cote d'Ivoire) dans le gabarit.
 *
 * Ils viennent maintenant de l'instantane, et un instantane anterieur qui ne
 * les porte pas ressort avec les textes d'origine — ceux avec lesquels il a
 * ete imprime et signe.
 */
class ReleveMesrsEtatEmetteurTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
        // Seul reglage lu au rendu : le logo. Vide → repli sur la marque.
        Cache::put('setting_school_logo', '', 60);
        Cache::put('setting_ui.mobile_shell.enabled', false, 60);
    }

    public function test_un_releve_emis_au_benin_porte_l_etat_beninois(): void
    {
        $html = $this->rendre(['authority' => [
            'republic' => 'République du Bénin',
            'motto' => 'Fraternité – Justice – Travail',
            'ministry' => "Ministère de l'Enseignement Supérieur et de la Recherche Scientifique",
        ]]);

        $this->assertStringContainsString('République du Bénin', $html);
        $this->assertStringContainsString('Fraternité – Justice – Travail', $html);
        $this->assertStringNotContainsString("Côte d'Ivoire", html_entity_decode($html, ENT_QUOTES));
    }

    public function test_un_releve_emis_avant_ce_changement_ressort_a_l_identique(): void
    {
        $html = html_entity_decode($this->rendre([]), ENT_QUOTES);

        $this->assertStringContainsString("République de Côte d'Ivoire", $html);
        $this->assertStringContainsString('Union – Discipline – Travail', $html);
        $this->assertStringContainsString("Ministère de l'Enseignement Supérieur<br />\net de la Recherche Scientifique", $html);
    }

    public function test_l_instantane_grave_l_etat_regle_par_l_ecole(): void
    {
        Cache::put('setting_lmd_bulletin_republic_text', 'République du Bénin', 60);
        Cache::put('setting_lmd_bulletin_union_text', 'Fraternité – Justice – Travail', 60);
        Cache::put('setting_lmd_bulletin_ministry_text', 'MESRS', 60);

        $builder = (new \ReflectionClass(LmdTranscriptSnapshotBuilder::class))->newInstanceWithoutConstructor();
        $authority = (new \ReflectionMethod($builder, 'authority'))->invoke($builder);

        $this->assertSame([
            'republic' => 'République du Bénin',
            'motto' => 'Fraternité – Justice – Travail',
            'ministry' => 'MESRS',
        ], $authority);
    }

    private function rendre(array $snapshot): string
    {
        return view('pdf.lmd-releve-notes-mesrs', [
            'snapshot' => $snapshot + [
                'document' => ['reference' => 'REL-TEST', 'version' => 1, 'number' => 1],
                'institution' => ['name' => 'École test', 'city' => 'Cotonou'],
                'student' => [
                    'matricule' => 'T001', 'last_name' => 'Test', 'first_names' => 'Étudiante',
                    'birth_date' => '2004-01-01', 'birth_place' => 'Cotonou', 'sexe' => 'F', 'is_redoublant' => false,
                ],
                'scope' => [
                    'year' => ['id' => 1, 'label' => '2026-2027'], 'level' => 'Master 1',
                    'domain' => 'Sciences', 'mention' => 'Gestion', 'parcours' => ['id' => 1, 'label' => 'Finance', 'code' => 'FIN'],
                ],
                'semesters' => [],
                'totals' => ['credits_earned' => 0, 'credits_expected' => 60, 'average' => null],
            ],
            'verificationCode' => 'TEST',
        ])->render();
    }
}
