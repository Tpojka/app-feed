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
use FeedIo\Factory;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use FeedIo\Reader\Result;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Throwable;
use UnexpectedValueException;

class FeedService
{
    /**
     * @var FeedIo|null
     */
    private $feedIo = null;

//    private CONST DEFAULT_SOURCE = 'https://xkcd.com/atom.xml';
    private CONST DEFAULT_SOURCE = 'https://rss.dw.com/atom/rss-en-all';

    public function __construct()
    {
        // create a simple FeedIo instance
        $this->feedIo = Factory::create()->getFeedIo();
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

            $lastItem = Item::query()->when($sourceExists, function (Builder $query) use ($source) {
                $query->where(['feed.reader_result' => $source]);
            })->orderBy('last_modified', 'desc')->first();

            $diff = 7776000;// last 90 days

            if ($lastItem) {
                $now = Carbon::now();
                $then = $lastItem->last_modified;
                $diff = $now->diffInSeconds($then);
            }

            // @todo this hard-coded value should go to config file
            if (7200 > $diff) {
                $itemsPaginate = Item::paginate(10, ['*'], 'page', $getPage);
                Cache::store('file')->put('items:index:get_page_' . (string)$getPage, $itemsPaginate);
                Cache::store('file')->put('items:pagination:total_pages', $itemsPaginate->total());
            }

            if (isset($itemsPaginate) && $itemsPaginate->isNotEmpty()) {
                // we set the cache that's all matter
                return;
            }

            // read a feed
            $result = $this->feedIo->readSince($source, new Carbon("-$diff seconds"));

            // reader result
            $readerResult = $this->storeReaderResult($result);

            // feed
            $feed = $this->storeFeed($readerResult->id, $result->getFeed());
            if (1 <= $result->getFeed()->getCategories()->count()) {
                $this->storeFeedCategories($feed, $result->getFeed()->getCategories());
            }

            // items
            $this->storeItems($feed, $result->getFeed());

            $itemsPaginate = Item::paginate(10);

            Cache::store('file')->put('items:index:get_page_' . (string)$getPage, $itemsPaginate);
            Cache::store('file')->put('items:pagination:total_pages', $itemsPaginate->total());

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
            'url' => $feedioReaderResult->getUrl(),
        ];

        return ReaderResult::create($create);
    }

    private function storeFeed($readerResultId, FeedInterface $feedioFeed)
    {
        $create = [
            'reader_result_id' => $readerResultId,
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

        return Feed::create($create);
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

            $mediaInsertData = $this->mediaInsertData($item);

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
     * @param ItemInterface $item
     * @return array
     */
    private function mediaInsertData(ItemInterface $item): array
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
