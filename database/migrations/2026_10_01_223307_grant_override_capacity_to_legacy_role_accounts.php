<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dépasser la capacité d'une classe était réservé aux comptes dont la colonne
 * HÉRITÉE `users.role` valait « superAdmin » ou « secretaire » — pas au rôle
 * Spatie du même nom. Le droit devient la permission
 * `inscriptions.override_capacity` : on la donne DIRECTEMENT à ces comptes-là,
 * et à eux seuls, pour que rien ne change le jour du déploiement.
 *
 * Idempotente : un compte qui a déjà la permission n'est ni retouché ni noté.
 * down() ne retire que ce que up() a donné, d'après la table de suivi.
 */
return new class extends Migration
{
    private const PERMISSION = 'inscriptions.override_capacity';

    private const SUIVI = 'heritage_droit_depassement_capacite';

    public function up(): void
    {
        // Gardée : rejouée par un test sous transaction, une création de table
        // validerait implicitement la transaction MySQL.
        if (! Schema::hasTable(self::SUIVI)) {
            Schema::create(self::SUIVI, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->primary();
                $table->timestamp('attribue_le')->useCurrent();
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        $permission = Permission::findOrCreate(self::PERMISSION, 'web');

        User::query()->whereIn('role', ['superAdmin', 'secretaire'])->each(function (User $user) use ($permission) {
            if ($user->permissions()->whereKey($permission->id)->exists()) {
                return;
            }
            $user->givePermissionTo($permission);
            DB::table(self::SUIVI)->insertOrIgnore(['user_id' => $user->id, 'attribue_le' => now()]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::SUIVI)) {
            return;
        }

        $ids = DB::table(self::SUIVI)->pluck('user_id')->all();
        if ($ids !== [] && ($permission = Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->first())) {
            foreach (User::whereIn('id', $ids)->get() as $user) {
                $user->revokePermissionTo($permission);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        Schema::dropIfExists(self::SUIVI);
    }
};
