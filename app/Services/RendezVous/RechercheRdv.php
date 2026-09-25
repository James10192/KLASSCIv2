<?php

namespace App\Services\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Portail\ReferencePublique;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Retrouver le rendez-vous d'une famille, quel que soit le jour.
 *
 * L'accueil du jour ne cherche que dans sa journee : une famille qui appelle
 * « c'est quand, notre rendez-vous ? » ne dit pas le jour, elle dit son nom.
 * Une seule definition de la recherche, pour l'ecran et pour klassci-cli.
 *
 * Le texte libre se decoupe en mots, et chaque mot doit se retrouver quelque
 * part : nom, prenoms, courriel, reference du dossier, matricule de l'eleve
 * pour une reinscription. Une saisie faite de chiffres est lue comme un
 * numero de telephone, espaces et indicatif compris.
 */
class RechercheRdv
{
    public const QUAND_A_VENIR = 'a_venir';

    public const QUAND_PASSES = 'passes';

    public const QUAND_TOUS = 'tous';

    public const TYPE_CANDIDATURE = 'candidature';

    public const TYPE_REINSCRIPTION = 'reinscription';

    public function __construct(private readonly ReferencePublique $references)
    {
    }

    /**
     * @return array{q: string, quand: string, statut: string, type: string}
     */
    public function filtres(Request $request): array
    {
        $quand = (string) $request->input('quand', '');
        $statut = (string) $request->input('statut', '');
        $type = (string) $request->input('type', '');
        $q = trim((string) $request->input('q', ''));

        return [
            'q' => mb_substr($q, 0, 120),
            // Sans texte, on regarde ce qui vient ; avec un nom, on cherche
            // partout : la famille a peut-etre deja ete recue.
            'quand' => in_array($quand, [self::QUAND_A_VENIR, self::QUAND_PASSES, self::QUAND_TOUS], true)
                ? $quand
                : ($q === '' ? self::QUAND_A_VENIR : self::QUAND_TOUS),
            'statut' => in_array($statut, StatutReservationRdv::values(), true) ? $statut : '',
            'type' => in_array($type, [self::TYPE_CANDIDATURE, self::TYPE_REINSCRIPTION], true) ? $type : '',
        ];
    }

    /**
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     */
    public function requete(array $filtres): Builder
    {
        $aujourdhui = Carbon::today()->toDateString();

        $requete = ESBTPRdvReservation::query()
            ->join('esbtp_rdv_creneaux', 'esbtp_rdv_creneaux.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->select('esbtp_rdv_reservations.*')
            ->with(['creneau', 'candidature', 'demande.etudiant:id,matricule', 'accueilliPar:id,name'])
            // Meme compte que l'accueil du jour : les rendez-vous deja manques.
            ->withCount(['reprogrammations as absences' => fn ($r) => $r->where('non_venue', true)]);

        match ($filtres['quand']) {
            self::QUAND_A_VENIR => $requete->where('esbtp_rdv_creneaux.date', '>=', $aujourdhui),
            self::QUAND_PASSES => $requete->where('esbtp_rdv_creneaux.date', '<', $aujourdhui),
            default => null,
        };

        if ($filtres['statut'] !== '') {
            $requete->where('esbtp_rdv_reservations.statut', $filtres['statut']);
        }
        if ($filtres['type'] === self::TYPE_CANDIDATURE) {
            $requete->whereNotNull('esbtp_rdv_reservations.candidature_id');
        } elseif ($filtres['type'] === self::TYPE_REINSCRIPTION) {
            $requete->whereNotNull('esbtp_rdv_reservations.reinscription_demande_id');
        }

        $this->texte($requete, $filtres['q']);

        // Ce qui vient se lit dans l'ordre du calendrier ; le passe, du plus
        // recent au plus ancien. L'identifiant departage : la liste se charge
        // par tranches.
        $sens = $filtres['quand'] === self::QUAND_A_VENIR ? 'asc' : 'desc';

        return $requete
            ->orderBy('esbtp_rdv_creneaux.date', $sens)
            ->orderBy('esbtp_rdv_creneaux.heure_debut', $sens)
            ->orderBy('esbtp_rdv_reservations.id', $sens);
    }

    private function texte(Builder $requete, string $q): void
    {
        if ($q === '') {
            return;
        }

        // Un numero de telephone : « 07 07 12 34 », « +225 0707… ».
        if (preg_match('/^[\d\s+().-]+$/', $q) === 1) {
            $chiffres = preg_replace('/\D/', '', $q) ?? '';
            if (strlen($chiffres) >= 4) {
                $requete->where('esbtp_rdv_reservations.telephone', 'like', '%'.$this->echapper($chiffres).'%');

                return;
            }
        }

        foreach (preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $mot) {
            $like = '%'.$this->echapper($mot).'%';
            $reference = $this->references->normaliser($mot);

            $requete->where(function (Builder $w) use ($like, $reference) {
                $w->where('esbtp_rdv_reservations.nom', 'like', $like)
                    ->orWhere('esbtp_rdv_reservations.prenoms', 'like', $like)
                    ->orWhere('esbtp_rdv_reservations.email', 'like', $like)
                    ->orWhereHas('demande.etudiant', fn (Builder $e) => $e->where('matricule', 'like', $like));

                // Les references sont stockees sans tiret ni espace.
                if (strlen($reference) >= 4) {
                    $like = '%'.$reference.'%';
                    $w->orWhereHas('candidature', fn (Builder $c) => $c->where('reference_publique', 'like', $like))
                        ->orWhereHas('demande', fn (Builder $d) => $d->where('reference_publique', 'like', $like));
                }
            });
        }
    }

    private function echapper(string $valeur): string
    {
        return addcslashes($valeur, '\\%_');
    }
}
