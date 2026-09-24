<?php

namespace Tests\Feature\Emails;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use App\Services\Emails\ResolveurDns;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * Donnees et appels communs aux tests de `POST /api/cli/emails/corriger-fautes`.
 * La verification MX reste ACTIVE ; le reseau est remplace par DnsSimule.
 */
trait ConstruitCorrections
{
    private const ROUTE = '/api/cli/emails/corriger-fautes';

    private ESBTPAnneeUniversitaire $annee;

    private DnsSimule $dns;

    private int $numero = 0;

    protected function preparerCorrections(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
            \App\Http\Middleware\ThrottleRequestsParRoute::class,
        ]);
        Sanctum::actingAs(User::factory()->create(['email' => 'agent@gmail.com']), ['cli:read', 'cli:admin']);
        Cache::flush();
        Storage::fake('local');
        config(['emails_joignables.mx.actif' => true]);
        $this->dns = new DnsSimule;
        $this->app->instance(ResolveurDns::class, $this->dns);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    private function corriger(array $corrections, array $options = []): TestResponse
    {
        return $this->postJson(self::ROUTE, ['execute' => true, 'corrections' => $corrections] + $options);
    }

    /** @return array{cle: string, domaine_propose: string} */
    private function cle(string $table, int $id, string $propose = 'gmail.com', string $colonne = 'email'): array
    {
        return ['cle' => $table.':'.$colonne.':'.$id, 'domaine_propose' => $propose];
    }

    private function candidature(string $email): ESBTPCandidature
    {
        return ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => '+22507010203'.sprintf('%02d', ++$this->numero),
            'email' => $email, 'annee_universitaire_id' => $this->annee->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
    }

    /** @param  array<string, int>  $porteur  candidature_id ou reinscription_demande_id */
    private function reservation(array $porteur, string $email, ?StatutConvocationRdv $convocation = null): ESBTPRdvReservation
    {
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $this->annee->id, 'date' => now()->addDays(3 + ++$this->numero)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);

        return ESBTPRdvReservation::create($porteur + [
            'creneau_id' => $creneau->id, 'statut' => 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => '+22505010203'.sprintf('%02d', $this->numero), 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => $convocation, 'convocation_action' => 'confirme',
        ]);
    }

    /** Un etudiant et sa demande de reinscription avec rendez-vous actif, meme adresse. */
    private function etudiantAvecReservation(string $email): array
    {
        $etudiant = ESBTPEtudiant::factory()->create(['email' => $email, 'email_personnel' => null, 'user_id' => null]);
        $demande = ESBTPReinscriptionDemande::forceCreate([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $this->annee->id,
            'statut' => ESBTPReinscriptionDemande::STATUT_EN_ATTENTE, 'consentement_at' => now(),
        ]);

        return [$etudiant, $this->reservation(['reinscription_demande_id' => $demande->id], $email)];
    }
}
