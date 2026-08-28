<?php

namespace App\Enums;

/**
 * Pourquoi un depot de candidature est refuse.
 *
 * Une exception, et autant de raisons que de cas. La forme precedente etait
 * une classe vide par raison — `extends RuntimeException` sans un membre —
 * chacune levee une fois, attrapee une fois, dans autant de `catch`
 * consecutifs. Elles etaient trois ; il y en aurait six aujourd'hui.
 *
 * Ce depot a deja rencontre cette forme cote ecole et l'a rejetee en ecrivant
 * pourquoi (voir ClotureCandidature) : elle fait dependre le message montre a
 * l'utilisateur d'un `catch` dont la classe doit etre importee, faute de quoi
 * il ne correspond jamais, en silence. Le meme diagnostic vaut ici, sur la
 * surface la plus exposee des deux.
 *
 * Un `throw new X` se verifie — bin/verifier-classes.php le voit. Un `catch`
 * aussi, depuis qu'il a ete elargi ; mais le supprimer vaut mieux que de
 * compter sur l'outil qui le surveille.
 */
enum RefusCandidature: string
{
    /**
     * L'ecole a ouvert le canal sans designer d'annee visee.
     *
     * Defaut de parametrage de son cote, pas faute du candidat : on ne lui
     * demande donc pas de verifier sa saisie, et surtout pas de reessayer.
     */
    case AnneeNonConfiguree = 'annee_non_configuree';

    /**
     * Le numero porte deja une inscription pour cette annee.
     */
    case DejaInscrit = 'deja_inscrit';

    /**
     * Le numero porte une candidature REJETEE, enregistree sous un autre nom.
     *
     * Rejetee, et non « decidee » : depuis qu'`AccepteePourUnAutre` existe, une
     * acceptation sous un autre nom part la-bas. Ce cas ne nait donc plus que
     * du bras `rejetee` de `refusDeRedepot()`, et c'est ce qui rend son remede
     * — ressaisir son nom comme la premiere fois — atteignable : un rejet se
     * rouvre, une acceptation non.
     *
     * Distinct de `DejaInscrit`, et la distinction n'est pas cosmetique : la,
     * une inscription existe vraiment ; ici l'ecole a seulement refuse un
     * dossier, et rien n'est inscrit. C'est le cas de l'ainee refusee en
     * septembre et du cadet qui depose en octobre sur le telephone du foyer :
     * lui repondre « une inscription existe deja » lui ferait croire que la
     * place est prise.
     */
    case AutrePersonne = 'autre_personne';

    /**
     * La candidature a deja ete ACCEPTEE : elle ne se rouvre plus.
     *
     * L'acceptation n'est pas une etape reversible du cote public, meme pour la
     * meme personne. Rouvrir remettait le dossier « en attente » et effacait
     * `traite_par` / `traite_at` — l'acceptation disparaissait sans trace.
     *
     * Le scenario tient en vingt minutes de rentree : l'agent accepte Aya a
     * 9h02 et est interrompu ; la famille, sans confirmation, redepose a 9h05
     * depuis le telephone du foyer, meme identite ; a 9h20 l'agent enregistre
     * l'inscription, et la fermeture — qui exige « acceptee » — rend
     * « deja changee ». L'etudiant est cree, la candidature retombee dans « En
     * attente » avec ses boutons Accepter/Rejeter et aucune trace qu'un
     * etudiant en est deja issu. L'agent l'accepte a nouveau, suit le lien, et
     * cree un SECOND etudiant. C'est le doublon meme que « convertie est
     * terminal » existe pour empecher, atteint depuis une surface publique.
     *
     * Un dossier REJETE, lui, se rouvre — mais pour la MEME personne
     * seulement : rien n'en est sorti, et c'est le remede que le message
     * « autre_personne » prescrit a celui qui redepose sous une ecriture
     * differente de son propre nom. Le serveur compare des chaines, il ne
     * connait pas les gens : « Aya Marie » puis « Aya » sont deux identites
     * pour lui, et ressaisir exactement comme la premiere fois les reunit.
     *
     * Ce remede-la ne sert PAS le cadet dont l'ainee a ete refusee : sa seule
     * issue est l'autre que le message lui donne, utiliser un autre numero.
     */
    case DejaTraitee = 'deja_traitee';

    /**
     * Une candidature ACCEPTEE porte ce numero, sous un AUTRE nom.
     *
     * Distinct de `DejaTraitee`, et la distinction se paie en deplacements.
     * Ce cas est celui du cadet qui depose depuis le telephone du foyer apres
     * que l'ainee a ete acceptee — la premisse de tout le dispositif, puisque
     * la cle d'unicite est un numero que la famille partage. Lui servir
     * « VOTRE candidature a ete acceptee, presentez-vous sur place » l'envoie
     * a l'ecole pour un dossier qui n'est pas le sien, et ou personne ne
     * l'attend.
     *
     * Distinct aussi d'`AutrePersonne`, dont le remede — ressaisir son nom
     * comme la premiere fois — ne rouvre rien sur une acceptation.
     *
     * La phrase se tient donc a la troisieme personne, comme `DejaInscrit`,
     * qui est ecrit ainsi pour exactement la meme raison.
     */
    case AccepteePourUnAutre = 'acceptee_pour_un_autre';

    /**
     * Le dossier est dans un etat que ce service ne connait pas.
     *
     * Il n'en existe aucun aujourd'hui : les quatre statuts sont couverts. Ce
     * cas est la parce que la decision de redepot est TOTALE, et qu'il fallait
     * bien repondre quelque chose au `default`.
     *
     * Ce quelque chose ne pouvait etre aucune des autres raisons. Chacune
     * affirme un fait precis — une inscription existe, l'ecole a accepte, un
     * autre nom porte ce numero — et servie sur un etat inconnu, chacune serait
     * fausse. Cette surface a passe l'essentiel de sa revue a corriger des
     * phrases qui disaient faux ou prescrivaient un geste impossible : en
     * ecrire une de plus par economie de cas serait refaire l'erreur en
     * connaissance de cause.
     *
     * Ici, et contrairement a `AutrePersonne`, appeler l'ecole EST le remede :
     * seul quelqu'un ayant acces a la ligne peut la remettre d'aplomb.
     */
    case EtatInattendu = 'etat_inattendu';

    /**
     * Ce refus envoie-t-il le candidat SUR PLACE ?
     *
     * Seuls ceux qui s'adressent a quelqu'un dont le dossier est deja accepte.
     * Leur reponse emporte alors la date d'ouverture des inscriptions
     * physiques, exactement comme la confirmation de depot : « je viens
     * quand ? » est la question que la scolarite entend le plus, et ces deux
     * refus-la parlent justement a ceux qui vont la poser.
     *
     * Les autres n'y envoient pas, et il ne faut pas leur joindre la date : ce
     * serait suggerer un deplacement a qui n'a rien a venir chercher.
     */
    public function renvoieSurPlace(): bool
    {
        return match ($this) {
            self::DejaTraitee, self::AccepteePourUnAutre => true,
            self::AnneeNonConfiguree, self::DejaInscrit,
            self::AutrePersonne, self::EtatInattendu => false,
        };
    }

    /** Le code HTTP que ce refus merite. */
    public function statut(): int
    {
        return match ($this) {
            // Defaut de parametrage de l'ecole : le service n'est pas en etat
            // de repondre, et cela ne depend pas de la demande.
            self::AnneeNonConfiguree => 503,
            // La demande entre en conflit avec l'etat de la ressource : la
            // repeter ne la fera pas reussir.
            self::DejaInscrit, self::AutrePersonne, self::DejaTraitee,
            self::AccepteePourUnAutre, self::EtatInattendu => 409,
        };
    }
}
