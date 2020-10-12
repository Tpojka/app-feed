<?php

namespace App\Http\Controllers;

use App\Item;
use App\Service\Feed\FeedService;
use FeedIo\Reader\Result;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ItemController extends Controller
{
    private $feedService;

    public function __construct()
    {
        $this->feedService = new FeedService();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|Response|View
     */
    public function index()
    {
        $items = Item::paginate(10);

        if ($items->isEmpty()) {
            // good place for Job Queue
            $this->feedService->fetchFeed();
        }

        return view('item.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Item $item
     * @return Response
     */
    public function show(Item $item)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Item $item
     * @return Response
     */
    public function edit(Item $item)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Item $item
     * @return Response
     */
    public function update(Request $request, Item $item)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Item $item
     * @return Response
     */
    public function destroy(Item $item)
    {
        //
    }


    public function fetch(Request $request)
    {
        $return = response()->json(['error' => 'Server error.'], 500);
        try {
            $this->feedService->fetchFeed();
            $return = response()->json(['message' => 'Success.'], 200);
        } catch (\UnexpectedValueException|\Exception $e) {
            Log::error($e->getMessage());
            $return = response()->json(['error' => $e->getMessage()], $e->getCode());
        } finally {
            return $return;
        }
    }
}
