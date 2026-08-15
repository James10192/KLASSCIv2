<?php

namespace Tests\Feature\Bulletin;

use App\Helpers\SettingsHelper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BtsBulletinConfigurationHttpTest extends TestCase
{
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::connection('sqlite')->create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_restart')->default(false);
            $table->string('category')->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Cache::flush();
    }

    public function test_partial_post_cannot_empty_required_bts1_threshold_text_from_existing_state(): void
    {
        SettingsHelper::setOrCreate('bulletin_bts1_council_mode', 'threshold', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_council_threshold', '10', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_council_below_text', 'Redouble la classe', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_council_at_or_above_text', 'Admis(e) en 2e Année BTS', 'bulletin');

        $response = $this->postJson(route('esbtp.bulletins.save-configuration'), [
            'bulletin_bts1_council_below_text' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bulletin_bts1_council_below_text']);
        self::assertSame('Redouble la classe', SettingsHelper::get('bulletin_bts1_council_below_text'));
    }

    public function test_partial_post_cannot_make_effective_bts_weight_pair_zero_zero(): void
    {
        SettingsHelper::setOrCreate('bulletin_bts1_semester1_weight', '0', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_semester2_weight', '1', 'bulletin');

        $response = $this->postJson(route('esbtp.bulletins.save-configuration'), [
            'bulletin_bts1_semester2_weight' => '0',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bulletin_bts1_semester2_weight']);
        self::assertSame('1', SettingsHelper::get('bulletin_bts1_semester2_weight'));
    }
}
