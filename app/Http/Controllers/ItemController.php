<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\ItemRateRequest;
use App\Item;
use App\Service\Feed\FeedService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Throwable;
use UnexpectedValueException;

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
     * @param Request $request
     * @return Application|Factory|Response|View
     * @throws InvalidArgumentException
     */
    public function index(Request $request)
    {
        $getPage = 1;

        if (1 <= (int)$request->get('page')) {
            $getPage = (int)$request->get('page');
        }

        $items = $this->fetchItems($getPage);

        return view('item.index', compact('items'));
    }

    /**
     * @param int $getPage
     * @param string|null $source
     * @return LengthAwarePaginator
     * @throws InvalidArgumentException
     */
    private function fetchItems(int $getPage = 1, ?string $source = null): ?LengthAwarePaginator
    {
        $return = null;

        try {
            if (!$items = Cache::store('file')->get('items:index:get_page_' . (string)$getPage)) {
                if (!Item::count()) {
                    $this->feedService->fetchFeed();
                }
                $data = Item::paginate(10);
                Cache::store('file')->put('items:index:get_page_' .  (string)$data->currentPage(), $data, 3600);
            }
            $return = Cache::store('file')->get('items:index:get_page_' . (string)$getPage);
        } catch (Exception $e) {
            $return = null;
        } finally {
            return $return;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|Response|View
     */
    public function create()
    {
        return view('item.create');
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function fetch(Request $request): JsonResponse
    {
        $return = response()->json(['error' => 'Server error.'], 500);
        try {
            if (!$request->ajax()) {
                throw new BadRequestException('Method Not Allowed', 405);
            }
            $items = $this->fetchItems();
            if (is_null($items)) {
                throw new Exception('Server error.', 500);
            }
            $return = response()->json(['message' => 'Success.'], 200);
        } catch (UnexpectedValueException|BadRequestException|InvalidArgumentException|Exception $e) {
            Log::error(formatErrorLine($e));
            //@todo this kind of messages shouldn't be provided to frontend in production
            // but generic message only
            // we can go with BadRequestException though
            if ($e instanceof BadRequestException) {
                $return = response()->json(['error' => $e->getMessage()], $e->getCode());
            }
        } finally {
            return $return;
        }
    }

    /**
     * @param ItemRateRequest $request
     * @return JsonResponse
     */
    public function rate(ItemRateRequest $request)
    {
        $return = response()->json(['error' => 'Server error.'], 500);
        try {
            if (!$request->ajax()) {
                throw new Exception('Not an AJAX call.', 405);
            }
            $item = Item::find($request->input('item_id'));
            $item->rate($request->input('rating'));
            $return = response()->json(['item_rating_average' => $item->avg_rating], 200);
        } catch (Throwable $t) {
            Log::error(formatErrorLine($t));
            $return = response()->json(['error' => 'Server error.'], 500);
        } finally {
            return $return;
        }
    }
}
