<?php

namespace Tests\Unit\Auth;

use App\Http\Controllers\Auth\ForgotPasswordController;
use Symfony\Component\Mailer\Exception\TransportException;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class LienDeReinitialisationNonEnvoyeTest extends TestCase
{
    /** @test */
    public function un_courriel_refuse_devient_un_message_a_l_ecran_et_une_ligne_au_journal(): void
    {
        $courtier = Mockery::mock(PasswordBroker::class);
        $courtier->shouldReceive('sendResetLink')->once()->andThrow(new TransportException('plafond'));
        Password::shouldReceive('broker')->andReturn($courtier);
        Log::spy();

        try {
            (new ForgotPasswordController())->sendResetLinkEmail(Request::create('/password/email', 'POST', ['email' => 'a@example.com']));
            $this->fail('Une ValidationException était attendue.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString("n'a pas pu partir", $e->errors()['email'][0]);
        }

        Log::shouldHaveReceived('warning')->once();
    }
}
