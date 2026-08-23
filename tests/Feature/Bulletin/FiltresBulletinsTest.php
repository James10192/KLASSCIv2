<?php

namespace Tests\Feature\Bulletin;

use App\Domain\Bulletins\FiltresBulletins;
use App\Http\Controllers\ESBTPBulletinController;
use App\Models\ESBTPBulletin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Un filtre hors des choix offerts doit être écarté de la requête ET de
 * l'affichage, jamais de l'un sans l'autre.
 *
 * Il l'était autrefois d'un seul côté : la requête filtrait sur une valeur que
 * le sélecteur ne savait pas afficher, celui-ci retombait sur « Toutes les
 * périodes », le tableau se vidait en annonçant l'inverse, et l'export — qui
 * lit le formulaire — emportait un autre ensemble que celui affiché.
 *
 * Base SQLite en mémoire, schéma minimal : le test doit être vrai partout, pas
 * seulement là où une base de développement se trouve peuplée.
 */
class FiltresBulletinsTest extends TestCase
{
    private const ANNEE_COURANTE = 7;

    private const ANNEE_ANCIENNE = 3;

    private const CLASSE_ACTIVE = 11;

    private const CLASSE_ARCHIVEE = 12;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('audit.enabled', false);
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->creerSchema();
        $this->semer();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function filtres(array $parametres): FiltresBulletins
    {
        return FiltresBulletins::depuis(Request::create('/esbtp/bulletins', 'GET', $parametres));
    }

    /** Les bulletins que la liste afficherait. */
    private function idsAffiches(array $parametres): array
    {
        $query = ESBTPBulletin::query();
        $this->filtres($parametres)->appliquerA($query);

        return $query->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
    }

    /** Les bulletins que l'export emporterait, par son vrai chemin. */
    private function idsExportes(array $parametres): array
    {
        $controleur = app(ESBTPBulletinController::class);
        $methode = new \ReflectionMethod($controleur, 'contexteExport');
        $methode->setAccessible(true);

        $contexte = $methode->invoke($controleur, Request::create('/esbtp/bulletins', 'GET', $parametres));

        return collect($contexte['bulletin_ids'])->sort()->values()->all();
    }

    public function test_annuel_reste_selectionnable(): void
    {
        $this->assertArrayHasKey('annuel', FiltresBulletins::PERIODES);
        $this->assertSame([5], $this->idsAffiches(['periode_id' => 'annuel']));
    }

    public function test_une_periode_hors_liste_ne_filtre_rien(): void
    {
        foreach (['1', 'trimestre1', ''] as $inconnue) {
            $this->assertNull($this->filtres(['periode_id' => $inconnue])->periode);
            $this->assertSame([1, 2, 3, 5], $this->idsAffiches(['periode_id' => $inconnue]),
                "La période « $inconnue » ne doit pas restreindre la liste.");
        }

        $this->assertSame([1, 2], $this->idsAffiches(['periode_id' => 'semestre1']));
    }

    public function test_une_classe_hors_liste_ne_filtre_rien(): void
    {
        $this->assertSame(self::CLASSE_ACTIVE, $this->filtres(['classe_id' => (string) self::CLASSE_ACTIVE])->classeId);
        $this->assertSame([1, 2, 5], $this->idsAffiches(['classe_id' => (string) self::CLASSE_ACTIVE]));

        // Une classe archivée n'est pas offerte : le filtre est écarté des deux côtés.
        $this->assertNull($this->filtres(['classe_id' => (string) self::CLASSE_ARCHIVEE])->classeId);
        $this->assertSame([1, 2, 3, 5], $this->idsAffiches(['classe_id' => (string) self::CLASSE_ARCHIVEE]));
    }

    /**
     * L'année est le seul filtre qui ne s'efface pas : elle retombe sur l'année
     * en cours. C'est l'écart assumé, et il doit rester borné — le bulletin de
     * l'année ancienne ne doit jamais reparaître.
     */
    public function test_une_annee_hors_liste_retombe_sur_l_annee_en_cours(): void
    {
        $this->assertSame(self::ANNEE_COURANTE, $this->filtres([])->anneeId);
        $this->assertSame(self::ANNEE_COURANTE, $this->filtres(['annee_universitaire_id' => '999999'])->anneeId);
        $this->assertSame(self::ANNEE_COURANTE, $this->filtres(['annee_universitaire_id' => ''])->anneeId);

        $this->assertNotContains(4, $this->idsAffiches(['annee_universitaire_id' => '999999']));
        $this->assertSame([4], $this->idsAffiches(['annee_universitaire_id' => (string) self::ANNEE_ANCIENNE]));
    }

    public function test_un_statut_hors_liste_ne_filtre_rien(): void
    {
        $this->assertSame([2], $this->idsAffiches(['published' => '1']));
        $this->assertSame([1, 3, 5], $this->idsAffiches(['published' => '0']));

        foreach (['7', '', 'oui'] as $inconnu) {
            $this->assertNull($this->filtres(['published' => $inconnu])->publie);
            $this->assertSame([1, 2, 3, 5], $this->idsAffiches(['published' => $inconnu]));
        }
    }

    public function test_la_recherche_est_nettoyee(): void
    {
        $this->assertSame('KOUAME', $this->filtres(['search' => '  KOUAME  '])->recherche);
        $this->assertSame([1, 2, 3, 5], $this->idsAffiches(['search' => '   ']));
        $this->assertSame([1, 5], $this->idsAffiches(['search' => 'KOUAME']));
    }

    /**
     * La thèse du découpage, éprouvée par les deux vraies portes : ce que la
     * liste affiche et ce que l'export emporte sont le même ensemble, y compris
     * lorsque la requête porte des valeurs que le sélecteur ne sait pas rendre.
     *
     * L'export ne retient que les bulletins générés ; la comparaison porte donc
     * sur cette part de la liste.
     */
    public function test_la_liste_et_l_export_portent_sur_le_meme_ensemble(): void
    {
        foreach ([
            'valeurs rejetées' => ['periode_id' => '1', 'published' => '9', 'annee_universitaire_id' => '999999'],
            'classe archivée' => ['classe_id' => (string) self::CLASSE_ARCHIVEE],
            'filtre valide' => ['classe_id' => (string) self::CLASSE_ACTIVE, 'periode_id' => 'semestre1'],
        ] as $cas => $parametres) {
            $generes = ESBTPBulletin::whereIn('id', $this->idsAffiches($parametres))
                ->whereNotNull('moyenne_generale')->pluck('id')
                ->map(fn ($id) => (int) $id)->sort()->values()->all();

            $this->assertSame($generes, $this->idsExportes($parametres),
                "Cas « $cas » : l'export doit porter sur ce que la liste affiche.");
        }
    }

    private function creerSchema(): void
    {
        Schema::create('esbtp_annee_universitaires', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('name')->nullable();
            $t->integer('annee_debut')->nullable();
            $t->boolean('is_current')->default(false);
            $t->boolean('is_active')->default(false);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('esbtp_classes', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->string('systeme_academique')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        // ESBTPClasse charge d'office filiere, niveau et annee.
        foreach (['esbtp_filieres', 'esbtp_niveau_etudes'] as $referentiel) {
            Schema::create($referentiel, function (Blueprint $t) {
                $t->integer('id')->primary();
                $t->string('name')->nullable();
                $t->timestamps();
                $t->softDeletes();
            });
        }

        Schema::create('esbtp_etudiants', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('matricule')->nullable();
            $t->string('nom')->nullable();
            $t->string('prenoms')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('esbtp_bulletins', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('etudiant_id')->nullable();
            $t->integer('classe_id')->nullable();
            $t->integer('annee_universitaire_id')->nullable();
            $t->string('periode')->nullable();
            $t->decimal('moyenne_generale', 5, 2)->nullable();
            $t->boolean('is_published')->default(false);
            $t->timestamp('archived_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    private function semer(): void
    {
        DB::table('esbtp_annee_universitaires')->insert([
            ['id' => self::ANNEE_COURANTE, 'name' => '2025-2026', 'annee_debut' => 2025, 'is_current' => true, 'is_active' => true],
            ['id' => self::ANNEE_ANCIENNE, 'name' => '2024-2025', 'annee_debut' => 2024, 'is_current' => false, 'is_active' => true],
        ]);
        DB::table('esbtp_classes')->insert([
            ['id' => self::CLASSE_ACTIVE, 'name' => 'TRONC COMMUN K', 'is_active' => true, 'systeme_academique' => null],
            ['id' => self::CLASSE_ARCHIVEE, 'name' => 'PROMO CLOSE', 'is_active' => false, 'systeme_academique' => null],
        ]);
        DB::table('esbtp_etudiants')->insert([
            ['id' => 101, 'matricule' => 'M-101', 'nom' => 'KOUAME', 'prenoms' => 'Ama'],
            ['id' => 102, 'matricule' => 'M-102', 'nom' => 'TRAORE', 'prenoms' => 'Bakary'],
        ]);
        DB::table('esbtp_bulletins')->insert([
            ['id' => 1, 'etudiant_id' => 101, 'classe_id' => self::CLASSE_ACTIVE, 'annee_universitaire_id' => self::ANNEE_COURANTE, 'periode' => 'semestre1', 'moyenne_generale' => 12.5, 'is_published' => false],
            ['id' => 2, 'etudiant_id' => 102, 'classe_id' => self::CLASSE_ACTIVE, 'annee_universitaire_id' => self::ANNEE_COURANTE, 'periode' => 'semestre1', 'moyenne_generale' => 9.75, 'is_published' => true],
            ['id' => 3, 'etudiant_id' => 102, 'classe_id' => self::CLASSE_ARCHIVEE, 'annee_universitaire_id' => self::ANNEE_COURANTE, 'periode' => 'semestre2', 'moyenne_generale' => null, 'is_published' => false],
            ['id' => 4, 'etudiant_id' => 101, 'classe_id' => self::CLASSE_ACTIVE, 'annee_universitaire_id' => self::ANNEE_ANCIENNE, 'periode' => 'semestre1', 'moyenne_generale' => 11.0, 'is_published' => false],
            ['id' => 5, 'etudiant_id' => 101, 'classe_id' => self::CLASSE_ACTIVE, 'annee_universitaire_id' => self::ANNEE_COURANTE, 'periode' => 'annuel', 'moyenne_generale' => 10.0, 'is_published' => false],
        ]);
    }
}
