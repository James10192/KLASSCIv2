<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_external_identities', function (Blueprint $table) {
            $table->id();
            // Identifiant opaque : SHA256(issuer + NUL + subject + NUL + organization).
            // Ne pas stocker directement des jetons OAuth ou des e-mails.
            $table->char('identity_hash', 64)->unique();
            $table->string('organization', 120);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['organization', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_external_identities');
    }
};
