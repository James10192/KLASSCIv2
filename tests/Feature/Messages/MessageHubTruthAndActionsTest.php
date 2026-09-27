<?php

namespace Tests\Feature\Messages;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Messages\MessageAssistantContextBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MessageHubTruthAndActionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $viewer;
    private User $losseni;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach ([
            'messages.send', 'inscriptions.view', 'inscriptions.validate',
            'paiements.view', 'paiements.create', 'paiements.validate',
            'finances.etudiants.voir', 'admin.access',
        ] as $permission) Permission::findOrCreate($permission, 'web');

        Role::findOrCreate('coordinateur', 'web');
        $this->viewer = User::factory()->create(['name' => 'MARIE TEST', 'is_active' => true]);
        $this->viewer->givePermissionTo(['messages.send', 'inscriptions.view', 'paiements.view', 'finances.etudiants.voir']);

        $this->losseni = User::factory()->create([
            'name' => 'LOSSENI KABIROU COULIBALY',
            'position' => 'Coordinateur',
            'department' => 'Scolarité',
            'is_active' => true,
        ]);
        $this->losseni->assignRole('coordinateur');
        $this->losseni->givePermissionTo('messages.send');
    }

    public function test_losseni_is_coordinateur_and_kipre_is_only_a_shared_item(): void
    {
        [$conversation, $inscription] = $this->losseniKipreConversation();

        $response = $this->actingAs($this->viewer)
            ->getJson(route('message-hub.conversations.show', $conversation))
            ->assertOk()
            ->assertJsonPath('conversation.participants.0.name', 'LOSSENI KABIROU COULIBALY')
            ->assertJsonPath('conversation.participants.0.role_label', 'Coordinateur')
            ->assertJsonPath('conversation.participants.0.account_type', 'staff')
            ->assertJsonPath('linked_entities.0.entity_id', $inscription->id)
            ->assertJsonPath('linked_entities.0.entity_label', 'Inscription — KIPRE JEAN')
            ->assertJsonPath('linked_entities.0.shared_item', true)
            ->assertJsonPath('linked_entities.0.shared_by.name', 'LOSSENI KABIROU COULIBALY')
            ->assertJsonPath('linked_entities.0.shared_by.role_label', 'Coordinateur')
            ->assertJsonPath('messages.0.business_card.shared_item', true)
            ->assertJsonPath('messages.0.business_card.shared_by.name', 'LOSSENI KABIROU COULIBALY');

        $json = $response->getContent();
        $this->assertStringNotContainsString('Relation à vérifier', $json);
        $this->assertStringNotContainsString('Relation à confirmer', $json);
        $this->assertStringNotContainsString('confidence', $json);
        $this->assertNotSame('LOSSENI KABIROU COULIBALY', $response->json('linked_entities.0.details.student_name'));
    }

    public function test_shared_item_actions_depend_on_viewer_permissions_not_sender_relationship(): void
    {
        [$conversation] = $this->losseniKipreConversation();

        $before = $this->actingAs($this->viewer)
            ->getJson(route('message-hub.conversations.show', $conversation))
            ->assertOk();
        $this->assertFalse($before->json('linked_entities.0.can_act'));
        $this->assertFalse($before->json('linked_entities.0.sensitive_actions_allowed'));

        $this->viewer->givePermissionTo('inscriptions.validate');
        $after = $this->actingAs($this->viewer)
            ->getJson(route('message-hub.conversations.show', $conversation))
            ->assertOk();
        $this->assertTrue($after->json('linked_entities.0.can_act'));
        $this->assertTrue($after->json('linked_entities.0.sensitive_actions_allowed'));
        $this->assertSame('LOSSENI KABIROU COULIBALY', $after->json('linked_entities.0.shared_by.name'));
    }

    public function test_nanan_receives_real_authors_and_shared_item_semantics(): void
    {
        [$conversation] = $this->losseniKipreConversation();
        ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'sender_id' => $this->losseni->id,
            'type' => 'text',
            'body' => 'Peux-tu vérifier ce dossier ?',
        ]);
        ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'sender_id' => $this->viewer->id,
            'type' => 'text',
            'body' => 'Je regarde.',
        ]);

        $builder = app(MessageAssistantContextBuilder::class);
        $client = ['current_url' => url('/messages?conversation=' . $conversation->id)];
        $context = $builder->fromClientContext($this->viewer, $client);

        $this->assertSame('LOSSENI KABIROU COULIBALY', $context['participants'][0]['name']);
        $this->assertSame('Inscription — KIPRE JEAN', $context['shared_items'][0]['label']);
        $this->assertSame('LOSSENI KABIROU COULIBALY', $context['shared_items'][0]['shared_by']['name']);
        $this->assertSame('LOSSENI KABIROU COULIBALY', collect($context['messages'])->firstWhere('content', 'Peux-tu vérifier ce dossier ?')['author_name']);

        $prompt = $builder->promptBlock($this->viewer, $client);
        $this->assertStringContainsString('est un ÉLÉMENT PARTAGÉ', $prompt);
        $this->assertStringContainsString('Souhaitez-vous vérifier le paiement, l’état de l’inscription ou préparer une réponse', $prompt);
        $this->assertStringContainsString('Ne déduis JAMAIS que l\'auteur du partage est l\'étudiant', $prompt);
        $this->assertStringNotContainsString('Je ne peux pas établir le lien entre cet interlocuteur et ce dossier', $prompt);
        $this->assertStringNotContainsString('relationship_guard', $prompt);
    }

    public function test_important_and_archived_are_persisted(): void
    {
        $conversation = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
        $conversation->participants()->attach([$this->viewer->id, $this->losseni->id]);
        ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'sender_id' => $this->losseni->id,
            'type' => 'text',
            'body' => 'Test état conversation',
        ]);

        $this->actingAs($this->viewer)->patchJson(route('message-hub.conversations.state', $conversation), [
            'important' => true,
            'archived' => true,
        ])->assertOk()
            ->assertJsonPath('state.important', true)
            ->assertJsonPath('state.archived', true);

        $bootstrap = $this->actingAs($this->viewer)->getJson(route('message-hub.bootstrap'))->assertOk();
        $row = collect($bootstrap->json('conversations'))->firstWhere('id', $conversation->id);
        $this->assertTrue($row['state']['important']);
        $this->assertTrue($row['state']['archived']);
    }

    public function test_teacher_is_distinguished_from_staff_in_nanan_context(): void
    {
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create(['name' => 'ENSEIGNANT TEST', 'position' => 'Enseignant', 'is_active' => true]);
        $teacher->assignRole('teacher');
        $conversation = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
        $conversation->participants()->attach([$this->viewer->id, $teacher->id]);

        $builder = app(MessageAssistantContextBuilder::class);
        $context = $builder->fromClientContext($this->viewer, ['current_url' => url('/messages?conversation=' . $conversation->id)]);
        $this->assertSame('teacher', $context['participants'][0]['account_type']);
    }

    public function test_legacy_card_created_by_viewer_is_projected_to_the_conversation_peer(): void
    {
        [$conversation, $inscription] = $this->losseniKipreConversation();
        $conversation->messages()->where('type', 'action_card')->update(['sender_id' => $this->viewer->id]);

        $response = $this->actingAs($this->viewer)
            ->getJson(route('message-hub.conversations.show', $conversation))
            ->assertOk();

        $this->assertSame($inscription->id, $response->json('linked_entities.0.entity_id'));
        $this->assertSame('LOSSENI KABIROU COULIBALY', $response->json('linked_entities.0.shared_by.name'));
        $this->assertSame('Coordinateur', $response->json('linked_entities.0.shared_by.role_label'));
    }

    private function losseniKipreConversation(): array
    {
        $student = ESBTPEtudiant::factory()->create(['nom' => 'KIPRE', 'prenoms' => 'JEAN', 'matricule' => 'DEMO01197']);
        $inscription = ESBTPInscription::factory()->create(['etudiant_id' => $student->id]);
        $conversation = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
        $conversation->participants()->attach([$this->viewer->id, $this->losseni->id]);

        ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'sender_id' => $this->losseni->id,
            'type' => 'action_card',
            'payload' => [
                'kind' => 'inscription',
                'id' => $inscription->id,
                'snapshot' => [
                    'etudiant' => ['id' => $student->id, 'name' => 'KIPRE JEAN', 'matricule' => 'DEMO01197'],
                ],
            ],
        ]);

        return [$conversation, $inscription, $student];
    }
}
