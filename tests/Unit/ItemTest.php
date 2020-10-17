<?php

namespace Tests\Unit;

use App\Item;
use App\Service\Feed\FeedService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Assert;
use ReflectionObject;
use Tests\TestCase;
use UnexpectedValueException;

class ItemTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('cache:clear');
        Artisan::call('migrate:fresh');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function feed_service_class_has_feedIo_property()
    {
        $feedService = new FeedService();

        $reflectionFeedService = new ReflectionObject($feedService);

        $this->assertTrue($reflectionFeedService->hasProperty('feedIo'));

        Assert::assertObjectHasAttribute('feedIo', $feedService);
    }

    /**
     * If valid xml link is passed test will fail
     *
     * @test
     * @throws \Throwable
     */
    public function check_if_exception_is_thrown_after_invalid_xml_source_string()
    {
        $this->expectException(UnexpectedValueException::class);

        $feedService = new FeedService();
        $feedService->fetchFeed(1,'https://www.google.com');
    }

    /**
     * Helper method
     * @throws \Throwable
     */
    private function fetchFeed(): void
    {
        $feedService = new FeedService();
        $feedService->fetchFeed();
    }

    /**
     * @test
     * @throws \Throwable
     */
    public function default_source_is_set_and_its_a_valid_xml()
    {
        $this->fetchFeed();

        $items = Item::count();

        $this->assertTrue(1 <= $items);
    }

    /**
     * @test
     */
    public function return_500_and_error_message_if_rate_is_not_made_through_ajax()
    {
        $this->fetchFeed();

        $response = $this->post('/items/rate', ['item_id' => 1, 'rating' => 5]);
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Server error.', $content['error']);
    }

    /**
     * @test
     */
    public function items_from_cache_is_instance_of_length_aware_paginator_object()
    {
        $items = Item::orderBy('last_modified')->paginate(10);

        Cache::tags(['items_pagination'])->put('items:pagination:get_page_' .  (string)$items->currentPage(), $items, 3600);

        $itemsFromCache = Cache::tags(['items_pagination'])->get('items:pagination:get_page_1');

        $this->assertTrue($itemsFromCache instanceof LengthAwarePaginator);
    }
}
