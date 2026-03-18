<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('deleted_at');
            $table->index('archived_at');
        });

        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('deleted_at');
            $table->index('archived_at');
        });

        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('deleted_at');
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_notes', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });

        Schema::table('esbtp_resultats', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });

        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
