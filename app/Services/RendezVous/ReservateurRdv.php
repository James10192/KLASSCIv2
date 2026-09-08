<?php

namespace App\Services\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Support\SeauDeDebit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservateurRdv
{
    public const DEBIT_MAX = 5;

    public const DEBIT_FENETRE_SECONDES = 900;

    public function __construct(
        private readonly ReferencePublique $references,
        private readonly RendezVousReglages $reglages,
        private readonly CatalogueCreneaux $catalogue,
    ) {
    }

    public static function seauParReference(string $reference): SeauDeDebit
    {
        return SeauDeDebit::parIdentifiant(
            'rdv-ref:'.hash('sha256', Str::upper(trim($reference))),
            self::DEBIT_MAX,
            self::DEBIT_FENETRE_SECONDES
        );
    }

    public static function seauParIdentifiant(string $identifiant): SeauDeDebit
    {
        return SeauDeDebit::parIdentifiant(
            'rdv-id:'.hash('sha256', Str::lower(trim($identifiant))),
            self::DEBIT_MAX,
            self::DEBIT_FENETRE_SECONDES
        );
    }

    /**
     * @return array{ok: true, reservation: ESBTPRdvReservation}|array{ok: false, code: string, creneaux?: list<array<string, mixed>>}
     */
    public function reserver(string $reference, string $dateNaissance, int $creneauId): array
    {
        $porteur = $this->porteurConcordant($reference, $dateNaissance);
        if ($porteur === null) {
            return ['ok' => false, 'code' => 'introuvable'];
        }

        return DB::transaction(function () use ($porteur, $creneauId) {
            $creneau = ESBTPRdvCreneau::query()->whereKey($creneauId)->lockForUpdate()->first();
            if ($creneau === null || ! $creneau->ouvert) {
                return ['ok' => false, 'code' => 'ferme', 'creneaux' => $this->catalogue->publier()];
            }

            if ($this->tropTot($creneau) || $this->dejaCommence($creneau)) {
                return ['ok' => false, 'code' => 'trop_tot', 'creneaux' => $this->catalogue->publier()];
            }

            $existante = $this->reservationActiveDu($porteur);
            if ($existante !== null) {
                return ['ok' => false, 'code' => 'deja_reserve'];
            }

            if ($creneau->placesPrises() >= $creneau->capacite) {
                return ['ok' => false, 'code' => 'complet', 'creneaux' => $this->catalogue->publier()];
            }

            $snapshot = $this->snapshot($porteur);
            $reservation = ESBTPRdvReservation::create($snapshot + [
                'creneau_id' => $creneau->id,
                'candidature_id' => $porteur instanceof ESBTPCandidature ? $porteur->id : null,
                'reinscription_demande_id' => $porteur instanceof ESBTPReinscriptionDemande ? $porteur->id : null,
                'statut' => StatutReservationRdv::Confirmee->value,
            ]);

            return ['ok' => true, 'reservation' => $reservation->load('creneau')];
        });
    }

    /**
     * @return array{ok: true, reservation: ESBTPRdvReservation}|array{ok: false, code: string, creneaux?: list<array<string, mixed>>}
     */
    public function deplacer(string $reference, string $dateNaissance, int $creneauId): array
    {
        $porteur = $this->porteurConcordant($reference, $dateNaissance);
        if ($porteur === null) {
            return ['ok' => false, 'code' => 'introuvable'];
        }

        return DB::transaction(function () use ($porteur, $creneauId) {
            $actuelle = $this->reservationActiveDu($porteur);
            if ($actuelle === null) {
                return ['ok' => false, 'code' => 'introuvable'];
            }

            if (! $this->peutModifier($actuelle)) {
                return ['ok' => false, 'code' => 'trop_tard'];
            }

            $cible = ESBTPRdvCreneau::query()->whereKey($creneauId)->lockForUpdate()->first();
            if ($cible === null || ! $cible->ouvert) {
                return ['ok' => false, 'code' => 'ferme', 'creneaux' => $this->catalogue->publier()];
            }

            if ($this->tropTot($cible) || $this->dejaCommence($cible)) {
                return ['ok' => false, 'code' => 'trop_tot', 'creneaux' => $this->catalogue->publier()];
            }

            if ((int) $cible->id !== (int) $actuelle->creneau_id && $cible->placesPrises() >= $cible->capacite) {
                return ['ok' => false, 'code' => 'complet', 'creneaux' => $this->catalogue->publier()];
            }

            $actuelle->update(['creneau_id' => $cible->id]);

            return ['ok' => true, 'reservation' => $actuelle->fresh()->load('creneau')];
        });
    }

    /**
     * @return array{ok: true}|array{ok: false, code: string}
     */
    public function annuler(string $reference, string $dateNaissance): array
    {
        $porteur = $this->porteurConcordant($reference, $dateNaissance);
        if ($porteur === null) {
            return ['ok' => false, 'code' => 'introuvable'];
        }

        $actuelle = $this->reservationActiveDu($porteur);
        if ($actuelle === null) {
            return ['ok' => false, 'code' => 'introuvable'];
        }

        if (! $this->peutModifier($actuelle)) {
            return ['ok' => false, 'code' => 'trop_tard'];
        }

        $actuelle->update(['statut' => StatutReservationRdv::Annulee]);

        return ['ok' => true, 'reservation' => $actuelle->load('creneau')];
    }

    public function consulter(string $reference, string $dateNaissance): ?ESBTPRdvReservation
    {
        $porteur = $this->porteurConcordant($reference, $dateNaissance);
        if ($porteur === null) {
            return null;
        }

        return $this->reservationActiveDu($porteur)?->load('creneau');
    }

    public function retrouver(string $identifiant, string $dateNaissance): ?string
    {
        $identifiant = trim($identifiant);
        $naissance = $this->dateIso($dateNaissance);
        if ($identifiant === '' || $naissance === null) {
            return null;
        }

        $candidature = ESBTPCandidature::query()
            ->where('telephone', $identifiant)
            ->whereDate('date_naissance', $naissance)
            ->first();

        if ($candidature !== null) {
            return $this->references->assurerCandidature($candidature);
        }

        $demande = ESBTPReinscriptionDemande::query()
            ->whereHas('etudiant', function ($q) use ($identifiant, $naissance) {
                $q->where(function ($inner) use ($identifiant) {
                    $inner->where('matricule', $identifiant)
                        ->orWhere('telephone', $identifiant);
                })->whereDate('date_naissance', $naissance);
            })
            ->first();

        return $demande === null ? null : $this->references->assurerDemande($demande);
    }

    public function peutModifier(ESBTPRdvReservation $reservation): bool
    {
        $creneau = $reservation->creneau;
        if ($creneau === null) {
            return false;
        }

        $debut = $this->debutDuCreneau($creneau);
        $heures = (int) $this->reglages->valeur(RendezVousReglages::DELAI_MODIF, '12');

        return Carbon::now()->addHours(max(0, $heures))->lt($debut);
    }

    public function porteurConcordant(string $reference, string $dateNaissance): ESBTPCandidature|ESBTPReinscriptionDemande|null
    {
        $cle = $this->references->normaliser($reference);
        $naissance = $this->dateIso($dateNaissance);
        if ($cle === '' || $naissance === null) {
            return null;
        }

        $candidature = ESBTPCandidature::query()->where('reference_publique', $cle)->first();
        if ($candidature !== null) {
            $dob = $candidature->date_naissance;
            $dob = $dob instanceof \DateTimeInterface ? $dob->format('Y-m-d') : (string) $dob;

            return $dob === $naissance ? $candidature : null;
        }

        $demande = ESBTPReinscriptionDemande::query()
            ->where('reference_publique', $cle)
            ->with('etudiant')
            ->first();

        if ($demande?->etudiant === null) {
            return null;
        }

        $dob = $demande->etudiant->date_naissance;
        $dob = $dob instanceof \DateTimeInterface ? $dob->format('Y-m-d') : (string) $dob;

        return $dob === $naissance ? $demande : null;
    }

    private function reservationActiveDu(ESBTPCandidature|ESBTPReinscriptionDemande $porteur): ?ESBTPRdvReservation
    {
        $colonne = $porteur instanceof ESBTPCandidature ? 'candidature_id' : 'reinscription_demande_id';

        return ESBTPRdvReservation::query()
            ->occupantes()
            ->where($colonne, $porteur->id)
            ->first();
    }

    /**
     * @return array{nom: string, prenoms: string, telephone: string, date_naissance: string, email: ?string}
     */
    private function snapshot(ESBTPCandidature|ESBTPReinscriptionDemande $porteur): array
    {
        if ($porteur instanceof ESBTPCandidature) {
            $dob = $porteur->date_naissance;

            return [
                'nom' => (string) $porteur->nom,
                'prenoms' => (string) $porteur->prenoms,
                'telephone' => (string) $porteur->telephone,
                'date_naissance' => $dob instanceof \DateTimeInterface ? $dob->format('Y-m-d') : (string) $dob,
                'email' => $porteur->email,
            ];
        }

        $etudiant = $porteur->etudiant ?? ESBTPEtudiant::query()->find($porteur->etudiant_id);
        $dob = $etudiant?->date_naissance;

        return [
            'nom' => (string) ($etudiant->nom ?? ''),
            'prenoms' => (string) ($etudiant->prenoms ?? ''),
            'telephone' => (string) ($etudiant->telephone ?? ''),
            'date_naissance' => $dob instanceof \DateTimeInterface ? $dob->format('Y-m-d') : (string) $dob,
            'email' => $etudiant->email ?? null,
        ];
    }

    private function tropTot(ESBTPRdvCreneau $creneau): bool
    {
        $heures = (int) $this->reglages->valeur(RendezVousReglages::DELAI_MIN, '12');

        return Carbon::now()->addHours(max(0, $heures))->gt($this->debutDuCreneau($creneau));
    }

    private function dejaCommence(ESBTPRdvCreneau $creneau): bool
    {
        return Carbon::now()->gte($this->debutDuCreneau($creneau));
    }

    private function debutDuCreneau(ESBTPRdvCreneau $creneau): Carbon
    {
        return Carbon::parse($creneau->date->toDateString().' '.$creneau->heureDebutHi().':00');
    }

    private function dateIso(string $valeur): ?string
    {
        $date = \App\Services\Reinscription\PortailReinscriptionService::interpreterDateIso(trim($valeur));

        return $date?->toDateString();
    }
}
