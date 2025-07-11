<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ESBTPInscription;

class ESBTPInscriptionWorkflowHistory extends Model
{
    use HasFactory;

    /**
     * Table associée
     * @var string
     */
    protected $table = 'esbtp_inscription_workflow_histories';

    /**
     * Attributs assignables en masse
     * @var array
     */
    protected $fillable = [
        'inscription_id',
        'etape_from',
        'etape_to',
        'action',
        'user_id',
        'action_timestamp',
        'commentaires',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * Attributs castés
     * @var array
     */
    protected $casts = [
        'action_timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Désactiver les timestamps Laravel par défaut
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relation : utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation : inscription
     */
    public function inscription()
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    /**
     * Scope pour ordonner par timestamp d'action (plus récent d'abord).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('action_timestamp', 'desc');
    }

    /**
     * Scope pour filtrer par période.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $dateFrom
     * @param string $dateTo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInPeriod($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween('action_timestamp', [$dateFrom, $dateTo]);
    }

    /**
     * Créer une entrée d'historique workflow.
     *
     * @param int $inscriptionId
     * @param string|null $etapeFrom
     * @param string $etapeTo
     * @param string $action
     * @param int $userId
     * @param string|null $commentaires
     * @param array|null $metadata
     * @return self
     */
    public static function createEntry(
        int $inscriptionId, 
        ?string $etapeFrom, 
        string $etapeTo, 
        string $action, 
        int $userId, 
        ?string $commentaires = null, 
        ?array $metadata = null
    ): self {
        return self::create([
            'inscription_id' => $inscriptionId,
            'etape_from' => $etapeFrom,
            'etape_to' => $etapeTo,
            'action' => $action,
            'user_id' => $userId,
            'action_timestamp' => now(),
            'commentaires' => $commentaires,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Obtenir le texte descriptif de l'action.
     *
     * @return string
     */
    public function getActionDescriptionAttribute(): string
    {
        $descriptions = [
            'creation' => 'Création de l\'inscription',
            'validation' => 'Validation de l\'inscription',
            'rejet' => 'Rejet de l\'inscription',
            'creation_etudiant' => 'Création du compte étudiant',
            'modification' => 'Modification de l\'inscription',
            'annulation' => 'Annulation de l\'inscription',
            'reactivation' => 'Réactivation de l\'inscription',
        ];

        return $descriptions[$this->action] ?? $this->action;
    }

    /**
     * Obtenir la couleur associée à l'action pour l'affichage.
     *
     * @return string
     */
    public function getActionColorAttribute(): string
    {
        $colors = [
            'creation' => 'blue',
            'validation' => 'green',
            'rejet' => 'red',
            'creation_etudiant' => 'purple',
            'modification' => 'yellow',
            'annulation' => 'red',
            'reactivation' => 'green',
        ];

        return $colors[$this->action] ?? 'gray';
    }
}
