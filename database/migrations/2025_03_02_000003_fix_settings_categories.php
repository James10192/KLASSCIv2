<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixSettingsCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Mapping des clés vers les catégories appropriées
        $categoryMapping = [
            // PDF settings
            'pdf_margin_left' => 'pdf',
            'pdf_margin_right' => 'pdf',
            'pdf_margin_top' => 'pdf',
            'pdf_margin_bottom' => 'pdf',
            'pdf_header_height' => 'pdf',
            'pdf_footer_height' => 'pdf',
            'pdf_show_logo' => 'pdf',
            'pdf_logo_position' => 'pdf',
            'pdf_font_size' => 'pdf',

            // Interface settings
            'theme_primary_color' => 'interface',
            'theme_secondary_color' => 'interface',
            'theme_success_color' => 'interface',
            'theme_danger_color' => 'interface',
            'theme_warning_color' => 'interface',
            'theme_info_color' => 'interface',

            // Notifications settings
            'email_notifications' => 'notifications',
            'parent_notifications' => 'notifications',
            'sms_notifications' => 'notifications',
            'teacher_notifications' => 'notifications',
            'admin_notifications' => 'notifications',

            // Academic settings
            'current_academic_year' => 'academic',
            'semester_system' => 'academic',
            'grade_scale' => 'academic',
            'passing_grade' => 'academic',
            'attendance_required' => 'academic',

            // Establishment settings
            'school_name' => 'establishment',
            'school_acronym' => 'establishment',
            'school_address' => 'establishment',
            'school_city' => 'establishment',
            'school_country' => 'establishment',
            'school_phone' => 'establishment',
            'school_email' => 'establishment',
            'director_name' => 'establishment',
            'director_title' => 'establishment',

            // Attendance settings
            'attendance_tracking_enabled' => 'attendance',
            'late_arrival_tolerance' => 'attendance',
            'absence_justification_required' => 'attendance',

            // Bulletin settings
            'bulletin_show_absences' => 'bulletin',
            'bulletin_show_attendance_rate' => 'bulletin',
            'bulletin_show_teacher_comments' => 'bulletin',

            // General settings
            'sidebar_color' => 'general',
            'navbar_color' => 'general',
            'app_timezone' => 'general',
            'app_locale' => 'general',
            'maintenance_mode' => 'general',
        ];

        // Corriger les paramètres avec des catégories vides ou NULL
        foreach ($categoryMapping as $key => $category) {
            DB::table('settings')
                ->where('key', $key)
                ->where(function($query) {
                    $query->whereNull('category')
                          ->orWhere('category', '');
                })
                ->update(['category' => $category]);
        }

        // Log des corrections effectuées
        $correctedCount = 0;
        foreach ($categoryMapping as $key => $category) {
            $count = DB::table('settings')
                ->where('key', $key)
                ->where('category', $category)
                ->count();
            if ($count > 0) {
                $correctedCount++;
            }
        }

        if ($correctedCount > 0) {
            \Log::info("Migration FixSettingsCategories: {$correctedCount} paramètres corrigés avec leurs catégories appropriées.");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // On ne fait rien lors du rollback car on ne veut pas remettre les catégories vides
        // Cette migration est considérée comme une correction de données
    }
}
