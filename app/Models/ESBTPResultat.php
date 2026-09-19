<?php

namespace App\Models;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Services\AppreciationScaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPResultat extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist — résultats académiques officiels).
     *
     * Note : ce modèle stocke un résultat par matière (la décision
     * finale annuelle est calculée à partir des résultats matière par
     * matière). Le champ `appreciation` couvre la mention.
     *
     * @var array
     */
    protected $auditInclude = [
        'etudiant_id',
        'classe_id',
        'matiere_id',
        'periode',
        'annee_universitaire_id',
        'moyenne',
        'coefficient',
        'rang',
        'appreciation',
        'enseignant_id',
        'type',
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

    protected static function booted(): void
    {
        static::addGlobalScope('not_archived', function (Builder $builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::saving(function (self $resultat): void {
            $resultat->assertMatiereCoherenteAvecLaClasse();
        });
    }

    /**
     * Une moyenne manuelle ne se pose pas sur une matiere etrangere a la classe.
     *
     * CE GARDE EXISTE PARCE QUE SON JUMEAU NE SUFFISAIT PAS. `ESBTPEvaluation`
     * refuse depuis aout 2026 qu'une ECUE soit evaluee dans une classe BTS ;
     * personne n'avait vu que la moyenne manuelle atteint la MEME ligne de
     * bulletin sans passer par une evaluation. `ESBTPResultatController` ecrit
     * ici depuis trois endroits (dont `bulkUpdateMoyennes`, en AJAX), et sa
     * `FormRequest` ne valide qu'un `exists:esbtp_matieres,id` : rien ne
     * rapprochait la matiere du systeme de la classe.
     *
     * Le garde est au modele, et non dans les trois appelants, pour la raison
     * qui a fait echouer les quatre premieres passes du chantier : un filtre
     * pose chez l'appelant demande a chaque futur ecran de s'en souvenir.
     *
     * DEUX `find()` PAR LIGNE CREEE, ET C'EST ASSUME. `bulkUpdateMoyennes()`
     * enregistre une matiere pour tous les eleves d'une classe d'un seul envoi :
     * sur 60 eleves, cela fait 120 lectures par cle primaire dont 118
     * redondantes. Un memo statique les supprimerait — et servirait des lignes
     * perimees d'un test a l'autre sous `RefreshDatabase`, ou les identifiants
     * se reutilisent apres chaque rollback. Une lecture par cle primaire coute
     * moins cher qu'un garde qui se trompe. Si le cout se mesure un jour, c'est
     * a l'appelant en lot de valider une fois avant sa boucle, pas a ce garde
     * de devenir un cache.
     *
     * MEME EXCEPTION QUE POUR L'EVALUATION, et pour la meme raison : le
     * controle ne se declenche qu'a la creation, ou si la classe ou la matiere
     * change. Une ligne historiquement incoherente reste modifiable sur sa
     * moyenne ou son rang — sinon on ne pourrait meme plus la corriger, et
     * c'est exactement ce qui avait rendu une liaison posee par erreur
     * inextirpable ailleurs dans ce chantier.
     */
    protected function assertMatiereCoherenteAvecLaClasse(): void
    {
        $doitControler = ! $this->exists || $this->isDirty(['matiere_id', 'classe_id']);

        if (! $doitControler || ! $this->matiere_id || ! $this->classe_id) {
            return;
        }

        $classe = ESBTPClasse::find($this->classe_id);
        $matiere = ESBTPMatiere::find($this->matiere_id);

        if (! $classe || ! $matiere) {
            return;
        }

        if (CoherenceSystemeAcademique::estCoherente(
            $classe->systeme_academique,
            $matiere->unite_enseignement_id
        )) {
            return;
        }

        throw ValidationException::withMessages([
            'matiere_id' => CoherenceSystemeAcademique::messageDeRefus(
                $classe->systeme_academique,
                (string) $classe->name,
                (string) $matiere->name
            ),
        ]);
    }

    /**
     * Moyennes enregistrees pour une periode alors que la matiere n'y a plus
     * aucune note.
     *
     * Une evaluation deplacee d'un semestre a l'autre laisse sa ligne derriere
     * elle : la generation la reecrit sans jamais l'effacer, le calcul courant
     * la fusionne comme moyenne manuelle, et la valeur perimee continue de
     * s'afficher partout sans qu'aucun ecart ne la trahisse.
     *
     * La condition vit ICI, et seulement ici : la detection du pre-controle,
     * l'encart de la page etudiant et la suppression a la demande la lisent
     * tous trois. Trois copies divergeaient -- l'une en SQL brut ignorait
     * `deleted_at` et `archived_at`, si bien qu'une ligne deja supprimee
     * restait comptee et que l'ecran redemandait sans fin la meme suppression.
     * Passer par ce scope force Eloquent, donc les deux gardes du modele.
     *
     * @param  list<string>  $periodes  aliases inclus (periodeAliases)
     */
    public function scopeSansNoteSurLaPeriode(Builder $query, int $classeId, int $anneeUniversitaireId, array $periodes): Builder
    {
        return $query
            ->where('esbtp_resultats.classe_id', $classeId)
            ->where('esbtp_resultats.annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('esbtp_resultats.periode', $periodes)
            ->whereNotExists(fn ($sub) => $sub
                ->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('esbtp_notes as n')
                ->join('esbtp_evaluations as e', 'e.id', '=', 'n.evaluation_id')
                ->whereColumn('n.etudiant_id', 'esbtp_resultats.etudiant_id')
                ->whereColumn('n.matiere_id', 'esbtp_resultats.matiere_id')
                ->where('e.classe_id', $classeId)
                ->where('e.annee_universitaire_id', $anneeUniversitaireId)
                ->where('e.status', '!=', 'cancelled')
                ->whereIn('e.periode', $periodes));
    }

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_resultats';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'etudiant_id',
        'classe_id',
        'matiere_id',
        'periode',
        'annee_universitaire_id',
        'moyenne',
        'coefficient',
        'rang',
        'appreciation',
        'enseignant_id',
        'type',
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
        'coefficient' => 'float',
        'rang' => 'integer'
    ];

    /**
     * Les attributs avec des valeurs par défaut.
     *
     * @var array
     */
    protected $attributes = [
        'rang' => null,
        'appreciation' => null
    ];

    /**
     * Relation avec l'étudiant associé à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec la classe associée à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
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
     * Récupère toutes les matières associées à cette classe et période.
     * Cette méthode est utilisée pour obtenir une collection des matières liées à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function matieres()
    {
        // Récupérer toutes les matières associées aux résultats de cet étudiant, classe, période et année
        $matieresIds = ESBTPResultat::where('etudiant_id', $this->etudiant_id)
            ->where('classe_id', $this->classe_id)
            ->where('periode', $this->periode)
            ->where('annee_universitaire_id', $this->annee_universitaire_id)
            ->pluck('matiere_id')
            ->unique()
            ->toArray();

        // Récupérer les matières correspondantes
        if (!empty($matieresIds)) {
            return ESBTPMatiere::whereIn('id', $matieresIds)->get();
        }

        return collect();
    }

    /**
     * Relation avec l'année universitaire associée à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
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
     * Relation avec le dernier utilisateur ayant mis à jour ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relation avec l'enseignant assigné à ce résultat.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enseignant()
    {
        return $this->belongsTo(\App\Models\User::class, 'enseignant_id');
    }

    /**
     * Obtenir la moyenne pondérée (moyenne * coefficient).
     *
     * @return float
     */
    public function getMoyennePondereeAttribute()
    {
        return round($this->moyenne * $this->coefficient, 2);
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
