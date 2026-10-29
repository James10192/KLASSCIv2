<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('esbtp_candidature_workflows')) {
            Schema::create('esbtp_candidature_workflows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidature_id')
                    ->unique()
                    ->constrained('esbtp_candidatures')
                    ->cascadeOnDelete();
                $table->foreignId('etudiant_id')
                    ->nullable()
                    ->constrained('esbtp_etudiants')
                    ->nullOnDelete();
                $table->foreignId('paiement_id')
                    ->nullable()
                    ->constrained('esbtp_paiements')
                    ->nullOnDelete();
                $table->foreignId('selected_class_id')
                    ->nullable()
                    ->constrained('esbtp_classes')
                    ->nullOnDelete();
                $table->foreignId('final_inscription_id')
                    ->nullable()
                    ->constrained('esbtp_inscriptions')
                    ->nullOnDelete();

                // Etat du dossier, distinct du statut de la candidature : une
                // candidature reste « acceptee » pendant caisse, pieces et
                // activation, puis devient « convertie » une seule fois.
                $table->string('state', 40)->default('accepted')->index();

                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('documents_validated_at')->nullable();
                $table->foreignId('documents_validated_by')->nullable()->constrained('users')->nullOnDelete();

                // Jamais le jeton brut : seule son empreinte SHA-256 est gardee.
                $table->string('activation_token_hash', 64)->nullable()->unique();
                $table->timestamp('activation_token_expires_at')->nullable();
                $table->timestamp('activation_token_used_at')->nullable();
                $table->timestamp('access_activated_at')->nullable();

                // La finalisation en ligne est une vraie étape : l'étudiant
                // complète ses coordonnées avant de pouvoir confirmer sa classe.
                $table->timestamp('profile_completed_at')->nullable();
                $table->json('profile_payload')->nullable();

                $table->timestamp('class_selected_at')->nullable();
                $table->foreignId('class_selected_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('class_locked_at')->nullable();

                $table->timestamps();

                $table->index(['state', 'paid_at'], 'candidature_workflows_queue_idx');
            });
        }

        // Un versement de preinscription existe AVANT l'inscription academique.
        // `inscription_id` devient nullable (migration 2026_10_01_012319) ; cette cle donne au paiement sa
        // source certaine jusqu'a la conversion finale.
        if (Schema::hasTable('esbtp_paiements') && ! Schema::hasColumn('esbtp_paiements', 'candidature_id')) {
            Schema::table('esbtp_paiements', function (Blueprint $table) {
                $table->foreignId('candidature_id')
                    ->nullable()
                    ->after('inscription_id')
                    ->constrained('esbtp_candidatures')
                    ->nullOnDelete();
                $table->index('candidature_id', 'paiements_candidature_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('esbtp_paiements') && Schema::hasColumn('esbtp_paiements', 'candidature_id')) {
            Schema::table('esbtp_paiements', function (Blueprint $table) {
                $table->dropForeign(['candidature_id']);
                $table->dropIndex('paiements_candidature_idx');
                $table->dropColumn('candidature_id');
            });
        }

        Schema::dropIfExists('esbtp_candidature_workflows');
    }
};
