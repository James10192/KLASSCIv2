<?php

namespace Tests\Feature\RendezVous;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/** Reservations « d'avant le suivi » et courriels extraits de MailPulse, pour les tests du rattrapage. */
trait ConstruitRattrapage
{
    private ESBTPAnneeUniversitaire $annee;

    private int $numero = 0;

    protected function preparerRattrapage(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Sanctum::actingAs(User::factory()->create(), ['cli:read', 'cli:admin']);
        Cache::flush();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    private function rattraper(bool $executer, array $messages): TestResponse
    {
        return $this->postJson('/api/cli/rendez-vous/rattrapage-convocations', ['execute' => $executer, 'messages' => $messages]);
    }

    /** @return array<string, mixed> */
    private function message(?string $reference, string $id, ?string $destinataire, string $envoyeAt, string $action = 'confirme'): array
    {
        return [
            'reference' => $reference, 'message_id' => $id, 'envoye_at' => $envoyeAt,
            'destinataire_sha256' => $destinataire === null ? null : hash('sha256', mb_strtolower(trim($destinataire))),
            'destinataire_domaine' => $destinataire === null ? 'gmail.com' : substr(strrchr($destinataire, '@'), 1),
            'action' => $action,
        ];
    }

    private function reference(ESBTPRdvReservation $r): string
    {
        // Telle que la famille la voit dans le lien du courriel : XXXX-XXXX-XXXX.
        return (string) ($r->candidature ?? $r->demande)->referencePubliqueAffichee();
    }

    /**
     * Comme la migration du suivi : « envoyee », sans identifiant ni date.
     *
     * @param  array<string, mixed>  $options  invite_le, cree_le, annee, candidature, statut, action
     */
    private function reservation(?string $email, array $options = []): ESBTPRdvReservation
    {
        $annee = $options['annee'] ?? $this->annee;
        $n = ++$this->numero;
        $telephone = '+22507010203'.sprintf('%02d', $n);
        $candidature = $options['candidature'] ?? ESBTPCandidature::create([
            'nom' => 'KONE', 'prenoms' => 'Awa', 'date_naissance' => '2007-01-01', 'telephone' => $telephone,
            'email' => $email === null ? null : trim($email), 'annee_universitaire_id' => $annee->id,
            'consentement_at' => now(), 'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
        ]);
        if (array_key_exists('invite_le', $options)) {
            $candidature->forceFill(['rdv_invite_at' => Carbon::parse($options['invite_le'], 'UTC')])->saveQuietly();
        }

        return $this->reservationPour(['candidature_id' => $candidature->id], $email, $annee, $options);
    }

    private function reservationDeReinscription(string $email): ESBTPRdvReservation
    {
        $demande = ESBTPReinscriptionDemande::forceCreate([
            'etudiant_id' => ESBTPEtudiant::factory()->create()->id, 'annee_universitaire_id' => $this->annee->id,
            'statut' => ESBTPReinscriptionDemande::STATUT_EN_ATTENTE, 'consentement_at' => now(),
        ]);

        return $this->reservationPour(['reinscription_demande_id' => $demande->id], $email, $this->annee, []);
    }

    /** @param  array<string, int>  $porteur */
    private function reservationPour(array $porteur, ?string $email, ESBTPAnneeUniversitaire $annee, array $options): ESBTPRdvReservation
    {
        $n = ++$this->numero;
        $creneau = ESBTPRdvCreneau::create([
            'annee_universitaire_id' => $annee->id, 'date' => now()->addDays(3 + $n)->toDateString(),
            'heure_debut' => '09:00:00', 'heure_fin' => '09:40:00', 'capacite' => 10, 'ouvert' => true,
        ]);
        $reservation = ESBTPRdvReservation::create($porteur + [
            'creneau_id' => $creneau->id, 'statut' => $options['statut'] ?? 'confirmee',
            'nom' => 'KONE', 'prenoms' => 'Awa', 'telephone' => '+22505010203'.sprintf('%02d', $n), 'date_naissance' => '2007-01-01',
            'email' => $email, 'convocation_statut' => StatutConvocationRdv::Envoyee, 'convocation_action' => $options['action'] ?? 'confirme',
        ]);
        // Reservations d'avant les courriels du jeu d'essai (9 au 20 septembre).
        $reservation->forceFill(['created_at' => Carbon::parse($options['cree_le'] ?? '2026-09-05 08:00:00', 'UTC')])->saveQuietly();

        return $reservation->fresh();
    }
}
