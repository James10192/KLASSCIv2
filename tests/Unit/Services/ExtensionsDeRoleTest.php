<?php

namespace Tests\Unit\Services;

use App\Services\ExtensionsDeRole;
use App\Services\PermissionRegistry;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * La distinction entre une derive et une decision.
 *
 * La synchronisation des permissions nettoie cinq roles d'organigramme en
 * retirant tout ce qui ne figure pas dans les defauts partages. Elle ne savait
 * pas separer deux choses opposees : une permission restee la par accident, et
 * « chez nous, la scolarite valide les inscriptions ». Elle effacait donc la
 * seconde a chaque deploiement.
 *
 * C'est cette methode qui porte la separation. Un faux negatif — une decision
 * classee comme derive — se paie par une permission qui disparait sans message
 * et qu'on remet a la main a chaque livraison.
 *
 * Le registre est simule : ce qui est teste ici est la REGLE, pas le contenu
 * du referentiel, qui vit dans config/permissions.php et evolue sans elle.
 */
class ExtensionsDeRoleTest extends TestCase
{
    private function service(array $defauts, array $canoniques = []): ExtensionsDeRole
    {
        $registre = Mockery::mock(PermissionRegistry::class);
        $registre->shouldReceive('defaultPermissionsFor')->andReturn($defauts);
        $registre->shouldReceive('canonicalize')->andReturnUsing(
            fn (string $nom) => $canoniques[$nom] ?? $nom
        );

        return new ExtensionsDeRole($registre);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_une_permission_des_defauts_n_est_pas_une_extension(): void
    {
        $service = $this->service(['classes.view', 'students.view']);

        $this->assertSame([], $service->horsDefauts('serviceScolarite', ['classes.view', 'students.view']));
    }

    /**
     * LE cas qui a motive tout ceci.
     */
    public function test_une_permission_hors_defauts_est_une_extension(): void
    {
        $service = $this->service(['classes.view']);

        $this->assertSame(
            ['inscriptions.validate'],
            $service->horsDefauts('serviceScolarite', ['classes.view', 'inscriptions.validate'])
        );
    }

    /**
     * Un nom historique ne doit pas etre pris pour une extension sous pretexte
     * qu'il ne s'ecrit pas comme son equivalent canonique : ce serait inscrire
     * comme « voulue » une permission que le nettoyage devait justement
     * normaliser.
     */
    public function test_un_alias_d_une_permission_par_defaut_n_est_pas_une_extension(): void
    {
        $service = $this->service(
            ['inscriptions.validate'],
            ['valider inscriptions' => 'inscriptions.validate']
        );

        $this->assertSame([], $service->horsDefauts('serviceScolarite', ['valider inscriptions']));
    }

    public function test_les_doublons_ne_sont_comptes_qu_une_fois(): void
    {
        $service = $this->service(['classes.view']);

        $this->assertSame(
            ['classes.create'],
            $service->horsDefauts('serviceScolarite', ['classes.create', 'classes.create', 'classes.view'])
        );
    }

    public function test_un_role_sans_permission_n_a_aucune_extension(): void
    {
        $service = $this->service(['classes.view']);

        $this->assertSame([], $service->horsDefauts('serviceScolarite', []));
    }

    /**
     * Un role dont le referentiel ne declare aucun defaut : tout ce qu'il porte
     * a forcement ete decide localement.
     */
    public function test_sans_defauts_tout_est_extension(): void
    {
        $service = $this->service([]);

        $this->assertSame(
            ['classes.create', 'classes.edit'],
            $service->horsDefauts('roleSansDefauts', ['classes.create', 'classes.edit'])
        );
    }
}
