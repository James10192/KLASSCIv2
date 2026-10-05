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
 * Actions unitaires sur un rendez-vous. Les opérations bulk restent dans
 * PlacerDossiers/ConvocationsRdv ; ici Nanan agit sur l'ID exact trouvé avec
 * rechercher_rendez_vous.
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
        return 'PROPOSE une action ciblée sur un rendez-vous : programmer un dossier sur un créneau, reprogrammer une réservation, annuler un rendez-vous, fermer ou rouvrir un créneau. Utilise uniquement les IDs lus avec rechercher_rendez_vous. Une reprogrammation prépare automatiquement une nouvelle convocation.';
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
                'confirmation_annulation' => ['type' => 'boolean', 'description' => "Doit être true pour annuler un rendez-vous déjà fixé."],
            ],
            'required' => ['mode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $mode = (string) ($args['mode'] ?? '');

        return match ($mode) {
            'programmer' => $this->preparerProgrammer($args),
            'reprogrammer' => $this->preparerReprogrammer($args),
            'annuler' => $this->preparerAnnuler($args),
            'fermer_creneau', 'ouvrir_creneau' => $this->preparerCreneau($args, $mode),
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
            return new Proposition('Programmer le rendez-vous', '', manques: ['Il faut le type de dossier, son ID et le créneau choisi. Utilisez rechercher_rendez_vous.']);
        }

        $dossier = $type === 'candidature' ? ESBTPCandidature::find($dossierId) : ESBTPReinscriptionDemande::find($dossierId);
        $creneau = ESBTPRdvCreneau::withCount('reservationsActives')->find($creneauId);
        if (! $dossier || ! $creneau) {
            return new Proposition('Programmer le rendez-vous', '', manques: ['Le dossier ou le créneau n’existe plus. Relancez la recherche.']);
        }
        if ($dossier->dossierClos()) {
            return Proposition::sansObjet('Programmer le rendez-vous', 'Ce dossier est déjà clos.');
        }
        if ($this->reservationActiveDuDossier($type, $dossierId)) {
            return Proposition::sansObjet('Programmer le rendez-vous', 'Cette famille possède déjà un rendez-vous actif. Reprogrammez-le plutôt.');
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
            avertissements: ['Une convocation sera préparée pour ce nouveau rendez-vous.'],
            risque: 'moyen',
        );
    }

    private function preparerReprogrammer(array $args): Proposition
    {
        $reservationId = (int) ($args['reservation_id'] ?? 0);
        $creneauId = (int) ($args['creneau_id'] ?? 0);
        $r = ESBTPRdvReservation::with('creneau')->find($reservationId);
        $cible = ESBTPRdvCreneau::withCount('reservationsActives')->find($creneauId);
        if (! $r || ! $r->creneau || ! $cible || $reservationId <= 0 || $creneauId <= 0) {
            return new Proposition('Reprogrammer le rendez-vous', '', manques: ['Retrouvez la réservation et choisissez un créneau libre avec rechercher_rendez_vous.']);
        }
        if ((int) $r->creneau_id === $creneauId) {
            return Proposition::sansObjet('Reprogrammer le rendez-vous', 'Le rendez-vous est déjà sur ce créneau.');
        }

        return new Proposition(
            titre: 'Reprogrammer le rendez-vous',
            resume: $r->nomComplet().' changera de créneau.',
            tableau: ['colonnes' => ['Famille', 'Avant', 'Après'], 'lignes' => [[
                $r->nomComplet(),
                $r->creneau->date->format('d/m/Y').' '.$r->creneau->heureDebutHi(),
                $cible->date->format('d/m/Y').' '.$cible->heureDebutHi(),
            ]]],
            donnees: ['mode' => 'reprogrammer', 'reservation_id' => $reservationId, 'creneau_id' => $creneauId, 'creneau_vu' => (int) $r->creneau_id],
            etat: ['reservation_updated_at' => (string) $r->updated_at, 'cible_updated_at' => (string) $cible->updated_at],
            avertissements: ['Une nouvelle convocation « déplacé » remplacera la date précédente.'],
            risque: 'eleve',
        );
    }

    private function preparerAnnuler(array $args): Proposition
    {
        if (($args['confirmation_annulation'] ?? false) !== true) {
            return new Proposition('Annuler le rendez-vous', '', manques: ["Confirmez explicitement l'annulation avec confirmation_annulation=true."]);
        }
        $id = (int) ($args['reservation_id'] ?? 0);
        $r = ESBTPRdvReservation::with('creneau')->find($id);
        if (! $r || ! $r->creneau) {
            return new Proposition('Annuler le rendez-vous', '', manques: ['Réservation introuvable. Relancez rechercher_rendez_vous.']);
        }
        if (! $r->statut?->occupeLeCreneau()) {
            return Proposition::sansObjet('Annuler le rendez-vous', 'Ce rendez-vous ne réserve déjà plus de place.');
        }

        return new Proposition(
            titre: 'Annuler le rendez-vous',
            resume: 'Le rendez-vous de '.$r->nomComplet().' sera annulé et sa place libérée.',
            tableau: ['colonnes' => ['Famille', 'Créneau', 'Statut'], 'lignes' => [[$r->nomComplet(), $r->creneau->date->format('d/m/Y').' '.$r->creneau->heureDebutHi(), 'Annulé']]],
            donnees: ['mode' => 'annuler', 'reservation_id' => $id, 'creneau_vu' => (int) $r->creneau_id],
            etat: ['reservation_updated_at' => (string) $r->updated_at],
            avertissements: ['La place redevient disponible. Une convocation encore en attente ne sera pas envoyée.'],
            risque: 'eleve',
        );
    }

    private function preparerCreneau(array $args, string $mode): Proposition
    {
        $id = (int) ($args['creneau_id'] ?? 0);
        $c = ESBTPRdvCreneau::withCount('reservationsActives')->find($id);
        if (! $c) {
            return new Proposition('Gérer le créneau', '', manques: ['Créneau introuvable.']);
        }
        $ouvrir = $mode === 'ouvrir_creneau';
        if ((bool) $c->ouvert === $ouvrir) {
            return Proposition::sansObjet($ouvrir ? 'Ouvrir le créneau' : 'Fermer le créneau', $ouvrir ? 'Ce créneau est déjà ouvert.' : 'Ce créneau est déjà fermé.');
        }
        if ($ouvrir && $this->fermeture->doitEtreFerme($c)) {
            return new Proposition('Ouvrir le créneau', '', manques: ["Le réglage « fermeture du jour à minuit » interdit de rouvrir un créneau daté d'aujourd'hui."]);
        }

        return new Proposition(
            titre: $ouvrir ? 'Ouvrir le créneau' : 'Fermer le créneau',
            resume: sprintf('%s le créneau du %s à %s.', $ouvrir ? 'Ouvrir' : 'Fermer', $c->date->format('d/m/Y'), $c->heureDebutHi()),
            tableau: ['colonnes' => ['Créneau', 'Réservations existantes', 'Nouvel état'], 'lignes' => [[$c->date->format('d/m/Y').' '.$c->heureDebutHi(), (string) $c->reservations_actives_count, $ouvrir ? 'Ouvert' : 'Fermé']]],
            donnees: ['mode' => $mode, 'creneau_id' => $id],
            etat: ['creneau_updated_at' => (string) $c->updated_at, 'ouvert' => (bool) $c->ouvert],
            avertissements: $ouvrir ? [] : ['Les rendez-vous déjà placés sont conservés ; seules les nouvelles réservations sont bloquées.'],
        );
    }

    private function executerProgrammer(Proposition $p): array
    {
        $type = $p->donnees['type_dossier'];
        $dossier = $type === 'candidature' ? ESBTPCandidature::find($p->donnees['dossier_id']) : ESBTPReinscriptionDemande::find($p->donnees['dossier_id']);
        if (! $dossier || (string) $dossier->updated_at !== $p->etat['dossier_updated_at']) {
            throw new PropositionPerimee('Le dossier a changé depuis la proposition.');
        }
        $r = $this->reservateur->placer($dossier, (int) $p->donnees['creneau_id']);
        if (! $r['ok']) {
            throw new PropositionPerimee('Le créneau n’est plus disponible ('.$r['code'].').');
        }
        $this->convocations->confirmer($r['reservation']);

        return $this->retour($r['reservation'], 'Rendez-vous programmé. La convocation est préparée.');
    }

    private function executerReprogrammer(Proposition $p, int $agentId): array
    {
        $r = ESBTPRdvReservation::with('creneau')->find($p->donnees['reservation_id']);
        if (! $r || (string) $r->updated_at !== $p->etat['reservation_updated_at']) {
            throw new PropositionPerimee('Le rendez-vous a changé depuis la proposition.');
        }
        $refus = $this->accueil->reprogrammer($r, (int) $p->donnees['creneau_id'], $agentId, (int) $p->donnees['creneau_vu']);
        if ($refus !== null) {
            throw new PropositionPerimee(AccueilRdv::message($refus));
        }

        return $this->retour($r->fresh()->load('creneau'), 'Rendez-vous reprogrammé. Une nouvelle convocation est préparée.');
    }

    private function executerAnnuler(Proposition $p): array
    {
        $r = ESBTPRdvReservation::with('creneau')->find($p->donnees['reservation_id']);
        if (! $r || (string) $r->updated_at !== $p->etat['reservation_updated_at']) {
            throw new PropositionPerimee('Le rendez-vous a changé depuis la proposition.');
        }
        $resultat = $this->reservateur->annulerAuGuichet($r);
        if (! $resultat['ok']) {
            throw new PropositionPerimee(AccueilRdv::message($resultat['code']));
        }

        return $this->retour($resultat['reservation'], 'Rendez-vous annulé ; la place est libérée.');
    }

    private function executerCreneau(Proposition $p): array
    {
        $c = ESBTPRdvCreneau::find($p->donnees['creneau_id']);
        if (! $c || (string) $c->updated_at !== $p->etat['creneau_updated_at'] || (bool) $c->ouvert !== (bool) $p->etat['ouvert']) {
            throw new PropositionPerimee('Le créneau a changé depuis la proposition.');
        }
        $ouvrir = $p->donnees['mode'] === 'ouvrir_creneau';
        if ($ouvrir && $this->fermeture->doitEtreFerme($c)) {
            throw new PropositionPerimee("Le réglage de fermeture à minuit interdit maintenant de rouvrir ce créneau.");
        }
        $c->update(['ouvert' => $ouvrir]);

        return [
            'message' => $ouvrir ? 'Créneau rouvert aux nouvelles réservations.' : 'Créneau fermé aux nouvelles réservations ; les rendez-vous existants sont conservés.',
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
            'model_type' => ESBTPRdvCreneau::class,
            'model_id' => $c->id,
        ];
    }

    private function reservationActiveDuDossier(string $type, int $id): bool
    {
        return ESBTPRdvReservation::query()->occupantes()
            ->where($type === 'candidature' ? 'candidature_id' : 'reinscription_demande_id', $id)
            ->exists();
    }

    private function retour(ESBTPRdvReservation $r, string $message): array
    {
        return [
            'message' => $message,
            'lien' => Route::has('esbtp.rendez-vous.recherche.index') ? route('esbtp.rendez-vous.recherche.index', ['q' => $r->nomComplet()], false) : null,
            'model_type' => ESBTPRdvReservation::class,
            'model_id' => $r->id,
            'details' => ['reservation_id' => $r->id, 'creneau_id' => $r->creneau_id],
        ];
    }
}
