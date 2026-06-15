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
        Schema::table('esbtp_salaires', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_salaires', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->after('user_id')
                    ->constrained('esbtp_teachers')->nullOnDelete();
            }
            if (!Schema::hasColumn('esbtp_salaires', 'period_start')) {
                $table->date('period_start')->nullable()->after('annee');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'heures_total')) {
                $table->decimal('heures_total', 8, 2)->default(0)->after('salaire_base');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'impot_its')) {
                $table->decimal('impot_its', 12, 2)->default(0)->after('retenues');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'cnps')) {
                $table->decimal('cnps', 12, 2)->default(0)->after('impot_its');
            }
            // Workflow OHADA : brouillon → valide → paye (annule possible)
            if (!Schema::hasColumn('esbtp_salaires', 'workflow_status')) {
                $table->enum('workflow_status', ['brouillon', 'valide', 'paye', 'annule'])
                    ->default('brouillon')->after('statut');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'prepared_by')) {
                $table->unsignedBigInteger('prepared_by')->nullable()->after('createur_id');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'prepared_at')) {
                $table->timestamp('prepared_at')->nullable()->after('prepared_by');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'paid_by')) {
                $table->unsignedBigInteger('paid_by')->nullable()->after('date_validation');
            }
            if (!Schema::hasColumn('esbtp_salaires', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid_by');
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
        Schema::table('esbtp_salaires', function (Blueprint $table) {
            foreach (['period_start', 'period_end', 'heures_total', 'impot_its', 'cnps',
                      'workflow_status', 'prepared_by', 'prepared_at', 'paid_by', 'paid_at'] as $col) {
                if (Schema::hasColumn('esbtp_salaires', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('esbtp_salaires', 'teacher_id')) {
                $table->dropConstrainedForeignId('teacher_id');
            }
        });
    }
};
