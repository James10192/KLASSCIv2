<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('esbtp_rdv_reservations')) {
            return;
        }

        Schema::table('esbtp_rdv_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('esbtp_rdv_reservations', 'convocation_canal')) {
                $table->string('convocation_canal', 20)->nullable()->after('convocation_message_id');
            }
            if (! Schema::hasColumn('esbtp_rdv_reservations', 'convocation_destination_masquee')) {
                $table->string('convocation_destination_masquee', 180)->nullable()->after('convocation_canal');
            }
            if (! Schema::hasColumn('esbtp_rdv_reservations', 'convocation_fallback_utilise')) {
                $table->boolean('convocation_fallback_utilise')->default(false)->after('convocation_destination_masquee');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('esbtp_rdv_reservations')) {
            return;
        }

        foreach (['convocation_fallback_utilise', 'convocation_destination_masquee', 'convocation_canal'] as $colonne) {
            if (Schema::hasColumn('esbtp_rdv_reservations', $colonne)) {
                Schema::table('esbtp_rdv_reservations', fn (Blueprint $table) => $table->dropColumn($colonne));
            }
        }
    }
};
