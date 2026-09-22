<?php

namespace Tests\Unit\Domain\Notes;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les tables que lisent et écrivent le recalcul des moyennes et la fusion
 * d'ECUE, sur une base SQLite en mémoire.
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
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->softDeletes();
                $t->timestamps();
            });
        }
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
            // Même ENUM que la migration : c'est lui qui refuserait une source inconnue.
            $t->enum('source', ['observer', 'command', 'manual']);
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
        // Les modèles s'amorcent avec l'application — avant que ce trait ne
        // coupe `audit.enabled` — et `bootAuditable()` ne relit plus ce réglage
        // ensuite : un `save()` écrit donc bien son audit, comme en production.
        Schema::create('audits', function (Blueprint $t) {
            $t->id();
            $t->nullableMorphs('user');
            $t->string('event');
            $t->morphs('auditable');
            $t->text('old_values')->nullable();
            $t->text('new_values')->nullable();
            $t->text('url')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 1023)->nullable();
            $t->string('tags')->nullable();
            $t->timestamps();
        });
        Schema::create('esbtp_etudiants', function (Blueprint $t) {
            $t->id();
            $t->string('nom')->nullable();
            $t->string('prenoms')->nullable();
            $t->string('matricule')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_lmd_bulletins', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('etudiant_id');
            $t->unsignedBigInteger('classe_id');
            $t->unsignedBigInteger('annee_universitaire_id');
            $t->unsignedTinyInteger('semestre');
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('esbtp_lmd_resultats_ecues', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bulletin_id');
            $t->unsignedBigInteger('resultat_ue_id');
            $t->unsignedBigInteger('matiere_id');
            $t->unsignedBigInteger('etudiant_id');
            $t->decimal('moyenne', 5, 2)->nullable();
            $t->decimal('note_rattrapage', 5, 2)->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->unique(['bulletin_id', 'matiere_id']);
        });
    }
}
