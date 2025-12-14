<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OlxApiService
{
    private const CATEGORIES_URL = 'https://www.olx.com.lb/api/categories';
    private const CATEGORY_FIELDS_URL = 'https://www.olx.com.lb/api/categoryFields';
    private const CACHE_TTL = 86400; 
    private const CACHE_TAG = 'olx_api';

    /**
     * Fetch all categories from the OLX API with caching.
     *
     * @return array
     * @throws \Exception
     */
    public function fetchCategories(): array
    {
        $cache = $this->supportsTags() ? Cache::tags([self::CACHE_TAG]) : Cache::store();

        return $cache->remember('olx_categories', self::CACHE_TTL, function () {
            try {
                $response = Http::timeout(30)->get(self::CATEGORIES_URL);

                if (!$response->successful()) {
                    throw new \Exception('Failed to fetch categories from OLX API: ' . $response->status());
                }

                $data = $response->json();

                if (isset($data['data'])) {
                    return $data['data'];
                }

                return $data;
            } catch (\Exception $e) {
                Log::error('OLX API Error (Categories): ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Fetch category fields for a specific category with caching.
     *
     * @param string $categoryExternalId
     * @return array
     * @throws \Exception
     */
    public function fetchCategoryFields(string $categoryExternalId): array
    {
        $cacheKey = "olx_category_fields_{$categoryExternalId}";
        $cache = $this->supportsTags() ? Cache::tags([self::CACHE_TAG]) : Cache::store();

        return $cache->remember($cacheKey, self::CACHE_TTL, function () use ($categoryExternalId) {
            try {
                $response = Http::timeout(30)->get(self::CATEGORY_FIELDS_URL, [
                    'categoryExternalIDs' => $categoryExternalId,
                    'includeWithoutCategory' => 'true',
                    'splitByCategoryIDs' => 'true',
                    'flatChoices' => 'true',
                    'groupChoicesBySection' => 'true',
                    'flat' => 'true',
                ]);

                if (!$response->successful()) {
                    throw new \Exception("Failed to fetch category fields from OLX API: " . $response->status());
                }

                $data = $response->json();

                // The API returns fields organized by category ID
                if (isset($data['data'])) {
                    return $data['data'];
                }

                return $data;
            } catch (\Exception $e) {
                Log::error("OLX API Error (Category Fields for {$categoryExternalId}): " . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Clear all OLX API caches.
     * Uses cache tags if supported (Redis/Memcached), otherwise clears individual keys.
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        try {
            if ($this->supportsTags()) {
                Cache::tags([self::CACHE_TAG])->flush();
            } else {
                Cache::forget('olx_categories');

                $categories = Category::pluck('external_id');
                foreach ($categories as $categoryId) {
                    Cache::forget("olx_category_fields_{$categoryId}");
                }
            }

            Log::info('OLX API cache cleared successfully', [
                'method' => $this->supportsTags() ? 'tags' : 'individual_keys',
                'timestamp' => now()->toISOString(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear OLX API cache: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if the current cache driver supports tagging.
     *
     * @return bool
     */
    private function supportsTags(): bool
    {
        $driver = config('cache.default');
        $supportedDrivers = ['redis', 'memcached'];

        return in_array($driver, $supportedDrivers);
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        $cache = $this->supportsTags() ? Cache::tags([self::CACHE_TAG]) : Cache::store();
        $hasCategoriesCache = $cache->has('olx_categories');

        return [
            'cache_enabled' => true,
            'cache_driver' => config('cache.default'),
            'cache_ttl_hours' => self::CACHE_TTL / 3600,
            'categories_cached' => $hasCategoriesCache,
            'cache_tag' => self::CACHE_TAG,
            'supports_tagging' => $this->supportsTags(),
        ];
    }

    /**
     * Check if the OLX API is accessible.
     *
     * @return bool
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(10)->get(self::CATEGORIES_URL);
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('OLX API health check failed: ' . $e->getMessage());
            return false;
        }
    }
}
