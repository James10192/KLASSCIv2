<?php

namespace Tests\Unit\Exports;

use App\Exports\EnseignantsCompositionExport;
use App\Services\Enseignants\CompositionExportEnseignants as Composition;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CompositionExportEnseignantsTest extends TestCase
{
    private function affectations(): array
    {
        return [
            ['classe' => '1 BTS GTP', 'semestre' => 'S1', 'matiere' => 'Résistance des matériaux', 'nom' => 'M. KONE',
                'filiere' => 'Génie civil', 'niveau' => 'BTS 1', 'classe_id' => 14, 'matiere_id' => 5],
            ['classe' => '1 BTS GTP', 'semestre' => 'S2', 'matiere' => 'Résistance des matériaux', 'nom' => 'M. KONE',
                'filiere' => 'Génie civil', 'niveau' => 'BTS 1', 'classe_id' => 14, 'matiere_id' => 5],
            ['classe' => '2 BTS BTP', 'semestre' => 'S1', 'matiere' => 'Résistance des matériaux', 'nom' => 'Mme KOUAME',
                'filiere' => 'Génie civil', 'niveau' => 'BTS 2', 'classe_id' => 19, 'matiere_id' => 5],
            ['classe' => '2 BTS BTP', 'semestre' => 'S1', 'matiere' => 'Géotechnique', 'nom' => 'M. KONE',
                'filiere' => 'Génie civil', 'niveau' => 'BTS 2', 'classe_id' => 19, 'matiere_id' => 8],
        ];
    }

    public function test_liste_nom_seul_est_regroupee_par_matiere_et_tient_en_portrait(): void
    {
        $options = Composition::options('liste', ['nom'], 'matiere');
        $composition = Composition::creer([], $this->affectations(), 'liste', $options);

        $this->assertTrue($options['personnalise']);
        $this->assertSame(['nom' => 'Nom du professeur'], $composition['entetes']);
        $this->assertSame('portrait', Composition::orientation($composition, 'auto'));
        $this->assertSame(3, $composition['total']); // KONE n'est pas répété en S1/S2.
        $this->assertCount(2, $composition['groupes']);
        $this->assertSame('Résistance des matériaux', $composition['groupes'][1]['titre']);
        $this->assertCount(2, $composition['groupes'][1]['lignes']);
    }

    public function test_regroupement_niveau_separe_bts1_et_bts2_sans_colonne_niveau(): void
    {
        $options = Composition::options('liste', ['nom'], 'niveau');
        $composition = Composition::creer([], $this->affectations(), 'liste', $options);
        $this->assertSame(['BTS 1', 'BTS 2'], array_column($composition['groupes'], 'titre'));
        $this->assertSame(3, $composition['total']);
        $this->assertSame(['nom'], $composition['colonnes']);
    }

    public function test_regroupement_par_filiere_tient_en_portrait_et_deduplique_les_noms(): void
    {
        $options = Composition::options('liste', ['nom'], 'filiere');
        $composition = Composition::creer([], $this->affectations(), 'liste', $options);
        $this->assertSame('Génie civil', $composition['groupes'][0]['titre']);
        $this->assertSame(2, $composition['total']);
        $this->assertSame('portrait', Composition::orientation($composition, 'portrait'));
    }

    public function test_colonnes_larges_imposent_le_paysage_et_interdisent_portrait_force(): void
    {
        $options = Composition::options('liste',
            ['nom', 'matiere', 'classe', 'filiere', 'niveau'], 'matiere');
        $composition = Composition::creer([], $this->affectations(), 'liste', $options);
        $this->assertSame('landscape', Composition::orientation($composition, 'auto'));

        $this->expectException(ValidationException::class);
        Composition::orientation($composition, 'portrait');
    }

    public function test_export_excel_respecte_exactement_la_colonne_nom_et_les_rubriques(): void
    {
        $options = Composition::options('liste', ['nom'], 'matiere');
        $composition = Composition::creer([], $this->affectations(), 'liste', $options);
        $excel = new EnseignantsCompositionExport($composition);
        $this->assertSame(['Nom du professeur'], $excel->headings());
        $this->assertSame('Géotechnique — 1 ligne(s)', $excel->collection()->first()[0]);
        $this->assertCount(5, $excel->collection()); // 2 rubriques + 3 noms
    }

    public function test_groupement_sans_colonne_matiere_ne_peut_masquer_le_contexte(): void
    {
        $this->expectException(ValidationException::class);
        Composition::options('matieres', ['nom'], 'aucun');
    }

    public function test_perspectives_invalides_sont_refusees_et_defauts_historiques_conserves(): void
    {
        $this->assertFalse(Composition::options('matieres', null, null)['personnalise']);
        $this->assertTrue(Composition::options('matieres', ['nom'], null)['personnalise']);
        $this->assertTrue(Composition::options('annuaire', null, 'specialisation')['personnalise']);

        $this->expectException(ValidationException::class);
        Composition::options('liste', ['nom', 'identifiant_secret'], 'matiere');
    }

    public function test_noms_proches_ne_sont_pas_fusionnes(): void
    {
        $affectations = $this->affectations();
        $affectations[0]['nom'] = 'M. KONE';
        $affectations[1]['nom'] = 'KONE';
        $composition = Composition::creer([], $affectations, 'liste',
            Composition::options('liste', ['nom'], 'matiere'));
        $rm = $composition['groupes'][1];
        $this->assertCount(3, $rm['lignes']); // Deux variantes de KONE + KOUAME.
    }
}
