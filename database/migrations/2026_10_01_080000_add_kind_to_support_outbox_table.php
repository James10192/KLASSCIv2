<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La boite d'envoi KLASSCI Care porte desormais deux natures de messages : les
 * signalements (tickets) et les avis 👍 / 👎 sur les reponses de l'assistant.
 * Meme reprise, meme recul progressif, meme cle d'idempotence : seule la route
 * du Master change. Les lignes existantes restent des signalements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_outbox', function (Blueprint $table) {
            $table->string('kind', 32)->default('ticket')->after('user_id')
                ->comment('ticket | assistant_feedback');
            $table->index(['kind', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('support_outbox', function (Blueprint $table) {
            $table->dropIndex(['kind', 'user_id']);
            $table->dropColumn('kind');
        });
    }
};
