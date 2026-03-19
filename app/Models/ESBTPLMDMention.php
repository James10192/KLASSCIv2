<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLMDMention extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_mentions';

    protected $fillable = [
        'name', 'code', 'description', 'domaine_id', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function domaine()
    {
        return $this->belongsTo(ESBTPLMDDomaine::class, 'domaine_id');
    }

    public function parcours()
    {
        return $this->hasMany(ESBTPLMDParcours::class, 'mention_id');
    }

}
