<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_stock_mouvements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->string('type', 32);
            $table->decimal('quantite', 12, 4);
            $table->unsignedBigInteger('reception_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('article_id')->references('id')->on('esbtp_stock_articles')->cascadeOnDelete();
            $table->index(['article_id', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_stock_mouvements');
    }
};
