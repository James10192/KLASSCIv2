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

    public function test_la_moyenne_ponderee_ne_s_arrondit_pas(): void
    {
        // L'invariant que rien ne protegeait, et dont l'absence a coute une
        // relecture entiere : une relecture a lu les fixtures de ce fichier —
        // qui passaient « 9.60 » et « 10.12 » ecrits a la main — et en a
        // conclu que la production composait la moyenne a partir de blocs
        // deja arrondis. Elle a reclame un correctif sur une instance de plus
        // de deux mille etudiants. La production, elle, passe la sortie de
        // cette methode telle quelle (BulletinService, « Calculer les moyennes
        // par section »), et cette sortie n'est pas arrondie.
        //
        // Le bulletin papier arrondit une fois, au niveau de la MATIERE
        // (computeMoyenneFromNotesData). Composer ensuite sur des blocs
        // arrondis ajouterait un second arrondi que le papier ne fait pas.
        $service = app(BulletinService::class);

        // 8.67 x 4 + 6.00 x 4 + ... : un quotient a decimales infinies.
        $moyenne = $service->calculerMoyennePonderee($this->professionnelles());

        $this->assertNotSame(
            round($moyenne, 2),
            $moyenne,
            'calculerMoyennePonderee() doit rendre le quotient brut : un arrondi ici en ajouterait un second a la composition.'
        );
        $this->assertEqualsWithDelta(10.1155, $moyenne, 0.0001);
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
            $service->calculerMoyennePonderee($this->generales()),
            $service->calculerMoyennePonderee($this->professionnelles())
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
            $service->calculerMoyennePonderee($this->generales()),
            $service->calculerMoyennePonderee($this->professionnelles())
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
            $service->calculerMoyennePonderee($this->generales()),
            $service->calculerMoyennePonderee($this->professionnelles())
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

    public function test_une_matiere_hors_des_deux_blocs_fait_retomber_sur_la_ponderation(): void
    {
        // Le bloc d'une matiere vient de `type_formation`. Tant qu'une matiere
        // notee n'est classee ni en general ni en professionnel, composer par
        // blocs l'oublierait en silence : la moyenne serait celle d'une partie
        // du bulletin seulement. On rend alors la ponderation classique, qui
        // n'oublie personne.
        $this->modeBlocs(1, 1);
        $service = app(BulletinService::class);

        $orpheline = $this->ligne(20.00, 5);
        $toutes = $this->generales()->merge($this->professionnelles())->push($orpheline);

        $moyenne = $service->composerLaMoyenneDuSemestre(
            $toutes,
            $this->generales(),
            $this->professionnelles(),
            $service->calculerMoyennePonderee($this->generales()),
            $service->calculerMoyennePonderee($this->professionnelles())
        );

        $ponderee = $service->calculerMoyennePonderee($toutes);
        $this->assertEqualsWithDelta($ponderee, $moyenne, 0.001, 'Une note hors bloc doit ramener a la ponderation classique.');
        $this->assertNotEqualsWithDelta(9.86, $moyenne, 0.01, 'Et surtout pas a la moyenne des deux blocs, qui ignorerait cette note.');
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
