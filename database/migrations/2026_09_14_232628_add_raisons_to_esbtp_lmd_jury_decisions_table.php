<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La motivation de chaque décision de jury existe déjà — on la jetait.
 *
 * `JuryDeliberationService::calculerDecisionAuto()` rédige, pour chaque
 * branche, la raison en français qui l'explique : « Moyenne 11.20 OK mais
 * crédits 24/30 insuffisants », « Note eliminatoire detectee », « Periode
 * annuelle : 2 bulletins agreges ». Elle était retournée sous la clé
 * `raisons`, absente de `$fillable`, absente des attributs persistés, absente
 * du procès-verbal : la seule occurrence de ce mot dans tout le dépôt était sa
 * fabrication.
 *
 * Le jury ne voyait donc aucune motivation, non pas parce qu'il en manquait
 * une, mais parce qu'on la détruisait à chaque appel. Une colonne suffit à la
 * rendre visible — et gravable au PV, où elle a sa place : un procès-verbal
 * qui dit « ajourné » sans dire pourquoi se conteste mal.
 *
 * Nullable et sans valeur par défaut : les décisions déjà prises n'en ont pas,
 * et leur en inventer une serait pire que de l'absence assumée. Elles la
 * recevront à leur prochain recalcul, s'il a lieu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_lmd_jury_decisions', function (Blueprint $table) {
            $table->json('raisons')
                ->nullable()
                ->after('credits_attendus')
                ->comment('Motivation calculee de la decision (liste de phrases). Null = decision anterieure a la colonne.');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_lmd_jury_decisions', function (Blueprint $table) {
            $table->dropColumn('raisons');
        });
    }
};
