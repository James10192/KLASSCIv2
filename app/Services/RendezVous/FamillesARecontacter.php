<?php

namespace App\Services\RendezVous;

use App\Enums\EtatEmail;
use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Emails\AnalyseurEmail;
use App\Services\Verification\MasqueContact;
use Illuminate\Support\Collection;

/**
 * Qui recontacter, famille par famille : un rendez-vous peut avoir ete
 * deplace ou annule plusieurs fois, la decision porte sur le DOSSIER
 * (candidature ou demande de reinscription), pas sur chaque reservation.
 *
 * Categories exclusives :
 * - A : au moins une convocation remise (confirmee par MailPulse) ;
 * - B : sinon, uniquement des echecs (rebonds, suppressions, refus) ;
 * - D : sinon, aucune adresse e-mail ;
 * - C : le reste (remise inconnue).
 * E compte les familles B ou D joignables par telephone. F compte les
 * adresses fabriquees par KLASSCI, reparties selon ce qui reste pour les
 * joindre.
 *
 * Perimetre : celui de PerimetreRdv (annee cible des inscriptions), le meme
 * que le diagnostic des convocations. Lus par paquets, colonnes utiles seulement.
 *
 * Lecture seule. Aucune donnee personnelle en clair dans ce qui sort d'ici.
 */
class FamillesARecontacter
{
    private const PAQUET = 500;

    private const COLONNES = [
        'id', 'creneau_id', 'candidature_id', 'reinscription_demande_id', 'statut', 'email', 'telephone',
        'convocation_statut', 'convocation_delivree_at', 'convocation_code_distant',
    ];

    public function __construct(
        private readonly AnalyseurEmail $emails,
        private readonly PerimetreRdv $perimetre,
    ) {}

    /** @return array{synthese: array<string, mixed>, familles: list<array<string, mixed>>} */
    public function rapport(): array
    {
        $familles = $this->reservationsParFamille()->map(fn (Collection $r) => $this->famille($r))->values();

        return ['synthese' => $this->synthese($familles), 'familles' => $familles->all()];
    }

    /** @return Collection<string, Collection<int, ESBTPRdvReservation>> */
    private function reservationsParFamille(): Collection
    {
        $parFamille = [];

        $this->perimetre->reservations()
            ->select(self::COLONNES)
            ->with([
                'candidature:id,email,tuteur_telephone',
                'demande:id,etudiant_id',
                'demande.etudiant:id,telephone,email,email_personnel',
                'demande.etudiant.parents:esbtp_parents.id,esbtp_parents.telephone,esbtp_parents.email',
            ])
            ->where(fn ($q) => $q->whereNotNull('candidature_id')->orWhereNotNull('reinscription_demande_id'))
            ->chunkById(self::PAQUET, function (Collection $lot) use (&$parFamille) {
                foreach ($lot as $r) {
                    $parFamille[$r->candidature_id ? 'c'.$r->candidature_id : 'd'.$r->reinscription_demande_id][] = $r;
                }
            });

        return collect($parFamille)->map(fn (array $reservations) => collect($reservations));
    }

    /** @param  Collection<int, ESBTPRdvReservation>  $reservations */
    private function famille(Collection $reservations): array
    {
        $derniere = $reservations->last();
        $email = trim((string) ($derniere->email ?: $derniere->porteur()?->emailRdv()));
        $etat = $email === '' ? EtatEmail::Vide : $this->emails->analyser($email)->etat;
        [$telephones, $alternatifs] = $this->autresContacts($derniere, $email);

        $famille = [
            'type' => $derniere->candidature_id ? 'candidature' : 'reinscription',
            'reservations' => $reservations->count(),
            'statut_reservation' => $derniere->statut?->value,
            'statut_convocation' => $derniere->convocation_statut?->value,
            'remise' => $this->remise($reservations),
            'email_masque' => $email === '' ? null : MasqueContact::email($email),
            'email_etat' => $etat === EtatEmail::FauteProbable ? EtatEmail::Valide->value : $etat->value,
            'telephones_masques' => array_map([MasqueContact::class, 'telephoneGroupe'], $telephones),
            'emails_alternatifs_masques' => array_map([MasqueContact::class, 'email'], $alternatifs),
            'a_telephone' => $telephones !== [],
            'a_email_alternatif_joignable' => $alternatifs !== [],
        ];
        $famille['categorie'] = $this->categorie($famille, $reservations);

        return $famille;
    }

    /** @return array{0: list<string>, 1: list<string>} telephones, puis adresses alternatives joignables */
    private function autresContacts(ESBTPRdvReservation $r, string $email): array
    {
        $telephones = [$r->telephone, $r->candidature?->tuteur_telephone];
        $emails = [];
        $etudiant = $r->demande?->etudiant;
        if ($etudiant !== null) {
            $telephones[] = $etudiant->telephone;
            $emails = [$etudiant->email_personnel, $etudiant->email];
            foreach ($etudiant->parents as $parent) {
                $telephones[] = $parent->telephone;
                $emails[] = $parent->email;
            }
        }

        $telephones = array_values(array_unique(array_filter(array_map(fn ($t) => trim((string) $t), $telephones))));
        $alternatifs = array_values(array_unique(array_filter(
            array_map(fn ($e) => mb_strtolower(trim((string) $e)), $emails),
            fn ($e) => $e !== '' && $e !== mb_strtolower($email) && $this->emails->analyser($e)->joignable(),
        )));

        return [$telephones, $alternatifs];
    }

    /** @param  Collection<int, ESBTPRdvReservation>  $reservations */
    private function remise(Collection $reservations): string
    {
        if ($reservations->contains(fn ($r) => $r->convocation_delivree_at !== null)) {
            return 'delivree';
        }
        $echec = $reservations->last(fn ($r) => $r->convocation_statut === StatutConvocationRdv::Echec);
        if ($echec !== null) {
            $famille = MotifsRemiseConvocation::famille($echec->convocation_code_distant);

            return $famille === 'autre' ? 'echec' : $famille;
        }

        return 'inconnue';
    }

    /** @param  Collection<int, ESBTPRdvReservation>  $reservations */
    private function categorie(array $famille, Collection $reservations): string
    {
        return match (true) {
            $famille['remise'] === 'delivree' => 'A',
            $reservations->every(fn ($r) => $r->convocation_statut === StatutConvocationRdv::Echec) => 'B',
            $famille['email_masque'] === null => 'D',
            default => 'C',
        };
    }

    /** @param  Collection<int, array<string, mixed>>  $familles */
    private function synthese(Collection $familles): array
    {
        $factices = $familles->where('email_etat', EtatEmail::Factice->value);

        return [
            'familles' => $familles->count(),
            'A_au_moins_une_remise' => $familles->where('categorie', 'A')->count(),
            'B_uniquement_des_echecs' => $familles->where('categorie', 'B')->count(),
            'C_remise_inconnue' => $familles->where('categorie', 'C')->count(),
            'D_sans_email' => $familles->where('categorie', 'D')->count(),
            'E_B_ou_D_avec_telephone' => $familles->filter(fn ($f) => in_array($f['categorie'], ['B', 'D'], true) && $f['a_telephone'])->count(),
            'F_adresse_fabriquee' => [
                'total' => $factices->count(),
                'email_alternatif' => $factices->where('a_email_alternatif_joignable', true)->count(),
                'telephone_seul' => $factices->filter(fn ($f) => ! $f['a_email_alternatif_joignable'] && $f['a_telephone'])->count(),
                'rien' => $factices->filter(fn ($f) => ! $f['a_email_alternatif_joignable'] && ! $f['a_telephone'])->count(),
            ],
        ];
    }
}
