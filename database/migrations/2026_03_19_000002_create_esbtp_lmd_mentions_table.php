<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esbtp_lmd_mentions', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Ex: Genie Civil
            $table->string('code')->unique();               // Ex: GC
            $table->text('description')->nullable();
            $table->foreignId('domaine_id')->constrained('esbtp_lmd_domaines')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_lmd_mentions');
    }
};
