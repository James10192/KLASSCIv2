<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class AuSelectSetOptionsTest extends TestCase
{
    public function test_au_select_exposes_set_options(): void
    {
        $source = file_get_contents(resource_path('views/components/au-select.blade.php'));

        $this->assertStringContainsString("setOptions(items, selectedValue = '')", $source);
        $this->assertStringContainsString("native.innerHTML = ''", $source);
    }

    public function test_payment_create_uses_set_options_instead_of_native_options(): void
    {
        $source = file_get_contents(resource_path('views/esbtp/paiements/create.blade.php'));

        $this->assertStringContainsString('setOptions(', $source);
        $this->assertStringContainsString("route('esbtp.api.etudiants.search')", $source);
        $this->assertStringNotContainsString('new Option(', $source);
        $this->assertStringNotContainsString('$inscriptionSelect', $source);
        $this->assertStringNotContainsString('pc-select-field', $source);
        $this->assertStringNotContainsString('->limit(10)', $source);
    }
}
