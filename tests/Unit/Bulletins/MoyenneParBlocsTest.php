<?php

namespace Tests\Unit\Bulletins;

use App\Helpers\SettingsHelper;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Comment la moyenne d'un semestre se compose.
 *
 * Les chiffres viennent d'un vrai bulletin d'ESBTP Abidjan — 2A BTS A Mines
 * Geologie et Petrole, 2e semestre. Sur ce cas, les deux compositions ne
 * donnent pas le meme verdict : 10.01 en ponderation classique, 09.86 en
 * blocs a poids egaux. L'etudiante passe dans un cas et pas dans l'autre.
 */
class MoyenneParBlocsTest extends TestCase
{
    use DatabaseTransactions;

    /** Enseignement general du bulletin : Gestion-comptabilite et Maths. */
    private function generales(): Collection
    {
        return collect([
            $this->ligne(12.00, 2),
            $this->ligne(8.00, 3),
        ]);
    }

    /** Enseignement professionnel : les sept matieres de specialite. */
    private function professionnelles(): Collection
    {
        return collect([
            $this->ligne(8.67, 4),
            $this->ligne(6.00, 4),
            $this->ligne(13.00, 4),
            $this->ligne(11.00, 4),
            $this->ligne(12.00, 1),
            $this->ligne(11.63, 1),
            $this->ligne(12.00, 2),
        ]);
    }

    public function test_le_defaut_reste_la_ponderation_sur_toutes_les_matieres(): void
    {
        // Aucun reglage pose : une ecole qui n'a rien demande garde son calcul.
        $service = app(BulletinService::class);
        $toutes = $this->generales()->merge($this->professionnelles());

        $moyenne = $service->composerLaMoyenneDuSemestre(
            $toutes,
            $this->generales(),
            $this->professionnelles(),
            9.60,
            10.12
        );

        $this->assertEqualsWithDelta(10.01, $moyenne, 0.01, 'Le mode par defaut ne doit rien changer a l existant.');
    }

    public function test_en_mode_blocs_les_deux_enseignements_pesent_a_egalite(): void
    {
        $this->modeBlocs(1, 1);
        $service = app(BulletinService::class);

        $moyenne = $service->composerLaMoyenneDuSemestre(
            $this->generales()->merge($this->professionnelles()),
            $this->generales(),
            $this->professionnelles(),
            9.60,
            10.12
        );

        // (09.60 + 10.12) / 2 — exactement le « Moy. : 09.86 / 20 » du papier.
        $this->assertEqualsWithDelta(9.86, $moyenne, 0.01);
    }

    public function test_les_coefficients_de_bloc_sont_ceux_de_l_ecole(): void
    {
        // Une autre ecole peut vouloir 1 et 2 : rien n'est fige dans le code.
        $this->modeBlocs(1, 2);
        $service = app(BulletinService::class);

        $moyenne = $service->composerLaMoyenneDuSemestre(
            $this->generales()->merge($this->professionnelles()),
            $this->generales(),
            $this->professionnelles(),
            9.60,
            10.12
        );

        $this->assertEqualsWithDelta((9.60 + 2 * 10.12) / 3, $moyenne, 0.01);
    }

    public function test_un_bloc_sans_aucune_matiere_notee_sort_du_calcul(): void
    {
        // Son coefficient quitte le denominateur en meme temps que sa moyenne
        // quitte le numerateur : sinon il vaudrait zero et punirait l'etudiant.
        $this->modeBlocs(1, 1);
        $service = app(BulletinService::class);

        $moyenne = $service->composerLaMoyenneDuSemestre(
            $this->generales(),
            $this->generales(),
            collect(),
            9.60,
            null
        );

        $this->assertEqualsWithDelta(9.60, $moyenne, 0.01);
    }

    public function test_un_bloc_entierement_a_zero_reste_dans_le_calcul(): void
    {
        // Zero est une note, pas une absence. Le confondre avec « pas de
        // matiere » remonterait la moyenne d'un etudiant qui a tout rate.
        $this->modeBlocs(1, 1);
        $service = app(BulletinService::class);
        $nulles = collect([$this->ligne(0.0, 3)]);

        $moyenne = $service->composerLaMoyenneDuSemestre(
            $this->generales()->merge($nulles),
            $this->generales(),
            $nulles,
            9.60,
            0.0
        );

        $this->assertEqualsWithDelta(4.80, $moyenne, 0.01, 'Un bloc a zero doit peser, pas disparaitre.');
    }

    private function modeBlocs(float $general, float $professionnel): void
    {
        SettingsHelper::setOrCreate('bulletin_moyenne_mode', 'blocs', 'bulletin', 'string');
        SettingsHelper::setOrCreate('bulletin_bloc_general_coef', (string) $general, 'bulletin', 'string');
        SettingsHelper::setOrCreate('bulletin_bloc_professionnel_coef', (string) $professionnel, 'bulletin', 'string');
    }

    private function ligne(float $moyenne, float $coefficient): object
    {
        return (object) [
            'moyenne' => $moyenne,
            'coefficient' => $coefficient,
            'statut' => \App\Models\ESBTPResultatMatiere::STATUT_NOTE,
        ];
    }
}
