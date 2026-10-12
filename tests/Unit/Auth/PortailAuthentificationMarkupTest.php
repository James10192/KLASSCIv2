<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;

class PortailAuthentificationMarkupTest extends TestCase
{
    private function template(string $path): string
    {
        return file_get_contents(dirname(__DIR__, 3).'/resources/views/'.$path);
    }

    public function test_password_forms_share_login_design_and_preserve_csrf(): void
    {
        foreach (['auth/passwords/email.blade.php', 'auth/passwords/reset.blade.php', 'auth/change-password.blade.php'] as $path) {
            $blade = $this->template($path);
            $this->assertStringContainsString("@extends('auth.layouts.portal')", $blade);
            $this->assertStringContainsString('@csrf', $blade);
            $this->assertStringNotContainsString("@extends('layouts.app')", $blade);
        }
        $this->assertStringContainsString("@include('auth.partials.login-styles')", $this->template('auth/login.blade.php'));
        $this->assertStringContainsString("@include('auth.partials.login-styles')", $this->template('auth/layouts/portal.blade.php'));
    }

    public function test_guests_do_not_render_internal_shell_on_errors(): void
    {
        foreach (['errors/500.blade.php', 'errors/404.blade.php', 'errors/419.blade.php', 'errors/429.blade.php', 'errors/503.blade.php'] as $path) {
            $blade = $this->template($path);
            $this->assertStringContainsString("@extends('layouts.guest-error')", $blade);
            $this->assertStringNotContainsString('SettingsHelper::get', $blade);
        }
        $this->assertStringContainsString('@guest', $this->template('errors/403.blade.php'));
    }
}
