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
                // Ce que le vrai formulaire envoie : un marqueur par case rendue,
                // la case décochée elle-même n'étant jamais transmise.
                'bulletin_show_subjects_table_present' => '1',
                'bulletin_show_header_present' => '1',
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
            'bulletin_header_scale' => '125',
            'bulletin_header_left_font_size' => '14',
            'bulletin_header_school_name_font_size' => '20',
            'bulletin_header_school_meta_font_size' => '11',
            'bulletin_header_title_font_size' => '19',
            'bulletin_header_right_font_size' => '13',
            'bulletin_header_logo_height' => '110',
            'bulletin_signature_height' => '120',
            'bulletin_signature_width' => '330',
            'bulletin_signature_font_size' => '12',
            'bulletin_edition_font_size' => '10',
            'bulletin_edition_opacity' => '75',
            'bulletin_authenticity_font_size' => '12',
            'bulletin_authenticity_opacity' => '55',
            'bulletin_bts1_s1_council_title' => 'Appreciation du Conseil de Classe',
            'bulletin_bts1_semester1_weight' => '1',
            'bulletin_bts1_semester2_weight' => '2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('settings.bulletin_style', 'yakro');
        $response->assertJsonPath('settings.bulletin_font_size', '14');
        $response->assertJsonPath('settings.bulletin_header_scale', '125');
        $response->assertJsonPath('settings.bulletin_header_left_font_size', '14');
        $response->assertJsonPath('settings.bulletin_header_school_name_font_size', '20');
        $response->assertJsonPath('settings.bulletin_header_logo_height', '110');
        $response->assertJsonPath('settings.bulletin_signature_height', '120');
        $response->assertJsonPath('settings.bulletin_signature_width', '330');
        $response->assertJsonPath('settings.bulletin_authenticity_font_size', '12');
        $response->assertJsonPath('settings.bulletin_authenticity_opacity', '55');
        $response->assertJsonPath(
            'settings.bulletin_bts1_s1_council_title',
            'Appreciation du Conseil de Classe'
        );
        self::assertFalse($response->isRedirection());
        self::assertSame('yakro', SettingsHelper::get('bulletin_style'));
        self::assertSame('14', SettingsHelper::get('bulletin_font_size'));
        self::assertSame('125', SettingsHelper::get('bulletin_header_scale'));
        self::assertSame('14', SettingsHelper::get('bulletin_header_left_font_size'));
        self::assertSame('110', SettingsHelper::get('bulletin_header_logo_height'));
        self::assertSame('120', SettingsHelper::get('bulletin_signature_height'));
        self::assertSame('55', SettingsHelper::get('bulletin_authenticity_opacity'));
        self::assertSame(
            'Appreciation du Conseil de Classe',
            SettingsHelper::get('bulletin_bts1_s1_council_title')
        );
    }

    public function test_lmd_ajax_save_persists_typography_logo_and_spacing_without_settings_redirect(): void
    {
        $response = $this->postJson(route('esbtp.bulletins.save-configuration'), [
            'lmd_bulletin_font_school_name' => '18',
            'lmd_bulletin_font_title' => '16.5',
            'lmd_bulletin_font_header_meta' => '10',
            'lmd_bulletin_font_table' => '11',
            'lmd_bulletin_logo_height' => '96',
            'lmd_bulletin_header_padding_y' => '5',
            'lmd_bulletin_header_meta_padding_y' => '1.5',
            'lmd_bulletin_signature_space_height' => '58',
            'lmd_bulletin_parcours_auto' => '1',
            'lmd_bulletin_direction' => 'Direction des Études',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        self::assertFalse($response->isRedirection());
        self::assertSame('18', SettingsHelper::get('lmd_bulletin_font_school_name'));
        self::assertSame('16.5', SettingsHelper::get('lmd_bulletin_font_title'));
        self::assertSame('11', SettingsHelper::get('lmd_bulletin_font_table'));
        self::assertSame('96', SettingsHelper::get('lmd_bulletin_logo_height'));
        self::assertSame('5', SettingsHelper::get('lmd_bulletin_header_padding_y'));
        self::assertSame('1.5', SettingsHelper::get('lmd_bulletin_header_meta_padding_y'));
        self::assertSame('58', SettingsHelper::get('lmd_bulletin_signature_space_height'));
        self::assertSame('Direction des Études', SettingsHelper::get('lmd_bulletin_direction'));
    }

    public function test_yakro_header_scale_is_rejected_outside_safe_bounds(): void
    {
        SettingsHelper::setOrCreate('bulletin_bts1_semester1_weight', '1', 'bulletin');
        SettingsHelper::setOrCreate('bulletin_bts1_semester2_weight', '1', 'bulletin');

        $response = $this->postJson(route('esbtp.bulletins.save-configuration'), [
            'bulletin_header_scale' => '225',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bulletin_header_scale']);
        self::assertNull(SettingsHelper::get('bulletin_header_scale'));
    }
}
