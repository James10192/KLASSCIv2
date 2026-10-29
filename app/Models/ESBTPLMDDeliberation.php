<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLMDDeliberation extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_deliberations';

    protected $fillable = [
        'bulletin_id', 'type', 'decision', 'mention_honorifique',
        'observations', 'jury_date', 'president_jury',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'jury_date' => 'date',
    ];

    public function bulletin()
    {
        return $this->belongsTo(ESBTPLMDBulletin::class, 'bulletin_id');
    }

}
