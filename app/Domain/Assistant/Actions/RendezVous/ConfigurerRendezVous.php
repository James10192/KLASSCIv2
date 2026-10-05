<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\Setting;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\RendezVous\FermetureAutomatiqueCreneauxRdv;
use App\Services\RendezVous\RendezVousReglages;
use Illuminate\Support\Facades\Route;

/** Réglages RDV volontairement séparés de ModifierReglages. */
class ConfigurerRendezVous extends ActionAgent
{
    public function __construct(private readonly FermetureAutomatiqueCreneauxRdv $fermeture) {}

    public function cle(): string
    {
        return 'configuration_rendez_vous';
    }

    public function description(): string
    {
        return "PROPOSE d'activer/désactiver deux bascules de l'école : la prise de rendez-vous et la fermeture automatique des créneaux du jour à 00:00. Pour la fermeture à minuit, l'activation ferme aussi immédiatement les créneaux d'aujourd'hui.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'prise_rendez_vous_active' => ['type' => 'boolean'],
                'fermer_jour_a_minuit' => ['type' => 'boolean'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $changements = [];
        if (array_key_exists('prise_rendez_vous_active', $args)) {
            $changements[RendezVousReglages::ENABLED] = (bool) $args['prise_rendez_vous_active'];
        }
        if (array_key_exists('fermer_jour_a_minuit', $args)) {
            $changements[RendezVousReglages::FERMER_JOUR_A_MINUIT] = (bool) $args['fermer_jour_a_minuit'];
        }
        if ($changements === []) {
            return new Proposition('Configurer les rendez-vous', '', manques: ['Indiquez au moins une bascule à modifier.']);
        }

        if (array_key_exists(RendezVousReglages::ENABLED, $changements)) {
            $ouvert = $changements[RendezVousReglages::ENABLED];
            $incoherence = InscriptionWorkflowSettings::incoherence(
                fn (string $cle): string => $cle === RendezVousReglages::ENABLED ? ($ouvert ? '1' : '0') : (string) Setting::get($cle, '')
            );
            if ($incoherence !== null) {
                return new Proposition('Configurer les rendez-vous', '', manques: [$incoherence]);
            }
        }

        $etat = [];
        $lignes = [];
        $effectifs = [];
        foreach ($changements as $cle => $apres) {
            $avant = filter_var(Setting::get($cle, '0'), FILTER_VALIDATE_BOOLEAN);
            $etat[$cle] = $avant;
            if ($avant === $apres) {
                continue;
            }
            $effectifs[$cle] = $apres;
            $lignes[] = [
                $cle === RendezVousReglages::ENABLED ? 'Prise de rendez-vous' : 'Fermer le jour à minuit',
                $avant ? 'Activé' : 'Désactivé',
                $apres ? 'Activé' : 'Désactivé',
            ];
        }

        if ($effectifs === []) {
            return Proposition::sansObjet('Configurer les rendez-vous', 'Les réglages ont déjà les valeurs demandées.');
        }

        $avertissements = [];
        if (($effectifs[RendezVousReglages::FERMER_JOUR_A_MINUIT] ?? false) === true) {
            $avertissements[] = "Les créneaux d'aujourd'hui seront fermés immédiatement aux nouvelles réservations. Les rendez-vous existants restent valides.";
        }
        if (array_key_exists(RendezVousReglages::ENABLED, $effectifs)) {
            $avertissements[] = "Cette bascule change immédiatement ce que les familles peuvent faire sur le portail de rendez-vous.";
        }

        return new Proposition(
            titre: 'Configurer les rendez-vous',
            resume: count($effectifs).' réglage(s) rendez-vous seront modifiés.',
            tableau: ['colonnes' => ['Réglage', 'Avant', 'Après'], 'lignes' => $lignes],
            donnees: ['ecritures' => $effectifs],
            etat: $etat,
            avertissements: $avertissements,
            risque: $avertissements !== [] ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.configure')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de configurer les rendez-vous.");
        }

        foreach ($proposition->etat as $cle => $avant) {
            $courant = filter_var(Setting::get($cle, '0'), FILTER_VALIDATE_BOOLEAN);
            if ($courant !== (bool) $avant) {
                throw new PropositionPerimee("Le réglage « {$cle} » a changé depuis la proposition.");
            }
        }

        foreach ($proposition->donnees['ecritures'] as $cle => $valeur) {
            Setting::set($cle, $valeur ? '1' : '0', (int) $user->id);
        }
        Setting::clearCache();

        $fermes = $this->fermeture->fermerAujourdHui();

        return [
            'message' => count($proposition->donnees['ecritures']).' réglage(s) rendez-vous enregistré(s).'.($fermes > 0 ? " {$fermes} créneau(x) d'aujourd'hui fermé(s)." : ''),
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', ['reglages' => 1], false) : null,
            'model_type' => Setting::class,
            'model_id' => Setting::where('key', array_key_first($proposition->donnees['ecritures']))->value('id'),
            'details' => ['ecritures' => $proposition->donnees['ecritures']],
        ];
    }
}
