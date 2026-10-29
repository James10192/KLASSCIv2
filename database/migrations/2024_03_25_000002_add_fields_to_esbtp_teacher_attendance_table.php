<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEsbtpTeacherAttendanceTable extends Migration
{
    public function up()
    {
        Schema::table('esbtp_teacher_attendance', function (Blueprint $table) {
            $table->enum('status', ['present', 'late'])->default('present')->after('device_info');
            $table->integer('attempt_count')->default(1)->after('status');
            $table->enum('validation_status', ['pending', 'validated', 'rejected'])->default('pending')->after('attempt_count');
            $table->text('validation_notes')->nullable()->after('validation_status');
            $table->foreignId('validated_by')->nullable()->after('validation_notes')->constrained('users');
            $table->timestamp('validated_at')->nullable()->after('validated_by');
        });
    }

    public function down()
    {
        Schema::table('esbtp_teacher_attendance', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'status',
                'attempt_count',
                'validation_status',
                'validation_notes',
                'validated_by',
                'validated_at'
            ]);
        });
    }
}
