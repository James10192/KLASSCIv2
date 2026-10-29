<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            // Ajouter des colonnes pour la configuration robuste
            $table->string('type')->default('string')->after('value'); // string, integer, boolean, json, file
            $table->text('description')->nullable()->after('type');
            $table->boolean('is_required')->default(false)->after('description');
            $table->text('default_value')->nullable()->after('is_required');
            $table->json('validation_rules')->nullable()->after('default_value');
            $table->boolean('is_active')->default(true)->after('validation_rules');
            $table->boolean('requires_restart')->default(false)->after('is_active');
            $table->string('category')->nullable()->after('requires_restart'); // establishment, pdf, interface, academic, notifications
            $table->integer('sort_order')->default(0)->after('category');
            $table->unsignedBigInteger('created_by')->nullable()->after('sort_order');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            // Index pour améliorer les performances
            $table->index(['group', 'category']);
            $table->index(['is_active', 'is_required']);

            // Clés étrangères
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            // Supprimer les clés étrangères
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Supprimer les index
            $table->dropIndex(['group', 'category']);
            $table->dropIndex(['is_active', 'is_required']);

            // Supprimer les colonnes
            $table->dropColumn([
                'type',
                'description',
                'is_required',
                'default_value',
                'validation_rules',
                'is_active',
                'requires_restart',
                'category',
                'sort_order',
                'created_by',
                'updated_by'
            ]);
        });
    }
}
