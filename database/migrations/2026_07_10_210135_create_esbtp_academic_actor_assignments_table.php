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
        Schema::create('esbtp_academic_actor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained('esbtp_classes')->onDelete('restrict');
            $table->foreignId('annee_universitaire_id')
                ->constrained('esbtp_annee_universitaires')->onDelete('restrict');
            $table->string('responsibility', 40);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Current state is unique; OwenIt auditing preserves assignment history.
            $table->unique(
                ['user_id', 'classe_id', 'annee_universitaire_id', 'responsibility'],
                'eaaa_actor_scope_unique'
            );
            $table->index(
                ['annee_universitaire_id', 'classe_id', 'responsibility', 'is_active'],
                'eaaa_scope_active_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_academic_actor_assignments');
    }
};
