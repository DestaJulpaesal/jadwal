<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteAccessSmokeTest extends TestCase
{
    #[DataProvider('protectedUserRoutes')]
    public function test_guest_is_redirected_from_user_pages(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    #[DataProvider('protectedAdminRoutes')]
    public function test_guest_is_redirected_from_admin_pages(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    public static function protectedUserRoutes(): array
    {
        return [
            ['user.dashboard'],
            ['user.calendar'],
            ['user.tasks.index'],
            ['user.habits.index'],
            ['user.goals.index'],
            ['user.money.index'],
            ['user.obligations.index'],
            ['user.loans.index'],
            ['user.reports.index'],
            ['user.search'],
            ['settings.edit'],
            ['profile.edit'],
        ];
    }

    public static function protectedAdminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.users.index'],
            ['admin.categories.index'],
        ];
    }
}
