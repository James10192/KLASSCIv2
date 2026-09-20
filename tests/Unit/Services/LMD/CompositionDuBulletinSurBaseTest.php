<?php

declare(strict_types=1);

namespace Tests\Unit\Services\LMD;

use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDResultatUE;
use App\Services\LMD\CompositionDuBulletin;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le chemin DESTRUCTEUR, exécuté sur une vraie base.
 *
 * `CompositionDuBulletinTest`, à côté, ne contrôle que la décision pure
 * (`idsAElaguer`). Elle ne pouvait pas attraper ce qui suit : l'élagage et
 * `reprendreOuCreer()` n'existent qu'à cause d'un comportement du MOTEUR, et
 * un comportement de moteur ne se vérifie qu'en l'exécutant.
 *
 * Ce que ces contrôles prouvent, et qu'aucune lecture de code ne prouve :
 * **un index UNIQUE compte les lignes mises à la corbeille, alors
 * qu'`updateOrCreate` ne les voit pas.** Retirer une unité d'une maquette puis
 * l'y remettre produit donc une insertion refusée, et toute la génération du
 * bulletin tombe — sur des instances à plus de deux mille inscriptions, sur des
 * lignes qui portent des notes de seconde session saisies à la main.
 *
 * Le premier contrôle reproduit cette panne. Les suivants montrent que
 * l'auxiliaire l'évite, et qu'il RAPPORTE la ligne plutôt que d'en créer une
 * seconde remise à zéro.
 *
 * SQLite en mémoire, et non MySQL : l'invariant testé — un index UNIQUE ignore
 * `deleted_at` — est le même sur les deux moteurs, et c'est lui qu'on mesure.
 * Le prix de ce choix est qu'on ne rejoue pas les clés étrangères réelles ; le
 * gain est que ce contrôle tourne partout, y compris là où aucune base MySQL
 * n'est disponible, donc qu'il tourne vraiment.
 */
class CompositionDuBulletinSurBaseTest extends TestCase
{
    private const CONNEXION = 'sqlite_elagage';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.'.self::CONNEXION => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        config(['database.default' => self::CONNEXION]);
        DB::purge(self::CONNEXION);

        // Les deux tables reproduites d'après leurs migrations, clés étrangères
        // en moins : les colonnes réelles plutôt qu'un sous-ensemble deviné —
        // les modèles renseignent d'eux-mêmes `created_by`/`updated_by`, et une
        // colonne manquante ferait échouer le contrôle pour une raison qui n'a
        // rien à voir avec ce qu'il mesure. Les deux index UNIQUE, eux, sont le
        // sujet même.
        Schema::connection(self::CONNEXION)->create('esbtp_lmd_resultats_ues', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->unsignedBigInteger('unite_enseignement_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->string('statut')->default('NAQ');
            $table->string('mention')->nullable();
            $table->unsignedInteger('credit')->default(0);
            $table->decimal('stat_min', 5, 2)->nullable();
            $table->decimal('stat_moy', 5, 2)->nullable();
            $table->decimal('stat_max', 5, 2)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['bulletin_id', 'unite_enseignement_id'], 'lmd_res_ue_bulletin_ue_unique');
        });

        Schema::connection(self::CONNEXION)->create('esbtp_lmd_resultats_ecues', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('bulletin_id');
            $table->unsignedBigInteger('resultat_ue_id');
            $table->unsignedBigInteger('matiere_id');
            $table->unsignedBigInteger('etudiant_id');
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->unsignedInteger('credit')->default(0);
            $table->unsignedInteger('rang')->nullable();
            $table->unsignedBigInteger('enseignant_id')->nullable();
            $table->decimal('stat_min', 5, 2)->nullable();
            $table->decimal('stat_moy', 5, 2)->nullable();
            $table->decimal('stat_max', 5, 2)->nullable();
            // Ajoutées plus tard par `add_rattrapage_columns_…` : ce sont elles
            // qui portent la saisie manuelle que le retour dans la maquette doit
            // rendre intacte.
            $table->decimal('note_session_normale', 5, 2)->nullable();
            $table->decimal('note_rattrapage', 5, 2)->nullable();
            $table->decimal('note_finale', 5, 2)->nullable();
            $table->boolean('rattrapage_eligible')->default(false);
            $table->boolean('rattrapage_inscrit')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['bulletin_id', 'matiere_id'], 'lmd_res_ecue_bulletin_matiere_unique');
        });
    }

    protected function tearDown(): void
    {
        Schema::connection(self::CONNEXION)->dropIfExists('esbtp_lmd_resultats_ecues');
        Schema::connection(self::CONNEXION)->dropIfExists('esbtp_lmd_resultats_ues');

        parent::tearDown();
    }

    /**
     * La panne que `reprendreOuCreer()` existe pour éviter, reproduite.
     *
     * Si ce contrôle cesse d'échouer sur `updateOrCreate`, c'est que le moteur
     * ou le schéma a changé — et alors l'auxiliaire mérite d'être rediscuté.
     * Tant qu'il échoue, l'auxiliaire est justifié, et ce test est ce qui le
     * démontre.
     */
    public function test_update_or_create_se_heurte_a_l_index_unique_sur_une_ligne_en_corbeille(): void
    {
        $ue = $this->creerUniteEnCorbeille();

        $this->expectException(QueryException::class);
        // Et pas n'importe laquelle : sans cette ligne, ajouter demain une
        // colonne NOT NULL au schéma monté plus haut laisserait ce contrôle vert
        // pour une tout autre raison, et il cesserait en silence de démontrer ce
        // que son titre affirme. La formulation est celle de SQLite, le moteur
        // que ce fichier choisit lui-même ; MySQL dirait « Duplicate entry ».
        $this->expectExceptionMessage('UNIQUE constraint failed');

        // `updateOrCreate` applique le scope de suppression en douceur : il ne
        // voit pas la ligne, conclut qu'elle n'existe pas, et tente une
        // insertion que l'index UNIQUE — lui — refuse.
        ESBTPLMDResultatUE::query()->updateOrCreate(
            ['bulletin_id' => $ue->bulletin_id, 'unite_enseignement_id' => $ue->unite_enseignement_id],
            ['etudiant_id' => 7, 'credit' => 6],
        );
    }

    public function test_reprendre_ou_creer_ressort_la_ligne_de_la_corbeille_au_lieu_d_en_creer_une_seconde(): void
    {
        $ue = $this->creerUniteEnCorbeille();

        $repris = (new CompositionDuBulletin)->reprendreOuCreer(
            ESBTPLMDResultatUE::query(),
            ['bulletin_id' => 42, 'unite_enseignement_id' => 3],
            ['etudiant_id' => 7, 'credit' => 6, 'moyenne' => 14.5, 'statut' => 'AQ'],
        );

        $this->assertSame((int) $ue->id, (int) $repris->id, 'La ligne doit être reprise, pas recréée.');
        $this->assertNull($repris->fresh()->deleted_at, 'La ligne doit être ressortie de la corbeille.');

        // Une seule ligne au total, corbeille comprise : aucune seconde n'a été
        // créée à côté de l'ancienne.
        $this->assertSame(1, ESBTPLMDResultatUE::withTrashed()->count());

        // Et elle a bien été MISE À JOUR. C'est ce que ces deux assertions
        // discriminent : une implémentation bâtie sur `firstOrCreate` — la forme
        // du `restoreOrCreate()` arrivé en Laravel 11, absent d'ici (9.52) —
        // ressortirait bien la ligne, mais la laisserait à `NAQ` et sans
        // moyenne.
        $this->assertSame('AQ', $repris->fresh()->statut);
        $this->assertEqualsWithDelta(14.5, (float) $repris->fresh()->moyenne, 0.001);
    }

    /**
     * Le cas métier qui justifie « ressortir » plutôt que « recréer » : une
     * note de seconde session saisie à la main, que rien ne recalcule.
     */
    public function test_une_note_de_seconde_session_survit_au_retrait_puis_au_retour_dans_la_maquette(): void
    {
        // DEUX unités, et non une seule retirée : une composition entièrement
        // vide ne supprime rien — `idsAElaguer()` protège alors les lignes, et
        // `refuserSurUneMaquetteVide()` intercepte ce cas avant même d'arriver
        // ici. Le retrait réel est donc toujours PARTIEL, et c'est celui-là
        // qu'il faut reproduire.
        $gardee = ESBTPLMDResultatUE::create([
            'bulletin_id' => 42, 'unite_enseignement_id' => 2, 'etudiant_id' => 7, 'credit' => 4,
        ]);
        $ue = ESBTPLMDResultatUE::create([
            'bulletin_id' => 42, 'unite_enseignement_id' => 3, 'etudiant_id' => 7, 'credit' => 6,
        ]);
        $ecue = ESBTPLMDResultatECUE::create([
            'bulletin_id' => 42, 'resultat_ue_id' => $ue->id, 'matiere_id' => 11,
            'etudiant_id' => 7, 'moyenne' => 8.0, 'note_rattrapage' => 12.0, 'credit' => 3,
        ]);

        // La maquette ne rattache plus l'unité 3 : élagage.
        (new CompositionDuBulletin)->elaguerLesUnites($this->bulletin(42), [$gardee]);

        $this->assertSoftDeleted('esbtp_lmd_resultats_ues', ['id' => $ue->id]);
        // Les éléments ne tombent pas avec leur unité — il n'y a pas de cascade
        // sur une suppression en douceur. C'est la ligne explicite du service
        // qui les emporte, et ce contrôle est ce qui le vérifie.
        $this->assertSoftDeleted('esbtp_lmd_resultats_ecues', ['id' => $ecue->id]);

        // La maquette la réintègre.
        $composition = new CompositionDuBulletin;
        $ueRepris = $composition->reprendreOuCreer(
            ESBTPLMDResultatUE::query(),
            ['bulletin_id' => 42, 'unite_enseignement_id' => 3],
            ['etudiant_id' => 7, 'credit' => 6],
        );
        $ecueRepris = $composition->reprendreOuCreer(
            ESBTPLMDResultatECUE::query(),
            ['bulletin_id' => 42, 'matiere_id' => 11],
            ['resultat_ue_id' => $ueRepris->id, 'etudiant_id' => 7, 'moyenne' => 8.0, 'credit' => 3],
        );

        $this->assertSame((int) $ecue->id, (int) $ecueRepris->id);
        $this->assertEqualsWithDelta(
            12.0,
            (float) $ecueRepris->fresh()->note_rattrapage,
            0.001,
            'La note de seconde session doit revenir telle quelle, pas remise à zéro.',
        );
    }

    public function test_elaguer_les_unites_ne_retire_que_celles_qui_ont_quitte_la_maquette(): void
    {
        $gardee = ESBTPLMDResultatUE::create([
            'bulletin_id' => 42, 'unite_enseignement_id' => 3, 'etudiant_id' => 7, 'credit' => 6,
        ]);
        $retiree = ESBTPLMDResultatUE::create([
            'bulletin_id' => 42, 'unite_enseignement_id' => 4, 'etudiant_id' => 7, 'credit' => 4,
        ]);
        // Une unité d'un AUTRE bulletin : l'élagage ne doit jamais déborder.
        $voisine = ESBTPLMDResultatUE::create([
            'bulletin_id' => 99, 'unite_enseignement_id' => 3, 'etudiant_id' => 8, 'credit' => 6,
        ]);

        (new CompositionDuBulletin)->elaguerLesUnites($this->bulletin(42), [$gardee]);

        $this->assertNull($gardee->fresh()->deleted_at);
        $this->assertSoftDeleted('esbtp_lmd_resultats_ues', ['id' => $retiree->id]);
        $this->assertNull($voisine->fresh()->deleted_at, 'Un autre bulletin ne doit jamais être touché.');
    }

    /**
     * L'asymétrie qui a coûté une revue : une unité dont la maquette ne
     * rattache plus AUCUN élément garde ses lignes, et l'appelant doit le
     * savoir pour ne pas lui écrire une moyenne vide par-dessus.
     */
    public function test_une_unite_sans_element_dans_la_maquette_est_signalee_et_laissee_intacte(): void
    {
        $ue = ESBTPLMDResultatUE::create([
            'bulletin_id' => 42, 'unite_enseignement_id' => 3, 'etudiant_id' => 7, 'credit' => 6,
        ]);
        $ecue = ESBTPLMDResultatECUE::create([
            'bulletin_id' => 42, 'resultat_ue_id' => $ue->id, 'matiere_id' => 11,
            'etudiant_id' => 7, 'moyenne' => 13.0, 'credit' => 3,
        ]);

        $laisserIntacte = (new CompositionDuBulletin)->elaguerLesElements($ue->fresh(), []);

        $this->assertTrue($laisserIntacte, 'L appelant doit être averti de laisser l unité telle quelle.');
        $this->assertNull($ecue->fresh()->deleted_at, 'Aucune ligne ne doit être retirée dans ce cas.');
    }

    public function test_elaguer_les_elements_retire_ceux_qui_ont_quitte_l_unite(): void
    {
        $ue = ESBTPLMDResultatUE::create([
            'bulletin_id' => 42, 'unite_enseignement_id' => 3, 'etudiant_id' => 7, 'credit' => 6,
        ]);
        $garde = ESBTPLMDResultatECUE::create([
            'bulletin_id' => 42, 'resultat_ue_id' => $ue->id, 'matiere_id' => 11, 'etudiant_id' => 7, 'credit' => 3,
        ]);
        $retire = ESBTPLMDResultatECUE::create([
            'bulletin_id' => 42, 'resultat_ue_id' => $ue->id, 'matiere_id' => 12, 'etudiant_id' => 7, 'credit' => 3,
        ]);

        $laisserIntacte = (new CompositionDuBulletin)->elaguerLesElements($ue->fresh(), [$garde]);

        $this->assertFalse($laisserIntacte);
        $this->assertNull($garde->fresh()->deleted_at);
        $this->assertSoftDeleted('esbtp_lmd_resultats_ecues', ['id' => $retire->id]);
    }

    /**
     * Un bulletin non persisté : `elaguerLesUnites()` n'en lit que l'identifiant
     * et deux champs de journal, jamais la base. Monter une table de bulletins
     * et ses six clés étrangères pour cela n'apporterait aucune garantie de plus.
     */
    private function bulletin(int $id): ESBTPLMDBulletin
    {
        $bulletin = new ESBTPLMDBulletin(['classe_id' => 5, 'semestre' => 3]);
        $bulletin->id = $id;

        return $bulletin;
    }

    private function creerUniteEnCorbeille(): ESBTPLMDResultatUE
    {
        $ue = ESBTPLMDResultatUE::create([
            'bulletin_id' => 42, 'unite_enseignement_id' => 3, 'etudiant_id' => 7, 'credit' => 6,
        ]);
        $ue->delete();

        return $ue;
    }
}
