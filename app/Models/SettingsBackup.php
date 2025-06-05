<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingsBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'backup_name',
        'description',
        'settings_data',
        'backup_type',
        'status',
        'backup_date',
        'created_by',
        'restored_by',
        'restored_at',
        'restore_notes'
    ];

    protected $casts = [
        'settings_data' => 'json',
        'backup_date' => 'datetime',
        'restored_at' => 'datetime'
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restorer()
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeManual($query)
    {
        return $query->where('backup_type', 'manual');
    }

    public function scopeAutomatic($query)
    {
        return $query->where('backup_type', 'automatic');
    }

    // Créer une sauvegarde manuelle
    public static function createManualBackup($name, $description = null, $userId = null)
    {
        try {
            $settings = Setting::all()->toArray();

            return static::create([
                'backup_name' => $name,
                'description' => $description,
                'settings_data' => $settings,
                'backup_type' => 'manual',
                'backup_date' => now(),
                'created_by' => $userId ?? auth()->id()
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la création de la sauvegarde manuelle", [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    // Restaurer une sauvegarde
    public function restore($userId = null, $notes = null)
    {
        try {
            DB::beginTransaction();

            // Créer une sauvegarde automatique avant la restauration
            $preRestoreBackup = static::create([
                'backup_name' => 'Pre-Restore Backup - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'Sauvegarde automatique avant restauration de: ' . $this->backup_name,
                'settings_data' => Setting::all()->toArray(),
                'backup_type' => 'pre_restore',
                'backup_date' => now(),
                'created_by' => $userId ?? auth()->id()
            ]);

            // Supprimer toutes les configurations actuelles
            Setting::truncate();

            // Restaurer les configurations depuis la sauvegarde
            foreach ($this->settings_data as $settingData) {
                Setting::create($settingData);
            }

            // Marquer cette sauvegarde comme restaurée
            $this->update([
                'status' => 'restored',
                'restored_by' => $userId ?? auth()->id(),
                'restored_at' => now(),
                'restore_notes' => $notes
            ]);

            // Vider le cache
            Setting::clearCache();

            DB::commit();

            Log::info("Sauvegarde restaurée avec succès", [
                'backup_id' => $this->id,
                'backup_name' => $this->backup_name,
                'user_id' => $userId ?? auth()->id()
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Erreur lors de la restauration de la sauvegarde", [
                'backup_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    // Comparer avec les configurations actuelles
    public function compareWithCurrent()
    {
        $currentSettings = Setting::all()->keyBy('key');
        $backupSettings = collect($this->settings_data)->keyBy('key');

        $differences = [
            'added' => [],
            'removed' => [],
            'modified' => []
        ];

        // Configurations ajoutées (présentes actuellement mais pas dans la sauvegarde)
        foreach ($currentSettings as $key => $setting) {
            if (!$backupSettings->has($key)) {
                $differences['added'][$key] = $setting->toArray();
            }
        }

        // Configurations supprimées (présentes dans la sauvegarde mais pas actuellement)
        foreach ($backupSettings as $key => $setting) {
            if (!$currentSettings->has($key)) {
                $differences['removed'][$key] = $setting;
            }
        }

        // Configurations modifiées
        foreach ($currentSettings as $key => $currentSetting) {
            if ($backupSettings->has($key)) {
                $backupSetting = $backupSettings[$key];
                if ($currentSetting->value !== $backupSetting['value']) {
                    $differences['modified'][$key] = [
                        'current' => $currentSetting->toArray(),
                        'backup' => $backupSetting
                    ];
                }
            }
        }

        return $differences;
    }

    // Archiver une sauvegarde
    public function archive()
    {
        $this->update(['status' => 'archived']);

        Log::info("Sauvegarde archivée", [
            'backup_id' => $this->id,
            'backup_name' => $this->backup_name
        ]);
    }

    // Nettoyer les anciennes sauvegardes automatiques
    public static function cleanupOldBackups($keepCount = 10)
    {
        $oldBackups = static::where('backup_type', 'automatic')
            ->where('status', 'active')
            ->orderBy('backup_date', 'desc')
            ->skip($keepCount)
            ->get();

        foreach ($oldBackups as $backup) {
            $backup->archive();
        }

        Log::info("Nettoyage des anciennes sauvegardes", [
            'archived_count' => $oldBackups->count(),
            'keep_count' => $keepCount
        ]);
    }

    // Obtenir les statistiques des sauvegardes
    public static function getStats()
    {
        return [
            'total' => static::count(),
            'active' => static::where('status', 'active')->count(),
            'restored' => static::where('status', 'restored')->count(),
            'archived' => static::where('status', 'archived')->count(),
            'manual' => static::where('backup_type', 'manual')->count(),
            'automatic' => static::where('backup_type', 'automatic')->count(),
            'latest' => static::latest('backup_date')->first(),
            'oldest' => static::oldest('backup_date')->first()
        ];
    }
}
