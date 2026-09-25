<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retire au rôle secrétaire la vue sur l'activité de tout le personnel.
 *
 * Ces droits lui étaient donnés PAR DÉFAUT, jamais choisis par l'école : une
 * secrétaire voyait la « note » du comptable et du directeur des études, et
 * pouvait les recalculer. La synchronisation des droits ne retire rien à un
 * rôle déjà peuplé, d'où cette migration unique. Une école qui veut rendre ce
 * droit à sa secrétaire le fait dans /esbtp/custom-roles.
 *
 * `performance.recalculate` disparaît avec le score : on le retire de TOUS
 * les rôles, puisqu'il ne commande plus rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_has_permissions') || ! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $secretaire = DB::table('roles')->where('name', 'secretaire')->where('guard_name', 'web')->value('id');
        $viewAll = DB::table('permissions')->where('name', 'performance.view_all')->where('guard_name', 'web')->value('id');
        $recalculer = DB::table('permissions')->whereIn('name', ['performance.recalculate', 'performance.configure'])->pluck('id');

        if ($secretaire && $viewAll) {
            DB::table('role_has_permissions')->where('role_id', $secretaire)->where('permission_id', $viewAll)->delete();
        }

        if ($recalculer->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $recalculer)->delete();
        }

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        // Rien à rendre : le droit retiré à la secrétaire se redonne, si
        // l'école le veut, dans /esbtp/custom-roles ; les droits de recalcul
        // ne commandent plus rien.
    }
};
