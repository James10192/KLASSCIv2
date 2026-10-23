<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une seule base MySQL/MariaDB PAR école : la contrainte UNIQUE
        // garantit l'anti-rejeu entre PHP-FPM, cron et processus cPanel,
        // sans Redis, daemon ou cache partagé à provisionner.
        Schema::create('mcp_bridge_nonces', function (Blueprint $table) {
            $table->char('nonce_hash', 64)->primary();
            $table->timestamp('expires_at')->index();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_bridge_nonces');
    }
};
