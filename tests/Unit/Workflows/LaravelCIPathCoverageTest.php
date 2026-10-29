<?php

namespace Tests\Unit\Workflows;

use PHPUnit\Framework\TestCase;

class LaravelCIPathCoverageTest extends TestCase
{
    public function test_each_artisan_test_invocation_has_at_most_one_positional_test_file(): void
    {
        $workflow = file_get_contents(dirname(__DIR__, 3).'/.github/workflows/laravel-ci.yml');
        $this->assertNotFalse($workflow);

        preg_match_all('/^\\s*(?:run:\\s*)?php artisan test ([^\\r\\n]+)$/m', $workflow, $commands);
        $this->assertGreaterThanOrEqual(20, count($commands[1]), 'Les commandes de la CI doivent etre inspectees.');

        foreach ($commands[1] as $arguments) {
            preg_match_all('~tests/[^\\s]+?\\.php\\b~', $arguments, $paths);
            $this->assertLessThanOrEqual(
                1,
                count($paths[0]),
                'PHPUnit ignore les chemins positionnels suivants : '.$arguments
            );
        }
    }
}
