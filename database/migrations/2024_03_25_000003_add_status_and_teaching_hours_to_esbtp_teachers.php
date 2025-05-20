<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Teacher;

class AddStatusAndTeachingHoursToEsbtpTeachers extends Migration
{
    public function up()
    {
        Schema::table('esbtp_teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('esbtp_teachers', 'status')) {
                $table->string('status')->after('matricule')->default(Teacher::STATUS_VACATAIRE);
            }
            if (!Schema::hasColumn('esbtp_teachers', 'teaching_hours_due')) {
                $table->float('teaching_hours_due')->after('status')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('esbtp_teachers', function (Blueprint $table) {
            if (Schema::hasColumn('esbtp_teachers', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('esbtp_teachers', 'teaching_hours_due')) {
                $table->dropColumn('teaching_hours_due');
            }
        });
    }
}
