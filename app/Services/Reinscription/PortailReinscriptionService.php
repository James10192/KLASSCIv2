<?php

namespace App\Services\Reinscription;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\TenantScolariteSettings;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Ce que le portail public a le droit de savoir, et rien de plus.
 *
 * Le couple matricule + date de naissance est un facteur d'identification
 * FAIBLE : ces deux informations circulent entre camarades, figurent sur
 * d'anciens bulletins papier, se devinent. La protection ne vient donc pas de
 * la difficulte a franchir la porte, mais de ce qu'il n'y a presque rien
 * derriere : aucun solde, aucun montant, aucune note, aucun motif de refus
 * detaille. Un attaquant qui devine un couple valide apprend qu'un etudiant
 * existe et peut se reinscrire. C'est tout.
 */
class PortailReinscriptionService
{
    public const REGLAGE_OUVERTURE = 'reinscriptions.en_ligne.ouverture';

    public const REGLAGE_FERMETURE = 'reinscriptions.en_ligne.fermeture';

    /** Sentinelle : borne de fenetre presente mais impossible a interpreter. */
    private const BORNE_ILLISIBLE = 'illisible';

    public function __construct(private readonly TenantScolariteSettings $reglages) {}

    /**
     * Le canal est-il ouvert ? La reinscription est saisonniere : hors de sa
     * fenetre, l'ecole n'a aucune raison d'exposer quoi que ce soit.
     */
    public function canalOuvert(): bool
    {
        if (! $this->reglages->reinscriptionEnLigneEnabled()) {
            return false;
        }

        $aujourdhui = Carbon::today();

        foreach ([self::REGLAGE_OUVERTURE, self::REGLAGE_FERMETURE] as $cle) {
            $borne = $this->dateReglage($cle);

            // Une borne posee mais illisible ferme le canal. Le contraire —
            // traiter l'illisible comme une absence de borne — ouvrirait le
            // portail hors saison sur une simple faute de frappe, en silence.
            // Une configuration qu'on ne sait pas lire n'est pas une
            // configuration absente.
            if ($borne === self::BORNE_ILLISIBLE) {
                return false;
            }

            if ($borne === null) {
                continue;
            }

            $horsBorne = $cle === self::REGLAGE_OUVERTURE
                ? $aujourdhui->lt($borne)
                : $aujourdhui->gt($borne);

            if ($horsBorne) {
                return false;
            }
        }

        return true;
    }

    /**
     * Identification par matricule et date de naissance.
     *
     * Retourne null aussi bien pour « ce matricule n'existe pas » que pour
     * « il existe mais la date ne correspond pas ». L'appelant ne doit pas
     * pouvoir distinguer les deux : ce serait un oracle d'enumeration.
     */
    public function identifier(string $matricule, string $dateNaissance): ?ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::where('matricule', trim($matricule))->first();

        if ($etudiant === null || $etudiant->date_naissance === null) {
            return null;
        }

        // Comparaison a temps constant. Le signal temporel dominant reste la
        // requete ci-dessus (index touche ou non) : c'est le plancher de
        // reponse du garde qui protege reellement. hash_equals ne coute rien,
        // on le garde, mais il ne porte pas la protection a lui seul.
        return hash_equals($etudiant->date_naissance->toDateString(), $dateNaissance)
            ? $etudiant
            : null;
    }

    /**
     * Evalue la situation UNE fois. Consultation et depot partent du meme
     * objet : c'est ce qui garantit qu'ils ne peuvent pas diverger.
     */
    public function evaluer(ESBTPEtudiant $etudiant): ?SituationReinscription
    {
        $anneeCible = $this->anneeCible();

        if ($anneeCible === null) {
            return null;
        }

        // Sans inscription anterieure, il n'y a rien a reinscrire : c'est une
        // premiere inscription, qui passe par l'ecole et non par ce canal.
        $inscriptionPrecedente = ESBTPInscription::precedantAnnee($etudiant->id, $anneeCible);

        if ($inscriptionPrecedente === null) {
            return null;
        }

        return new SituationReinscription(
            $etudiant,
            $anneeCible,
            $inscriptionPrecedente,
            dejaReinscrit: ESBTPInscription::aUneInscriptionVivantePour($etudiant->id, $anneeCible->id),
            // « Une demande EN ATTENTE existe », pas « une demande existe ».
            //
            // Le site vitrine se sert de ce drapeau pour masquer le formulaire
            // de depot. Sans le filtre de statut, une demande rejetee le
            // masquerait aussi : l'etudiant qui a corrige sa piece manquante ne
            // pourrait plus redeposer, et sa famille attendrait un traitement
            // qui ne viendrait jamais. C'est exactement ce que la reouverture
            // d'une demande traitee (voir deposer()) sert a eviter — la fermer
            // ici la rendrait inatteignable.
            demandeExistante: ESBTPReinscriptionDemande::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $anneeCible->id)
                ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
                ->exists(),
        );
    }

    /**
     * Depot d'une demande. Idempotent : un double clic, un rejeu de requete ou
     * un retour arriere du navigateur ne doivent pas encombrer la corbeille.
     *
     * Refuse une situation non eligible. Sans ce refus, un etudiant DEJA
     * reinscrit pouvait deposer, la scolarite convertir, et la famille se
     * retrouver avec deux jeux de frais pour la meme annee.
     */
    public function deposer(SituationReinscription $situation, string $adresseVisiteur): ?ESBTPReinscriptionDemande
    {
        if (! $situation->eligible()) {
            return null;
        }

        $cles = [
            'etudiant_id' => $situation->etudiant->id,
            'annee_universitaire_id' => $situation->anneeCible->id,
        ];

        $existante = ESBTPReinscriptionDemande::where($cles)->first();

        // Toute demande DEJA TRAITEE doit pouvoir etre redeposee. Deux cas
        // reels : l'ecole rejette pour piece manquante et l'etudiant corrige ;
        // ou l'ecole convertit puis annule l'inscription, ce qui rend
        // l'etudiant a nouveau eligible. Sans cette reouverture, l'index unique
        // rendait la ligne close telle quelle, rien n'atterrissait dans la
        // corbeille, et le portail repondait quand meme « votre demande a bien
        // ete transmise » : la famille attendait un traitement qui ne viendrait
        // jamais. L'historique reste dans le journal d'audit.
        //
        // La condition porte sur « pas en attente » plutot que sur la liste des
        // etats clos : un etat ajoute demain sera couvert sans qu'on y pense.
        if ($existante !== null && $existante->statut !== ESBTPReinscriptionDemande::STATUT_EN_ATTENTE) {
            $existante->update([
                'statut' => ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
                'motif_rejet' => null,
                'traite_par' => null,
                'traite_at' => null,
                'inscription_id' => null,
                'consentement_at' => now(),
                'ip_hash' => $this->empreinteAdresse($adresseVisiteur),
            ]);

            return $existante;
        }

        try {
            $demande = ESBTPReinscriptionDemande::firstOrCreate($cles, [
                // La classe de l'annee precedente sert de point de depart a la
                // scolarite. Elle n'est jamais un engagement.
                'classe_souhaitee_id' => $situation->inscriptionPrecedente->classe_id,
                'statut' => ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
                'consentement_at' => now(),
                'ip_hash' => $this->empreinteAdresse($adresseVisiteur),
            ]);
        } catch (QueryException $e) {
            // Deux requetes simultanees ont lu « pas de demande » en meme temps.
            // L'index unique a tranche ; on rend la gagnante, pour que le
            // perdant recoive la meme reponse que s'il avait gagne.
            //
            // 1062 est le code MySQL « Duplicate entry ». Laravel ne rend une
            // exception dediee qu'a partir de la version 10 : ce depot tourne
            // sur la 9, ou tout passe par QueryException. Attraper une classe
            // plus specifique reviendrait a n'attraper rien du tout, PHP
            // ignorant en silence un catch dont la classe est introuvable.
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }

            return ESBTPReinscriptionDemande::where($cles)->first();
        }

        if ($demande->wasRecentlyCreated) {
            Log::info('Demande de reinscription deposee depuis le portail public', [
                'demande_id' => $demande->id,
                'etudiant_id' => $situation->etudiant->id,
                'annee_universitaire_id' => $situation->anneeCible->id,
            ]);
        }

        return $demande;
    }

    /**
     * Empreinte d'adresse : de quoi reperer un abus, pas de quoi identifier
     * une personne. La cle applicative sert de sel, l'empreinte est donc
     * inexploitable hors de cette instance.
     *
     * L'adresse attendue est celle du VISITEUR, transmise dans le corps signe
     * par le site vitrine. L'adresse vue par Laravel serait celle du site,
     * identique pour toute l'ecole, donc sans valeur.
     */
    private function empreinteAdresse(string $adresse): string
    {
        return hash_hmac('sha256', $adresse, (string) config('app.key'));
    }

    /**
     * Seau qui borne le forcage d'une date de naissance.
     *
     * Cle, plafond et fenetre sont definis ici parce que deux endroits les
     * manipulent : le garde consulte le seau avant tout travail, le controleur
     * l'incremente sur echec et le vide sur succes. Des litteraux repartis
     * entre les deux divergeraient en silence, et la borne ne bornerait plus
     * rien.
     */
    public const DEBIT_MATRICULE_MAX = 5;

    public const DEBIT_MATRICULE_FENETRE_SECONDES = 900;

    public static function cleDebitMatricule(mixed $matricule): string
    {
        $normalise = mb_strtolower(trim(is_string($matricule) ? $matricule : ''));

        return 'rp-mat:'.hash('sha256', $normalise);
    }

    public function anneeCible(): ?ESBTPAnneeUniversitaire
    {
        return ESBTPAnneeUniversitaire::where('is_current', true)->first();
    }

    /**
     * Interprete une borne de fenetre, STRICTEMENT.
     *
     * `Carbon::createFromFormat` ne leve pas sur une date qui deborde : elle
     * reporte. « 2026-13-45 » devient 2027-02-14, « 2026-02-30 » devient le
     * 2 mars. Une faute de frappe decalerait donc la saison de plusieurs mois
     * en silence. D'ou l'aller-retour de comparaison ci-dessous, et non un
     * simple try/catch.
     *
     * @return Carbon|string|null  null = borne absente, BORNE_ILLISIBLE = borne
     *                             presente mais impossible a interpreter.
     */
    private function dateReglage(string $cle): Carbon|string|null
    {
        $valeur = SettingsHelper::get($cle, '');

        if (! is_string($valeur) || trim($valeur) === '') {
            return null;
        }

        $date = self::interpreterDateIso(trim($valeur));

        if ($date === null) {
            Log::warning('Date de fenetre de reinscription illisible : canal ferme par precaution', [
                'reglage' => $cle,
                'valeur' => $valeur,
            ]);

            return self::BORNE_ILLISIBLE;
        }

        return $date->startOfDay();
    }

    /**
     * Point de verite unique du format des bornes : la lecture (ici) et
     * l'ecriture (le formulaire des parametres) doivent avoir exactement la
     * meme severite, sinon l'ecole enregistre une valeur que le portail refuse
     * ensuite d'appliquer, sans que rien ne le dise.
     */
    public static function interpreterDateIso(string $valeur): ?Carbon
    {
        // DateTimeImmutable rend `false` la ou Carbon leve : pas de try/catch
        // a maintenir. Le `!` remet l'heure a zero, et la comparaison
        // aller-retour rejette les dates qui debordent silencieusement.
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);

        return ($date !== false && $date->format('Y-m-d') === $valeur)
            ? Carbon::instance($date)
            : null;
    }
}
