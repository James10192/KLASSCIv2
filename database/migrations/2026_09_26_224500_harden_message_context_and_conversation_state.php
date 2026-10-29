<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_conversation_participants', function (Blueprint $table) {
            $table->timestamp('important_at')->nullable()->after('last_read_at');
            $table->timestamp('archived_at')->nullable()->after('important_at');
            $table->timestamp('pinned_at')->nullable()->after('archived_at');
        });

        Schema::create('chat_conversation_entity_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_label')->nullable();
            $table->string('relation_type', 50)->default('unknown_verify');
            $table->string('confidence', 20)->default('unknown');
            $table->string('source_type', 50)->default('legacy_action_card');
            $table->foreignId('source_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->foreignId('related_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('required_view_permission', 120)->nullable();
            $table->string('required_detail_permission', 120)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chat_conversation_id', 'entity_type', 'entity_id'], 'chat_entity_conv_type_id_ix');
            $table->index(['entity_type', 'entity_id'], 'chat_entity_type_id_ix');
            $table->unique(['source_message_id', 'entity_type', 'entity_id'], 'chat_entity_source_unique');
        });

        Schema::create('chat_context_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('chat_conversation_id')->nullable()->constrained('chat_conversations')->nullOnDelete();
            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('purpose', 50);
            $table->boolean('allowed')->default(false);
            $table->string('permission_checked', 120)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at'], 'chat_context_access_user_date_ix');
            $table->index(['chat_conversation_id', 'created_at'], 'chat_context_access_conv_date_ix');
        });

        $this->backfillLegacyActionCards();
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_context_access_logs');
        Schema::dropIfExists('chat_conversation_entity_links');

        Schema::table('chat_conversation_participants', function (Blueprint $table) {
            $table->dropColumn(['important_at', 'archived_at', 'pinned_at']);
        });
    }

    private function backfillLegacyActionCards(): void
    {
        if (! Schema::hasTable('chat_messages')) {
            return;
        }

        DB::table('chat_messages')
            ->where('type', 'action_card')
            ->select(['id', 'chat_conversation_id', 'sender_id', 'payload', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($messages) {
                foreach ($messages as $message) {
                    $payload = json_decode((string) $message->payload, true);
                    if (! is_array($payload)) {
                        continue;
                    }

                    $kind = $payload['kind'] ?? null;
                    $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT);
                    if (! $kind || ! $id || ! in_array($kind, ['inscription', 'paiement'], true)) {
                        continue;
                    }

                    $entity = $kind === 'inscription'
                        ? $this->legacyInscription((int) $id)
                        : $this->legacyPaiement((int) $id);

                    DB::table('chat_conversation_entity_links')->updateOrInsert(
                        [
                            'source_message_id' => $message->id,
                            'entity_type' => $kind,
                            'entity_id' => (int) $id,
                        ],
                        [
                            'chat_conversation_id' => $message->chat_conversation_id,
                            'entity_label' => $entity['label'],
                            'relation_type' => 'unknown_verify',
                            'confidence' => 'unknown',
                            'source_type' => 'legacy_action_card',
                            'related_user_id' => null,
                            'required_view_permission' => $entity['view_permission'],
                            'required_detail_permission' => $entity['detail_permission'],
                            'created_by' => $message->sender_id,
                            'verified_by' => null,
                            'verified_at' => null,
                            'metadata' => json_encode([
                                'migrated_from' => 'chat_messages.action_card',
                                'legacy_payload_kind' => $kind,
                                'entity_found' => $entity['found'],
                            ], JSON_UNESCAPED_UNICODE),
                            'created_at' => $message->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }, 'id');
    }

    private function legacyInscription(int $id): array
    {
        $row = DB::table('esbtp_inscriptions as i')
            ->leftJoin('esbtp_etudiants as e', 'e.id', '=', 'i.etudiant_id')
            ->where('i.id', $id)
            ->select(['i.id', 'e.nom', 'e.prenoms'])
            ->first();

        $name = $row ? trim(((string) ($row->nom ?? '')) . ' ' . ((string) ($row->prenoms ?? ''))) : '';

        return [
            'found' => (bool) $row,
            'label' => $row
                ? 'Inscription — ' . ($name !== '' ? $name : 'Données indisponibles')
                : "Inscription #{$id} — Données indisponibles",
            'view_permission' => 'inscriptions.view',
            'detail_permission' => 'finances.etudiants.voir',
        ];
    }

    private function legacyPaiement(int $id): array
    {
        $row = DB::table('esbtp_paiements as p')
            ->leftJoin('esbtp_etudiants as e', 'e.id', '=', 'p.etudiant_id')
            ->where('p.id', $id)
            ->select(['p.id', 'e.nom', 'e.prenoms'])
            ->first();

        $name = $row ? trim(((string) ($row->nom ?? '')) . ' ' . ((string) ($row->prenoms ?? ''))) : '';

        return [
            'found' => (bool) $row,
            'label' => $row
                ? 'Paiement — ' . ($name !== '' ? $name : 'Données indisponibles')
                : "Paiement #{$id} — Données indisponibles",
            'view_permission' => 'paiements.view',
            'detail_permission' => 'finances.etudiants.voir',
        ];
    }
};
