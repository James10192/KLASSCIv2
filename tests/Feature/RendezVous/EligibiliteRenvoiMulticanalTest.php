<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\Renvoi\EligibiliteRenvoi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EligibiliteRenvoiMulticanalTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    public function test_un_dossier_portail_sans_email_joignable_peut_etre_renvoye_sur_whatsapp(): void
    {
        $reservation = $this->reservation('ama@gmai.com', '+2250701020304');

        $this->assertNull(app(EligibiliteRenvoi::class)->raison($reservation));
    }

    public function test_sans_email_ni_numero_whatsapp_joignable_le_renvoi_reste_refuse(): void
    {
        $reservation = $this->reservation('ama@gmai.com', 'numero-invalide');

        $this->assertSame(
            EligibiliteRenvoi::ADRESSE,
            app(EligibiliteRenvoi::class)->raison($reservation)
        );
    }

    private function reservation(string $email, string $telephone): ESBTPRdvReservation
    {
        $candidature = ESBTPCandidature::create([
            'nom' => 'KONE',
            'prenoms' => 'Awa',
            'date_naissance' => '2007-01-01',
            'telephone' => $telephone,
            'email' => $email,
            'annee_universitaire_id' => $this->annee->id,
            'consentement_at' => now(),
            'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee->id,
            'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00',
            'heure_fin' => '09:30:00',
            'capacite' => 10,
            'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id,
            'candidature_id' => $candidature->id,
            'statut' => 'confirmee',
            'nom' => 'KONE',
            'prenoms' => 'Awa',
            'telephone' => $telephone,
            'date_naissance' => '2007-01-01',
            'email' => $email,
            'convocation_statut' => StatutConvocationRdv::Envoyee,
            'convocation_action' => 'confirme',
            'convocation_envoyee_at' => now()->subDay(),
        ]);
    }
}
