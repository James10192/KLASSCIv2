<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_parcours', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Ex: GCV Batiment & Urbanisme
            $table->string('code')->unique();               // Ex: GCV-BU
            $table->text('description')->nullable();
            $table->foreignId('mention_id')->constrained('esbtp_lmd_mentions')->cascadeOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('credits_licence')->default(180);   // Total credits Licence
            $table->unsignedInteger('credits_master')->default(120);    // Total credits Master
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_parcours');
    }
};
