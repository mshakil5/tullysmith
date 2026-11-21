<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /api/app-status is the legacy release endpoint used by builds that were
 * published before /api/app/version existed. It must keep returning the
 * fields those builds read.
 */
class AppStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_legacy_min_version_and_store_link(): void
    {
        AppSetting::create([
            'min_version' => '1.2.0',
            'store_link' => 'https://play.google.com/store/apps/details?id=com.tullysmiths',
        ]);

        $this->getJson('/api/app-status')
            ->assertOk()
            ->assertJson([
                'min_version' => '1.2.0',
                'store_link' => 'https://play.google.com/store/apps/details?id=com.tullysmiths',
            ]);
    }

    public function test_it_returns_empty_strings_when_no_settings_row_exists(): void
    {
        $this->getJson('/api/app-status')
            ->assertOk()
            ->assertJson([
                'min_version' => '',
                'store_link' => '',
            ]);
    }
}
