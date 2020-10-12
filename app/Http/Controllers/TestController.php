<?php

namespace App\Http\Controllers;

use FeedIo\Async\DefaultCallback;
use FeedIo\Factory;
use Illuminate\Log\Logger;
use Psr\Log\NullLogger;

class TestController extends Controller
{
    public function index()
    {
        $sources = [
            'https://xkcd.com/atom.xml',
            'https://rss.dw.com/atom/rss-en-all',
        ];

        $asyncSources = array_map(function ($item) {
            return new \FeedIo\Async\Request($item);
        }, $sources);

        $feedIo = Factory::create()->getFeedIo();

        $result = $feedIo->read($sources[1]);

        dd($result);

        // create a simple FeedIo instance
        $feedIo = Factory::create()->getFeedIo();

        $url = 'https://xkcd.com/atom.xml';

// read a feed
        $result = $feedIo->read($url);

        dd($result);

// or read a feed since a certain date
        $result = $feedIo->readSince($url, new \DateTime('-7 days'));

// get title
        $feedTitle = $result->getFeed()->getTitle();

// iterate through items
        foreach( $result->getFeed() as $item ) {
            echo $item->getTitle();
        }
    }
}
