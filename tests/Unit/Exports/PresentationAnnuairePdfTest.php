<?php

namespace Tests\Unit\Exports;

use App\Services\Enseignants\PresentationAnnuairePdf;
use PHPUnit\Framework\TestCase;

final class PresentationAnnuairePdfTest extends TestCase
{
    private function rows(): array
    {
        return [
            [
                'nom' => 'ANO MARCEL', 'origine' => 'Enregistré + bulletin',
                'email' => 'NEANT12@NEANT.com', 'telephone' => '—',
                'matricule' => 'ENS-001', 'specialisation' => 'Informatique',
                'regime' => 'Permanent', 'statut' => 'Actif', 'classes' => '1 BTS',
            ],
            [
                'nom' => 'M. ANO MARCEL', 'origine' => 'Bulletin (saisie manuelle)',
                'email' => '—', 'telephone' => '—', 'matricule' => '—',
                'specialisation' => '—', 'regime' => '—', 'statut' => '—',
                'classes' => '1 BTS, 2 BTS',
            ],
            [
                'nom' => 'Mme Kouamé', 'origine' => 'Bulletin (saisie manuelle)',
                'email' => '—', 'telephone' => '—', 'matricule' => '—',
                'specialisation' => '—', 'regime' => '—', 'statut' => '—',
                'classes' => '2 BTS',
            ],
        ];
    }

    private function affectations(): array
    {
        return [
            ['classe' => '1 BTS', 'semestre' => 'S1', 'matiere' => 'Maths', 'nom' => 'ANO MARCEL'],
            ['classe' => '1 BTS', 'semestre' => 'S2', 'matiere' => 'Français', 'nom' => 'M. ANO MARCEL'],
            ['classe' => '2 BTS', 'semestre' => 'S1', 'matiere' => 'Maths', 'nom' => 'Mme Kouamé'],
        ];
    }

    public function test_la_synthese_separe_fiches_et_libelles_et_repartit_les_affectations(): void
    {
        $r = PresentationAnnuairePdf::preparer($this->rows(), $this->affectations(), 'synthese');

        $this->assertSame(1, $r['nombreFiches']);
        $this->assertSame(2, $r['nombreSaisies']);
        $this->assertSame(2, $r['nombreClasses']);
        $this->assertSame('1 BTS', $r['repartition'][0]['classe']);
        $this->assertSame(1, $r['repartition'][0]['S1']);
        $this->assertSame(1, $r['repartition'][0]['S2']);
        $this->assertSame(2, $r['repartition'][0]['enseignants_saisis']);
        $this->assertCount(2, $r['saisies']);
    }

    public function test_les_variantes_sont_signalees_mais_non_fusionnees(): void
    {
        $r = PresentationAnnuairePdf::preparer($this->rows(), $this->affectations(), 'rapprochement');

        $this->assertCount(1, $r['pistes']);
        $this->assertStringContainsString('ANO MARCEL', $r['pistes'][0]);
        $this->assertStringContainsString('M. ANO MARCEL', $r['pistes'][0]);
        $this->assertSame(2, $r['nombreSaisies']);
    }

    public function test_les_adresses_provisoires_sont_masquees_sans_modifier_la_donnee(): void
    {
        $r = PresentationAnnuairePdf::preparer($this->rows(), [], 'annuaire');

        $this->assertSame('—', $r['fiches'][0]['email_affiche']);
        $this->assertSame('NEANT12@NEANT.com', $this->rows()[0]['email']);
        $this->assertSame('prof@esbtp.edu.ci', PresentationAnnuairePdf::contactValide('prof@esbtp.edu.ci', true));
        $this->assertSame('—', PresentationAnnuairePdf::contactValide('adresse invalide', true));
    }

    public function test_les_affectations_conservent_les_deux_semestres(): void
    {
        $r = PresentationAnnuairePdf::preparer($this->rows(), $this->affectations(), 'affectations');
        $this->assertCount(2, $r['affectationsSemestres']['S1']);
        $this->assertCount(1, $r['affectationsSemestres']['S2']);
        $this->assertSame('M. ANO MARCEL', $r['affectationsSemestres']['S2'][0]['nom']);
    }
}
