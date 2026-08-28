<?php

namespace App\Support;

/**
 * Un seau de limitation de debit, et ce qu'il repond quand il deborde.
 *
 * Une cle, un plafond, et le refus qui va avec — nommes, pas positionnels. La
 * forme precedente etait un tableau `[$cle, $max]` relu en `$seau[0]` /
 * `$seau[1]` dans la fonction meme qui decide de qui peut inonder six ecoles.
 *
 * Le refus voyage AVEC le seau, parce que les deux saturations ne disent pas
 * la meme chose. Celui d'une adresse s'adresse a quelqu'un qui a effectivement
 * trop demande ; le plafond de l'etablissement se remplit du trafic de tout le
 * monde, et accuser CE visiteur d'avoir trop essaye serait faux — en plus de
 * lui prescrire une attente qui ne le debloquera pas.
 */
final class SeauDeDebit
{
    /** Les seaux de volume se vident chaque minute. */
    private const FENETRE_PAR_MINUTE = 60;

    private function __construct(
        public readonly string $cle,
        public readonly int $maximum,
        /**
         * Combien de temps le compteur retient ses jetons.
         *
         * Portee par la valeur, et non par un litteral chez l'appelant.
         *
         * Le seau du matricule dure quinze fois plus longtemps que les seaux de
         * volume, et sa cle etait manipulee a deux endroits : le garde qui la
         * consulte, le controleur qui l'incremente. Chacun allait chercher la
         * meme constante de son cote — donc rien ne garantissait qu'ils
         * parlaient du meme seau, sinon la vigilance. Les faire passer tous
         * deux par `PortailReinscriptionService::seauDuMatricule()` fait
         * voyager ensemble la cle, le plafond et la fenetre : le jour ou l'une
         * des trois bouge, les deux cotes bougent avec elle.
         */
        public readonly int $fenetreSecondes,
        private readonly string $code,
        private readonly string $message,
    ) {}

    public static function parAdresse(string $cle, int $maximum): self
    {
        return new self(
            $cle,
            $maximum,
            self::FENETRE_PAR_MINUTE,
            'trop_de_tentatives',
            'Trop de tentatives. Réessayez dans quelques minutes.'
        );
    }

    /**
     * Le seau d'un IDENTIFIANT — aujourd'hui le matricule.
     *
     * Ni « par adresse » ni « global », et la nuance n'est pas cosmetique :
     * c'est le seul seau qu'un TIERS peut remplir. Il ne se consomme que sur un
     * echec d'identification, et n'importe qui peut poster cinq mauvaises dates
     * de naissance sur le matricule d'un camarade depuis le formulaire public.
     * Aya arrive ensuite et lirait qu'elle a trop essaye, alors qu'elle n'a rien
     * tente.
     *
     * Sa fenetre est aussi quinze fois plus longue que celle des seaux de
     * volume : « patientez quelques minutes » y serait faux deux fois.
     *
     * Aucune duree n'est ANNONCEE, et c'est un choix. Le message affiche est
     * ecrit dans le site vitrine, un autre depot, deploye separement : un
     * chiffre calcule ici n'y arriverait jamais — seul le CODE traverse — et
     * un chiffre recopie la-bas se figerait le jour ou l'on durcit la fenetre.
     * Deux textes qui pretendent dire la meme duree finissent toujours par en
     * dire deux differentes.
     *
     * Et meme exacte, la duree serait trompeuse : la fenetre de Laravel part
     * du PREMIER essai, pas du dernier. Qui arrive a la quatorzieme minute
     * lirait « quinze minutes » alors qu'il en reste une.
     */
    public static function parIdentifiant(string $cle, int $maximum, int $fenetreSecondes): self
    {
        return new self(
            $cle,
            $maximum,
            $fenetreSecondes,
            'identification_bloquee',
            "Trop d'essais infructueux sur ce dossier. Réessayez plus tard, ou rapprochez-vous de votre établissement."
        );
    }

    public static function global(string $cle, int $maximum): self
    {
        return new self(
            $cle,
            $maximum,
            self::FENETRE_PAR_MINUTE,
            'affluence',
            "L'établissement reçoit beaucoup de demandes en ce moment. Réessayez dans quelques minutes, ou rapprochez-vous de lui directement."
        );
    }

    /**
     * Le message accompagne le code par courtoisie envers un client sans
     * traduction ; le site vitrine, lui, affiche les siennes a partir du code.
     *
     * @return array{code: string, message: string}
     */
    public function refus(): array
    {
        return ['code' => $this->code, 'message' => $this->message];
    }
}
