<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Teacher;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'status')) {
                $table->string('status')->after('matricule')->default(Teacher::STATUS_VACATAIRE);
            }
            if (!Schema::hasColumn('teachers', 'teaching_hours_due')) {
                $table->float('teaching_hours_due')->after('status')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('teachers', 'teaching_hours_due')) {
                $table->dropColumn('teaching_hours_due');
            }
        });
    }
};
