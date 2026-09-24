<?php

namespace Tests\Feature\Verification;

use App\Enums\StatutConvocationRdv;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPVerificationContact;
use App\Models\User;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\Verification\ConfirmationContactEcole;
use App\Services\Verification\ControleVerification;
use App\Services\Verification\DemarrageVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Ce que devient un rendez-vous deja pris quand le contact est enfin prouve,
 * et ce que garde un dossier marque quand l'ecole coupe le reglage.
 * MailPulse est simule : aucun message ne part.
 */
class RepriseConvocationsVerifieesTest extends TestCase
{
    use ActiveLaVerificationContact;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailpulse.enabled' => true,
            'services.mailpulse.api_key' => 'cle-de-test',
            'services.mailpulse.base_url' => 'https://mailpulse.test',
        ]);
        Cache::flush();
        $this->reglerVerificationContact(true);
    }

    public function test_le_code_saisi_par_la_famille_remet_sa_convocation_retenue_en_file(): void
    {
        Http::fake(['mailpulse.test/api/v1/messages' => Http::response([
            'dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false],
            'message' => ['id' => 'msg_1', 'status' => 'queued'],
        ], 202)]);
        $candidature = $this->candidature('awa@gmail.com');
        $verification = app(DemarrageVerification::class)->apresDepot($candidature);

        // Rendez-vous pris avant la saisie du code : la convocation est retenue.
        $reservation = $this->reservation($candidature, 'awa@gmail.com');
        app(FileConvocationsRdv::class)->poser($reservation, 'confirme');
        $this->assertSame(StatutConvocationRdv::SansEmail, $reservation->fresh()->convocation_statut);

        $resultat = app(ControleVerification::class)->parCode($verification->demandeId, $this->codeEnvoye(), 'email');

        $this->assertTrue($resultat->verifie);
        $this->assertSame(StatutConvocationRdv::EnAttente, $reservation->fresh()->convocation_statut, 'La famille n\'attend pas qu\'un agent la rappelle.');
    }

    public function test_un_dossier_marque_garde_son_badge_et_se_confirme_apres_coupure_du_reglage(): void
    {
        $candidature = $this->candidature(null);
        $candidature->forceFill(['verification_contact' => StatutVerificationContact::Impossible->value])->saveQuietly();
        $reservation = $this->reservation($candidature, 'saisie@gmail.com', StatutConvocationRdv::SansEmail);

        $this->reglerVerificationContact(false);

        $this->assertStringContainsString('Contact non vérifiable', $this->badge($candidature->fresh()));
        $this->assertFalse($candidature->fresh()->contactAConfirmer(), 'Reglage coupe : plus rien n\'est retenu.');

        [$resultat, $reprises] = app(ConfirmationContactEcole::class)
            ->confirmer($candidature->fresh(), $candidature->fresh()->empreinteContact(), User::factory()->create()->id);

        $this->assertSame(ConfirmationContactEcole::CONFIRME, $resultat);
        $this->assertSame(1, $reprises);
        $this->assertSame(StatutConvocationRdv::EnAttente, $reservation->fresh()->convocation_statut);
        $this->assertSame('', trim($this->badge($candidature->fresh())), 'Contact confirme : le badge tombe.');
    }

    public function test_une_verification_whatsapp_inconnue_de_mailpulse_est_un_code_expire(): void
    {
        Http::fake([
            'mailpulse.test/api/v1/verifications' => Http::response(['id' => 'ver_1', 'status' => 'pending', 'to_masked' => '+225******04', 'expires_at' => now()->addMinutes(10)->toIso8601String()], 201),
            'mailpulse.test/api/v1/verifications/ver_1/check' => Http::response(['error' => 'verification_introuvable'], 404),
        ]);
        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature(null));

        $resultat = app(ControleVerification::class)->parCode($verification->demandeId, '123456', 'telephone');

        $this->assertSame(ControleVerification::EXPIRE, $resultat->motif);
        $this->assertSame(0, ESBTPVerificationContact::query()->sole()->tentatives, 'Un code expire n\'est pas un essai.');
    }

    private function badge(ESBTPCandidature $candidature): string
    {
        return Blade::render('<x-demande-contact-badge :demande="$d" />', ['d' => $candidature]);
    }

    private function candidature(?string $email): ESBTPCandidature
    {
        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+2250701020304',
            'email' => $email, 'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory()->create()->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    private function reservation(ESBTPCandidature $candidature, string $email, ?StatutConvocationRdv $etat = null): ESBTPRdvReservation
    {
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $candidature->annee_universitaire_id, 'date' => now()->addDays(3)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $candidature->telephone, 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => $etat, 'convocation_action' => 'confirme',
        ]);
    }

    private function codeEnvoye(): string
    {
        $texte = '';
        Http::assertSent(function (RequeteHttp $r) use (&$texte) {
            if (str_ends_with($r->url(), '/api/v1/messages')) {
                $texte = (string) ($r['content']['text'] ?? '');
            }

            return true;
        });
        preg_match('/Votre code : (\d{6})/', $texte, $m);
        $this->assertNotEmpty($m, 'Le courriel doit contenir un code a six chiffres.');

        return $m[1];
    }
}
