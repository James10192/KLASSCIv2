<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsBackupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings_backups', function (Blueprint $table) {
            $table->id();
            $table->string('backup_name');
            $table->text('description')->nullable();
            $table->json('settings_data'); // Sauvegarde complète des paramètres
            $table->string('backup_type')->default('manual'); // manual, automatic, pre_update
            $table->string('status')->default('active'); // active, restored, archived
            $table->timestamp('backup_date');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('restored_by')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->text('restore_notes')->nullable();
            $table->timestamps();

            // Index
            $table->index(['backup_type', 'status']);
            $table->index(['backup_date']);
            $table->index(['created_by']);

            // Clés étrangères
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('restored_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings_backups');
    }
}
