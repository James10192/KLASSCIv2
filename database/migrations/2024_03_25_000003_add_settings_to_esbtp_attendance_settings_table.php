<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSettingsToESBTPAttendanceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $settings = [
            [
                'key' => 'max_distance_meters',
                'value' => '100',
                'description' => 'Distance maximale autorisée en mètres pour l\'émargement',
            ],
            [
                'key' => 'allowed_late_minutes',
                'value' => '15',
                'description' => 'Minutes de retard autorisées pour l\'émargement',
            ],
            [
                'key' => 'display_code_duration',
                'value' => '60',
                'description' => 'Durée d\'affichage du code en minutes avant actualisation',
            ],
            [
                'key' => 'school_latitude',
                'value' => '0',
                'description' => 'Latitude de l\'établissement pour la vérification de la géolocalisation',
            ],
            [
                'key' => 'school_longitude',
                'value' => '0',
                'description' => 'Longitude de l\'établissement pour la vérification de la géolocalisation',
            ],
        ];

        foreach ($settings as $setting) {
            // Check if setting already exists
            $exists = DB::table('esbtp_attendance_settings')
                ->where('key', $setting['key'])
                ->exists();

            if (!$exists) {
                DB::table('esbtp_attendance_settings')->insert([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('esbtp_attendance_settings')
            ->whereIn('key', [
                'max_distance_meters',
                'allowed_late_minutes',
                'display_code_duration',
                'school_latitude',
                'school_longitude',
            ])
            ->delete();
    }
}
