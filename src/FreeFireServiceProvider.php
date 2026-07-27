<?php
declare(strict_types=1);
namespace Refatbd\LaravelFreeFire;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Refatbd\FreeFire\Cache\CacheStoreInterface;
use Refatbd\FreeFire\Credentials\BundledCredentialProvider;
use Refatbd\FreeFire\Credentials\ChainCredentialProvider;
use Refatbd\FreeFire\Credentials\CredentialProviderInterface;
use Refatbd\FreeFire\Credentials\EnvironmentCredentialProvider;
use Refatbd\FreeFire\Exception\ConfigurationException;
use Refatbd\FreeFire\FreeFireClient;
use Refatbd\FreeFire\Http\HttpTransportInterface;
use Refatbd\FreeFire\Http\StreamHttpTransport;
use Refatbd\FreeFire\Media\AstcencProcessDecoder;
use Refatbd\FreeFire\Media\FontResolver;
use Refatbd\FreeFire\Media\GdPlayerMediaRenderer;
use Refatbd\FreeFire\Media\MediaService;
use Refatbd\FreeFire\Media\OfficialAssetDownloader;
use Refatbd\FreeFire\Media\OfficialAssetPolicy;
use Refatbd\FreeFire\Media\OfficialImageLoader;
use Refatbd\FreeFire\Media\PlayerMediaRendererInterface;
use Refatbd\FreeFire\Player\GoogleProtobufPlayerResponseDecoder;
use Refatbd\FreeFire\Player\PlayerResponseDecoderInterface;
use Refatbd\FreeFire\Protocol\BuiltInProtocolProfiles;
use Refatbd\FreeFire\Protocol\ProtocolProfileInterface;
use Refatbd\FreeFire\Protocol\ProtocolProfileRegistry;
use Refatbd\FreeFire\Token\TokenManager;
use Refatbd\LaravelFreeFire\Cache\LaravelCacheStore;
use Refatbd\LaravelFreeFire\Console\MediaCheckCommand;
use Refatbd\LaravelFreeFire\Console\RefreshTokensCommand;

final class FreeFireServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/freefire.php', 'freefire');

        $this->app->singleton(ProtocolProfileRegistry::class, function ($app) {
            $configured = array_replace(
                BuiltInProtocolProfiles::classes(),
                (array) config('freefire.profiles', []),
            );
            $profiles = [];
            foreach ($configured as $class) {
                if (!is_string($class) || $class === '') {
                    throw new ConfigurationException('Every configured Free Fire protocol profile must be a class name.');
                }
                $profile = $app->make($class);
                if (!$profile instanceof ProtocolProfileInterface) {
                    throw new ConfigurationException("Configured protocol profile {$class} is invalid.");
                }
                $profiles[] = $profile;
            }
            return new ProtocolProfileRegistry($profiles);
        });
        $this->app->singleton(ProtocolProfileInterface::class, function ($app) {
            return $app->make(ProtocolProfileRegistry::class)->get(
                (string) config('freefire.protocol', 'OB54')
            );
        });
        $this->app->singleton(HttpTransportInterface::class, fn () => new StreamHttpTransport());
        $this->app->singleton(CredentialProviderInterface::class, fn () => new ChainCredentialProvider([
            new EnvironmentCredentialProvider(),
            new BundledCredentialProvider(),
        ]));
        $this->app->singleton(CacheStoreInterface::class, function ($app) {
            $repository = $app['cache']->store(config('freefire.cache_store'));
            return new LaravelCacheStore($repository);
        });
        $this->app->singleton(PlayerResponseDecoderInterface::class, function ($app) {
            $profile = $app->make(ProtocolProfileInterface::class);
            return new GoogleProtobufPlayerResponseDecoder($profile->playerResponseMessageClass());
        });
        $this->app->singleton(PlayerMediaRendererInterface::class, function ($app) {
            $decoder = new AstcencProcessDecoder((string) config('freefire.media.astcenc_binary', 'astcenc'));
            $configuredBases = array_values(array_filter((array) config('freefire.media.asset_bases', [])));
            $policy = new OfficialAssetPolicy($configuredBases ?: null);
            $loader = new OfficialImageLoader(
                new OfficialAssetDownloader(
                    policy: $policy,
                    cache: $app->make(CacheStoreInterface::class),
                    cacheTtl: (int) config('freefire.media.asset_cache_ttl', 21600),
                    cacheNamespace: $app->make(ProtocolProfileInterface::class)->obVersion(),
                ),
                $decoder,
                (string) config('freefire.media.temporary_directory', storage_path('app/freefire/tmp')),
            );
            return new GdPlayerMediaRenderer(
                $loader,
                new FontResolver(array_filter([(string) config('freefire.media.font_path', '')])),
                (int) config('freefire.media.quality', 92),
            );
        });
        $this->app->singleton(MediaService::class, function ($app) {
            return new MediaService(
                $app->make(PlayerMediaRendererInterface::class),
                $app->make(CacheStoreInterface::class),
                $app->make(ProtocolProfileInterface::class)->obVersion(),
                (int) config('freefire.media.cache_ttl', 300),
            );
        });
        $this->app->singleton(TokenManager::class, function ($app) {
            return new TokenManager(
                $app->make(ProtocolProfileInterface::class),
                $app->make(CredentialProviderInterface::class),
                $app->make(HttpTransportInterface::class),
                $app->make(CacheStoreInterface::class),
                logger: $app['log'],
            );
        });
        $this->app->singleton(FreeFireClient::class, function ($app) {
            return new FreeFireClient(
                $app->make(ProtocolProfileInterface::class),
                $app->make(TokenManager::class),
                $app->make(HttpTransportInterface::class),
                $app->make(PlayerResponseDecoderInterface::class),
                $app->make(CacheStoreInterface::class),
                playerCacheTtl: (int) config('freefire.player_cache_ttl', 300),
            );
        });
        $this->app->alias(FreeFireClient::class, 'freefire');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/freefire.php' => config_path('freefire.php')], 'freefire-config');
        RateLimiter::for('freefire', fn ($request) => Limit::perMinute(
            max(1, (int) config('freefire.routes.rate_limit_per_minute', 30))
        )->by(
            $request->user()?->getAuthIdentifier() ?? $request->ip()
        ));
        if (config('freefire.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
        if ($this->app->runningInConsole()) {
            $this->commands([MediaCheckCommand::class, RefreshTokensCommand::class]);
        }
    }
}
