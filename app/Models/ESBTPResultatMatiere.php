<?php

namespace App\Models;

use App\Services\AppreciationScaleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPResultatMatiere extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_resultats_matieres';

    /**
     * Ce qu'une ligne de bulletin signifie.
     *
     * Jusqu'ici une ligne portait une moyenne, et une matiere sans note ne
     * figurait pas du tout : le lecteur ne pouvait pas distinguer « le
     * professeur n'a pas rendu ses notes » de « cet etudiant en est dispense ».
     * Ces trois etats le disent, et seul `note` entre dans les moyennes.
     */
    public const STATUT_NOTE = 'note';

    public const STATUT_DISPENSE = 'dispense';

    public const STATUT_NON_NOTE = 'non_note';

    /** Ce qu'on ecrit dans la colonne moyenne quand il n'y a pas de note. */
    public const SYMBOLE_TROU = '—';

    /**
     * Pose la ligne d'une matiere sur un bulletin, meme si une ligne SUPPRIMEE
     * occupe deja la place.
     *
     * La cle unique `(bulletin_id, matiere_id)` ne porte pas `deleted_at` :
     * une ligne soft-deletee occupe donc la place tout en etant invisible aux
     * requetes ordinaires. `updateOrCreate` ne la trouvait pas, tentait un
     * INSERT, et la base repondait « Duplicate entry » — une erreur 500
     * DEFINITIVE sur cet etudiant, puisque la ligne fantome ne disparait
     * jamais d'elle-meme.
     *
     * C'est exactement ce qui arrive apres une regeneration : la generation
     * termine en soft-deletant les matieres qu'elle n'a pas retenues
     * (`persistOfficialSubjectRows`), et la generation SUIVANTE, si la matiere
     * revient, butait dessus. Les bulletins d'une classe ou une matiere a ete
     * retiree puis remise etaient donc definitivement ingenerables.
     *
     * `ESBTPResultat` avait deja recu ce traitement, avec le meme commentaire,
     * dans `BulletinService::persistSubjectAverages()`. Sa table jumelle avait
     * ete oubliee ; cette methode existe pour qu'on ne puisse plus l'oublier.
     *
     * @param  array<string, mixed>  $valeurs
     */
    public static function poserSurLeBulletin(int $bulletinId, int $matiereId, array $valeurs): self
    {
        $ligne = static::withTrashed()->updateOrCreate(
            [
                'bulletin_id' => $bulletinId,
                'matiere_id' => $matiereId,
            ],
            $valeurs,
        );

        // La ligne revit : une matiere de nouveau au bulletin n'a aucune raison
        // de rester marquee supprimee.
        if ($ligne->trashed()) {
            $ligne->restore();
        }

        return $ligne;
    }

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'bulletin_id',
        'matiere_id',
        'moyenne',
        'statut',
        'motif_dispense',
        'dispense_id',
        'coefficient',
        'rang',
        'appreciation',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'moyenne' => 'decimal:2',
        'coefficient' => 'integer'
    ];

    /**
     * Indique si les timestamps sont utilisés.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relation avec le bulletin associé à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bulletin()
    {
        return $this->belongsTo(ESBTPBulletin::class, 'bulletin_id');
    }

    /**
     * Relation avec la matière associée à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    /**
     * Relation avec le créateur de ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec le mise à jour de ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir la moyenne pondérée (moyenne * coefficient).
     *
     * @return float
     */
    public function getMoyennePondereeAttribute()
    {
        // Une ligne sans moyenne (dispense, matiere non notee) n'a pas de
        // moyenne ponderee. Rendre 0 la ferait compter comme un echec partout
        // ou ce champ est somme.
        if ($this->moyenne === null) {
            return null;
        }

        return round($this->moyenne * $this->coefficient, 2);
    }

    /**
     * Les lignes qui comptent dans une moyenne.
     *
     * Meme definition que estNotee(), cote requete : une matiere dispensee ou
     * non notee ne doit entrer ni au numerateur ni au denominateur.
     */
    public function scopeNotees($query)
    {
        return $query->whereNotNull('moyenne')->where('statut', self::STATUT_NOTE);
    }

    /**
     * Cette ligne porte-t-elle une note, ou seulement un etat ?
     *
     * Definition unique, valable aussi pour les lignes vivantes du service de
     * bulletin (de simples objets, pas des modeles) : elles traversent les
     * gabarits PDF avant d'etre enregistrees, et doivent y etre jugees
     * exactement comme les lignes deja en base. Une ligne venue d'ailleurs et
     * sans statut est notee, comme elle l'a toujours ete.
     *
     * @param  object|array  $ligne
     */
    public static function ligneNotee($ligne): bool
    {
        $ligne = is_array($ligne) ? (object) $ligne : $ligne;

        $statut = $ligne->statut ?? self::STATUT_NOTE;

        return $statut === self::STATUT_NOTE && ($ligne->moyenne ?? null) !== null;
    }

    public function estNotee(): bool
    {
        return self::ligneNotee($this);
    }

    /**
     * Ce qu'on ecrit a la place d'une appreciation quand il n'y a pas de note.
     *
     * Un bulletin qui laisse une case vide n'apprend rien au lecteur : il doit
     * dire pourquoi la note manque.
     *
     * @param  object|array  $ligne
     */
    public static function libelleEtat($ligne): string
    {
        $ligne = is_array($ligne) ? (object) $ligne : $ligne;

        return match ($ligne->statut ?? self::STATUT_NOTE) {
            self::STATUT_DISPENSE => 'Dispensé',
            self::STATUT_NON_NOTE => 'Non notée',
            default => '-',
        };
    }

    /** Ce que la colonne « Moyenne » affiche : la note, ou le symbole de trou. */
    public function moyenneLisible(): string
    {
        if (! $this->estNotee()) {
            return self::SYMBOLE_TROU;
        }

        return number_format((float) $this->moyenne, 2, ',', ' ');
    }

    /**
     * Obtenir la mention associée à la moyenne.
     *
     * @return string
     */
    public function getMentionAttribute()
    {
        return app(AppreciationScaleService::class)->labelFor($this->moyenne === null ? null : (float) $this->moyenne, 'bts');
    }

    /**
     * Déterminer l'appréciation associée à la moyenne.
     *
     * @return string
     */
    public function determinerAppreciation()
    {
        return app(AppreciationScaleService::class)->labelFor($this->moyenne === null ? null : (float) $this->moyenne, 'bts');
    }
}
