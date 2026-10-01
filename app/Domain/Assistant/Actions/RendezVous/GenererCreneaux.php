<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Exceptions\ReglagesRdvIncomplets;
use App\Models\ESBTPRdvCreneau;
use App\Services\RendezVous\GenerateurCreneaux;
use App\Services\RendezVous\RendezVousReglages;
use Illuminate\Support\Facades\Route;

/**
 * Propose de (re)générer les créneaux de rendez-vous d'inscription à partir des
 * réglages de l'école (période, jours, horaires, durée, capacité) : le bouton
 * « Générer les créneaux » de l'écran, et `POST /api/cli/rendez-vous/generer`.
 *
 * Aucune date n'est choisie ici : elles viennent des réglages. S'ils sont
 * incomplets, Nanan le dit et renvoie vers l'écran des rendez-vous. Un créneau
 * déjà réservé n'est jamais retouché ; un créneau libre hors règle est fermé.
 * Aucune famille n'est prévenue par la génération.
 */
class GenererCreneaux extends ActionAgent
{
    public function __construct(private GenerateurCreneaux $generateur, private RendezVousReglages $reglages)
    {
    }

    public function cle(): string
    {
        return 'generation_creneaux_rdv';
    }

    public function description(): string
    {
        return 'PROPOSE de générer les créneaux de rendez-vous d\'inscription selon les réglages de l\'écran des rendez-vous (aucune date à fournir, aucune à inventer). '
            .'Montre combien seront créés, mis à jour, fermés, et ceux conservés parce que réservés. Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => []];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Générer les créneaux de rendez-vous';
        try {
            $regle = $this->reglages->pourGeneration();
            $rapport = $this->generateur->simuler();
        } catch (ReglagesRdvIncomplets $e) {
            return new Proposition(titre: $titre, resume: '', manques: [
                $e->getMessage().' Ces réglages se renseignent sur l\'écran des rendez-vous (Inscriptions → Rendez-vous → Réglages) : ne les devine pas.',
            ]);
        }

        // Un créneau déjà conforme n'est pas un changement : il ne compte pas.
        $misAJour = $rapport->misAJour - $rapport->inchanges;
        $chiffres = ['crees' => $rapport->crees, 'mis_a_jour' => $misAJour, 'fermes' => $rapport->fermes, 'conserves' => $rapport->conservesOccupes];
        if ($rapport->crees + $misAJour + $rapport->fermes === 0) {
            return new Proposition(titre: $titre, resume: '', manques: [
                'Les créneaux sont déjà à jour : rien à générer'.($rapport->conservesOccupes > 0 ? " ({$rapport->conservesOccupes} réservés conservés)." : '.'),
            ]);
        }

        $jours = ['', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam', 'dim'];
        $avertissements = [];
        if ($rapport->fermes > 0) {
            $avertissements[] = "{$rapport->fermes} créneau(x) libre(s) hors des réglages seront fermés (pas supprimés).";
        }
        if (! $this->reglages->enabled()) {
            $avertissements[] = 'La prise de rendez-vous est fermée : les familles ne verront ces créneaux qu\'une fois ouverte.';
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('Créneaux : %d seront créés, %d mis à jour, %d fermés ; %d réservés seront conservés.', ...array_values($chiffres)),
            tableau: [
                'colonnes' => ['Période', 'Jours', 'Horaires', 'Durée', 'Places par créneau', 'Créés', 'Mis à jour', 'Fermés', 'Conservés'],
                'lignes' => [[
                    $regle->plancher->format('d/m/Y').' → '.$regle->fermeture->format('d/m/Y'),
                    implode(' ', array_map(fn (int $j) => $jours[$j] ?? (string) $j, $regle->joursOuverts)),
                    $regle->heureDebut.'–'.$regle->heureFin.($regle->pauseDebut ? " (pause {$regle->pauseDebut}–{$regle->pauseFin})" : ''),
                    $regle->dureeMinutes.' min', (string) $regle->capacite,
                    (string) $rapport->crees, (string) $misAJour, (string) $rapport->fermes, (string) $rapport->conservesOccupes,
                ]],
            ],
            avertissements: $avertissements,
            donnees: $chiffres,
            // Les chiffres (donnees) suffisent : une reservation faite entre-temps
            // ne change la generation que si elle change ces chiffres.
            etat: ['regle' => (array) $regle],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.manage')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de gérer les rendez-vous.");
        }
        try {
            $rapport = $this->generateur->generer();
        } catch (ReglagesRdvIncomplets $e) {
            throw new PropositionPerimee($e->getMessage());
        }

        return [
            'message' => sprintf('Créneaux générés : %d créés, %d mis à jour, %d fermés, %d conservés car déjà réservés.', $rapport->crees, $rapport->misAJour, $rapport->fermes, $rapport->conservesOccupes),
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
            'model_type' => ESBTPRdvCreneau::class,
            'details' => (array) $rapport,
        ];
    }
}
