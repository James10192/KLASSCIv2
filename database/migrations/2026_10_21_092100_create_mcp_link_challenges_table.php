<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_link_challenges', function (Blueprint $table) {
            $table->id();
            // Code aléatoire 256 bits : uniquement son empreinte en base.
            $table->char('code_hash', 64)->unique();
            $table->char('identity_hash', 64);
            $table->string('organization', 120);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            // Deux contrôles distincts : identité OAuth signée par ChatGPT,
            // puis validation nominative par un administrateur local différent.
            $table->unsignedBigInteger('approved_user_id')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['identity_hash', 'organization']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_link_challenges');
    }
};
