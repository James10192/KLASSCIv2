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
use App\Services\RendezVous\FamillesAPrevenirRdv;
use App\Services\RendezVous\RendezVousReglages as R;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Un dossier sans e-mail est place lui aussi, et part dans la liste d'appel.
 *
 * Avant : il n'etait pas place du tout. Seul le compte-rendu du bouton le
 * mentionnait (« N sans e-mail »), et aucun ecran ne permettait ensuite de
 * savoir quelles familles attendaient encore un rendez-vous.
 */
class PlacerSansEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_dossier_sans_email_est_place_et_devient_a_prevenir(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        foreach ([
            R::ENABLED => '1', R::OUVERTURE => now()->toDateString(), R::FERMETURE => now()->addDays(10)->toDateString(),
            R::HEURE_DEBUT => '08:00', R::HEURE_FIN => '12:00', R::DUREE => '30', R::CAPACITE => '5', R::JOURS => '1,2,3,4,5,6,7',
            PortailCandidaturePublication::REGLAGE_PHYSIQUES => now()->toDateString(),
            PortailReinscriptionService::REGLAGE_ANNEE_CIBLE => (string) $annee->id,
        ] as $cle => $valeur) {
            Setting::setOrCreate($cle, $valeur);
        }
        Cache::flush();

        ESBTPRdvCreneau::create(['annee_universitaire_id' => $annee->id, 'date' => now()->addDays(2)->toDateString(), 'heure_debut' => '09:00:00', 'heure_fin' => '09:30:00', 'capacite' => 5, 'ouvert' => true]);
        foreach ([['AVEC', 'avec@exemple.ci', '+2250700000001'], ['SANS', null, '+2250700000002']] as [$nom, $email, $tel]) {
            ESBTPCandidature::create([
                'nom' => $nom, 'prenoms' => 'Ama', 'date_naissance' => '2007-01-01', 'telephone' => $tel,
                'email' => $email, 'annee_universitaire_id' => $annee->id, 'consentement_at' => now(),
                'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
            ]);
        }

        $rapport = app(AffecteurDossiersRdv::class)->placer();

        $this->assertSame(2, $rapport['places']);
        $this->assertSame(1, $rapport['a_prevenir'], 'Inclus dans places.');
        $sans = ESBTPRdvReservation::where('nom', 'SANS')->sole();
        $this->assertSame(StatutConvocationRdv::SansEmail, $sans->convocation_statut);
        $this->assertSame(StatutConvocationRdv::EnAttente, ESBTPRdvReservation::where('nom', 'AVEC')->sole()->convocation_statut);
        $this->assertSame(['SANS'], array_column(app(FamillesAPrevenirRdv::class)->lignes(), 'nom'));

        $second = app(AffecteurDossiersRdv::class)->placer();
        $this->assertSame([0, 2], [$second['places'], $second['deja']], 'Un second passage ne replace personne.');
    }
}
