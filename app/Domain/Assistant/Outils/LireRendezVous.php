<?php

namespace App\Domain\Assistant\Outils;

use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\EtatChaineRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\Chatbot\Tools\ChatbotTool;

/**
 * Où en est la prise de rendez-vous d'inscription : chaque maillon (canal,
 * réglages, places, portail, messagerie), les convocations par état, et les
 * dossiers qui attendent d'être placés. La même source que l'écran et que
 * `GET /api/cli/rendez-vous/diagnostic` (EtatChaineRdv).
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
            .'les convocations par état (en attente, envoyées, échecs, inconnues), et combien de dossiers attendent d\'être placés. À lire avant de proposer une génération, un placement ou un envoi.';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function execute(array $args, $user): array
    {
        $etat = app(EtatChaineRdv::class);
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
