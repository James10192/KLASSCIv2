<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_examen_surveillants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('examen_id')
                ->constrained('esbtp_examens_planifies')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('role', 32)->default('surveillant')
                ->comment('surveillant|surveillant_principal|secretaire|responsable_salle');
            $table->boolean('notification_sent')->default(false);
            $table->dateTime('notification_sent_at')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->dateTime('confirmed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['examen_id', 'user_id'], 'uniq_examen_user');
            $table->index(['user_id', 'confirmed'], 'idx_surv_user_confirmed');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_examen_surveillants');
    }
};
