<?php

namespace Tests\Unit\Support;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\Messages\MessageHubProjection;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class MessageHubProjectionTest extends TestCase
{
    public function test_it_separates_human_conversations_from_legacy_workflow_actions(): void
    {
        $viewer = new User(['name' => 'Direction']);
        $viewer->id = 1;

        $recipient = new User(['name' => 'Service Scolarité', 'email' => 'scolarite@example.test']);
        $recipient->id = 2;

        $dm = new ChatConversation([
            'type' => 'dm',
            'last_message_at' => Carbon::parse('2026-09-26 12:00:00'),
        ]);
        $dm->id = 10;
        $dm->setRelation('participants', new Collection([$viewer, $recipient]));
        $dm->setRelation('lastMessage', new ChatMessage(['type' => 'text', 'body' => 'Bonjour']));

        $workflow = new ChatConversation([
            'type' => 'workflow',
            'title' => 'Paiement à valider',
            'context' => ['priority' => 'urgent', 'service' => 'Caisse'],
            'last_message_at' => Carbon::parse('2026-09-26 13:00:00'),
        ]);
        $workflow->id = 11;
        $workflow->created_at = Carbon::parse('2026-09-26 11:00:00');
        $workflow->setRelation('participants', new Collection([$viewer, $recipient]));
        $workflow->setRelation('lastMessage', new ChatMessage([
            'type' => 'action_card',
            'payload' => ['kind' => 'paiement', 'etudiant' => 'Awa Kouassi', 'montant' => 150000],
        ]));

        $hub = (new MessageHubProjection())->project(new Collection([$dm, $workflow]), $viewer);

        $this->assertCount(1, $hub['conversation']);
        $this->assertSame(10, $hub['conversation'][0]['id']);
        $this->assertSame('Service Scolarité', $hub['conversation'][0]['title']);

        $this->assertCount(1, $hub['workflowAction']);
        $this->assertSame(11, $hub['workflowAction'][0]['id']);
        $this->assertSame('paiement', $hub['workflowAction'][0]['type']);
        $this->assertSame('urgent', $hub['workflowAction'][0]['priority']);
        $this->assertSame('Caisse', $hub['workflowAction'][0]['service']);
        $this->assertSame('Awa Kouassi', $hub['workflowAction'][0]['subject']);
    }
}
