<?php

namespace Tests\Feature\Admissions\Concerns;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Candidatures, demandes de reinscription, creneaux et reservations, pour les
 * tests des dossiers d'admission et du poste « Aujourd'hui ».
 */
trait FabriqueDeDossiers
{
    private ESBTPAnneeUniversitaire $annee;

    private int $numero = 0;

    private function candidature(array $valeurs = []): ESBTPCandidature
    {
        $n = ++$this->numero;

        return ESBTPCandidature::forceCreate(array_merge([
            'nom' => 'CANDIDAT'.$n, 'prenoms' => 'Ama', 'date_naissance' => '2007-03-12', 'sexe' => 'M',
            'telephone' => '+22507'.sprintf('%08d', $n), 'email' => 'famille'.$n.'@exemple.ci',
            'annee_universitaire_id' => $this->annee->id, 'consentement_at' => now(), 'statut' => 'en_attente',
        ], $valeurs));
    }

    private function demande(string $nom, array $valeurs = []): ESBTPReinscriptionDemande
    {
        $etudiant = ESBTPEtudiant::factory()->create(['nom' => $nom, 'prenoms' => 'Serge', 'matricule' => 'MAT-'.(++$this->numero)]);

        return ESBTPReinscriptionDemande::forceCreate(array_merge([
            'etudiant_id' => $etudiant->id, 'annee_universitaire_id' => $this->annee->id,
            'statut' => 'en_attente', 'consentement_at' => now(),
        ], $valeurs));
    }

    private function creneau(int $dansJours, string $debut = '10:00', string $fin = '10:30'): ESBTPRdvCreneau
    {
        // Un creneau est unique par annee, jour et heure : on reprend celui qui existe.
        return ESBTPRdvCreneau::firstOrCreate([
            'annee_universitaire_id' => $this->annee->id, 'date' => Carbon::today()->addDays($dansJours)->toDateString(), 'heure_debut' => $debut.':00',
        ], ['heure_fin' => $fin.':00', 'capacite' => 10, 'ouvert' => true]);
    }

    /** @param  ESBTPCandidature|ESBTPReinscriptionDemande  $dossier */
    private function reserver(Model $dossier, ESBTPRdvCreneau $creneau, StatutReservationRdv $statut = StatutReservationRdv::Confirmee, ?Carbon $accueilli = null): ESBTPRdvReservation
    {
        $cle = $dossier instanceof ESBTPCandidature ? 'candidature_id' : 'reinscription_demande_id';

        return ESBTPRdvReservation::create([
            'creneau_id' => $creneau->id, $cle => $dossier->id, 'statut' => $statut->value,
            'nom' => $dossier->nom ?? $dossier->etudiant?->nom ?? 'FAMILLE', 'prenoms' => $dossier->prenoms ?? 'Serge',
            'telephone' => $dossier->telephone ?? '+2250700000000', 'date_naissance' => '2007-03-12',
            'accueilli_at' => $statut === StatutReservationRdv::Honoree ? ($accueilli ?? now()) : null,
        ]);
    }
}
