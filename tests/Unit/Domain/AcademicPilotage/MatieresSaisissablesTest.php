<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Models\ESBTPClasse;
use App\Services\Notes\MatieresSaisissables;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le modal de saisie des notes proposait tout le catalogue de l'école (35
 * matières sur la démo) pour n'importe quelle classe.
 */
class MatieresSaisissablesTest extends AcademicPilotageDatabaseTestCase
{
    private function base(): void
    {
        Schema::create('esbtp_classes', function (Blueprint $t): void {
            $t->id(); $t->string('name'); $t->string('code')->nullable();
            $t->unsignedBigInteger('filiere_id')->nullable(); $t->unsignedBigInteger('niveau_etude_id')->nullable();
            $t->string('systeme_academique')->nullable(); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->softDeletes();
        });
        // `unite_enseignement_id` obligatoire : sans elle, `btsOnly()` rend vide
        // sans erreur sous SQLite (piège #15 de klassci-debugging-discipline).
        Schema::create('esbtp_matieres', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('unite_enseignement_id')->nullable();
            $t->string('name'); $t->string('code')->nullable(); $t->boolean('is_active')->default(true);
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('esbtp_filieres', function (Blueprint $t): void {
            $t->id(); $t->string('name'); $t->string('code')->nullable();
            $t->boolean('is_tronc_commun')->default(false); $t->unsignedBigInteger('parent_id')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('esbtp_matiere_filiere_niveau', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('matiere_id'); $t->unsignedBigInteger('filiere_id');
            $t->unsignedBigInteger('niveau_etude_id'); $t->string('classification')->nullable();
            $t->unsignedSmallInteger('ordre_bulletin')->nullable(); $t->unsignedTinyInteger('semestre')->nullable();
            $t->boolean('semestre_renseigne')->default(false);
        });
        Schema::create('esbtp_classe_matiere', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('classe_id'); $t->unsignedBigInteger('matiere_id');
            $t->decimal('coefficient')->nullable(); $t->integer('total_heures')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        // ESBTPClasse charge son niveau et son année d'office.
        Schema::create('esbtp_annee_universitaires', function (Blueprint $t): void {
            $t->id(); $t->string('name')->nullable(); $t->boolean('is_current')->default(false);
            $t->timestamps(); $t->softDeletes();
        });
        Schema::create('esbtp_niveau_etudes', function (Blueprint $t): void {
            $t->id(); $t->string('name'); $t->string('type')->nullable(); $t->unsignedInteger('year')->nullable();
            $t->timestamps(); $t->softDeletes();
        });
        Schema::table('esbtp_evaluations', function (Blueprint $t): void {
            $t->string('titre')->nullable(); $t->string('status')->nullable(); $t->softDeletes();
        });

        DB::table('esbtp_filieres')->insert([['id' => 100, 'name' => 'CG', 'code' => 'CG']]);
        DB::table('esbtp_niveau_etudes')->insert([['id' => 200, 'name' => 'BTS 1']]);
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'name' => 'BTS1 CG A', 'code' => 'CG-A', 'filiere_id' => 100, 'niveau_etude_id' => 200, 'systeme_academique' => 'BTS', 'is_active' => true],
            ['id' => 11, 'name' => 'Sans maquette', 'code' => 'SM', 'filiere_id' => null, 'niveau_etude_id' => null, 'systeme_academique' => 'BTS', 'is_active' => true],
        ]);
        DB::table('esbtp_matieres')->insert([
            ['id' => 501, 'name' => 'Comptabilite', 'code' => 'CPT', 'is_active' => true],
            ['id' => 502, 'name' => 'Anglais', 'code' => 'ANG', 'is_active' => true],
            ['id' => 503, 'name' => 'Geologie', 'code' => 'GEO', 'is_active' => true],
            ['id' => 504, 'name' => 'Droit', 'code' => 'DRT', 'is_active' => true],
        ]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([
            ['matiere_id' => 501, 'filiere_id' => 100, 'niveau_etude_id' => 200],
            ['matiere_id' => 502, 'filiere_id' => 100, 'niveau_etude_id' => 200],
        ]);
    }

    public function test_la_liste_se_limite_a_la_maquette_plus_les_matieres_deja_evaluees(): void
    {
        $this->base();
        DB::table('esbtp_evaluations')->insert([
            ['id' => 701, 'titre' => 'Hors maquette', 'classe_id' => 10, 'matiere_id' => 503, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'completed'],
            ['id' => 702, 'titre' => 'Autre annee', 'classe_id' => 10, 'matiere_id' => 504, 'annee_universitaire_id' => 19, 'periode' => 'semestre1', 'status' => 'completed'],
        ]);

        $resultat = app(MatieresSaisissables::class)->pour(ESBTPClasse::find(10), 20);

        $this->assertSame('maquette', $resultat['source']);
        $this->assertSame([
            ['id' => 502, 'name' => 'Anglais', 'hors_maquette' => false],
            ['id' => 501, 'name' => 'Comptabilite', 'hors_maquette' => false],
            ['id' => 503, 'name' => 'Geologie', 'hors_maquette' => true],
        ], $resultat['matieres']);
    }

    public function test_sans_maquette_ni_evaluation_le_catalogue_reste_propose(): void
    {
        $this->base();

        $resultat = app(MatieresSaisissables::class)->pour(ESBTPClasse::find(11), 20);

        $this->assertSame('catalogue', $resultat['source']);
        $this->assertCount(4, $resultat['matieres']);
        $this->assertFalse($resultat['matieres'][0]['hors_maquette']);
    }
}
