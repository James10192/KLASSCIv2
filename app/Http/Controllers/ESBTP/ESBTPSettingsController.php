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
use Barryvdh\DomPDF\Facade\Pdf;

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
        $this->ensureAttendanceNoteSettings();
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
            $this->ensureAttendanceNoteSettings();

            $pdfColorDefaults = [
                'pdf_primary_color' => '#0453cb',
                'pdf_secondary_color' => '#64748b',
                'pdf_accent_color' => '#f59e0b',
                'pdf_text_color' => '#1f2937',
                'pdf_header_bg_color' => '#0453cb',
                'pdf_header_text_color' => '#ffffff'
            ];

            foreach ($pdfColorDefaults as $key => $defaultValue) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $defaultValue,
                        'type' => 'string',
                        'group' => 'pdf',
                        'category' => 'pdf',
                        'description' => 'Couleur PDF',
                        'is_required' => false,
                        'default_value' => $defaultValue,
                        'validation_rules' => ['nullable', 'string', 'max:20'],
                        'sort_order' => 50
                    ]
                );
            }

            // Phase 9 — Settings avancées PDF (mise en page, footer, watermark)
            $pdfAdvancedDefaults = [
                'pdf_logo_size' => ['value' => '60', 'type' => 'integer', 'desc' => 'Hauteur max du logo (px)'],
                'pdf_footer_custom_text' => ['value' => '', 'type' => 'string', 'desc' => 'Texte personnalisé du pied de page'],
                'pdf_show_pagination' => ['value' => '1', 'type' => 'boolean', 'desc' => 'Afficher la pagination dans le footer'],
                'pdf_show_director_signature' => ['value' => '1', 'type' => 'boolean', 'desc' => 'Afficher la mention "Directeur" dans le footer'],
                'pdf_show_generator_name' => ['value' => '1', 'type' => 'boolean', 'desc' => 'Afficher le nom de l\'utilisateur qui a généré le PDF (Généré par X)'],
                'pdf_signature_height' => ['value' => '80', 'type' => 'integer', 'desc' => 'Hauteur des images de signature (px, défaut 80)'],
                'pdf_watermark_opacity' => ['value' => '0.05', 'type' => 'float', 'desc' => 'Opacité du filigrane (0.02 à 0.30)'],
                'pdf_watermark_rotation' => ['value' => '-30', 'type' => 'integer', 'desc' => 'Rotation du filigrane (-90 à 90 degrés)'],
                'pdf_watermark' => ['value' => '', 'type' => 'string', 'desc' => 'Texte du filigrane (vide = désactivé)'],
                'pdf_font_size' => ['value' => '12', 'type' => 'integer', 'desc' => 'Taille de police du corps (px)'],
                'pdf_margin_top' => ['value' => '20', 'type' => 'integer', 'desc' => 'Marge haut (mm)'],
                'pdf_margin_bottom' => ['value' => '20', 'type' => 'integer', 'desc' => 'Marge bas (mm)'],
                'pdf_margin_left' => ['value' => '15', 'type' => 'integer', 'desc' => 'Marge gauche (mm)'],
                'pdf_margin_right' => ['value' => '15', 'type' => 'integer', 'desc' => 'Marge droite (mm)'],
            ];

            foreach ($pdfAdvancedDefaults as $key => $attrs) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $attrs['value'],
                        'type' => $attrs['type'],
                        'group' => 'pdf',
                        'category' => 'pdf',
                        'description' => $attrs['desc'],
                        'is_required' => false,
                        'default_value' => $attrs['value'],
                        'validation_rules' => null,
                        'sort_order' => 60
                    ]
                );
            }

            $bulletinSemesterDefaults = [
                'bulletin_semester1_weight' => '1',
                'bulletin_semester2_weight' => '1',
            ];

            foreach ($bulletinSemesterDefaults as $key => $defaultValue) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $defaultValue,
                        'type' => 'integer',
                        'group' => 'bulletin',
                        'category' => 'bulletin',
                        'description' => 'Ponderation des semestres',
                        'is_required' => false,
                        'default_value' => $defaultValue,
                        'validation_rules' => ['nullable', 'numeric', 'min:0'],
                        'sort_order' => 120
                    ]
                );
            }

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

            // D'abord, traiter toutes les checkboxes (défaut à '0' si décochées)
            // Ensure certificat column settings exist (create with default=1 if first save)
            $certificatColumnDefaults = [
                'certificat_show_classe'  => '1',
                'certificat_show_niveau'  => '1',
                'certificat_show_filiere' => '1',
            ];
            foreach ($certificatColumnDefaults as $key => $defaultValue) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value'            => $defaultValue,
                        'type'             => 'boolean',
                        'group'            => 'documents',
                        'category'         => 'documents',
                        'description'      => 'Colonne certificat de scolarité',
                        'is_required'      => false,
                        'default_value'    => $defaultValue,
                        'validation_rules' => ['nullable', 'in:0,1'],
                        'sort_order'       => 200,
                    ]
                );
            }

            // Créer les settings de note de conduite si inexistants
            $conduiteDefaults = [
                'bulletin_conduite_enabled' => ['value' => '0', 'type' => 'boolean', 'description' => 'Activer la note de conduite sur le bulletin'],
                'conduite_note_defaut' => ['value' => '16', 'type' => 'float', 'description' => 'Note de conduite par défaut (/20)'],
                'conduite_heures_par_point' => ['value' => '4', 'type' => 'float', 'description' => 'Heures d\'absence pour retrancher 1 point'],
                'bulletin_show_absences_par_matiere' => ['value' => '1', 'type' => 'boolean', 'description' => 'Afficher les absences par matière sur le bulletin'],
            ];

            foreach ($conduiteDefaults as $key => $attrs) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $attrs['value'],
                        'type' => $attrs['type'],
                        'group' => 'bulletin',
                        'category' => 'bulletin',
                        'description' => $attrs['description'],
                        'is_required' => false,
                        'default_value' => $attrs['value'],
                        'validation_rules' => null,
                        'sort_order' => 150,
                    ]
                );
            }

            // Assiduité / saisie manuelle d'heures — toggle tenant
            Setting::firstOrCreate(
                ['key' => 'attendance_manual_hours_global_enabled'],
                [
                    'value' => '0',
                    'type' => 'boolean',
                    'group' => 'attendance',
                    'category' => 'bulletin',
                    'description' => "Active le mode global (sans matière) pour la saisie manuelle d'heures sur /esbtp/attendances/create",
                    'is_required' => false,
                    'default_value' => '0',
                    'validation_rules' => null,
                    'sort_order' => 155,
                ]
            );

            // Créer les settings tronc commun si inexistants
            $troncCommunDefaults = [
                'tronc_commun_enabled' => ['value' => '0', 'description' => 'Activer le tronc commun'],
                'tronc_commun_mga_include_s1' => ['value' => '1', 'description' => 'Reporter les notes S1 dans la MGA'],
                'tronc_commun_report_paiements' => ['value' => '1', 'description' => 'Reporter automatiquement les paiements du tronc commun'],
                'tronc_commun_report_notes' => ['value' => '1', 'description' => 'Conserver les notes du S1 accessibles depuis la spécialisation'],
                'tronc_commun_bulletin_show_origin' => ['value' => '1', 'description' => 'Mentionner la classe de tronc commun sur le bulletin'],
                'tronc_commun_matieres_communes' => ['value' => '1', 'description' => 'Détecter les matières partagées entre TC et spécialisation'],
                'tronc_commun_planning_semestre_strict' => ['value' => '0', 'description' => 'Restreindre le planning par semestre'],
            ];

            foreach ($troncCommunDefaults as $key => $attrs) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    [
                        'value' => $attrs['value'],
                        'type' => 'boolean',
                        'group' => 'tronc_commun',
                        'category' => 'tronc_commun',
                        'description' => $attrs['description'],
                        'is_required' => false,
                        'default_value' => $attrs['value'],
                        'validation_rules' => null,
                        'sort_order' => 160,
                    ]
                );
            }

            $allCheckboxSettings = Setting::whereIn('key', array_merge([
                'bulletin_show_logo', 'bulletin_show_header', 'bulletin_show_republic_info',
                'bulletin_show_ministry_info', 'bulletin_show_school_info', 'bulletin_show_cycle_info',
                'bulletin_show_edition_date', 'bulletin_show_student_info', 'bulletin_show_matricule',
                'bulletin_show_birth_date', 'bulletin_show_redoublant', 'bulletin_show_subjects_table',
                'bulletin_show_teachers', 'bulletin_show_absences', 'bulletin_show_statistics',
                'bulletin_show_signature', 'bulletin_show_attendance_note', 'bulletin_show_council_decision',
                'bulletin_show_highest_average', 'bulletin_show_lowest_average', 'bulletin_show_class_average',
                'bulletin_auto_calculate_mention', 'bulletin_show_felicitation', 'bulletin_show_encouragement',
                'certificat_show_classe', 'certificat_show_niveau', 'certificat_show_filiere',
                'bulletin_conduite_enabled', 'bulletin_show_absences_par_matiere',
                'attendance_manual_hours_global_enabled',
            ], array_keys($troncCommunDefaults)))->get();

            foreach ($allCheckboxSettings as $setting) {
                $formKey = $setting->key;  // Les champs n'ont pas le préfixe "setting_"
                $value = $request->has($formKey) ? '1' : '0';
                
                $setting->update([
                    'value' => $value,
                    'updated_by' => auth()->id()
                ]);
                
                $updatedSettings[] = $setting->key;
            }

            // Traiter les paramètres de rappels (ESBTPSystemSetting)
            $reminderSettings = [
                'reminder_inscription_enabled', 'reminder_inscription_first_delay',
                'reminder_inscription_frequency', 'reminder_inscription_max_count',
                'reminder_paiement_enabled', 'reminder_paiement_first_delay',
                'reminder_paiement_frequency', 'reminder_paiement_max_count'
            ];

            foreach ($reminderSettings as $key) {
                if ($request->has($key)) {
                    $value = $request->input($key);

                    // Pour les checkboxes
                    if (in_array($key, ['reminder_inscription_enabled', 'reminder_paiement_enabled'])) {
                        $value = $request->has($key) ? '1' : '0';
                        $type = 'boolean';
                    } else {
                        $type = 'integer';
                    }

                    \App\Models\ESBTPSystemSetting::setValue($key, $value, $type);
                    $updatedSettings[] = $key;
                } elseif (in_array($key, ['reminder_inscription_enabled', 'reminder_paiement_enabled'])) {
                    // Checkbox non cochée
                    \App\Models\ESBTPSystemSetting::setValue($key, '0', 'boolean');
                    $updatedSettings[] = $key;
                }
            }

            // Ensuite traiter les autres champs (texte, nombre, etc.)
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = str_replace('setting_', '', $key);
                    
                    // Skip les checkboxes déjà traitées
                    if (in_array($settingKey, $allCheckboxSettings->pluck('key')->toArray())) {
                        continue;
                    }
                    
                    $setting = Setting::where('key', $settingKey)->first();

                    if ($setting) {
                        // Lot 17b — Champs établissement nullable :
                        // si la valeur est vide ET le champ n'est pas marqué `is_required`,
                        // on skip la validation (sinon les règles legacy ['required', ...]
                        // dans la DB rejettent les champs facultatifs laissés vides).
                        $isEmpty = $value === null || $value === '';

                        // Idempotence : si la valeur soumise est identique à celle en DB,
                        // on ne valide pas (évite de pénaliser sur des seeds pourris où
                        // un setting est marqué is_required=1 mais a une value vide
                        // — ex: current_academic_year qui ne sert plus, l'année courante
                        // venant de esbtp_annee_universitaires.is_current). Pas de
                        // modification = pas de raison de re-valider.
                        $currentValue = (string) ($setting->value ?? '');
                        $newValue = $value === null ? '' : (string) $value;
                        if ($currentValue === $newValue) {
                            continue;
                        }

                        if ($isEmpty && ! $setting->is_required) {
                            // Permet d'écraser une valeur existante par '' (vidage volontaire).
                            $setting->update([
                                'value' => '',
                                'updated_by' => auth()->id()
                            ]);
                            $updatedSettings[] = $settingKey;
                            continue;
                        }

                        // Valider la valeur selon les règles définies
                        if ($setting->validation_rules) {
                            // Lot 17b — Forcer `nullable` en tête de liste sauf si le champ
                            // est explicitement `is_required` (sinon Laravel évalue
                            // `email|string|...` avant `nullable` et rejette '').
                            $rules = $setting->validation_rules;
                            if (! $setting->is_required && ! in_array('nullable', $rules, true)) {
                                $rules = array_values(array_diff($rules, ['required']));
                                array_unshift($rules, 'nullable');
                            }

                            $validator = Validator::make(
                                [$settingKey => $value],
                                [$settingKey => $rules]
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

                    Log::info('Traitement fichier uploadé', [
                        'key' => $key,
                        'settingKey' => $settingKey,
                        'setting_found' => $setting !== null,
                        'setting_type' => $setting ? $setting->type : null,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize()
                    ]);

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

                        // Stocker le nouveau fichier dans le bon dossier selon le type
                        $folder = $this->getStorageFolderForSetting($settingKey);
                        $path = $file->store($folder, 'public');

                        $setting->update([
                            'value' => $path,
                            'updated_by' => auth()->id()
                        ]);

                        Log::info('Fichier uploadé avec succès', [
                            'settingKey' => $settingKey,
                            'path' => $path,
                            'folder' => $folder
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
     * Déterminer le dossier de stockage selon la clé du paramètre
     */
    private function getStorageFolderForSetting($settingKey)
    {
        // Mapper les clés de paramètres aux dossiers appropriés
        $settingFolders = [
            'school_logo' => 'logos',
            'school_favicon' => 'logos',
            'bulletin_logo' => 'logos',
            'header_logo' => 'logos',
            'signature_image' => 'documents',
            'watermark_image' => 'documents',
        ];

        return $settingFolders[$settingKey] ?? 'settings';
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

    /**
     * Tester l'envoi des rappels automatiques (mode simulation)
     */
    public function testReminders()
    {
        try {
            // Exécuter la commande en mode test
            \Illuminate\Support\Facades\Artisan::call('reminders:send-inscription-paiement', ['--test' => true]);

            // Récupérer la sortie de la commande
            $output = \Illuminate\Support\Facades\Artisan::output();

            // Parser la sortie pour extraire les statistiques
            $inscriptionsFound = \App\Models\ESBTPInscription::where('status', 'en_attente')->count();
            $paiementsFound = \App\Models\ESBTPPaiement::where('status', 'en_attente')->count();

            // Simuler le comptage de rappels qui auraient été envoyés
            $inscriptionsSent = 0;
            $paiementsSent = 0;

            $firstDelayInscription = (int) \App\Models\ESBTPSystemSetting::getValue('reminder_inscription_first_delay', 3);
            $firstDelayPaiement = (int) \App\Models\ESBTPSystemSetting::getValue('reminder_paiement_first_delay', 2);

            foreach (\App\Models\ESBTPInscription::where('status', 'en_attente')->get() as $inscription) {
                $daysPending = now()->diffInDays($inscription->created_at);
                if ($daysPending >= $firstDelayInscription) {
                    $inscriptionsSent++;
                }
            }

            foreach (\App\Models\ESBTPPaiement::where('status', 'en_attente')->get() as $paiement) {
                $daysPending = now()->diffInDays($paiement->created_at);
                if ($daysPending >= $firstDelayPaiement) {
                    $paiementsSent++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Test exécuté avec succès',
                'data' => [
                    'inscriptions_found' => $inscriptionsFound,
                    'inscriptions_sent' => $inscriptionsSent,
                    'paiements_found' => $paiementsFound,
                    'paiements_sent' => $paiementsSent,
                    'output' => $output
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur test rappels: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère un PDF d'aperçu en utilisant les overrides fournis dans la requête
     * (sans persister). Permet à l'admin tenant de prévisualiser ses paramètres
     * PDF dans une nouvelle tab avant de sauvegarder.
     *
     * Phase 9 — Customisation PDF tenant.
     */
    public function pdfPreview(Request $request)
    {
        $overrides = $this->extractPdfOverrides($request);

        $pdf = Pdf::loadView('pdf.preview-sample', [
            'overrides' => $overrides,
        ])->setPaper('A4', 'portrait');

        return new \Illuminate\Http\Response(
            $pdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="apercu-pdf-' . now()->format('Ymd-His') . '.pdf"',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]
        );
    }

    /**
     * Convertit les inputs `pdf_*` du formulaire en array d'overrides matchant
     * les clés de SettingsHelper::getPdfSettings() (sans préfixe `pdf_`).
     * Booléens normalisés, marges/sizes castés en int, opacity en float.
     */
    private function extractPdfOverrides(Request $request): array
    {
        $defaults = \App\Helpers\SettingsHelper::getPdfDefaults();
        $overrides = [];

        foreach ($defaults as $key => $defaultValue) {
            // Le form existant prefixe les inputs avec `setting_` (form principal /esbtp/settings).
            // Le form du bouton "Aperçu" peut envoyer la clé directement.
            $value = $request->input($key, $request->input('setting_' . $key));
            if ($value === null) {
                continue;
            }
            $shortKey = str_starts_with($key, 'pdf_') ? substr($key, 4) : $key;

            // Normalisation par type. show_generator_name ajouté aux booléens
            // (oubli initial : le toggle "Généré par X" ne s'appliquait pas en preview).
            $booleanKeys = ['show_logo', 'show_director_signature', 'show_pagination', 'show_generator_name'];
            $intKeys = ['logo_size', 'signature_height', 'font_size', 'margin_top', 'margin_bottom', 'margin_left', 'margin_right', 'watermark_rotation'];

            $overrides[$shortKey] = match (true) {
                in_array($shortKey, $booleanKeys, true)
                    => in_array($value, ['1', 1, true, 'true', 'on'], true),
                in_array($shortKey, $intKeys, true)
                    => (int) $value,
                $shortKey === 'watermark_opacity'
                    => (float) $value,
                default => (string) $value,
            };
        }

        return $overrides;
    }

    private function ensureAttendanceNoteSettings(): void
    {
        // Barème 5 paliers (étendu 03/06/2026). Le palier legacy "two_or_more" reste
        // créé pour rétrocompat des appels code qui le lisent encore.
        $attendanceDefaults = [
            'attendance_note_zero_unjustified' => ['value' => '0.13', 'description' => 'Barème assiduité pour 0 absence non justifiée', 'sort_order' => 121],
            'attendance_note_one_unjustified' => ['value' => '0.00', 'description' => 'Barème assiduité pour 1 absence non justifiée', 'sort_order' => 122],
            'attendance_note_two_unjustified' => ['value' => '-0.13', 'description' => 'Barème assiduité pour 2 absences non justifiées', 'sort_order' => 123],
            'attendance_note_three_to_four_unjustified' => ['value' => '-0.39', 'description' => 'Barème assiduité pour 3 à 4 absences non justifiées', 'sort_order' => 124],
            'attendance_note_five_or_more_unjustified' => ['value' => '-0.50', 'description' => 'Barème assiduité pour 5 absences non justifiées ou plus', 'sort_order' => 125],
            'attendance_note_two_or_more_unjustified' => ['value' => '-0.13', 'description' => 'Barème assiduité legacy (2 absences ou plus, conservé pour rétrocompat)', 'sort_order' => 126],
        ];

        foreach ($attendanceDefaults as $key => $attrs) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $attrs['value'],
                    'type' => 'float',
                    'group' => 'bulletin',
                    'category' => 'bulletin',
                    'description' => $attrs['description'],
                    'is_required' => false,
                    'default_value' => $attrs['value'],
                    'validation_rules' => ['nullable', 'numeric', 'min:-20', 'max:20'],
                    'sort_order' => $attrs['sort_order'],
                ]
            );
        }
    }
}
