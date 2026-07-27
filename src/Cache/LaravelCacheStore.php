<?php
declare(strict_types=1);
namespace Refatbd\LaravelFreeFire\Cache;

use Illuminate\Contracts\Cache\Repository;
use Refatbd\FreeFire\Cache\CacheStoreInterface;

final class LaravelCacheStore implements CacheStoreInterface
{
    /** @var array<string, object> */
    private array $locks = [];

    public function __construct(private readonly Repository $cache) {}

    public function get(string $key): mixed { return $this->cache->get($key); }
    public function put(string $key, mixed $value, int $ttlSeconds): void { $this->cache->put($key, $value, $ttlSeconds); }
    public function forget(string $key): void { $this->cache->forget($key); }

    public function acquireLock(string $key, int $ttlSeconds): ?string
    {
        $owner = bin2hex(random_bytes(16));
        $store = method_exists($this->cache, 'getStore') ? $this->cache->getStore() : null;
        if (is_object($store) && method_exists($store, 'lock')) {
            $lock = $store->lock($key, $ttlSeconds, $owner);
            if ($lock->get()) {
                $this->locks[$key.'|'.$owner] = $lock;
                return $owner;
            }
            return null;
        }

        // Portable fallback for cache stores without Laravel's lock contract.
        return $this->cache->add('__lock:'.$key, $owner, $ttlSeconds) ? $owner : null;
    }

    public function releaseLock(string $key, string $owner): void
    {
        $index = $key.'|'.$owner;
        if (isset($this->locks[$index])) {
            $this->locks[$index]->release();
            unset($this->locks[$index]);
            return;
        }
        $cacheKey = '__lock:'.$key;
        if (hash_equals($owner, (string) $this->cache->get($cacheKey, ''))) {
            $this->cache->forget($cacheKey);
        }
    }
}
