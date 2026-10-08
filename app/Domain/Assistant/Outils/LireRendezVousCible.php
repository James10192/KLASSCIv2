<?php

namespace App\Domain\Assistant\Outils;

use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Services\Chatbot\Tools\ChatbotTool;
use App\Services\RendezVous\RendezVousReglages;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/**
 * Lecture ciblée avant toute action RDV de Nanan.
 * Retourne des identifiants réutilisables : aucune action n'a à deviner une
 * famille, une réservation ou un créneau à partir d'un simple nom.
 */
class LireRendezVousCible extends ChatbotTool
{
    public function name(): string
    {
        return 'lire_rendez_vous_cible';
    }

    public function description(): string
    {
        return 'Recherche un rendez-vous précis par identifiant, nom/prénom, référence de dossier ou date, et liste les créneaux d’une date avec leur disponibilité réelle. '
            .'À utiliser avant proposer_gestion_rendez_vous_cible pour programmer, reprogrammer, annuler, fermer ou rouvrir un créneau, et avant proposer_convocations_rdv pour un renvoi ciblé. '
            .'Pour reprogrammer : lis d’abord la réservation actuelle, puis la date cible ; choisis uniquement un créneau avec reservable=true. '
            .'Quand le réglage « fermeture du jour à minuit » est actif, les créneaux d’aujourd’hui sont non réservables dès 00:00 même s’ils étaient encore marqués ouverts en base. Nanan ne doit jamais tenter de les rouvrir.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reservation_id' => ['type' => 'integer'],
                'recherche' => ['type' => 'string', 'description' => 'Nom, prénom ou référence publique du dossier.'],
                'date' => ['type' => 'string', 'description' => 'Date AAAA-MM-JJ pour filtrer les rendez-vous et afficher les créneaux de ce jour.'],
                'creneau_id' => ['type' => 'integer'],
                'disponibles_seulement' => ['type' => 'boolean', 'description' => 'Si true, ne retourne que les créneaux réellement réservables.'],
                'limit' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $limit = min(max((int) ($args['limit'] ?? 15), 1), 30);
        $q = ESBTPRdvReservation::query()->with(['creneau', 'candidature', 'demande']);

        if (! empty($args['reservation_id'])) {
            $q->whereKey((int) $args['reservation_id']);
        }
        if (! empty($args['date'])) {
            $q->whereHas('creneau', fn (Builder $c) => $c->whereDate('date', (string) $args['date']));
        }
        if (($recherche = trim((string) ($args['recherche'] ?? ''))) !== '') {
            $q->where(function (Builder $w) use ($recherche) {
                $w->where('nom', 'like', '%'.$recherche.'%')
                    ->orWhere('prenoms', 'like', '%'.$recherche.'%')
                    ->orWhereHas('candidature', fn (Builder $c) => $c->where('reference_publique', 'like', '%'.$recherche.'%'))
                    ->orWhereHas('demande', fn (Builder $d) => $d->where('reference_publique', 'like', '%'.$recherche.'%'));
            });
        }

        $reservations = $q->latest('id')->limit($limit)->get()->map(fn (ESBTPRdvReservation $r) => [
            'reservation_id' => (int) $r->id,
            'famille' => $r->nomComplet(),
            'statut' => $r->statut?->value ?? (string) $r->statut,
            'candidature_id' => $r->candidature_id ? (int) $r->candidature_id : null,
            'reinscription_demande_id' => $r->reinscription_demande_id ? (int) $r->reinscription_demande_id : null,
            'reference' => $r->candidature?->reference_publique ?? $r->demande?->reference_publique,
            'creneau_id' => $r->creneau_id ? (int) $r->creneau_id : null,
            'date' => $r->creneau?->date?->toDateString(),
            'heure' => $r->creneau ? $r->creneau->heureDebutHi().'–'.$r->creneau->heureFinHi() : null,
            'convocation' => $r->convocation_statut?->value ?? ($r->convocation_statut ? (string) $r->convocation_statut : null),
        ])->values()->all();

        $creneaux = [];
        $creneauId = filter_var($args['creneau_id'] ?? null, FILTER_VALIDATE_INT);
        if ($creneauId !== false && $creneauId !== null) {
            $creneaux = ESBTPRdvCreneau::query()->whereKey($creneauId)->withCount(['reservations as prises' => fn ($r) => $r->occupantes()])->get();
        } elseif (! empty($args['date'])) {
            $creneaux = ESBTPRdvCreneau::query()->whereDate('date', (string) $args['date'])
                ->withCount(['reservations as prises' => fn ($r) => $r->occupantes()])
                ->orderBy('heure_debut')->limit(50)->get();
        }

        $reglages = app(RendezVousReglages::class);
        $fermetureMinuit = $reglages->fermerJourAMinuit();
        $aujourdhui = Carbon::today();

        $creneaux = collect($creneaux)->map(function (ESBTPRdvCreneau $c) use ($fermetureMinuit, $aujourdhui) {
            $prises = (int) ($c->prises ?? 0);
            $libres = max(0, (int) $c->capacite - $prises);
            $jourFerme = $fermetureMinuit && $c->date->copy()->startOfDay()->lte($aujourdhui);
            $commence = $c->aCommence();
            $reservable = (bool) $c->ouvert && $libres > 0 && ! $jourFerme && ! $commence;

            $raison = null;
            if ($jourFerme) {
                $raison = 'journée fermée à minuit par le réglage de l’école';
            } elseif (! $c->ouvert) {
                $raison = 'créneau fermé manuellement';
            } elseif ($commence) {
                $raison = 'créneau déjà commencé';
            } elseif ($libres <= 0) {
                $raison = 'créneau complet';
            }

            return [
                'creneau_id' => (int) $c->id,
                'date' => $c->date->toDateString(),
                'heure' => $c->heureDebutHi().'–'.$c->heureFinHi(),
                'ouvert' => (bool) $c->ouvert,
                'reservable' => $reservable,
                'raison_indisponible' => $raison,
                'capacite' => (int) $c->capacite,
                'prises' => $prises,
                'libres' => $libres,
            ];
        });

        if (($args['disponibles_seulement'] ?? false) === true) {
            $creneaux = $creneaux->where('reservable', true)->values();
        }

        return [
            'results' => $reservations,
            'count' => count($reservations),
            'display_type' => 'table',
            'creneaux' => $creneaux->values()->all(),
            'reglage_fermeture_jour_minuit' => $fermetureMinuit,
            'regle' => $fermetureMinuit
                ? 'La date du jour et les dates passées n’acceptent plus de nouvelles réservations dès 00:00. Les réservations existantes sont conservées.'
                : 'La fermeture automatique du jour à minuit est désactivée.',
            'actions_suivantes' => [
                'programmer_reprogrammer_annuler_fermer_ouvrir' => 'proposer_gestion_rendez_vous_cible',
                'renvoyer_convocation' => 'proposer_convocations_rdv',
                'modifier_fermeture_minuit' => 'proposer_gestion_rendez_vous_cible avec mode=fermeture_jour_minuit',
            ],
            'deep_link' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
        ];
    }
}
