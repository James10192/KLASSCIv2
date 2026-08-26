<?php

use App\Services\BulletinMentionResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $legacy = DB::table('settings')->whereIn('key', $this->legacyKeys())->pluck('value', 'key');

        if (! DB::table('settings')->where('key', BulletinMentionResolver::SETTING_KEY)->exists()) {
            $rules = BulletinMentionResolver::fromLegacySettings(
                static fn (string $key, mixed $default) => $legacy[$key] ?? $default
            );

            DB::table('settings')->insert([
                'key' => BulletinMentionResolver::SETTING_KEY,
                'value' => json_encode($rules),
                'type' => 'json',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Règles de mentions bulletin (libellé, min, max, source)',
                'is_required' => false,
                'default_value' => json_encode(BulletinMentionResolver::catalog()),
                'validation_rules' => null,
                'sort_order' => 51,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('settings')->where('key', BulletinMentionResolver::AUTH_TEXT_KEY)->exists()) {
            DB::table('settings')->insert([
                'key' => BulletinMentionResolver::AUTH_TEXT_KEY,
                'value' => BulletinMentionResolver::AUTH_TEXT_DEFAULT,
                'type' => 'string',
                'group' => 'bulletin',
                'category' => 'bulletin',
                'description' => 'Texte anti-duplicata en pied de bulletin',
                'is_required' => false,
                'default_value' => BulletinMentionResolver::AUTH_TEXT_DEFAULT,
                'validation_rules' => json_encode(['nullable', 'string']),
                'sort_order' => 52,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key', [
            BulletinMentionResolver::SETTING_KEY,
            BulletinMentionResolver::AUTH_TEXT_KEY,
        ])->delete();
    }

    /**
     * @return list<string>
     */
    private function legacyKeys(): array
    {
        $keys = [];

        foreach (BulletinMentionResolver::catalog() as $rule) {
            $keys[] = 'bulletin_'.$rule['key'].'_threshold';
            $keys[] = 'bulletin_'.$rule['key'].'_threshold_max';
            $keys[] = 'bulletin_'.$rule['key'].'_source';
            $keys[] = 'bulletin_show_'.$rule['key'];
        }

        return $keys;
    }
};
