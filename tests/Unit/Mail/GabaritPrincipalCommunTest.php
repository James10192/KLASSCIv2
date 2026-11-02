<?php

namespace Tests\Unit\Mail;

use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Le gabarit « Reçu » est désormais le même pour les annonces,
 * les rapports et les e-mails de service. Aucun envoi réel ici.
 */
class GabaritPrincipalCommunTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('setting_school_name', 'Institut de démonstration', 3600);
        Cache::put('setting_school_logo', '', 3600);
    }

    public function test_annonce_utilise_le_modele_recu_avec_le_contenu_echappe(): void
    {
        $annonce = new ESBTPAnnonce([
            'titre' => 'Rentrée <script>danger()</script>',
            'contenu' => 'Communication <img src=x onerror=danger()>',
            'priorite' => 1,
        ]);

        $html = view('esbtp.emails.annonce', [
            'annonce' => $annonce,
            'lien' => 'https://ecole.example/mes-annonces',
        ])->render();

        $this->assertStringContainsString('Communication KLASSCI', $html);
        $this->assertStringContainsString('border-radius:16px', $html);
        $this->assertStringContainsString('height:4px', $html);
        $this->assertStringContainsString('Consulter mon espace KLASSCI', $html);
        $this->assertSame(1, substr_count($html, '<h1 '), 'Un seul titre principal par e-mail.');
        $this->assertStringContainsString('&lt;script&gt;danger()&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>danger()</script>', $html);
    }

    public function test_rapports_et_taches_conservent_leurs_actions_metier(): void
    {
        $rapport = view('esbtp.emails.exportable-report', [
            'reportTitle' => 'Rapport de test',
            'reportSubtitle' => '2026-2027',
            'senderName' => 'Gestionnaire',
        ])->render();
        $this->assertStringContainsString('Rapport en pièce jointe', $rapport);
        $this->assertStringContainsString('Institut de démonstration', $rapport);

        $tache = view('esbtp.emails.tache-bulletins-terminee', [
            'schoolName' => 'Institut de démonstration',
            'reussie' => true,
            'libelle' => 'Export des bulletins',
            'message' => 'Document généré',
            'lien' => 'https://ecole.example/export',
            'libelleLien' => 'Télécharger les bulletins',
            'conservationHeures' => 24,
            'estExport' => true,
            'prenom' => 'Alex',
        ])->render();
        $this->assertStringContainsString('Télécharger les bulletins', $tache);
        $this->assertStringContainsString('Document généré', $tache);
        $this->assertStringContainsString('Travail terminé', $tache);
    }

    public function test_la_demande_de_demo_utilise_la_marque_klassci_et_preserve_les_champs(): void
    {
        $html = view('emails.demo-request', [
            'date_demande' => '02/11/2026',
            'nom' => 'Contact commercial',
            'email' => 'contact@example.test',
            'telephone' => '+225 0000000000',
            'etablissement' => 'École pilote',
            'type_etablissement' => 'Collège',
            'nombre_etudiants' => '100 - 500',
            'message' => '<script>alert("x")</script>',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Navigateur de test',
        ])->render();

        $this->assertStringContainsString('Nouvelle demande de démonstration', $html);
        $this->assertStringContainsString('École pilote', $html);
        $this->assertStringContainsString('contact@example.test', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
    }

    public function test_la_messagerie_legacy_reste_disponible(): void
    {
        $this->assertTrue(view()->exists('esbtp.emails.layout-ancien'));
        $this->assertTrue(view()->exists('esbtp.emails.parents.recu'));
        $this->assertTrue(view()->exists('esbtp.emails.layout'));
        $this->assertStringContainsString(
            "esbtp.emails.parents.recu",
            file_get_contents(resource_path('views/esbtp/emails/layout.blade.php'))
        );
    }
}
