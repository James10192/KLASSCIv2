<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Certaines instances possèdent déjà cette table (installation
        // historique/manuelle). Ne jamais la recréer ni perdre ses jetons.
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table): void {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        // Réparation non destructive : une table déjà présente avant cette
        // migration peut appartenir à l'instance. Préserver ses jetons.
    }
};
