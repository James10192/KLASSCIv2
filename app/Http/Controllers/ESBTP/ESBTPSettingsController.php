<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SettingsBackup;
use App\Http\Middleware\CheckRequiredSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ESBTPSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:superAdmin|secretaire');
    }

    /**
     * Afficher la page des paramètres
     */
    public function index()
    {
        $allSettings = Setting::orderBy('category')->orderBy('sort_order')->get();
        $settings = $allSettings->groupBy('category');
        $flatSettings = $allSettings; // Collection plate pour l'accès direct par clé
        $missingSettings = CheckRequiredSettings::getAllMissingSettings();
        $backupStats = SettingsBackup::getStats();

        return view('esbtp.settings.index', compact('settings', 'flatSettings', 'missingSettings', 'backupStats'));
    }

    /**
     * Mettre à jour les paramètres
     */
    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            // Créer une sauvegarde automatique avant la mise à jour
            $backup = SettingsBackup::create([
                'backup_name' => 'Auto Backup - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'Sauvegarde automatique avant mise à jour des paramètres',
                'settings_data' => Setting::all()->toArray(),
                'backup_type' => 'automatic',
                'backup_date' => now(),
                'created_by' => auth()->id()
            ]);

            $updatedSettings = [];
            $errors = [];

            // Traiter les champs de texte
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = str_replace('setting_', '', $key);
                    $setting = Setting::where('key', $settingKey)->first();

                    if ($setting) {
                        // Valider la valeur selon les règles définies
                        if ($setting->validation_rules) {
                            $validator = Validator::make(
                                [$settingKey => $value],
                                [$settingKey => $setting->validation_rules]
                            );

                            if ($validator->fails()) {
                                $errors[$settingKey] = $validator->errors()->first($settingKey);
                                continue;
                            }
                        }

                        // Traitement spécial selon le type
                        $processedValue = $this->processSettingValue($value, $setting->type, $request);

                        $setting->update([
                            'value' => $processedValue,
                            'updated_by' => auth()->id()
                        ]);

                        $updatedSettings[] = $settingKey;
                    }
                }
            }

            // Traiter les fichiers uploadés
            foreach ($request->allFiles() as $key => $file) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = str_replace('setting_', '', $key);
                    $setting = Setting::where('key', $settingKey)->first();

                    if ($setting && $setting->type === 'file') {
                        // Valider le fichier
                        $validator = Validator::make(
                            [$settingKey => $file],
                            [$settingKey => 'image|mimes:jpeg,png,jpg,gif|max:2048']
                        );

                        if ($validator->fails()) {
                            $errors[$settingKey] = $validator->errors()->first($settingKey);
                            continue;
                        }

                        // Supprimer l'ancien fichier s'il existe
                        if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                            Storage::disk('public')->delete($setting->value);
                        }

                        // Stocker le nouveau fichier
                        $path = $file->store('settings', 'public');

                        $setting->update([
                            'value' => $path,
                            'updated_by' => auth()->id()
                        ]);

                        $updatedSettings[] = $settingKey;
                    }
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return redirect()->back()
                    ->withErrors($errors)
                    ->withInput()
                    ->with('error', 'Certaines configurations contiennent des erreurs.');
            }

            // Vider les caches
            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            DB::commit();

            Log::info('Paramètres mis à jour', [
                'user_id' => auth()->id(),
                'updated_settings' => $updatedSettings,
                'backup_id' => $backup->id
            ]);

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Paramètres mis à jour avec succès.')
                ->with('updated_count', count($updatedSettings));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de la mise à jour des paramètres', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour des paramètres.')
                ->withInput();
        }
    }

    /**
     * Créer une nouvelle configuration
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:settings,key',
            'value' => 'required',
            'type' => 'required|in:string,integer,boolean,json,file',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'validation_rules' => 'nullable|string'
        ]);

        try {
            $setting = Setting::create([
                'key' => $request->key,
                'value' => $this->processSettingValue($request->value, $request->type),
                'type' => $request->type,
                'category' => $request->category,
                'description' => $request->description,
                'is_required' => $request->boolean('is_required'),
                'validation_rules' => $request->validation_rules ? json_decode($request->validation_rules, true) : null,
                'is_active' => true,
                'created_by' => auth()->id()
            ]);

            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Configuration créée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la configuration', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la configuration.')
                ->withInput();
        }
    }

    /**
     * Supprimer une configuration
     */
    public function destroy($id)
    {
        try {
            $setting = Setting::findOrFail($id);

            // Vérifier si la configuration est requise
            if ($setting->is_required) {
                return redirect()->back()
                    ->with('error', 'Impossible de supprimer une configuration requise.');
            }

            $setting->delete();

            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Configuration supprimée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la configuration', [
                'setting_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la configuration.');
        }
    }

    /**
     * Gestion des sauvegardes
     */
    public function backups()
    {
        $backups = SettingsBackup::with(['creator', 'restorer'])
            ->orderBy('backup_date', 'desc')
            ->paginate(20);

        $stats = SettingsBackup::getStats();

        return view('esbtp.settings.backups', compact('backups', 'stats'));
    }

    /**
     * Créer une sauvegarde manuelle
     */
    public function createBackup(Request $request)
    {
        $request->validate([
            'backup_name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        try {
            $backup = SettingsBackup::createManualBackup(
                $request->backup_name,
                $request->description,
                auth()->id()
            );

            return redirect()->route('esbtp.settings.backups')
                ->with('success', 'Sauvegarde créée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la sauvegarde', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la sauvegarde.');
        }
    }

    /**
     * Restaurer une sauvegarde
     */
    public function restoreBackup(Request $request, $id)
    {
        $request->validate([
            'restore_notes' => 'nullable|string'
        ]);

        try {
            $backup = SettingsBackup::findOrFail($id);

            $backup->restore(auth()->id(), $request->restore_notes);

            return redirect()->route('esbtp.settings.index')
                ->with('success', 'Sauvegarde restaurée avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la restauration de la sauvegarde', [
                'backup_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la restauration de la sauvegarde.');
        }
    }

    /**
     * Comparer une sauvegarde avec les configurations actuelles
     */
    public function compareBackup($id)
    {
        try {
            $backup = SettingsBackup::findOrFail($id);
            $differences = $backup->compareWithCurrent();

            return view('esbtp.settings.compare', compact('backup', 'differences'));

        } catch (\Exception $e) {
            Log::error('Erreur lors de la comparaison de la sauvegarde', [
                'backup_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la comparaison de la sauvegarde.');
        }
    }

    /**
     * Archiver une sauvegarde
     */
    public function archiveBackup($id)
    {
        try {
            $backup = SettingsBackup::findOrFail($id);
            $backup->archive();

            return redirect()->route('esbtp.settings.backups')
                ->with('success', 'Sauvegarde archivée avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'archivage de la sauvegarde.');
        }
    }

    /**
     * Nettoyer les anciennes sauvegardes
     */
    public function cleanupBackups()
    {
        try {
            SettingsBackup::cleanupOldBackups();

            return redirect()->route('esbtp.settings.backups')
                ->with('success', 'Nettoyage des sauvegardes effectué avec succès.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors du nettoyage des sauvegardes.');
        }
    }

    /**
     * Réinitialiser une configuration à sa valeur par défaut
     */
    public function resetToDefault($id)
    {
        try {
            $setting = Setting::findOrFail($id);

            if ($setting->default_value !== null) {
                $setting->update([
                    'value' => $setting->default_value,
                    'updated_by' => auth()->id()
                ]);

                Setting::clearCache();
                CheckRequiredSettings::clearCache();

                return redirect()->back()
                    ->with('success', 'Configuration réinitialisée à sa valeur par défaut.');
            } else {
                return redirect()->back()
                    ->with('error', 'Aucune valeur par défaut définie pour cette configuration.');
            }

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la réinitialisation de la configuration.');
        }
    }

    /**
     * Exporter les configurations
     */
    public function export()
    {
        try {
            $settings = Setting::all();
            $exportData = [
                'export_date' => now()->toISOString(),
                'exported_by' => auth()->user()->name,
                'settings' => $settings->toArray()
            ];

            $filename = 'esbtp_settings_' . now()->format('Y-m-d_H-i-s') . '.json';

            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'exportation des configurations.');
        }
    }

    /**
     * Importer les configurations
     */
    public function import(Request $request)
    {
        $request->validate([
            'settings_file' => 'required|file|mimes:json'
        ]);

        try {
            $file = $request->file('settings_file');
            $content = json_decode(file_get_contents($file->path()), true);

            if (!isset($content['settings'])) {
                return redirect()->back()
                    ->with('error', 'Format de fichier invalide.');
            }

            DB::beginTransaction();

            // Créer une sauvegarde avant l'importation
            $backup = SettingsBackup::createManualBackup(
                'Pre-Import Backup - ' . now()->format('Y-m-d H:i:s'),
                'Sauvegarde automatique avant importation',
                auth()->id()
            );

            $imported = 0;
            foreach ($content['settings'] as $settingData) {
                Setting::updateOrCreate(
                    ['key' => $settingData['key']],
                    array_merge($settingData, ['updated_by' => auth()->id()])
                );
                $imported++;
            }

            Setting::clearCache();
            CheckRequiredSettings::clearCache();

            DB::commit();

        return redirect()->route('esbtp.settings.index')
                ->with('success', "Importation réussie. {$imported} configurations importées.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Erreur lors de l\'importation des configurations', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de l\'importation des configurations.');
        }
    }

    /**
     * Traiter la valeur selon le type
     */
    private function processSettingValue($value, $type, $request = null)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'file':
                // Pour les fichiers, la valeur est traitée séparément dans la méthode update
                return $value;
            default:
                return $value;
        }
    }

    /**
     * Vérifier l'état des configurations
     */
    public function checkStatus()
    {
        $missingSettings = CheckRequiredSettings::getAllMissingSettings();
        $totalSettings = Setting::count();
        $activeSettings = Setting::where('is_active', true)->count();
        $requiredSettings = Setting::where('is_required', true)->count();

        return response()->json([
            'status' => empty($missingSettings) ? 'ok' : 'missing_required',
            'total_settings' => $totalSettings,
            'active_settings' => $activeSettings,
            'required_settings' => $requiredSettings,
            'missing_settings' => $missingSettings,
            'missing_count' => count($missingSettings)
        ]);
    }
}
