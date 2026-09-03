<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La double authentification, compte par compte.
 *
 * Trois colonnes, et la troisième est celle qui empêche de verrouiller une
 * école. `confirme_le` n'est posé qu'après qu'un utilisateur a saisi un code
 * valide depuis son téléphone : tant qu'il est nul, le compte se connecte
 * comme avant. Sans lui, quelqu'un qui perd son téléphone entre le moment où
 * il génère un secret et celui où il l'essaie perd son accès — et sur une
 * école en pleine rentrée, cet accès est celui du secrétariat.
 *
 * Le secret et les codes de secours sont chiffrés par Laravel (cast
 * `encrypted`). Une base lue par un tiers ne doit pas suffire à contourner le
 * second facteur : sinon il ne protège de rien de plus que le mot de passe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('double_auth_secret')->nullable()->after('password');
            $table->text('double_auth_codes_secours')->nullable()->after('double_auth_secret');
            $table->timestamp('double_auth_confirme_le')->nullable()->after('double_auth_codes_secours');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['double_auth_secret', 'double_auth_codes_secours', 'double_auth_confirme_le']);
        });
    }
};
