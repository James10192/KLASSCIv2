<?php

namespace Tests\Unit\Autorisations;

use App\Http\Middleware\OuvreLaMemoireDesAutorisations;
use App\Support\Autorisations\PorteMemorisee;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Http\Request;
use Tests\TestCase;

class PorteMemoriseeTest extends TestCase
{
    private int $appels = 0;

    private PorteMemorisee $porte;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appels = 0;
        // Une porte vierge : les rappels de l'application (Spatie, superAdmin,
        // accès temporaires) exigent un vrai utilisateur et ne sont pas l'objet ici.
        $this->porte = $porte = new PorteMemorisee($this->app, fn () => null);
        $porte->define('sonde.memoire', function () {
            $this->appels++;

            return true;
        });
        $porte->define('sonde.objet', function ($user, $objet) {
            $this->appels++;

            return $objet === 'ok';
        });
    }

    public function test_la_porte_liee_est_la_porte_memorisee(): void
    {
        $this->assertInstanceOf(PorteMemorisee::class, $this->app->make(GateContract::class));
    }

    public function test_une_lecture_ne_pose_la_question_qu_une_fois(): void
    {
        $this->ouvrirUneRequete('GET');
        $porte = $this->porte->forUser($this->personne(1));

        foreach (range(1, 5) as $_) {
            $this->assertTrue($porte->check('sonde.memoire'));
        }

        $this->assertSame(1, $this->appels);
    }

    public function test_une_ecriture_ne_retient_rien(): void
    {
        $this->ouvrirUneRequete('POST');
        $porte = $this->porte->forUser($this->personne(1));

        $porte->check('sonde.memoire');
        $porte->check('sonde.memoire');

        $this->assertSame(2, $this->appels);
    }

    public function test_hors_requete_rien_n_est_retenu(): void
    {
        $porte = $this->porte->forUser($this->personne(1));

        $porte->check('sonde.memoire');
        $porte->check('sonde.memoire');

        $this->assertSame(2, $this->appels);
    }

    public function test_une_question_sur_un_objet_n_est_jamais_retenue(): void
    {
        $this->ouvrirUneRequete('GET');
        $porte = $this->porte->forUser($this->personne(1));

        $this->assertTrue($porte->check('sonde.objet', ['ok']));
        $this->assertFalse($porte->check('sonde.objet', ['ko']));

        $this->assertSame(2, $this->appels);
    }

    public function test_chaque_personne_a_sa_propre_reponse(): void
    {
        $this->ouvrirUneRequete('GET');
        $porte = $this->porte;
        $porte->define('sonde.personne', function ($user) {
            $this->appels++;

            return $user->id === 1;
        });

        $this->assertTrue($porte->forUser($this->personne(1))->check('sonde.personne'));
        $this->assertFalse($porte->forUser($this->personne(2))->check('sonde.personne'));
        $this->assertTrue($porte->forUser($this->personne(1))->check('sonde.personne'));

        $this->assertSame(2, $this->appels);
    }

    public function test_une_nouvelle_requete_repart_d_une_memoire_vide(): void
    {
        $this->ouvrirUneRequete('GET');
        $this->porte->forUser($this->personne(1))->check('sonde.memoire');

        $this->ouvrirUneRequete('GET');
        $this->porte->forUser($this->personne(1))->check('sonde.memoire');

        $this->assertSame(2, $this->appels);
    }

    private function ouvrirUneRequete(string $methode): void
    {
        $requete = Request::create('/sonde', $methode);
        $this->app->instance('request', $requete);
        (new OuvreLaMemoireDesAutorisations())->handle($requete, fn () => null);
    }

    private function personne(int $id): GenericUser
    {
        return new class(['id' => $id]) extends GenericUser implements Authorizable {
            public function can($abilities, $arguments = [])
            {
                return false;
            }

            public function hasRole($roles): bool
            {
                return false;
            }
        };
    }
}
