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
        if (!Schema::hasTable('evaluations')) {
            Schema::create('evaluations', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('element_constitutif_id')->comment('EC concerné')
                      ->constrained()->cascadeOnDelete();
                $table->enum('type', ['examen', 'controle_continu', 'tp', 'projet', 'autre'])
                      ->default('examen');
                $table->float('coefficient', 4, 2)->default(1.0);
                $table->float('max_score', 8, 2)->default(20.0);
                $table->dateTime('date_time');
                $table->integer('duration')->comment('Durée en minutes');
                $table->string('location')->nullable();
                $table->boolean('is_published')->default(false)
                      ->comment('Si les résultats sont publiés');
                $table->dateTime('publication_date')->nullable();
                $table->foreignId('created_by')->nullable()
                      ->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()
                      ->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluations');
    }
};
