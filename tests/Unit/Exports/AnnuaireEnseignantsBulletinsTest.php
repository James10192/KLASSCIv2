<?php

namespace Tests\Unit\Exports;

use App\Services\BulletinInlineConfigurationService;
use App\Services\Enseignants\AnnuaireEnseignantsBulletins;
use Tests\TestCase;

class AnnuaireEnseignantsBulletinsTest extends TestCase
{
    private function annuaire(): AnnuaireEnseignantsBulletins
    {
        return new AnnuaireEnseignantsBulletins(
            $this->createMock(BulletinInlineConfigurationService::class)
        );
    }

    private function inscrit(string $nom): array
    {
        return [
            'matricule' => 'ENS-01', 'nom' => $nom,
            'telephone' => '—', 'email' => '—',
            'specialisation' => 'Sciences', 'regime' => 'Vacataire', 'statut' => 'Actif',
        ];
    }

    private function affectation(string $nom, string $classe = '2BTS GBAT A'): array
    {
        return [
            'classe' => $classe, 'semestre' => 'S2',
            'matiere' => 'Mathématiques', 'nom' => $nom,
        ];
    }

    public function test_un_professeur_saisi_sur_un_bulletin_sans_compte_figure_dans_l_annuaire(): void
    {
        $a = $this->annuaire()->consolider([], [$this->affectation('M TOURE')]);
        $this->assertCount(1, $a['rows']);
        $this->assertSame('Bulletin (saisie manuelle)', $a['rows'][0]['origine']);
        $this->assertSame('—', $a['rows'][0]['telephone']);
        $this->assertCount(1, $a['affectations']);
    }

    public function test_un_nom_manuel_identique_a_un_enseignant_enregistre_n_est_pas_duplique(): void
    {
        $a = $this->annuaire()->consolider(
            [$this->inscrit('M. TOURÉ')],
            [$this->affectation('M TOURE'), $this->affectation('M TOURE', '1BTS GTP')]
        );
        $this->assertCount(1, $a['rows']);
        $this->assertSame('ENS-01', $a['rows'][0]['matricule']);
        $this->assertSame('Enregistré + bulletin', $a['rows'][0]['origine']);
        $this->assertSame('2BTS GBAT A, 1BTS GTP', $a['rows'][0]['classes']);
        $this->assertCount(2, $a['affectations']);
    }

    public function test_filtre_sur_statut_ou_specialisation_ne_fait_pas_entrer_un_nom_manuel_sans_statut(): void
    {
        $a = $this->annuaire()->consolider(
            [$this->inscrit('Professeur Koffi')], [$this->affectation('M TOURE')], false
        );
        $this->assertCount(1, $a['rows']);
        $this->assertSame([], $a['affectations']);
    }

    public function test_la_recherche_filtre_aussi_les_noms_du_bulletin(): void
    {
        $a = $this->annuaire()->consolider(
            [], [$this->affectation('M TOURE'), $this->affectation('Mme Kouame')],
            true, 'kouame'
        );
        $this->assertCount(1, $a['rows']);
        $this->assertSame('Mme Kouame', $a['rows'][0]['nom']);
        $this->assertCount(1, $a['affectations']);
    }
}
