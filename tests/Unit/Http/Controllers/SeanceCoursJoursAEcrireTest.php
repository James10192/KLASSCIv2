<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ESBTPSeanceCoursController;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Les jours que `store()` écrit réellement.
 *
 * ## Le défaut que ces cas pinnent
 *
 * Le garde de conflit ne portait que sur `$request->jour`. Or en récurrence ce
 * jour-là n'est PAS créé : seuls les jours cochés le sont. La vérification
 * tombait donc sur un jour où rien ne s'écrit, et les jours écrits n'étaient
 * jamais vérifiés.
 *
 * Depuis, une seule liste sert au garde ET à la boucle de création. C'est elle
 * qu'on fixe ici — par réflexion, parce que la méthode est privée et qu'une
 * méthode d'un contrôleur n'a pas à devenir publique pour être testée.
 */
class SeanceCoursJoursAEcrireTest extends TestCase
{
    /** @return array<int, mixed> */
    private function joursAEcrire(array $entree): array
    {
        $methode = new ReflectionMethod(ESBTPSeanceCoursController::class, 'joursAEcrire');
        $methode->setAccessible(true);

        return $methode->invoke(new ESBTPSeanceCoursController, new Request($entree));
    }

    public function test_sans_recurrence_le_jour_du_formulaire(): void
    {
        $this->assertSame([3], $this->joursAEcrire(['jour' => 3]));
    }

    public function test_avec_recurrence_les_jours_coches_et_non_celui_du_formulaire(): void
    {
        // Le point du défaut : `jour` vaut 2, et 2 n'est PAS écrit.
        $this->assertSame(
            [1, 3, 5],
            $this->joursAEcrire(['jour' => 2, 'is_recurring' => '1', 'recurrence_days' => [1, 3, 5]])
        );
    }

    public function test_les_jours_coches_arrivent_parfois_en_une_seule_chaine(): void
    {
        // Selon le champ du formulaire, la liste part en tableau ou en chaîne
        // séparée par des virgules. La boucle de création acceptait déjà les
        // deux ; le garde doit voir la même chose qu'elle.
        $this->assertSame(
            ['1', '4'],
            $this->joursAEcrire(['jour' => 2, 'is_recurring' => '1', 'recurrence_days' => '1,4'])
        );
    }

    public function test_recurrence_cochee_sans_aucun_jour_retombe_sur_le_formulaire(): void
    {
        // C'est ce que faisait déjà la boucle de création : sans jour coché,
        // elle prenait la branche simple. Le garde ne doit pas, lui, conclure
        // « rien à écrire, donc rien à vérifier ».
        $this->assertSame([2], $this->joursAEcrire(['jour' => 2, 'is_recurring' => '1']));
        $this->assertSame([2], $this->joursAEcrire(['jour' => 2, 'is_recurring' => '1', 'recurrence_days' => []]));
    }
}
