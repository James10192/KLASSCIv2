<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupPortalSsoLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_email_requested',
        'issued_by',
        'group_member_id',
        'user_id',
        'redirect_to',
        'ip_address',
        'user_agent',
        'success',
        'error_reason',
    ];

    protected $casts = [
        'success' => 'boolean',
        'created_at' => 'datetime',
        'group_member_id' => 'integer',
        'user_id' => 'integer',
    ];
}
