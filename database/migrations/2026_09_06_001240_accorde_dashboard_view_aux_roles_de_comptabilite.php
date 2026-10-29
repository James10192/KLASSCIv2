<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La route du tableau de bord comptable n'exigeait que `comptabilite.access` ;
 * elle exige desormais aussi `comptabilite.dashboard.view`, qui existait au
 * registre sans etre demandee nulle part.
 *
 * Les roles livres l'ont deja. Mais une ecole a pu fabriquer un role sur mesure
 * portant `comptabilite.access` seul : pour elle, l'ecran se fermerait du jour au
 * lendemain, sans message et sans que personne ait rien change. On accorde donc
 * la permission a tout role qui detient deja l'acces au module. Personne ne perd
 * rien, et la permission devient veritablement le point de decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        $acces = DB::table('permissions')->where('name', 'comptabilite.access')->value('id');
        $tableau = DB::table('permissions')->where('name', 'comptabilite.dashboard.view')->value('id');

        // Sur une instance ou le registre n'a pas encore ete deploye, il n'y a
        // rien a rattraper : fix_permissions.php posera les deux permissions.
        if (! $acces || ! $tableau) {
            return;
        }

        $rolesAvecAcces = DB::table('role_has_permissions')
            ->where('permission_id', $acces)
            ->pluck('role_id');

        $rolesDejaServis = DB::table('role_has_permissions')
            ->where('permission_id', $tableau)
            ->pluck('role_id')
            ->all();

        $aAccorder = $rolesAvecAcces->reject(fn ($id) => in_array($id, $rolesDejaServis, true))->values();

        if ($aAccorder->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->insert(
            $aAccorder->map(fn ($id) => ['permission_id' => $tableau, 'role_id' => $id])->all()
        );

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        // On ne retire rien : impossible de distinguer ce que cette migration a
        // accorde de ce qu'une ecole a coche elle-meme depuis.
    }
};
