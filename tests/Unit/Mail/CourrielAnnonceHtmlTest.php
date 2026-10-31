<?php

namespace Tests\Unit\Mail;

use App\Mail\Transport\CorpsPourMailPulse;
use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CourrielAnnonceHtmlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('setting_school_name', 'ESBTP Abidjan', 3600);
        Cache::put('setting_school_logo', '', 3600);
    }

    public function test_le_gabarit_annonce_reutilise_identite_et_bouton_commun_et_echappe_le_contenu(): void
    {
        $annonce = new ESBTPAnnonce([
            'titre' => 'Reprise des cours <script>alert(1)</script>',
            'contenu' => "Bonjour les étudiants\nPrésence obligatoire <img src=x onerror=alert(1)>",
            'priorite' => 2,
        ]);
        $html = view('esbtp.emails.annonce', [
            'annonce' => $annonce,
            'lien' => 'https://ecole.test/esbtp/mes-annonces',
            'schoolName' => 'ESBTP Abidjan',
            'schoolLogoUrl' => null,
            'emailPrimaryColor' => '#0453cb',
        ])->render();

        $this->assertStringContainsString('ESBTP Abidjan', $html);
        $this->assertStringContainsString('Consulter mon espace KLASSCI', $html);
        $this->assertStringContainsString('INFORMATION IMPORTANTE', $html);
        $this->assertStringContainsString('white-space:pre-line', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertLessThan(15000, CorpsPourMailPulse::octetsJson(['email_html' => $html]));
    }
}
