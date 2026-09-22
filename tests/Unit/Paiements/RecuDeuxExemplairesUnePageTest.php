<?php

namespace Tests\Unit\Paiements;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecuDeuxExemplairesUnePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $valeurs = \App\Helpers\SettingsHelper::getPdfDefaults();
        $valeurs['pdf_signature_director'] = '';
        $valeurs['pdf_signature_secretary'] = '';
        $valeurs['school_logo'] = '';
        $valeurs['school_name'] = 'ISLG';
        $valeurs['school_address'] = 'Abidjan';
        $valeurs['school_phone'] = '01020304';
        $valeurs['school_email'] = 'contact@example.com';
        // Lu par le composer du shell mobile au rendu de la vue : sans lui,
        // le test interroge la base, absente du job « Tests unitaires (sans base) ».
        $valeurs['ui.mobile_shell.enabled'] = false;
        foreach ($valeurs as $cle => $valeur) {
            Cache::put('setting_'.$cle, $valeur, 600);
        }
    }

    public function test_les_deux_exemplaires_tiennent_sur_une_page_a4(): void
    {
        $html = view('esbtp.paiements.recu', $this->donneesRecuChargé())->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait')->setOptions([
            'dpi' => 150,
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
            'isFontSubsettingEnabled' => true,
        ]);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $this->assertSame(
            1,
            $dompdf->getCanvas()->get_page_count(),
            'Les deux exemplaires du recu doivent tenir sur une seule page A4.'
        );
        $this->assertStringContainsString('EXEMPLAIRE ÉLÈVE / PARENT', $html);
        $this->assertStringContainsString('EXEMPLAIRE CAISSE', $html);
        $this->assertStringContainsString('exempté', $html);
        $this->assertStringNotContainsString('à définir', $html);
    }

    public function test_le_gabarit_interdit_un_saut_de_page_entre_exemplaires(): void
    {
        $css = file_get_contents(resource_path('views/esbtp/paiements/recu.blade.php'));

        $this->assertStringContainsString('page-break-inside: avoid', $css);
        $this->assertStringNotContainsString('margin-bottom: 14mm', $css);
        $this->assertStringNotContainsString('height: 128mm', $css);
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesRecuChargé(): array
    {
        $etudiant = new ESBTPEtudiant;
        $etudiant->matricule = 'TRAY1107000002';
        $etudiant->nom = 'TRAORE';
        $etudiant->prenoms = 'YAFOUAN';
        $user = new User;
        $user->name = 'TRAORE YAFOUAN';
        $etudiant->setRelation('user', $user);

        $filiere = new ESBTPFiliere;
        $filiere->name = 'RESSOURCES HUMAINES ET COMMUNICATION';
        $niveau = new ESBTPNiveauEtude;
        $niveau->name = 'BTS première année';
        $annee = new ESBTPAnneeUniversitaire;
        $annee->name = '2026-2027';

        $inscription = new ESBTPInscription;
        $inscription->affectation_status = 'affecté';
        $inscription->setRelation('filiere', $filiere);
        $inscription->setRelation('niveauEtude', $niveau);
        $inscription->setRelation('anneeUniversitaire', $annee);

        $emetteur = new User;
        $emetteur->name = 'SALL AMINATA';

        $paiement = new ESBTPPaiement;
        $paiement->numero_recu = 'REC-2026-0008';
        $paiement->montant = 60000;
        $paiement->date_paiement = Carbon::parse('2026-09-01');
        $paiement->status = 'validé';
        $paiement->mode_paiement = 'Espèces';
        $paiement->tranche = 'Première tranche';
        $paiement->setRelation('etudiant', $etudiant);
        $paiement->setRelation('inscription', $inscription);
        $paiement->setRelation('fraisCategory', null);
        $paiement->setRelation('creator', $emetteur);
        $paiement->setRelation('allocations', collect());

        $avant = new ESBTPPaiement;
        $avant->date_paiement = Carbon::parse('2026-09-01');
        $avant->montant = 40000;
        $avant->numero_recu = 'REC-2026-0007';

        $frais = collect([
            ['name' => 'FRAIS D\'iNSCRIPTION', 'checked' => true, 'in_kind' => false, 'restant' => 0, 'current' => true, 'non_configure' => false],
            ['name' => 'FRAIS DE TENUE', 'checked' => true, 'in_kind' => false, 'restant' => 0, 'current' => false, 'non_configure' => false],
            ['name' => 'SCOLARITE', 'checked' => false, 'in_kind' => false, 'restant' => 350000, 'current' => false, 'non_configure' => false],
            ['name' => 'PAQUET DE RAM', 'checked' => false, 'in_kind' => true, 'restant' => 0, 'current' => false, 'non_configure' => false],
            ['name' => 'CHEMISE CARTONNEE', 'checked' => false, 'in_kind' => true, 'restant' => 0, 'current' => false, 'non_configure' => false],
            ['name' => 'ASSURANCE', 'checked' => false, 'in_kind' => false, 'restant' => 0, 'current' => false, 'non_configure' => true],
        ]);

        return [
            'paiement' => $paiement,
            'settings' => [
                'school_name' => 'ISLG',
                'school_address' => 'Abidjan',
                'school_phone' => '01020304',
                'school_email' => 'contact@example.com',
                'show_logo' => false,
            ],
            'fraisLignes' => $frais,
            'resteAPayer' => 365000,
            'totalVerse' => 100000,
            'versementsAvant' => collect([$avant]),
            'versementsApres' => collect(),
            'affectationLabel' => 'Affecté',
        ];
    }
}
