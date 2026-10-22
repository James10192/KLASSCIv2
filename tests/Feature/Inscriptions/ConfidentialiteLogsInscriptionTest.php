<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPEtudiant;
use App\Services\ESBTPInscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ConfidentialiteLogsInscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_echec_creation_ne_journalise_jamais_identite_parent_paiement_ou_trace_brute(): void
    {
        $secretEnfant = 'NOM_ELEVE_CONFIDENTIEL_CI';
        $secretParent = 'TELEPHONE_PARENT_SECRET_CI';
        $secretPaiement = 'REFERENCE_BANQUE_SECRET_CI';
        $journal = null;

        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) use (&$journal) {
            $journal = json_encode([$message, $context], JSON_UNESCAPED_UNICODE);
            return $message === 'Echec de création inscription';
        });

        try {
            // Prénom absent -> échec AVANT écriture : le service doit encore
            // journaliser l'action sans écrire aucun des champs fournis.
            app(ESBTPInscriptionService::class)->createInscription(
                ['nom' => $secretEnfant, 'telephone' => '0766666666'],
                ['classe_id' => 999999, 'motif' => $secretEnfant],
                [['telephone' => $secretParent, 'email' => 'private@example.invalid']],
                ['reference_paiement' => $secretPaiement],
                101
            );
            $this->fail('Les données incomplètes auraient dû être refusées.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('informations de base', $e->getMessage());
        }

        $this->assertNotNull($journal);
        foreach ([$secretEnfant, $secretParent, $secretPaiement, 'private@example.invalid', '0766666666'] as $secret) {
            $this->assertStringNotContainsString($secret, $journal);
        }
        $this->assertStringContainsString('exception_type', $journal);
        $this->assertStringContainsString('reference', $journal);
        $this->assertStringNotContainsString('etudiantData', $journal);
        $this->assertStringNotContainsString('parentsData', $journal);
        $this->assertStringNotContainsString('trace', $journal);
    }

    public function test_parent_incomplet_et_parent_inconnu_ne_journalisent_aucune_donnee_familiale(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $journal = [];
        Log::shouldReceive('warning')->once()->withArgs(function ($message, $context) use (&$journal) {
            $journal[] = json_encode([$message, $context], JSON_UNESCAPED_UNICODE);
            return $message === 'Parent incomplet ignoré durant inscription';
        });
        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) use (&$journal) {
            $journal[] = json_encode([$message, $context], JSON_UNESCAPED_UNICODE);
            return $message === 'Echec du rattachement parent durant inscription';
        });

        $ref = new \ReflectionMethod(ESBTPInscriptionService::class, 'attachParentsToEtudiant');
        $ref->invoke(app(ESBTPInscriptionService::class), $etudiant, [
            ['telephone' => 'CONTACT_PRIVE_FAMILLE_A'],
            ['parent_id' => 99999999, 'nom' => 'NOM_PRIVE_FAMILLE_B', 'email' => 'famille@example.invalid'],
        ], 101);

        $this->assertCount(2, $journal);
        $logs = implode("\n", $journal);
        foreach (['CONTACT_PRIVE_FAMILLE_A', 'NOM_PRIVE_FAMILLE_B', 'famille@example.invalid', 'parentData'] as $secret) {
            $this->assertStringNotContainsString($secret, $logs);
        }
        $this->assertStringContainsString('parent_index', $logs);
        $this->assertStringContainsString('exception_type', $logs);
    }
}
