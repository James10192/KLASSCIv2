<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Models\ESBTPRdvReservation;
use Illuminate\Support\Facades\DB;

class AnnulerRendezVous extends ActionAgent
{
    public function cle(): string { return 'annulation_rdv'; }
    public function libelle(): string { return 'Préparation de l’annulation du rendez-vous…'; }
    public function description(): string
    {
        return 'PROPOSE d’annuler un rendez-vous précis à partir de son identifiant de réservation. '
            .'La place est libérée pour une autre famille. Une convocation encore en attente devient sans objet. '
            .'Utilise reprogrammation_rdv lorsqu’il faut déplacer la famille au lieu d’annuler.';
    }
    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => [
            'reservation_id' => ['type' => 'integer'],
            'motif' => ['type' => 'string', 'description' => 'Motif administratif court, facultatif.'],
        ], 'required' => ['reservation_id']];
    }
    public function isAvailableFor($user): bool
    {
        return (bool) config('assistant.actions.actives', true) && $user?->can('inscriptions.rdv.manage');
    }
    public function preparer(array $args, $user): Proposition
    {
        $r = ESBTPRdvReservation::with('creneau')->find((int) ($args['reservation_id'] ?? 0));
        if (! $r || ! $r->creneau) return new Proposition(titre: 'Annuler un rendez-vous', resume: '', manques: ['Réservation introuvable.']);
        if (! in_array($r->statut, [StatutReservationRdv::Confirmee, StatutReservationRdv::Manquee], true)) {
            return Proposition::sansObjet('Annuler le rendez-vous', 'cette réservation n’est plus active.');
        }
        return new Proposition(
            titre: 'Annuler le rendez-vous de '.$r->nomComplet(),
            resume: 'Rendez-vous du '.$r->creneau->date->format('d/m/Y').' à '.$r->creneau->heureDebutHi().' : la réservation sera annulée et la place libérée.',
            avertissements: ['Cette action n’envoie pas automatiquement une nouvelle convocation. Pour déplacer la famille, reprogrammez plutôt le rendez-vous.'],
            donnees: ['reservation_id' => $r->id, 'motif' => trim((string) ($args['motif'] ?? ''))],
            etat: ['statut' => $r->statut instanceof \BackedEnum ? $r->statut->value : (string) $r->statut, 'creneau_id' => (int) $r->creneau_id],
            risque: 'eleve'
        );
    }
    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.manage')) throw new PropositionPerimee('Vous n’avez plus le droit de gérer les rendez-vous.');
        $id = (int) $proposition->donnees['reservation_id'];
        $r = DB::transaction(function () use ($id, $proposition) {
            $r = ESBTPRdvReservation::query()->lockForUpdate()->find($id);
            if (! $r) throw new PropositionPerimee('La réservation n’existe plus.');
            $statut = $r->statut instanceof \BackedEnum ? $r->statut->value : (string) $r->statut;
            if ($statut !== $proposition->etat['statut'] || (int) $r->creneau_id !== (int) $proposition->etat['creneau_id']) {
                throw new PropositionPerimee('La réservation a changé depuis la proposition.');
            }
            $valeurs = ['statut' => StatutReservationRdv::Annulee];
            $convocation = $r->convocation_statut instanceof \BackedEnum ? $r->convocation_statut->value : (string) $r->convocation_statut;
            if ($convocation === StatutConvocationRdv::EnAttente->value) {
                $valeurs['convocation_statut'] = StatutConvocationRdv::SansObjet;
                $valeurs['convocation_erreur'] = 'Rendez-vous annulé par l’administration avant envoi.';
            }
            $r->update($valeurs);
            return $r;
        });
        return ['message' => 'Rendez-vous annulé et place libérée.', 'lien' => route('esbtp.rendez-vous.index', [], false), 'model_type' => ESBTPRdvReservation::class, 'model_id' => $r->id, 'details' => ['motif' => $proposition->donnees['motif'] ?? '']];
    }
}
