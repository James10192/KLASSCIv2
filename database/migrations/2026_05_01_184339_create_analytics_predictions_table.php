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
        Schema::create('analytics_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('predictor', 50);
            $table->string('context_hash', 64);
            $table->json('context_json');
            $table->date('target_date')->nullable();
            $table->decimal('predicted_value', 15, 2)->nullable();
            $table->string('predicted_label', 50)->nullable();
            $table->decimal('confidence_lower', 15, 2)->nullable();
            $table->decimal('confidence_upper', 15, 2)->nullable();
            $table->string('confidence_label', 20)->nullable();
            $table->json('explanation_json');
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->decimal('accuracy_score', 5, 4)->nullable();
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->index(['predictor', 'target_date']);
            $table->index('context_hash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analytics_predictions');
    }
};
