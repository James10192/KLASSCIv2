<?php

namespace Tests\Unit\Routes;

use PHPUnit\Framework\TestCase;

class MessageLegacySansRouteTest extends TestCase
{
    public function test_message_controller_n_est_pas_route(): void
    {
        $routes = file_get_contents(__DIR__.'/../../../routes/web.php');

        $this->assertStringNotContainsString(
            'MessageController',
            $routes,
            'Le Message legacy ne doit pas reprendre une route. Le chat vit sous ChatController /messages.'
        );
    }
}
