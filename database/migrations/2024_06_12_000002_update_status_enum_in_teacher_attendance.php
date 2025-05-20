<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement("ALTER TABLE esbtp_teacher_attendance MODIFY status ENUM('present','late','not_signed') DEFAULT 'not_signed'");
    }
    public function down()
    {
        DB::statement("ALTER TABLE esbtp_teacher_attendance MODIFY status ENUM('present','late') DEFAULT 'present'");
    }
};
