<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateESBTPAttendanceSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('esbtp_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('esbtp_attendance_settings')->insert([
            [
                'key' => 'code_validity_hours',
                'value' => '24',
                'description' => 'Durée de validité du code en heures',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'max_attempts',
                'value' => '3',
                'description' => 'Nombre maximum de tentatives de saisie du code',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'geolocation_required',
                'value' => 'false',
                'description' => 'Exiger la géolocalisation pour l\'émargement',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'allowed_early_minutes',
                'value' => '30',
                'description' => 'Minutes autorisées avant le début du cours pour émarger',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'allowed_late_minutes',
                'value' => '60',
                'description' => 'Minutes autorisées après le début du cours pour émarger',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_attendance_settings');
    }
}
