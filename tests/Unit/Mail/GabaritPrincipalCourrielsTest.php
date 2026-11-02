<?php

namespace Tests\Unit\Mail;

use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GabaritPrincipalCourrielsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('setting_school_name', 'Établissement Démo', 3600);
        Cache::put('setting_school_logo', '', 3600);
    }

    public function test_le_courriel_annonce_utilise_le_gabarit_recu_et_garde_ses_donnees(): void
    {
        $annonce = new ESBTPAnnonce([
            'titre' => 'Message important',
            'contenu' => 'Reprise des cours demain',
            'priorite' => 2,
        ]);

        $html = view('esbtp.emails.annonce', [
            'annonce' => $annonce,
            'lien' => 'https://ecole.test/esbtp/mes-annonces',
        ])->render();

        $this->assertSame(1, substr_count($html, '<h1 '));
        $this->assertStringContainsString('Communication urgente', $html);
        $this->assertStringContainsString('Reprise des cours demain', $html);
        $this->assertStringContainsString('Consulter mon espace KLASSCI', $html);
        $this->assertStringContainsString('height:4px;', $html);
        $this->assertStringNotContainsString('class="kl-card"', $html);
    }

    public function test_le_code_de_connexion_conserve_son_contenu_et_le_sous_titre(): void
    {
        $html = view('esbtp.emails.student-login-code', [
            'nom' => 'Exemple',
            'code' => '749201',
            'minutes' => 5,
        ])->render();

        $this->assertStringContainsString('749201', $html);
        $this->assertStringContainsString('Une connexion simple, sans mot de passe', $html);
        $this->assertSame(1, substr_count($html, '<h1 '));
    }

    public function test_l_ancien_gabarit_est_toujours_disponible(): void
    {
        $html = view('esbtp.emails.layout-legacy', [
            'emailTitle' => 'Ancien modèle',
        ])->render();

        $this->assertStringContainsString('class="kl-card"', $html);
        $this->assertStringContainsString('Ancien modèle', $html);
    }
}
