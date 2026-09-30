<?php

namespace Tests\Feature\Admissions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\Setting;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManagedInscriptionSequenceTest extends TestCase
{
    use RefreshDatabase;

    private InscriptionWorkflowSettings $settings;
    private ManagedInscriptionSequence $sequence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(InscriptionWorkflowSettings::class);
        $this->settings->ensureDefaults();
        $this->sequence = app(ManagedInscriptionSequence::class);

        Setting::setOrCreate(InscriptionWorkflowSettings::KEY_ENABLED, '1', 'inscriptions', 'boolean');
    }

    /** @test */
    public function caisse_avant_pieces_interdit_le_controle_documentaire_avant_paiement(): void
    {
        Setting::setOrCreate(
            InscriptionWorkflowSettings::KEY_MODE,
            InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES,
            'inscriptions',
        );

        $workflow = new ESBTPCandidatureWorkflow();

        try {
            $this->sequence->assertDocumentsAllowed($workflow);
            $this->fail('Le controle des pieces aurait du etre refuse avant le paiement.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('workflow', $e->errors());
        }

        $workflow->paid_at = now();
        $workflow->paiement_id = 123;
        $this->sequence->assertDocumentsAllowed($workflow);
        $this->assertTrue(true);
    }

    /** @test */
    public function pieces_avant_caisse_interdit_le_paiement_avant_validation_documentaire(): void
    {
        Setting::setOrCreate(
            InscriptionWorkflowSettings::KEY_MODE,
            InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE,
            'inscriptions',
        );

        $workflow = new ESBTPCandidatureWorkflow();

        try {
            $this->sequence->assertPaymentAllowed($workflow);
            $this->fail('Le paiement aurait du etre refuse avant la validation des pieces.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('workflow', $e->errors());
        }

        $workflow->documents_validated_at = now();
        $this->sequence->assertPaymentAllowed($workflow);
        $this->assertTrue(true);
    }

    /** @test */
    public function en_mode_pieces_avant_caisse_la_file_ne_montre_que_les_dossiers_documentaires_valides(): void
    {
        Setting::setOrCreate(
            InscriptionWorkflowSettings::KEY_MODE,
            InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE,
            'inscriptions',
        );

        $candidature = $this->candidatureAcceptee();

        $this->assertCount(0, $this->sequence->cashierQueue());

        ESBTPCandidatureWorkflow::create([
            'candidature_id' => $candidature->id,
            'state' => ESBTPCandidatureWorkflow::STATE_AWAITING_PAYMENT,
            'documents_validated_at' => now(),
        ]);

        $queue = $this->sequence->cashierQueue();
        $this->assertCount(1, $queue);
        $this->assertSame($candidature->id, $queue->first()->id);
    }

    private function candidatureAcceptee(): ESBTPCandidature
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        return ESBTPCandidature::forceCreate([
            'reference' => 'CAND-SEQ-'.uniqid(),
            'statut' => ESBTPCandidature::STATUT_ACCEPTEE,
            'nom' => 'KOUASSI',
            'prenoms' => 'Awa',
            'email' => 'awa.'.uniqid().'@example.test',
            'telephone' => '+2250700000000',
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'traite_at' => now(),
        ]);
    }
}
