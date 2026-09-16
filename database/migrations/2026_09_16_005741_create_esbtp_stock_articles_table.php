<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('esbtp_stock_articles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64);
            $table->string('libelle');
            $table->string('type', 32)->default('consommable');
            $table->timestamps();
            $table->unique('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('esbtp_stock_articles');
    }
};
