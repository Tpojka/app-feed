<?php
/**
 * Project trivago-feed.local
 * File: FeedService.php
 * Created by: tpojka
 * On: 12/10/2020
 */

namespace App\Service\Feed;

use App\Feed;
use App\ReaderResult;
use Carbon\Carbon;
use Exception;
use FeedIo\Factory;
use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use FeedIo\Reader\Result;
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

    public function fetchFeed(?string $source = null)
    {
        if (is_null($source)) {
            $source = SELF::DEFAULT_SOURCE;
        }

        if (false === $this->isSourceValidXml($source)) {
            throw new UnexpectedValueException();
        }

        // read a feed
        $result = $this->feedIo->read($source);

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
                $newItem->author()->create($authorInsertData);
            }

            $mediaInsertData = $this->mediaInsertData($item);

            if (!empty($mediaInsertData)) {
                $newItem->media()->insert($mediaInsertData);
            }
        }
    }

    /**
     * @param string $source
     * @return bool
     */
    private function isSourceValidXml(string $source): bool
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
