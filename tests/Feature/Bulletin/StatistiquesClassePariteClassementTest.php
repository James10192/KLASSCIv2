<?php

namespace Tests\Feature\Bulletin;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * Regression ESBTP Yamoussoukro (novembre 2026).
 *
 * Un premier du semestre ne peut pas avoir une moyenne inferieure a la
 * "plus forte moyenne" affichee sur son bulletin. Les statistiques doivent
 * venir des memes snapshots que le classement, pas d'un second calcul.
 */
class StatistiquesClassePariteClassementTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts, SeedsConfiguredBulletin;

    public static function periodes(): array
    {
        return [
            'semestre 1' => ['semestre1'],
            'semestre 2' => ['semestre2'],
        ];
    }

    /** @dataProvider periodes */
    public function test_mode_blocs_le_maximum_est_la_moyenne_du_premier(string $periode): void
    {
        $this->monterLaClasse();
        $this->configurerBlocs();

        // Le S2 du cas signale porte une classe de specialite, pas le TC S1.
        if ($periode === 'semestre2') {
            $this->filiere->forceFill(['is_tronc_commun' => false])->save();
        }

        $generale = $this->matiereConfigureePour($periode, 'general', 1);
        $technique = $this->matiereConfigureePour($periode, 'technique', 4);
        $evaluationGenerale = $this->evaluationPour($generale, $periode);
        $evaluationTechnique = $this->evaluationPour($technique, $periode);

        $premier = $this->etudiantInscrit();
        $second = $this->etudiantInscrit();
        $this->noter($premier, $evaluationGenerale, 10);
        $this->noter($premier, $evaluationTechnique, 20);
        $this->noter($second, $evaluationGenerale, 14);
        $this->noter($second, $evaluationTechnique, 14);

        $this->seedConfiguredBulletin(
            $premier->id, $this->classe->id, $this->annee->id, $periode,
            [$generale->id], [$technique->id]
        );

        $donnees = app(BulletinService::class)->genererDonneesBulletinPreview(
            $premier->id, $this->classe->id, $this->annee->id, $periode
        );
        $rang = app(RankingService::class)->calculerRangPourEtudiant(
            $premier->id, $this->classe->id, $this->annee->id, $periode
        );

        // Moyenne par blocs : (10 + 20) / 2 = 15.
        // L'ancien chemin des stats reponderait 10*1 + 20*4, soit 18.
        $this->assertSame(1, $rang['rang']);
        $this->assertEqualsWithDelta(15.0, (float) $donnees['moyenneAvecAssiduite'], 0.01);
        $this->assertEqualsWithDelta(15.0, (float) $donnees['meilleure_moyenne'], 0.01);
        $this->assertEqualsWithDelta(14.0, (float) $donnees['plus_faible_moyenne'], 0.01);
        $this->assertEqualsWithDelta(14.5, (float) $donnees['moyenne_classe'], 0.01);
    }

    public function test_moyenne_manuelle_sur_une_matiere_ne_masque_pas_les_notes_des_autres(): void
    {
        $this->monterLaClasse();
        $this->configurerBlocs();

        $generale = $this->matiereConfigureePour('semestre1', 'general', 1);
        $technique = $this->matiereConfigureePour('semestre1', 'technique', 4);
        $evaluationGenerale = $this->evaluationPour($generale, 'semestre1');
        $evaluationTechnique = $this->evaluationPour($technique, 'semestre1');

        $premier = $this->etudiantInscrit();
        $second = $this->etudiantInscrit();
        $this->noter($premier, $evaluationGenerale, 10);
        $this->noter($premier, $evaluationTechnique, 20);
        $this->noter($second, $evaluationGenerale, 14);
        $this->noter($second, $evaluationTechnique, 14);

        ESBTPResultat::create([
            'etudiant_id' => $premier->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $generale->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne' => 12,
            'coefficient' => 1,
        ]);
        $this->seedConfiguredBulletin(
            $premier->id, $this->classe->id, $this->annee->id, 'semestre1',
            [$generale->id], [$technique->id]
        );

        $donnees = app(BulletinService::class)->genererDonneesBulletinPreview(
            $premier->id, $this->classe->id, $this->annee->id, 'semestre1'
        );
        $rang = app(RankingService::class)->calculerRangPourEtudiant(
            $premier->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        // La moyenne manuelle (12) remplace seulement le 10 de la matiere
        // generale ; la matiere technique a 20 reste prise en compte.
        $this->assertSame(1, $rang['rang']);
        $this->assertEqualsWithDelta(16.0, (float) $donnees['moyenneAvecAssiduite'], 0.01);
        $this->assertEqualsWithDelta(16.0, (float) $donnees['meilleure_moyenne'], 0.01);
        $this->assertEqualsWithDelta(14.0, (float) $donnees['plus_faible_moyenne'], 0.01);
        $this->assertEqualsWithDelta(15.0, (float) $donnees['moyenne_classe'], 0.01);
    }

    public function test_zero_note_est_compte_mais_pas_un_etudiant_sans_notes(): void
    {
        $this->monterLaClasse();
        $this->configurerBlocs();

        $matiere = $this->matiereConfigureePour('semestre1', 'general', 1);
        $evaluation = $this->evaluationPour($matiere, 'semestre1');
        $premier = $this->etudiantInscrit();
        $zero = $this->etudiantInscrit();
        $sansNotes = $this->etudiantInscrit();
        $this->noter($premier, $evaluation, 12);
        $this->noter($zero, $evaluation, 0);

        $this->seedConfiguredBulletin(
            $premier->id, $this->classe->id, $this->annee->id, 'semestre1', [$matiere->id], []
        );
        $donnees = app(BulletinService::class)->genererDonneesBulletinPreview(
            $premier->id, $this->classe->id, $this->annee->id, 'semestre1'
        );
        $classement = app(RankingService::class)->calculerRangsClasse(
            $this->classe->id, $this->annee->id, 'semestre1'
        );

        $this->assertSame(2, $classement['total'], 'Un eleve sans notes ne participe pas.');
        $this->assertSame(2, $classement['rows']->firstWhere('etudiant_id', $zero->id)['rang']);
        $this->assertNull($classement['rows']->firstWhere('etudiant_id', $sansNotes->id)['rang']);
        $this->assertEqualsWithDelta(12.0, (float) $donnees['meilleure_moyenne'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $donnees['plus_faible_moyenne'], 0.01);
        $this->assertEqualsWithDelta(6.0, (float) $donnees['moyenne_classe'], 0.01);
    }

    public function test_assiduite_active_s_applique_aux_stats_comme_au_classement(): void
    {
        $this->monterLaClasse();
        $this->configurerBlocs(true);

        $matiere = $this->matiereConfigureePour('semestre1', 'general', 1);
        $evaluation = $this->evaluationPour($matiere, 'semestre1');
        $premier = $this->etudiantInscrit();
        $second = $this->etudiantInscrit();
        $this->noter($premier, $evaluation, 14);
        $this->noter($second, $evaluation, 10);

        $this->seedConfiguredBulletin(
            $premier->id, $this->classe->id, $this->annee->id, 'semestre1', [$matiere->id], []
        );
        $donnees = app(BulletinService::class)->genererDonneesBulletinPreview(
            $premier->id, $this->classe->id, $this->annee->id, 'semestre1'
        );

        $this->assertEqualsWithDelta(14.13, (float) $donnees['moyenneAvecAssiduite'], 0.01);
        $this->assertEqualsWithDelta(14.13, (float) $donnees['meilleure_moyenne'], 0.01);
        $this->assertEqualsWithDelta(10.13, (float) $donnees['plus_faible_moyenne'], 0.01);
        $this->assertEqualsWithDelta(12.13, (float) $donnees['moyenne_classe'], 0.01);
    }

    private function configurerBlocs(bool $avecAssiduite = false): void
    {
        SettingsHelper::setOrCreate('bulletin_moyenne_mode', 'blocs');
        SettingsHelper::setOrCreate('bulletin_bloc_general_coef', '1');
        SettingsHelper::setOrCreate('bulletin_bloc_professionnel_coef', '1');
        SettingsHelper::setOrCreate('bulletin_show_attendance_note', $avecAssiduite ? '1' : '0');
        SettingsHelper::setOrCreate('attendance_note_zero_unjustified', '0.13');
    }

    private function matiereConfigureePour(string $periode, string $type, int $coefficient): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => $periode,
            'config' => ['type' => $type, 'coefficient' => $coefficient],
        ]);
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => $periode,
            'coefficient' => $coefficient,
        ]);

        return $matiere;
    }

    private function evaluationPour(ESBTPMatiere $matiere, string $periode): ESBTPEvaluation
    {
        return ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => $periode,
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
    }
}
