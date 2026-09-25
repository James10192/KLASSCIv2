<?php

namespace Tests\Unit\Domain\Assistant;

use App\Domain\Assistant\Outils\Presentation\AfficherDiagramme;
use App\Domain\Assistant\Outils\Presentation\AfficherGraphique;
use App\Domain\Assistant\Outils\Presentation\AfficherTableau;
use App\Domain\Assistant\Outils\ResumeOutil;
use PHPUnit\Framework\TestCase;

/**
 * Outils de présentation : ce que le modèle demande est contrôlé avant d'atteindre
 * l'écran, et une demande mal formée revient au modèle avec ce qu'il doit corriger.
 */
class OutilsDePresentationTest extends TestCase
{
    public function test_un_graphique_valide_devient_un_widget(): void
    {
        $r = (new AfficherGraphique())->execute([
            'type' => 'courbe', 'titre' => 'Encaissements', 'libelles' => ['avr.', 'mai', 'juin'],
            'series' => [['nom' => 'Encaissé', 'valeurs' => [100, '250', 90.5]]], 'unite' => 'FCFA',
        ], new \stdClass());

        $this->assertTrue($r['affiche']);
        $this->assertSame('graphique', $r['widget']['kind']);
        $this->assertSame([100.0, 250.0, 90.5], $r['widget']['series'][0]['valeurs']);
    }

    public function test_un_graphique_mal_forme_dit_quoi_corriger(): void
    {
        $outil = new AfficherGraphique();
        $this->assertStringContainsString('3 valeurs', $outil->execute(['type' => 'barres', 'titre' => 'x', 'libelles' => ['a', 'b', 'c'], 'series' => [['nom' => 'S', 'valeurs' => [1, 2]]]], 1)['error']);
        $this->assertStringContainsString('nombres', $outil->execute(['type' => 'barres', 'titre' => 'x', 'libelles' => ['a', 'b'], 'series' => [['nom' => 'S', 'valeurs' => ['1 000 FCFA', 2]]]], 1)['error']);
        $this->assertArrayHasKey('error', $outil->execute(['type' => 'anneau', 'titre' => 'x', 'libelles' => ['a', 'b'], 'series' => [['nom' => 'A', 'valeurs' => [1, 2]], ['nom' => 'B', 'valeurs' => [1, 2]]]], 1));
    }

    public function test_le_tableau_ne_garde_que_les_liens_internes_et_les_tons_connus(): void
    {
        $r = (new AfficherTableau())->execute([
            'titre' => 'Top',
            'colonnes' => [['cle' => 'nom', 'libelle' => 'Nom', 'type' => 'lien'], ['cle' => 'du', 'libelle' => 'Dû', 'type' => 'montant'], ['cle' => 's', 'libelle' => 'Statut', 'type' => 'statut']],
            'lignes' => [
                ['nom' => ['url' => '/esbtp/etudiants/12', 'texte' => 'KOFFI'], 'du' => '1500', 's' => ['texte' => 'En retard', 'ton' => 'danger']],
                ['nom' => ['url' => 'https://piege.example/x', 'texte' => 'PIEGE'], 'du' => 'beaucoup', 's' => ['texte' => 'x', 'ton' => 'violet']],
            ],
        ], 1);

        $lignes = $r['widget']['lignes'];
        $this->assertSame(['url' => '/esbtp/etudiants/12', 'texte' => 'KOFFI'], $lignes[0]['nom']);
        $this->assertSame('PIEGE', $lignes[1]['nom'], 'un lien externe devient du texte');
        $this->assertSame(1500.0, $lignes[0]['du']);
        $this->assertNull($lignes[1]['du']);
        $this->assertSame('neutre', $lignes[1]['s']['ton']);

        foreach (["/\\evil.com", "/\t/evil.com", "//evil.com", "javascript:alert(1)", "/esbtp/x\n//evil"] as $piege) {
            $this->assertFalse(AfficherTableau::estLienInterne($piege), json_encode($piege));
        }
        $this->assertTrue(AfficherTableau::estLienInterne('/esbtp/paiements?statut=en_attente'));
    }

    public function test_le_diagramme_refuse_directives_et_clics(): void
    {
        $outil = new AfficherDiagramme();
        $ok = $outil->execute(['titre' => 'Circuit', 'mermaid' => "```mermaid\nflowchart TD\n A[\"Dépôt\"] --> B{\"Complet ?\"}\n```"], 1);
        $this->assertSame("flowchart TD\n A[\"Dépôt\"] --> B{\"Complet ?\"}", $ok['widget']['mermaid']);

        $this->assertArrayHasKey('error', $outil->execute(['titre' => 'x', 'mermaid' => "%%{init: {}}%%\nflowchart TD\n A-->B"], 1));
        $this->assertArrayHasKey('error', $outil->execute(['titre' => 'x', 'mermaid' => "flowchart TD\n A-->B\n click A \"javascript:alert(1)\""], 1));
        $this->assertArrayHasKey('error', $outil->execute(['titre' => 'x', 'mermaid' => "classDiagram\n A <|-- B"], 1));
    }

    public function test_le_modele_recoit_un_resume_borne_et_la_consigne_du_widget(): void
    {
        $lignes = array_map(fn ($i) => ['etudiant' => "E{$i}", 'reste' => '1 000 FCFA', 'lien' => "/esbtp/etudiants/{$i}", 'initials' => 'E'], range(1, 30));
        $json = ResumeOutil::pourModele('search_debtors', ['results' => $lignes, 'count' => 30, 'total' => 219, 'display_type' => 'cards'], true);
        $donnees = json_decode($json, true);

        $this->assertLessThanOrEqual(6000, strlen($json));
        $this->assertCount(12, $donnees['elements']);
        $this->assertSame(18, $donnees['elements_non_transmis']);
        $this->assertSame(219, $donnees['total_disponible']);
        $this->assertSame(['etudiant' => 'E1', 'reste' => '1 000 FCFA', 'url' => '/esbtp/etudiants/1'], $donnees['elements'][0]);
        $this->assertArrayHasKey('affichage', $donnees);

        $this->assertSame('15 étudiants en retard sur 219', ResumeOutil::resumeCourt('search_debtors', ['count' => 15, 'total' => 219]));
        $this->assertSame('1 frais configuré', ResumeOutil::resumeCourt('search_fees', ['count' => 1]));
        $this->assertSame('Aucun résultat', ResumeOutil::resumeCourt('search_students', ['results' => [], 'count' => 0]));
    }
}
