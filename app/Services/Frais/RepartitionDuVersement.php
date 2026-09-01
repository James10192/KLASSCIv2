<?php

namespace App\Services\Frais;

use App\Exceptions\AllocationIncoherenteException;
use App\Exceptions\RepartitionRefuseeException;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;

/**
 * Ou va l'argent, decide AU MOMENT OU IL ENTRE.
 *
 * `esbtp_paiements` ne porte qu'une seule `frais_category_id`. Le caissier qui
 * encaisse 255 000 F couvrant trois frais devait donc en designer un seul, et le
 * calcul du restant — `max(0, du - paye)` par categorie — ECRETAIT l'excedent.
 *
 * {@see RepartitionTropPercu} reconstitue cette information APRES COUP. Rien ne
 * le declenche : ni le planificateur, ni l'ecran. Les montants par frais ne sont
 * donc justes que si quelqu'un se souvient de lancer une commande, indefiniment.
 * Pire, le repli « un versement sans allocation garde son comportement
 * historique » fait une machine a deux etats dont le mode se fixe hors bande :
 * le meme argent donne deux reponses selon qu'on a joue la commande ou non.
 *
 * Ce service ferme la question a la source. Tout versement encaisse porte ses
 * allocations des sa creation, l'invariant « la somme des allocations vaut le
 * montant » tient dans TOUS les cas, et la reprise d'historique redevient ce
 * qu'elle aurait toujours du etre : un outil ponctuel pour le passe.
 *
 * DEUX DECISIONS QUI SE DISCUTENT.
 *
 * 1. On REFUSE l'encaissement qui depasse tout ce que l'etudiant doit, la ou la
 *    reprise d'historique en faisait une avance. La reprise n'avait personne a
 *    qui demander ; la caisse, si. Une avance doit etre une decision, pas un
 *    effet de bord d'une somme mal saisie.
 *
 * 2. Un frais dont le tarif n'est pas connu n'a AUCUN plafond. Le zero veut dire
 *    « inconnu », pas « rien a payer » (cf. `.claude/rules/rien-en-dur.md`), et
 *    une ecole qui n'a pas fini de configurer ses frais doit pouvoir encaisser.
 *    Ne pas confondre avec un depot en nature : celui-la reclame reellement
 *    zero, et se refuse.
 */
class RepartitionDuVersement
{
    public function __construct(
        private readonly ServirLesFrais $service,
        private readonly RefletAllocationsSurAvoirs $reflet,
    ) {
    }

    /**
     * Ce que chaque frais de cette inscription reclame ENCORE.
     *
     * Le « deja paye » inclut les versements EN ATTENTE de validation. Sans
     * cela, deux versements successifs de 300 000 F sur une dette de 300 000 F
     * passeraient tous les deux : le premier, non encore valide, serait invisible
     * au garde-fou du second.
     *
     * Un frais absent de ce tableau n'a pas de plafond connu — soit il n'a pas de
     * souscription, soit sa souscription ne dit pas ce qu'il coute.
     *
     * LIMITE ASSUMEE : ce reste est lu hors verrou. Deux caissiers qui encaissent
     * le meme etudiant a la meme seconde lisent donc le meme reste, et la somme
     * des deux peut le depasser. Le verrouiller couterait une contention sur la
     * table la plus sollicitee, au moment ou elle l'est le plus (jour de
     * rentree), pour un ecart borne au montant du versement concurrent — que la
     * reconciliation de caisse existe precisement pour rattraper. On prefere une
     * caisse qui encaisse a une caisse qui se bloque.
     *
     * `$saufPaiementId` retire UN versement du deja-paye. Il n'a qu'un usage :
     * reventiler un versement deja enregistre. Sans lui, ce versement se
     * compare a lui-meme — la scolarite qu'il vient de solder ressort a zero de
     * reste, et il ne peut plus y etre impute. Le mecanisme se refuserait sa
     * propre ecriture.
     *
     * @return array<int, float> frais_category_id => reste du (>= 0)
     */
    public function resteConnuParFrais(int $inscriptionId, ?int $saufPaiementId = null): array
    {
        $paye = ESBTPPaiement::netPaidByCategory($inscriptionId, true, $saufPaiementId);

        $souscriptions = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->with('fraisCategory')
            ->get()
            // L'ordre de service est celui que l'ecole a range. Aucune priorite
            // n'est codee ici : elle imposerait la reponse d'un etablissement a
            // tous les autres.
            ->sortBy(fn ($s) => [$s->fraisCategory->sort_order ?? 9999, $s->fraisCategory->id ?? 0])
            ->values();

        $reste = [];

        foreach ($souscriptions as $sub) {
            // Montant non defini : l'ecole n'a pas encore dit ce que ce frais
            // coute. On ne lui oppose donc aucun plafond.
            if ($sub->montantNonDefini()) {
                continue;
            }

            $du = (float) $sub->chargedAmount();
            $dejaPaye = (float) ($paye[$sub->frais_category_id] ?? 0);

            $reste[(int) $sub->frais_category_id] = max(0.0, round($du - $dejaPaye, 2));
        }

        return $reste;
    }

    /**
     * Traduit ce que le caissier a saisi en lignes d'imputation.
     *
     * @param  array<int|string, mixed>|null  $saisie  Une repartition explicite
     *                                                 (frais => montant). Absente,
     *                                                 le service applique la regle
     *                                                 de l'ecole depuis le frais
     *                                                 designe.
     * @param  int|null  $saufPaiementId  Le versement a ne pas compter dans le
     *                                     deja-paye. Renseigne uniquement par
     *                                     {@see self::reventiler()}.
     * @return array<int, float> frais_category_id => montant
     *
     * @throws RepartitionRefuseeException
     */
    public function calculer(
        int $inscriptionId,
        float $montant,
        ?int $fraisDesigne,
        ?array $saisie = null,
        ?int $saufPaiementId = null
    ): array {
        $montant = round($montant, 2);

        // Un versement nul n'impute rien. Ecrire des lignes a zero ne dirait
        // rien de plus que le versement, et l'invariant tient trivialement.
        if ($montant <= ServirLesFrais::EPSILON) {
            return [];
        }

        $reste = $this->resteConnuParFrais($inscriptionId, $saufPaiementId);

        if ($saisie !== null && $saisie !== []) {
            return $this->verifierLaSaisie($saisie, $montant, $reste, $fraisDesigne);
        }

        if ($fraisDesigne === null) {
            return [];
        }

        // Frais au tarif inconnu : rien a plafonner, et rien qui permette de
        // repartir a partir de lui. Le versement lui revient en entier, comme
        // avant. Le refuser rendrait inencaissable un frais que l'ecole n'a pas
        // fini de configurer.
        if (! array_key_exists($fraisDesigne, $reste)) {
            return [$fraisDesigne => $montant];
        }

        $servi = $this->service->servir($montant, $reste, $fraisDesigne);
        $allocations = $servi['allocations'];

        // Ce versement n'eteint AUCUNE dette : tout ce qui est configure est
        // deja couvert. C'est le cas que le garde-fou existe pour attraper —
        // deux versements successifs de 300 000 F sur une dette de 300 000 F,
        // le premier encore en attente de validation et donc invisible sans le
        // comptage des « en attente ».
        //
        // On ne le transforme pas en avance d'office : une avance se decide, et
        // le caissier qui la veut la designe explicitement.
        if ($allocations === []) {
            throw new RepartitionRefuseeException(sprintf(
                'Cet etudiant ne doit plus rien sur les frais configures : ce versement de %s FCFA '
                .'n\'eteint aucune dette. S\'il s\'agit d\'une avance deliberee, indiquez sur quel '
                .'frais l\'imputer ; sinon, corrigez le montant.',
                $this->fcfa($montant)
            ));
        }

        // Tous les frais servis et il reste de l'argent : c'est une avance. Elle
        // demeure sur le frais que le caissier a designe — la MEME regle que
        // {@see RepartitionTropPercu} applique a l'historique, pour que la reprise
        // et l'encaissement n'ecrivent jamais deux comptabilites differentes.
        if ($servi['reliquat'] > ServirLesFrais::EPSILON) {
            $allocations[$fraisDesigne] = round(
                ($allocations[$fraisDesigne] ?? 0) + $servi['reliquat'],
                2
            );
        }

        return $allocations;
    }

    /**
     * Ecrit les lignes d'un versement qui vient d'etre encaisse.
     */
    public function ecrire(ESBTPPaiement $paiement, array $allocations): void
    {
        // Seule difference de fond avec {@see self::remplacer()} : ici, ne rien
        // imputer est LEGITIME. Un versement nul n'a rien a repartir, et des
        // lignes a zero ne diraient rien de plus que le versement lui-meme.
        // Corriger une ventilation vers rien du tout, en revanche, est un refus.
        if ($allocations === []) {
            return;
        }

        $this->remplacer($paiement, $allocations);
    }

    /**
     * Ou irait ce versement DEJA ENREGISTRE si on le reventilait ainsi.
     *
     * Seule porte d'entree de la correction d'imputation, et c'est voulu : elle
     * garantit l'exclusion du versement lui-meme. Un appelant qui passerait par
     * {@see self::calculer()} en oubliant `$saufPaiementId` obtiendrait un
     * calcul ou le versement se compare a lui-meme, et refuserait toute
     * correction sur un frais qu'il a deja soldé.
     *
     * Le frais designe reste celui que porte le versement : c'est lui qui a le
     * droit de recevoir plus qu'il ne reclame, exactement comme a
     * l'encaissement. Le changer ici ferait qu'une avance encaissee sur A
     * deviendrait irrecevable des qu'on ouvre l'ecran de correction.
     *
     * @param  array<int|string, mixed>|null  $saisie
     * @return array<int, float>
     *
     * @throws RepartitionRefuseeException
     */
    public function reventiler(ESBTPPaiement $paiement, ?array $saisie = null): array
    {
        return $this->calculer(
            (int) $paiement->inscription_id,
            (float) $paiement->montant,
            $paiement->frais_category_id !== null ? (int) $paiement->frais_category_id : null,
            $saisie,
            (int) $paiement->id
        );
    }

    /**
     * Pose l'imputation d'un versement, en remplacement de toute precedente.
     *
     * La SUPPRESSION des frais absents de la nouvelle ventilation est le point
     * qui compte : corriger « tout sur la scolarite » en « tout sur la ramette »
     * en se contentant d'ecrire la nouvelle ligne laisserait celle de scolarite
     * en place, et le versement compterait double dans les totaux par frais —
     * l'invariant verifie une ligne plus haut serait faux immediatement apres.
     *
     * A la creation, il n'y a rien a supprimer et la requete ne touche aucune
     * ligne : {@see self::ecrire()} passe donc par ici sans precaution
     * particuliere.
     *
     * @param  array<int, float>  $allocations
     */
    public function remplacer(ESBTPPaiement $paiement, array $allocations): void
    {
        $this->verifierInvariant($paiement, $allocations);

        ESBTPPaiementAllocation::query()
            ->where('paiement_id', $paiement->id)
            ->whereNotIn('frais_category_id', array_map('intval', array_keys($allocations)))
            // Un par un plutot qu'en masse : la suppression doit passer par le
            // modele pour laisser une trace d'audit. Une ventilation compte
            // quelques lignes, jamais des milliers.
            ->get()
            ->each->delete();

        foreach ($allocations as $categoryId => $part) {
            ESBTPPaiementAllocation::updateOrCreate(
                ['paiement_id' => $paiement->id, 'frais_category_id' => (int) $categoryId],
                ['montant' => round((float) $part, 2)]
            );
        }

        // Un avoir annule le versement LA OU CE VERSEMENT EST ALLE. Sa
        // ventilation est donc un calque de celle de son parent, et le calque
        // doit suivre quand l'original change.
        //
        // Le reflet vivait chez les APPELANTS : l'emission d'un avoir le
        // rejouait, la reventilation manuelle non. Un versement reventile
        // laissait donc ses avoirs sur l'ancienne imputation, et le remboursement
        // creditait des frais que le versement ne couvrait plus.
        //
        // Il vit desormais ici, seul endroit ou une ventilation est reecrite :
        // tous les chemins en beneficient, y compris ceux qui n'existent pas
        // encore. L'operation est idempotente.
        $this->reflet->refleterSurLesAvoirsDe($paiement);
    }

    /**
     * La somme des parts vaut EXACTEMENT le versement.
     *
     * Verifie ici et pas seulement chez l'appelant : un versement qui porte des
     * allocations est lu PAR ELLES et plus du tout par sa propre categorie. Une
     * repartition partielle ferait donc disparaitre la difference des totaux par
     * frais — sans erreur, sans trace.
     *
     * @param  array<int, float>  $allocations
     */
    private function verifierInvariant(ESBTPPaiement $paiement, array $allocations): void
    {
        $montant = round((float) $paiement->montant, 2);
        $ecart = round($montant - array_sum($allocations), 2);

        if (abs($ecart) >= 0.005) {
            throw new AllocationIncoherenteException(sprintf(
                'Versement #%d (%s) : la repartition totalise %s pour un montant de %s, '
                .'soit %s non impute. Ecrire cela ferait disparaitre cette somme des totaux par frais.',
                $paiement->id,
                $paiement->numero_recu ?: 'sans numero',
                $this->fcfa(array_sum($allocations)),
                $this->fcfa($montant),
                $this->fcfa($ecart)
            ));
        }
    }

    /**
     * Le caissier a dit lui-meme ou va chaque franc. On verifie qu'il fait le
     * compte, et qu'aucune ligne ne reclame plus que son frais ne doit.
     *
     * Une exception : le frais DESIGNE peut recevoir plus qu'il ne reclame.
     * C'est la seule facon d'enregistrer une avance, et elle est alors voulue —
     * le caissier a nomme le frais et saisi le montant. C'est aussi la ou la
     * repartition automatique depose son surplus : les deux modes posent
     * l'avance au meme endroit, sinon la meme somme s'imputerait differemment
     * selon la case cochee.
     *
     * @param  array<int|string, mixed>  $saisie
     * @param  array<int, float>  $reste
     * @return array<int, float>
     *
     * @throws RepartitionRefuseeException
     */
    private function verifierLaSaisie(
        array $saisie,
        float $montant,
        array $reste,
        ?int $fraisDesigne
    ): array {
        $lignes = [];

        foreach ($saisie as $categoryId => $part) {
            $part = round((float) $part, 2);

            // Avant le filtre du zero : un montant negatif y disparaitrait
            // silencieusement, et la somme se remettrait a faire le compte en
            // ayant perdu une ligne.
            if ($part < 0) {
                throw new RepartitionRefuseeException(
                    'Une part de repartition ne peut pas etre negative : un encaissement '
                    .'ne retire pas d\'argent a un frais. Un remboursement se passe par un avoir.'
                );
            }

            // Une ligne a zero ne dit rien : le frais n'a rien recu.
            if ($part <= ServirLesFrais::EPSILON) {
                continue;
            }

            $lignes[(int) $categoryId] = round(($lignes[(int) $categoryId] ?? 0) + $part, 2);
        }

        if ($lignes === []) {
            throw new RepartitionRefuseeException(
                'Aucun frais ne recoit ce versement. Indiquez sur quel(s) frais l\'imputer.'
            );
        }

        $noms = $this->nomsDesFrais(array_keys($lignes));

        foreach (array_keys($lignes) as $categoryId) {
            if (! isset($noms[$categoryId])) {
                throw new RepartitionRefuseeException(sprintf(
                    'Le frais #%d n\'existe pas : la repartition ne peut pas y imputer d\'argent.',
                    $categoryId
                ));
            }
        }

        $somme = round(array_sum($lignes), 2);

        if (abs($somme - $montant) >= 0.01) {
            throw new RepartitionRefuseeException(sprintf(
                'La repartition totalise %s FCFA pour un versement de %s FCFA. '
                .'Chaque franc encaisse doit etre impute a un frais : %s FCFA %s.',
                $this->fcfa($somme),
                $this->fcfa($montant),
                $this->fcfa(abs($somme - $montant)),
                $somme > $montant ? 'sont imputes en trop' : 'ne sont imputes nulle part'
            ));
        }

        foreach ($lignes as $categoryId => $part) {
            // Tarif inconnu : aucun plafond a opposer.
            if (! array_key_exists($categoryId, $reste)) {
                continue;
            }

            // Le frais designe porte l'avance, quand il y en a une.
            if ($categoryId === $fraisDesigne) {
                continue;
            }

            if ($part - $reste[$categoryId] >= 0.01) {
                throw new RepartitionRefuseeException(sprintf(
                    '« %s » ne reclame plus que %s FCFA, or la repartition lui impute %s FCFA. '
                    .'Reportez la difference sur un autre frais.',
                    $noms[$categoryId],
                    $this->fcfa($reste[$categoryId]),
                    $this->fcfa($part)
                ));
            }
        }

        // Le residu d'arrondi tolere plus haut revient a la premiere ligne, pour
        // que la somme vaille EXACTEMENT le versement.
        $premiere = array_key_first($lignes);
        $lignes[$premiere] = round($lignes[$premiere] + ($montant - $somme), 2);

        return $lignes;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    private function nomsDesFrais(array $ids): array
    {
        return ESBTPFraisCategory::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->map(fn ($nom) => (string) $nom)
            ->all();
    }

    private function fcfa(float $montant): string
    {
        return number_format($montant, 0, ',', ' ');
    }
}
