<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEsbtpFraisSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esbtp_frais_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inscription_id');
            $table->unsignedBigInteger('frais_category_id');
            $table->unsignedBigInteger('selected_option_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscribed_at')->useCurrent();
            $table->unsignedBigInteger('created_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Contraintes uniques et index
            $table->unique(['inscription_id', 'frais_category_id'], 'subscription_unique');
            $table->index(['inscription_id', 'is_active'], 'subscription_active_idx');
            $table->index(['frais_category_id', 'is_active'], 'category_active_idx');
            $table->index('created_by', 'created_by_idx');
            
            // Clés étrangères
            $table->foreign('inscription_id', 'subscriptions_inscription_fk')
                  ->references('id')->on('esbtp_inscriptions')
                  ->onDelete('cascade');
            $table->foreign('frais_category_id', 'subscriptions_category_fk')
                  ->references('id')->on('esbtp_frais_categories')
                  ->onDelete('cascade');
            $table->foreign('selected_option_id')
                  ->references('id')->on('esbtp_frais_options')
                  ->onDelete('set null');
            $table->foreign('created_by', 'subscriptions_user_fk')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esbtp_frais_subscriptions');
    }
}
