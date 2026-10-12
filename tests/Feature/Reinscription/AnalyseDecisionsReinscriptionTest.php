<?php

namespace Tests\Feature\Reinscription;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPRegleAcademique;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\ReeinscriptionService;
use App\Services\Reinscription\MoyennesAnnuellesDuBulletin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'analyse des decisions de reinscription porte sur l'annee PRECEDENTE : pour
 * reinscrire vers 2025-2026, on regarde les inscriptions de 2024-2025.
 *
 * Une ecole qui demarre n'a pas d'annee precedente. Ce cas doit rendre un
 * resultat vide, pas lever une exception qui casserait la page de
 * reinscription. Ces tests executent le service ; ils remplacent un ancien
 * test qui se contentait de chercher des morceaux de texte dans le fichier
 * source, et qui serait donc reste vert sur du code casse.
 */
class AnalyseDecisionsReinscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La factory des evaluations pointe en dur sur l'utilisateur 1.
        User::factory()->create(['id' => 1]);
        $this->actingAs(User::find(1));
    }

    public function test_sans_annee_precedente_l_analyse_rend_un_resultat_vide_sans_lever_d_exception(): void
    {
        // Une seule annee existe : c'est la premiere rentree de l'ecole.
        $anneeCourante = $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);
        $this->etudiantInscritEn($anneeCourante);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision($anneeCourante->name);

        $this->assertSame(
            ['passages' => [], 'rattrapages' => [], 'redoublements' => [], 'errors' => []],
            $resultat,
            "Sans annee precedente, l'analyse doit rendre les quatre listes vides."
        );
    }

    public function test_avec_une_annee_precedente_l_analyse_parcourt_reellement_les_inscriptions(): void
    {
        // Ce test est le garde-fou du precedent : sans lui, une methode qui
        // renverrait toujours un resultat vide passerait pour correcte.
        $anneePrecedente = $this->annee('2024-2025', '2024-09-01', '2025-07-31');
        $anneeCourante = $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);

        $etudiant = $this->etudiantInscritEn($anneePrecedente);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision($anneeCourante->name);

        $tousLesEtudiants = collect($resultat)
            ->flatten(1)
            ->pluck('etudiant.id')
            ->filter()
            ->all();

        $this->assertContains(
            $etudiant->id,
            $tousLesEtudiants,
            "Un etudiant inscrit l'annee precedente et non reinscrit doit apparaitre dans l'analyse."
        );
    }

    public function test_un_etudiant_deja_reinscrit_est_exclu_de_l_analyse(): void
    {
        $anneePrecedente = $this->annee('2024-2025', '2024-09-01', '2025-07-31');
        $anneeCourante = $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);

        $etudiant = $this->etudiantInscritEn($anneePrecedente);
        // Il a deja son inscription pour l'annee a venir : il n'a plus rien a faire ici.
        $this->etudiantInscritEn($anneeCourante, $etudiant);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision($anneeCourante->name);

        $tousLesEtudiants = collect($resultat)
            ->flatten(1)
            ->pluck('etudiant.id')
            ->filter()
            ->all();

        $this->assertNotContains($etudiant->id, $tousLesEtudiants);
    }

    public function test_sans_annee_courante_l_analyse_refuse_explicitement(): void
    {
        // Aucune annee marquee courante : le service doit le dire, pas rendre
        // un resultat vide qu'on prendrait pour « rien a reinscrire ».
        $this->annee('2024-2025', '2024-09-01', '2025-07-31');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucune année universitaire courante définie');

        app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');
    }

    public function test_la_decision_se_lit_sur_les_notes_de_l_annee_terminee(): void
    {
        $anneePrecedente = $this->annee('2024-2025', '2024-09-01', '2025-07-31');
        $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);

        $etudiant = $this->etudiantInscritEn($anneePrecedente);
        $this->noterEn($etudiant, $anneePrecedente->name, 14.0);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');

        $this->assertContains(
            $etudiant->id,
            collect($resultat['passages'])->pluck('etudiant.id')->all(),
            'Une moyenne de 14 sur l\'annee terminee vaut un passage. La trouver ailleurs '
            .'signifie que l\'analyse regarde de nouveau l\'annee rejointe, ou ces etudiants '
            .'n\'ont par construction aucune note — et une moyenne a zero ne leve rien.'
        );
    }

    public function test_les_notes_de_l_annee_rejointe_ne_decident_de_rien(): void
    {
        // Le pendant du test precedent : sans lui, une analyse qui lirait les
        // DEUX annees passerait pour correcte.
        $anneePrecedente = $this->annee('2024-2025', '2024-09-01', '2025-07-31');
        $anneeCourante = $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);

        $etudiant = $this->etudiantInscritEn($anneePrecedente);
        $this->noterEn($etudiant, $anneeCourante->name, 18.0);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision($anneeCourante->name);

        $this->assertNotContains(
            $etudiant->id,
            collect($resultat['passages'])->pluck('etudiant.id')->all(),
            'Des notes portant l\'annee vers laquelle on reinscrit ne doivent pas fonder la decision.'
        );
    }

    /**
     * La reinscription decide sur la moyenne annuelle PONDEREE que le bulletin
     * imprime, pas sur sa moyenne simple des matieres. Cas mesure sur
     * esbtp-abidjan (FESBTP25-0433, BTS1, poids S1 x1 + S2 x2) : bulletins
     * S1 10,97 et S2 9,89, annuelle imprimee 10,25, « Admis ». Ses notes
     * brutes, elles, donnaient une moyenne simple sous 10.
     */
    public function test_la_decision_suit_la_moyenne_annuelle_ponderee_du_bulletin(): void
    {
        [$etudiant, $inscription] = $this->eleveAvecBulletins(10.97, 9.89, noteBrute: 8.0);

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertSame('bulletin_annuel', $analyse['moyenne_source']);
        $this->assertEqualsWithDelta(10.25, (float) $analyse['moyenne_generale'], 0.005);
        $this->assertSame('passage', $analyse['decision'], 'Le bulletin dit « Admis » : la reinscription doit proposer le passage.');

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');
        $this->assertContains($etudiant->id, collect($resultat['passages'])->pluck('etudiant.id')->all());
    }

    public function test_un_bon_s2_ne_fait_pas_passer_un_eleve_que_le_bulletin_fait_redoubler(): void
    {
        // Moyenne simple des notes brutes : 14, l'ancien calcul proposait le
        // passage. Annuelle du bulletin : (7,41 + 2 x 10,24) / 3 = 9,30.
        [$etudiant, $inscription] = $this->eleveAvecBulletins(7.41, 10.24, noteBrute: 14.0);

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertEqualsWithDelta(9.30, (float) $analyse['moyenne_generale'], 0.005);
        $this->assertNotSame('passage', $analyse['decision']);

        $resultat = app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');
        $this->assertNotContains($etudiant->id, collect($resultat['passages'])->pluck('etudiant.id')->all());
    }

    /**
     * L'ecole qui fait trancher le conseil sur le semestre 2 : la reinscription
     * suit le meme reglage que le bulletin, pas l'annuelle d'office.
     */
    public function test_la_decision_suit_la_source_du_conseil_reglee_pour_le_niveau(): void
    {
        [, $inscription] = $this->eleveAvecBulletins(7.41, 10.24, noteBrute: 14.0);
        $annee = (int) $inscription->classe->niveau->year;
        SettingsHelper::setOrCreate("bulletin_bts{$annee}_council_average_source", 'semestre2');

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertSame('bulletin_semestre2', $analyse['moyenne_source']);
        $this->assertEqualsWithDelta(10.24, (float) $analyse['moyenne_generale'], 0.005);
        $this->assertSame('passage', $analyse['decision']);
    }

    public function test_le_choix_deux_moyennes_refuse_le_passage_si_annuelle_sous_le_seuil(): void
    {
        [$etudiant, $inscription] = $this->eleveAvecBulletins(7.41, 10.24, noteBrute: 14.0);
        SettingsHelper::setOrCreate('bulletin_bts1_council_average_source', 'both');

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertSame('bulletin_deux_moyennes', $analyse['moyenne_source']);
        $this->assertEqualsWithDelta(9.30, $analyse['moyenne_generale'], 0.01);
        $this->assertNotSame('passage', $analyse['decision']);
        $this->assertFalse($analyse['peut_passer']);

        $liste = app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');
        $this->assertNotContains($etudiant->id, collect($liste['passages'])->pluck('etudiant.id')->all());
    }

    public function test_le_choix_deux_moyennes_refuse_le_passage_si_s2_sous_le_seuil(): void
    {
        [, $inscription] = $this->eleveAvecBulletins(14.0, 8.0, noteBrute: 16.0);
        SettingsHelper::setOrCreate('bulletin_bts1_council_average_source', 'both');

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertSame('bulletin_deux_moyennes', $analyse['moyenne_source']);
        $this->assertEqualsWithDelta(8.0, $analyse['moyenne_generale'], 0.01);
        $this->assertNotSame('passage', $analyse['decision']);
    }

    public function test_le_choix_deux_moyennes_admet_si_les_deux_atteignent_le_seuil(): void
    {
        [, $inscription] = $this->eleveAvecBulletins(11.0, 13.0, noteBrute: 7.0);
        SettingsHelper::setOrCreate('bulletin_bts1_council_average_source', 'both');

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertSame('bulletin_deux_moyennes', $analyse['moyenne_source']);
        $this->assertSame('passage', $analyse['decision']);
        $this->assertTrue($analyse['peut_passer']);
    }

    public function test_une_annuelle_sous_le_seuil_refuse_le_passage_meme_si_le_s2_est_bon_et_la_regle_academique_plus_basse(): void
    {
        [, $inscription] = $this->eleveAvecBulletins(7.41, 10.24, noteBrute: 17.0);
        SettingsHelper::setOrCreate('bulletin_bts1_council_mode', 'threshold');
        SettingsHelper::setOrCreate('bulletin_bts1_council_average_source', 'annual');
        SettingsHelper::setOrCreate('bulletin_bts1_council_threshold', '10');
        ESBTPRegleAcademique::where('niveau', $inscription->classe->niveau->name)
            ->where('filiere', $inscription->classe->filiere->name)
            ->update(['moyenne_passage' => 9]);

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);
        $this->assertSame('bulletin_annuel', $analyse['moyenne_source']);
        $this->assertEqualsWithDelta(9.30, $analyse['moyenne_generale'], 0.01);
        $this->assertFalse($analyse['peut_passer']);
        $this->assertSame('redoublement', $analyse['decision']);
    }

    public function test_une_annuelle_indisponible_ne_declenche_aucun_passage_avec_bon_s2_en_mode_annuel(): void
    {
        [$etudiant, $inscription] = $this->eleveAvecBulletins(12.0, 13.0, noteBrute: 18.0);
        ESBTPBulletin::where('etudiant_id', $etudiant->id)->where('periode', 'semestre1')->delete();
        SettingsHelper::setOrCreate('bulletin_bts1_council_mode', 'threshold');
        SettingsHelper::setOrCreate('bulletin_bts1_council_average_source', 'annual');
        SettingsHelper::setOrCreate('bulletin_bts1_council_threshold', '10');

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertSame('bulletin_incomplet', $analyse['moyenne_source']);
        $this->assertNull($analyse['moyenne_generale']);
        $this->assertFalse($analyse['peut_passer']);
        $this->assertSame('a_verifier', $analyse['decision']);

        $liste = app(ReeinscriptionService::class)->getEtudiantsParDecision('2025-2026');
        $this->assertNotContains($etudiant->id, collect($liste['passages'])->pluck('etudiant.id')->all());
        $this->assertContains($etudiant->id, collect($liste['errors'])->pluck('etudiant.id')->all());
    }

    public function test_une_annuelle_absente_ne_peut_etre_remplacee_par_les_notes_brutes_en_mode_deux(): void
    {
        [, $inscription] = $this->eleveAvecBulletins(12.0, 13.0, noteBrute: 18.0);
        SettingsHelper::setOrCreate('bulletin_bts1_council_average_source', 'both');

        $decision = app(MoyennesAnnuellesDuBulletin::class)->decision(
            $inscription,
            $inscription->classe,
            [
                'semestre1' => null,
                'semestre2' => 13.0,
                'moyenne' => null,
                'source_conseil' => 'both',
                'moyenne_decision' => null,
            ],
            18.0,
            false
        );

        $this->assertNull($decision['moyenne']);
        $this->assertSame('bulletin_incomplet', $decision['source']);
    }

    public function test_le_seuil_du_conseil_est_respecte_pour_la_reinscription_en_mode_deux_moyennes(): void
    {
        [, $inscription] = $this->eleveAvecBulletins(11.0, 11.0, noteBrute: 15.0);
        SettingsHelper::setOrCreate('bulletin_bts1_council_mode', 'threshold');
        SettingsHelper::setOrCreate('bulletin_bts1_council_average_source', 'both');
        SettingsHelper::setOrCreate('bulletin_bts1_council_threshold', '12');

        $analyse = app(ReeinscriptionService::class)->analyserSituationEtudiantParInscription($inscription);

        $this->assertEqualsWithDelta(11.0, $analyse['moyenne_generale'], 0.01);
        $this->assertSame('redoublement', $analyse['decision']);
        $this->assertFalse($analyse['peut_passer']);
        $this->assertTrue($analyse['doit_redoubler']);
    }

    /**
     * @return array{0: ESBTPEtudiant, 1: ESBTPInscription}
     */
    private function eleveAvecBulletins(float $s1, float $s2, float $noteBrute): array
    {
        $anneePrecedente = $this->annee('2024-2025', '2024-09-01', '2025-07-31');
        $this->annee('2025-2026', '2025-09-01', '2026-07-31', courante: true);

        SettingsHelper::setOrCreate('bulletin_semester1_weight', '1');
        SettingsHelper::setOrCreate('bulletin_semester2_weight', '2');
        SettingsHelper::setOrCreate('bulletin_show_attendance_note', '0');

        // Le conseil automatique visé est celui du BTS 1 : le niveau ne
        // peut pas être tiré aléatoirement entre BTS 1, 2 et 3 par la factory.
        $etudiant = $this->etudiantInscritEn($anneePrecedente, null, 1);
        $inscription = ESBTPInscription::where('etudiant_id', $etudiant->id)->with(['classe.niveau', 'classe.filiere', 'anneeUniversitaire', 'etudiant'])->firstOrFail();

        ESBTPRegleAcademique::create([
            'niveau' => $inscription->classe->niveau?->name ?? '',
            'filiere' => $inscription->classe->filiere?->name ?? '',
            'moyenne_passage' => 10,
            'moyenne_rattrapage' => 7,
            'max_matieres_rattrapage' => 0,
            'autoriser_redoublement' => true,
            'max_redoublements' => 2,
            'actif' => true,
        ]);

        foreach (['semestre1' => $s1, 'semestre2' => $s2] as $periode => $moyenne) {
            ESBTPBulletin::factory()->create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $inscription->classe_id,
                'annee_universitaire_id' => $anneePrecedente->id,
                'periode' => $periode,
                'moyenne_generale' => $moyenne,
                'note_assiduite' => 0,
            ]);
        }

        $this->noterEn($etudiant, $anneePrecedente->name, $noteBrute);

        return [$etudiant, $inscription];
    }

    private function noterEn(ESBTPEtudiant $etudiant, string $anneeNom, float $note): void
    {
        $matiere = ESBTPMatiere::factory()->create();

        // `annee_universitaire` est une colonne CHAINE, distincte de la cle
        // etrangere de l'inscription : c'est elle, et elle seule, que
        // l'analyse interroge.
        ESBTPNote::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'evaluation_id' => null,
            'classe_id' => ESBTPInscription::where('etudiant_id', $etudiant->id)->value('classe_id'),
            'annee_universitaire' => $anneeNom,
            'note' => $note,
            'is_absent' => false,
        ]);
    }

    private function annee(string $nom, string $debut, string $fin, bool $courante = false): ESBTPAnneeUniversitaire
    {
        return ESBTPAnneeUniversitaire::factory()->create([
            'name' => $nom,
            'start_date' => $debut,
            'end_date' => $fin,
            'is_current' => $courante,
        ]);
    }

    private function etudiantInscritEn(ESBTPAnneeUniversitaire $annee, ?ESBTPEtudiant $etudiant = null, ?int $anneeBts = null): ESBTPEtudiant
    {
        $etudiant ??= ESBTPEtudiant::factory()->create();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create($anneeBts === null ? [] : [
            'year' => $anneeBts,
            'name' => 'BTS '.$anneeBts,
            'libelle' => 'BTS Année '.$anneeBts,
        ]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => true,
        ]);

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        return $etudiant;
    }
}
