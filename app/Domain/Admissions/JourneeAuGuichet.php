<?php

namespace App\Domain\Admissions;

use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\User;
use App\Services\RendezVous\AccueilRdv;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * La journee du guichet pour l'ecran « Aujourd'hui » : les creneaux du jour et
 * leurs familles, les quatre compteurs, et les familles reçues dont
 * l'inscription reste a finaliser.
 *
 * Tout vient de AccueilRdv::journee(), la meme lecture que l'Accueil du jour :
 * les deux ecrans ne peuvent pas diverger sur qui est attendu, reçu ou absent.
 * Cette classe ajoute seulement ce que le cockpit affiche en plus (le parcours
 * demande, l'inscription faite, le lien vers le dossier), charge en lot.
 */
final class JourneeAuGuichet
{
    public function __construct(private readonly AccueilRdv $accueil)
    {
    }

    /**
     * @return array{
     *     creneaux: list<array{creneau: ESBTPRdvCreneau, etat: string, libelle: string, familles: list<FamilleDuJour>}>,
     *     compteurs: array{attendues: int, recues: int, en_retard: int, finalisees: int, a_recevoir: int, non_venues: int},
     *     auGuichet: list<FamilleDuJour>
     * }
     */
    public function pour(User $agent, ?Carbon $maintenant = null): array
    {
        $maintenant ??= Carbon::now();
        $journee = $this->accueil->journee($maintenant->copy()->startOfDay());
        $this->chargerLesDossiers($journee['creneaux']);
        $droits = self::droits($agent);

        $creneaux = $journee['creneaux']->map(fn (ESBTPRdvCreneau $c) => [
            'creneau' => $c,
            'etat' => $c->estTermine() ? 'termine' : ($c->aCommence() ? 'en_cours' : 'a_venir'),
            'libelle' => $c->estTermine() ? 'Terminé' : ($c->aCommence() ? 'En cours' : 'À venir'),
            'familles' => $c->reservations
                ->map(fn (ESBTPRdvReservation $r) => FamilleDuJour::depuis($r, $this->accueil, $droits, $maintenant))
                ->all(),
        ])->values()->all();

        $familles = collect($creneaux)->flatMap(fn (array $c) => $c['familles']);

        return [
            'creneaux' => $creneaux,
            'compteurs' => [
                'attendues' => $journee['compteurs']['attendus'],
                'recues' => $journee['compteurs']['recus'],
                'en_retard' => $journee['compteurs']['en_retard'],
                'finalisees' => $familles->filter(fn (FamilleDuJour $f) => $f->etat === FamilleDuJour::INSCRITE)->count(),
                'a_recevoir' => $journee['compteurs']['a_recevoir'],
                'non_venues' => $journee['compteurs']['non_venues'],
            ],
            'auGuichet' => $familles
                ->filter(fn (FamilleDuJour $f) => $f->estRecue())
                ->sortByDesc(fn (FamilleDuJour $f) => $f->reservation->accueilli_at?->timestamp ?? 0)
                ->values()->all(),
        ];
    }

    /**
     * Ce que l'agent peut ouvrir et finaliser, lu une fois pour toute la page.
     *
     * @return array{voir: list<string>, finaliser: list<string>}
     */
    public static function droits(User $agent): array
    {
        return [
            'voir' => FileDesDemandes::typesVisibles($agent),
            'finaliser' => array_values(array_filter([
                $agent->can('inscriptions.candidatures.process') && $agent->can('inscriptions.ouvrir-formulaire') ? FileDesDemandes::TYPE_NOUVELLE : null,
                $agent->can('reinscriptions.demandes.process') ? FileDesDemandes::TYPE_REINSCRIPTION : null,
            ])),
        ];
    }

    /**
     * Le parcours demande (voeu, classe souhaitee), en une requete par relation
     * pour tout le jour. Les dossiers sont relus en entier : AccueilRdv ne
     * charge que les colonnes utiles a sa propre liste.
     *
     * @param  \Illuminate\Support\Collection<int, ESBTPRdvCreneau>  $creneaux
     */
    private function chargerLesDossiers($creneaux): void
    {
        (new EloquentCollection($creneaux->flatMap->reservations->all()))->load([
            'candidature' => fn ($q) => $q->with(['filiere:id,name', 'niveau:id,name']),
            'demande' => fn ($q) => $q->with('classeSouhaitee:id,name'),
        ]);
    }
}
