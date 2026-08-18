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

    public function test_partial_post_cannot_turn_off_display_checkboxes_without_save_flag(): void
    {
        SettingsHelper::setOrCreate('bulletin_show_subjects_table', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_show_header', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_show_signatures', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_semester1_weight', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_semester2_weight', '2', 'bulletin');

        $response = $this->from(route('esbtp.bulletins.configuration'))
            ->post(route('esbtp.bulletins.save-configuration'), [
                'bulletin_bts1_semester1_weight' => '1',
                'bulletin_bts1_semester2_weight' => '2',
            ]);

        $response->assertRedirect();
        self::assertSame('1', SettingsHelper::get('bulletin_show_subjects_table'));
        self::assertSame('1', SettingsHelper::get('bulletin_show_header'));
        self::assertSame('1', SettingsHelper::get('bulletin_show_signatures'));
    }

    public function test_full_display_save_can_still_uncheck_subjects_table(): void
    {
        SettingsHelper::setOrCreate('bulletin_show_subjects_table', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_show_header', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_semester1_weight', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_semester2_weight', '2', 'bulletin');

        $response = $this->from(route('esbtp.bulletins.configuration'))
            ->post(route('esbtp.bulletins.save-configuration'), [
                'bulletin_save_display' => '1',
                'bulletin_show_header' => '1',
                'bulletin_bts1_semester1_weight' => '1',
                'bulletin_bts1_semester2_weight' => '2',
            ]);

        $response->assertRedirect();
        self::assertSame('0', SettingsHelper::get('bulletin_show_subjects_table'));
        self::assertSame('1', SettingsHelper::get('bulletin_show_header'));
    }

    public function test_ajax_save_returns_json_settings_without_redirect(): void
    {
        SettingsHelper::setOrCreate('bulletin_bts1_semester1_weight', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_semester2_weight', '2', 'bulletin');

        $response = $this->postJson(route('esbtp.bulletins.save-configuration'), [
            'bulletin_style' => 'yakro',
            'bulletin_font_size' => '14',
            'bulletin_bts1_s1_council_title' => 'Appreciation du Conseil de Classe',
            'bulletin_bts1_semester1_weight' => '1',
            'bulletin_bts1_semester2_weight' => '2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('settings.bulletin_style', 'yakro');
        $response->assertJsonPath('settings.bulletin_font_size', '14');
        $response->assertJsonPath(
            'settings.bulletin_bts1_s1_council_title',
            'Appreciation du Conseil de Classe'
        );
        self::assertFalse($response->isRedirection());
        self::assertSame('yakro', SettingsHelper::get('bulletin_style'));
        self::assertSame('14', SettingsHelper::get('bulletin_font_size'));
        self::assertSame(
            'Appreciation du Conseil de Classe',
            SettingsHelper::get('bulletin_bts1_s1_council_title')
        );
    }
}
