<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\CurseurSupport;
use App\Domain\Support\Models\DemandeSuivie;
use App\Domain\Support\Services\AvertirDuRetourDuSupport;
use App\Models\User;
use App\Services\Care\ClientMasterSupport;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Throwable;

/**
 * Lit au Master les demandes de l'ecole modifiees depuis le dernier passage,
 * et avertit le rapporteur quand le support a repondu ou que la demande est
 * resolue / fermee.
 *
 * Idempotent : la decision se prend en comparant au dernier etat vu
 * (DemandeSuivie), jamais a l'heure du passage. Relire une demande deja vue
 * ne renotifie donc rien — d'ou un recouvrement de quelques minutes sur le
 * curseur, qui absorbe les ecarts d'horloge sans risque de doublon.
 *
 * Premier passage : on releve l'etat de toutes les demandes SANS avertir. Une
 * ecole qui active le suivi ne doit pas recevoir d'un coup l'historique de
 * toutes ses demandes.
 *
 * Une erreur du Master remonte avant que le curseur ne bouge : le passage
 * suivant reprend au meme endroit.
 *
 * Curseur trop ancien (instance arretee, Master injoignable des semaines) :
 * le Master refuse un `mis_a_jour_depuis` au-dela de sa limite (30 jours) et
 * repondrait 422 a chaque passage, pour toujours. Au-dela de
 * `support.suivi.depuis_jours_max`, on relit donc sans filtre, en s'arretant
 * a l'ancien curseur cote instance ; rien n'est reinitialise, et ce qui est
 * deja connu dans DemandeSuivie n'est pas renotifie.
 */
class SuivreLesReponsesDuSupport
{
    public const STATUTS_CLOTURES = ['RESOLU', 'FERME'];

    private const PAGES_MAX = 50;

    private const RECOUVREMENT_MINUTES = 5;

    public function __construct(
        private readonly ClientMasterSupport $master,
        private readonly AvertirDuRetourDuSupport $avertir,
    ) {
    }

    /** @return array{initialisation: bool, lues: int, averties: int} */
    public function executer(): array
    {
        $initialisation = ! CurseurSupport::existe(CurseurSupport::SUIVI_DEMANDES);
        $curseur = CurseurSupport::lire(CurseurSupport::SUIVI_DEMANDES);
        $depuis = $initialisation || $curseur === null ? null : $curseur->copy()->subMinutes(self::RECOUVREMENT_MINUTES);
        // Le filtre envoye au Master ; l'arret de lecture, lui, reste $depuis.
        $filtre = $depuis !== null && $depuis->gte(now()->subDays((int) config('support.suivi.depuis_jours_max', 29)))
            ? $depuis
            : null;

        $plusRecente = $curseur;
        $lues = 0;
        $averties = 0;

        for ($page = 1; $page <= self::PAGES_MAX; $page++) {
            $reponse = $this->master->demandesModifiees($filtre?->toIso8601String(), $page);
            $resumes = (array) ($reponse['data'] ?? []);
            $plusAnciennePage = null;

            foreach ($resumes as $resume) {
                if (! is_array($resume)) {
                    continue;
                }
                $lues++;
                $maj = self::date($resume['mis_a_jour_le'] ?? null);
                if ($maj !== null) {
                    $plusRecente = $plusRecente === null || $maj->gt($plusRecente) ? $maj : $plusRecente;
                    $plusAnciennePage = $plusAnciennePage === null || $maj->lt($plusAnciennePage) ? $maj : $plusAnciennePage;
                }
                $averties += (int) $this->traiter($resume, ! $initialisation);
            }

            // Le Master trie du plus recent au plus ancien : passee la date de
            // depart, les pages suivantes sont deja connues. Vaut aussi si le
            // filtre `mis_a_jour_depuis` n'est pas (encore) applique au Master.
            $finDesDonnees = $page >= (int) ($reponse['meta']['pages'] ?? 1) || $resumes === [];
            $depasse = $depuis !== null && $plusAnciennePage !== null && $plusAnciennePage->lt($depuis);
            if ($finDesDonnees || $depasse) {
                break;
            }
        }

        CurseurSupport::poser(CurseurSupport::SUIVI_DEMANDES, $plusRecente ?? now());

        return ['initialisation' => $initialisation, 'lues' => $lues, 'averties' => $averties];
    }

    /** Rend vrai si le rapporteur a ete averti. */
    private function traiter(array $resume, bool $avertir): bool
    {
        $reference = (string) ($resume['reference'] ?? '');
        if (preg_match('/^KC-\d{4}-\d{6,}$/', $reference) !== 1) {
            return false;
        }

        $statut = isset($resume['statut']['code']) ? (string) $resume['statut']['code'] : null;
        $reponseLe = self::date($resume['derniere_reponse_support_le'] ?? null);
        // Repli tant que le Master n'expose pas `derniere_reponse_support_le` :
        // la derniere reponse publique, si c'est le support qui l'a ecrite.
        if ($reponseLe === null && ($resume['derniere_reponse']['auteur'] ?? null) === 'SUPPORT') {
            $reponseLe = self::date($resume['derniere_reponse']['le'] ?? null);
        }

        $suivie = DemandeSuivie::firstOrNew(['reference' => $reference]);
        $aRepondu = $reponseLe !== null
            && ($suivie->derniere_reponse_support_le === null || $reponseLe->gt($suivie->derniere_reponse_support_le));
        $cloturee = in_array($statut, self::STATUTS_CLOTURES, true)
            && ! in_array($suivie->statut_code, self::STATUTS_CLOTURES, true);

        $rapporteur = User::find((int) ($resume['rapporteur']['id'] ?? 0));
        $averti = $avertir && $rapporteur !== null && ($aRepondu || $cloturee);

        // L'etat vu s'enregistre AVANT d'avertir : si l'avertissement leve,
        // le passage suivant ne renvoie pas une notification deja partie.
        $suivie->fill([
            'user_id' => $rapporteur?->getKey(),
            'statut_code' => $statut,
            'derniere_reponse_support_le' => $aRepondu ? $reponseLe : $suivie->derniere_reponse_support_le,
            'mis_a_jour_le' => self::date($resume['mis_a_jour_le'] ?? null),
            'averti_le' => $averti ? now() : $suivie->averti_le,
        ])->save();

        if ($averti) {
            $this->avertir->executer($rapporteur, $resume, $aRepondu, $cloturee);
        }

        return $averti;
    }

    /** Une date du Master, a l'heure de l'instance ; absente ou illisible → null. */
    private static function date(mixed $iso): ?CarbonInterface
    {
        if (! is_string($iso) || trim($iso) === '') {
            return null;
        }

        try {
            return Carbon::parse($iso)->setTimezone(config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }
}
