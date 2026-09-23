<?php

use App\Services\Notes\UniciteDesNotes;
use Illuminate\Database\Migrations\Migration;

/**
 * Une seule note vivante par élève et par évaluation. Si une instance porte
 * déjà des doublons, la migration passe sans poser l'index et le journalise :
 * `php artisan notes:unicite` les liste, et repose l'index une fois tranchés.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(UniciteDesNotes::class)->poser();
    }

    public function down(): void
    {
        app(UniciteDesNotes::class)->retirer();
    }
};
