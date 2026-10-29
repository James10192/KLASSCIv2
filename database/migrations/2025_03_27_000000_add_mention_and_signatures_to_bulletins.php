<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('esbtp_bulletins', 'mention')) {
                // Add mention field
                $table->string('mention')->nullable()->after('decision_conseil');
            }

            if (!Schema::hasColumn('esbtp_bulletins', 'signature_directeur')) {
                // Add signature fields
                $table->boolean('signature_directeur')->default(false)->after('mention');
            }

            if (!Schema::hasColumn('esbtp_bulletins', 'signature_responsable')) {
                $table->boolean('signature_responsable')->default(false)->after('signature_directeur');
            }

            if (!Schema::hasColumn('esbtp_bulletins', 'signature_parent')) {
                $table->boolean('signature_parent')->default(false)->after('signature_responsable');
            }

            if (!Schema::hasColumn('esbtp_bulletins', 'date_signature_directeur')) {
                // Add signature dates
                $table->timestamp('date_signature_directeur')->nullable()->after('signature_parent');
            }

            if (!Schema::hasColumn('esbtp_bulletins', 'date_signature_responsable')) {
                $table->timestamp('date_signature_responsable')->nullable()->after('date_signature_directeur');
            }

            if (!Schema::hasColumn('esbtp_bulletins', 'date_signature_parent')) {
                $table->timestamp('date_signature_parent')->nullable()->after('date_signature_responsable');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('esbtp_bulletins', function (Blueprint $table) {
            $table->dropColumn([
                'mention',
                'signature_directeur',
                'signature_responsable',
                'signature_parent',
                'date_signature_directeur',
                'date_signature_responsable',
                'date_signature_parent'
            ]);
        });
    }
};
