<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLMDDomaine extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_domaines';

    protected $fillable = [
        'name', 'code', 'nature', 'description', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        // UFR, faculte, ecole, institut — null pour un domaine au sens du referentiel LMD.
        'nature' => \App\Enums\NatureComposante::class,
    ];

    public function mentions()
    {
        return $this->hasMany(ESBTPLMDMention::class, 'domaine_id');
    }

}
