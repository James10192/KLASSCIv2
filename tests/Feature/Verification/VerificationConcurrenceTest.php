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
use App\Services\Verification\ConfirmationContactEcole;
use App\Services\Verification\DemarrageVerification;
use App\Services\Verification\RenvoiVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Envois croises avec un redepot, et perimetre de « Confirmer le contact ».
 * MailPulse est simule : aucun message ne part.
 */
class VerificationConcurrenceTest extends TestCase
{
    use RefreshDatabase;

    private const URL_MESSAGES = 'mailpulse.test/api/v1/messages';

    private int $jour = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.mailpulse.enabled' => true,
            'services.mailpulse.api_key' => 'cle-de-test',
            'services.mailpulse.base_url' => 'https://mailpulse.test',
        ]);
        Cache::flush();
    }

    public function test_un_renvoi_e_mail_croise_avec_un_redepot_n_ecrit_rien_sur_le_nouveau_contact(): void
    {
        Http::fake([self::URL_MESSAGES => Http::sequence()
            ->push($this->accepte('m1'), 202)
            ->whenEmpty(function () {
                ESBTPVerificationContact::query()->update(['destination' => 'nouvelle@gmail.com']);

                return Http::response($this->accepte('m2'), 202);
            })]);
        $verification = app(DemarrageVerification::class)->apresDepot($this->candidature('awa@gmail.com'));

        $this->assertNull(app(RenvoiVerification::class)->renvoyer($verification->demandeId, 'email'));

        $ligne = ESBTPVerificationContact::query()->sole();
        $this->assertSame('nouvelle@gmail.com', $ligne->destination);
        $this->assertSame('m1', $ligne->mailpulse_message_id, 'Le message parti vers l\'ancienne adresse ne s\'attache pas au nouveau contact.');
    }

    public function test_le_premier_envoi_croise_avec_un_redepot_ne_supprime_ni_ne_marque_rien(): void
    {
        Http::fake([self::URL_MESSAGES => function () {
            ESBTPVerificationContact::query()->update(['destination' => 'nouvelle@gmail.com']);

            return Http::response($this->accepte('m1'), 202);
        }]);
        $candidature = $this->candidature('awa@gmail.com');

        $this->assertNull(app(DemarrageVerification::class)->demarrer($candidature, true));

        $this->assertSame(1, ESBTPVerificationContact::query()->count(), 'La ligne appartient desormais au redepot.');
        $this->assertNull($candidature->fresh()->verification_contact, 'Ni masquee ni « impossible » : le redepot decide.');
    }

    public function test_confirmer_par_telephone_garde_l_adresse_saisie_et_reste_dans_le_dossier(): void
    {
        // Un dossier n'a qu'un rendez-vous actif : un dossier par cas.
        $candidature = $this->candidature(null, StatutVerificationContact::Expiree);
        $retenue = $this->reservation($candidature, 'saisie@gmail.com', StatutConvocationRdv::SansEmail);
        $dossierEnvoye = $this->candidature(null, StatutVerificationContact::Expiree, '+2250701020397');
        $envoyee = $this->reservation($dossierEnvoye, 'envoyee@gmail.com', StatutConvocationRdv::Envoyee);
        $dossierAppele = $this->candidature(null, StatutVerificationContact::Expiree, '+2250701020398');
        $telephone = $this->reservation($dossierAppele, 'appelee@gmail.com', StatutConvocationRdv::Telephone);
        $autre = $this->reservation($this->candidature(null, StatutVerificationContact::Expiree, '+2250701020399'), 'autre@gmail.com', StatutConvocationRdv::SansEmail);
        $agent = User::factory()->create()->id;
        $confirmer = fn (ESBTPCandidature $c) => app(ConfirmationContactEcole::class)->confirmer($c->fresh(), $c->fresh()->empreinteContact(), $agent);

        [$resultat, $replanifiees] = $confirmer($candidature);

        $this->assertSame(ConfirmationContactEcole::CONFIRME, $resultat);
        $this->assertSame(1, $replanifiees);
        $this->assertSame('saisie@gmail.com', $retenue->fresh()->email, 'Un contact confirme par telephone n\'efface pas l\'adresse saisie.');
        $this->assertSame(StatutConvocationRdv::EnAttente, $retenue->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::SansEmail, $autre->fresh()->convocation_statut, 'Un autre dossier n\'est pas touche.');

        // Une convocation deja remise, ou une famille deja appelee, ne repart pas.
        $this->assertSame([ConfirmationContactEcole::CONFIRME, 0], $confirmer($dossierEnvoye));
        $this->assertSame([ConfirmationContactEcole::CONFIRME, 0], $confirmer($dossierAppele));
        $this->assertSame(StatutConvocationRdv::Envoyee, $envoyee->fresh()->convocation_statut);
        $this->assertSame(StatutConvocationRdv::Telephone, $telephone->fresh()->convocation_statut);
    }

    public function test_une_adresse_fabriquee_en_base_ne_remplace_pas_une_adresse_valide(): void
    {
        $candidature = $this->candidature('m22-0521@esbtp.edu.ci', StatutVerificationContact::Impossible);
        $retenue = $this->reservation($candidature, 'valide@gmail.com', StatutConvocationRdv::SansEmail);

        app(ConfirmationContactEcole::class)->confirmer($candidature->fresh(), $candidature->fresh()->empreinteContact(), User::factory()->create()->id);

        $this->assertSame('valide@gmail.com', $retenue->fresh()->email);
    }

    private function candidature(?string $email, ?StatutVerificationContact $statut = null, string $telephone = '+2250701020304'): ESBTPCandidature
    {
        $candidature = ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => $telephone,
            'email' => $email, 'annee_universitaire_id' => ESBTPAnneeUniversitaire::factory()->create()->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
        if ($statut !== null) {
            $candidature->forceFill(['verification_contact' => $statut->value])->saveQuietly();
        }

        return $candidature;
    }

    private function reservation(ESBTPCandidature $candidature, string $email, StatutConvocationRdv $etat): ESBTPRdvReservation
    {
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $candidature->annee_universitaire_id, 'date' => now()->addDays(3 + $this->jour++)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, 'candidature_id' => $candidature->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => $candidature->telephone, 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => $etat, 'convocation_action' => 'confirme',
        ]);
    }

    /** @return array<string, mixed> */
    private function accepte(string $id): array
    {
        return ['dispatch' => ['state' => 'accepted', 'sms_fallback_eligible' => false], 'message' => ['id' => $id, 'status' => 'queued']];
    }
}
