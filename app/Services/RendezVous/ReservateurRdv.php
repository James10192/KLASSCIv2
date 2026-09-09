<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Services\Portail\ReferencePublique;
use App\Support\IdentitePersonne;
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

        return $this->sousVerrou($porteur, function (PorteurDeRendezVous $porteur) use ($creneauId) {
            if ($this->reservationActiveDu($porteur, true) !== null) {
                return ['ok' => false, 'code' => 'deja_reserve'];
            }

            $creneau = $this->prendreCreneau($creneauId);
            if (! $creneau instanceof ESBTPRdvCreneau) {
                return $creneau;
            }

            $reservation = ESBTPRdvReservation::create($porteur->snapshotRdv() + $porteur->clesReservationRdv() + [
                'creneau_id' => $creneau->id,
                'statut' => StatutReservationRdv::Confirmee->value,
            ]);

            return ['ok' => true, 'reservation' => $reservation->load('creneau')];
        });
    }

    /**
     * @return array{ok: true, reservation: ESBTPRdvReservation}|array{ok: false, code: string}
     */
    public function placer(PorteurDeRendezVous $porteur, int $creneauId): array
    {
        return $this->sousVerrou($porteur, function (PorteurDeRendezVous $porteur) use ($creneauId) {
            if ($this->reservationActiveDu($porteur, true) !== null) {
                return ['ok' => false, 'code' => 'deja_reserve'];
            }

            $creneau = $this->prendreCreneau($creneauId, null, false);
            if (! $creneau instanceof ESBTPRdvCreneau) {
                return ['ok' => false, 'code' => $creneau['code'] ?? 'ferme'];
            }

            $reservation = ESBTPRdvReservation::create($porteur->snapshotRdv() + $porteur->clesReservationRdv() + [
                'creneau_id' => $creneau->id,
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

        return $this->sousVerrou($porteur, function (PorteurDeRendezVous $porteur) use ($creneauId) {
            $actuelle = $this->reservationActiveDu($porteur, true);
            if ($actuelle === null) {
                return ['ok' => false, 'code' => 'introuvable'];
            }

            if (! $this->peutModifier($actuelle)) {
                return ['ok' => false, 'code' => 'trop_tard'];
            }

            $cible = $this->prendreCreneau($creneauId, (int) $actuelle->creneau_id);
            if (! $cible instanceof ESBTPRdvCreneau) {
                return $cible;
            }

            $actuelle->update(['creneau_id' => $cible->id]);

            return ['ok' => true, 'reservation' => $actuelle->fresh()->load('creneau')];
        });
    }

    /**
     * @return array{ok: true, reservation?: ESBTPRdvReservation}|array{ok: false, code: string}
     */
    public function annuler(string $reference, string $dateNaissance): array
    {
        $porteur = $this->porteurConcordant($reference, $dateNaissance);
        if ($porteur === null) {
            return ['ok' => false, 'code' => 'introuvable'];
        }

        return $this->sousVerrou($porteur, function (PorteurDeRendezVous $porteur) {
            $actuelle = $this->reservationActiveDu($porteur, true);
            if ($actuelle === null) {
                return ['ok' => false, 'code' => 'introuvable'];
            }

            if (! $this->peutModifier($actuelle)) {
                return ['ok' => false, 'code' => 'trop_tard'];
            }

            $actuelle->update(['statut' => StatutReservationRdv::Annulee]);

            return ['ok' => true, 'reservation' => $actuelle->load('creneau')];
        });
    }

    public function consulter(string $reference, string $dateNaissance): ?ESBTPRdvReservation
    {
        $porteur = $this->porteurConcordant($reference, $dateNaissance);

        return $porteur === null ? null : $this->reservationActiveDu($porteur)?->load('creneau');
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
            return $candidature->assurerReferencePublique();
        }

        $demande = ESBTPReinscriptionDemande::query()
            ->whereHas('etudiant', function ($q) use ($identifiant, $naissance) {
                $q->where(function ($inner) use ($identifiant) {
                    $inner->where('matricule', $identifiant)
                        ->orWhere('telephone', $identifiant);
                })->whereDate('date_naissance', $naissance);
            })
            ->first();

        return $demande?->assurerReferencePublique();
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

    public function porteurConcordant(string $reference, string $dateNaissance): ?PorteurDeRendezVous
    {
        $cle = $this->references->normaliser($reference);
        $naissance = $this->dateIso($dateNaissance);
        if ($cle === '' || $naissance === null) {
            return null;
        }

        $candidature = ESBTPCandidature::query()->where('reference_publique', $cle)->first();
        if ($candidature !== null) {
            return IdentitePersonne::jour($candidature->date_naissance) === $naissance
                ? $candidature
                : null;
        }

        $demande = ESBTPReinscriptionDemande::query()
            ->where('reference_publique', $cle)
            ->with('etudiant')
            ->first();

        if ($demande?->etudiant === null) {
            return null;
        }

        return IdentitePersonne::jour($demande->etudiant->date_naissance) === $naissance
            ? $demande
            : null;
    }

    /**
     * @template T
     * @param  callable(PorteurDeRendezVous): T  $suite
     * @return T|array{ok: false, code: string}
     */
    private function sousVerrou(PorteurDeRendezVous $porteur, callable $suite): mixed
    {
        return DB::transaction(function () use ($porteur, $suite) {
            $verrouille = $porteur->verrouillerPourRdv();
            if ($verrouille === null) {
                return ['ok' => false, 'code' => 'introuvable'];
            }

            return $suite($verrouille);
        });
    }

    /**
     * @return ESBTPRdvCreneau|array{ok: false, code: string, creneaux: list<array<string, mixed>>}
     */
    private function prendreCreneau(int $creneauId, ?int $ignorerId = null, bool $respecterDelai = true): ESBTPRdvCreneau|array
    {
        $creneau = ESBTPRdvCreneau::query()->whereKey($creneauId)->lockForUpdate()->first();
        if ($creneau === null || ! $creneau->ouvert) {
            return ['ok' => false, 'code' => 'ferme', 'creneaux' => $this->catalogue->publier()];
        }

        if ($this->dejaCommence($creneau) || ($respecterDelai && $this->tropTot($creneau))) {
            return ['ok' => false, 'code' => 'trop_tot', 'creneaux' => $this->catalogue->publier()];
        }

        $meme = $ignorerId !== null && (int) $creneau->id === $ignorerId;
        if (! $meme && $creneau->placesPrises() >= $creneau->capacite) {
            return ['ok' => false, 'code' => 'complet', 'creneaux' => $this->catalogue->publier()];
        }

        return $creneau;
    }

    private function reservationActiveDu(PorteurDeRendezVous $porteur, bool $verrouiller = false): ?ESBTPRdvReservation
    {
        $requete = ESBTPRdvReservation::query()
            ->occupantes()
            ->where($porteur->colonneReservationRdv(), $porteur->clesReservationRdv()[$porteur->colonneReservationRdv()]);

        if ($verrouiller) {
            $requete->lockForUpdate();
        }

        return $requete->first();
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
