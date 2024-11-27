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
     * @var string
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
            $this->endpointCacheConfigKey = config()->has($this->configKey . '.web_service.endpoints.' . $this->endpoint . '.caching') ? config($this->configKey . '.web_service.endpoints.' . $this->endpoint . '.caching') : null;

            if (!is_null($this->endpointCacheConfigKey)) {
                $this->shouldCache = config($this->endpointCacheConfigKey . '.should_cache');
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

        $cacheSeconds = config($this->endpointCacheConfigKey . '.cache_seconds');
        if (!is_null($seconds)) {
            $cacheSeconds = $seconds;
        }

        $endpointCacheKey = config($this->endpointCacheConfigKey . '.cache_key');
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
            $endpointCacheKey = config($this->endpointCacheConfigKey . '.cache_key');
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
