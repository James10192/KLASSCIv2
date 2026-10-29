<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\Setting;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\RendezVousReglages as R;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Un second « Placer et convoquer » ne reprend pas ce que le premier a pose.
 *
 * Avant : « deja convoque » se lisait sur `rdv_invite_at`, pose seulement apres
 * un envoi reussi. Une convocation encore en attente ou en echec etait donc
 * reprise a chaque clic : tentatives remises a zero, motif efface, et le dossier
 * recompte « place ».
 */
class PlacerDeuxFoisTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_second_passage_compte_en_deja_et_ne_remet_rien_a_zero(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $demain = now()->addDays(2)->toDateString();
        foreach ([
            R::ENABLED => '1', R::OUVERTURE => now()->toDateString(), R::FERMETURE => now()->addDays(10)->toDateString(),
            R::HEURE_DEBUT => '08:00', R::HEURE_FIN => '12:00', R::DUREE => '30', R::CAPACITE => '5', R::JOURS => '1,2,3,4,5,6,7',
            PortailCandidaturePublication::REGLAGE_PHYSIQUES => now()->toDateString(),
            PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => (string) $annee->id,
        ] as $cle => $valeur) {
            Setting::setOrCreate($cle, $valeur);
        }
        Cache::flush();

        ESBTPRdvCreneau::create(['annee_universitaire_id' => $annee->id, 'date' => $demain, 'heure_debut' => '09:00:00', 'heure_fin' => '09:30:00', 'capacite' => 5, 'ouvert' => true]);
        ESBTPCandidature::create([
            'nom' => 'KOUASSI', 'prenoms' => 'Ama', 'date_naissance' => '2007-01-01', 'telephone' => '+2250700000001',
            'email' => 'ama@exemple.ci', 'annee_universitaire_id' => $annee->id, 'consentement_at' => now(),
            'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);

        $premier = app(AffecteurDossiersRdv::class)->placer();
        $this->assertSame(1, $premier['places']);

        // L'envoi a echoue deux fois entre-temps : cette trace doit survivre.
        $reservation = ESBTPRdvReservation::firstOrFail();
        $reservation->forceFill(['convocation_tentatives' => 2, 'convocation_erreur' => 'MailPulse est injoignable.'])->save();

        $second = app(AffecteurDossiersRdv::class)->placer();

        $this->assertSame(0, $second['places']);
        $this->assertSame(1, $second['deja']);
        $reservation->refresh();
        $this->assertSame(StatutConvocationRdv::EnAttente, $reservation->convocation_statut);
        $this->assertSame(2, $reservation->convocation_tentatives);
        $this->assertSame('MailPulse est injoignable.', $reservation->convocation_erreur);
        $this->assertSame(1, ESBTPRdvReservation::count());
    }
}
