<?php

namespace App\Models;

use App\Services\FraisScopeResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPFraisSubscription extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_frais_subscriptions';

    protected $fillable = [
        'inscription_id',
        'frais_category_id',
        'selected_option_id',
        'amount',
        'is_active',
        'subscribed_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
    ];

    protected $auditInclude = [
        'inscription_id',
        'frais_category_id',
        'selected_option_id',
        'amount',
        'is_active',
        'subscribed_at',
        'notes',
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function fraisCategory(): BelongsTo
    {
        return $this->belongsTo(ESBTPFraisCategory::class, 'frais_category_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(ESBTPFraisOption::class, 'selected_option_id');
    }

    public function getFraisConfigurationAttribute()
    {
        if ($this->selected_option_id && $this->selectedOption) {
            return $this->selectedOption->configuration
                ?? $this->selectedOption->fraisConfiguration
                ?? null;
        }

        $this->loadMissing('inscription.classe.parcours.mention.domaine');

        if ($this->inscription) {
            $scope = app(FraisScopeResolver::class)->resolveForInscription($this->inscription);
            return ESBTPFraisConfiguration::getApplicableForScope($this->frais_category_id, $scope);
        }

        return ESBTPFraisConfiguration::where('frais_category_id', $this->frais_category_id)
            ->where('is_active', true)
            ->first();
    }

    public function getConfigurationNameAttribute()
    {
        $config = $this->frais_configuration;

        return $config
            ? ($config->fraisCategory->name ?? $this->fraisCategory?->name ?? 'N/A')
            : ($this->fraisCategory ? $this->fraisCategory->name : 'N/A');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeForInscription($query, $inscriptionId)
    {
        return $query->where('inscription_id', $inscriptionId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('frais_category_id', $categoryId);
    }

    public static function isSubscribed($inscriptionId, $fraisCategoryId): bool
    {
        return self::where('inscription_id', $inscriptionId)
            ->where('frais_category_id', $fraisCategoryId)
            ->where('is_active', true)
            ->exists();
    }

    public static function subscribe($inscriptionId, $fraisCategoryId, $amount, $userId, $notes = null)
    {
        return self::updateOrCreate(
            [
                'inscription_id' => $inscriptionId,
                'frais_category_id' => $fraisCategoryId,
            ],
            [
                'amount' => $amount,
                'is_active' => true,
                'subscribed_at' => Carbon::now(),
                'created_by' => $userId,
                'notes' => $notes,
            ]
        );
    }

    public static function unsubscribe($inscriptionId, $fraisCategoryId)
    {
        return self::where('inscription_id', $inscriptionId)
            ->where('frais_category_id', $fraisCategoryId)
            ->update(['is_active' => false]);
    }

    public static function getActiveSubscriptions($inscriptionId)
    {
        return self::with(['fraisCategory'])
            ->where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->get();
    }

    public static function resolveSubscriptionsForStudentContext(ESBTPInscription $inscription)
    {
        return self::with(['fraisCategory', 'selectedOption', 'inscription.classe.parcours.mention.domaine'])
            ->where('inscription_id', $inscription->id)
            ->where('is_active', true)
            ->get();
    }

    public static function getSubscribedOptionalFeesForInscription(ESBTPInscription $inscription)
    {
        return self::resolveSubscriptionsForStudentContext($inscription)
            ->filter(fn (self $subscription) => optional($subscription->fraisCategory)->is_mandatory === false)
            ->values();
    }

    public static function getTotalSubscribedAmount($inscriptionId)
    {
        return self::where('inscription_id', $inscriptionId)
            ->where('is_active', true)
            ->sum('amount');
    }

    public static function getCategoryStats($fraisCategoryId)
    {
        return [
            'total_subscriptions' => self::where('frais_category_id', $fraisCategoryId)
                ->where('is_active', true)
                ->count(),
            'total_amount' => self::where('frais_category_id', $fraisCategoryId)
                ->where('is_active', true)
                ->sum('amount'),
            'average_amount' => self::where('frais_category_id', $fraisCategoryId)
                ->where('is_active', true)
                ->avg('amount'),
        ];
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' FCFA';
    }

    public function getIsRecentAttribute()
    {
        return $this->subscribed_at->gt(Carbon::now()->subDay());
    }

    public function getStudentNameAttribute()
    {
        return $this->inscription->etudiant->nom . ' ' . $this->inscription->etudiant->prenom;
    }
}
