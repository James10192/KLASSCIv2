<?php

namespace App\Services\WhatsApp;

use App\Models\ESBTPParent;
use App\Models\ParentNotificationPreference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bounce tracker per-recipient (Phase 4 hardening).
 *
 * Pattern hérité adminKlassci PR #51. Si N envois consécutifs échouent
 * pour un même phone → auto-désactive le canal WhatsApp dans préférences parent.
 */
class BounceTracker
{
    private const FAILURE_THRESHOLD = 3;

    public function recordBounce(ESBTPParent $parent): void
    {
        $count = $this->getBounceCount($parent->id) + 1;

        DB::table('whatsapp_bounces')->updateOrInsert(
            ['parent_id' => $parent->id],
            ['bounce_count' => $count, 'last_bounce_at' => now(), 'updated_at' => now()]
        );

        if ($count >= self::FAILURE_THRESHOLD) {
            $this->autoDisable($parent);
        }
    }

    public function recordSuccess(ESBTPParent $parent): void
    {
        DB::table('whatsapp_bounces')->where('parent_id', $parent->id)->delete();
    }

    private function getBounceCount(int $parentId): int
    {
        return (int) DB::table('whatsapp_bounces')
            ->where('parent_id', $parentId)
            ->value('bounce_count');
    }

    private function autoDisable(ESBTPParent $parent): void
    {
        try {
            $prefs = $parent->getOrCreateNotificationPreferences();
            $channels = $prefs->preferred_channels ?? ['app', 'email'];
            $channels = array_values(array_diff($channels, ['whatsapp']));
            $prefs->update(['preferred_channels' => $channels]);

            Log::warning('[wa-bounce] Auto-disabled WhatsApp', [
                'parent_id' => $parent->id,
                'phone' => $parent->telephone,
            ]);
        } catch (\Throwable $e) {
            Log::error('[wa-bounce] Auto-disable failed', ['error' => $e->getMessage()]);
        }
    }
}
