<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppVersionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app_update.android' => [
                'version' => '1.4.0',
                'build' => 14,
                'store_url' => 'https://play.google.com/store/apps/details?id=com.tullysmiths',
            ],
            'app_update.ios' => [
                'version' => '1.4.0',
                'build' => 14,
                'store_url' => 'https://apps.apple.com/app/id1234567890',
            ],
            'app_update.required' => false,
            'app_update.remind_later_hours' => 24,
            'app_update.message' => '',
        ]);
    }

    public function test_it_reports_no_update_when_the_installed_build_is_current(): void
    {
        $this->getJson('/api/app/version?platform=android&version=1.4.0&build=14')
            ->assertOk()
            ->assertJson([
                'update_available' => false,
                'update_required' => false,
                'latest_version' => '1.4.0',
            ]);
    }

    public function test_it_offers_an_optional_update_when_a_newer_version_is_published(): void
    {
        $this->getJson('/api/app/version?platform=android&version=1.3.0&build=13')
            ->assertOk()
            ->assertJson([
                'update_available' => true,
                'update_required' => false,
                'latest_version' => '1.4.0',
                'latest_build' => 14,
                'store_url' => 'https://play.google.com/store/apps/details?id=com.tullysmiths',
                'remind_later_hours' => 24,
            ]);
    }

    public function test_it_uses_the_store_link_for_the_reported_platform(): void
    {
        $this->getJson('/api/app/version?platform=ios&version=1.3.0&build=13')
            ->assertOk()
            ->assertJsonPath('store_url', 'https://apps.apple.com/app/id1234567890');
    }

    public function test_it_offers_a_build_only_release_when_the_version_matches(): void
    {
        config(['app_update.android.build' => 15]);

        $this->getJson('/api/app/version?platform=android&version=1.4.0&build=14')
            ->assertOk()
            ->assertJson([
                'update_available' => true,
                'update_required' => false,
            ]);
    }

    public function test_it_does_not_prompt_a_newer_version_for_a_lower_published_build(): void
    {
        $this->getJson('/api/app/version?platform=android&version=1.5.0&build=10')
            ->assertOk()
            ->assertJson([
                'update_available' => false,
                'update_required' => false,
            ]);
    }

    public function test_it_forces_an_update_when_required_is_enabled(): void
    {
        config(['app_update.required' => true]);

        $this->getJson('/api/app/version?platform=android&version=1.3.0&build=13')
            ->assertOk()
            ->assertJson([
                'update_available' => true,
                'update_required' => true,
            ]);
    }

    public function test_it_uses_the_published_message_when_present(): void
    {
        config(['app_update.message' => 'Rota changes are live - update to see them.']);

        $this->getJson('/api/app/version?platform=android&version=1.3.0&build=13')
            ->assertOk()
            ->assertJsonPath('message', 'Rota changes are live - update to see them.');
    }

    public function test_it_fails_open_when_the_client_sends_no_version(): void
    {
        $this->getJson('/api/app/version')
            ->assertOk()
            ->assertJson([
                'update_available' => false,
                'update_required' => false,
            ]);
    }

    public function test_it_falls_back_to_android_settings_for_an_unknown_platform(): void
    {
        $this->getJson('/api/app/version?platform=windows&version=1.0.0&build=1')
            ->assertOk()
            ->assertJson([
                'update_available' => true,
                'latest_version' => '1.4.0',
            ]);
    }
}
