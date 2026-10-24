<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Squelette généré par php artisan make:migration dans Laravel CI
// (run 40133022563, étape « Générer le squelette de révocations »).
// Les décisions de retrait restent strictement locales à la base du tenant.
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('role_permission_revocations')) {
            return;
        }

        Schema::create('role_permission_revocations', function (Blueprint $table) {
            $table->id();
            $table->string('role_name', 125);
            $table->string('permission_name', 190);
            $table->string('motif', 500)->nullable();
            $table->unsignedBigInteger('retiree_par')->nullable();
            $table->timestamps();

            $table->unique(['role_name', 'permission_name'], 'role_perm_revocation_unique');
            $table->index('role_name', 'role_perm_revocation_role_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_permission_revocations');
    }
};
