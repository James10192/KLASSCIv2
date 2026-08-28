<?php

namespace Tests\Unit\Inscription;

use App\Enums\RefusCandidature;
use App\Models\ESBTPCandidature;
use App\Services\Inscription\PortailCandidatureService;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'un redepot public a le droit de faire d'une candidature existante.
 *
 * La regle a recule de trois etats en trois revues : d'abord seul
 * « convertie » etait protege, puis l'identite sur « decidee », puis
 * « acceptee » tout court. A chaque fois elle a ete trouvee A LA LECTURE,
 * parce qu'aucun test ne pouvait l'atteindre : le chemin reel ouvre une
 * transaction et pose un verrou de ligne, donc reclame une base — indisponible
 * ici (MariaDB 10.4 s'arrete sur l'introspection Doctrine).
 *
 * D'ou la decision extraite, pure et statique. Ce fichier couvre la matrice
 * entiere statut x identite : neuf lignes, et la dixieme est refusee par
 * construction.
 *
 * Ce qui se joue derriere n'est pas une nuance de message. Rouvrir une
 * candidature acceptee effacait `traite_par` et la rendait « en attente »,
 * boutons intacts, sans aucune trace de l'etudiant deja cree — l'agent
 * l'acceptait une seconde fois et creait un second dossier, depuis une
 * surface que personne n'authentifie.
 */
class RedepotCandidatureTest extends TestCase
{
    /** @return list<array{0: string, 1: bool, 2: ?RefusCandidature}> */
    public static function matrice(): array
    {
        return [
            // Un etudiant existe : c'est un fait, plus une decision.
            'convertie, meme personne' => [ESBTPCandidature::STATUT_CONVERTIE, true, RefusCandidature::DejaInscrit],
            'convertie, autre personne' => [ESBTPCandidature::STATUT_CONVERTIE, false, RefusCandidature::DejaInscrit],

            // Acceptee : refusee dans les deux cas — mais pas avec la meme
            // raison, donc pas avec la meme phrase. « Votre candidature a ete
            // acceptee, presentez-vous sur place » enverrait a l'ecole le
            // cadet qui emprunte le telephone de son ainee.
            'acceptee, meme personne' => [ESBTPCandidature::STATUT_ACCEPTEE, true, RefusCandidature::DejaTraitee],
            'acceptee, autre personne' => [ESBTPCandidature::STATUT_ACCEPTEE, false, RefusCandidature::AccepteePourUnAutre],

            // Rejetee : rien n'en est sorti, le dossier se rouvre — mais pour
            // celui dont c'est le dossier. L'ainee refusee en septembre, le
            // cadet qui depose en octobre depuis le telephone du foyer.
            'rejetee, meme personne' => [ESBTPCandidature::STATUT_REJETEE, true, null],
            'rejetee, autre personne' => [ESBTPCandidature::STATUT_REJETEE, false, RefusCandidature::AutrePersonne],

            // En attente : rien n'a ete decide, donc tout se corrige. Y compris
            // une faute de frappe dans son propre nom, ce qu'un controle
            // d'identite empecherait justement.
            'en attente, meme personne' => [ESBTPCandidature::STATUT_EN_ATTENTE, true, null],
            'en attente, nom corrige' => [ESBTPCandidature::STATUT_EN_ATTENTE, false, null],

            // Etat inconnu : refuse, et avec SA raison. Les trois autres
            // affirment chacune un fait precis qui, ici, serait faux.
            'statut inconnu' => ['statut_qui_nexiste_pas', true, RefusCandidature::EtatInattendu],
        ];
    }

    /** @dataProvider matrice */
    public function test_la_decision_de_redepot(string $statut, bool $memeIdentite, ?RefusCandidature $attendu): void
    {
        $this->assertSame(
            $attendu,
            PortailCandidatureService::refusDeRedepot($statut, $memeIdentite)
        );
    }

    /**
     * Une acceptation ne se rouvre pas, quelle que soit l'identite.
     *
     * Redite volontaire de deux lignes de la matrice : c'est l'invariant dont
     * depend « un candidat accepte ne peut pas devenir deux etudiants », et il
     * doit se lire sans avoir a deplier un fournisseur de donnees.
     */
    public function test_aucune_identite_ne_rouvre_une_acceptation(): void
    {
        foreach ([true, false] as $memeIdentite) {
            $this->assertNotNull(
                PortailCandidatureService::refusDeRedepot(ESBTPCandidature::STATUT_ACCEPTEE, $memeIdentite),
                'Rouvrir une acceptation depuis le portail permet un second etudiant'
            );
        }
    }

    /**
     * Une acceptation ne parle jamais a la premiere personne sans etre sure.
     *
     * Le refus est le meme dans les deux cas ; c'est ce qu'on DIT qui change.
     * Ce test fige la distinction parce qu'elle a coute un aller-retour a
     * l'ecole a une famille : la phrase « votre candidature a ete acceptee,
     * presentez-vous sur place » etait servie a qui partageait simplement le
     * numero.
     */
    public function test_l_identite_choisit_la_phrase_d_une_acceptation(): void
    {
        $this->assertNotSame(
            PortailCandidatureService::refusDeRedepot(ESBTPCandidature::STATUT_ACCEPTEE, true),
            PortailCandidatureService::refusDeRedepot(ESBTPCandidature::STATUT_ACCEPTEE, false),
            'Le meme refus pour les deux lecteurs rend une des deux phrases fausse'
        );
    }

    /**
     * Seul « en attente » se rouvre librement.
     *
     * Fige le sens du `default` : un statut ajoute plus tard tombe du cote
     * strict. Se tromper de ce cote-la se voit dans le journal ; de l'autre,
     * cela cree un doublon que personne ne remarque.
     */
    public function test_tout_statut_inconnu_est_refuse(): void
    {
        foreach (['', 'ACCEPTEE', 'archivee', 'en_attente_de_paiement', '0'] as $statut) {
            $this->assertSame(
                RefusCandidature::EtatInattendu,
                PortailCandidatureService::refusDeRedepot($statut, true),
                "Le statut « {$statut} » doit etre refuse, et avec sa propre raison"
            );
        }
    }
}
