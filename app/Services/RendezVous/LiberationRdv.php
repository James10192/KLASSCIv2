<?php

namespace App\Services\RendezVous;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rend a la campagne le creneau d'un dossier rejete.
 *
 * Une reservation « confirmee » occupe sa place. Rejeter le dossier ne la
 * touchait pas : la place restait prise, ni la famille refusee ni une autre ne
 * pouvaient s'en servir, et seul le filtre d'affichage `dossierOuvert` cachait
 * la ligne au guichet.
 *
 * Seuls les creneaux pas encore termines sont liberes. Un creneau passe est de
 * l'historique : il ne rend de place a personne, et la ligne dit encore ce qui
 * etait prevu. Une famille deja recue (honoree) ou absente (manquee) n'est pas
 * touchee non plus : ce sont des faits, pas des places.
 *
 * L'inscription, elle, ne libere rien : l'accueil garde le rendez-vous d'un
 * dossier inscrit sous « Dossier traite », parce que la famille vient souvent
 * quand meme.
 */
class LiberationRdv
{
    /**
     * @return Collection<int, ESBTPRdvReservation> les reservations liberees, creneau charge
     */
    public function apresRejet(PorteurDeRendezVous $porteur): Collection
    {
        $cles = array_filter($porteur->clesReservationRdv(), fn ($id) => $id !== null);
        if ($cles === []) {
            return collect();
        }

        $maintenant = Carbon::now();

        $aLiberer = ESBTPRdvReservation::query()
            ->where($cles)
            ->where('statut', StatutReservationRdv::Confirmee->value)
            // Le OU est groupe : laisse a nu, il s'echapperait de la jointure de
            // la relation et ramenerait les reservations de n'importe quel dossier.
            ->whereHas('creneau', fn (Builder $c) => $c->where(fn (Builder $pasTermine) => $pasTermine
                ->whereDate('date', '>', $maintenant->toDateString())
                ->orWhere(fn (Builder $jour) => $jour
                    ->whereDate('date', $maintenant->toDateString())
                    ->where('heure_fin', '>', $maintenant->format('H:i:s')))))
            ->with('creneau')
            ->get();

        $aLiberer->each(fn (ESBTPRdvReservation $r) => $r->update([
            'statut' => StatutReservationRdv::Liberee,
            'libere_at' => $maintenant,
        ]));

        return $aLiberer;
    }

    /**
     * La phrase ajoutee au message de rejet, vide si rien n'a ete libere.
     *
     * @param  Collection<int, ESBTPRdvReservation>  $liberees
     */
    public static function phrase(Collection $liberees): string
    {
        $premiere = $liberees->first();
        if ($premiere === null) {
            return '';
        }

        $creneau = $premiere->creneau;

        return ' Son rendez-vous du '.$creneau->date->translatedFormat('l j F')
            .' à '.$creneau->heureDebutHi().' est libéré.';
    }
}
