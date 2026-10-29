<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_deliberations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulletin_id')->constrained('esbtp_lmd_bulletins')->cascadeOnDelete();
            $table->string('type')->default('semestre');             // semestre|annuel
            $table->string('decision')->nullable();                 // Encouragement, Avertissement, Exclusion...
            $table->string('mention_honorifique')->nullable();      // Felicitations du jury, Tableau d'honneur...
            $table->text('observations')->nullable();
            $table->date('jury_date')->nullable();
            $table->string('president_jury')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['bulletin_id', 'type'], 'lmd_delib_bulletin_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_deliberations');
    }
};
