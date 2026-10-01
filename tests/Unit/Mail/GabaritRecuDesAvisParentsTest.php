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
    /** Étiquette « info » : la nuance sombre de la couleur de l'école, calculée au test. */
    private const PRIMAIRE_SOMBRE = 'primaire-sombre';

    /** Avis => [texte de l'étiquette, couleur attendue du texte de l'étiquette]. */
    private const STATUTS = [
        'absence-notification' => ['Absence', IdentiteDesCourriels::COULEUR_ALERTE_TEXTE],
        'bulletin-published' => ['Bulletin disponible', self::PRIMAIRE_SOMBRE],
        'inscription-confirmation' => ['Inscription confirmée', IdentiteDesCourriels::COULEUR_SUCCES_TEXTE],
        'low-attendance' => ['Assiduité insuffisante', IdentiteDesCourriels::COULEUR_DANGER],
        'low-grades' => ['Résultats insuffisants', IdentiteDesCourriels::COULEUR_DANGER],
        'note-published' => ['Nouvelle note', self::PRIMAIRE_SOMBRE],
        'paiement-created' => ['En attente de validation', self::PRIMAIRE_SOMBRE],
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
            if ($couleur === self::PRIMAIRE_SOMBRE) {
                $couleur = IdentiteDesCourriels::melanger('#0453cb', '#000000', 0.28);
            }

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
            $this->assertStringContainsString('Merci de ne pas répondre à ce courriel.', html_entity_decode($html, ENT_QUOTES), $gabarit);
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
        $this->assertStringContainsString(
            "'seuilPresence' => (int) \$preferences->attendance_rate_threshold",
            $source,
            "Le seuil affiché aux parents doit être celui qui décide l'envoi."
        );
    }

    public function test_les_avis_d_assiduite_affichent_le_seuil_transmis(): void
    {
        foreach (['absence-notification', 'low-attendance'] as $gabarit) {
            $html = html_entity_decode($this->rendre($gabarit, ['seuilPresence' => 75, 'tauxPresence' => 70]), ENT_QUOTES);

            $this->assertStringContainsString("seuil recommandé de 75\u{00A0}%", mb_strtolower($html), $gabarit);
            $this->assertStringNotContainsString("de 80\u{00A0}%", $html, "$gabarit : plus de seuil à 80.");
            $this->assertStringNotContainsString("80\u{00A0}%", $html, "$gabarit : plus de seuil à 80.");
        }
    }

    public function test_sans_seuil_transmis_les_avis_d_assiduite_retombent_sur_80(): void
    {
        $html = html_entity_decode($this->rendre('low-attendance'), ENT_QUOTES);

        $this->assertStringContainsString("seuil recommandé de 80\u{00A0}%", $html);
    }

    public function test_les_couleurs_de_texte_des_avis_sont_lisibles_sur_leur_fond(): void
    {
        // [texte, fond] : libellés, légendes, précisions et pied, sur la page
        // blanche comme sur l'encadré vedette.
        $paires = [
            ['#64748b', '#ffffff'], ['#64748b', '#f8fafc'], ['#5b6b80', '#eef2f7'],
            [IdentiteDesCourriels::COULEUR_DANGER, '#f8fafc'],
            [IdentiteDesCourriels::COULEUR_ALERTE_TEXTE, '#f8fafc'],
            [IdentiteDesCourriels::COULEUR_SUCCES_TEXTE, '#f8fafc'],
            [IdentiteDesCourriels::melanger('#0453cb', '#000000', 0.28), '#ffffff'],
        ];
        foreach ($paires as [$texte, $fond]) {
            $this->assertGreaterThanOrEqual(4.5, $this->contraste($texte, $fond), "$texte sur $fond");
        }

        $sources = array_merge(
            glob(resource_path('views/esbtp/emails/parents/*.blade.php')),
            glob(resource_path('views/esbtp/emails/parents/partials/*.blade.php')),
            [resource_path('views/esbtp/emails/partials/bouton.blade.php')],
        );
        foreach ($sources as $source) {
            $texte = (string) file_get_contents($source);
            $this->assertStringNotContainsString('#94a3b8', $texte, basename($source).' : gris illisible (2,56:1).');
            $this->assertStringNotContainsString('#f5f7fb', $texte, basename($source).' : fond qui fait tomber les libellés sous 4,5:1.');
        }
    }

    public function test_aucune_donnee_inconnue_ne_s_ecrit_n_a_dans_une_phrase(): void
    {
        $inconnues = [
            'classe' => 'N/A', 'anneeUniversitaire' => 'N/A', 'dateReinscription' => 'N/A',
            'dateRejet' => 'N/A', 'dateSoumission' => 'N/A', 'dateValidation' => 'N/A', 'periode' => 'N/A',
            'matieresEnDifficulte' => [['matiere' => 'N/A', 'moyenne' => 7]],
        ];
        foreach (AvisDExemple::noms() as $gabarit) {
            $html = $this->rendre($gabarit, $inconnues);
            $this->assertStringNotContainsString('N/A', $html, $gabarit);
        }

        $reinscription = html_entity_decode($this->rendre('reinscription-confirmation', $inconnues), ENT_QUOTES);
        $this->assertStringContainsString('Awa Koné est réinscrit(e)'."\n", $reinscription);
        $this->assertStringContainsString('Matière non précisée', $this->rendre('low-grades', $inconnues));
    }

    public function test_la_legende_du_paiement_valide_donne_le_pourcentage_paye(): void
    {
        $this->assertStringContainsString(' · 67&nbsp;%', $this->rendre('paiement-valide'));
    }

    public function test_le_taux_de_presence_s_ecrit_avec_une_virgule(): void
    {
        $html = $this->rendre('absence-notification', ['tauxPresence' => 78.57]);
        $this->assertStringContainsString('78,6', $html);
        $this->assertStringNotContainsString('78.57', $html);

        $this->assertStringContainsString('>78<', $this->rendre('low-attendance'));
    }

    public function test_l_avis_d_absence_ne_double_plus_l_alerte_d_assiduite(): void
    {
        $methode = new \ReflectionMethod(\App\Services\NotificationService::class, 'notifyParentsAbsence');
        $lignes = file($methode->getFileName());
        $corps = implode('', array_slice($lignes, $methode->getStartLine() - 1, $methode->getEndLine() - $methode->getStartLine() + 1));

        $this->assertStringContainsString('AbsenceNotificationMail', $corps);
        $this->assertStringNotContainsString('LowAttendanceMail', $corps, "Le seuil franchi est porté par l'avis d'absence : pas de second courriel.");
        $this->assertStringContainsString('MailPulseWorkflowIntent::absenceReported(', $corps);
        $this->assertStringContainsString('MailPulseWorkflowIntent::lowAttendance(', $corps, "L'intention MailPulse d'assiduité continue de partir.");
        $this->assertStringContainsString("'seuilPresence' => (int) \$preferences->attendance_rate_threshold", $corps);
    }

    private function contraste(string $texte, string $fond): float
    {
        $a = \App\Helpers\SettingsHelper::relativeLuminance($texte);
        $b = \App\Helpers\SettingsHelper::relativeLuminance($fond);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
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
