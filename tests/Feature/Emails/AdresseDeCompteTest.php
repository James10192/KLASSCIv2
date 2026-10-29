<?php

namespace Tests\Feature\Emails;

use App\Models\User;
use App\Services\Emails\AdresseDeCompte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un compte cree automatiquement ne recoit plus d'adresse fabriquee : son
 * adresse personnelle si elle est joignable et libre, sinon rien.
 */
class AdresseDeCompteTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_adresse_personnelle_joignable_et_libre_est_reprise(): void
    {
        $this->assertSame('awa.kone@gmail.com', app(AdresseDeCompte::class)->pour(' awa.kone@gmail.com '));
    }

    public function test_sans_adresse_reelle_le_compte_reste_sans_email(): void
    {
        $adresses = app(AdresseDeCompte::class);

        $this->assertNull($adresses->pour(null));
        $this->assertNull($adresses->pour(''));
        $this->assertNull($adresses->pour('m22-0521@esbtp.edu.ci'));
        $this->assertNull($adresses->pour('awa@gmail.con'));
    }

    public function test_une_adresse_deja_prise_n_est_pas_dupliquee(): void
    {
        User::factory()->create(['email' => 'awa.kone@gmail.com']);

        $this->assertNull(app(AdresseDeCompte::class)->pour('awa.kone@gmail.com'));
    }

    public function test_un_compte_sans_email_se_cree(): void
    {
        $user = User::factory()->create(['email' => null, 'username' => 'awa.kone']);

        $this->assertNull($user->fresh()->email);
    }
}
