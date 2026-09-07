<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Candidature deposee par un NOUVEL etudiant depuis le portail public.
 *
 * Inerte, comme une demande de reinscription : elle n'ouvre aucun droit, ne
 * genere aucun frais, ne compte dans aucun effectif. C'est l'ecole qui la
 * convertit en etudiant puis en inscription. Cette separation est ce qui rend
 * le canal public acceptable.
 */
class ESBTPCandidature extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_candidatures';

    /**
     * Les champs qui n'existent que si `est_transfert` est leve.
     *
     * Declares ici, et pas dans la requete qui les efface : cette liste est la
     * DEFINITION du bloc, pas un detail de validation. Une seconde liste dans
     * PortailCandidatureRequest se serait desynchronisee au premier champ
     * ajoute — et la desynchronisation aurait ete SILENCIEUSE, puisque le
     * symptome est une valeur qui survit a un drapeau baisse, visible
     * seulement sur la fiche d'un agent qui n'a aucun moyen de la trancher.
     *
     * `est_transfert` n'en fait pas partie : il commande le bloc, il n'en est
     * pas membre.
     */
    public const CHAMPS_TRANSFERT = [
        'etablissement_sup_origine',
        'formation_origine',
        'niveau_atteint_origine',
        'annee_derniere_inscription',
        'motif_transfert',
    ];

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_ACCEPTEE = 'acceptee';

    public const STATUT_REJETEE = 'rejetee';

    public const STATUT_CONVERTIE = 'convertie';

    /**
     * Les statuts d'affectation qu'un candidat peut declarer.
     *
     * Ils viennent de ESBTPEcheancierRule, et pas d'une liste ecrite ici,
     * parce que c'est cette classe qui les fait vivre : c'est elle qui choisit
     * le bareme de frais selon le statut. Un accent qui divergerait entre les
     * deux ne se verrait pas a la relecture — il se verrait a la caisse, sur
     * un etudiant qu'on ne saurait pas facturer.
     *
     * STATUS_ALL en est exclu a dessein : c'est un joker de regle de frais
     * (« quel que soit le statut »), pas un etat d'etudiant.
     *
     * Le LIBELLE voyage avec la valeur, comme pour les nationalites juste a
     * cote. La forme precedente servait une cle ASCII que le site vitrine
     * utilisait pour indexer sa propre table de traduction : le jour ou une
     * ecole obtient un quatrieme statut, le portail public aurait affiche la
     * chaine « inscription.formulaire.affectations.boursier » dans son menu
     * deroulant — next-intl rend le chemin de cle quand elle manque. Ni le
     * compilateur, ni les tests d'aucun des deux depots ne peuvent voir cela,
     * puisque les deux moities se deploient separement.
     *
     * @return array<string, string> valeur stockee => libelle affichable
     */
    public static function liensTuteurDeclarables(): array
    {
        // Exactement les valeurs du menu deroulant du formulaire d'inscription
        // (resources/views/esbtp/inscriptions/create.blade.php). C'est ce qui
        // rend la reprise du tuteur fidele : une valeur choisie ici se recopie
        // telle quelle la-bas.
        //
        // Le champ etait libre sur le portail. « Grand-pere », « oncle »,
        // « tuteur legal » arrivaient donc dans un champ que l'ecole restreint
        // a quatre valeurs, et il fallait deviner laquelle — une normalisation
        // qui rendait « Pere » pour un grand-pere jusqu'a ce qu'on la corrige.
        // Une liste fermee supprime la devinette au lieu de l'ameliorer.
        return [
            'Père' => 'Père',
            'Mère' => 'Mère',
            'Tuteur' => 'Tuteur',
            'Autre' => 'Autre',
        ];
    }

    public static function affectationsDeclarables(): array
    {
        return [
            ESBTPEcheancierRule::STATUS_AFFECTE => "Affecté par l'État",
            ESBTPEcheancierRule::STATUS_REAFFECTE => 'Réaffecté',
            ESBTPEcheancierRule::STATUS_NON_AFFECTE => 'Non affecté',
        ];
    }

    /**
     * Le lien declare, ramene aux quatre options du formulaire d'inscription.
     *
     * Cote portail, « Lien avec vous » est un champ LIBRE : on y lit « père »,
     * « Mon oncle », « Grand frere », « tutrice ». Le formulaire d'inscription,
     * lui, propose quatre choix fermes. Sans cette table, reprendre le tuteur
     * laisserait le selecteur sur « Selectionner » — donc vide, donc en faute
     * sur une regle `required` que le meme geste vient de declencher.
     *
     * Le repli est « Autre », qui est une option VALIDE : on ne bloque jamais
     * l'agent sur une formulation qu'on n'avait pas prevue.
     */
    public static function relationTuteurNormalisee(?string $lien): string
    {
        $normalise = mb_strtolower(trim(Str::ascii((string) $lien)), 'UTF-8');

        if ($normalise === '') {
            return '';
        }

        // Un lien QUALIFIE n'est aucune des trois options.
        //
        // La version precedente cherchait « pere » n'importe ou dans la chaine.
        // « Grand-pere » contient « pere » : le bandeau proposait donc « Pere »,
        // l'agent cliquait « Reprendre ce tuteur » sans relire, et la fiche de
        // l'etudiant affirmait que son grand-pere etait son pere. Meme chose
        // pour « belle-mere », « beau-pere », « arriere-grand-mere ».
        //
        // Ces liens-la existent et sont frequents : un bachelier d'Abidjan
        // heberge chez sa grand-mere le declare tel quel. « Autre » est la
        // reponse juste — le formulaire l'offre, et l'agent precisera.
        if (preg_match('/\b(grand|arriere|beau|belle|demi)\b|grand-|beau-|belle-/', $normalise) === 1) {
            return 'Autre';
        }

        // Mot entier, et non fragment : « esperer » ne fait pas un pere, ni
        // « intendant » un gardien.
        foreach ([
            'Père' => ['pere', 'papa', 'daron'],
            'Mère' => ['mere', 'maman'],
            'Tuteur' => ['tuteur', 'tutrice', 'gardien'],
        ] as $option => $formes) {
            foreach ($formes as $forme) {
                if (preg_match('/\b'.preg_quote($forme, '/').'\b/', $normalise) === 1) {
                    return $option;
                }
            }
        }

        return 'Autre';
    }

    /**
     * Le candidat n'a rien declare de son parcours.
     *
     * La vue posait la question en enumerant cinq colonnes. Elle devenait donc
     * fausse a chaque colonne ajoutee — et c'est deja arrive une fois, avec le
     * transfert. Le modele sait ce que « parcours » recouvre ; la vue n'a qu'a
     * demander.
     */
    public function parcoursEstVide(): bool
    {
        return ! $this->serie_bac
            && ! $this->etablissement_origine
            && ! $this->annee_bac
            && ! $this->affectation_status
            && ! $this->est_transfert;
    }

    /**
     * Les evenements reellement audites : les MUTATIONS, pas les lectures.
     *
     * `config/audit.php` active aussi `retrieved`, ce qui fait ecrire une ligne
     * dans `audits` a chaque fois qu'un modele est LU. Vingt et un modeles s'en
     * protegent deja par cette meme propriete ; ceux-ci ne le faisaient pas.
     *
     * Le cout n'etait pas theorique : c'est par ce canal que la table `audits` a
     * enfle au point que la page qui la consulte ne repondait plus.
     *
     * Ce qui reste trace : creation, modification, suppression, restauration.
     * La conservation OHADA porte sur les mutations, pas sur les consultations.
     */
    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];
    protected $fillable = [
        'nom', 'prenoms', 'date_naissance', 'lieu_naissance', 'sexe', 'nationalite',
        'telephone', 'email', 'ville', 'commune',
        'filiere_id', 'niveau_id', 'voeu_libre',
        'annee_universitaire_id',
        'serie_bac', 'etablissement_origine', 'annee_bac', 'affectation_status',
        // Le bac reste au-dessus : il est demande dans les deux cas. Ce bloc
        // ne s'y substitue pas, il decrit d'ou vient celui qui n'arrive pas
        // du lycee.
        'est_transfert', 'etablissement_sup_origine', 'formation_origine',
        'niveau_atteint_origine', 'annee_derniere_inscription', 'motif_transfert',
        'tuteur_nom', 'tuteur_telephone', 'tuteur_lien', 'tuteur_profession',
        'message', 'statut', 'consentement_at', 'ip_hash',
        'motif_rejet', 'traite_par', 'traite_at',
        'etudiant_id', 'inscription_id',
        'reference_publique',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'consentement_at' => 'datetime',
        'traite_at' => 'datetime',
        'annee_bac' => 'integer',
        'est_transfert' => 'boolean',
        'annee_derniere_inscription' => 'integer',
    ];

    /**
     * `consentement_at` est audite : le rouvrir le reecrit, et la preuve du
     * consentement initial — que la loi ivoirienne 2013-450 impose de pouvoir
     * produire — disparaitrait sans trace. L'empreinte d'adresse reste dehors :
     * la conserver en plusieurs exemplaires irait contre la minimisation.
     */
    protected $auditInclude = [
        'statut', 'filiere_id', 'niveau_id', 'motif_rejet',
        'traite_par', 'traite_at', 'etudiant_id', 'inscription_id',
        'consentement_at',
        // L'identite est auditee parce qu'elle est REECRITE par une surface
        // non authentifiee : la cle d'unicite est le telephone, et un foyer le
        // partage. Un redepot met a jour la ligne existante ; sans ces trois
        // colonnes, le journal dirait « acceptée → en attente » sans jamais
        // dire que le dossier a change de personne. Les autres champs restent
        // dehors, par minimisation : ce sont ceux-la qui portent la decision.
        'nom', 'prenoms', 'date_naissance',
    ];

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    public function estTraitable(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function nomComplet(): string
    {
        return trim($this->nom.' '.$this->prenoms);
    }

    /** Le voeu tel qu'il doit s'afficher : la filiere choisie, sinon le texte libre. */
    public function voeu(): string
    {
        $depuisListe = trim(($this->filiere?->name ?? '').' '.($this->niveau?->name ?? ''));

        return $depuisListe !== '' ? $depuisListe : (string) ($this->voeu_libre ?? '');
    }
}
