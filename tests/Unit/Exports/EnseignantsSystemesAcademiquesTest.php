<?php

namespace Tests\Unit\Exports;

use App\Exports\EnseignantsCompositionExport;
use App\Services\Enseignants\CompositionExportEnseignants as Composer;
use App\Services\Enseignants\PresentationAnnuairePdf;
use PHPUnit\Framework\TestCase;

final class EnseignantsSystemesAcademiquesTest extends TestCase
{
    private function donnees(): array
    {
        return [
            [
                'systeme' => 'BTS', 'matiere_id' => 5, 'classe_id' => 10,
                'matiere' => 'Mathématiques', 'classe' => '1 BTS A', 'nom' => 'M. KONE',
                'semestre' => 'S1', 'filiere' => 'Bâtiment', 'niveau' => 'BTS 1',
                'ue' => '—', 'parcours' => '—',
            ],
            [
                'systeme' => 'LMD', 'matiere_id' => 29, 'classe_id' => 20,
                'matiere' => 'Mathématiques', 'classe' => 'Licence 3 A', 'nom' => 'M. KONE',
                'semestre' => 'S5', 'filiere' => 'Maths', 'niveau' => 'Licence 3',
                'ue' => 'UE MATHS', 'parcours' => 'Mathématiques appliquées',
            ],
            [
                'systeme' => 'LMD', 'matiere_id' => 30, 'classe_id' => 21,
                'matiere' => 'Mathématiques', 'classe' => 'Licence 3 B', 'nom' => 'M. KONE',
                'semestre' => 'S6', 'filiere' => 'Maths', 'niveau' => 'Licence 3',
                'ue' => 'UE ANALYSE', 'parcours' => 'Sciences des données',
            ],
        ];
    }

    public function test_matieres_homonymes_sont_separees_par_systeme_et_ue_parcours(): void
    {
        $resultat = PresentationAnnuairePdf::preparer([], $this->donnees(), 'matieres');
        $this->assertSame(3, $resultat['nombreMatieres']);
        $this->assertCount(3, $resultat['matieresGroupes']);
        $this->assertCount(4, $resultat['affectationsSemestres']);
        $this->assertCount(1, $resultat['affectationsSemestres']['S5']);
        $this->assertCount(1, $resultat['affectationsSemestres']['S6']);

        $options = Composer::options('liste', ['nom'], 'matiere');
        $options['separer_systemes'] = true;
        $composition = Composer::creer([], $this->donnees(), 'liste', $options);
        $this->assertCount(3, $composition['groupes']);
        $titres = array_column($composition['groupes'], 'titre');
        $this->assertCount(1, array_filter($titres, fn ($v) => str_starts_with($v, 'BTS — Mathématiques')));
        $this->assertCount(2, array_filter($titres, fn ($v) => str_starts_with($v, 'LMD — Mathématiques')));
        $this->assertSame('portrait', Composer::orientation($composition, 'auto'));
        $this->assertSame(['Nom du professeur'], (new EnseignantsCompositionExport($composition))->headings());
    }

    public function test_homonymes_ecue_parcours_différents_ne_sont_pas_fusionnes_meme_id(): void
    {
        $lignes = $this->donnees();
        $lignes[2]['matiere_id'] = 29; // ECUE partagé entre deux parcours
        $resultat = PresentationAnnuairePdf::preparer([], $lignes, 'matieres');
        $this->assertCount(3, $resultat['matieresGroupes']);
    }

    public function test_classe_de_meme_nom_dans_deux_systemes_est_separee(): void
    {
        $lignes = $this->donnees();
        $lignes[1]['classe'] = '1 BTS A'; // Collision libellé
        $resultat = PresentationAnnuairePdf::preparer([], $lignes, 'synthese');
        $this->assertCount(3, $resultat['repartition']);
        $lmd = array_values(array_filter($resultat['repartition'],
            fn ($r) => $r['systeme'] === 'LMD'));
        $this->assertSame('S5', $lmd[0]['semestres']);
        $this->assertSame(1, $lmd[0]['total']);
        $this->assertSame(0, $lmd[0]['S1']);
    }

    public function test_lmd_en_s5_peut_afficher_periodes_et_ue_mais_pas_de_s1_bts(): void
    {
        $options = Composer::options('matieres', ['nom', 'ue', 'parcours', 'semestres', 'total'], 'matiere');
        $options['separer_systemes'] = true;
        $r = Composer::creer([], $this->donnees(), 'matieres', $options);
        $ecuesLmd = array_values(array_filter($r['groupes'],
            fn ($g) => str_starts_with($g['titre'], 'LMD')));
        $this->assertCount(2, $ecuesLmd);
        $this->assertEqualsCanonicalizing(['S5', 'S6'], array_map(
            fn ($g) => $g['lignes'][0]['semestres'], $ecuesLmd));
    }

    public function test_annuaire_classifie_meme_nom_par_programme_sans_fusion_automatique(): void
    {
        $rows = [
            ['nom' => 'M. KONE', 'origine' => 'Enregistré + bulletin', 'systeme' => 'BTS',
                'specialisation' => 'Mathématiques', 'matricule' => 'E001', 'regime' => 'Vacataire',
                'statut' => 'Actif', 'telephone' => '', 'email' => ''],
            ['nom' => 'M. KONE', 'origine' => 'Enregistré + bulletin', 'systeme' => 'LMD',
                'specialisation' => 'Mathématiques', 'matricule' => 'E001', 'regime' => 'Vacataire',
                'statut' => 'Actif', 'telephone' => '', 'email' => ''],
        ];
        $o = Composer::options('annuaire', ['nom', 'systeme'], 'aucun');
        $o['separer_systemes'] = true;
        $r = Composer::creer($rows, [], 'annuaire', $o);
        $this->assertSame(2, $r['total']);
        $this->assertSame(['BTS — Enseignants sélectionnés', 'LMD — Enseignants sélectionnés'],
            array_column($r['groupes'], 'titre'));
    }
}
