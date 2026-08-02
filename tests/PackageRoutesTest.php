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
            ->assertJsonPath('protocol', 'OB54')
            ->assertJsonPath('credentials', 'configured-server-side')
            ->assertJsonMissing(['password'])
            ->assertJsonMissing(['token']);
    }

    public function test_legacy_player_route_requires_uid(): void
    {
        $this->getJson('/player-info?region=BD')
            ->assertStatus(422)
            ->assertJsonPath('error', 'The uid query parameter is required.');
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
}
