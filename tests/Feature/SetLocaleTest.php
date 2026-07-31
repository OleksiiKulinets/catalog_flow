<?php

namespace Tests\Feature;

use CatFlow\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression guard: a freshly created (not re-fetched) user's in-memory
     * `locale` is null even though the column defaults to 'en' in the
     * database — App::setLocale(null) would otherwise leave the app locale
     * null for the rest of the request, breaking anything downstream that
     * assumes app()->getLocale() is always a string (e.g. Carbon's
     * ->locale() treats a null argument as a getter call, not a setter).
     */
    public function test_a_freshly_created_user_without_an_in_memory_locale_does_not_break_the_app_locale(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->locale);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertIsString(app()->getLocale());
        $this->assertNotSame('', app()->getLocale());
    }
}
