<?php

namespace Tests\Feature\Documents;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\Documents\MoyennesAnnuellesDuCertificat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CertificatMoyenneAnnuelleHistoriqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SettingsHelper::setOrCreate('bulletin_semester1_weight', '1');
        SettingsHelper::setOrCreate('bulletin_semester2_weight', '2');
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '1');
    }

    public function test_une_moyenne_historique_remplit_le_certificat_sans_creer_de_notes(): void
    {
        [$user, $etudiant, $inscription] = $this->contexte();

        $bulletin = app(MoyennesAnnuellesDuCertificat::class)
            ->enregistrerHistorique($inscription, 12.75, $user);

        $this->assertSame('annuel', $bulletin->periode);
        $this->assertSame(12.75, (float) $bulletin->moyenne_generale);
        $this->assertNull($bulletin->rang);

        $inscription = $inscription->fresh(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude']);
        app(MoyennesAnnuellesDuCertificat::class)->attacher(collect([$inscription]), $etudiant->id);

        $this->assertSame(12.75, (float) $inscription->moyenne_generale_calculee);
        $this->assertSame('snapshot_annuel_historique', $inscription->moyenne_generale_source);
        $this->assertTrue($inscription->moyenne_historique_saisissable);
    }

    public function test_s1_s2_redeviennent_prioritaires_et_bloquent_l_override_manuel(): void
    {
        [$user, $etudiant, $inscription] = $this->contexte();
        $service = app(MoyennesAnnuellesDuCertificat::class);
        $service->enregistrerHistorique($inscription, 9.50, $user);

        foreach ([['semestre1', 10.0], ['semestre2', 16.0]] as [$periode, $moyenne]) {
            ESBTPBulletin::create([
                'etudiant_id' => $etudiant->id,
                'classe_id' => $inscription->classe_id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id,
                'periode' => $periode,
                'moyenne_generale' => $moyenne,
                'note_assiduite' => 0,
            ]);
        }

        $inscription = $inscription->fresh(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude']);
        $service->attacher(collect([$inscription]), $etudiant->id);

        $this->assertEqualsWithDelta(14.0, (float) $inscription->moyenne_generale_calculee, 0.001);
        $this->assertSame('bulletin_canonique', $inscription->moyenne_generale_source);
        $this->assertFalse($inscription->moyenne_historique_saisissable);

        $this->expectException(ValidationException::class);
        $service->enregistrerHistorique($inscription, 8.0, $user);
    }

    public function test_l_annee_en_cours_refuse_la_saisie_historique(): void
    {
        [$user, , $inscription] = $this->contexte(terminee: false);

        $this->expectException(ValidationException::class);
        app(MoyennesAnnuellesDuCertificat::class)->enregistrerHistorique($inscription, 13.0, $user);
    }

    /** @return array{User, ESBTPEtudiant, ESBTPInscription} */
    private function contexte(bool $terminee = true): array
    {
        $user = User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => ! $terminee,
            'start_date' => $terminee ? '2024-09-01' : today()->startOfYear(),
            'end_date' => $terminee ? '2025-07-31' : today()->addMonths(6),
        ]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create();
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ])->load(['anneeUniversitaire', 'classe', 'filiere', 'niveauEtude']);

        return [$user, $etudiant, $inscription];
    }
}
