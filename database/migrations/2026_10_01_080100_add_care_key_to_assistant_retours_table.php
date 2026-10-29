<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque avis sur une reponse de l'assistant part au Master (KLASSCI Care).
 *
 * `care_uuid` identifie l'avis de facon stable ; `care_version` compte les
 * versions transmises. La cle d'idempotence envoyee est l'uuid pour la
 * premiere version, puis « uuid:N » quand la personne change d'avis apres un
 * premier envoi : une cle d'idempotence est liee a son contenu, le Master
 * refuse de la rejouer avec un autre corps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_retours', function (Blueprint $table) {
            $table->uuid('care_uuid')->nullable()->unique()->after('care_reference');
            $table->unsignedSmallInteger('care_version')->default(0)->after('care_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_retours', function (Blueprint $table) {
            $table->dropUnique(['care_uuid']);
            $table->dropColumn(['care_uuid', 'care_version']);
        });
    }
};
