<?php

namespace Tests\Unit\Services;

use App\Services\BulletinMentionResolver;
use PHPUnit\Framework\TestCase;

class BulletinMentionResolverTest extends TestCase
{
    public function test_fourteen_gets_encouragement_and_honor_roll(): void
    {
        $checked = $this->checkedKeys(14.0, null);

        $this->assertContains('encouragement', $checked);
        $this->assertContains('honor_roll', $checked);
        $this->assertNotContains('felicitation', $checked);
    }

    public function test_sixteen_gets_felicitation_and_honor_roll_but_not_encouragement(): void
    {
        $checked = $this->checkedKeys(16.0, null);

        $this->assertContains('felicitation', $checked);
        $this->assertContains('honor_roll', $checked);
        $this->assertNotContains('encouragement', $checked);
    }

    public function test_twelve_gets_honor_roll_only(): void
    {
        $this->assertSame(['honor_roll'], $this->checkedKeys(12.0, null));
    }

    public function test_twenty_is_included_when_max_is_empty(): void
    {
        $checked = $this->checkedKeys(20.0, null);

        $this->assertContains('felicitation', $checked);
        $this->assertContains('honor_roll', $checked);
    }

    public function test_work_warning_range_is_independent(): void
    {
        $this->assertContains('work_warning', $this->checkedKeys(5.0, null));
        $this->assertNotContains('work_warning', $this->checkedKeys(6.0, null));
        $this->assertNotContains('work_warning', $this->checkedKeys(3.5, null));
    }

    public function test_blame_uses_conduct_and_skips_when_missing(): void
    {
        $this->assertContains('conduct_blame', $this->checkedKeys(14.0, 2.0));
        $this->assertNotContains('conduct_blame', $this->checkedKeys(14.0, null));
        $this->assertNotContains('conduct_blame', $this->checkedKeys(14.0, 3.99));
    }

    public function test_normalize_drops_rows_without_label(): void
    {
        $rules = BulletinMentionResolver::normalize([
            ['label' => '', 'min' => 10],
            ['label' => 'Tableau d\'honneur', 'min' => '12', 'max' => '', 'source' => 'moyenne', 'enabled' => '1'],
        ]);

        $this->assertCount(1, $rules);
        $this->assertSame('tableau_d_honneur', $rules[0]['key']);
        $this->assertNull($rules[0]['max']);
    }

    public function test_from_legacy_settings_reads_existing_tenant_thresholds(): void
    {
        $legacy = [
            'bulletin_encouragement_threshold' => '14',
            'bulletin_show_honor_roll' => '1',
        ];

        $rules = BulletinMentionResolver::fromLegacySettings(
            static fn (string $key, mixed $default) => $legacy[$key] ?? $default
        );
        $byKey = array_column($rules, null, 'key');

        $this->assertSame(14.0, $byKey['encouragement']['min']);
        $this->assertTrue($byKey['honor_roll']['enabled']);
    }

    public function test_normalize_treats_missing_enabled_as_off(): void
    {
        $rules = BulletinMentionResolver::normalize([
            ['label' => 'Encouragement', 'min' => 13],
        ]);

        $this->assertFalse($rules[0]['enabled']);
    }

    /**
     * @return list<string>
     */
    private function checkedKeys(float $moyenne, ?float $conduite): array
    {
        return array_values(array_map(
            static fn (array $item) => $item['key'],
            array_filter(
                BulletinMentionResolver::resolve($moyenne, $conduite, BulletinMentionResolver::catalog()),
                static fn (array $item) => $item['checked']
            )
        ));
    }
}
