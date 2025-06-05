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

    /**
     * Relations
     */
    public function configuration()
    {
        return $this->belongsTo(ESBTPConfiguration::class, 'configuration_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Clean old backups (keep only last 10 per configuration)
     */
    public static function cleanOldBackups()
    {
        $configurations = ESBTPConfiguration::all();

        foreach ($configurations as $config) {
            $backups = self::where('configuration_id', $config->id)
                          ->orderBy('backup_date', 'desc')
                          ->skip(10)
                          ->take(PHP_INT_MAX)
                          ->get();

            foreach ($backups as $backup) {
                $backup->delete();
            }
        }
    }
}