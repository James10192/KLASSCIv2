<?php

namespace Tests\Unit\Mail;

use App\Mail\Parents\AvisDExemple;
use App\Mail\Transport\CorpsPourMailPulse;
use App\Mail\Transport\MailPulseTransport;
use App\View\Composers\IdentiteDesCourriels;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Les avis aux parents passent par le bouton commun (`esbtp.emails.partials.bouton`).
 * L'ancien `<a class="button">` devenait lavande à texte sombre dans le mode sombre
 * des applications Gmail : le fond d'un lien seul est recoloré, pas celui porté par
 * `background-image`. Ce test vérifie le balisage, pas le rendu de Gmail.
 */
class BoutonsDesAvisAuxParentsTest extends TestCase
{
    /** Gabarit => [URL du bouton, libellé]. */
    private const AVIS = [
        'absence-notification' => ['https://ecole.test/absences/justifier', 'Soumettre un justificatif'],
        'bulletin-published' => ['https://ecole.test/bulletins/7', 'Télécharger le bulletin'],
        'inscription-confirmation' => ['https://ecole.test/login', 'Accéder à la plateforme'],
        'low-attendance' => ['https://ecole.test/absences', 'Voir les détails des absences'],
        'low-grades' => ['https://ecole.test/bulletins/7', 'Consulter le bulletin complet'],
        'note-published' => ['https://ecole.test/notes', 'Voir les détails'],
        'paiement-created' => ['https://ecole.test/paiements/42', 'Suivre mon paiement'],
        'paiement-rejete' => ['https://ecole.test/paiements', 'Soumettre un nouveau paiement'],
        'paiement-relance' => ['https://ecole.test/paiements', 'Effectuer un paiement'],
        'paiement-valide' => ['https://ecole.test/recus/42', 'Télécharger le reçu'],
        'reinscription-confirmation' => ['https://ecole.test/login', 'Accéder à la plateforme'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Cache::put('setting_school_name', 'Institut Supérieur KLASSCI', 3600);
        Cache::put('setting_school_logo', '', 3600);
    }

    private function rendre(string $gabarit): string
    {
        return view('esbtp.emails.parents.'.$gabarit, AvisDExemple::donnees())->render();
    }

    public function test_la_liste_des_avis_est_celle_de_l_essai_par_la_cli(): void
    {
        $this->assertSame(array_keys(self::AVIS), AvisDExemple::noms());
    }

    public function test_chaque_avis_porte_le_bouton_commun_et_plus_l_ancien(): void
    {
        foreach (self::AVIS as $gabarit => [$url, $libelle]) {
            $html = $this->rendre($gabarit);

            $this->assertStringNotContainsString('class="button"', $html, $gabarit);
            $this->assertStringNotContainsString('button-container', $html, $gabarit);
            $this->assertMatchesRegularExpression(
                '#<a href="'.preg_quote($url, '#').'" target="_blank" rel="noopener" [^>]*><span style="color:\#ffffff;">'.preg_quote(e($libelle), '#').'#',
                $html,
                "$gabarit : le libellé porte sa couleur dans un span (Gmail recolore un `<a>`)."
            );
            $this->assertStringContainsString('background-image:linear-gradient(', $html, $gabarit);
            $this->assertStringContainsString('<div class="gm-ecran"', $html, $gabarit);
            $this->assertStringContainsString('Copiez ce lien', $html, "$gabarit : le lien de secours reste offert.");
            $this->assertStringContainsString('word-break:break-all;">'.e($url).'</p>', $html, "$gabarit : l'adresse est recopiée en clair, à copier.");
            $this->assertStringNotContainsString('Ouvrir le lien', $html, "$gabarit : pas de second lien vers la même cible.");
            $this->assertStringContainsString('<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px;">', $html, "$gabarit : bouton en pleine largeur.");
        }
    }

    public function test_les_avis_courants_gardent_la_couleur_de_l_ecole(): void
    {
        $html = $this->rendre('paiement-valide');

        $this->assertStringContainsString('bgcolor="#0453cb" style="background:#0453cb;background-image:linear-gradient(#0453cb,#0453cb)', $html);
        $this->assertStringNotContainsString('linear-gradient('.IdentiteDesCourriels::COULEUR_DANGER, $html);
    }

    public function test_l_alerte_de_resultats_garde_son_bouton_rouge_et_son_contact(): void
    {
        $html = $this->rendre('low-grades');
        $rouge = IdentiteDesCourriels::COULEUR_DANGER;

        $this->assertStringContainsString("bgcolor=\"$rouge\" style=\"background:$rouge;background-image:linear-gradient($rouge,$rouge)", $html);
        $this->assertStringContainsString('href="https://ecole.test/contact"', $html);
        $this->assertStringContainsString('Contacter le coordinateur', $html);
        $this->assertSame(1, substr_count($html, 'style="margin:24px 0 8px;"'), 'Un seul bouton plein : le contact est un lien simple.');
    }

    public function test_un_en_tete_a_texte_sombre_retire_la_garde_sauf_sur_le_bouton_rouge(): void
    {
        // Le fondu ne restitue que le blanc : sur le bouton de l'école, à texte
        // sombre, il l'effacerait. Le bouton rouge, lui, est toujours écrit en blanc.
        Cache::put('setting_pdf_header_bg_color', '#f1f5f9', 3600);
        Cache::put('setting_pdf_header_text_color', '#111827', 3600);

        $this->assertStringNotContainsString('class="gm-ecran"', $this->rendre('paiement-valide'));

        $rouge = $this->rendre('low-grades');
        $this->assertSame(1, substr_count($rouge, '<div class="gm-ecran"'), "Seul le bouton rouge garde l'enveloppe.");
        $this->assertStringContainsString('<span style="color:#ffffff;">Consulter le bulletin complet', $rouge);
    }

    public function test_le_plus_lourd_des_avis_tient_largement_sous_le_plafond_de_mailpulse(): void
    {
        foreach (array_keys(self::AVIS) as $gabarit) {
            $octets = CorpsPourMailPulse::octetsJson(['email_html' => CorpsPourMailPulse::resserrer($this->rendre($gabarit))]);
            // Marge de 4 Ko : le HTML part dans `metadata`, à côté d'autres clés.
            $this->assertLessThan(MailPulseTransport::PLAFOND_METADATA - 4096, $octets, $gabarit);
        }
    }
}
