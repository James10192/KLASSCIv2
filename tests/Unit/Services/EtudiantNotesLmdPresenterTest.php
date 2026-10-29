<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\EtudiantNotesLmdPresenter;
use App\Services\LMD\LmdBulletinProjectionService;
use App\Services\LMDBulletinService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Sans base de donnees : les modeles sont construits en memoire (l'application
 * est amorcee pour leurs traits d'audit, mais aucune requete n'est emise) et la
 * projection (LmdBulletinProjectionService) est doublee. Le presenter ne
 * calcule aucune moyenne, il reforme celles de la projection.
 */
class EtudiantNotesLmdPresenterTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFormatNoteSansZerosInutilesEtTiretPourAbsence(): void
    {
        self::assertSame('—', EtudiantNotesLmdPresenter::formatNote(null));
        self::assertSame('15', EtudiantNotesLmdPresenter::formatNote(15.0));
        self::assertSame('13,4', EtudiantNotesLmdPresenter::formatNote(13.4));
        self::assertSame('11,55', EtudiantNotesLmdPresenter::formatNote(11.55));
        self::assertSame('0', EtudiantNotesLmdPresenter::formatNote(0.0));
    }

    public function testLibelleCourtDesTypesDEvaluation(): void
    {
        self::assertSame('CC', EtudiantNotesLmdPresenter::libelleCourt('devoir'));
        self::assertSame('Examen', EtudiantNotesLmdPresenter::libelleCourt('examen'));
        self::assertSame('Rattrapage', EtudiantNotesLmdPresenter::libelleCourt('RATTRAPAGE'));
        self::assertSame('TP', EtudiantNotesLmdPresenter::libelleCourt('tp'));
        self::assertSame('Évaluation', EtudiantNotesLmdPresenter::libelleCourt(null));
        self::assertSame('Oral', EtudiantNotesLmdPresenter::libelleCourt('oral'));
    }

    public function testPresenterSemestreRegroupeParUePuisEcueAvecCreditsEtValidation(): void
    {
        $presenter = new EtudiantNotesLmdPresenter(
            Mockery::mock(LmdBulletinProjectionService::class),
            Mockery::mock(LMDBulletinService::class)
        );

        $ueDroit = $this->ue(11, 'DRP21', 'Droit privé fondamental', 8);
        $ueLangues = $this->ue(12, 'LSH21', 'Langues et méthodologie', 4);
        $obligations = $this->matiere(101, 'DRP211', 'Droit des obligations');
        $biens = $this->matiere(102, 'DRP212', 'Droit des biens');
        $anglais = $this->matiere(201, 'LSH212', 'Anglais juridique');

        $projection = [
            'moyenne_generale' => 13.4,
            'mention_generale' => 'Assez bien',
            'credits_capitalises' => 8,
            'credits_totaux' => 12,
            'has_bulletin' => false,
            'bulletin' => null,
            'status' => 'incomplete',
            'resultats_ues' => collect([
                [
                    'unite_enseignement' => $ueDroit,
                    'unite_enseignement_id' => 11,
                    'moyenne' => 13.3,
                    'statut' => ESBTPLMDResultatUE::STATUT_AQ,
                    'mention' => 'Assez bien',
                    'credit' => 8,
                    'missing_ecues' => 0,
                    'resultats_ecues' => collect([
                        ['matiere' => $obligations, 'matiere_id' => 101, 'moyenne' => 15.0, 'credit' => 4, 'coefficient' => 2.0],
                        ['matiere' => $biens, 'matiere_id' => 102, 'moyenne' => 11.6, 'credit' => 4, 'coefficient' => 2.0],
                    ]),
                ],
                [
                    'unite_enseignement' => $ueLangues,
                    'unite_enseignement_id' => 12,
                    'moyenne' => null,
                    'statut' => ESBTPLMDResultatUE::STATUT_NAQ,
                    'mention' => null,
                    'credit' => 4,
                    'missing_ecues' => 1,
                    'resultats_ecues' => collect([
                        ['matiere' => $anglais, 'matiere_id' => 201, 'moyenne' => null, 'credit' => 4, 'coefficient' => 1.0],
                    ]),
                ],
            ]),
        ];

        $notes = collect([
            $this->note(101, 'devoir', 15.0, '2026-10-01'),
            $this->note(102, 'devoir', 11.0, '2026-10-05'),
            $this->note(102, 'examen', 12.0, '2026-12-15'),
            $this->note(101, 'examen', null, '2026-12-16', true),
        ]);

        $semestre = $presenter->presenterSemestre(3, $projection, $notes);

        self::assertSame('S3', $semestre['code']);
        self::assertSame('Semestre 3', $semestre['label']);
        self::assertSame(13.4, $semestre['moyenne']);
        self::assertSame(8, $semestre['credits_acquis']);
        self::assertSame(12, $semestre['credits_attendus']);
        self::assertNull($semestre['rang']);
        self::assertSame('incomplete', $semestre['statut']);
        self::assertSame(4, $semestre['notes_count']);
        self::assertCount(2, $semestre['ues']);

        $droit = $semestre['ues'][0];
        self::assertSame('DRP21', $droit['code']);
        self::assertSame('Droit privé fondamental', $droit['name']);
        self::assertSame(8, $droit['credit']);
        self::assertSame(13.3, $droit['moyenne']);
        self::assertSame('validee', $droit['etat']);
        self::assertSame('Validée', $droit['etat_label']);
        self::assertCount(2, $droit['ecues']);

        $obligationsPresente = $droit['ecues'][0];
        self::assertSame('DRP211', $obligationsPresente['code']);
        self::assertSame(15.0, $obligationsPresente['moyenne']);
        self::assertSame(4, $obligationsPresente['credit']);
        self::assertSame(2.0, $obligationsPresente['coefficient']);
        // Deux evaluations, triees par date : le CC puis l'examen ou l'etudiant etait absent.
        self::assertSame(['CC', 'Examen'], array_column($obligationsPresente['evaluations'], 'type_label'));
        self::assertSame(15.0, $obligationsPresente['evaluations'][0]['note']);
        self::assertNull($obligationsPresente['evaluations'][1]['note']);
        self::assertTrue($obligationsPresente['evaluations'][1]['absent']);

        $biensPresente = $droit['ecues'][1];
        self::assertSame([11.0, 12.0], array_column($biensPresente['evaluations'], 'note'));
        self::assertSame('DRP212', $biensPresente['code']);

        $langues = $semestre['ues'][1];
        self::assertSame('en_cours', $langues['etat']);
        self::assertSame('En cours', $langues['etat_label']);
        self::assertNull($langues['moyenne']);
        self::assertSame(1, $langues['ecues_manquantes']);
        self::assertSame([], $langues['ecues'][0]['evaluations']);
    }

    public function testUeCompenseeEstValideeEtNonAcquiseNeLEstPas(): void
    {
        $presenter = new EtudiantNotesLmdPresenter(
            Mockery::mock(LmdBulletinProjectionService::class),
            Mockery::mock(LMDBulletinService::class)
        );

        $projection = [
            'moyenne_generale' => 10.5,
            'credits_capitalises' => 6,
            'credits_totaux' => 6,
            'status' => 'complete',
            'resultats_ues' => collect([
                [
                    'unite_enseignement' => $this->ue(1, 'UE1', 'Compensée', 3),
                    'moyenne' => 9.0,
                    'statut' => ESBTPLMDResultatUE::STATUT_APC,
                    'credit' => 3,
                    'resultats_ecues' => collect(),
                ],
                [
                    'unite_enseignement' => $this->ue(2, 'UE2', 'Échouée', 3),
                    'moyenne' => 8.0,
                    'statut' => ESBTPLMDResultatUE::STATUT_NAQ,
                    'credit' => 3,
                    'resultats_ecues' => collect(),
                ],
            ]),
        ];

        $semestre = $presenter->presenterSemestre(1, $projection, collect());

        self::assertSame('validee', $semestre['ues'][0]['etat']);
        self::assertSame('Validée par compensation', $semestre['ues'][0]['etat_label']);
        self::assertSame('non_validee', $semestre['ues'][1]['etat']);
        self::assertSame('Non validée', $semestre['ues'][1]['etat_label']);
    }

    public function testRangEtEffectifViennentDuBulletinPersiste(): void
    {
        $presenter = new EtudiantNotesLmdPresenter(
            Mockery::mock(LmdBulletinProjectionService::class),
            Mockery::mock(LMDBulletinService::class)
        );

        $bulletin = new ESBTPLMDBulletin(['rang' => 2, 'effectif' => 38, 'semestre' => 3]);

        $semestre = $presenter->presenterSemestre(3, [
            'bulletin' => $bulletin,
            'has_bulletin' => true,
            'moyenne_generale' => 13.4,
            'resultats_ues' => collect(),
        ], collect());

        self::assertTrue($semestre['has_bulletin']);
        self::assertSame(2, $semestre['rang']);
        self::assertSame(38, $semestre['effectif']);
    }

    public function testPresenterInterrogeLaProjectionPourChaqueSemestreDuNiveauEtVentileLesNotes(): void
    {
        $projections = Mockery::mock(LmdBulletinProjectionService::class);
        $bulletins = Mockery::mock(LMDBulletinService::class);

        $niveau = new ESBTPNiveauEtude(['name' => 'Licence 2', 'type' => 'Licence', 'year' => 2]);
        $classe = new ESBTPClasse(['name' => 'L2 Droit privé A', 'systeme_academique' => 'LMD']);
        $classe->id = 7;
        $classe->setRelation('niveau', $niveau);

        $projectionVide = ['moyenne_generale' => null, 'credits_capitalises' => 0, 'credits_totaux' => 0, 'status' => 'incomplete', 'resultats_ues' => collect()];

        $projections->shouldReceive('calculerProjectionLive')->once()->with(42, 7, 3, 3)->andReturn($projectionVide);
        $projections->shouldReceive('calculerProjectionLive')->once()->with(42, 7, 3, 4)->andReturn($projectionVide);
        $bulletins->shouldReceive('getPeriodeVariants')->with(3)->andReturn(['3', 'semestre3', 'S3', 'Semestre 3', 'semestre 3']);
        $bulletins->shouldReceive('getPeriodeVariants')->with(4)->andReturn(['4', 'semestre4', 'S4', 'Semestre 4', 'semestre 4']);

        $notes = collect([
            $this->note(101, 'devoir', 12.0, '2026-10-01', false, 'semestre3'),
            $this->note(101, 'examen', 14.0, '2027-02-01', false, '4'),
            $this->note(101, 'devoir', 9.0, '2027-03-01', false, 'Semestre 4'),
        ]);

        $presenter = new EtudiantNotesLmdPresenter($projections, $bulletins);
        $resultat = $presenter->presenter($classe, 42, 3, $notes);

        self::assertSame(['S3', 'S4'], array_column($resultat['semestres'], 'code'));
        self::assertSame(1, $resultat['semestres'][0]['notes_count']);
        self::assertSame(2, $resultat['semestres'][1]['notes_count']);
    }

    private function ue(int $id, string $code, string $name, int $credit): ESBTPUniteEnseignement
    {
        $ue = new ESBTPUniteEnseignement(['code' => $code, 'name' => $name, 'credit' => $credit]);
        $ue->id = $id;

        return $ue;
    }

    private function matiere(int $id, string $code, string $name): ESBTPMatiere
    {
        $matiere = new ESBTPMatiere(['code' => $code, 'name' => $name]);
        $matiere->id = $id;

        return $matiere;
    }

    private function note(int $matiereId, string $type, ?float $valeur, string $date, bool $absent = false, string $periode = '3'): ESBTPNote
    {
        $evaluation = new ESBTPEvaluation([
            'titre' => ucfirst($type),
            'type' => $type,
            'matiere_id' => $matiereId,
            'bareme' => 20,
            'coefficient' => 1,
            'periode' => $periode,
        ]);
        $evaluation->date_evaluation = $date;

        $note = new ESBTPNote([
            'matiere_id' => $matiereId,
            'note' => $valeur,
            'is_absent' => $absent,
        ]);
        $note->setRelation('evaluation', $evaluation);

        return $note;
    }
}
