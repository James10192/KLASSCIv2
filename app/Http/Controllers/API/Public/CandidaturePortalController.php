<?php

namespace App\Http\Controllers\API\Public;

use App\Enums\RefusCandidature;
use App\Exceptions\RefusCandidatureException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inscription\PortailCandidatureRequest;
use App\Services\Inscription\PortailCandidaturePublication;
use App\Services\Inscription\PortailCandidatureService;
use App\Services\Verification\DemarrageVerification;
use Illuminate\Http\JsonResponse;

/**
 * Candidatures des NOUVEAUX etudiants, depuis klassci.com.
 *
 * Deuxieme surface non authentifiee de l'application, et elle ne se protege
 * pas comme la premiere. La reinscription identifie quelqu'un, donc son ennemi
 * est l'enumeration : d'ou ses reponses uniformes et son plancher de temps.
 * Une candidature n'identifie personne — il n'y a rien a retrouver, donc rien
 * a enumerer — et peut donc repondre franchement.
 *
 * Son ennemi a elle est le remplissage abusif. Il est tenu par trois choses :
 * la signature du site vitrine (personne d'autre ne parle a ce canal), les
 * seaux de debit du garde partage, et l'unicite (telephone, annee) qui
 * transforme un renvoi de formulaire en mise a jour plutot qu'en doublon.
 */
class CandidaturePortalController extends Controller
{
    /**
     * Defaut de parametrage de l'ecole, pas faute du candidat : on ne lui
     * demande donc pas de verifier sa saisie, et surtout pas de reessayer plus
     * tard — cela ne se debloquera pas en patientant.
     */
    private const ANNEE_NON_CONFIGUREE = "Les inscriptions ne sont pas encore configurées pour cette rentrée. Rapprochez-vous de l'établissement.";

    /**
     * Deux collaborateurs, parce que ce controleur fait deux choses : il
     * PUBLIE (l'etat du canal, les listes, la date des inscriptions sur place)
     * et il DEPOSE. La lecture n'a ni verrou ni transaction ; le depot en a
     * trois. Les melanger dans un seul service avait produit un fichier de
     * cinq cent dix lignes a cinq responsabilites.
     */
    public function __construct(
        private readonly PortailCandidaturePublication $publication,
        private readonly PortailCandidatureService $candidatures,
    ) {}

    /**
     * Les filieres et niveaux que l'ecole publie.
     *
     * Des noms, rien d'autre : ni effectif, ni place restante. Un candidat n'a
     * pas a savoir quelle classe est pleine, et le publier renseignerait un
     * concurrent sur l'etat de l'ecole.
     */
    public function choix(): JsonResponse
    {
        // Le meme refus que l'envoi, et au meme code : c'est ici qu'il sert.
        // A la rentree, l'ecole active l'interrupteur avant d'avoir designe
        // l'annee visee — le cas le plus frequent des deux. Ne le dire qu'a
        // l'envoi laisserait le candidat remplir vingt-cinq champs pour
        // apprendre qu'il doit telephoner, et les perdre en chemin.
        if ($this->publication->anneeVisee() === null) {
            return $this->anneeNonConfiguree();
        }

        return response()->json($this->publication->choixPublies());
    }

    public function submit(PortailCandidatureRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        try {
            $candidature = $this->candidatures->deposer(
                $donnees,
                $this->empreinte($donnees['ip_client'])
            );
        } catch (RefusCandidatureException $e) {
            // UN seul catch, et un match sur la raison. Trois catch consecutifs
            // sur trois classes vides faisaient dependre chaque message d'un
            // import : un catch sur une classe absente ne leve rien, il ne
            // correspond simplement jamais. Un match sur enum, lui, est
            // exhaustif par construction.
            //
            // Les refus qui envoient SUR PLACE emportent la date, comme la
            // confirmation.
            //
            // Ce sont ceux qui s'adressent a quelqu'un dont le dossier est
            // accepte — l'audience exacte de cette date. Sans elle, une famille
            // qui redepose se deplace le jour meme alors que le guichet ouvre
            // dans trois semaines, et le meme serveur, sur la requete d'a cote,
            // savait le dire.
            $reponse = [
                'enregistre' => false,
                'code' => $e->raison->value,
                'message' => $this->messageDeRefus($e->raison),
            ];

            if ($e->raison->renvoieSurPlace()) {
                $reponse['inscriptions_physiques'] = $this->publication->inscriptionsPhysiques();
            }

            return response()->json($reponse, $e->raison->statut());
        }

        // La suite du parcours voyage avec la confirmation : le dossier se
        // finalise SUR PLACE, et « je viens quand ? » est la question que la
        // scolarite entend le plus au telephone. Le portail y repond lui-meme
        // quand l'ecole a renseigne la date.
        //
        // Une candidature neuve attend la verification de son contact : elle
        // n'est transmise a l'ecole qu'apres le code (voir DemarrageVerification).
        $verification = app(DemarrageVerification::class)->apresDepot($candidature);

        return response()->json(array_merge([
            'enregistre' => true,
            'message' => $verification === null
                ? 'Votre candidature a bien été transmise à l\'établissement.'
                : 'Confirmez votre contact pour que votre candidature soit transmise à l\'établissement.',
            'inscriptions_physiques' => $this->publication->inscriptionsPhysiques(),
            'reference_publique' => $candidature->referencePubliqueAffichee(),
        ], $verification?->reponse() ?? []), 201);
    }

    /**
     * La phrase de reference de chaque refus.
     *
     * « De reference », et non « ce que le candidat lit » : le site vitrine
     * affiche ses propres textes, traduits, et se repere sur le CODE. Ces
     * phrases-ci sont ce que lit qui appelle l'API directement, et le modele
     * dont les traductions sont ecrites — un ecart entre les deux se corrige
     * donc des deux cotes.
     *
     * Elles se relisent TOUTES ENSEMBLE a chaque ajout, et c'est necessaire :
     * chacune a ete corrigee au moins une fois parce qu'elle disait faux ou
     * prescrivait un geste impossible, et l'erreur se voyait en les comparant.
     *
     * Ne pas compter les phrases dans cette phrase-ci : un decompte fige vieillit
     * a la premiere raison ajoutee, et c'est justement l'ajout qui doit declencher
     * la relecture. `RefusCandidature::cases()` fait foi, et un test parcourt
     * l'enum pour qu'une raison sans phrase echoue ici plutot que chez un
     * bachelier en pleine rentree.
     */
    private function messageDeRefus(RefusCandidature $raison): string
    {
        return match ($raison) {
            RefusCandidature::AnneeNonConfiguree => self::ANNEE_NON_CONFIGUREE,

            // Le numero porte deja une inscription, et on le dit.
            //
            // C'est un arbitrage, pas une evidence. Repondre 409 fait de ce
            // canal un oracle « ce numero est-il scolarise ici cette annee ? » :
            // n'importe qui peut essayer un numero, il n'a pas besoin de le
            // posseder. Le debit le rend impraticable en masse — dix par minute
            // et par adresse, cent vingt au global, contre un espace de
            // numerotation dense — mais reste faisable en cible, sur un numero
            // qu'on connait deja.
            //
            // On l'accepte parce que le cout du silence est certain et repete :
            // sans ce message, la famille redepose en boucle sans jamais
            // comprendre pourquoi rien n'arrive, et finit par appeler l'ecole en
            // pleine rentree. La donnee divulguee, elle, est faible :
            // l'appartenance a une ecole n'est pas un secret, et le portail
            // affiche deja le nom de l'etablissement.
            RefusCandidature::DejaInscrit => 'Une inscription existe déjà pour ce numéro cette année. Si vous candidatez pour une autre personne, utilisez son propre numéro.',

            // Le numéro porte une candidature REJETÉE, enregistrée sous un
            // autre nom.
            //
            // Trois precautions dans cette phrase, et chacune vient d'une
            // formulation precedente qui disait faux.
            //
            // Elle ne parle pas d'INSCRIPTION : sur une candidature refusee
            // aucune n'existe, et le cadet en conclurait que la place est prise.
            //
            // Elle dit « sous un autre nom » et non « pour une autre
            // personne » : le serveur compare des chaines, il ne connait pas
            // les gens. La meme candidate qui redepose « Aya » apres avoir
            // ecrit « Aya Marie » arrive ici.
            //
            // Et elle donne l'action qui MARCHE. Envoyer appeler l'ecole pour
            // faire rouvrir le dossier serait envoyer demander l'impossible :
            // la corbeille n'expose « Accepter » et « Rejeter » que sur une
            // candidature « en attente », et rien ne ramene a cet etat depuis
            // « rejetee » — sauf ce depot lui-meme, avec la meme identite.
            // Ressaisir son nom comme la premiere fois est donc le seul remede,
            // et c'est celui qu'on donne.
            //
            // Le remede n'aboutit que sur un dossier REJETE, et c'est le seul
            // qui parvient ici. Pas par un ordre de gardes : le bras
            // « acceptee » de `refusDeRedepot()` rend « deja_traitee » ou
            // « acceptee_pour_un_autre », jamais cette raison-ci, parce qu'il
            // lit l'identite LUI-MEME. Ne pas se representer un garde
            // « acceptee » place en amont : croire cela, c'est croire qu'on
            // peut le hisser en tete du match sans rien changer — ce qui
            // supprimerait « acceptee_pour_un_autre » en silence et renverrait
            // le cadet au guichet pour le dossier de son ainee.
            RefusCandidature::AutrePersonne => "Une candidature enregistrée sous un autre nom utilise déjà ce numéro. Si c'est la vôtre, ressaisissez vos nom, prénoms et date de naissance exactement comme la première fois. Sinon, utilisez un autre numéro.",

            // Une candidature ACCEPTEE porte deja ce numero.
            //
            // Elle parle a la PREMIERE personne, et elle en a le droit : ce
            // bras ne se rend que si nom, prenoms et date de naissance
            // concordent avec la ligne acceptee. Celui qui emprunte le
            // telephone du foyer sans etre le titulaire part sur
            // « acceptee_pour_un_autre », qui se tient a la troisieme.
            //
            // Ne pas fusionner les deux bras pour economiser une phrase : ce
            // serait renvoyer « VOTRE candidature a ete acceptee, presentez-vous
            // sur place » a quelqu'un qui n'a rien a venir chercher.
            //
            // Elle ne dit pas « une inscription existe » : l'ecole a accepte le
            // dossier, elle n'a pas encore inscrit. Le candidat qui lirait
            // l'inverse croirait n'avoir plus rien a faire, et ne viendrait pas.
            RefusCandidature::DejaTraitee => "Votre candidature a déjà été acceptée par l'établissement : elle n'a pas besoin d'être renvoyée. Présentez-vous sur place pour finaliser votre inscription. Si vous candidatez pour une autre personne, utilisez son propre numéro.",

            // Une acceptation sous un AUTRE nom porte ce numero.
            //
            // Troisieme personne, comme « deja inscrit », et pour la meme
            // raison : le lecteur n'est pas forcement celui dont c'est le
            // dossier. Ne pas trancher qui il est evite les deux erreurs
            // symetriques — envoyer le cadet se presenter pour l'inscription
            // de son ainee, ou dire a l'ainee que son dossier appartient a
            // quelqu'un d'autre.
            //
            // Aucune des deux issues ne demande de ressaisir son nom : sur une
            // acceptation, plus rien ne se rouvre depuis ce formulaire.
            RefusCandidature::AccepteePourUnAutre => "Une candidature enregistrée sous un autre nom, déjà acceptée par l'établissement, utilise ce numéro. Si c'est votre dossier, l'établissement l'a accepté : présentez-vous sur place. Sinon, utilisez un autre numéro.",

            // Etat inconnu : on ne dit que ce qui est certain.
            //
            // Elle envoie contacter l'ecole, et c'est justifie : la ligne est
            // dans un etat que personne d'autre ne peut remettre d'aplomb.
            //
            // Trois demarches differentes se partagent ces messages, et les
            // confondre coute une matinee a une famille :
            //
            // - FAIRE CORRIGER : ici, et sur « annee non configuree ». Un
            //   parametrage manquant ou une ligne dans un etat inconnu ne se
            //   resolvent qu'a l'ecole.
            // - FINALISER SUR PLACE : « deja traitee » et « acceptee pour un
            //   autre », qui s'adressent a un dossier deja accepte. Ce n'est
            //   pas la meme demarche, et `renvoieSurPlace()` leur joint la date
            //   d'ouverture pour cette raison.
            // - N'ALLER NULLE PART : « deja inscrit » et « autre personne ». Se
            //   deplacer ou telephoner n'y changerait rien.
            //
            // Aucun decompte dans ce qui precede : le docblock de cette methode
            // explique pourquoi, et une septieme raison le rendrait faux.
            RefusCandidature::EtatInattendu => "Une candidature existe déjà pour ce numéro et ne peut pas être renvoyée. Contactez l'établissement, ou utilisez un autre numéro si vous candidatez pour une autre personne.",
        };
    }

    /**
     * Le refus « annee non configuree », ecrit une seule fois.
     *
     * Les deux points d'entree le rendent : /choix parce que c'est la que le
     * candidat doit l'apprendre, avant de remplir quoi que ce soit, et
     * /submit parce qu'un formulaire ouvert avant que l'ecole ne se
     * deparametre doit refuser proprement.
     *
     * Pas de cle `ouvert` : elle veut dire « revenez a l'ouverture », et le
     * site vitrine la lit en premier. Un parametrage manquant ne se resout pas
     * en revenant.
     */
    private function anneeNonConfiguree(): JsonResponse
    {
        return response()->json([
            'enregistre' => false,
            'code' => RefusCandidature::AnneeNonConfiguree->value,
            'message' => self::ANNEE_NON_CONFIGUREE,
        ], RefusCandidature::AnneeNonConfiguree->statut());
    }

    /**
     * Empreinte de l'adresse du visiteur : de quoi voir un abus, pas de quoi
     * identifier une personne. La cle applicative sert de sel.
     */
    private function empreinte(string $adresse): string
    {
        return hash_hmac('sha256', $adresse, (string) config('app.key'));
    }
}
