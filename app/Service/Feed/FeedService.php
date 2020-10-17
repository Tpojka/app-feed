<?php
/**
 * Project trivago-feed.local
 * File: FeedService.php
 * Created by: tpojka
 * On: 12/10/2020
 */

namespace App\Service\Feed;

use App\Feed;
use App\Item;
use App\ReaderResult;
use Carbon\Carbon;
use Exception;
use FeedIo\Adapter\Guzzle\Client;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use FeedIo\Reader\Result;
use GuzzleHttp\Client As GuzzleClient;
use GuzzleHttp\HandlerStack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\LaravelCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Psr\Log\NullLogger;
use Throwable;
use UnexpectedValueException;

class FeedService
{
    /**
     * @var FeedIo|null
     */
    private $feedIo = null;

    private CONST DEFAULT_SOURCE = 'https://xkcd.com/atom.xml';
//    private CONST DEFAULT_SOURCE = 'https://rss.dw.com/atom/rss-en-all';

    public function __construct()
    {
        // Create default HandlerStack
        $stack = HandlerStack::create();

        // Add this middleware to the top with `push`
        $stack->push(
            new CacheMiddleware(
                new PrivateCacheStrategy(
                    new LaravelCacheStorage(
                        Cache::store(config('cache.default'))
                    )
                )
            ),
            'cache'
        );

        // Initialize the client with the handler option
        $guzzle = new GuzzleClient(['handler' => $stack]);
        $client = new Client($guzzle);
        $logger = new NullLogger();

        $this->feedIo = new FeedIo($client, $logger);
    }

    /**
     * Method must set cache of items collection and return void
     *
     * @param int $getPage
     * @param string|null $source
     * @return void
     * @throws Throwable
     */
    public function fetchFeed(int $getPage = 1, ?string $source = null): void
    {
        try {
            if (is_null($source)) {
                $source = SELF::DEFAULT_SOURCE;
            }

            if (false === $this->isSourceValidXml($source)) {
                throw new UnexpectedValueException();
            }

            $sourceExists = $this->isSourceAlreadyExistsInDb($source);

            // @todo modified_since field should be used instead of $diff
            $lastItem = Item::whereHas('feed', function (Builder $queryFeed) use ($source) {
                $queryFeed->whereHas('readerResult', function (Builder $queryReaderResult) use ($source) {
                    $queryReaderResult->where(['url' => $source]);
                });
            })->orderBy('last_modified', 'desc')->first();

            $diff = null;

            if ($lastItem) {
                $now = Carbon::now();
                $then = $lastItem->last_modified;
                $diff = $now->diffInSeconds($then);
            }

            // do not make guzzle call if last article is newer than [config cache feed_recent] seconds
            if (!is_null($diff) && config('cache.feed_recent') > $diff) {
                // we already have recent articles
                $itemsPaginate = Item::orderBy('last_modified')->paginate(10, ['*'], 'page', $getPage);
                Cache::tags(['items_pagination'])->put('items:pagination:get_page_' . (string)$getPage, $itemsPaginate, config('cache.feed_recent'));
                Cache::tags(['items_pagination'])->put('items:pagination:total_pages', $itemsPaginate->total(), config('cache.feed_recent'));
            }

            if (isset($itemsPaginate) && $itemsPaginate->isNotEmpty()) {
                // we set the cache that's all matter
                return;
            }

            // read a feed
            if (!is_null($diff)) {
                $result = $this->feedIo->readSince($source, new Carbon("-$diff seconds"));
            } else {
                $result = $this->feedIo->read($source);
            }

            // reader result
            $readerResult = $this->storeReaderResult($result);

            // feed
            $feed = $this->storeFeed($readerResult, $result->getFeed());
            if (1 <= $result->getFeed()->getCategories()->count()) {
                $this->storeFeedCategories($feed, $result->getFeed()->getCategories());
            }

            // items
            $this->storeItems($feed, $result->getFeed());

            $itemsPaginate = Item::orderBy('last_modified')->paginate(10, ['*'], 'page', $getPage);
            Cache::tags(['items_pagination'])->put('items:pagination:get_page_' . (string)$getPage, $itemsPaginate, config('cache.feed_recent'));
            Cache::tags(['items_pagination'])->put('items:pagination:total_pages', $itemsPaginate->total(), config('cache.feed_recent'));

        } catch (Throwable $t) {
            throw $t;
        }
    }

    /**
     * @param Result $feedioReaderResult
     * @return mixed
     */
    private function storeReaderResult(Result $feedioReaderResult)
    {
        $create = [
            'modified_since' => $feedioReaderResult->getModifiedSince(),
            'date' => $feedioReaderResult->getDate(),
            'document_content' => $feedioReaderResult->getResponse()->getBody() ?? null,
        ];

        return ReaderResult::updateOrCreate(['url' => $feedioReaderResult->getUrl()], $create);
    }

    private function storeFeed(ReaderResult $readerResult, FeedInterface $feedioFeed)
    {
        $updateOrCreate = [
            'url' => $feedioFeed->getUrl(),
            'language' => $feedioFeed->getLanguage(),
            'logo' => $feedioFeed->getLogo(),
            'title' => $feedioFeed->getTitle(),
            'public_id' => $feedioFeed->getPublicId(),
            'description' => $feedioFeed->getDescription(),
            'last_modified' => $feedioFeed->getLastModified(),
            'link' => $feedioFeed->getLink(),
            'host' => $feedioFeed->getHost(),
        ];

        return $readerResult->feed()->updateOrCreate($updateOrCreate);
    }

    private function storeFeedCategories(Feed $feed, iterable $feedioFeedCategories)
    {
        foreach ($feedioFeedCategories as $category) {
            $feed->categories()->firstOrCreate([
                'term' => $category->getTerm(),
                'scheme' => $category->getScheme(),
                'label' => $category->getLabel(),
            ]);
        }
    }

    /**
     * @param Feed $feed
     * @param FeedInterface $feedioFeed
     * @todo foreach insert can be expensive, should be discussed of another approach
     */
    private function storeItems(Feed $feed, FeedInterface $feedioFeed)
    {
        foreach ($feedioFeed as $item) {

            $newItem = $feed->items()->updateOrCreate([
                'public_id' => $item->getPublicId(),
            ], [
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
                'last_modified' => $item->getLastModified(),
                'link' => $item->getLink(),
                'host' => $feedioFeed->getHost(),
            ]);

            $authorInsertData = $this->authorInsertData($item);

            if (!empty($authorInsertData)) {
                // author
                $newItem->author()->create($authorInsertData);
            }

            // $newItem is DB Item model
            // $item is ItemInterface object
            $mediaInsertData = $this->mediaInsertData($newItem, $item);

            if (!empty($mediaInsertData)) {
                // media
                $newItem->media()->insert($mediaInsertData);
            }
        }
    }

    /**
     * @param string $source
     * @return bool
     */
    public function isSourceValidXml(string $source): bool
    {
        $return = false;

        try {
            if (false === simplexml_load_file($source)) {
                throw new UnexpectedValueException();
            }
            $return = true;
        } catch (UnexpectedValueException|Exception $e) {
            $return = false;
        } finally {
            return $return;
        }
    }

    public function isSourceAlreadyExistsInDb(string $source)
    {
        return ReaderResult::where(['url' => $source])->exists();
    }

    /**
     * @param ItemInterface $item
     * @return array
     */
    private function authorInsertData(ItemInterface $item): array
    {
        $authorInsertData = [];

        try {
            if (!$item->getAuthor()) {
                throw new Exception('No Item\'s Author.');
            }
            if (!is_null($item->getAuthor()->getName())) {
                $authorInsertData['name'] = $item->getAuthor()->getName();
            }
            if (!is_null($item->getAuthor()->getEmail())) {
                $authorInsertData['email'] = $item->getAuthor()->getEmail();
            }
            if (!is_null($item->getAuthor()->getUri())) {
                $authorInsertData['uri'] = $item->getAuthor()->getUri();
            }
        } catch (Throwable $t) {
            $authorInsertData = [];
        } finally {
            return $authorInsertData;
        }
    }

    /**
     * @param Item $newItem
     * @param ItemInterface $item
     * @return array
     */
    private function mediaInsertData(Item $newItem, ItemInterface $item): array
    {
        $mediaInsertData = [];
        try {
            if (!count($item->getMedias())) {
                throw new Exception('No Item\'s Media.');
            }

            $media = $item->getMedias();

            $now = Carbon::now();

            foreach ($media as $m) {
                $mediaInsertData[] = [
                    'item_id' => $newItem->id,
                    'node_name' => $m->getNodeName(),
                    'type' => $m->getType(),
                    'url' => $m->getUrl(),
                    'length' => $m->getLength(),
                    'title' => $m->getTitle(),
                    'description' => $m->getDescription(),
                    'thumbnail' => $m->getThumbnail(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        } catch (Throwable $t) {
            $mediaInsertData = [];
        } finally {
            return $mediaInsertData;
        }
    }
}
