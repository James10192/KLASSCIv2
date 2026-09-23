<?php

namespace Tests\Unit\Domain\Notes;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OwenIt\Auditing\Models\Audit;

/**
 * Les tables que lisent et écrivent le recalcul des moyennes, sur une base
 * SQLite en mémoire.
 *
 * Écrit à la main parce que la chaîne de migrations ne passe pas sous SQLite.
 * Chaque colonne que le code FILTRE y figure, pas seulement celles qu'il lit :
 * une colonne manquante y rend `false` sans erreur, et un test passerait pour
 * la mauvaise raison (piège #15 de `klassci-debugging-discipline.md`).
 */
trait SchemaDesMoyennes
{
    protected function monterLeSchemaDesMoyennes(): void
    {
        config()->set('audit.enabled', false);
        // L'observateur d'audit s'accroche au premier démarrage du modèle, et
        // ce démarrage survit d'un test à l'autre : le réglage ci-dessus arrive
        // trop tard pour un modèle déjà démarré. Seul l'interrupteur global
        // tient à coup sûr (il n'y a pas de table `audits` ici).
        Audit::$auditingGloballyDisabled = true;
        config()->set('queue.default', 'sync');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->creerLesTables();
    }

    protected function demonterLeSchemaDesMoyennes(): void
    {
        Audit::$auditingGloballyDisabled = false;
        DB::disconnect('sqlite');
    }

    private function creerLesTables(): void
    {
        Schema::create('esbtp_classes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('systeme_academique')->nullable();
            $t->unsignedBigInteger('filiere_id')->nullable();
            $t->unsignedBigInteger('niveau_etude_id')->nullable();
            $t->unsignedBigInteger('annee_universitaire_id')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        // `ESBTPClasse::$with` charge ces trois relations à chaque lecture : le
        // garde de cohérence d'`ESBTPResultat` lit la classe dès qu'une ligne
        // de résultat est CRÉÉE, donc ces tables doivent exister, même vides.
        foreach (['esbtp_filieres', 'esbtp_niveau_etudes', 'esbtp_annee_universitaires'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                if ($table === 'esbtp_annee_universitaires') {
                    // Lu par l'écran d'édition des évaluations.
                    $t->boolean('is_current')->default(false);
                    // Filtrées par `esbtp:check-evaluations-annees` pour dater une évaluation.
                    $t->date('start_date')->nullable();
                    $t->date('end_date')->nullable();
                }
                $t->softDeletes();
                $t->timestamps();
            });
        }
        // Lu par le garde de période d'`ESBTPEvaluation` à chaque changement
        // de semestre, et par la réparation `evaluations-periode`.
        Schema::create('esbtp_classe_orientation_targets', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('source_classe_id')->nullable();
            $t->unsignedBigInteger('target_classe_id');
            $t->unsignedTinyInteger('semestre_activation')->default(1);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        // Source de l'année d'une évaluation qui n'en a pas
        // (`esbtp:check-evaluations-annees`) : une classe n'en porte aucune.
        Schema::create('esbtp_inscriptions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('etudiant_id');
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->unsignedBigInteger('annee_universitaire_id');
            $t->string('status')->default('active');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_matieres', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->unsignedBigInteger('unite_enseignement_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_evaluations', function (Blueprint $t) {
            $t->id();
            $t->string('titre');
            // Écrites par l'écran d'édition (`ESBTPEvaluationController::update()`).
            $t->text('description')->nullable();
            $t->string('type')->nullable();
            $t->dateTime('date_evaluation')->nullable();
            $t->integer('duree_minutes')->nullable();
            $t->boolean('is_published')->default(false);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            // Remis à nul par la synchronisation du devoir d'une séance.
            $t->unsignedBigInteger('enseignant_id')->nullable();
            $t->unsignedBigInteger('matiere_id')->nullable();
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->unsignedBigInteger('annee_universitaire_id')->nullable();
            $t->string('periode')->nullable();
            $t->string('status')->default('draft');
            $t->decimal('bareme', 5, 2)->default(20);
            $t->decimal('coefficient', 5, 2)->default(1);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_notes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('evaluation_id');
            $t->unsignedBigInteger('etudiant_id');
            $t->unsignedBigInteger('matiere_id')->nullable();
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->string('semestre')->nullable();
            $t->decimal('note', 5, 2)->nullable();
            $t->boolean('is_absent')->default(false);
            $t->timestamp('archived_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_resultats', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('etudiant_id');
            $t->unsignedBigInteger('classe_id');
            $t->unsignedBigInteger('matiere_id');
            $t->string('periode');
            $t->unsignedBigInteger('annee_universitaire_id');
            $t->decimal('moyenne', 5, 2)->nullable();
            $t->decimal('coefficient', 5, 2)->default(1);
            $t->integer('rang')->nullable();
            $t->string('appreciation')->nullable();
            $t->unsignedBigInteger('enseignant_id')->nullable();
            $t->string('type')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamp('archived_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_resultats_recompute_log', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('etudiant_id');
            $t->unsignedBigInteger('classe_id');
            $t->unsignedBigInteger('matiere_id');
            $t->string('periode', 32);
            $t->unsignedBigInteger('annee_universitaire_id');
            $t->decimal('moyenne_avant', 5, 2)->nullable();
            $t->decimal('moyenne_apres', 5, 2);
            // `string(30)` depuis `elargir_source_du_journal_de_recalcul`.
            $t->string('source', 30);
            $t->unsignedBigInteger('triggered_by')->nullable();
            $t->timestamp('recomputed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('esbtp_bulletins', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('etudiant_id');
            $t->unsignedBigInteger('classe_id');
            $t->unsignedBigInteger('annee_universitaire_id');
            $t->string('periode');
            $t->timestamp('archived_at')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_ue_matiere', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('unite_enseignement_id');
            $t->unsignedBigInteger('matiere_id');
            $t->unsignedBigInteger('parcours_id')->default(0);
            $t->timestamps();
        });
        Schema::create('esbtp_planifications_academiques', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('matiere_id');
            $t->timestamps();
        });
        Schema::create('esbtp_matiere_filiere', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('filiere_id');
            $t->unsignedBigInteger('matiere_id');
        });
    }
}
