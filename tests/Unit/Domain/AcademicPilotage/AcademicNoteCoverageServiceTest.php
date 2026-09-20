<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicNoteCoverageServiceTest extends AcademicPilotageDatabaseTestCase
{
    public function test_it_summarizes_subject_evaluation_student_and_actor_note_coverage(): void
    {
        $this->createReferenceTables();

        DB::table('users')->insert([
            ['id' => 41, 'name' => 'Awa Koffi'],
            ['id' => 42, 'name' => 'Mariam Yao'],
        ]);
        DB::table('esbtp_filieres')->insert([
            ['id' => 100, 'name' => 'Comptabilite et Gestion', 'code' => 'CG', 'is_tronc_commun' => false, 'parent_id' => null, 'is_active' => true],
        ]);
        DB::table('esbtp_niveau_etudes')->insert([
            ['id' => 200, 'name' => 'BTS 1', 'type' => 'BTS', 'year' => 1],
        ]);
        DB::table('esbtp_classes')->insert([
            ['id' => 10, 'name' => 'BTS1 CG A', 'code' => 'CG-A', 'filiere_id' => 100, 'niveau_etude_id' => 200, 'systeme_academique' => 'BTS', 'is_active' => true],
        ]);
        DB::table('esbtp_matieres')->insert([
            ['id' => 501, 'name' => 'Comptabilite', 'code' => 'CPT', 'is_active' => true],
            ['id' => 502, 'name' => 'Anglais', 'code' => 'ANG', 'is_active' => true],
        ]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([
            ['matiere_id' => 501, 'filiere_id' => 100, 'niveau_etude_id' => 200],
            ['matiere_id' => 502, 'filiere_id' => 100, 'niveau_etude_id' => 200],
        ]);
        DB::table('esbtp_etudiants')->insert([
            ['id' => 301, 'nom' => 'Kouadio', 'prenoms' => 'Awa', 'matricule' => 'M301'],
            ['id' => 302, 'nom' => 'Yao', 'prenoms' => 'Eric', 'matricule' => 'M302'],
        ]);
        DB::table('esbtp_inscriptions')->insert([
            ['etudiant_id' => 301, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => now(), 'updated_at' => now()],
            ['etudiant_id' => 302, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => now(), 'updated_at' => now()],
            ['etudiant_id' => 999, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'en_attente', 'workflow_step' => 'prospect', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('esbtp_evaluations')->insert([
            ['id' => 701, 'titre' => 'Devoir 1', 'classe_id' => 10, 'matiere_id' => 501, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'completed', 'type' => 'devoir', 'date_evaluation' => now()],
            ['id' => 702, 'titre' => 'Oral', 'classe_id' => 10, 'matiere_id' => 502, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'completed', 'type' => 'devoir', 'date_evaluation' => now()],
            ['id' => 703, 'titre' => 'Annule', 'classe_id' => 10, 'matiere_id' => 502, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'cancelled', 'type' => 'devoir', 'date_evaluation' => now()],
        ]);
        DB::table('esbtp_notes')->insert([
            ['evaluation_id' => 701, 'etudiant_id' => 301, 'matiere_id' => 501, 'note' => 14, 'is_absent' => false, 'created_by' => 41, 'updated_by' => 42, 'created_at' => now()->subMinute(), 'updated_at' => now()],
            ['evaluation_id' => 701, 'etudiant_id' => 302, 'matiere_id' => 501, 'note' => 0, 'is_absent' => true, 'created_by' => 41, 'updated_by' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre1', 'BTS', 10);

        $this->assertSame(2, $result['summary']['subjects_total']);
        $this->assertSame(1, $result['summary']['subjects_evaluated']);
        $this->assertSame(0, $result['summary']['orphan_subjects']);
        $this->assertSame(2, $result['summary']['evaluations_total']);
        $this->assertSame(2, $result['summary']['students_expected']);
        $this->assertSame(4, $result['summary']['expected_results']);
        $this->assertSame(2, $result['summary']['treated_results']);
        $this->assertSame(2, $result['summary']['missing_results']);
        $this->assertSame(2, $result['summary']['incomplete_students']);
        $this->assertSame(2, $result['summary']['actors_count']);

        $comptabilite = collect($result['subjects'])->firstWhere('id', 501);
        $this->assertSame(2, $comptabilite['treated_count']);
        $this->assertSame(0, $comptabilite['missing_count']);
        $this->assertSame(1, $comptabilite['numeric_count']);
        $this->assertSame(1, $comptabilite['absent_count']);

        $anglais = collect($result['subjects'])->firstWhere('id', 502);
        $this->assertSame(2, $anglais['missing_count']);
        $this->assertSame(['Kouadio Awa', 'Yao Eric'], collect($anglais['missing_students'])->pluck('name')->all());
    }

    /** Le decor minimal commun aux tests de correction ci-dessous. */
    private function monterUneClasse(int $classeId, int $filiereId, string $systeme = 'BTS'): void
    {
        DB::table('esbtp_niveau_etudes')->insert([['id' => 200, 'name' => 'BTS 1', 'type' => 'BTS', 'year' => 1]]);
        DB::table('esbtp_classes')->insert([
            ['id' => $classeId, 'name' => 'Classe', 'code' => 'C', 'filiere_id' => $filiereId, 'niveau_etude_id' => 200, 'systeme_academique' => $systeme, 'is_active' => true],
        ]);
    }

    public function test_une_classe_de_specialite_voit_les_matieres_du_tronc_commun(): void
    {
        $this->createReferenceTables();

        // Filiere de specialite, fille d'un tronc commun.
        DB::table('esbtp_filieres')->insert([
            ['id' => 90, 'name' => 'Tronc commun', 'code' => 'TC', 'is_tronc_commun' => true, 'parent_id' => null, 'is_active' => true],
            ['id' => 100, 'name' => 'Comptabilite', 'code' => 'CG', 'is_tronc_commun' => false, 'parent_id' => 90, 'is_active' => true],
        ]);
        $this->monterUneClasse(10, 100);
        DB::table('esbtp_matieres')->insert([
            ['id' => 501, 'name' => 'Comptabilite', 'code' => 'CPT', 'is_active' => true],
            ['id' => 503, 'name' => 'Culture generale', 'code' => 'CG0', 'is_active' => true],
        ]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([
            ['matiere_id' => 501, 'filiere_id' => 100, 'niveau_etude_id' => 200],
            // Rattachee au tronc commun parent : les etudiants y ont ete notes
            // pendant la phase de tronc commun, elle doit etre attendue.
            ['matiere_id' => 503, 'filiere_id' => 90, 'niveau_etude_id' => 200],
        ]);

        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre1', 'BTS', 10);

        $this->assertSame([501, 503], collect($result['subjects'])->pluck('id')->sort()->values()->all());
    }

    public function test_une_matiere_de_specialite_est_exclue_du_combo_de_tronc_commun(): void
    {
        $this->createReferenceTables();

        DB::table('esbtp_filieres')->insert([
            ['id' => 90, 'name' => 'Tronc commun', 'code' => 'TC', 'is_tronc_commun' => true, 'parent_id' => null, 'is_active' => true],
        ]);
        $this->monterUneClasse(11, 90);
        DB::table('esbtp_matieres')->insert([
            ['id' => 501, 'name' => 'Socle', 'code' => 'SOC', 'is_active' => true],
            ['id' => 504, 'name' => 'Specialite mal rattachee', 'code' => 'SPE', 'is_active' => true],
        ]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([
            ['matiere_id' => 501, 'filiere_id' => 90, 'niveau_etude_id' => 200, 'classification' => 'tronc_commun'],
            ['matiere_id' => 504, 'filiere_id' => 90, 'niveau_etude_id' => 200, 'classification' => 'specialite'],
        ]);

        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre1', 'BTS', 11);

        $this->assertSame([501], collect($result['subjects'])->pluck('id')->all());
    }

    public function test_une_classe_de_tronc_commun_voit_ses_etudiants_au_premier_semestre(): void
    {
        $this->createReferenceTables();

        DB::table('esbtp_filieres')->insert([
            ['id' => 90, 'name' => 'Tronc commun', 'code' => 'TC', 'is_tronc_commun' => true, 'parent_id' => null, 'is_active' => true],
            ['id' => 100, 'name' => 'Comptabilite', 'code' => 'CG', 'is_tronc_commun' => false, 'parent_id' => 90, 'is_active' => true],
        ]);
        DB::table('esbtp_niveau_etudes')->insert([['id' => 200, 'name' => 'BTS 1', 'type' => 'BTS', 'year' => 1]]);
        DB::table('esbtp_classes')->insert([
            ['id' => 11, 'name' => 'Tronc commun A', 'code' => 'TC-A', 'filiere_id' => 90, 'niveau_etude_id' => 200, 'systeme_academique' => 'BTS', 'is_active' => true],
            ['id' => 12, 'name' => 'Comptabilite A', 'code' => 'CG-A', 'filiere_id' => 100, 'niveau_etude_id' => 200, 'systeme_academique' => 'BTS', 'is_active' => true],
        ]);
        DB::table('esbtp_matieres')->insert([['id' => 501, 'name' => 'Socle', 'code' => 'SOC', 'is_active' => true]]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([['matiere_id' => 501, 'filiere_id' => 90, 'niveau_etude_id' => 200]]);
        DB::table('esbtp_etudiants')->insert([['id' => 301, 'nom' => 'Kouadio', 'prenoms' => 'Awa', 'matricule' => 'M301']]);

        // L'inscription pointe sur la classe de SPECIALITE : c'est la ou
        // l'etudiant finira l'annee. Au semestre 1, il est pourtant en tronc
        // commun — et c'est la phase qui le dit, pas `classe_id`.
        DB::table('esbtp_inscriptions')->insert([
            ['id' => 1, 'etudiant_id' => 301, 'classe_id' => 12, 'annee_universitaire_id' => 20, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('esbtp_inscription_phases')->insert([
            ['inscription_id' => 1, 'type_phase' => 'tronc_commun', 'classe_id' => 11, 'filiere_id' => 90, 'semestre_debut' => 1, 'semestre_fin' => 1, 'is_active' => true],
            ['inscription_id' => 1, 'type_phase' => 'specialisation', 'classe_id' => 12, 'filiere_id' => 100, 'semestre_debut' => 2, 'semestre_fin' => null, 'is_active' => true],
        ]);

        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre1', 'BTS', 11);

        // Le filtre par `classe_id` brut rendait zero etudiant ici, et la
        // couverture annoncait « tout est note » sur une classe pleine.
        $this->assertSame(1, $result['summary']['students_expected']);
        $this->assertNotSame('cohorte_vide', $result['summary']['state']);
    }

    public function test_une_periode_non_reconnue_est_refusee_proprement(): void
    {
        $this->createReferenceTables();
        DB::table('esbtp_filieres')->insert([['id' => 100, 'name' => 'CG', 'is_tronc_commun' => false, 'is_active' => true]]);
        $this->monterUneClasse(10, 100);

        // Levait une exception au premier acces aux evaluations, donc une
        // erreur serveur sur tout le tableau de bord.
        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'trimestre7', 'BTS', 10);

        $this->assertFalse($result['ok']);
        $this->assertSame('Période académique non reconnue.', $result['message']);
    }

    public function test_une_cohorte_vide_n_est_pas_une_couverture_complete(): void
    {
        $this->createReferenceTables();
        DB::table('esbtp_filieres')->insert([['id' => 100, 'name' => 'CG', 'is_tronc_commun' => false, 'is_active' => true]]);
        $this->monterUneClasse(10, 100);
        DB::table('esbtp_matieres')->insert([['id' => 501, 'name' => 'Comptabilite', 'code' => 'CPT', 'is_active' => true]]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([['matiere_id' => 501, 'filiere_id' => 100, 'niveau_etude_id' => 200]]);

        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre1', 'BTS', 10);

        // Zero manquant, mais zero etudiant : ce n'est pas « tout est note ».
        $this->assertSame(0, $result['summary']['missing_results']);
        $this->assertSame('cohorte_vide', $result['summary']['state']);
    }

    public function test_les_matieres_hors_referentiel_ne_gonflent_pas_le_prevu(): void
    {
        $this->createReferenceTables();
        DB::table('esbtp_filieres')->insert([['id' => 100, 'name' => 'CG', 'is_tronc_commun' => false, 'is_active' => true]]);
        $this->monterUneClasse(10, 100);
        DB::table('esbtp_matieres')->insert([
            ['id' => 501, 'name' => 'Comptabilite', 'code' => 'CPT', 'is_active' => true],
            ['id' => 599, 'name' => 'Hors referentiel', 'code' => 'HRS', 'is_active' => true],
        ]);
        DB::table('esbtp_matiere_filiere_niveau')->insert([['matiere_id' => 501, 'filiere_id' => 100, 'niveau_etude_id' => 200]]);
        DB::table('esbtp_etudiants')->insert([['id' => 301, 'nom' => 'Kouadio', 'prenoms' => 'Awa', 'matricule' => 'M301']]);
        DB::table('esbtp_inscriptions')->insert([
            ['etudiant_id' => 301, 'classe_id' => 10, 'annee_universitaire_id' => 20, 'status' => 'active', 'workflow_step' => 'etudiant_cree', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('esbtp_evaluations')->insert([
            ['id' => 701, 'titre' => 'Devoir', 'classe_id' => 10, 'matiere_id' => 501, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'completed', 'type' => 'devoir', 'date_evaluation' => now()],
            ['id' => 702, 'titre' => 'Hors', 'classe_id' => 10, 'matiere_id' => 599, 'annee_universitaire_id' => 20, 'periode' => 'semestre1', 'status' => 'completed', 'type' => 'devoir', 'date_evaluation' => now()],
        ]);

        $result = app(AcademicNoteCoverageService::class)->summarize(20, 'semestre1', 'BTS', 10);

        // Un etudiant, une matiere au referentiel : un seul resultat attendu.
        // L'evaluation hors referentiel est comptee a part, pas dans le prevu,
        // faute de quoi le ratio « traite / prevu » melange deux perimetres.
        $this->assertSame(1, $result['summary']['expected_results']);
        $this->assertSame(1, $result['summary']['orphan_expected_results']);
        $this->assertSame(1, $result['summary']['orphan_subjects']);
    }

    protected function createReferenceTables(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Le modele User est soft-deletable et porte un telephone : sans
            // ces colonnes, resoudre l'enseignant a relancer echoue.
            $table->string('phone')->nullable();
            $table->softDeletes();
        });
        Schema::create('esbtp_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->unsignedBigInteger('niveau_etude_id')->nullable();
            $table->string('systeme_academique')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_matieres', function (Blueprint $table): void {
            $table->id();
            // NULL = matiere BTS ; non-NULL = ECUE d'une unite d'enseignement LMD.
            // La colonne n'est pas decorative ici, et son absence NE SE VOIT PAS.
            // SQLite accepte un identifiant inconnu entre guillemets doubles et le
            // traite comme une CHAINE : `"unite_enseignement_id" IS NULL` rend alors
            // `false` pour toutes les lignes, sans lever la moindre erreur. Le scope
            // `btsOnly()` rendait donc zero matiere, le resolveur de bulletin tombait
            // dans son repli `esbtp_classe_matiere` — table absente de cette doublure —
            // et six tests echouaient sur un « no such table » qui ne nommait pas la
            // vraie cause. Toute doublure de `esbtp_matieres` doit porter cette colonne.
            $table->unsignedBigInteger('unite_enseignement_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        // Second etage du lecteur canonique des professeurs : le reglage d'abord,
        // cette colonne ensuite. Le bandeau de couverture y cherche un nom
        // d'enseignant quand le planning general n'en porte pas.
        Schema::create('esbtp_bulletins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('annee_universitaire_id')->nullable();
            $table->string('periode')->nullable();
            $table->text('professeurs')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        // Lue par `BulletinSubjectOrder` : l'ordre des matieres au bulletin est un
        // reglage d'instance. La couverture passe desormais par la maquette, qui
        // prend sa liste chez lui — la doublure doit donc porter cette table.
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        // La couverture BTS passe desormais par le resolver de tronc commun :
        // il lit la filiere pour savoir si elle herite d'une filiere mere.
        Schema::create('esbtp_filieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_tronc_commun')->default(false);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        // Colonne portee par le schema reel et absente du socle minimal partage.
        // On l'ajoute ici plutot que dans le socle, pour ne pas modifier le
        // decor des trente autres tests du pilotage.
        Schema::table('esbtp_inscriptions', function (Blueprint $table): void {
            $table->unsignedBigInteger('inscription_origine_id')->nullable();
        });

        // La cohorte canonique sait qu'un etudiant change de classe entre les
        // semestres : elle lit les phases d'inscription.
        Schema::create('esbtp_inscription_phases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inscription_id');
            $table->string('type_phase', 32);
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('filiere_id')->nullable();
            $table->unsignedTinyInteger('semestre_debut');
            $table->unsignedTinyInteger('semestre_fin')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('esbtp_annee_universitaires', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('libelle')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_niveau_etudes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->unsignedInteger('year')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('esbtp_matiere_filiere_niveau', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('filiere_id');
            $table->unsignedBigInteger('niveau_etude_id');
            $table->string('classification')->nullable();
            $table->unsignedSmallInteger('ordre_bulletin')->nullable();
            $table->unsignedTinyInteger('semestre')->nullable();
            $table->boolean('semestre_renseigne')->default(false);
        });
        Schema::create('esbtp_etudiants', function (Blueprint $table): void {
            $table->id();
            $table->string('nom');
            $table->string('prenoms')->nullable();
            $table->string('matricule')->nullable();
            $table->softDeletes();
        });
        Schema::table('esbtp_evaluations', function (Blueprint $table): void {
            $table->string('titre')->nullable();
            $table->string('type')->nullable();
            $table->dateTime('date_evaluation')->nullable();
            $table->string('status')->nullable();
            $table->softDeletes();
        });
        Schema::table('esbtp_notes', function (Blueprint $table): void {
            $table->decimal('note', 8, 2)->nullable();
        });
    }
}
