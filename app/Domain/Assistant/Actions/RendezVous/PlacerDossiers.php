<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use Illuminate\Support\Facades\Route;

/**
 * Propose de placer les dossiers en attente (candidatures, réinscriptions) sur
 * les créneaux libres : le bouton « Placer les dossiers » de l'écran, et
 * `POST /api/cli/rendez-vous/placer`.
 *
 * Placer POSE la convocation de chaque famille joignable : la tâche planifiée
 * l'envoie dans les minutes qui suivent. C'est donc un envoi aux familles, et la
 * proposition en donne le nombre exact avant « Valider ».
 */
class PlacerDossiers extends ActionAgent
{
    public function __construct(private AffecteurDossiersRdv $affecteur, private FileConvocationsRdv $file)
    {
    }

    public function cle(): string
    {
        return 'placement_dossiers_rdv';
    }

    public function description(): string
    {
        return 'PROPOSE de placer les dossiers d\'inscription en attente sur les créneaux de rendez-vous libres. '
            .'Chaque famille placée qui a une adresse reçoit sa convocation peu après la validation : la proposition donne leur nombre exact. '
            .'Ne le propose que si la personne demande de placer ou de convoquer. Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Placer les dossiers en attente';
        $apercu = $this->affecteur->apercu();
        if ($apercu['refus'] !== null) {
            return new Proposition(titre: $titre, resume: '', manques: [$apercu['refus']]);
        }
        if ($apercu['places'] === 0) {
            return new Proposition(titre: $titre, resume: '', manques: [
                $apercu['sans_creneau'] > 0
                    ? "{$apercu['sans_creneau']} dossier(s) attendent, mais aucune place libre : générez ou ouvrez des créneaux d'abord."
                    : 'Aucun dossier en attente de rendez-vous'.($apercu['deja'] > 0 ? " ({$apercu['deja']} déjà placé(s))." : '.'),
            ]);
        }

        $convoques = $apercu['places'] - $apercu['a_prevenir'];
        $avertissements = [
            "{$convoques} famille(s) recevront leur convocation dans les minutes qui suivent la validation (tâche planifiée).",
        ];
        if ($apercu['a_prevenir'] > 0) {
            $avertissements[] = "{$apercu['a_prevenir']} dossier(s) sans adresse : à prévenir par téléphone (liste « Familles à prévenir »).";
        }
        if ($apercu['sans_creneau'] > 0) {
            $avertissements[] = "{$apercu['sans_creneau']} dossier(s) resteront sans créneau faute de place.";
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d dossier(s) placé(s), dont %d convocation(s) envoyée(s) aux familles.', $apercu['places'], $convoques),
            tableau: [
                'colonnes' => ['Dossiers placés', 'Convocations envoyées', 'À prévenir par téléphone', 'Sans créneau', 'Déjà placés', 'Places libres'],
                'lignes' => [[(string) $apercu['places'], (string) $convoques, (string) $apercu['a_prevenir'],
                    (string) $apercu['sans_creneau'], (string) $apercu['deja'], (string) $apercu['places_libres']]],
            ],
            avertissements: $avertissements,
            donnees: ['places' => $apercu['places'], 'convoques' => $convoques],
            etat: ['dossiers' => $apercu['dossiers'], 'places_libres' => $apercu['places_libres']],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.manage')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de gérer les rendez-vous.");
        }
        $r = $this->affecteur->placer();
        if ($r['refus'] !== null) {
            throw new PropositionPerimee($r['refus']);
        }

        return [
            'message' => sprintf('%d dossier(s) placé(s).', $r['places']).AffecteurDossiersRdv::mentionAPrevenir($r['a_prevenir'])
                .sprintf(' %d convocation(s) en attente d\'envoi.', $this->file->enAttente()),
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
            'model_type' => ESBTPRdvReservation::class,
            'details' => $r,
        ];
    }
}
