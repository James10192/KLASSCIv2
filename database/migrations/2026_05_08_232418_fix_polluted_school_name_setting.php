<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One-shot — corrige le school_name pollué hérité d'un .env APP_NAME=
 * "ESBTP-yAKRO" (tenants clonés depuis yakro avant le hardening de
 * SettingsHelper::initializeDefaults du 8 mai 2026).
 *
 * Détection : valeur exacte 'ESBTP-yAKRO' (ou variantes case) en DB pour
 * la clé school_name, ET TENANT_CODE différent de 'esbtp-yakro'.
 *
 * Action : reset à 'KLASSCI' → laisse l'admin du tenant configurer le bon
 * nom dans /esbtp/settings (page premium déjà existante).
 *
 * Idempotente : ne touche rien si la valeur est déjà correcte ou si on est
 * sur le tenant esbtp-yakro lui-même.
 */
return new class extends Migration {
    public function up(): void
    {
        $tenantCode = (string) config('app.tenant_code', env('TENANT_CODE', 'unknown'));

        if ($tenantCode === 'esbtp-yakro') {
            Log::info('fix_polluted_school_name: skip on esbtp-yakro (legitimate value)');
            return;
        }

        $pollutedValues = ['ESBTP-yAKRO', 'ESBTP-yakro', 'ESBTP-Yakro', 'ESBTP yAKRO', 'ESBTP yakro'];

        if (! \Schema::hasTable('settings')) {
            Log::info('fix_polluted_school_name: settings table not found, skip');
            return;
        }

        $updated = DB::table('settings')
            ->where('key', 'school_name')
            ->whereIn('value', $pollutedValues)
            ->update(['value' => 'KLASSCI', 'updated_at' => now()]);

        DB::table('settings')
            ->where('key', 'school_acronym')
            ->whereIn('value', $pollutedValues)
            ->update(['value' => 'KLASSCI', 'updated_at' => now()]);

        if ($updated > 0) {
            Log::warning('fix_polluted_school_name: cleaned ' . $updated . ' polluted setting(s) on tenant ' . $tenantCode);
            \Cache::flush();
        } else {
            Log::info('fix_polluted_school_name: no pollution detected on tenant ' . $tenantCode);
        }
    }

    public function down(): void
    {
        // No-op — on ne ré-injecte pas une valeur polluée
    }
};
