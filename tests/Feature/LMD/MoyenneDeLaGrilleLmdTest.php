<?php

namespace Tests\Feature\LMD;

use App\Services\LMD\LmdAcademicRuleProfile;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * La moyenne affichée dans la grille des notes LMD doit être celle du bulletin
 * (LMDBulletinService::calculerMoyenneECUE). À ESBTP Abidjan, avec la pondération
 * 40/60 active, la grille affichait 11,00 pour 12 et 10 (moyenne simple), là où
 * le bulletin calcule 10,80 ; et elle ignorait un absent que le bulletin compte 0.
 *
 * Le test exécute la fonction réellement livrée dans la vue.
 */
class MoyenneDeLaGrilleLmdTest extends TestCase
{
    private function moyenne(array $lignes, ?array $ponderation): ?float
    {
        $vue = file_get_contents(resource_path('views/esbtp/lmd/notes/index.blade.php'));
        $this->assertSame(1, preg_match('/^function moyenneDeLaGrille\(.*?^}/ms', $vue, $m), 'fonction introuvable dans la vue');

        $script = $m[0]."\nconsole.log(JSON.stringify(moyenneDeLaGrille(".json_encode($lignes).', '.json_encode($ponderation).')));';
        $process = new Process(['node', '-e', $script]);
        $process->run();
        if (! $process->isSuccessful()) {
            $this->markTestSkipped('node indisponible : '.$process->getErrorOutput());
        }
        $sortie = json_decode(trim($process->getOutput()), true);

        return $sortie === null ? null : round((float) $sortie, 2);
    }

    private static function l(float $note, bool $examen, float $coefficient = 1): array
    {
        return ['note' => $note, 'coefficient' => $coefficient, 'examen' => $examen];
    }

    public function test_sans_ponderation_la_moyenne_reste_celle_des_coefficients(): void
    {
        $this->assertSame(15.5, $this->moyenne([self::l(20, false), self::l(11, true)], null));
        // Un absent compte 0, comme au bulletin.
        $this->assertSame(8.0, $this->moyenne([self::l(16, false), self::l(0, true)], null));
        $this->assertNull($this->moyenne([], null));
    }

    public function test_avec_la_ponderation_de_l_ecole_la_grille_donne_le_chiffre_du_bulletin(): void
    {
        $poids = ['cc' => 40.0, 'examen' => 60.0];

        $this->assertSame(10.8, $this->moyenne([self::l(12, false), self::l(10, true)], $poids));
        $this->assertSame(14.6, $this->moyenne([self::l(20, false), self::l(11, true)], $poids));
        $this->assertSame(10.0, $this->moyenne([self::l(12, false), self::l(8, false), self::l(10, true)], $poids));
        $this->assertSame(6.4, $this->moyenne([self::l(16, false), self::l(0, true)], $poids));
        // Une seule partie présente compte seule.
        $this->assertSame(9.0, $this->moyenne([self::l(9, true)], $poids));
    }

    public function test_la_vue_recoit_la_regle_que_lit_le_bulletin(): void
    {
        $vue = file_get_contents(resource_path('views/esbtp/lmd/notes/index.blade.php'));
        $controleur = file_get_contents(app_path('Http/Controllers/ESBTPLMDNoteController.php'));

        $this->assertStringContainsString('const ponderationEcue = @json($ponderation ?? null);', $vue);
        $this->assertStringContainsString('LmdAcademicRuleProfile::class)->ponderationGravee()', $controleur);
        $this->assertSame(
            ['cc' => 40.0, 'examen' => 60.0],
            (new LmdAcademicRuleProfile(fn ($k, $d = null) => [LmdAcademicRuleProfile::REGLAGE_PONDERATION_ACTIVE => '1'][$k] ?? $d))->ponderationGravee()
        );
    }
}
