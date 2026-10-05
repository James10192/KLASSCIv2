<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\Setting;
use App\Services\RendezVous\RendezVousReglages;

class ReglerFermetureJourMinuit extends ActionAgent
{
    public function cle(): string { return 'fermeture_jour_rdv_minuit'; }
    public function libelle(): string { return 'Préparation du réglage de fermeture des rendez-vous…'; }
    public function description(): string
    {
        return 'PROPOSE d’activer ou désactiver le réglage école « fermer automatiquement les rendez-vous du jour à 00:00 ». '
            .'Quand il est actif, les familles ne peuvent plus choisir un créneau daté du jour dès minuit ; les rendez-vous déjà réservés restent inchangés.';
    }
    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => ['actif' => ['type' => 'boolean']], 'required' => ['actif']];
    }
    public function isAvailableFor($user): bool
    {
        return (bool) config('assistant.actions.actives', true) && $user?->can('inscriptions.rdv.configure');
    }
    public function preparer(array $args, $user): Proposition
    {
        if (! array_key_exists('actif', $args)) {
            return new Proposition(titre: 'Fermeture du jour à minuit', resume: '', manques: ['Faut-il activer ou désactiver ce réglage ?']);
        }
        $actif = (bool) $args['actif'];
        $actuel = app(RendezVousReglages::class)->fermerJourAMinuit();
        if ($actif === $actuel) {
            return Proposition::sansObjet('Fermeture du jour à minuit', $actif ? 'le réglage est déjà activé.' : 'le réglage est déjà désactivé.');
        }
        return new Proposition(
            titre: ($actif ? 'Activer' : 'Désactiver').' la fermeture du jour à minuit',
            resume: $actif
                ? 'À partir de 00:00, les créneaux datés du jour ne seront plus proposés aux nouvelles réservations.'
                : 'Les créneaux du jour pourront de nouveau être proposés selon le délai minimal habituel.',
            donnees: ['actif' => $actif], etat: ['actuel' => $actuel], risque: 'moyen'
        );
    }
    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.configure')) throw new PropositionPerimee('Vous n’avez plus le droit de configurer les rendez-vous.');
        $reglages = app(RendezVousReglages::class);
        if ($reglages->fermerJourAMinuit() !== (bool) $proposition->etat['actuel']) throw new PropositionPerimee('Le réglage a changé depuis la proposition.');
        Setting::set(RendezVousReglages::FERMER_JOUR_A_MINUIT, $proposition->donnees['actif'] ? '1' : '0', $user->id);
        Setting::clearCache();
        return ['message' => 'Réglage de fermeture du jour à minuit '.($proposition->donnees['actif'] ? 'activé.' : 'désactivé.'), 'lien' => route('esbtp.rendez-vous.index', [], false), 'model_type' => Setting::class];
    }
}
