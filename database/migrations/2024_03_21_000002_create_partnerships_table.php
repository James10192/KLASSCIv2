<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('organization');
            $table->enum('type', ['academic', 'industry', 'research', 'other']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_phone', 20)->nullable();
            $table->enum('status', ['active', 'pending', 'expired']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('partnerships');
    }
};
