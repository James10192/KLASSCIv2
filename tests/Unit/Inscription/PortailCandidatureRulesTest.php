<?php

namespace Tests\Unit\Inscription;

use App\Http\Requests\Inscription\PortailCandidatureRequest;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPEcheancierRule;
use App\Support\Nationalites;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Ce que le portail public accepte, et ce qu'il refuse.
 *
 * Le formulaire s'est enrichi de l'identite complete (lieu de naissance,
 * nationalite, residence), du statut d'affectation et du tuteur — parce que
 * c'est ce que la scolarite remplit reellement. Chaque champ ajoute est une
 * porte de plus sur une surface NON AUTHENTIFIEE : ces tests figent le fait
 * qu'aucune n'accepte n'importe quoi.
 *
 * Le statut d'affectation merite son test a lui : il est ferme a trois valeurs
 * parce que la colonne l'est aussi, et parce qu'il pilote a terme le bareme de
 * frais. Une quatrieme valeur passee par le portail ne serait pas un champ
 * libre de plus, ce serait une insertion qui echoue en base — ou pire, une
 * candidature acceptee que l'ecole ne saurait pas facturer.
 *
 * Pas de base de donnees ici, et c'est voulu : ces regles sont pures des lors
 * qu'on laisse de cote filiere_id et niveau_id (les seules a porter un
 * `exists`). Le voeu passe donc par le texte libre, comme pour un bachelier
 * qui ne connait pas le nom exact de la formation.
 */
class PortailCandidatureRulesTest extends TestCase
{
    /** Le minimum qu'une candidature recevable transporte. */
    private function candidature(array $remplace = []): array
    {
        return array_merge([
            'nom' => 'Kouassi',
            'prenoms' => 'Aya Marie',
            'date_naissance' => '2007-03-15',
            'telephone' => '0707121234',
            'voeu_libre' => 'Genie civil',
            'consentement' => true,
            'ip_client' => '196.1.2.3',
        ], $remplace);
    }

    /**
     * Passe la charge par le meme chemin que la requete reelle : la
     * preparation d'abord (c'est la qu'un accent absent est rattrape), les
     * regles ensuite, la verification croisee du voeu pour finir.
     */
    private function echecs(array $donnees): array
    {
        $requete = new PortailCandidatureRequest;
        $requete->merge($donnees);

        $preparer = (new \ReflectionClass($requete))->getMethod('prepareForValidation');
        $preparer->setAccessible(true);
        $preparer->invoke($requete);

        $validateur = Validator::make($requete->all(), $requete->rules());
        $requete->withValidator($validateur);

        return $validateur->errors()->toArray();
    }

    /** Ce que la requete a retenu apres preparation. */
    private function prepare(array $donnees): array
    {
        $requete = new PortailCandidatureRequest;
        $requete->merge($donnees);

        $preparer = (new \ReflectionClass($requete))->getMethod('prepareForValidation');
        $preparer->setAccessible(true);
        $preparer->invoke($requete);

        return $requete->all();
    }

    public function test_une_candidature_complete_passe(): void
    {
        $echecs = $this->echecs($this->candidature([
            'lieu_naissance' => 'Bouaké',
            'nationalite' => 'Ivoirienne',
            'ville' => 'Abidjan',
            'commune' => 'Cocody',
            'affectation_status' => 'affecté',
            'tuteur_nom' => 'Kouassi Jean',
            'tuteur_lien' => 'Père',
            'tuteur_telephone' => '0505998877',
            'tuteur_profession' => 'Enseignant',
        ]));

        $this->assertSame([], $echecs);
    }

    /**
     * Beaucoup de bacheliers ne savent pas encore s'ils sont affectes au
     * moment ou ils candidatent. Les forcer a choisir produirait une reponse
     * au hasard, que l'ecole prendrait pour une declaration.
     */
    public function test_les_champs_ajoutes_restent_facultatifs(): void
    {
        $this->assertSame([], $this->echecs($this->candidature()));
    }

    public function test_un_statut_d_affectation_invente_est_refuse(): void
    {
        $echecs = $this->echecs($this->candidature(['affectation_status' => 'boursier']));

        $this->assertArrayHasKey('affectation_status', $echecs);
    }

    /**
     * Les trois valeurs doivent etre celles de esbtp_inscriptions, accents
     * compris : c'est la meme colonne qui les recevra a la conversion, et
     * c'est elle que esbtp_echeancier_rules interroge pour choisir le bareme.
     */
    public function test_les_trois_statuts_reels_sont_acceptes(): void
    {
        foreach (array_keys(ESBTPCandidature::affectationsDeclarables()) as $statut) {
            $this->assertSame(
                [],
                $this->echecs($this->candidature(['affectation_status' => $statut])),
                "Le statut {$statut} devrait etre accepte"
            );
        }
    }

    /**
     * Le canal est public : un formulaire recopie ou un traitement qui aplatit
     * les accents enverra « affecte ». Le refuser obligerait la scolarite a
     * rappeler pour une difference invisible a l'oeil.
     */
    public function test_un_statut_sans_accent_est_rattrape(): void
    {
        $prepare = $this->prepare($this->candidature(['affectation_status' => 'non-affecte']));

        $this->assertSame(
            ESBTPEcheancierRule::STATUS_NON_AFFECTE,
            $prepare['affectation_status']
        );
        $this->assertSame([], $this->echecs($this->candidature(['affectation_status' => 'non-affecte'])));
    }

    /**
     * « all » est un joker de regle de frais (« quel que soit le statut »),
     * pas un etat d'etudiant. Un candidat ne peut pas le declarer.
     */
    public function test_le_joker_de_bareme_n_est_pas_un_statut_declarable(): void
    {
        $echecs = $this->echecs($this->candidature([
            'affectation_status' => ESBTPEcheancierRule::STATUS_ALL,
        ]));

        $this->assertArrayHasKey('affectation_status', $echecs);
    }

    /**
     * La nationalite est un vocabulaire ferme, partage avec le formulaire
     * d'inscription de l'ecole. En texte libre, « ivoirienne » passerait ici
     * et ne correspondrait a aucune option la-bas : le champ, pourtant
     * obligatoire, retomberait a vide au pre-remplissage, en silence.
     */
    public function test_une_nationalite_hors_liste_est_refusee(): void
    {
        foreach (['ivoirienne', "Côte d'Ivoire", 'CI'] as $saisie) {
            $this->assertArrayHasKey(
                'nationalite',
                $this->echecs($this->candidature(['nationalite' => $saisie])),
                "La saisie « {$saisie} » ne devrait pas passer"
            );
        }
    }

    public function test_les_nationalites_de_la_liste_sont_acceptees(): void
    {
        foreach (['Ivoirienne', 'Béninoise', 'Française'] as $valeur) {
            $this->assertContains($valeur, Nationalites::valeurs());
            $this->assertSame([], $this->echecs($this->candidature(['nationalite' => $valeur])));
        }
    }

    public function test_un_champ_de_tuteur_trop_long_est_refuse(): void
    {
        $echecs = $this->echecs($this->candidature([
            'tuteur_nom' => str_repeat('a', 151),
        ]));

        $this->assertArrayHasKey('tuteur_nom', $echecs);
    }

    /**
     * Sans voeu, la scolarite doit rappeler pour demander « vous voulez faire
     * quoi ? ». Autant le refuser au depot.
     *
     * L'erreur porte sur « voeu » et non sur `voeu_libre` : la contrainte vaut
     * pour les TROIS champs ensemble — filiere OU niveau OU texte libre — et le
     * site vitrine l'affiche au niveau de la section pour cette raison.
     * Rattachee au troisieme champ, elle apparaissait sous un libelle qui
     * commence par « Ou », et envoyait decrire a la main une formation qu'il
     * suffisait de choisir dans la liste au-dessus.
     */
    public function test_une_candidature_sans_voeu_est_refusee(): void
    {
        $donnees = $this->candidature();
        unset($donnees['voeu_libre']);

        $echecs = $this->echecs($donnees);

        $this->assertArrayHasKey('voeu', $echecs);
        $this->assertArrayNotHasKey('voeu_libre', $echecs);
    }

    public function test_le_consentement_est_obligatoire(): void
    {
        $echecs = $this->echecs($this->candidature(['consentement' => false]));

        $this->assertArrayHasKey('consentement', $echecs);
    }
}
