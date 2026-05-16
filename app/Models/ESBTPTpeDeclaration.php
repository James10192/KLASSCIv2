<?php

namespace App\Models;

use App\Enums\TpeDeclarationStatut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * TPE — Travail Personnel Étudiant (déclaration auto par étudiant).
 *
 * Voir migration `2026_05_16_110104_create_esbtp_tpe_declarations_table.php`
 * et `App\Services\LMD\Tpe\TpeValidationStrategy` pour le pilotage workflow.
 */
class ESBTPTpeDeclaration extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_tpe_declarations';

    protected $fillable = [
        'etudiant_id',
        'matiere_id',
        'annee_universitaire_id',
        'semaine_debut',
        'heures',
        'description',
        'statut',
        'validated_by',
        'validated_at',
        'commentaire_rejet',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'semaine_debut' => 'date',
        'heures' => 'decimal:2',
        'statut' => TpeDeclarationStatut::class,
        'validated_at' => 'datetime',
    ];

    /**
     * Whitelist d'audit (anti audit-explosion).
     *
     * Voir feedback_owen_it_auditable_pattern.md + rule pre-merge-checklist.md
     * — Auditable obligatoirement assorti d'$auditInclude.
     */
    protected $auditInclude = [
        'heures',
        'description',
        'statut',
        'validated_by',
        'commentaire_rejet',
    ];

    // ===== Relations =====

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * En LMD, matiere = ECUE (esbtp_matieres avec unite_enseignement_id != null).
     */
    public function matiere(): BelongsTo
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * L'enseignant qui a validé/rejeté la déclaration (NULL tant que statut == EN_ATTENTE).
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ===== Scopes =====

    public function scopeValide($query)
    {
        return $query->where('statut', TpeDeclarationStatut::VALIDE->value);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', TpeDeclarationStatut::EN_ATTENTE->value);
    }

    public function scopeRejete($query)
    {
        return $query->where('statut', TpeDeclarationStatut::REJETE->value);
    }

    /**
     * Filtre les déclarations dont l'ECUE a un enseignant_principal_id = $user->id
     * dans esbtp_planifications_academiques.
     *
     * Source canonique : rule globale klassci-classe-matieres.md
     * (matière = ECUE → planification académique par filière/niveau/semestre).
     */
    public function scopePourEnseignant($query, User $user)
    {
        return $query->whereExists(function ($sub) use ($user) {
            $sub->select(\DB::raw(1))
                ->from('esbtp_planifications_academiques as pa')
                ->whereColumn('pa.matiere_id', 'esbtp_tpe_declarations.matiere_id')
                ->where('pa.enseignant_principal_id', $user->id)
                ->where('pa.is_active', true);
        });
    }

    // ===== Méthodes métier =====

    /**
     * Un enseignant peut valider/rejeter une déclaration ssi il est l'enseignant
     * principal de l'ECUE concerné dans la planification académique active.
     *
     * (Le superAdmin passe via Gate::before — pas besoin de l'expliciter ici.)
     */
    public function canBeValidatedBy(User $user): bool
    {
        if (! $this->statut->isPendingTeacherAction()) {
            return false;
        }

        return ESBTPPlanificationAcademique::query()
            ->where('matiere_id', $this->matiere_id)
            ->where('enseignant_principal_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function markValidatedBy(User $user): void
    {
        $this->statut = TpeDeclarationStatut::VALIDE;
        $this->validated_by = $user->id;
        $this->validated_at = now();
        $this->commentaire_rejet = null;
        $this->updated_by = $user->id;
        $this->save();
    }

    public function markRejectedBy(User $user, string $commentaire): void
    {
        $this->statut = TpeDeclarationStatut::REJETE;
        $this->validated_by = $user->id;
        $this->validated_at = now();
        $this->commentaire_rejet = $commentaire;
        $this->updated_by = $user->id;
        $this->save();
    }
}
