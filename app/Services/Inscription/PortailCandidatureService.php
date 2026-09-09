<?php

namespace App\Services\Inscription;

use App\Enums\ClotureCandidature;
use App\Enums\RefusCandidature;
use App\Exceptions\RefusCandidatureException;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPInscription;
use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Support\IdentitePersonne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Les candidatures des nouveaux etudiants, deposees depuis klassci.com.
 *
 * Le pendant de PortailReinscriptionService pour ceux qui n'ont pas encore de
 * dossier. La difference de nature commande tout le reste :
 *
 * Une reinscription IDENTIFIE quelqu'un — d'ou l'obsession de la reponse
 * uniforme, puisque distinguer « ce matricule n'existe pas » de « la date ne
 * correspond pas » donnerait un oracle d'enumeration.
 *
 * Une candidature n'identifie personne : il n'y a rien a retrouver, donc rien
 * a enumerer. En echange, n'importe qui peut deposer. Le risque bascule de la
 * fuite d'information vers le remplissage abusif — d'ou l'unicite sur le
 * telephone, qui transforme un renvoi de formulaire en mise a jour plutot
 * qu'en doublon dans la corbeille.
 *
 * Ce fichier ne tient plus qu'UNE chose : la machine a etats d'un depot —
 * deposer, rouvrir, fermer — avec ses trois transactions, ses deux verrous et
 * l'invariant « convertie est terminal ». Ce que le portail publie (etat du
 * canal, annee visee, dates, listes) vit dans PortailCandidaturePublication :
 * de la lecture pure, sans verrou ni transaction, qui n'avait rien a faire
 * dans le meme fichier.
 */
class PortailCandidatureService
{
    public function __construct(private readonly PortailCandidaturePublication $publication) {}

    /**
     * Depose une candidature.
     *
     * Idempotent par (telephone, annee) TANT QUE la candidature n'a pas ete
     * decidee : un double clic, un retour arriere du navigateur ou une famille
     * qui renvoie le formulaire mettent a jour la ligne existante au lieu d'en
     * creer une seconde. Une corbeille remplie de doublons est une corbeille
     * que la scolarite cesse de lire.
     *
     * Passe la decision, l'idempotence s'arrete et les refus prennent le
     * relais — voir refusDeRedepot(), qui les enumere tous. Ils ne sont pas
     * anecdotiques : la cle est le TELEPHONE, souvent celui du foyer, donc un
     * cadet qui depose sur le numero de son aine arrive ici.
     *
     * @param  array<string, mixed>  $champs
     *
     * @throws RefusCandidatureException portant sa raison. AnneeNonConfiguree
     *                                   quand l'ecole n'a designe aucune annee visee ; toutes les autres
     *                                   viennent de refusDeRedepot(), qui les enumere avec leur cas. Ne
     *                                   pas les recopier ici : la liste a deja vieilli une fois, et un
     *                                   inventaire faux dans une annotation vaut moins que pas
     *                                   d'inventaire.
     */
    public function deposer(array $champs, ?string $empreinteAdresse = null): ESBTPCandidature
    {
        $anneeCible = $this->publication->anneeVisee();

        if ($anneeCible === null) {
            Log::warning('Candidature refusee : aucune annee universitaire visee');

            throw new RefusCandidatureException(
                RefusCandidature::AnneeNonConfiguree,
                'aucune annee universitaire visee'
            );
        }

        $cles = [
            'telephone' => $champs['telephone'],
            'annee_universitaire_id' => $anneeCible->id,
        ];

        $valeurs = [
            'nom' => $champs['nom'],
            'prenoms' => $champs['prenoms'],
            'date_naissance' => $champs['date_naissance'],
            'lieu_naissance' => $champs['lieu_naissance'] ?? null,
            'sexe' => $champs['sexe'] ?? null,
            'nationalite' => $champs['nationalite'] ?? null,
            'email' => $champs['email'] ?? null,
            'ville' => $champs['ville'] ?? null,
            'commune' => $champs['commune'] ?? null,
            'filiere_id' => $champs['filiere_id'] ?? null,
            'niveau_id' => $champs['niveau_id'] ?? null,
            'voeu_libre' => $champs['voeu_libre'] ?? null,
            'serie_bac' => $champs['serie_bac'] ?? null,
            'etablissement_origine' => $champs['etablissement_origine'] ?? null,
            'annee_bac' => $champs['annee_bac'] ?? null,
            'affectation_status' => $champs['affectation_status'] ?? null,
            'tuteur_nom' => $champs['tuteur_nom'] ?? null,
            'tuteur_telephone' => $champs['tuteur_telephone'] ?? null,
            'tuteur_lien' => $champs['tuteur_lien'] ?? null,
            'tuteur_profession' => $champs['tuteur_profession'] ?? null,
            'message' => $champs['message'] ?? null,
            'consentement_at' => now(),
            'ip_hash' => $empreinteAdresse,
        ];

        // Le bloc transfert, DERIVE de sa definition plutot que reecrit.
        //
        // C'est la troisieme liste de ces champs — les regles, l'effaceur, et
        // celle-ci — et la seule qui les ECRIT. C'est donc la seule dont
        // l'oubli ne produit aucune erreur : la candidature s'enregistre, la
        // reponse dit « transmise », et ce que le candidat a declare de son
        // transfert disparait en silence. Seul un depot de bout en bout le
        // revele, ce qui est arrive.
        //
        // `false` et non `null` pour le drapeau : la colonne est NOT NULL, et
        // « on ne m'a rien dit » vaut « pas un transfert » — c'est aussi ce que
        // portent les candidatures deposees avant que la question n'existe.
        $valeurs['est_transfert'] = $champs['est_transfert'] ?? false;

        foreach (ESBTPCandidature::CHAMPS_TRANSFERT as $champTransfert) {
            $valeurs[$champTransfert] = $champs[$champTransfert] ?? null;
        }

        $existante = ESBTPCandidature::where($cles)->first();

        if ($existante !== null) {
            $rouverte = $this->rouvrir($existante->id, $valeurs);

            if ($rouverte !== null) {
                $rouverte->assurerReferencePublique();
                $this->attribuerCreneau($rouverte);

                return $rouverte;
            }

            // Disparue entre la lecture et le verrou : on la recree plus bas.
        }

        try {
            $candidature = ESBTPCandidature::create($cles + $valeurs + [
                'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
            ]);
        } catch (QueryException $e) {
            // UNIQUEMENT le doublon de cle (1062), et pas n'importe quelle
            // panne SQL.
            //
            // Deux envois simultanes ont lu « pas de candidature » en meme
            // temps ; l'index unique a tranche, on rend la gagnante pour que le
            // perdant recoive la meme reponse que s'il avait gagne.
            //
            // La ligne rattrapee vient de naitre, donc « en attente » — sauf a
            // ce qu'une decision d'ecole se glisse entre l'insertion perdante
            // et cette relecture, fenetre de l'ordre de la microseconde. Le
            // perdant lirait alors « votre candidature a bien ete transmise »
            // sur un dossier deja accepte : rien n'est ecrit, aucun second
            // etudiant, aucune decision effacee, et son redepot suivant lui
            // rendra le bon refus. On ne verrouille pas pour cela.
            //
            // Sans ce filtre, le rattrapage etait plus large que le cas qu'il
            // decrit — une colonne disparue, une connexion coupee — et rendait
            // une candidature EXISTANTE, quel que soit son etat, en contournant
            // `refusDeRedepot()`. Une acceptation aurait pu ressortir comme un
            // depot reussi.
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }

            $rattrapee = ESBTPCandidature::where($cles)->first();

            if ($rattrapee === null) {
                throw $e;
            }

            $rattrapee->assurerReferencePublique();
            $this->attribuerCreneau($rattrapee);

            return $rattrapee;
        }

        Log::info('Candidature deposee depuis le portail public', [
            'candidature_id' => $candidature->id,
            'annee_universitaire_id' => $cles['annee_universitaire_id'],
        ]);

        $candidature->assurerReferencePublique();
        $this->attribuerCreneau($candidature);

        return $candidature;
    }

    private function attribuerCreneau(ESBTPCandidature $candidature): void
    {
        app(AffecteurDossiersRdv::class)->placerApresCommit($candidature);
    }

    /**
     * Rouvre une candidature existante, sous verrou de ligne.
     *
     * Sur le chemin du DEPOT, le verrou est pose ici et nulle part ailleurs, et
     * par cle primaire. L'autre verrou de cette classe garde le chemin oppose,
     * `fermerApresInscription()` : les deux moities d'une meme course.
     *
     * Deux raisons. D'abord il est necessaire : entre le moment ou l'on lit le
     * statut et celui ou l'on reecrit la ligne, l'ecole peut avoir converti la
     * candidature ; le depot la remettrait alors « en attente » tout en
     * conservant `etudiant_id` et `inscription_id`. Laisser une moitie de la
     * paire sans protection, ce serait n'en proteger aucune.
     *
     * Ensuite il doit s'arreter la. Poser `lockForUpdate` sur la recherche par
     * (telephone, annee) quand AUCUNE ligne ne correspond ne prend pas un
     * verrou de ligne : InnoDB prend un verrou d'intervalle sur l'index
     * unique. Deux depots simultanes d'un numero encore inconnu l'obtiennent
     * tous les deux — ils sont compatibles — puis se bloquent l'un l'autre sur
     * leur insertion : interblocage (1213) au lieu du doublon (1062) que le
     * rattrapage plus bas sait traiter. Le double-clic, cas le plus banal du
     * formulaire, aurait rendu 500.
     *
     * @param  array<string, mixed>  $valeurs
     * @return ESBTPCandidature|null null si la ligne a disparu entre-temps
     */
    private function rouvrir(int $id, array $valeurs): ?ESBTPCandidature
    {
        return DB::transaction(function () use ($id, $valeurs) {
            $candidature = ESBTPCandidature::whereKey($id)->lockForUpdate()->first();

            // Disparue entre la lecture et le verrou. Rare, mais possible :
            // une purge, une suppression manuelle. L'appelant la recreera.
            if ($candidature === null) {
                return null;
            }

            // Ce que l'etat de la ligne autorise. La decision vit dans
            // `refusDeRedepot`, seule et testable : elle a repris trois fois de
            // suite, chaque fois pour reculer d'un etat, et chaque fois sans
            // qu'aucun test ne puisse l'atteindre — ouvrir cette transaction
            // demande une base.
            $refus = self::refusDeRedepot(
                (string) $candidature->statut,
                $this->memeIdentite($candidature, $valeurs)
            );

            if ($refus !== null) {
                // Une identite qui ne concorde pas merite un cran de plus qu'un
                // formulaire renvoye deux fois : le premier est banal, le second
                // dit qu'un dossier decide porte le numero de quelqu'un d'autre.
                $niveau = $refus === RefusCandidature::AutrePersonne ? 'warning' : 'info';

                Log::log($niveau, 'Redepot refuse sur le portail public', [
                    'candidature_id' => $candidature->id,
                    'statut' => $candidature->statut,
                    'refus' => $refus->value,
                ]);

                // Message technique : ce que lit le candidat est ecrit par le
                // controleur, seul a connaitre la formulation publique.
                // Recopier la phrase ici en ferait une seconde version a
                // maintenir, dans un endroit qui ne la montrera jamais.
                throw new RefusCandidatureException($refus, 'redepot refuse : '.$refus->value);
            }

            // Rejetee pour la meme personne, en revanche, se rouvre. La mise a
            // jour passe par le MODELE, donc le journal d'audit garde la trace
            // du retour en arriere — et de l'identite, qui figure desormais
            // dans la liste blanche pour cette raison precise.
            $candidature->update($valeurs + [
                'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
                'motif_rejet' => null,
                'traite_par' => null,
                'traite_at' => null,
            ]);

            return $candidature;
        });
    }

    /**
     * Ferme une candidature dont l'inscription vient d'etre creee.
     *
     * L'autre moitie de « convertie est terminal ». La premiere vit dans
     * rouvrir() juste au-dessus, et les deux gardent le meme invariant depuis
     * les deux portes : le portail public qui redepose, et la scolarite qui
     * inscrit. Les ecrire dans deux fichiers differents, c'etait laisser deux
     * gardiens d'une meme porte apprendre la consigne separement.
     *
     * Meme forme que rouvrir() pour la meme raison : verrou de ligne par cle
     * primaire (deux agents peuvent cliquer a une seconde d'intervalle), et
     * mise a jour par le MODELE, pour que le journal d'audit garde la trace de
     * la seule transition qui fabrique un etudiant. `etudiant_id` et
     * `inscription_id` figurent dans la liste blanche d'audit et ne sont
     * ecrits que par ce chemin.
     *
     * Le droit d'ecrire, lui, reste au controleur : c'est une question de
     * qui appelle, pas de ce que la candidature autorise.
     *
     * L'inscription est passee en OBJET, et pas ses deux identifiants : trois
     * entiers d'affilee dont deux se lisent sur le meme modele
     * (`$inscription->etudiant_id`, `$inscription->id`) s'intervertissent sans
     * que rien ne le signale — ni le compilateur, ni les tests — et la
     * candidature se retrouverait rattachee au mauvais etudiant.
     *
     * Les deux issues sont des fins NORMALES, pas des pannes : d'ou une valeur
     * de retour plutot qu'un booleen muet. L'appelant en tire le message a
     * montrer a l'agent ; aucune ne fait echouer la requete, puisque
     * l'inscription, elle, est deja enregistree.
     *
     * Ce que cette methode ne verifie PAS : que l'inscription concerne bien le
     * candidat. Ce controle-la se fait AVANT la creation, dans
     * RattachementCandidature::refuserSiNaissanceDivergente(), ou la decision
     * est encore reversible. Le faire ici ne pouvait que constater apres coup,
     * sur une inscription deja committee, et laisser la candidature ouverte
     * pour toujours.
     */
    public function fermerApresInscription(
        int $candidatureId,
        ESBTPInscription $inscription,
        ?int $agentId
    ): ClotureCandidature {
        return DB::transaction(function () use ($candidatureId, $inscription, $agentId) {
            $candidature = ESBTPCandidature::whereKey($candidatureId)
                ->lockForUpdate()
                ->first();

            if (
                $candidature === null ||
                $candidature->statut !== ESBTPCandidature::STATUT_ACCEPTEE
            ) {
                return ClotureCandidature::DejaChangee;
            }

            $candidature->update([
                'statut' => ESBTPCandidature::STATUT_CONVERTIE,
                'etudiant_id' => $inscription->etudiant_id,
                'inscription_id' => $inscription->id,
                'traite_par' => $agentId,
                'traite_at' => now(),
            ]);

            return ClotureCandidature::Fermee;
        });
    }

    /**
     * Ce qu'un redepot public a le droit de faire d'une ligne existante.
     *
     * Pure et statique : c'est la seule facon de la mettre sous test. Le
     * chemin reel ouvre une transaction et pose un verrou de ligne, donc
     * demande une base — et cette regle a repris TROIS fois, chaque fois pour
     * reculer d'un etat, chaque fois apres qu'une revue l'ait trouvee a la
     * lecture. Trois `if` narratifs le disaient aussi bien ; ils ne le
     * disaient a personne d'autre qu'au lecteur.
     *
     * Elle est TOTALE, et l'ordre du `match` porte du sens :
     *
     * - `convertie` : un etudiant existe. Ce n'est plus une decision, c'est un
     *   fait, et la cle d'unicite est le telephone du FOYER — un cadet qui
     *   reprend le numero de son aine ecraserait son identite en gardant le
     *   lien vers son etudiant.
     * - `acceptee` : refusee dans les deux cas, mais pas avec la meme raison.
     *   Rouvrir effacait `traite_par` et rendait le dossier a « en attente »
     *   avec ses boutons intacts ; l'agent, ne voyant aucune trace de
     *   l'etudiant deja cree, l'acceptait a nouveau et en creait un second.
     *   L'identite ne change donc RIEN au refus — mais tout a la phrase :
     *   « votre candidature a ete acceptee, presentez-vous sur place » envoie
     *   se deplacer le cadet qui emprunte le telephone de son ainee.
     * - « acceptee » ne rend JAMAIS « autre_personne », et ce n'est pas une
     *   question d'ordre : les bras d'un `match` sont disjoints, et l'identite
     *   se lit A L'INTERIEUR du bras. Ce refus-la prescrit de ressaisir son nom
     *   comme la premiere fois, remede qui n'aboutit que sur un dossier encore
     *   ouvrable — donc jamais sur une acceptation.
     * - `en_attente` : rien n'a ete decide, donc tout se corrige — y compris
     *   une faute de frappe dans son propre nom, ce qu'aucun controle
     *   d'identite ne doit empecher.
     * - defaut : un statut inconnu se refuse, avec SA raison. Il n'en existe
     *   pas d'autre aujourd'hui ; le jour ou il en naitra un, tomber du cote
     *   strict laisse une trace dans le journal, alors que l'ouvrir creerait un
     *   doublon. Lui preter l'une des autres raisons afficherait au candidat
     *   un fait qui n'a pas eu lieu.
     *
     * @param  bool  $memeIdentite  nom, prenoms et date de naissance concordent
     * @return RefusCandidature|null null quand le redepot est autorise
     */
    public static function refusDeRedepot(string $statut, bool $memeIdentite): ?RefusCandidature
    {
        return match ($statut) {
            ESBTPCandidature::STATUT_CONVERTIE => RefusCandidature::DejaInscrit,
            ESBTPCandidature::STATUT_ACCEPTEE => $memeIdentite
                ? RefusCandidature::DejaTraitee
                : RefusCandidature::AccepteePourUnAutre,
            ESBTPCandidature::STATUT_EN_ATTENTE => null,
            ESBTPCandidature::STATUT_REJETEE => $memeIdentite ? null : RefusCandidature::AutrePersonne,
            default => RefusCandidature::EtatInattendu,
        };
    }

    /**
     * Est-ce bien la meme personne qui redepose ?
     *
     * Comparaison indulgente sur la forme, stricte sur le fond : accents,
     * casse et espaces multiples ne comptent pas — « KOUASSI  Aya » et
     * « Kouassi Aya » sont la meme candidate, et un formulaire public se
     * remplit rarement deux fois a l'identique. Le nom, les prenoms et la date
     * de naissance, eux, doivent concorder.
     *
     * @param  array<string, mixed>  $valeurs
     */
    private function memeIdentite(ESBTPCandidature $candidature, array $valeurs): bool
    {
        return IdentitePersonne::concordent(
            $candidature->nom,
            $candidature->prenoms,
            $candidature->date_naissance,
            $valeurs['nom'] ?? '',
            $valeurs['prenoms'] ?? '',
            $valeurs['date_naissance'] ?? '',
        );
    }
}
