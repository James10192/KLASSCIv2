<?php

namespace Tests\Feature\Messages;

use App\Events\WorkflowStepCompleted;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Notifications\WorkflowNextStepNotification;
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
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('secretaire', 'web');

        $this->viewer = User::factory()->create(['name' => 'MARIE TEST', 'is_active' => true]);
        $this->viewer->givePermissionTo([
            'messages.send', 'inscriptions.view', 'paiements.view',
            'finances.etudiants.voir', 'paiements.create', 'paiements.validate',
        ]);

        $this->losseni = User::factory()->create([
            'name' => 'LOSSENI KABIROU COULIBALY',
            'position' => 'Agent administratif',
            'department' => 'Scolarité',
            'is_active' => true,
        ]);
        $this->losseni->assignRole('secretaire');
        $this->losseni->givePermissionTo('messages.send');
    }

    public function test_losseni_stays_staff_and_kipre_is_the_distinct_linked_student(): void
    {
        [$conversation, $inscription] = $this->losseniKipreConversation();

        $response = $this->actingAs($this->viewer)
            ->getJson(route('message-hub.conversations.show', $conversation));

        $response->assertOk()
            ->assertJsonPath('conversation.participants.0.name', 'LOSSENI KABIROU COULIBALY')
            ->assertJsonPath('conversation.participants.0.account_type', 'staff')
            ->assertJsonPath('linked_entities.0.entity_id', $inscription->id)
            ->assertJsonPath('linked_entities.0.entity_label', 'Inscription — KIPRE JEAN')
            ->assertJsonPath('linked_entities.0.relation_label', 'Relation à vérifier')
            ->assertJsonPath('linked_entities.0.relation_verified', false)
            ->assertJsonPath('linked_entities.0.can_verify', false)
            ->assertJsonPath('linked_entities.0.sensitive_actions_allowed', false)
            ->assertJsonPath('messages.0.business_card.entity_label', 'Inscription — KIPRE JEAN')
            ->assertJsonPath('messages.0.business_card.sensitive_actions_allowed', false);

        $json = $response->getContent();
        $this->assertStringNotContainsString('Dossier KLASSCI', $json);
        $this->assertStringNotContainsString('À consulter', $json);
        $this->assertNotSame(
            'LOSSENI KABIROU COULIBALY',
            $response->json('linked_entities.0.details.student_name'),
            'Le participant ne doit jamais devenir l’étudiant lié par fallback.'
        );
    }

    public function test_link_verification_requires_validator_permission_and_non_debtor_relations_stay_safe(): void
    {
        [$conversation] = $this->losseniKipreConversation();
        $context = $this->actingAs($this->viewer)
            ->getJson(route('message-hub.conversations.show', $conversation))
            ->assertOk();
        $linkId = $context->json('linked_entities.0.id');

        $this->actingAs($this->viewer)->patchJson(route('message-hub.entity-links.update', $linkId), [
            'relation' => 'shared_by',
            'related_user_id' => $this->losseni->id,
        ])->assertForbidden();

        $this->viewer->givePermissionTo('inscriptions.validate');

        $verified = $this->actingAs($this->viewer)->patchJson(route('message-hub.entity-links.update', $linkId), [
            'relation' => 'shared_by',
            'related_user_id' => $this->losseni->id,
        ]);

        $verified->assertOk()
            ->assertJsonPath('link.relation_verified', true)
            ->assertJsonPath('link.relation_label', 'Partagé par cet interlocuteur')
            ->assertJsonPath('link.can_verify', true)
            ->assertJsonPath('link.sensitive_actions_allowed', false);

        $this->assertDatabaseHas('chat_conversation_entity_links', [
            'id' => $linkId,
            'relation_type' => 'shared_by',
            'related_user_id' => $this->losseni->id,
            'verified_by' => $this->viewer->id,
            'confidence' => 'verified',
        ]);
    }

    public function test_nanan_receives_real_authors_and_must_refuse_financial_conclusion_when_link_is_ambiguous(): void
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
        $this->assertSame('staff', $context['participants'][0]['account_type']);
        $this->assertTrue($context['relationship_guard']['ambiguous']);
        $this->assertFalse($context['relationship_guard']['financial_or_enrolment_action_allowed']);
        $this->assertSame('LOSSENI KABIROU COULIBALY', collect($context['messages'])->firstWhere('content', 'Peux-tu vérifier ce dossier ?')['author_name']);
        $this->assertSame('MARIE TEST', collect($context['messages'])->firstWhere('content', 'Je regarde.')['author_name']);

        $prompt = $builder->promptBlock($this->viewer, $client);
        $this->assertStringContainsString('Je ne peux pas établir le lien entre cet interlocuteur et ce dossier', $prompt);
        $this->assertStringContainsString('ne recommande ni validation d’inscription, ni validation de paiement, ni relance financière', $prompt);
        $this->assertStringNotContainsString("Moi: Peux-tu vérifier ce dossier ?", $prompt);
    }

    public function test_historical_workflow_notifications_reappear_in_action_center(): void
    {
        $student = ESBTPEtudiant::factory()->create(['nom' => 'KIPRE', 'prenoms' => 'JEAN']);
        $inscription = ESBTPInscription::factory()->create(['etudiant_id' => $student->id]);

        $this->viewer->notify(new WorkflowNextStepNotification(new WorkflowStepCompleted(
            'inscription.created',
            $this->losseni,
            ['inscription_id' => $inscription->id],
        )));

        $response = $this->actingAs($this->viewer)->getJson(route('message-hub.bootstrap'));
        $response->assertOk();

        $legacy = collect($response->json('actions'))->firstWhere('source', 'legacy_notification');
        $this->assertNotNull($legacy);
        $this->assertSame('todo', $legacy['status']);
        $this->assertSame('Inscription — KIPRE JEAN', $legacy['subject']);
        $this->assertGreaterThanOrEqual(1, $response->json('counts.actions_open'));
    }

    public function test_internal_action_is_created_assigned_updated_and_audited(): void
    {
        $assignee = User::factory()->create(['name' => 'RESPONSABLE SCOLARITE', 'is_active' => true]);
        $assignee->givePermissionTo('messages.send');

        $create = $this->actingAs($this->viewer)->postJson(route('message-hub.actions.store'), [
            'action_type' => 'internal_request',
            'title' => 'Vérifier le dossier avant relance',
            'description' => 'Contrôler la relation entre le contact et le dossier.',
            'priority' => 'high',
            'service' => 'Scolarité',
            'assigned_to' => $assignee->id,
            'context_data' => ['subject' => 'Inscription — KIPRE JEAN'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('action.status', 'todo')
            ->assertJsonPath('action.assignee', 'RESPONSABLE SCOLARITE');

        $id = $create->json('action.id');
        $this->assertDatabaseHas('workflow_actions', ['id' => $id, 'assigned_to' => $assignee->id, 'priority' => 'high']);
        $this->assertDatabaseHas('workflow_action_activities', ['workflow_action_id' => $id, 'event' => 'created']);
        $this->assertDatabaseHas('workflow_action_activities', ['workflow_action_id' => $id, 'event' => 'assigned']);

        $this->actingAs($assignee)->patchJson(route('message-hub.actions.update', $id), [
            'status' => 'in_progress',
            'comment' => 'Vérification commencée.',
        ])->assertOk()->assertJsonPath('action.status', 'in_progress');

        $this->assertDatabaseHas('workflow_actions', ['id' => $id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('workflow_action_activities', ['workflow_action_id' => $id, 'event' => 'status_changed']);
    }

    public function test_important_and_archived_are_persisted_and_bootstrap_preview_is_typed_text(): void
    {
        $conversation = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
        $conversation->participants()->attach([$this->viewer->id, $this->losseni->id]);
        ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'sender_id' => $this->losseni->id,
            'type' => 'action_card',
            'payload' => ['kind' => 'unknown', 'id' => 42],
        ]);
        $conversation->update(['last_message_at' => now()]);

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
        $this->assertIsString($row['last_message_preview']['preview']);
        $this->assertStringNotContainsString('[object Object]', $row['last_message_preview']['preview']);
    }

    private function losseniKipreConversation(): array
    {
        $student = ESBTPEtudiant::factory()->create([
            'nom' => 'KIPRE',
            'prenoms' => 'JEAN',
            'matricule' => 'KIPRE-001',
        ]);
        $inscription = ESBTPInscription::factory()->create(['etudiant_id' => $student->id]);
        $conversation = ChatConversation::create(['type' => 'dm', 'last_message_at' => now()]);
        $conversation->participants()->attach([$this->viewer->id, $this->losseni->id]);

        ChatMessage::create([
            'chat_conversation_id' => $conversation->id,
            'sender_id' => $this->viewer->id,
            'type' => 'action_card',
            'payload' => [
                'kind' => 'inscription',
                'id' => $inscription->id,
                'snapshot' => [
                    'etudiant' => ['id' => $student->id, 'name' => 'KIPRE JEAN', 'matricule' => 'KIPRE-001'],
                ],
            ],
        ]);

        return [$conversation, $inscription, $student];
    }
}
