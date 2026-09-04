<?php

namespace Tests\Unit\Dossier;

use App\Models\ESBTPInscription;
use PHPUnit\Framework\TestCase;

/**
 * Verrouille les invariants de schema du dossier sans monter de base.
 *
 * Deux ecoles de plus de 2000 inscrits tournent sur ce code : une regression
 * sur l'index unique ou une reintroduction de la colonne morte doit se voir en
 * revue, pas en production.
 */
class SchemaDossierTest extends TestCase
{
    private function racine(): string
    {
        return dirname(__DIR__, 3);
    }

    private function migration(string $fragment): string
    {
        $trouves = glob($this->racine().'/database/migrations/*'.$fragment.'*.php');

        $this->assertNotEmpty($trouves, "Migration introuvable pour « {$fragment} »");

        return file_get_contents($trouves[0]);
    }

    /**
     * L'index unique EST la garantie d'idempotence : rouvrir une inscription ne
     * peut pas recreer une ligne deja saisie, meme si deux requetes arrivent en
     * meme temps. Le code applicatif ne fait que l'aider.
     */
    public function test_l_etat_est_unique_par_inscription_et_par_piece(): void
    {
        $migration = $this->migration('creer_table_esbtp_inscription_pieces');

        $this->assertStringContainsString('esbtp_inscription_pieces', $migration);
        $this->assertMatchesRegularExpression(
            "/unique\(\s*\[\s*'inscription_id'\s*,\s*'piece_dossier_id'\s*\]/",
            $migration,
            'La contrainte unique (inscription_id, piece_dossier_id) doit rester : sans elle, la matérialisation duplique.'
        );
    }

    /**
     * L'etat vit sur l'INSCRIPTION, jamais sur l'etudiant : un etudiant de
     * troisieme annee a trois lignes « extrait de naissance », une par annee.
     */
    public function test_l_etat_est_rattache_a_l_inscription_et_pas_a_l_etudiant(): void
    {
        $migration = $this->migration('creer_table_esbtp_inscription_pieces');

        $this->assertStringContainsString("foreignId('inscription_id')", $migration);
        $this->assertStringNotContainsString("foreignId('etudiant_id')", $migration);
    }

    /**
     * La piece du catalogue n'est jamais supprimee sous les pieds d'un dossier :
     * on la desactive. Le restrictOnDelete l'impose au niveau de la base.
     */
    public function test_le_lien_vers_le_catalogue_ne_peut_pas_etre_efface_en_cascade(): void
    {
        $migration = $this->migration('creer_table_esbtp_inscription_pieces');

        $this->assertStringContainsString('restrictOnDelete()', $migration);
    }

    /**
     * Lecture par reflexion et non par instanciation : instancier un modele
     * Auditable exige le conteneur Laravel, alors que cet invariant n'a besoin
     * que de la declaration.
     */
    public function test_documents_fournis_est_bien_retiree_du_modele(): void
    {
        $defauts = (new \ReflectionClass(ESBTPInscription::class))->getDefaultProperties();

        $this->assertNotContains('documents_fournis', $defauts['fillable'] ?? []);
        $this->assertArrayNotHasKey('documents_fournis', $defauts['casts'] ?? []);
        $this->assertNotContains('documents_fournis', $defauts['auditInclude'] ?? []);
    }

    public function test_la_suppression_de_documents_fournis_est_reversible(): void
    {
        $migration = $this->migration('supprimer_documents_fournis');

        $this->assertStringContainsString("dropColumn('documents_fournis')", $migration);
        $this->assertStringContainsString("json('documents_fournis')", $migration);
    }
}
