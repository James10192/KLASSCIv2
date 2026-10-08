<?php

namespace App\Models;

use App\Models\Relations\LmdSnapshotHasMany;
use App\Models\Traits\HasAuditTrail;
use App\Services\AppreciationScaleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ESBTPLMDBulletin extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_bulletins';

    protected $fillable = [
        'etudiant_id', 'classe_id', 'parcours_id', 'annee_universitaire_id',
        'semestre', 'niveau', 'domaine_label', 'mention_label', 'parcours_label',
        'affectation_status',
        'moyenne_generale', 'credits_capitalises', 'credits_totaux',
        'rang', 'effectif', 'decision_deliberation', 'appreciation',
        'absences_justifiees', 'absences_non_justifiees', 'is_published',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'semestre' => 'integer',
        'moyenne_generale' => 'decimal:2',
        'credits_capitalises' => 'integer',
        'credits_totaux' => 'integer',
        'rang' => 'integer',
        'effectif' => 'integer',
        'absences_justifiees' => 'integer',
        'absences_non_justifiees' => 'integer',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ESBTPLMDBulletin $bulletin): void {
            if ($bulletin->exists && (bool) $bulletin->getOriginal('is_published')) {
                return;
            }

            if (! $bulletin->etudiant_id || ! $bulletin->classe_id || ! $bulletin->annee_universitaire_id) {
                return;
            }

            $statut = ESBTPInscription::query()
                ->where('etudiant_id', $bulletin->etudiant_id)
                ->where('classe_id', $bulletin->classe_id)
                ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
                ->orderByDesc('id')
                ->value('affectation_status');

            if ($statut !== null && trim((string) $statut) !== '') {
                $bulletin->affectation_status = (string) $statut;
            }

            // Juste avant la première publication, les enfants relisent le
            // planning courant. Le parent n'est pas encore publié en base : la
            // garde d'immuabilité des résultats autorise donc ce dernier refresh.
            if ($bulletin->exists
                && $bulletin->isDirty('is_published')
                && (bool) $bulletin->is_published) {
                foreach ($bulletin->resultatsECUEs()->get() as $resultat) {
                    $resultat->unsetRelation('bulletin');
                    $resultat->save();
                }
            }

            $dirtyAcademique = array_diff(
                array_keys($bulletin->getDirty()),
                ['is_published', 'updated_at', 'affectation_status']
            );
            $publicationSeule = $bulletin->exists
                && $bulletin->isDirty('is_published')
                && (bool) $bulletin->is_published
                && $dirtyAcademique === [];

            if (! $publicationSeule && $dirtyAcademique !== []) {
                $publieDansCohorte = static::query()
                    ->where('classe_id', $bulletin->classe_id)
                    ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
                    ->where('semestre', $bulletin->semestre)
                    ->where('is_published', true)
                    ->when($bulletin->exists, fn ($query) => $query->whereKeyNot($bulletin->getKey()))
                    ->exists();

                if ($publieDansCohorte) {
                    throw ValidationException::withMessages([
                        'bulletin' => 'La cohorte contient déjà un bulletin LMD publié. Dépubliez les bulletins de cette classe/année/semestre avant toute nouvelle génération afin de préserver les rangs et statistiques du snapshot.',
                    ]);
                }
            }
        });

        static::updating(function (ESBTPLMDBulletin $bulletin): void {
            if (! (bool) $bulletin->getOriginal('is_published')) {
                return;
            }

            $dirty = array_diff(array_keys($bulletin->getDirty()), ['is_published', 'updated_at']);
            if ($dirty !== []) {
                throw ValidationException::withMessages([
                    'bulletin' => 'Ce bulletin LMD est publié et donc figé. Dépubliez-le avant toute régénération ou correction académique.',
                ]);
            }
        });

        static::deleting(function (ESBTPLMDBulletin $bulletin): void {
            if ($bulletin->is_published) {
                throw ValidationException::withMessages([
                    'bulletin' => 'Un bulletin LMD publié ne peut pas être supprimé. Dépubliez-le d’abord.',
                ]);
            }
        });
    }

    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey): LmdSnapshotHasMany
    {
        return new LmdSnapshotHasMany($query, $parent, $foreignKey, $localKey);
    }

    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function parcours()
    {
        return $this->belongsTo(ESBTPLMDParcours::class, 'parcours_id');
    }

    public function anneeUniversitaire()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function resultatsUEs()
    {
        return $this->hasMany(ESBTPLMDResultatUE::class, 'bulletin_id')->orderBy('id');
    }

    public function resultatsECUEs()
    {
        return $this->hasMany(ESBTPLMDResultatECUE::class, 'bulletin_id');
    }

    public function deliberation()
    {
        return $this->hasOne(ESBTPLMDDeliberation::class, 'bulletin_id');
    }

    public function scopeForJury(Builder $query, ESBTPLMDJury $jury): Builder
    {
        return $query
            ->where('annee_universitaire_id', $jury->annee_universitaire_id)
            ->when($jury->parcours_id, fn (Builder $builder, int $parcoursId) => $builder->where('parcours_id', $parcoursId))
            ->when($jury->classe_id, fn (Builder $builder, int $classeId) => $builder->where('classe_id', $classeId))
            ->when($jury->semestre, fn (Builder $builder, int $semestre) => $builder->where('semestre', $semestre));
    }

    public function getMentionGeneraleAttribute(): ?string
    {
        if ($this->moyenne_generale === null) return null;

        return app(AppreciationScaleService::class)->labelFor((float) $this->moyenne_generale, 'lmd');
    }

    public function getMentionAttribute(): ?string
    {
        return $this->mention_generale;
    }

    public function getTauxCapitalisationAttribute(): float
    {
        if ($this->credits_totaux == 0) return 0;
        return round(($this->credits_capitalises / $this->credits_totaux) * 100, 1);
    }

    public function getAffectationLabelAttribute(): string
    {
        $statut = mb_strtolower(trim((string) $this->affectation_status), 'UTF-8');

        return match ($statut) {
            'affecté', 'affecte' => 'Affecté',
            'réaffecté', 'reaffecté', 'réaffecte', 'reaffecte' => 'Réaffecté',
            'non_affecté', 'non_affecte', 'non affecté', 'non affecte' => 'Non affecté',
            default => '—',
        };
    }
}
