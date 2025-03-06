<?php

namespace aliirfaan\CitronelExternalService\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Use this trait if we want to cache request and responses for external services
 */
trait ExternalServiceCacheTrait
{
    /**
     * whether to cache any response for the external service
     * Use it as a general switch for all caching for this external service
     *
     * @var bool
     */
    public $shouldCache;

    
    /**
     * endpoint cache config key that overrides the general cache
     *
     * @var mixed
     */
    public $endpointCacheConfigKey;

    /**
     *
     * endpoint
     *
     * @var array
     */
    public $endpoint;
    
    /**
     * Method setIntegrationCacheConfig
     * set caching for endpoint based on service and endpoint cache config
     *
     * @return void
     */
    public function setIntegrationCacheConfig()
    {
        $this->shouldCache = config()->has($this->configKey . '.caching.should_cache') ? config($this->configKey . '.caching.should_cache') : false;

        if ($this->shouldCache) {
            $this->endpointCacheConfigKey = (is_array($this->endpoint) && array_key_exists('caching', $this->endpoint)) ? $this->endpoint['caching']: null;

            if (!is_array($this->endpointCacheConfigKey) && array_key_exists('should_cache', $this->endpointCacheConfigKey)) {
                $this->shouldCache = $this->endpointCacheConfigKey['should_cache'];
            }
        }
    }

    /**
     * Method cacheResponse
     *
     * @param string $cacheConfigKey [explicite description]
     * @param mixed $cacheData [explicite description]
     * @param int $seconds [explicite description]
     *
     * @return void
     */
    public function cacheResponse($cacheData, $seconds = null, $cacheKey = null)
    {
        if (!$this->shouldCache) {
            return false;
        }

        $cacheSeconds = is_array($this->endpointCacheConfigKey) && array_key_exists('cache_seconds', $this->endpointCacheConfigKey) ? $this->endpointCacheConfigKey['cache_seconds'] : null;
        if (!is_null($seconds)) {
            $cacheSeconds = $seconds;
        }

        $endpointCacheKey = is_array($this->endpointCacheConfigKey) && array_key_exists('cache_key', $this->endpointCacheConfigKey) ? $this->endpointCacheConfigKey['cache_key'] : null;
        if (!is_null($cacheKey)) {
            $endpointCacheKey = $cacheKey;
        }

        Cache::put($endpointCacheKey, $cacheData, $cacheSeconds);
    }
    
    /**
     * Method getCachedResponse
     *
     * @param string $cacheConfigKey
     *
     * @return void|mixed
     */
    public function getCachedResponse($cacheKey = null)
    {
        if ($this->shouldCache) {
            $endpointCacheKey = is_array($this->endpointCacheConfigKey) && array_key_exists('cache_key', $this->endpointCacheConfigKey) ? $this->endpointCacheConfigKey['cache_key'] : null;
            if (!is_null($cacheKey)) {
                $endpointCacheKey = $cacheKey;
            }

            if (Cache::has($endpointCacheKey)) {
                return Cache::get($endpointCacheKey);
            }
        }

        return null;
    }
}
