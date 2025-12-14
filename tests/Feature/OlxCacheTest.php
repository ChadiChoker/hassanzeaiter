<?php

namespace Tests\Feature;

use App\Services\OlxApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OlxCacheTest extends TestCase
{
    use RefreshDatabase;

    private OlxApiService $olxApiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->olxApiService = new OlxApiService();

        if ($this->supportsTags()) {
            Cache::tags(['olx_api'])->flush();
        } else {
            Cache::flush();
        }
    }

    private function supportsTags(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached']);
    }

    public function test_categories_are_cached(): void
    {
        Http::fake([
            'https://www.olx.com.lb/api/categories*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Test Category'],
                ],
            ], 200),
        ]);

        $firstResult = $this->olxApiService->fetchCategories();

        $cache = $this->supportsTags() ? Cache::tags(['olx_api']) : Cache::store();
        $this->assertTrue($cache->has('olx_categories'));

        Http::fake();
        $secondResult = $this->olxApiService->fetchCategories();

        $this->assertEquals($firstResult, $secondResult);
        $this->assertCount(1, $secondResult);
    }

    public function test_category_fields_are_cached(): void
    {
        $categoryId = '23';

        Http::fake([
            'https://www.olx.com.lb/api/categoryFields*' => Http::response([
                'data' => [
                    $categoryId => [
                        ['id' => 1, 'key' => 'test_field', 'type' => 'text'],
                    ],
                ],
            ], 200),
        ]);

        $fields = $this->olxApiService->fetchCategoryFields($categoryId);

        $cacheKey = "olx_category_fields_{$categoryId}";
        $cache = $this->supportsTags() ? Cache::tags(['olx_api']) : Cache::store();
        $this->assertTrue($cache->has($cacheKey));

        $this->assertArrayHasKey($categoryId, $fields);
    }

    public function test_clear_cache_removes_all_olx_caches(): void
    {
        \App\Models\Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'external_id' => '23',
        ]);

        Http::fake([
            'https://www.olx.com.lb/api/categories*' => Http::response([
                'data' => [['id' => 1, 'name' => 'Test']],
            ], 200),
            'https://www.olx.com.lb/api/categoryFields*' => Http::response([
                'data' => ['23' => [['id' => 1, 'key' => 'test']]],
            ], 200),
        ]);

        $this->olxApiService->fetchCategories();
        $this->olxApiService->fetchCategoryFields('23');

        $cache = $this->supportsTags() ? Cache::tags(['olx_api']) : Cache::store();
        $this->assertTrue($cache->has('olx_categories'));
        $this->assertTrue($cache->has('olx_category_fields_23'));

        $result = $this->olxApiService->clearCache();

        $this->assertTrue($result);
        $this->assertFalse($cache->has('olx_categories'));
        $this->assertFalse($cache->has('olx_category_fields_23'));
    }

    public function test_get_cache_stats_returns_correct_data(): void
    {
        $stats = $this->olxApiService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
        $this->assertArrayHasKey('cache_driver', $stats);
        $this->assertArrayHasKey('cache_ttl_hours', $stats);
        $this->assertArrayHasKey('categories_cached', $stats);
        $this->assertArrayHasKey('cache_tag', $stats);

        $this->assertTrue($stats['cache_enabled']);
        $this->assertEquals(24, $stats['cache_ttl_hours']);
        $this->assertEquals('olx_api', $stats['cache_tag']);
    }

    public function test_artisan_command_clears_cache(): void
    {
        Http::fake([
            'https://www.olx.com.lb/api/categories*' => Http::response([
                'data' => [['id' => 1]],
            ], 200),
        ]);

        $this->olxApiService->fetchCategories();

        $cache = $this->supportsTags() ? Cache::tags(['olx_api']) : Cache::store();
        $this->assertTrue($cache->has('olx_categories'));

        $exitCode = Artisan::call('olx:clear-cache', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
        $this->assertFalse($cache->has('olx_categories'));
    }

    public function test_artisan_command_shows_stats(): void
    {
        $exitCode = Artisan::call('olx:clear-cache', [
            '--stats' => true,
            '--force' => true,
        ]);

        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Cache Statistics', $output);
        $this->assertStringContainsString('Cache Driver', $output);
        $this->assertStringContainsString('Cache TTL', $output);
    }

    public function test_health_check_returns_true_when_api_is_accessible(): void
    {
        Http::fake([
            'https://www.olx.com.lb/api/categories*' => Http::response([], 200),
        ]);

        $isHealthy = $this->olxApiService->healthCheck();

        $this->assertTrue($isHealthy);
    }

    public function test_health_check_returns_false_when_api_is_down(): void
    {
        Http::fake([
            'https://www.olx.com.lb/api/categories*' => Http::response([], 500),
        ]);

        $isHealthy = $this->olxApiService->healthCheck();

        $this->assertFalse($isHealthy);
    }
}
