<?php

namespace App\Domain\Assistant\Outils;

use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\CatalogueCreneaux;
use App\Services\RendezVous\EtatChaineRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\Chatbot\Tools\ChatbotTool;
use App\Services\Reinscription\PortailReinscriptionService;

/**
 * Où en est la prise de rendez-vous d'inscription : chaque maillon (canal,
 * réglages, places, portail, messagerie), les convocations par état, et les
 * dossiers qui attendent d'être placés. Avec `date`, lit aussi les vraies
 * réservations actives de ce jour : Nanan n'a plus à demander des identifiants
 * à l'utilisateur ni à inventer un bouton « modifier ».
 */
class LireRendezVous extends ChatbotTool
{
    public function name(): string
    {
        return 'lire_rendez_vous';
    }

    public function description(): string
    {
        return "Lit l'état des rendez-vous d'inscription : ce qui bloque (prise de rendez-vous fermée, réglages incomplets, aucune place, messagerie coupée), "
            .'les convocations par état et combien de dossiers attendent d’être placés. '
            .'Passe date=AAAA-MM-JJ pour lister les réservations ACTIVES de ce jour avec leurs identifiants et créneaux, dans la campagne de rendez-vous courante. '
            .'À utiliser avant toute génération, placement, convocation ou reprogrammation. Si un jour futur doit être supprimé du planning, lire cette date puis utiliser proposer_reprogrammation_rdv.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'description' => 'Optionnel : date ISO AAAA-MM-JJ dont on veut voir les rendez-vous actifs.',
                ],
                'limite' => [
                    'type' => 'integer',
                    'description' => 'Nombre maximal de réservations détaillées pour une date (1 à 100, défaut 50).',
                ],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $etat = app(EtatChaineRdv::class);
        $maillons = $etat->maillons();
        $convocations = $etat->convocations();
        $apercu = app(AffecteurDossiersRdv::class)->apercu();

        $diagnostic = [
            'tout_en_ordre' => ! in_array(false, array_column($maillons, 'ok'), true),
            'bloquants' => array_values(array_map(fn ($m) => $m['cle'].' : '.$m['detail'], array_filter($maillons, fn ($m) => ! $m['ok']))),
            'convocations' => $convocations + ['a_envoyer_maintenant' => app(FileConvocationsRdv::class)->enAttente()],
            'dossiers' => [
                'a_placer' => $apercu['places'],
                'sans_creneau' => $apercu['sans_creneau'],
                'deja_places' => $apercu['deja'],
                'places_libres' => $apercu['places_libres'],
                'refus_placement' => $apercu['refus'],
            ],
        ];

        $dateBrute = trim((string) ($args['date'] ?? ''));
        if ($dateBrute === '') {
            $lignes = array_map(fn ($m) => ['maillon' => $m['titre'], 'etat' => $m['ok'] ? 'OK' : 'À régler', 'detail' => $m['detail']], $maillons);

            return [
                'results' => $lignes,
                'count' => count($lignes),
                'display_type' => 'table',
                'deep_link' => \Illuminate\Support\Facades\Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
                'diagnostic' => $diagnostic,
            ];
        }

        $jour = PortailReinscriptionService::interpreterDateIso($dateBrute);
        if ($jour === null) {
            return ['error' => 'Date invalide : utilisez AAAA-MM-JJ.'];
        }

        $annee = app(CatalogueCreneaux::class)->anneeDesCreneaux()?->id ?? 0;
        $limite = min(max((int) ($args['limite'] ?? 50), 1), 100);
        $base = ESBTPRdvReservation::query()
            ->select('esbtp_rdv_reservations.*')
            ->join('esbtp_rdv_creneaux as c', 'c.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->where('c.annee_universitaire_id', $annee)
            ->whereDate('c.date', $jour->toDateString())
            ->occupantes()
            ->dossierOuvert();

        $total = (clone $base)->count();
        $reservations = $base
            ->with('creneau')
            ->orderBy('c.heure_debut')
            ->orderBy('esbtp_rdv_reservations.id')
            ->limit($limite)
            ->get();

        $lignes = $reservations->map(function (ESBTPRdvReservation $r) {
            $creneau = $r->creneau;

            return [
                'reservation' => (int) $r->id,
                'famille' => $r->nomComplet(),
                'creneau' => $creneau ? $creneau->heureDebutHi().'–'.$creneau->heureFinHi() : '—',
                'creneau_id' => $creneau ? (int) $creneau->id : null,
                'ouvert' => $creneau?->ouvert ? 'oui' : 'non',
                'convocation' => $r->convocation_statut?->value ?? 'non suivie',
                'canal' => $r->convocation_canal?->value ?? '—',
            ];
        })->all();

        return [
            'results' => $lignes,
            'count' => $total,
            'display_type' => 'table',
            'deep_link' => \Illuminate\Support\Facades\Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', ['debut' => $jour->copy()->startOfWeek()->toDateString()], false) : null,
            'diagnostic' => $diagnostic + [
                'date_lue' => $jour->toDateString(),
                'reservations_actives' => $total,
                'lignes_affichees' => count($lignes),
                'suite' => $total > $limite ? sprintf('%d autre(s) réservation(s) non détaillée(s).', $total - $limite) : null,
                'action_si_jour_a_fermer' => 'Pour un jour futur à supprimer du planning, utiliser proposer_reprogrammation_rdv avec date_source='.$jour->toDateString().'.',
            ],
        ];
    }
}
