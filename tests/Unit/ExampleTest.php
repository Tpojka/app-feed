<?php

namespace Tests\Unit;

use App\Item;
use App\Service\Feed\FeedService;
use PHPUnit\Framework\Assert;
use Tests\TestCase;
use UnexpectedValueException;

class ExampleTest extends TestCase
{
    /**
     * @test
     */
    public function feed_service_class_has_feedIo_property()
    {
        $feedService = new FeedService();

        $hasFeedio = new \ReflectionObject($feedService);

        $hasFeedio->hasProperty('feedIo');

        $this->assertTrue($hasFeedio->hasProperty('feedIo'));

        Assert::assertObjectHasAttribute('feedIo', $feedService);
    }

    /**
     * If valid xml link is passed test will fail
     *
     * @test
     */
    public function check_if_exception_is_thrown_after_invalid_xml_source_string()
    {
        $this->expectException(UnexpectedValueException::class);

        $feedService = new FeedService();
        $feedService->fetchFeed('https://www.google.com');
    }

    /**
     * @test
     */
    public function default_source_is_set_and_its_a_valid_xml()
    {
        $feedService = new FeedService();
        $feedService->fetchFeed();

        $items = Item::count();

        $this->assertTrue(1 <= $items);
    }

    /**
     * @test
     */
    public function return_500_and_error_message_if_rate_is_not_made_through_ajax()
    {
        // already seeded data in method above
        // should be considered seeding and resetting through setUp tearDown

        $response = $this->post('/items/rate', ['item_id' => 1, 'rating' => 5]);

        $this->assertEquals(500, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertEquals('Server error.', $content['error']);
    }
}
