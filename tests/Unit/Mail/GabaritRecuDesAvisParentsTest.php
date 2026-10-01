<?php

namespace Tests\Unit\Mail;

use App\Helpers\MontantFcfa;
use App\Mail\Parents\AvisDExemple;
use App\View\Composers\IdentiteDesCourriels;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Les onze avis aux parents suivent la direction « Reçu » : page blanche, une
 * étiquette de statut, un chiffre en vedette, des lignes libellé / valeur, un
 * pied de page utile. Les anciennes cartes KPI et encadrés d'alerte en cascade
 * ne reviennent pas.
 */
class GabaritRecuDesAvisParentsTest extends TestCase
{
    /** Avis => [texte de l'étiquette, couleur attendue du texte de l'étiquette]. */
    private const STATUTS = [
        'absence-notification' => ['Absence', IdentiteDesCourriels::COULEUR_ALERTE_TEXTE],
        'bulletin-published' => ['Bulletin disponible', '#0453cb'],
        'inscription-confirmation' => ['Inscription confirmée', IdentiteDesCourriels::COULEUR_SUCCES_TEXTE],
        'low-attendance' => ['Assiduité insuffisante', IdentiteDesCourriels::COULEUR_DANGER],
        'low-grades' => ['Résultats insuffisants', IdentiteDesCourriels::COULEUR_DANGER],
        'note-published' => ['Nouvelle note', '#0453cb'],
        'paiement-created' => ['En attente de validation', '#0453cb'],
        'paiement-rejete' => ['Paiement rejeté', IdentiteDesCourriels::COULEUR_DANGER],
        'paiement-relance' => ['Rappel · deuxième envoi', IdentiteDesCourriels::COULEUR_ALERTE_TEXTE],
        'paiement-valide' => ['Paiement validé', IdentiteDesCourriels::COULEUR_SUCCES_TEXTE],
        'reinscription-confirmation' => ['Réinscription confirmée', IdentiteDesCourriels::COULEUR_SUCCES_TEXTE],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Cache::put('setting_school_name', 'Institut Supérieur KLASSCI', 3600);
        Cache::put('setting_school_address', 'Cocody Riviera 3', 3600);
        Cache::put('setting_school_city', 'Abidjan', 3600);
        Cache::put('setting_school_phone', '+225 27 22 00 00 00', 3600);
        Cache::put('setting_school_email', 'scolarite@ecole.test', 3600);
        Cache::put('setting_school_logo', '', 3600);
    }

    private function rendre(string $gabarit, array $surcharge = []): string
    {
        return view('esbtp.emails.parents.'.$gabarit, $surcharge + AvisDExemple::donnees())->render();
    }

    public function test_les_etiquettes_couvrent_les_onze_avis(): void
    {
        $this->assertSame(AvisDExemple::noms(), array_keys(self::STATUTS));
    }

    public function test_aucun_avis_ne_porte_plus_les_cartes_kpi_ni_les_encadres_d_alerte(): void
    {
        foreach (AvisDExemple::noms() as $gabarit) {
            $html = $this->rendre($gabarit);

            $this->assertDoesNotMatchRegularExpression('/class="[^"]*\b(kpi-[a-z]+|alert(-[a-z]+)?|info-table|badge(-[a-z]+)?|message-intro|instruction-box)\b/', $html, $gabarit);
            $this->assertStringNotContainsString('.kpi-card', $html, "$gabarit : l'ancienne feuille de style est partie.");
            // Les directives Blade mal collées à un mot sortent en texte : aucune ne doit fuir.
            $this->assertDoesNotMatchRegularExpression('/@(if|endif|endsection|section|include|else)\b/', $html, $gabarit);
        }
    }

    public function test_chaque_avis_porte_son_etiquette_de_statut_en_couleur_de_sens(): void
    {
        foreach (self::STATUTS as $gabarit => [$texte, $couleur]) {
            $html = $this->rendre($gabarit);

            $this->assertStringContainsString(
                'text-transform:uppercase;color:'.$couleur.';">'.e($texte).'</td>',
                $html,
                "$gabarit : étiquette « $texte »."
            );
            $this->assertSame(1, substr_count($html, '<h1 '), "$gabarit : une seule phrase-titre.");
        }
    }

    public function test_chaque_avis_porte_un_pied_de_page_utile_lu_dans_les_reglages(): void
    {
        foreach (AvisDExemple::noms() as $gabarit) {
            $html = $this->rendre($gabarit);

            $this->assertStringContainsString('<strong style="color:#0f172a;">Institut Supérieur KLASSCI</strong>', $html, $gabarit);
            $this->assertStringContainsString('Cocody Riviera 3, Abidjan', $html, $gabarit);
            $this->assertStringContainsString('href="tel:+2252722000000"', $html, "$gabarit : téléphone cliquable.");
            $this->assertStringContainsString('href="mailto:scolarite@ecole.test"', $html, $gabarit);
            $this->assertStringContainsString("contact parent de l'élève Awa Koné", html_entity_decode($html, ENT_QUOTES), "$gabarit : la raison de l'envoi.");
        }
    }

    public function test_le_pied_de_page_suit_l_ecole_et_jamais_un_nom_en_dur(): void
    {
        Cache::put('setting_school_name', 'Lycée Technique Démo', 3600);

        $html = $this->rendre('paiement-valide');

        $this->assertStringContainsString('Lycée Technique Démo', $html);
        $this->assertStringNotContainsString('Institut Supérieur KLASSCI', $html);
    }

    public function test_les_montants_des_avis_sont_insecables(): void
    {
        $html = $this->rendre('paiement-relance');

        $this->assertStringContainsString((string) MontantFcfa::html(150000), $html, 'Phrase-titre : montant insécable.');
        $this->assertStringNotContainsString('150 000', $html, 'Plus aucun séparateur de milliers sécable.');
        $this->assertStringContainsString("150\u{202F}000", $html);
    }

    public function test_un_avis_se_passe_d_une_donnee_absente_ou_inconnue(): void
    {
        $html = $this->rendre('paiement-relance', ['echeance' => null, 'classe' => 'N/A']);

        $this->assertStringNotContainsString('Échéance', $html);
        $this->assertStringNotContainsString('N/A', $html);
        $this->assertStringNotContainsString('>Classe<', $html);
    }

    public function test_l_alerte_de_resultats_ne_cite_aucun_taux_de_presence_sans_la_donnee(): void
    {
        // Son appelant réel ne transmet plus de taux : il en écrivait un en dur (85).
        $donnees = AvisDExemple::donnees();
        unset($donnees['tauxPresence']);

        $html = view('esbtp.emails.parents.low-grades', $donnees)->render();

        $this->assertStringNotContainsString('taux de présence', mb_strtolower(html_entity_decode($html, ENT_QUOTES)));
        $this->assertStringContainsString('Veillez à son assiduité.', html_entity_decode($html, ENT_QUOTES));
    }

    public function test_aucun_appelant_n_ecrit_de_taux_de_presence_ni_de_mois_en_anglais(): void
    {
        $source = (string) file_get_contents(app_path('Services/NotificationService.php'));

        $this->assertDoesNotMatchRegularExpression("/'tauxPresence'\s*=>\s*\d/", $source, 'Taux de présence écrit en dur.');
        $this->assertStringNotContainsString("now()->format('F Y')", $source, 'Mois en anglais : utiliser translatedFormat.');
    }

    public function test_les_alertes_lisent_les_donnees_des_vrais_appelants(): void
    {
        // L'alerte d'assiduité reçoit les données de l'avis d'absence : ni
        // `periode` ni `absencesUrl`. L'alerte de résultats nomme la matière `matiere`.
        $donnees = AvisDExemple::donnees();
        unset($donnees['periode'], $donnees['absencesUrl']);
        $assiduite = view('esbtp.emails.parents.low-attendance', $donnees)->render();
        $this->assertStringContainsString('septembre 2026', $assiduite);
        $this->assertStringContainsString('href="https://ecole.test/absences/justifier"', $assiduite);

        $resultats = $this->rendre('low-grades', ['matieresEnDifficulte' => [['matiere' => 'Topographie', 'moyenne' => 6]]]);
        $this->assertStringContainsString('Topographie', $resultats);
        $this->assertStringContainsString('6,00/20', $resultats);
    }
}
