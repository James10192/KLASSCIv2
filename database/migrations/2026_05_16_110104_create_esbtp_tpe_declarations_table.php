<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TPE — Travail Personnel Étudiant (UEMOA LMD).
 *
 * Table d'auto-déclaration des heures TPE par étudiant et par ECUE (matière).
 *
 * Options livrées :
 *  - Option 2 : Journal auto-déclaratif étudiant (statut = 'valide' direct
 *    via AutoValidateStrategy quand `tpe.validation.enabled = false`)
 *  - Option 3 : Workflow validation prof (statut = 'en_attente' via
 *    TeacherValidateStrategy quand `tpe.validation.enabled = true`)
 *
 * Le pilotage Option 2 vs Option 3 se fait via Setting tenant
 * `tpe.validation.enabled` (defaut false → tout est auto-validé).
 *
 * Tout le code Option 3 est PRÉSENT mais DORMANT par défaut. L'école active
 * via `/esbtp/settings` (toggle Setting) sans aucune migration ni redeploy.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('esbtp_tpe_declarations')) {
            return; // idempotent
        }

        Schema::create('esbtp_tpe_declarations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('etudiant_id')
                ->constrained('esbtp_etudiants')
                ->cascadeOnDelete();

            // matiere_id pointe sur l'ECUE (esbtp_matieres avec unite_enseignement_id != null)
            $table->foreignId('matiere_id')
                ->constrained('esbtp_matieres')
                ->cascadeOnDelete();

            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires');

            // Lundi de la semaine ISO (clé canonique)
            $table->date('semaine_debut');

            // Heures déclarées (ex: 4.5)
            $table->decimal('heures', 5, 2);

            // Description optionnelle de ce que l'étudiant a fait
            $table->text('description')->nullable();

            // Statut : 'valide' | 'en_attente' | 'rejete' — voir App\Enums\TpeDeclarationStatut
            $table->string('statut', 20)->default('valide');

            // Quand prof valide / rejette
            $table->foreignId('validated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable();

            // Commentaire de rejet (requis si statut = 'rejete')
            $table->text('commentaire_rejet')->nullable();

            // Audit utilisateur
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Une déclaration par étudiant × ECUE × semaine × année
            // (évite les doublons quand l'étudiant resoumet la même semaine)
            $table->unique(
                ['etudiant_id', 'matiere_id', 'semaine_debut', 'annee_universitaire_id'],
                'tpe_decl_unique'
            );

            // Index workflow prof : récupère vite les "en_attente" assignées à un prof
            $table->index(['statut', 'validated_by'], 'tpe_decl_statut_validator_idx');

            // Index étudiant : récupère vite les déclarations d'un étudiant pour une année
            $table->index(['etudiant_id', 'annee_universitaire_id'], 'tpe_decl_etudiant_annee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_tpe_declarations');
    }
};
