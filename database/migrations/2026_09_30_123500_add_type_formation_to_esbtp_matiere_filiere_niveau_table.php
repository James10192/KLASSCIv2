<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table): void {
            if (! Schema::hasColumn('esbtp_matiere_filiere_niveau', 'type_formation')) {
                // Défaut du combo pour les bulletins. Les réglages par classe/période
                // dans esbtp_config_matieres restent des overrides plus précis.
                $table->string('type_formation', 32)->nullable()->after('classification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_matiere_filiere_niveau', function (Blueprint $table): void {
            if (Schema::hasColumn('esbtp_matiere_filiere_niveau', 'type_formation')) {
                $table->dropColumn('type_formation');
            }
        });
    }
};
