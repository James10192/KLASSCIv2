<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPEtudiant extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist — données identitaires sensibles).
     *
     * @var array
     */
    protected $auditInclude = [
        'user_id',
        'matricule',
        'nom',
        'prenoms',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'nationalite',
        'adresse',
        'telephone',
        'email',
        'email_personnel',
        'photo',
        'statut',
        'classe_id',
        'annee_universitaire_id',
        'urgence_contact_nom',
        'urgence_contact_telephone',
        'date_abandon',
        'motif_abandon',
        'abandon_type',
    ];

    /**
     * Événements à auditer.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_etudiants';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'classe_id',
        'annee_universitaire_id',
        'matricule',
        'nom',
        'prenoms',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'ville_naissance',
        'commune_naissance',
        'nationalite',
        'adresse',
        'telephone',
        'email',
        'email_personnel',
        'photo',
        'statut',
        'groupe_sanguin',
        'situation_matrimoniale',
        'nombre_enfants',
        'urgence_contact_nom',
        'urgence_contact_telephone',
        'urgence_contact_relation',
        'created_by',
        'updated_by',
        'ville',
        'commune',
        'date_abandon',
        'motif_abandon',
        'abandon_type'
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'date_naissance' => 'date',
        'date_abandon' => 'datetime',
        'nombre_enfants' => 'integer',
    ];

    /**
     * Relation avec l'utilisateur.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec les parents.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parents()
    {
        return $this->belongsToMany(ESBTPParent::class, 'esbtp_etudiant_parent', 'etudiant_id', 'parent_id')
                    ->withPivot('relation', 'is_tuteur')
                    ->withTimestamps();
    }

    /**
     * Relation avec les inscriptions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inscriptions()
    {
        return $this->hasMany(ESBTPInscription::class, 'etudiant_id');
    }

    /**
     * Relation avec les notes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(ESBTPNote::class, 'etudiant_id');
    }

    /**
     * Relation avec les absences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function absences()
    {
        return $this->hasMany(ESBTPAbsence::class, 'etudiant_id');
    }

    /**
     * Relation avec les paiements.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paiements()
    {
        return $this->hasMany(ESBTPPaiement::class, 'etudiant_id');
    }

    public function documents()
    {
        return $this->hasMany(ESBTPEtudiantDocument::class, 'etudiant_id');
    }

    public function relances()
    {
        return $this->hasMany(ESBTPRelance::class, 'etudiant_id');
    }

    public function accessibilityProfile()
    {
        return $this->hasOne(ESBTPStudentAccessibilityProfile::class, 'etudiant_id');
    }

    public function getHasAccessibilityProfileAttribute(): bool
    {
        return $this->accessibilityProfile()->exists();
    }

    /**
     * Obtenir l'inscription active de l'étudiant.
     *
     * @return \App\Models\ESBTPInscription|null
     */
    public function getInscriptionActiveAttribute()
    {
        return $this->inscriptions()
            ->whereHas('anneeUniversitaire', function($query) {
                $query->where('is_current', true);
            })
            ->where('status', 'active')
            ->first();
    }

    /**
     * Obtenir la classe active de l'étudiant.
     *
     * @return \App\Models\ESBTPClasse|null
     */
    public function getClasseActiveAttribute()
    {
        $inscription = $this->inscription_active;

        if (!$inscription) {
            return null;
        }

        return $inscription->classe;
    }

    /**
     * Obtenir le nom complet de l'étudiant.
     * Convention KLASSCI : NOM en premier (familial), Prénoms ensuite.
     * Cohérent avec l'usage en Côte d'Ivoire et les documents officiels.
     *
     * @return string
     */
    public function getNomCompletAttribute()
    {
        return trim($this->nom . ' ' . $this->prenoms);
    }

    /**
     * Obtenir l'âge de l'étudiant.
     *
     * @return int|null
     */
    public function getAgeAttribute()
    {
        if (!$this->date_naissance) {
            return null;
        }

        return $this->date_naissance->age;
    }

    /**
     * Obtenir le parent tuteur de l'étudiant.
     *
     * @return \App\Models\ESBTPParent|null
     */
    public function getTuteurAttribute()
    {
        return $this->parents()
                    ->wherePivot('is_tuteur', true)
                    ->first();
    }

    /**
     * Utilisateur qui a créé l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utilisateur qui a mis à jour l'entrée.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Générer un matricule unique pour l'étudiant.
     *
     * @param string $filiere Code de la filière
     * @param string $niveau Code du niveau
     * @param string $annee Année d'inscription (format court, ex: 23 pour 2023)
     * @return string
     */
    public static function genererMatricule($filiere, $niveau, $annee)
    {
        // Récupérer le dernier numéro séquentiel pour cette combinaison
        $lastMatricule = self::where('matricule', 'like', "{$filiere}-{$niveau}-{$annee}-%")
                            ->orderByRaw('CAST(SUBSTRING_INDEX(matricule, "-", -1) AS UNSIGNED) DESC')
                            ->first();

        $seq = 1;
        if ($lastMatricule) {
            $parts = explode('-', $lastMatricule->matricule);
            $lastSeq = intval(end($parts));
            $seq = $lastSeq + 1;
        }

        // Formater le numéro séquentiel sur 4 chiffres
        $seqFormatted = str_pad($seq, 4, '0', STR_PAD_LEFT);

        return "{$filiere}-{$niveau}-{$annee}-{$seqFormatted}";
    }

    /**
     * Générer un username unique basé sur le prénom et le nom.
     *
     * @param string $prenom
     * @param string $nom
     * @return string
     */
    public static function genererUsername($prenom, $nom)
    {
        // Nettoyer et formater le prénom et le nom
        $prenom = self::nettoyerChaine($prenom);
        $nom = self::nettoyerChaine($nom);

        // Créer le username de base
        $username = strtolower($prenom) . '.' . strtolower($nom);

        // Vérifier si le username existe déjà
        $baseUsername = $username;
        $i = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $i;
            $i++;
        }

        return $username;
    }

    /**
     * Générer un mot de passe aléatoire sécurisé.
     *
     * @param int $length Longueur du mot de passe
     * @return string
     */
    public static function genererMotDePasse($length = 10)
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_-+=';

        $all = $lowercase . $uppercase . $numbers . $special;

        // Garantir au moins un caractère de chaque type
        $password =
            $lowercase[rand(0, strlen($lowercase) - 1)] .
            $uppercase[rand(0, strlen($uppercase) - 1)] .
            $numbers[rand(0, strlen($numbers) - 1)] .
            $special[rand(0, strlen($special) - 1)];

        // Compléter avec des caractères aléatoires
        for ($i = 0; $i < $length - 4; $i++) {
            $password .= $all[rand(0, strlen($all) - 1)];
        }

        // Mélanger le mot de passe
        return str_shuffle($password);
    }

    /**
     * Obtenir l'URL de la photo de profil de l'étudiant
     *
     * @return string|null
     */
    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) {
            return null;
        }
        
        // Chemins possibles pour la photo (pour compatibilité)
        $paths = [
            'photos/etudiants/' . $this->photo,  // Nouveau chemin
            $this->photo,                        // Ancien chemin direct
        ];
        
        // Vérifier quel chemin existe
        foreach ($paths as $path) {
            $fullPath = storage_path('app/public/' . $path);
            if (file_exists($fullPath)) {
                return asset('storage/' . $path);
            }
        }
        
        // Par défaut, retourner le nouveau chemin
        return asset('storage/photos/etudiants/' . $this->photo);
    }

    /**
     * Nettoyer une chaîne pour la génération d'username.
     *
     * @param string $chaine
     * @return string
     */
    private static function nettoyerChaine($chaine)
    {
        // Utiliser directement la méthode alternative sans vérifier l'extension intl
        $chaine = self::removeAccents($chaine);

        // Remplacer les caractères spéciaux par des espaces
        $chaine = preg_replace('/[^a-zA-Z0-9]/', ' ', $chaine);

        // Remplacer les espaces multiples par un seul espace
        $chaine = preg_replace('/\s+/', ' ', $chaine);

        // Découper en mots et prendre le premier
        $mots = explode(' ', trim($chaine));
        return $mots[0];
    }

    /**
     * Fonction alternative pour supprimer les accents sans l'extension intl
     *
     * @param string $string
     * @return string
     */
    private static function removeAccents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = [
            // Decompositions for Latin-1 Supplement
            'ª' => 'a', 'º' => 'o', 'À' => 'A', 'Á' => 'A',
            'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'Æ' => 'AE', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
            'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
            'Ö' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
            'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'TH', 'ß' => 's',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y',
            // Decompositions for Latin Extended-A
            'Œ' => 'OE', 'œ' => 'oe', 'Š' => 'S', 'š' => 's',
            'Ÿ' => 'Y', 'Ž' => 'Z', 'ž' => 'z'
        ];

        return strtr($string, $chars);
    }

    /**
     * Accesseur pour le champ 'genre' (alias de 'sexe')
     *
     * @return string
     */
    public function getGenreAttribute()
    {
        return $this->sexe;
    }

    /**
     * Mutateur pour le champ 'genre' (alias de 'sexe')
     *
     * @param string $value
     * @return void
     */
    public function setGenreAttribute($value)
    {
        $this->attributes['sexe'] = $value;
    }

    /**
     * Accesseur pour le champ 'email_personnel' qui retourne l'email de l'utilisateur si celui-ci est vide
     *
     * @return string|null
     */
    public function getEmailPersonnelAttribute($value)
    {
        // Si email_personnel n'est pas vide, le retourner
        if (!empty($value)) {
            return $value;
        }

        // Sinon, retourner l'email de l'utilisateur associé si disponible
        if ($this->user_id && $this->user) {
            return $this->user->email;
        }

        // Si aucun des deux n'est disponible, retourner null
        return null;
    }

    /**
     * Relation avec la classe de l'étudiant via son inscription active.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function classe()
    {
        return $this->hasOneThrough(
            ESBTPClasse::class,
            ESBTPInscription::class,
            'etudiant_id', // Clé étrangère sur la table intermédiaire (inscriptions)
            'id', // Clé primaire sur la table cible (classes)
            'id', // Clé primaire sur la table source (etudiants)
            'classe_id' // Clé étrangère sur la table intermédiaire (inscriptions)
        )->where('esbtp_inscriptions.status', 'active');
    }

    /**
     * Relation avec l'inscription (uniquement l'inscription active).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inscription()
    {
        return $this->hasOne(ESBTPInscription::class, 'etudiant_id')
            ->whereHas('anneeUniversitaire', function($query) {
                $query->where('is_current', true);
            })
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Récupère les inscriptions en attente de validation.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingInscriptionsAttribute()
    {
        return $this->inscriptions()->where('status', 'pending')->get();
    }

    /**
     * Vérifie si l'étudiant a au moins une inscription en attente.
     *
     * @return bool
     */
    public function getHasPendingInscriptionAttribute()
    {
        return $this->inscriptions()->where('status', 'pending')->exists();
    }
}
