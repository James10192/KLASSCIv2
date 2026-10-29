<?php

namespace App\Models;

use App\Enums\StatutReclamationNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Une contestation de note déposée par un élève.
 *
 * `note_initiale` fige la valeur contestée au dépôt : si la note bouge
 * ensuite, on sait toujours ce que l'élève contestait. `enseignant_id` est
 * résolu au dépôt (voir DestinatairesReclamation) et ne suit pas les
 * réaffectations ultérieures : c'est le correcteur de l'époque qui répond.
 */
class ESBTPReclamationNote extends Model
{
    use SoftDeletes;

    protected $table = 'esbtp_reclamations_notes';

    protected $fillable = [
        'note_id', 'etudiant_id', 'evaluation_id', 'matiere_id', 'classe_id', 'annee_universitaire_id',
        'enseignant_id', 'note_initiale', 'motif', 'photo_path', 'statut',
        'avis', 'note_proposee', 'commentaire_enseignant', 'avis_par', 'avis_at',
        'commentaire_decision', 'note_finale', 'decision_par', 'decision_at',
    ];

    protected $casts = [
        'statut' => StatutReclamationNote::class,
        'note_initiale' => 'decimal:2',
        'note_proposee' => 'decimal:2',
        'note_finale' => 'decimal:2',
        'avis_at' => 'datetime',
        'decision_at' => 'datetime',
    ];

    public const AVIS_CONFIRMER = 'confirmer';
    public const AVIS_CORRIGER = 'corriger';

    public function note(): BelongsTo
    {
        return $this->belongsTo(ESBTPNote::class, 'note_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(ESBTPEvaluation::class, 'evaluation_id')->withTrashed();
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id')->withTrashed();
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    public function avisPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avis_par');
    }

    public function decisionPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_par');
    }

    public function scopeOuvertes(Builder $q): Builder
    {
        return $q->whereIn('statut', StatutReclamationNote::ouvertes());
    }

    public function estOuverte(): bool
    {
        return $this->statut->estOuverte();
    }
}
