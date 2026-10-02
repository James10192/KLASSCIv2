<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Réclamations de notes : l'élève conteste, l'enseignant donne son avis, le
 * personnel habilité tranche.
 *
 * Sème aussi les deux réglages d'école (activé, délai). Les clés sont écrites
 * en toutes lettres : une migration décrit un état figé.
 */
return new class extends Migration
{
    private const REGLAGES = [
        'notes.reclamations.enabled' => [
            'value' => '1', 'type' => 'boolean', 'sort_order' => 173,
            'description' => 'Les élèves peuvent contester une note depuis leur espace, photo de la copie à l’appui.',
        ],
        'notes.reclamations.delai_jours' => [
            'value' => '15', 'type' => 'integer', 'sort_order' => 174,
            'description' => 'Nombre de jours, après la saisie d’une note, pendant lesquels l’élève peut la contester.',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('esbtp_reclamations_notes')) {
            Schema::create('esbtp_reclamations_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('note_id')->constrained('esbtp_notes')->cascadeOnDelete();
                $table->foreignId('etudiant_id')->constrained('esbtp_etudiants')->cascadeOnDelete();
                $table->unsignedBigInteger('evaluation_id')->index();
                $table->unsignedBigInteger('matiere_id')->nullable()->index();
                $table->unsignedBigInteger('classe_id')->nullable()->index();
                $table->unsignedBigInteger('annee_universitaire_id')->nullable()->index();
                $table->foreignId('enseignant_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('note_initiale', 5, 2)->nullable()->comment('Valeur contestée, figée au dépôt ; null = absent');
                $table->text('motif');
                $table->string('photo_path');
                $table->string('statut', 20)->default('soumise')->comment('App\Enums\StatutReclamationNote');
                $table->string('avis', 20)->nullable()->comment('confirmer | corriger');
                $table->decimal('note_proposee', 5, 2)->nullable();
                $table->text('commentaire_enseignant')->nullable();
                $table->foreignId('avis_par')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('avis_at')->nullable();
                $table->text('commentaire_decision')->nullable();
                $table->decimal('note_finale', 5, 2)->nullable();
                $table->foreignId('decision_par')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('decision_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['statut', 'created_at']);
                $table->index(['note_id', 'statut']);
            });
        }

        $createur = DB::table('users')->min('id');
        foreach (self::REGLAGES as $cle => $r) {
            if (DB::table('settings')->where('key', $cle)->exists()) {
                continue;
            }
            DB::table('settings')->insert([
                'key' => $cle,
                'value' => $r['value'],
                'type' => $r['type'],
                'group' => 'scolarite',
                'category' => 'scolarite',
                'default_value' => $r['value'],
                'description' => $r['description'],
                'is_required' => 0,
                'validation_rules' => null,
                'is_active' => 1,
                'sort_order' => $r['sort_order'],
                'created_by' => $createur,
                'updated_by' => $createur,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_reclamations_notes');
        DB::table('settings')->whereIn('key', array_keys(self::REGLAGES))->delete();
    }
};
