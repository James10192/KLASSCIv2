<?php

namespace App\Domain\Assistant\Actions\RendezVous;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Http\Requests\CLI\RenvoyerConvocationsRequest;
use App\Models\ESBTPRdvReservation;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\Renvoi\RenvoiConvocationsCiblees;
use App\Services\Verification\MasqueContact;
use Illuminate\Support\Facades\Route;

/**
 * Propose un geste sur les convocations de rendez-vous, par les mêmes services
 * que l'écran et le CLI :
 *
 *  - envoyer   : un paquet de convocations EN ATTENTE part maintenant (FileConvocationsRdv::envoyerUnPaquet),
 *                restreint aux réservations montrées ;
 *  - remettre  : « Convoquer les non suivies » / « Relancer les échecs » (FileConvocationsRdv::remettreEnAttente) ;
 *  - renvoyer  : renvoi CIBLÉ de 1 à 50 réservations, avec motif (RenvoiConvocationsCiblees).
 *
 * Chaque mode écrit à des familles : la proposition donne le nombre exact de
 * destinataires. Remettre et renvoyer reconvoquent des familles qui ont pu déjà
 * recevoir un message : ils exigent en plus que la personne l'ait confirmé
 * explicitement (`confirmation_renvoi`), jamais déduit par Nanan.
 */
class ConvocationsRdv extends ActionAgent
{
    /** Comme le CLI : un paquet de 50, 25 secondes au plus. */
    private const PAQUET = 50;

    private const BUDGET_SECONDES = 25.0;

    private const LIGNES_MONTREES = 50;

    public function __construct(private FileConvocationsRdv $file, private RenvoiConvocationsCiblees $renvoi)
    {
    }

    public function cle(): string
    {
        return 'convocations_rdv';
    }

    public function description(): string
    {
        return 'PROPOSE un geste sur les convocations de rendez-vous d\'inscription. mode « envoyer » : envoie maintenant les convocations en attente (un paquet de '.self::PAQUET.'). '
            .'mode « remettre » : remet en attente les réservations « inconnues » (d\'avant le suivi) ou les « echecs », la tâche planifiée les envoie ensuite (limite = les plus anciennes seulement). '
            .'mode « renvoyer » : renvoi ciblé de 1 à '.RenvoyerConvocationsRequest::MAX.' réservations (identifiants), avec un motif. '
            .'remettre et renvoyer reconvoquent des familles : passe confirmation_renvoi=true SEULEMENT si la personne l\'a confirmé explicitement. Rien n\'est envoyé avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'mode' => ['type' => 'string', 'enum' => ['envoyer', 'remettre', 'renvoyer']],
                'quoi' => ['type' => 'string', 'enum' => ['inconnues', 'echecs'], 'description' => 'mode remettre : lesquelles.'],
                'limite' => ['type' => 'integer', 'description' => 'mode remettre : les N plus anciennes seulement (vérifier un premier envoi).'],
                'reservations' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'mode renvoyer : identifiants des réservations.'],
                'motif' => ['type' => 'string', 'enum' => RenvoyerConvocationsRequest::MOTIFS, 'description' => 'mode renvoyer : pourquoi.'],
                'confirmation_renvoi' => ['type' => 'boolean', 'description' => 'La personne a confirmé explicitement reconvoquer ces familles.'],
            ],
            'required' => ['mode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        return match ($args['mode'] ?? null) {
            'envoyer' => $this->preparerEnvoi(),
            'remettre' => $this->preparerRemise($args),
            'renvoyer' => $this->preparerRenvoi($args),
            default => new Proposition(titre: 'Convocations', resume: '', manques: ['Quel geste : envoyer les convocations en attente, remettre en attente (inconnues ou échecs), ou renvoyer à des réservations précises ?']),
        };
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('inscriptions.rdv.manage')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de gérer les rendez-vous.");
        }
        $d = $proposition->donnees;
        $lien = Route::has('esbtp.rendez-vous.index') ? route('esbtp.rendez-vous.index', [], false) : null;

        if ($d['mode'] === 'envoyer') {
            $r = $this->file->envoyerUnPaquet(self::PAQUET, self::BUDGET_SECONDES, $d['ids']);
            if ($r['en_cours']) {
                throw new PropositionPerimee('Un autre envoi de convocations est en cours.');
            }
            $message = sprintf('%d convocation(s) envoyée(s), %d échec(s).', $r['envoyees'], $r['echecs'])
                .($r['bloque'] !== null ? ' Envoi interrompu : '.$r['bloque'] : '')
                .sprintf(' %d restent en attente.', $r['restantes']);

            return ['message' => $message, 'lien' => $lien, 'model_type' => ESBTPRdvReservation::class, 'details' => $r];
        }

        if ($d['mode'] === 'remettre') {
            $remises = $this->file->remettreEnAttente($d['quoi'], null, $d['ids']);

            return [
                'message' => sprintf('%d convocation(s) remise(s) en attente : la tâche planifiée les enverra dans les minutes qui viennent.', $remises),
                'lien' => $lien, 'model_type' => ESBTPRdvReservation::class, 'details' => ['remises' => $remises, 'a_envoyer' => $this->file->enAttente()],
            ];
        }

        $r = $this->renvoi->executer($d['ids'], $d['motif'], $user);

        return [
            'message' => sprintf('%d convocation(s) remise(s) en file pour renvoi : la tâche planifiée les enverra.', $r['remises'])
                .($r['non_eligibles'] !== [] ? ' '.count($r['non_eligibles']).' devenue(s) non éligible(s) entre-temps.' : ''),
            'lien' => $lien, 'model_type' => ESBTPRdvReservation::class, 'details' => $r,
        ];
    }

    private function preparerEnvoi(): Proposition
    {
        $titre = 'Envoyer les convocations en attente';
        $paquet = $this->file->paquetEnAttente(self::PAQUET)->with('creneau')->get();
        if ($paquet->isEmpty()) {
            return new Proposition(titre: $titre, resume: '', manques: ['Aucune convocation en attente d\'envoi.']);
        }
        $total = $this->file->enAttente();
        $avertissements = [];
        if ($total > $paquet->count()) {
            $avertissements[] = ($total - $paquet->count()).' autre(s) en attente partiront avec la tâche planifiée ou un prochain envoi.';
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d famille(s) reçoivent leur convocation maintenant.', $paquet->count()),
            tableau: $this->tableau($paquet),
            avertissements: $avertissements,
            donnees: ['mode' => 'envoyer', 'ids' => $paquet->pluck('id')->map(fn ($id) => (int) $id)->all()],
            etat: ['statuts' => $this->statuts($paquet)],
            risque: 'eleve',
        );
    }

    private function preparerRemise(array $args): Proposition
    {
        $titre = 'Remettre des convocations en attente';
        $quoi = $args['quoi'] ?? null;
        $limite = $args['limite'] ?? null;
        $manques = [];
        if (! in_array($quoi, ['inconnues', 'echecs'], true)) {
            $manques[] = 'Lesquelles : les réservations d\'avant le suivi (« inconnues ») ou les envois échoués (« echecs ») ?';
        }
        if ($limite !== null && (filter_var($limite, FILTER_VALIDATE_INT) === false || (int) $limite < 1)) {
            $manques[] = 'La limite doit être un nombre entier positif.';
        }
        if (($args['confirmation_renvoi'] ?? false) !== true) {
            $manques[] = 'Ces familles ont pu déjà recevoir un message : la personne confirme-t-elle explicitement les reconvoquer ?';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $requete = $this->file->aRemettre($quoi)->orderBy('id');
        $ids = ($limite === null ? $requete->pluck('id') : $requete->limit((int) $limite)->pluck('id'))->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return new Proposition(titre: $titre, resume: '', manques: [$quoi === 'echecs' ? 'Aucun envoi en échec.' : 'Aucune réservation d\'avant le suivi à convoquer.']);
        }
        $reservations = ESBTPRdvReservation::with('creneau')->whereIn('id', array_slice($ids, 0, self::LIGNES_MONTREES))->orderBy('id')->get();
        $avertissements = ['Elles partiront au prochain passage de la tâche planifiée (toutes les 5 minutes), sans autre validation.'];
        if (count($ids) > self::LIGNES_MONTREES) {
            $avertissements[] = 'Le tableau montre les '.self::LIGNES_MONTREES.' premières sur '.count($ids).'.';
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d convocation(s) remise(s) en attente (%s) : %d famille(s) seront reconvoquées.', count($ids), $quoi === 'echecs' ? 'envois échoués' : 'réservations d\'avant le suivi', count($ids)),
            tableau: $this->tableau($reservations),
            avertissements: $avertissements,
            donnees: ['mode' => 'remettre', 'quoi' => $quoi, 'ids' => $ids],
            etat: ['ids' => $ids],
            risque: 'eleve',
        );
    }

    private function preparerRenvoi(array $args): Proposition
    {
        $titre = 'Renvoyer des convocations';
        $ids = array_values(array_unique(array_map('intval', array_filter((array) ($args['reservations'] ?? []), fn ($v) => is_numeric($v)))));
        $motif = (string) ($args['motif'] ?? '');
        $manques = [];
        if ($ids === []) {
            $manques[] = 'À quelles réservations renvoyer la convocation ? Donne leurs identifiants.';
        } elseif (count($ids) > RenvoyerConvocationsRequest::MAX) {
            $manques[] = 'Au plus '.RenvoyerConvocationsRequest::MAX.' réservations par renvoi : un renvoi est ciblé, jamais toute l\'école.';
        }
        if (! in_array($motif, RenvoyerConvocationsRequest::MOTIFS, true)) {
            $manques[] = 'Pourquoi ce renvoi : '.implode(', ', RenvoyerConvocationsRequest::MOTIFS).' ?';
        }
        if (($args['confirmation_renvoi'] ?? false) !== true) {
            $manques[] = 'Ces familles ont déjà été convoquées : la personne confirme-t-elle explicitement le renvoi ?';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $simulation = $this->renvoi->simuler($ids);
        $eligibles = array_values(array_map(fn ($l) => $l['id'], array_filter($simulation, fn ($l) => $l['eligible'])));
        $refusees = array_filter($simulation, fn ($l) => ! $l['eligible']);
        if ($eligibles === []) {
            return new Proposition(titre: $titre, resume: '', manques: ['Aucune de ces réservations ne peut être reconvoquée : '
                .implode(', ', array_map(fn ($l) => "n° {$l['id']} ({$l['raison']})", $simulation)).'.']);
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d famille(s) seront reconvoquée(s) (motif : %s).', count($eligibles), $motif),
            tableau: [
                'colonnes' => ['Réservation', 'Adresse', 'Convocation actuelle', 'Renvoi'],
                'lignes' => array_map(fn ($l) => ['n° '.$l['id'], (string) ($l['email_masque'] ?? '—'), (string) ($l['statut_convocation'] ?? '—'),
                    $l['eligible'] ? 'Oui' : 'Non ('.$l['raison'].')'], $simulation),
            ],
            avertissements: array_filter([
                'Elles partiront au prochain passage de la tâche planifiée (toutes les 5 minutes), sans autre validation.',
                $refusees !== [] ? count($refusees).' réservation(s) écartée(s) : voir la colonne « Renvoi ».' : null,
            ]),
            donnees: ['mode' => 'renvoyer', 'ids' => $eligibles, 'motif' => $motif],
            etat: ['simulation' => $simulation],
            risque: 'eleve',
        );
    }

    private function tableau($reservations): array
    {
        return [
            'colonnes' => ['Réservation', 'Famille', 'Rendez-vous', 'Adresse'],
            'lignes' => $reservations->map(fn (ESBTPRdvReservation $r) => [
                'n° '.$r->id, $r->nomComplet(),
                $r->creneau ? $r->creneau->date?->format('d/m/Y').' '.$r->creneau->heureDebutHi() : '—',
                $r->email ? MasqueContact::email($r->email) : '—',
            ])->values()->all(),
        ];
    }

    private function statuts($reservations): array
    {
        return $reservations->mapWithKeys(fn (ESBTPRdvReservation $r) => [$r->id => $r->convocation_statut?->value])->all();
    }
}
