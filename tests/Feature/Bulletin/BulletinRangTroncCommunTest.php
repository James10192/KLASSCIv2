<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * c5 — Rang TC-aware réconcilié modèle + service via BtsBulletinCohortResolver.
 *
 * Pour un étudiant orienté (Tronc Commun S1 → Spécialité S2) :
 *  - bulletin S1 (classe = spécialité) → rang/effectif calculés dans la cohorte de
 *    la classe TC qui portait réellement les notes du S1 ;
 *  - bulletin S2 / annuel → cohorte = classe du bulletin (spécialité), inchangé ;
 *  - étudiant BTS pur (sans orientation) → cohorte = classe du bulletin, inchangé.
 *
 * Les DEUX chemins de calcul du rang doivent être réconciliés :
 *  - ESBTPBulletin::calculerRang (modèle, appelé par la génération controller) ;
 *  - BulletinService::calculerRang (service, preview/regen).
 *
 * BTS uniquement (LMD intouché). RefreshDatabase, DB klassci_testing.
 */
class BulletinRangTroncCommunTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function model_calculer_rang_uses_tronc_commun_cohort_for_s1_oriented_student(): void
    {
        $ctx = $this->makeOrientedContext();

        // Cohorte TC : 2 étudiants à 16 et 14 sur la classe TC, période S1.
        $this->makeBulletin($ctx['tcClasse']->id, $ctx['annee']->id, 'semestre1', 16.00);
        $this->makeBulletin($ctx['tcClasse']->id, $ctx['annee']->id, 'semestre1', 14.00);

        // Bulletin S1 de l'étudiant orienté, porté sur la classe de SPÉCIALITÉ, à 15.
        $bulletin = $this->makeBulletin(
            $ctx['specClasse']->id,
            $ctx['annee']->id,
            'semestre1',
            15.00,
            $ctx['etudiant']->id
        );

        $bulletin->calculerRang();

        // 1 bulletin TC strictement au-dessus (16) → rang 2 dans la cohorte TC.
        $this->assertSame(2, (int) $bulletin->rang);
    }

    /** @test */
    public function service_calculer_rang_uses_tronc_commun_cohort_for_s1_oriented_student(): void
    {
        $ctx = $this->makeOrientedContext();

        $this->makeBulletin($ctx['tcClasse']->id, $ctx['annee']->id, 'semestre1', 16.00);
        $this->makeBulletin($ctx['tcClasse']->id, $ctx['annee']->id, 'semestre1', 14.00);

        $bulletin = $this->makeBulletin(
            $ctx['specClasse']->id,
            $ctx['annee']->id,
            'semestre1',
            15.00,
            $ctx['etudiant']->id
        );

        app(BulletinService::class)->calculerRang($bulletin);

        // Service et modèle réconciliés : même cohorte TC, même rang 2.
        $this->assertSame(2, (int) $bulletin->rang);
    }

    /** @test */
    public function s2_bulletin_keeps_specialite_cohort(): void
    {
        $ctx = $this->makeOrientedContext();

        // Cohorte spécialité (S2) : un seul autre bulletin à 12.
        $this->makeBulletin($ctx['specClasse']->id, $ctx['annee']->id, 'semestre2', 12.00);

        $bulletin = $this->makeBulletin(
            $ctx['specClasse']->id,
            $ctx['annee']->id,
            'semestre2',
            18.00,
            $ctx['etudiant']->id
        );

        $bulletin->calculerRang();

        // S2 → cohorte = classe du bulletin (spécialité) → 1er sur 18.
        $this->assertSame(1, (int) $bulletin->rang);
    }

    /** @test */
    public function pure_bts_student_rank_unchanged(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        // Deux autres bulletins S1 dans la même classe.
        $this->makeBulletin($classe->id, $annee->id, 'semestre1', 17.00);
        $this->makeBulletin($classe->id, $annee->id, 'semestre1', 10.00);

        $bulletin = $this->makeBulletin($classe->id, $annee->id, 'semestre1', 13.00, $etudiant->id);

        $bulletin->calculerRang();

        // 1 bulletin au-dessus (17) → rang 2, cohorte = sa propre classe.
        $this->assertSame(2, (int) $bulletin->rang);
    }

    /**
     * Crée un contexte orienté (phases tronc_commun S1 + specialisation S2).
     *
     * @return array{annee: ESBTPAnneeUniversitaire, etudiant: ESBTPEtudiant, tcClasse: ESBTPClasse, specClasse: ESBTPClasse}
     */
    private function makeOrientedContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $specClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specClasse->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return [
            'annee' => $annee,
            'etudiant' => $etudiant,
            'tcClasse' => $tcClasse,
            'specClasse' => $specClasse,
        ];
    }

    private function makeBulletin(
        int $classeId,
        int $anneeId,
        string $periode,
        float $moyenne,
        ?int $etudiantId = null
    ): ESBTPBulletin {
        $attributes = [
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeId,
            'periode' => $periode,
            'moyenne_generale' => $moyenne,
        ];

        if ($etudiantId !== null) {
            $attributes['etudiant_id'] = $etudiantId;
        }

        return ESBTPBulletin::factory()->create($attributes);
    }
}
