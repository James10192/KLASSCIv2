<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\RendezVous\AccueilRdv;
use App\Services\RendezVous\FermetureAutomatiqueCreneauxRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\ReservateurRdv;
use Illuminate\Support\Facades\Route;

/**
 * Actions unitaires sur un rendez-vous. Nanan ne devine jamais un dossier ni un
 * créneau : les IDs viennent de rechercher_rendez_vous, puis cette action ne
 * fait que préparer une proposition à valider.
 */
class GererRendezVous extends ActionAgent
{
    public function __construct(
        private readonly ReservateurRdv $reservateur,
        private readonly AccueilRdv $accueil,
        private readonly FileConvocationsRdv $convocations,
        private readonly FermetureAutomatiqueCreneauxRdv $fermeture,
    ) {}

    public function cle(): string
    {
        return 'gestion_rendez_vous';
    }

    public function description(): string
    {
        return 'PROPOSE une action ciblée : programmer un dossier sans rendez-vous, reprogrammer une réservation, annuler un rendez-vous, fermer ou rouvrir un créneau. Utilise seulement les IDs rendus par rechercher_rendez_vous. Programmer prépare une convocation « confirmé », reprogrammer une convocation « déplacé », annuler un avis « annulé ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'mode' => ['type' => 'string', 'enum' => ['programmer', 'reprogrammer', 'annuler', 'fermer_creneau', 'ouvrir_creneau']],
                'type_dossier' => ['type' => 'string', 'enum' => ['candidature', 'reinscription']],
                'dossier_id' => ['type' => 'integer'],
                'reservation_id' => ['type' => 'integer'],
                'creneau_id' => ['type' => 'integer'],
                'confirmation_annulation' => ['type' => 'boolean', 'description' => "Doit être true uniquement après confirmation explicite de la personne."],
            ],
            'required' => ['mode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        return match ((string) ($args['mode'] ?? '')) {
            'programmer' => $this->preparerProgrammer($args),
            'reprogrammer' => $this->preparerReprogrammer($args),
            'annuler' => $this->preparerAnnuler($args),
            'fermer_creneau', 'ouvrir_creneau' => $this->preparerCreneau($args, (string) $args['mode']),
            default => new Proposition('Gérer un rendez-vous', '', manques: ['Choisissez une action rendez-vous valide.']),
        };
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.manage')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de gérer les rendez-vous.");
        }

        return match ($proposition->donnees['mode']) {
            'programmer' => $this->executerProgrammer($proposition),
            'reprogrammer' => $this->executerReprogrammer($proposition, (int) $user->id),
            'annuler' => $this->executerAnnuler($proposition),
            'fermer_creneau', 'ouvrir_creneau' => $this->executerCreneau($proposition),
            default => throw new PropositionPerimee('Action rendez-vous inconnue.'),
        };
    }

    private function preparerProgrammer(array $args): Proposition
    {
        $type = $args['type_dossier'] ?? null;
        $dossierId = (int) ($args['dossier_id'] ?? 0);
        $creneauId = (int) ($args['creneau_id'] ?? 0);
        if (! in_array($type, ['candidature', 'reinscription'], true) || $dossierId <= 0 || $creneauId <= 0) {
            return new Proposition('Programmer le rendez-vous', '', manques: ['Il faut le type de dossier, son ID et le créneau choisi. Utilisez rechercher_rendez_vous avec creneaux_libres=true.']);
        }

        $dossier = $this->dossier($type, $dossierId);
        $creneau = ESBTPRdvCreneau::withCount('reservationsActives')->find($creneauId);
        if (! $dossier || ! $creneau) {
            return new Proposition('Programmer le rendez-vous', '', manques: ['Le dossier ou le créneau n’existe plus. Relancez rechercher_rendez_vous.']);
        }
        if ($dossier->dossierClos()) {
            return Proposition::sansObjet('Programmer le rendez-vous', 'Ce dossier est déjà clos.');
        }
        if ($this->reservationActiveDuDossier($type, $dossierId)) {
            return Proposition::sansObjet('Programmer le rendez-vous', 'Cette famille possède déjà un rendez-vous actif. Utilisez reprogrammer.');
        }
        if (! $creneau->ouvert || $creneau->aCommence() || $this->fermeture->doitEtreFerme($creneau)) {
            return new Proposition('Programmer le rendez-vous', '', manques: ['Ce créneau n’est plus réservable. Demandez de nouveau les créneaux libres.']);
        }
        if ((int) $creneau->reservations_actives_count >= (int) $creneau->capacite) {
            return new Proposition('Programmer le rendez-vous', '', manques: ['Ce créneau est complet. Demandez de nouveau les créneaux libres.']);
        }

        return new Proposition(
            titre: 'Programmer le rendez-vous',
            resume: sprintf('Programmer le dossier #%d au %s à %s.', $dossierId, $creneau->date->format('d/m/Y'), $creneau->heureDebutHi()),
            tableau: ['colonnes' => ['Dossier', 'Créneau', 'Occupation'], 'lignes' => [[
                $type.' #'.$dossierId,
                $creneau->date->format('d/m/Y').' '.$creneau->heureDebutHi().'–'.$creneau->heureFinHi(),
                $creneau->reservations_actives_count.'/'.$creneau->capacite,
            ]]],
            donnees: ['mode' => 'programmer', 'type_dossier' => $type, 'dossier_id' => $dossierId, 'creneau_id' => $creneauId],
            etat: ['dossier_updated_at' => (string) $dossier->updated_at, 'creneau_updated_at' => (string) $creneau->updated_at],
            avertissements: ['Après validation, une convocation de confirmation sera préparée pour la famille.'],
            risque: 'moyen',
        );
    }

    private function preparerReprogrammer(array $args): Proposition
    {
        $reservationId = (int) ($args['reservation_id'] ?? 0);
        $creneauId = (int) ($args['creneau_id'] ?? 0);
        $reservation = ESBTPRdvReservation::with('creneau')->find($reservationId);
        $cible = ESBTPRdvCreneau::withCount('reservationsActives')->find($creneauId);
        if (! $reservation || ! $reservation->creneau || ! $cible || $reservationId <= 0 || $creneauId <= 0) {
            return new Proposition('Reprogrammer le rendez-vous', '', manques: ['Retrouvez la réservation et choisissez un créneau libre avec rechercher_rendez_vous.']);
        }
        if (! $reservation->statut?->occupeLeCreneau()) {
            return Proposition::sansObjet('Reprogrammer le rendez-vous', 'Cette réservation ne tient plus de créneau.');
        }
        if ((int) $reservation->creneau_id === $creneauId) {
            return Proposition::sansObjet('Reprogrammer le rendez-vous', 'Le rendez-vous est déjà sur ce créneau.');
        }
        if (! $cible->ouvert || $cible->aCommence() || $this->fermeture->doitEtreFerme($cible)) {
            return new Proposition('Reprogrammer le rendez-vous', '', manques: ['Le nouveau créneau n’est plus disponible. Relancez rechercher_rendez_vous.']);
        }

        return new Proposition(
            titre: 'Reprogrammer le rendez-vous',
            resume: $reservation->nomComplet().' changera de créneau.',
            tableau: ['colonnes' => ['Famille', 'Avant', 'Après'], 'lignes' => [[
                $reservation->nomComplet(),
                $reservation->creneau->date->format('d/m/Y').' '.$reservation->creneau->heureDebutHi(),
                $cible->date->format('d/m/Y').' '.$cible->heureDebutHi(),
            ]]],
            donnees: ['mode' => 'reprogrammer', 'reservation_id' => $reservationId, 'creneau_id' => $creneauId, 'creneau_vu' => (int) $reservation->creneau_id],
            etat: ['reservation_updated_at' => (string) $reservation->updated_at, 'cible_updated_at' => (string) $cible->updated_at],
            avertissements: ['Après validation, une nouvelle convocation « déplacé » annoncera la nouvelle date.'],
            risque: 'eleve',
        );
    }

    private function preparerAnnuler(array $args): Proposition
    {
        if (($args['confirmation_annulation'] ?? false) !== true) {
            return new Proposition('Annuler le rendez-vous', '', manques: ["Ce geste libère la place et avertit la famille. Demandez une confirmation explicite, puis rappelez avec confirmation_annulation=true."]);
        }

        $id = (int) ($args['reservation_id'] ?? 0);
        $reservation = ESBTPRdvReservation::with('creneau')->find($id);
        if (! $reservation || ! $reservation->creneau) {
            return new Proposition('Annuler le rendez-vous', '', manques: ['Réservation introuvable. Relancez rechercher_rendez_vous.']);
        }
        if (! $reservation->statut?->occupeLeCreneau()) {
            return Proposition::sansObjet('Annuler le rendez-vous', 'Ce rendez-vous ne réserve déjà plus de place.');
        }

        return new Proposition(
            titre: 'Annuler le rendez-vous',
            resume: 'Le rendez-vous de '.$reservation->nomComplet().' sera annulé et sa place libérée.',
            tableau: ['colonnes' => ['Famille', 'Créneau', 'Après'], 'lignes' => [[
                $reservation->nomComplet(),
                $reservation->creneau->date->format('d/m/Y').' '.$reservation->creneau->heureDebutHi(),
                'Annulé · avis à envoyer',
            ]]],
            donnees: ['mode' => 'annuler', 'reservation_id' => $id, 'creneau_vu' => (int) $reservation->creneau_id],
            etat: ['reservation_updated_at' => (string) $reservation->updated_at],
            avertissements: ['La place redevient disponible. Une notification d’annulation sera préparée par le canal de convocation disponible.'],
            risque: 'eleve',
        );
    }

    private function preparerCreneau(array $args, string $mode): Proposition
    {
        $id = (int) ($args['creneau_id'] ?? 0);
        $creneau = ESBTPRdvCreneau::withCount('reservationsActives')->find($id);
        if (! $creneau) {
            return new Proposition('Gérer le créneau', '', manques: ['Créneau introuvable.']);
        }
        $ouvrir = $mode === 'ouvrir_creneau';
        if ((bool) $creneau->ouvert === $ouvrir) {
            return Proposition::sansObjet($ouvrir ? 'Ouvrir le créneau' : 'Fermer le créneau', $ouvrir ? 'Ce créneau est déjà ouvert.' : 'Ce créneau est déjà fermé.');
        }
        if ($ouvrir && $this->fermeture->doitEtreFerme($creneau)) {
            return new Proposition('Ouvrir le créneau', '', manques: ["Le réglage « fermeture du jour à minuit » interdit de rouvrir un créneau daté d'aujourd'hui."]);
        }

        return new Proposition(
            titre: $ouvrir ? 'Ouvrir le créneau' : 'Fermer le créneau',
            resume: sprintf('%s le créneau du %s à %s.', $ouvrir ? 'Ouvrir' : 'Fermer', $creneau->date->format('d/m/Y'), $creneau->heureDebutHi()),
            tableau: ['colonnes' => ['Créneau', 'Réservations existantes', 'Nouvel état'], 'lignes' => [[
                $creneau->date->format('d/m/Y').' '.$creneau->heureDebutHi(),
                (string) $creneau->reservations_actives_count,
                $ouvrir ? 'Ouvert' : 'Fermé',
            ]]],
            donnees: ['mode' => $mode, 'creneau_id' => $id],
            etat: ['creneau_updated_at' => (string) $creneau->updated_at, 'ouvert' => (bool) $creneau->ouvert],
            avertissements: $ouvrir ? [] : ['Les rendez-vous déjà placés sont conservés ; seules les nouvelles réservations sont bloquées.'],
            risque: 'moyen',
        );
    }

    private function executerProgrammer(Proposition $p): array
    {
        $type = $p->donnees['type_dossier'];
        $dossier = $this->dossier($type, (int) $p->donnees['dossier_id']);
        if (! $dossier || (string) $dossier->updated_at !== $p->etat['dossier_updated_at']) {
            throw new PropositionPerimee('Le dossier a changé depuis la proposition.');
        }

        $resultat = $this->reservateur->placer($dossier, (int) $p->donnees['creneau_id']);
        if (! $resultat['ok']) {
            throw new PropositionPerimee('Le créneau n’est plus disponible ('.$resultat['code'].').');
        }
        $this->convocations->confirmer($resultat['reservation'], 'confirme');

        return $this->retour($resultat['reservation'], 'Rendez-vous programmé. La convocation de confirmation est préparée.');
    }

    private function executerReprogrammer(Proposition $p, int $agentId): array
    {
        $reservation = ESBTPRdvReservation::with('creneau')->find($p->donnees['reservation_id']);
        if (! $reservation || (string) $reservation->updated_at !== $p->etat['reservation_updated_at']) {
            throw new PropositionPerimee('Le rendez-vous a changé depuis la proposition.');
        }

        $refus = $this->accueil->reprogrammer($reservation, (int) $p->donnees['creneau_id'], $agentId, (int) $p->donnees['creneau_vu']);
        if ($refus !== null) {
            throw new PropositionPerimee(AccueilRdv::message($refus));
        }

        return $this->retour($reservation->fresh()->load('creneau'), 'Rendez-vous reprogrammé. Une nouvelle convocation est préparée.');
    }

    private function executerAnnuler(Proposition $p): array
    {
        $reservation = ESBTPRdvReservation::with('creneau')->find($p->donnees['reservation_id']);
        if (! $reservation || (string) $reservation->updated_at !== $p->etat['reservation_updated_at']) {
            throw new PropositionPerimee('Le rendez-vous a changé depuis la proposition.');
        }

        $resultat = $this->reservateur->annulerAuGuichet($reservation);
        if (! $resultat['ok']) {
            throw new PropositionPerimee(AccueilRdv::message($resultat['code']));
        }

        // MessagerieRdv accepte explicitement l'action « annule » : contrairement
        // à une simple libération de place, la famille reçoit donc l'information.
        $this->convocations->confirmer($resultat['reservation'], 'annule');

        return $this->retour($resultat['reservation'], 'Rendez-vous annulé ; la place est libérée et l’avis d’annulation est préparé.');
    }

    private function executerCreneau(Proposition $p): array
    {
        $creneau = ESBTPRdvCreneau::find($p->donnees['creneau_id']);
        if (! $creneau
            || (string) $creneau->updated_at !== $p->etat['creneau_updated_at']
            || (bool) $creneau->ouvert !== (bool) $p->etat['ouvert']) {
            throw new PropositionPerimee('Le créneau a changé depuis la proposition.');
        }

        $ouvrir = $p->donnees['mode'] === 'ouvrir_creneau';
        if ($ouvrir && $this->fermeture->doitEtreFerme($creneau)) {
            throw new PropositionPerimee('Le réglage de fermeture à minuit interdit maintenant de rouvrir ce créneau.');
        }
        $creneau->update(['ouvert' => $ouvrir]);

        return [
            'message' => $ouvrir
                ? 'Créneau rouvert aux nouvelles réservations.'
                : 'Créneau fermé aux nouvelles réservations ; les rendez-vous existants sont conservés.',
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
            'model_type' => ESBTPRdvCreneau::class,
            'model_id' => $creneau->id,
        ];
    }

    private function dossier(string $type, int $id): ESBTPCandidature|ESBTPReinscriptionDemande|null
    {
        return $type === 'candidature' ? ESBTPCandidature::find($id) : ESBTPReinscriptionDemande::find($id);
    }

    private function reservationActiveDuDossier(string $type, int $id): bool
    {
        return ESBTPRdvReservation::query()->occupantes()
            ->where($type === 'candidature' ? 'candidature_id' : 'reinscription_demande_id', $id)
            ->exists();
    }

    private function retour(ESBTPRdvReservation $reservation, string $message): array
    {
        return [
            'message' => $message,
            'lien' => Route::has('esbtp.rendez-vous.recherche')
                ? route('esbtp.rendez-vous.recherche', ['q' => $reservation->nomComplet()], false)
                : null,
            'model_type' => ESBTPRdvReservation::class,
            'model_id' => $reservation->id,
            'details' => ['reservation_id' => $reservation->id, 'creneau_id' => $reservation->creneau_id],
        ];
    }
}
