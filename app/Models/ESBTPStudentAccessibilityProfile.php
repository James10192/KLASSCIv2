<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
