<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\Setting;
use App\Services\RendezVous\FermetureAutomatiqueCreneaux;
use App\Services\RendezVous\GestionRendezVousCible;
use App\Services\RendezVous\RendezVousReglages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Gestes unitaires sur les rendez-vous. Contrairement au placement en lot,
 * Nanan ne choisit jamais « le prochain » dossier : les IDs viennent de
 * lire_rendez_vous_cible et sont affichés avant validation.
 */
class GererRendezVousCible extends ActionAgent
{
    public function __construct(private readonly GestionRendezVousCible $gestion) {}

    public function cle(): string
    {
        return 'gestion_rendez_vous_cible';
    }

    public function libelle(): string
    {
        return 'Préparation du rendez-vous…';
    }

    public function description(): string
    {
        return 'PROPOSE un geste ciblé sur les rendez-vous : programmer un dossier, reprogrammer ou annuler une réservation, fermer/rouvrir un créneau, ou activer/désactiver la fermeture automatique du jour à minuit. '
            .'Utilise les identifiants obtenus avec lire_rendez_vous_cible. Rien n’est écrit avant « Valider ». Pour renvoyer une convocation déjà traitée, utilise proposer_convocations_rdv.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'mode' => ['type' => 'string', 'enum' => ['programmer', 'reprogrammer', 'annuler', 'fermer_creneau', 'ouvrir_creneau', 'fermeture_jour_minuit']],
                'type_dossier' => ['type' => 'string', 'enum' => ['candidature', 'reinscription']],
                'dossier_id' => ['type' => 'integer'],
                'reservation_id' => ['type' => 'integer'],
                'creneau_id' => ['type' => 'integer', 'description' => 'Créneau cible pour programmer/reprogrammer, ou créneau à ouvrir/fermer.'],
                'creneau_vu' => ['type' => 'integer', 'description' => 'Pour reprogrammer/annuler : créneau actuellement lu, afin de détecter une modification concurrente.'],
                'actif' => ['type' => 'boolean', 'description' => 'Pour fermeture_jour_minuit : true pour activer, false pour désactiver.'],
            ],
            'required' => ['mode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $mode = (string) ($args['mode'] ?? '');
        $modes = ['programmer', 'reprogrammer', 'annuler', 'fermer_creneau', 'ouvrir_creneau', 'fermeture_jour_minuit'];
        if (! in_array($mode, $modes, true)) {
            return new Proposition(titre: 'Gérer un rendez-vous', resume: '', manques: ['Quel geste : programmer, reprogrammer, annuler, fermer/rouvrir un créneau ou régler la fermeture du jour à minuit ?']);
        }

        if (! $this->autorise($user, $mode)) {
            return new Proposition(titre: 'Gérer un rendez-vous', resume: '', manques: ['Vous n’avez pas le droit nécessaire pour ce geste de rendez-vous.']);
        }

        return match ($mode) {
            'programmer' => $this->preparerProgrammation($args),
            'reprogrammer' => $this->preparerReprogrammation($args),
            'annuler' => $this->preparerAnnulation($args),
            'fermer_creneau', 'ouvrir_creneau' => $this->preparerCreneau($args, $mode),
            'fermeture_jour_minuit' => $this->preparerFermetureMinuit($args),
        };
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $mode = $d['mode'];
        if (! $this->autorise($user, $mode)) {
            throw new PropositionPerimee('Vous n’avez plus le droit nécessaire pour ce geste de rendez-vous.');
        }

        if ($mode === 'fermeture_jour_minuit') {
            return $this->executerFermetureMinuit($proposition, $user);
        }

        $resultat = match ($mode) {
            'programmer' => $this->gestion->programmer($d['type_dossier'], $d['dossier_id'], $d['creneau_id']),
            'reprogrammer' => $this->gestion->reprogrammer($d['reservation_id'], $d['creneau_id'], (int) $user->id, $d['creneau_vu']),
            'annuler' => $this->gestion->annuler($d['reservation_id'], $d['creneau_vu']),
            'fermer_creneau' => $this->gestion->fermerCreneau($d['creneau_id']),
            'ouvrir_creneau' => $this->gestion->ouvrirCreneau($d['creneau_id']),
        };

        if (! ($resultat['ok'] ?? false)) {
            throw new PropositionPerimee(GestionRendezVousCible::message((string) ($resultat['code'] ?? 'introuvable')));
        }

        $reservation = $resultat['reservation'] ?? null;
        $creneau = $resultat['creneau'] ?? $reservation?->creneau;
        $message = match ($mode) {
            'programmer' => 'Rendez-vous programmé. La convocation a été mise en file.',
            'reprogrammer' => 'Rendez-vous reprogrammé. Une nouvelle convocation a été mise en file.',
            'annuler' => 'Rendez-vous annulé. La place est libérée.',
            'fermer_creneau' => 'Créneau fermé. Les réservations déjà existantes sont conservées.',
            'ouvrir_creneau' => 'Créneau rouvert aux nouvelles réservations.',
        };

        return [
            'message' => $message,
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null,
            'model_type' => $reservation ? ESBTPRdvReservation::class : ESBTPRdvCreneau::class,
            'model_id' => $reservation?->id ?? $creneau?->id,
            'details' => [
                'mode' => $mode,
                'reservation_id' => $reservation?->id,
                'creneau_id' => $creneau?->id,
                'date' => $creneau?->date?->toDateString(),
                'heure' => $creneau ? $creneau->heureDebutHi() : null,
            ],
        ];
    }

    private function preparerProgrammation(array $args): Proposition
    {
        $type = (string) ($args['type_dossier'] ?? '');
        $dossierId = filter_var($args['dossier_id'] ?? null, FILTER_VALIDATE_INT);
        $creneauId = filter_var($args['creneau_id'] ?? null, FILTER_VALIDATE_INT);
        $manques = [];
        if (! in_array($type, ['candidature', 'reinscription'], true)) $manques[] = 'Quel type de dossier : candidature ou réinscription ?';
        if ($dossierId === false || $dossierId === null) $manques[] = 'Quel dossier précis (dossier_id lu auparavant) ?';
        if ($creneauId === false || $creneauId === null) $manques[] = 'Sur quel créneau précis (creneau_id) ?';
        if ($manques !== []) return new Proposition(titre: 'Programmer un rendez-vous', resume: '', manques: $manques);

        $dossier = $type === 'candidature' ? ESBTPCandidature::find($dossierId) : ESBTPReinscriptionDemande::find($dossierId);
        $creneau = ESBTPRdvCreneau::withCount(['reservations as prises' => fn ($r) => $r->occupantes()])->find($creneauId);
        if (! $dossier || ! $creneau) return new Proposition(titre: 'Programmer un rendez-vous', resume: '', manques: ['Le dossier ou le créneau n’existe plus. Relisez les rendez-vous ciblés.']);

        return new Proposition(
            titre: 'Programmer ce rendez-vous',
            resume: sprintf('Le dossier n°%d sera placé le %s à %s.', $dossierId, $creneau->date->translatedFormat('d/m/Y'), $creneau->heureDebutHi()),
            tableau: ['colonnes' => ['Dossier', 'Créneau', 'Places'], 'lignes' => [["{$type} n°{$dossierId}", 'n°'.$creneau->id.' · '.$creneau->date->format('d/m/Y').' '.$creneau->heureDebutHi(), ($creneau->prises ?? 0).'/'.$creneau->capacite]]],
            avertissements: ['La convocation sera mise en file après la programmation.'],
            donnees: ['mode' => 'programmer', 'type_dossier' => $type, 'dossier_id' => (int) $dossierId, 'creneau_id' => (int) $creneauId],
            etat: ['creneau_ouvert' => (bool) $creneau->ouvert, 'prises' => (int) ($creneau->prises ?? 0)],
            risque: 'eleve',
        );
    }

    private function preparerReprogrammation(array $args): Proposition
    {
        $reservationId = filter_var($args['reservation_id'] ?? null, FILTER_VALIDATE_INT);
        $creneauId = filter_var($args['creneau_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reservationId === false || $reservationId === null || $creneauId === false || $creneauId === null) {
            return new Proposition(titre: 'Reprogrammer un rendez-vous', resume: '', manques: ['Donnez reservation_id et le nouveau creneau_id, lus avec lire_rendez_vous_cible.']);
        }
        $r = ESBTPRdvReservation::with('creneau')->find($reservationId);
        $cible = ESBTPRdvCreneau::find($creneauId);
        if (! $r || ! $r->creneau || ! $cible) return new Proposition(titre: 'Reprogrammer un rendez-vous', resume: '', manques: ['Le rendez-vous ou le nouveau créneau n’existe plus.']);

        return new Proposition(
            titre: 'Reprogrammer '.$r->nomComplet(),
            resume: sprintf('Le rendez-vous passera du %s %s au %s %s.', $r->creneau->date->format('d/m/Y'), $r->creneau->heureDebutHi(), $cible->date->format('d/m/Y'), $cible->heureDebutHi()),
            avertissements: ['Une nouvelle convocation sera mise en file après le déplacement.'],
            donnees: ['mode' => 'reprogrammer', 'reservation_id' => (int) $r->id, 'creneau_id' => (int) $cible->id, 'creneau_vu' => (int) $r->creneau_id],
            etat: ['statut' => $r->statut?->value, 'creneau_id' => (int) $r->creneau_id],
            risque: 'eleve',
        );
    }

    private function preparerAnnulation(array $args): Proposition
    {
        $reservationId = filter_var($args['reservation_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reservationId === false || $reservationId === null) return new Proposition(titre: 'Annuler un rendez-vous', resume: '', manques: ['Quel reservation_id précis ?']);
        $r = ESBTPRdvReservation::with('creneau')->find($reservationId);
        if (! $r || ! $r->creneau) return new Proposition(titre: 'Annuler un rendez-vous', resume: '', manques: ['Ce rendez-vous n’existe plus.']);

        $avertissements = ['La place sera libérée.'];
        if ($r->convocation_statut && $r->convocation_statut->value !== 'en_attente') {
            $avertissements[] = 'Une convocation a déjà été traitée : son historique est conservé. Prévenez la famille si nécessaire.';
        }
        return new Proposition(
            titre: 'Annuler le rendez-vous de '.$r->nomComplet(),
            resume: sprintf('Rendez-vous du %s à %s.', $r->creneau->date->format('d/m/Y'), $r->creneau->heureDebutHi()),
            avertissements: $avertissements,
            donnees: ['mode' => 'annuler', 'reservation_id' => (int) $r->id, 'creneau_vu' => (int) $r->creneau_id],
            etat: ['statut' => $r->statut?->value, 'creneau_id' => (int) $r->creneau_id],
            risque: 'eleve',
        );
    }

    private function preparerCreneau(array $args, string $mode): Proposition
    {
        $id = filter_var($args['creneau_id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null) return new Proposition(titre: 'Gérer un créneau', resume: '', manques: ['Quel creneau_id précis ?']);
        $c = ESBTPRdvCreneau::withCount(['reservations as prises' => fn ($r) => $r->occupantes()])->find($id);
        if (! $c) return new Proposition(titre: 'Gérer un créneau', resume: '', manques: ['Ce créneau n’existe plus.']);
        $ouvrir = $mode === 'ouvrir_creneau';
        if ((bool) $c->ouvert === $ouvrir) return Proposition::sansObjet($ouvrir ? 'Rouvrir le créneau' : 'Fermer le créneau', $ouvrir ? 'Ce créneau est déjà ouvert.' : 'Ce créneau est déjà fermé.');

        return new Proposition(
            titre: $ouvrir ? 'Rouvrir ce créneau' : 'Fermer ce créneau',
            resume: sprintf('Créneau n°%d · %s %s · %d réservation(s) existante(s).', $c->id, $c->date->format('d/m/Y'), $c->heureDebutHi(), (int) ($c->prises ?? 0)),
            avertissements: $ouvrir ? ['La réouverture sera refusée si la journée est déjà fermée par la règle de minuit.'] : ['Les réservations existantes sont conservées ; seules les nouvelles prises sont bloquées.'],
            donnees: ['mode' => $mode, 'creneau_id' => (int) $c->id],
            etat: ['ouvert' => (bool) $c->ouvert, 'prises' => (int) ($c->prises ?? 0)],
            risque: 'moyen',
        );
    }

    private function preparerFermetureMinuit(array $args): Proposition
    {
        if (! array_key_exists('actif', $args) || ! is_bool($args['actif'])) {
            return new Proposition(titre: 'Fermeture du jour à minuit', resume: '', manques: ['Faut-il activer ou désactiver la fermeture du jour à minuit ?']);
        }
        $reglage = Setting::where('key', RendezVousReglages::FERMER_JOUR_A_MINUIT)->first();
        if (! $reglage) {
            return new Proposition(titre: 'Fermeture du jour à minuit', resume: '', manques: ['Le réglage n’existe pas encore sur cette instance : appliquez d’abord les migrations.']);
        }
        $avant = in_array(strtolower((string) $reglage->value), ['1', 'true'], true);
        $apres = (bool) $args['actif'];
        if ($avant === $apres) {
            return Proposition::sansObjet('Fermeture du jour à minuit', $apres ? 'La fermeture automatique est déjà activée.' : 'La fermeture automatique est déjà désactivée.');
        }

        return new Proposition(
            titre: $apres ? 'Activer la fermeture du jour à minuit' : 'Désactiver la fermeture du jour à minuit',
            resume: $apres
                ? 'Dès 00:00, les créneaux datés du jour ne recevront plus de nouvelle réservation. Les rendez-vous déjà pris seront conservés.'
                : 'Le jour courant pourra de nouveau être proposé selon les délais normaux, à condition que ses créneaux soient ouverts.',
            avertissements: $apres ? ['La validation ferme immédiatement les créneaux du jour et passés encore ouverts.'] : ['La désactivation ne rouvre pas automatiquement les créneaux déjà fermés. Utilisez « rouvrir le créneau » si nécessaire.'],
            donnees: ['mode' => 'fermeture_jour_minuit', 'actif' => $apres],
            etat: ['value' => (string) $reglage->value, 'updated_at' => optional($reglage->updated_at)->toISOString()],
            risque: 'moyen',
        );
    }

    private function executerFermetureMinuit(Proposition $proposition, $user): array
    {
        $actif = (bool) $proposition->donnees['actif'];
        DB::transaction(function () use ($proposition, $actif, $user) {
            $reglage = Setting::where('key', RendezVousReglages::FERMER_JOUR_A_MINUIT)->lockForUpdate()->first();
            if (! $reglage) throw new PropositionPerimee('Le réglage n’existe plus.');
            $etat = ['value' => (string) $reglage->value, 'updated_at' => optional($reglage->updated_at)->toISOString()];
            if ($etat !== $proposition->etat) throw new PropositionPerimee('Le réglage a changé depuis la proposition. Relisez-le avant de valider.');
            $reglage->update(['value' => $actif ? '1' : '0', 'updated_by' => $user->id]);
        });

        $fermes = $actif ? app(FermetureAutomatiqueCreneaux::class)->fermer() : 0;

        return [
            'message' => $actif
                ? "Fermeture du jour à minuit activée. {$fermes} créneau(x) du jour/passé(s) fermé(s) immédiatement."
                : 'Fermeture du jour à minuit désactivée.',
            'lien' => Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false).'#reglages' : null,
            'model_type' => Setting::class,
            'model_id' => Setting::where('key', RendezVousReglages::FERMER_JOUR_A_MINUIT)->value('id'),
            'details' => ['mode' => 'fermeture_jour_minuit', 'actif' => $actif, 'creneaux_fermes' => $fermes],
        ];
    }

    private function autorise($user, string $mode): bool
    {
        return in_array($mode, ['reprogrammer', 'annuler'], true)
            ? ($user->can('inscriptions.rdv.accueil') || $user->can('inscriptions.rdv.manage'))
            : $user->can('inscriptions.rdv.manage');
    }
}
