<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPConfigurationBackup extends Model
{
    use HasFactory;

    protected $table = 'esbtp_configuration_backups';

    protected $fillable = [
        'configuration_id',
        'key',
        'old_value',
        'backup_date',
        'created_by'
    ];

    protected $casts = [
        'old_value' => 'json',
        'backup_date' => 'datetime'
    ];


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}