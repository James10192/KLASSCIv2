<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fees', function (Blueprint $table) {
            // Modifier la colonne status pour inclure 'pending'
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending')->change();
        });
    }

    public function down()
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });
    }
};
