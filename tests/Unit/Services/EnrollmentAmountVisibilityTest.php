<?php

namespace Tests\Unit\Services;

use App\Services\EnrollmentAmountVisibility;
use Tests\TestCase;

class EnrollmentAmountVisibilityTest extends TestCase
{
    public function test_user_without_finance_cannot_see_amounts(): void
    {
        $user = new class {
            public function can(string $permission): bool
            {
                return $permission === 'identity.enrollment_officer';
            }

            public function hasAnyPermission(array $permissions): bool
            {
                return false;
            }
        };

        $visibility = new EnrollmentAmountVisibility();

        $this->assertTrue($visibility->hideAmounts($user));
    }

    public function test_finance_user_can_see_amounts(): void
    {
        $user = new class {
            public function can(string $permission): bool
            {
                return false;
            }

            public function hasAnyPermission(array $permissions): bool
            {
                return in_array('paiements.view', $permissions, true);
            }
        };

        $visibility = new EnrollmentAmountVisibility();

        $this->assertFalse($visibility->hideAmounts($user));
    }
}
