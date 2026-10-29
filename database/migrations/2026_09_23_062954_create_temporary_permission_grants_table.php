<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une permission accordee a une personne pour un temps donne.
 *
 * Rien n'est ecrit dans les tables de Spatie : l'acces tient tant que
 * `expires_at` n'est pas passe, et cesse de lui-meme a l'heure dite, sans
 * tache planifiee. Une ligne expiree ou retiree reste en base : c'est la trace
 * de qui a ouvert quoi a qui, et pourquoi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_permission_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 125);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('motif');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_permission_grants');
    }
};
