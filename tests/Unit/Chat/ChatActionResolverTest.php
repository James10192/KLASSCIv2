<?php

namespace Tests\Unit\Chat;

use App\Models\ChatMessage;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\ChatActionResolver;
use Tests\TestCase;

class ChatActionResolverTest extends TestCase
{
    public function test_un_message_texte_n_a_pas_de_cta(): void
    {
        $message = new ChatMessage(['type' => 'text', 'payload' => null]);
        $viewer = new User;

        $this->assertNull((new ChatActionResolver)->resolveCta(
            $message,
            $viewer,
            ['inscriptions' => collect(), 'paiements' => collect()]
        ));
    }

    public function test_le_cta_est_une_url_pas_une_mutation(): void
    {
        $inscription = new ESBTPInscription;
        $inscription->id = 7;
        $inscription->workflow_step = 'etudiant_cree';
        $inscription->status = 'active';

        $message = new ChatMessage;
        $message->type = 'action_card';
        $message->payload = ['kind' => 'inscription', 'id' => 7];

        $viewer = $this->createMock(User::class);
        $viewer->method('can')->willReturnCallback(fn (string $perm) => $perm === 'inscriptions.view');

        $cta = (new ChatActionResolver)->resolveCta(
            $message,
            $viewer,
            ['inscriptions' => collect([7 => $inscription]), 'paiements' => collect()]
        );

        $this->assertIsArray($cta);
        $this->assertArrayHasKey('url', $cta);
        $this->assertArrayNotHasKey('execute', $cta);
        $this->assertArrayNotHasKey('action', $cta);
        $this->assertDoesNotMatchRegularExpression('#/store|/destroy|/update#', $cta['url']);
    }

    public function test_sans_permission_le_cta_lecture_disparait(): void
    {
        $inscription = new ESBTPInscription;
        $inscription->id = 7;
        $inscription->workflow_step = 'etudiant_cree';

        $message = new ChatMessage;
        $message->type = 'action_card';
        $message->payload = ['kind' => 'inscription', 'id' => 7];

        $viewer = $this->createMock(User::class);
        $viewer->method('can')->willReturn(false);

        $this->assertNull((new ChatActionResolver)->resolveCta(
            $message,
            $viewer,
            ['inscriptions' => collect([7 => $inscription]), 'paiements' => collect()]
        ));
    }
}
