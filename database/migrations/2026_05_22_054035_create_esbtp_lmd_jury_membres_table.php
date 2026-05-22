<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_lmd_jury_membres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jury_id')
                ->constrained('esbtp_lmd_jurys')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('role', 32)->default('assesseur')
                ->comment('president|assesseur|secretaire|consultatif');
            $table->boolean('present')->default(true)
                ->comment('Comptabilisé dans le quorum');

            // Signature digital (canvas HTML5 base64 ou checkbox simple selon setting)
            $table->longText('signature_data')->nullable()
                ->comment('PNG base64 du canvas signature OU JSON {checked, ip, ts}');
            $table->dateTime('signature_at')->nullable();
            $table->string('signature_ip', 45)->nullable();
            $table->text('signature_user_agent')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jury_id', 'user_id'], 'uniq_jury_user');
            $table->index(['role'], 'idx_jury_membres_role');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_lmd_jury_membres');
    }
};
