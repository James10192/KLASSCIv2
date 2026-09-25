<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPInscription;
use App\Models\User;
use Mockery;
use Tests\TestCase;

/**
 * Une inscription validee garde sa filiere, son niveau et sa classe — sauf
 * pour qui detient le droit dedie. Ce droit a remplace `admin.access`, que
 * les roles de guichet (agent d'inscription, scolarite) n'ont pas : sans lui,
 * la personne qui avait saisi la mauvaise classe ne pouvait plus la reprendre.
 */
class InscriptionReaffectationApresValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_le_droit_dedie_suffit_sans_admin_access(): void
    {
        $agent = $this->utilisateur(['inscriptions.edit_validated']);

        $this->assertTrue(ESBTPInscription::reaffectationAutoriseePour($agent));
        $this->assertTrue($this->inscription('active')->parcoursModifiablePar($agent));
    }

    public function test_admin_access_reste_accepte(): void
    {
        $direction = $this->utilisateur(['admin.access']);

        $this->assertTrue($this->inscription('active')->parcoursModifiablePar($direction));
    }

    public function test_sans_aucun_des_deux_une_inscription_validee_reste_verrouillee(): void
    {
        $guichet = $this->utilisateur(['inscriptions.edit']);

        $this->assertFalse(ESBTPInscription::reaffectationAutoriseePour($guichet));
        $this->assertFalse($this->inscription('active')->parcoursModifiablePar($guichet));
        $this->assertFalse(ESBTPInscription::reaffectationAutoriseePour(null));
    }

    public function test_une_inscription_non_validee_reste_modifiable_par_tous(): void
    {
        $guichet = $this->utilisateur([]);

        $this->assertTrue($this->inscription('en_attente')->parcoursModifiablePar($guichet));
    }

    private function inscription(string $status): ESBTPInscription
    {
        $inscription = new ESBTPInscription();
        $inscription->setRawAttributes(['status' => $status]);

        return $inscription;
    }

    /** @param array<int, string> $droits */
    private function utilisateur(array $droits): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->andReturnUsing(
            fn (string $ability) => in_array($ability, $droits, true)
        );

        return $user;
    }
}
