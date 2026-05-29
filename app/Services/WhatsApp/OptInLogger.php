<?php

namespace App\Services\WhatsApp;

use App\Models\ESBTPParent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tracking opt-in / opt-out WhatsApp (Phase 18 sécurité + ARTCI compliance).
 *
 * Conserve preuve immuable du consentement parent pour défense légale
 * (loi 2016-886 Côte d'Ivoire + Meta TOS opt-in obligatoire).
 *
 * Table whatsapp_opt_ins à créer (migration séparée) :
 *   - parent_id, phone, opted_in_at, opted_out_at, source, ip_address, audit_meta
 */
class OptInLogger
{
    public function logOptIn(ESBTPParent $parent, string $source, ?string $ip = null): void
    {
        try {
            DB::table('whatsapp_opt_ins')->insert([
                'parent_id' => $parent->id,
                'phone' => $parent->telephone,
                'opted_in_at' => now(),
                'source' => $source, // inscription_form / settings_page / whatsapp_yes
                'ip_address' => $ip,
                'audit_meta' => json_encode(['user_agent' => request()->userAgent()]),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[wa-optin] Log opt-in failed', ['error' => $e->getMessage()]);
        }
    }

    public function logOptOut(ESBTPParent $parent, string $source = 'stop_keyword'): void
    {
        try {
            DB::table('whatsapp_opt_ins')
                ->where('parent_id', $parent->id)
                ->whereNull('opted_out_at')
                ->update([
                    'opted_out_at' => now(),
                    'opt_out_source' => $source,
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            Log::error('[wa-optin] Log opt-out failed', ['error' => $e->getMessage()]);
        }
    }

    public function isOptedIn(ESBTPParent $parent): bool
    {
        return DB::table('whatsapp_opt_ins')
            ->where('parent_id', $parent->id)
            ->whereNotNull('opted_in_at')
            ->whereNull('opted_out_at')
            ->exists();
    }
}
