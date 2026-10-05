<?php

namespace App\Domain\Assistant\Outils;

use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\EtatChaineRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\Chatbot\Tools\ChatbotTool;

class LireRendezVous extends ChatbotTool
{
    public function name(): string { return 'lire_rendez_vous'; }

    public function description(): string
    {
        return "Lit l'état des rendez-vous d'inscription, les blocages, les convocations, les dossiers à placer et les bascules de l'école dont la fermeture des créneaux du jour à minuit. À lire avant une action globale ou un changement de réglage.";
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function execute(array $args, $user): array
    {
        $etat = app(EtatChaineRdv::class);
        $rdv = app(RendezVousReglages::class);
        $maillons = $etat->maillons();
        $convocations = $etat->convocations();
        $apercu = app(AffecteurDossiersRdv::class)->apercu();
        $lignes = array_map(fn ($m) => ['maillon' => $m['titre'], 'etat' => $m['ok'] ? 'OK' : 'À régler', 'detail' => $m['detail']], $maillons);

        return [
            'results' => $lignes,
            'count' => count($lignes),
            'display_type' => 'table',
            'deep_link' => \Illuminate\Support\Facades\Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
            'diagnostic' => [
                'tout_en_ordre' => ! in_array(false, array_column($maillons, 'ok'), true),
                'bloquants' => array_values(array_map(fn ($m) => $m['cle'].' : '.$m['detail'], array_filter($maillons, fn ($m) => ! $m['ok']))),
                'reglages' => [
                    'prise_rendez_vous_active' => $rdv->enabled(),
                    'fermer_jour_a_minuit' => $rdv->fermerJourAMinuit(),
                    'lieu' => $rdv->lieu(),
                ],
                'convocations' => $convocations + ['a_envoyer_maintenant' => app(FileConvocationsRdv::class)->enAttente()],
                'dossiers' => [
                    'a_placer' => $apercu['places'],
                    'sans_creneau' => $apercu['sans_creneau'],
                    'deja_places' => $apercu['deja'],
                    'places_libres' => $apercu['places_libres'],
                    'refus_placement' => $apercu['refus'],
                ],
            ],
        ];
    }
}
