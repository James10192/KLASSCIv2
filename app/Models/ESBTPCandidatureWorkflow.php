<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPCandidatureWorkflow extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public const STATE_ACCEPTED = 'accepted';
    public const STATE_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATE_AWAITING_DOCUMENTS = 'awaiting_documents';
    public const STATE_AWAITING_ACTIVATION = 'awaiting_activation';
    public const STATE_AWAITING_STUDENT = 'awaiting_student';
    public const STATE_READY_TO_FINALIZE = 'ready_to_finalize';
    public const STATE_COMPLETED = 'completed';

    protected $table = 'esbtp_candidature_workflows';

    protected $fillable = [
        'candidature_id',
        'etudiant_id',
        'paiement_id',
        'selected_class_id',
        'final_inscription_id',
        'state',
        'paid_at',
        'paid_by',
        'documents_validated_at',
        'documents_validated_by',
        'activation_token_hash',
        'activation_token_expires_at',
        'activation_token_used_at',
        'access_activated_at',
        'class_selected_at',
        'class_selected_by',
        'class_locked_at',
        'profile_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'documents_validated_at' => 'datetime',
        'activation_token_expires_at' => 'datetime',
        'activation_token_used_at' => 'datetime',
        'access_activated_at' => 'datetime',
        'class_selected_at' => 'datetime',
        'class_locked_at' => 'datetime',
        'profile_payload' => 'array',
    ];

    protected $auditInclude = [
        'candidature_id',
        'etudiant_id',
        'paiement_id',
        'selected_class_id',
        'final_inscription_id',
        'state',
        'paid_at',
        'paid_by',
        'documents_validated_at',
        'documents_validated_by',
        'access_activated_at',
        'class_selected_at',
        'class_selected_by',
        'class_locked_at',
    ];

    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(ESBTPCandidature::class, 'candidature_id');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(ESBTPPaiement::class, 'paiement_id');
    }

    public function selectedClass(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'selected_class_id');
    }

    public function finalInscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'final_inscription_id');
    }

    public function paymentRecorded(): bool
    {
        return $this->paid_at !== null && $this->paiement_id !== null;
    }

    public function documentsValidated(): bool
    {
        return $this->documents_validated_at !== null;
    }

    public function accessActivated(): bool
    {
        return $this->access_activated_at !== null;
    }

    public function classIsLocked(): bool
    {
        return $this->class_locked_at !== null;
    }
}
