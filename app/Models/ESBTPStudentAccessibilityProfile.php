<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPStudentAccessibilityProfile extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_student_accessibility_profiles';

    public const CATEGORIES = [
        'motrice'    => 'Motrice',
        'visuelle'   => 'Visuelle',
        'auditive'   => 'Auditive',
        'cognitive'  => 'Cognitive',
        'psychique'  => 'Psychique',
        'dys'        => 'Dys (dyslexie, dyspraxie, ...)',
        'chronique'  => 'Maladie chronique',
        'autre'      => 'Autre',
    ];

    public const ACCOMMODATIONS = [
        'tiers_temps'           => 'Tiers-temps aux examens',
        'salle_adaptee'         => 'Salle adaptée',
        'support_agrandi'       => 'Supports agrandis',
        'interprete_lsf'        => 'Interprète LSF',
        'prise_de_notes'        => 'Aide à la prise de notes',
        'ordinateur_autorise'   => 'Ordinateur autorisé',
        'repos_examen'          => 'Pauses pendant les épreuves',
        'autre'                 => 'Autre',
    ];

    /** Préfixes du single-dropdown filter sur etudiants.index */
    public const FILTER_WITH        = 'with';
    public const FILTER_WITHOUT     = 'without';
    public const FILTER_TIERS_TEMPS = 'tiers_temps';
    public const FILTER_ASSISTANT   = 'assistant';
    public const FILTER_RECOGNITION = 'recognition';
    public const FILTER_PREFIX_CATEGORY      = 'cat:';
    public const FILTER_PREFIX_ACCOMMODATION = 'acc:';

    /**
     * Règles de validation partagées entre StoreAccessibilityProfileRequest
     * (édition fiche étudiant) et AttachAccessibilityProfile (inscription).
     * Source de vérité unique pour éviter le drift.
     */
    public static function validationRules(): array
    {
        $cats = array_keys(self::CATEGORIES);
        $accs = array_keys(self::ACCOMMODATIONS);

        return [
            'has_official_recognition' => 'sometimes|boolean',
            'recognition_reference'    => 'nullable|string|max:100',
            'categories'               => 'nullable|array',
            'categories.*'             => ['string', Rule::in($cats)],
            'short_description'        => 'nullable|string|max:200',
            'full_description'         => 'nullable|string|max:5000',
            'accommodations'           => 'nullable|array',
            'accommodations.*'         => ['string', Rule::in($accs)],
            'accommodations_notes'     => 'nullable|string|max:2000',
            'requires_third_time'      => 'sometimes|boolean',
            'third_time_percentage'    => 'nullable|integer|min:0|max:100',
            'assistant_required'       => 'sometimes|boolean',
            'effective_from'           => 'nullable|date',
            'effective_to'             => 'nullable|date|after_or_equal:effective_from',
        ];
    }

    public function scopeWithCategory(Builder $q, string $key): Builder
    {
        return $q->whereJsonContains('categories', $key);
    }

    public function scopeWithAccommodation(Builder $q, string $key): Builder
    {
        return $q->whereJsonContains('accommodations', $key);
    }

    protected $fillable = [
        'etudiant_id',
        'has_official_recognition',
        'recognition_reference',
        'categories',
        'short_description',
        'full_description',
        'accommodations',
        'accommodations_notes',
        'requires_third_time',
        'third_time_percentage',
        'assistant_required',
        'effective_from',
        'effective_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'has_official_recognition' => 'boolean',
        'categories'               => 'array',
        'accommodations'           => 'array',
        'requires_third_time'      => 'boolean',
        'third_time_percentage'    => 'integer',
        'assistant_required'       => 'boolean',
        'effective_from'           => 'date',
        'effective_to'             => 'date',
    ];

    protected $auditInclude = [
        'has_official_recognition',
        'recognition_reference',
        'categories',
        'short_description',
        'full_description',
        'accommodations',
        'accommodations_notes',
        'requires_third_time',
        'third_time_percentage',
        'assistant_required',
        'effective_from',
        'effective_to',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isCurrentlyEffective(): bool
    {
        $today = now()->toDateString();

        if ($this->effective_from && $this->effective_from->toDateString() > $today) {
            return false;
        }

        if ($this->effective_to && $this->effective_to->toDateString() < $today) {
            return false;
        }

        return true;
    }

    public function categoryLabels(): array
    {
        return collect($this->categories ?? [])
            ->map(fn ($key) => self::CATEGORIES[$key] ?? $key)
            ->all();
    }

    public function accommodationLabels(): array
    {
        return collect($this->accommodations ?? [])
            ->map(fn ($key) => self::ACCOMMODATIONS[$key] ?? $key)
            ->all();
    }

    public function summaryBadge(): string
    {
        $parts = [];

        if ($this->requires_third_time) {
            $parts[] = 'Tiers-temps ' . $this->third_time_percentage . '%';
        }

        if ($this->assistant_required) {
            $parts[] = 'Assistant requis';
        }

        if (! empty($this->accommodations) && empty($parts)) {
            $parts[] = 'Aménagements';
        }

        return implode(' · ', $parts) ?: 'Profil enregistré';
    }
}
