<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPRdvCreneau;

class BasculerCreneauRdv extends ActionAgent
{
    public function cle(): string { return 'bascule_creneau_rdv'; }
    public function libelle(): string { return 'Préparation du changement de créneau…'; }
    public function description(): string
    {
        return 'PROPOSE d’ouvrir ou fermer un créneau précis de rendez-vous à partir de son identifiant. '
            .'Fermer un créneau le retire des choix des familles mais conserve les réservations existantes. Utilise reprogrammation_rdv si les familles doivent aussi être déplacées.';
    }
    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => [
            'creneau_id' => ['type' => 'integer'],
            'ouvert' => ['type' => 'boolean'],
        ], 'required' => ['creneau_id', 'ouvert']];
    }
    public function isAvailableFor($user): bool
    {
        return (bool) config('assistant.actions.actives', true) && $user?->can('inscriptions.rdv.manage');
    }
    public function preparer(array $args, $user): Proposition
    {
        $creneau = ESBTPRdvCreneau::withCount(['reservations as prises' => fn ($q) => $q->occupantes()])->find((int) ($args['creneau_id'] ?? 0));
        if (! $creneau) return new Proposition(titre: 'Modifier un créneau', resume: '', manques: ['Créneau introuvable.']);
        $ouvert = (bool) ($args['ouvert'] ?? false);
        if ((bool) $creneau->ouvert === $ouvert) {
            return Proposition::sansObjet('Modifier le créneau', $ouvert ? 'ce créneau est déjà ouvert.' : 'ce créneau est déjà fermé.');
        }
        return new Proposition(
            titre: ($ouvert ? 'Ouvrir' : 'Fermer').' le créneau du '.$creneau->date->format('d/m/Y').' à '.$creneau->heureDebutHi(),
            resume: sprintf('Le créneau sera %s. %d réservation(s) existante(s) seront conservées.', $ouvert ? 'ouvert aux familles' : 'retiré des choix des familles', (int) ($creneau->prises ?? 0)),
            avertissements: ! $ouvert && (int) ($creneau->prises ?? 0) > 0 ? ['Les familles déjà réservées ne seront pas déplacées. Pour cela, utilisez la reprogrammation administrative.'] : [],
            donnees: ['creneau_id' => $creneau->id, 'ouvert' => $ouvert],
            etat: ['ouvert' => (bool) $creneau->ouvert, 'updated_at' => optional($creneau->updated_at)->toISOString()],
            risque: 'moyen'
        );
    }
    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.manage')) throw new PropositionPerimee('Vous n’avez plus le droit de gérer les rendez-vous.');
        $creneau = ESBTPRdvCreneau::find((int) $proposition->donnees['creneau_id']);
        if (! $creneau || (bool) $creneau->ouvert !== (bool) $proposition->etat['ouvert']) throw new PropositionPerimee('Le créneau a changé depuis la proposition.');
        $creneau->update(['ouvert' => (bool) $proposition->donnees['ouvert']]);
        return ['message' => $proposition->donnees['ouvert'] ? 'Créneau ouvert aux familles.' : 'Créneau fermé aux nouvelles réservations.', 'lien' => route('esbtp.rendez-vous.index', [], false), 'model_type' => ESBTPRdvCreneau::class, 'model_id' => $creneau->id];
    }
}
