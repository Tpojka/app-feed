<?php

namespace App\Http\Controllers;

use App\Http\Requests\Item\ItemRateRequest;
use App\Http\Requests\Item\ItemStoreRequest;
use App\Item;
use App\Service\Feed\FeedService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
     */
    public function index(Request $request)
    {
        try {
            $getPage = (int)$request->get('page');

            if ((int)$getPage > (int)Cache::tags(['items_pagination'])->get('items:pagination:total_pages')
                || 0 === (int)$getPage) {

                $getPage = 1;
            }

            $items = Cache::tags(['items_pagination'])->get('items:pagination:get_page_' . (string)$getPage);

            if (!$items) {
                $items = $this->fetchItems($getPage);
            }
        } catch (Throwable $t) {
            $items = new Collection();
        } finally {
            return view('item.index', compact('items'));
        }
    }

    /**
     * @param int $getPage
     * @param string|null $source
     * @return LengthAwarePaginator
     * @throws Throwable
     */
    private function fetchItems(int $getPage = 1, ?string $source = null): ?LengthAwarePaginator
    {
        try {
            // cache setting
            $this->feedService->fetchFeed($getPage);
            $return = Cache::tags(['items_pagination'])->get('items:pagination:get_page_' . (string)$getPage);
        } catch (Throwable $t) {
            throw $t;
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
     * @param ItemStoreRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(ItemStoreRequest $request)
    {
        try {
            Cache::tags('items_pagination')->flush();
            $this->feedService->fetchFeed(1, $request->input('xml_link'));
            return redirect()->route('items.index');
        } catch (Throwable $t) {
            Log::error(formatErrorLine($t));
            return redirect()->back()->withErrors(['error' => 'Something went wrong, please try again.']);
        }
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
     * @throws Throwable
     * @deprecated
     */
    public function fetch(Request $request): JsonResponse
    {
        $return = response()->json(['error' => 'Server error.'], 500);
        try {
            if (!$request->ajax()) {
                throw new BadRequestException('Method Not Allowed', 405);
            }
            // no items so ?page=1
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
