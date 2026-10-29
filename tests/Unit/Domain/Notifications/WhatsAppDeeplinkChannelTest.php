<?php

namespace Tests\Unit\Domain\Notifications;

use App\Domain\Notifications\Channels\WhatsAppDeeplinkChannel;
use App\Domain\Notifications\EtudiantContact;
use PHPUnit\Framework\TestCase;

class WhatsAppDeeplinkChannelTest extends TestCase
{
    public function test_dispatch_with_valid_phone_returns_deeplink(): void
    {
        $channel = new WhatsAppDeeplinkChannel();
        $contact = new EtudiantContact(
            etudiantId: 1,
            nomComplet: 'Konan Yao',
            phone: '0707123456',
            email: null,
        );

        $result = $channel->dispatch($contact, 'Bonjour');

        $this->assertTrue($result->success);
        $this->assertSame('whatsapp_deeplink', $result->channel);
        $this->assertFalse($result->automated);
        $this->assertSame('https://wa.me/2250707123456?text=Bonjour', $result->deeplinkUrl);
    }

    public function test_dispatch_url_encodes_message(): void
    {
        $channel = new WhatsAppDeeplinkChannel();
        $contact = new EtudiantContact(1, 'Test', '0707123456', null);

        $result = $channel->dispatch($contact, "Bonjour Konan,\nVotre solde est 50 000 FCFA");

        $this->assertStringContainsString('Bonjour%20Konan%2C%0AVotre%20solde%20est%2050%20000%20FCFA', $result->deeplinkUrl);
    }

    public function test_dispatch_with_invalid_phone_returns_unavailable(): void
    {
        $channel = new WhatsAppDeeplinkChannel();
        $contact = new EtudiantContact(1, 'Test', 'invalid', null);

        $result = $channel->dispatch($contact, 'Bonjour');

        $this->assertFalse($result->success);
        $this->assertNull($result->deeplinkUrl);
        $this->assertSame('Numéro de téléphone invalide ou manquant', $result->errorReason);
    }

    public function test_dispatch_with_null_phone_returns_unavailable(): void
    {
        $channel = new WhatsAppDeeplinkChannel();
        $contact = new EtudiantContact(1, 'Test', null, null);

        $result = $channel->dispatch($contact, 'Bonjour');

        $this->assertFalse($result->success);
    }

    public function test_is_not_automated(): void
    {
        $channel = new WhatsAppDeeplinkChannel();
        $this->assertFalse($channel->isAutomated());
        $this->assertSame('whatsapp_deeplink', $channel->name());
    }
}
