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
use FeedIo\Factory;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use FeedIo\Reader\Result;

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

    public function fetchFeed(?string $source = null)
    {
        if (is_null($source)) {
            $source = SELF::DEFAULT_SOURCE;
        }

        // read a feed
        $result = $this->feedIo->read($source);

        if (!$result) {
            throw new \UnexpectedValueException();
        }

        // reader result
        $readerResult = $this->storeReaderResult($result);

        // feed
        $feed = $this->storeFeed($readerResult->id, $result->getFeed());
        if (1 <= $result->getFeed()->getCategories()->count()) {
            $this->storeFeedCategories($feed, $result->getFeed()->getCategories());
        }

        // items
        $this->storeItems($feed, $result->getFeed());
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

    private function storeItems(Feed $feed, FeedInterface $feedioFeed)
    {
//        $create = [];
//
//        $now = Carbon::now();
//
//        foreach ($feedioFeed as $item) {
//            $create[] = [
//                'feed_id' => $feedId,
//                'title' => $item->getTitle(),
//                'public_id' => $item->getPublicId(),
//                'description' => $item->getDescription(),
//                'last_modified' => $item->getLastModified(),
//                'link' => $item->getLink(),
//                'host' => $item->getHost(),
//                'created_at' => $now,
//                'updated_at' => $now,
//            ];
//        }
//
//        Item::insert($create);

        foreach ($feedioFeed as $item) {
            $feed->items()->updateOrCreate([
                'public_id' => $item->getPublicId(),
            ], [
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
                'last_modified' => $item->getLastModified(),
                'link' => $item->getLink(),
                'host' => $item->getHost(),
            ]);
        }
    }
}
