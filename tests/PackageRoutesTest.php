<?php
declare(strict_types=1);

namespace Refatbd\LaravelFreeFire\Tests;

final class PackageRoutesTest extends TestCase
{
    public function test_health_endpoint_reports_protocol_without_exposing_credentials(): void
    {
        $response = $this->getJson('/api/free-fire/v1/health');

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('protocol', 'OB55')
            ->assertJsonPath('credentials', 'configured-server-side')
            ->assertJsonMissing(['password'])
            ->assertJsonMissing(['token']);
    }

    public function test_legacy_player_route_requires_uid(): void
    {
        $this->getJson('/player-info?region=BD')
            ->assertStatus(422)
            ->assertJsonPath('error', 'The uid query parameter is required.')
            ->assertJsonPath('code', 'INVALID_INPUT');
    }

    public function test_partial_account_override_reports_configuration_error(): void
    {
        putenv('FREEFIRE_BD_UID=123456789');
        try {
            $this->getJson('/api/free-fire/v1/players/4422076728?region=BD')
                ->assertStatus(503)
                ->assertJsonPath('code', 'CREDENTIAL_CONFIG_ERROR');
        } finally {
            putenv('FREEFIRE_BD_UID');
        }
    }

    public function test_media_route_can_be_disabled_without_contacting_upstream(): void
    {
        config()->set('freefire.media.enabled', false);

        $this->getJson('/api/avatar/avatar_4422076728.webp?region=BD')
            ->assertStatus(503)
            ->assertJsonPath('error', 'Free Fire media rendering is disabled.');
    }

    public function test_named_routes_are_registered(): void
    {
        self::assertTrue(app('router')->has('freefire.player'));
        self::assertTrue(app('router')->has('freefire.player.compat'));
        self::assertTrue(app('router')->has('freefire.avatar.compat'));
        self::assertTrue(app('router')->has('freefire.banner.compat'));
    }

    public function test_player_lookup_command_validates_uid(): void
    {
        $this->artisan('freefire:player', ['uid' => 'invalid'])
            ->expectsOutputToContain('Please provide a valid 5-20 digit numeric UID.')
            ->assertFailed();
    }
}
