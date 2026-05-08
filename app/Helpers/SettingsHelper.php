<?php

namespace App\Helpers;

use App\Models\Setting;

class SettingsHelper
{
    /**
     * Récupère un paramètre avec une valeur par défaut
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Setting::get($key, $default);
    }

    /**
     * Définit un paramètre
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return bool
     */
    public static function set($key, $value, $group = 'general')
    {
        return Setting::set($key, $value, $group);
    }

    /**
     * Définit un paramètre ou le crée s'il n'existe pas
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param string $type
     * @return bool
     */
    public static function setOrCreate($key, $value, $group = 'general', $type = 'string')
    {
        return Setting::setOrCreate($key, $value, $group, $type);
    }

    /**
     * Récupère tous les paramètres
     *
     * @return array
     */
    public static function all()
    {
        try {
            $settings = Setting::all();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère tous les paramètres d'un groupe
     *
     * @param string $group
     * @return array
     */
    public static function getGroup($group)
    {
        try {
            $settings = Setting::where('group', $group)->get();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->value;
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les informations de l'établissement
     *
     * @return array
     */
    public static function getSchoolInfo()
    {
        return [
            'name' => self::get('school_name', config('app.name', 'KLASSCI')),
            'acronym' => self::get('school_acronym', config('app.name', 'KLASSCI')),
            'address' => self::get('school_address', ''),
            'city' => self::get('school_city', ''),
            'postal_code' => self::get('school_postal_code', ''),
            'country' => self::get('school_country', 'Côte d\'Ivoire'),
            'phone' => self::get('school_phone', ''),
            'mobile' => self::get('school_mobile', ''),
            'email' => self::get('school_email', ''),
            'website' => self::get('school_website', ''),
            'logo' => self::get('school_logo', ''),
            'director_name' => self::get('director_name', ''),
            'director_title' => self::get('director_title', 'Directeur Général'),
        ];
    }

    /**
     * Résout le logo de l'école en base64 pour inlining (DomPDF + previews web).
     *
     * Tente plusieurs candidats (storage public, public/storage, fallbacks pré-bundlés)
     * pour absorber les divergences de chemin entre tenants legacy et nouveau format.
     * Mémoïsé par requête : le base64 d'un logo (~50KB) appelé sur 8+ pages d'un même
     * export évite les file_get_contents répétés.
     *
     * @return array{mime: string, ext: string, b64: string, data_uri: string}|null
     */
    public static function resolveLogoBase64(): ?array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache === false ? null : $cache;
        }

        $logoPath = self::get('school_logo', '');
        $normalized = str_replace('\\', '/', ltrim((string) $logoPath, '/'));
        $relative = preg_replace('#^storage/#', '', $normalized);
        $basename = basename($relative);

        $candidates = [];
        if ($logoPath) {
            $candidates = [
                storage_path('app/public/' . $normalized),
                storage_path('app/public/' . $relative),
                storage_path('app/public/logos/' . $basename),
                public_path('storage/' . $normalized),
                public_path('storage/' . $relative),
                public_path('storage/logos/' . $basename),
                public_path($normalized),
            ];
        }
        $candidates[] = public_path('images/esbtp_logo.png');
        $candidates[] = public_path('images/LOGO-KLASSCI-PNG.png');

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }
            $contents = @file_get_contents($candidate);
            if ($contents === false) {
                continue;
            }
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION) ?: 'png');
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                default => 'image/png',
            };
            $b64 = base64_encode($contents);
            return $cache = [
                'mime' => $mime,
                'ext' => $ext,
                'b64' => $b64,
                'data_uri' => 'data:' . $mime . ';base64,' . $b64,
            ];
        }

        $cache = false;
        return null;
    }

    /**
     * Récupère les paramètres PDF
     *
     * @return array
     */
    public static function getPdfSettings()
    {
        return [
            'header_text' => self::get('pdf_header_text', ''),
            'footer_text' => self::get('pdf_footer_text', ''),
            'footer_custom_text' => self::get('pdf_footer_custom_text', ''),
            'show_logo' => self::get('pdf_show_logo', '1') === '1',
            'logo_position' => self::get('pdf_logo_position', 'left'),
            'logo_size' => (int) self::get('pdf_logo_size', '60'),
            'signature_director' => self::get('pdf_signature_director', ''),
            'signature_secretary' => self::get('pdf_signature_secretary', ''),
            'signature_height' => (int) self::get('pdf_signature_height', '80'),
            'show_director_signature' => self::get('pdf_show_director_signature', '1') === '1',
            'show_generator_name' => self::get('pdf_show_generator_name', '1') === '1',
            'show_pagination' => self::get('pdf_show_pagination', '1') === '1',
            'watermark' => self::get('pdf_watermark', ''),
            'watermark_opacity' => (float) self::get('pdf_watermark_opacity', '0.05'),
            'watermark_rotation' => (int) self::get('pdf_watermark_rotation', '-30'),
            'font_size' => (int) self::get('pdf_font_size', '12'),
            'margin_top' => (int) self::get('pdf_margin_top', '20'),
            'margin_bottom' => (int) self::get('pdf_margin_bottom', '20'),
            'margin_left' => (int) self::get('pdf_margin_left', '15'),
            'margin_right' => (int) self::get('pdf_margin_right', '15'),
            'primary_color' => self::get('pdf_primary_color', '#0453cb'),
            'secondary_color' => self::get('pdf_secondary_color', '#64748b'),
            'accent_color' => self::get('pdf_accent_color', '#f59e0b'),
            'text_color' => self::get('pdf_text_color', '#1f2937'),
            'header_bg_color' => self::get('pdf_header_bg_color', '#0453cb'),
            'header_text_color' => self::get('pdf_header_text_color', '#ffffff'),
        ];
    }

    /**
     * Defaults stricts du PDF (sans aller chercher en base).
     * Utilisé pour le bouton "Réinitialiser défauts" et le formulaire d'aperçu.
     */
    public static function getPdfDefaults(): array
    {
        return [
            'pdf_header_text' => '',
            'pdf_footer_text' => '',
            'pdf_footer_custom_text' => '',
            'pdf_show_logo' => '1',
            'pdf_logo_position' => 'left',
            'pdf_logo_size' => '60',
            'pdf_show_director_signature' => '1',
            'pdf_show_generator_name' => '1',
            'pdf_signature_height' => '80',
            'pdf_show_pagination' => '1',
            'pdf_watermark' => '',
            'pdf_watermark_opacity' => '0.05',
            'pdf_watermark_rotation' => '-30',
            'pdf_font_size' => '12',
            'pdf_margin_top' => '20',
            'pdf_margin_bottom' => '20',
            'pdf_margin_left' => '15',
            'pdf_margin_right' => '15',
            'pdf_primary_color' => '#0453cb',
            'pdf_secondary_color' => '#64748b',
            'pdf_accent_color' => '#f59e0b',
            'pdf_text_color' => '#1f2937',
            'pdf_header_bg_color' => '#0453cb',
            'pdf_header_text_color' => '#ffffff',
        ];
    }

    /**
     * Récupère les couleurs du thème
     *
     * @return array
     */
    public static function getThemeColors()
    {
        return [
            'primary' => self::get('theme_primary_color', '#007bff'),
            'secondary' => self::get('theme_secondary_color', '#6c757d'),
            'success' => self::get('theme_success_color', '#28a745'),
            'danger' => self::get('theme_danger_color', '#dc3545'),
            'warning' => self::get('theme_warning_color', '#ffc107'),
            'info' => self::get('theme_info_color', '#17a2b8'),
            'sidebar' => self::get('sidebar_color', '#343a40'),
            'navbar' => self::get('navbar_color', '#ffffff'),
            'background' => self::get('background_color', '#f8f9fa'),
            'text' => self::get('text_color', '#212529'),
            'link' => self::get('link_color', '#007bff'),
        ];
    }

    /**
     * Récupère les paramètres académiques
     *
     * @return array
     */
    public static function getAcademicSettings()
    {
        return [
            'current_year' => self::get('current_academic_year', ''),
            'semester_system' => self::get('semester_system', 'trimester'),
            'grade_scale' => (int) self::get('grade_scale', '20'),
            'passing_grade' => (float) self::get('passing_grade', '10'),
            'attendance_required' => (int) self::get('attendance_required', '75'),
            'late_tolerance' => (int) self::get('late_arrival_tolerance', '15'),
        ];
    }

    /**
     * Récupère les paramètres de notifications
     *
     * @return array
     */
    public static function getNotificationSettings()
    {
        return [
            'email' => self::get('email_notifications', '1') === '1',
            'sms' => self::get('sms_notifications', '0') === '1',
            'parents' => self::get('parent_notifications', '1') === '1',
            'teachers' => self::get('teacher_notifications', '1') === '1',
            'admins' => self::get('admin_notifications', '1') === '1',
        ];
    }

    /**
     * Récupère les paramètres Analytics (poids, seuils, notifications).
     *
     * @return array
     */
    public static function getAnalyticsSettings()
    {
        return [
            'default_risk' => [
                'weight_solde'      => (float) self::get('analytics.default_risk.weight.solde', 3.0),
                'weight_retard'     => (float) self::get('analytics.default_risk.weight.retard', 2.5),
                'weight_engagement' => (float) self::get('analytics.default_risk.weight.engagement', 1.0),
                'weight_montant'    => (float) self::get('analytics.default_risk.weight.montant', 0.5),
                'bias'              => (float) self::get('analytics.default_risk.bias', -2.5),
                'threshold_high'    => (float) self::get('analytics.default_risk.threshold_high', 0.66),
                'threshold_medium'  => (float) self::get('analytics.default_risk.threshold_medium', 0.33),
                'top_n'             => (int)   self::get('analytics.default_risk.top_n', 50),
            ],
            'anomaly' => [
                'z_warning'                       => (float) self::get('analytics.anomaly.z_warning', 2.0),
                'z_critical'                      => (float) self::get('analytics.anomaly.z_critical', 3.0),
                'payment_outlier_multiplier'      => (float) self::get('analytics.anomaly.payment_outlier_multiplier', 3.0),
                'recouvrement_gap_warning_pct'    => (float) self::get('analytics.anomaly.recouvrement_gap_warning_pct', 30.0),
                'recouvrement_gap_critical_pct'   => (float) self::get('analytics.anomaly.recouvrement_gap_critical_pct', 50.0),
                'recouvrement_gap_min_expected'   => (float) self::get('analytics.anomaly.recouvrement_gap_min_expected', 100000.0),
                'notifications_enabled'           => (string) self::get('analytics.anomaly.notifications_enabled', '1') === '1',
            ],
            'recouvrement' => [
                'whatsapp_template' => (string) self::get(
                    'analytics.recouvrement.whatsapp_template',
                    "Bonjour {prenom}, votre solde de scolarité de {solde} FCFA est en retard de {retard} jours. Merci de régulariser dès que possible. — {ecole}",
                ),
            ],
        ];
    }

    /**
     * Initialise les paramètres par défaut
     *
     * @return void
     */
    public static function initializeDefaults()
    {
        $defaults = [
            // Établissement
            'school_name' => ['value' => config('app.name', 'KLASSCI'), 'group' => 'establishment'],
            'school_acronym' => ['value' => config('app.name', 'KLASSCI'), 'group' => 'establishment'],
            'school_country' => ['value' => 'Côte d\'Ivoire', 'group' => 'establishment'],
            'director_title' => ['value' => 'Directeur Général', 'group' => 'establishment'],

            // PDF
            'pdf_show_logo' => ['value' => '1', 'group' => 'pdf'],
            'pdf_logo_position' => ['value' => 'left', 'group' => 'pdf'],
            'pdf_font_size' => ['value' => '12', 'group' => 'pdf'],
            'pdf_margin_top' => ['value' => '20', 'group' => 'pdf'],
            'pdf_margin_bottom' => ['value' => '20', 'group' => 'pdf'],
            'pdf_margin_left' => ['value' => '15', 'group' => 'pdf'],
            'pdf_margin_right' => ['value' => '15', 'group' => 'pdf'],

            // Bulletin
            'bulletin_style' => ['value' => 'yakro', 'group' => 'bulletin'],
            'bulletin_semester1_weight' => ['value' => '1', 'group' => 'bulletin'],
            'bulletin_semester2_weight' => ['value' => '1', 'group' => 'bulletin'],

            // Interface
            'theme_primary_color' => ['value' => '#007bff', 'group' => 'interface'],
            'theme_secondary_color' => ['value' => '#6c757d', 'group' => 'interface'],
            'theme_success_color' => ['value' => '#28a745', 'group' => 'interface'],
            'theme_danger_color' => ['value' => '#dc3545', 'group' => 'interface'],
            'theme_warning_color' => ['value' => '#ffc107', 'group' => 'interface'],
            'theme_info_color' => ['value' => '#17a2b8', 'group' => 'interface'],
            'sidebar_color' => ['value' => '#343a40', 'group' => 'interface'],
            'navbar_color' => ['value' => '#ffffff', 'group' => 'interface'],
            'background_color' => ['value' => '#f8f9fa', 'group' => 'interface'],
            'text_color' => ['value' => '#212529', 'group' => 'interface'],
            'link_color' => ['value' => '#007bff', 'group' => 'interface'],

            // Académique
            'semester_system' => ['value' => 'trimester', 'group' => 'academic'],
            'grade_scale' => ['value' => '20', 'group' => 'academic'],
            'passing_grade' => ['value' => '10', 'group' => 'academic'],
            'attendance_required' => ['value' => '75', 'group' => 'academic'],
            'late_arrival_tolerance' => ['value' => '15', 'group' => 'academic'],

            // Notifications
            'email_notifications' => ['value' => '1', 'group' => 'notifications'],
            'sms_notifications' => ['value' => '0', 'group' => 'notifications'],
            'parent_notifications' => ['value' => '1', 'group' => 'notifications'],
            'teacher_notifications' => ['value' => '1', 'group' => 'notifications'],
            'admin_notifications' => ['value' => '1', 'group' => 'notifications'],
        ];

        foreach ($defaults as $key => $config) {
            // Ne créer que si le paramètre n'existe pas déjà
            if (!Setting::where('key', $key)->exists()) {
                Setting::set($key, $config['value'], $config['group']);
            }
        }
    }
}
