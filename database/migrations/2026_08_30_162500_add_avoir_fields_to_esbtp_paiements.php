<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->string('nature', 20)->default('encaissement')->after('status');
            $table->string('avoir_kind', 20)->nullable()->after('nature');
            $table->unsignedBigInteger('parent_paiement_id')->nullable()->after('avoir_kind');
            $table->string('numero_avoir', 40)->nullable()->after('numero_recu');
            $table->foreign('parent_paiement_id', 'paiements_parent_avoir_fk')
                ->references('id')->on('esbtp_paiements')
                ->nullOnDelete();
            $table->index(['nature', 'avoir_kind']);
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_paiements', function (Blueprint $table) {
            $table->dropForeign('paiements_parent_avoir_fk');
            $table->dropIndex(['nature', 'avoir_kind']);
            $table->dropColumn(['nature', 'avoir_kind', 'parent_paiement_id', 'numero_avoir']);
        });
    }
};
