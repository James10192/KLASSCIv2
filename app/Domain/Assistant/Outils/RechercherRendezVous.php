<?php

namespace App\Domain\Assistant\Outils;

use App\Domain\Admissions\FileDesDemandes;
use App\Enums\StatutReservationRdv;
use App\Services\RendezVous\AccueilRdv;
use App\Services\RendezVous\RechercheRdv;
use App\Services\Chatbot\Tools\ChatbotTool;
use Illuminate\Support\Facades\Route;

/**
 * Lecture ciblée avant une action RDV. Elle retrouve aussi les dossiers ouverts
 * sans rendez-vous : Nanan peut donc programmer une famille qui n'a encore
 * jamais choisi de créneau, sans inventer un identifiant de dossier.
 */
class RechercherRendezVous extends ChatbotTool
{
    public function __construct(
        private readonly RechercheRdv $recherche,
        private readonly AccueilRdv $accueil,
        private readonly FileDesDemandes $demandes,
    ) {}

    public function name(): string { return 'rechercher_rendez_vous'; }

    public function description(): string
    {
        return "Retrouver une famille par nom, téléphone, matricule ou référence. Rend les réservations existantes ET les dossiers ouverts sans rendez-vous avec leurs IDs exacts, ainsi que les prochains créneaux libres. À utiliser AVANT de programmer, reprogrammer, annuler ou renvoyer une convocation.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'q' => ['type' => 'string', 'description' => 'Nom, téléphone, matricule ou référence du dossier.'],
                'quand' => ['type' => 'string', 'enum' => ['a_venir', 'passes', 'tous']],
                'statut' => ['type' => 'string', 'enum' => StatutReservationRdv::values()],
                'type_dossier' => ['type' => 'string', 'enum' => ['candidature', 'reinscription']],
                'creneaux_libres' => ['type' => 'boolean', 'description' => 'Inclure les prochains créneaux libres pour programmer/reprogrammer.'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20],
            ],
            'required' => ['q'],
        ];
    }

    public function execute(array $args, $user): array
    {
        $q = mb_substr(trim((string) ($args['q'] ?? '')), 0, 120);
        if ($q === '') return ['error' => 'Indiquez un nom, un téléphone, un matricule ou une référence.'];

        $quand = in_array($args['quand'] ?? '', [RechercheRdv::QUAND_A_VENIR, RechercheRdv::QUAND_PASSES, RechercheRdv::QUAND_TOUS], true)
            ? (string) $args['quand'] : RechercheRdv::QUAND_TOUS;
        $statut = in_array($args['statut'] ?? '', StatutReservationRdv::values(), true) ? (string) $args['statut'] : '';
        $type = match ($args['type_dossier'] ?? '') {
            'candidature' => RechercheRdv::TYPE_CANDIDATURE,
            'reinscription' => RechercheRdv::TYPE_REINSCRIPTION,
            default => '',
        };
        $filtres = compact('q', 'quand', 'statut', 'type');
        $limit = min(max((int) ($args['limit'] ?? 10), 1), 20);

        $reservations = $this->recherche->requete($filtres)->limit($limit)->get();
        $resultats = $reservations->map(function ($r): array {
            $c = $r->creneau;
            $type = $r->candidature_id ? 'candidature' : 'reinscription';
            $dossierId = $r->candidature_id ?: $r->reinscription_demande_id;

            return [
                'reservation_id' => (int) $r->id,
                'nom' => $r->nomComplet(),
                'type_dossier' => $type,
                'dossier_id' => (int) $dossierId,
                'statut' => $r->statut?->value ?? (string) $r->statut,
                'creneau_id' => $c ? (int) $c->id : null,
                'date' => $c?->date?->toDateString(),
                'heure' => $c ? $c->heureDebutHi().' – '.$c->heureFinHi() : null,
                'creneau_ouvert' => (bool) ($c?->ouvert ?? false),
                'convocation_statut' => $r->convocation_statut?->value ?? (string) ($r->convocation_statut ?? ''),
                'convocation_action' => $r->convocation_action,
                'convocation_tentatives' => (int) ($r->convocation_tentatives ?? 0),
                'reference' => $r->candidature?->reference_publique ?? $r->demande?->reference_publique,
            ];
        })->values()->all();

        // La file unifiée applique elle-même les permissions candidature /
        // réinscription de l'agent. On demande seulement les dossiers ouverts
        // qui n'occupent aucun créneau, avec la même recherche texte que l'écran.
        $typeFile = match ($type) {
            RechercheRdv::TYPE_CANDIDATURE => FileDesDemandes::TYPE_NOUVELLE,
            RechercheRdv::TYPE_REINSCRIPTION => FileDesDemandes::TYPE_REINSCRIPTION,
            default => '',
        };
        $sansRdv = $this->demandes->page($user, [
            'type' => $typeFile,
            'etat' => 'a_traiter',
            'q' => $q,
            'sans_rdv' => true,
            'contact' => false,
        ], 1)->getCollection()->take($limit)->map(fn ($d): array => [
            'type_dossier' => $d->type === FileDesDemandes::TYPE_NOUVELLE ? 'candidature' : 'reinscription',
            'dossier_id' => (int) $d->id,
            'nom' => $d->nom,
            'reference' => $d->reference(),
            'statut' => $d->statut,
            'statut_lisible' => $d->libelleStatut(),
            'parcours' => $d->parcours,
            'obstacle' => $d->obstacle,
        ])->values()->all();

        return [
            'results' => $resultats,
            'count' => count($resultats),
            'dossiers_sans_rendez_vous' => $sansRdv,
            'count_sans_rendez_vous' => count($sansRdv),
            'approchant' => $this->recherche->approchant($filtres),
            'creneaux_libres' => ! empty($args['creneaux_libres']) ? $this->accueil->creneauxProposes() : [],
            'deep_link' => Route::has('esbtp.rendez-vous.recherche') ? route('esbtp.rendez-vous.recherche', ['q' => $q], false) : null,
        ];
    }
}
