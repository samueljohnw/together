<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\ItemResource;
use App\Jobs\SendActivityPushNotifications;
use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class ItemController extends Controller
{
    /**
     * The workspace page, rendered with the open items so the lists appear without waiting for a request.
     */
    public function workspace(Request $request): View
    {
        return view('welcome', [
            'openItems' => ItemResource::collection($this->openItems())->resolve($request),
        ]);
    }

    /**
     * The open items, polled by the workspace to pick up the other member's changes.
     */
    public function index(): AnonymousResourceCollection
    {
        return ItemResource::collection($this->openItems());
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = new Item([
            'kind' => $request->validated('kind'),
            'title' => $request->validated('title'),
            'last_activity_at' => now(),
        ]);
        $item->creator()->associate($request->user());
        $item->save();

        SendActivityPushNotifications::dispatchAfterResponse($request->user());

        return ItemResource::make($item->load(['latestMessage.author', 'closedBy']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Item $item): ItemResource
    {
        return ItemResource::make($item->load(['latestMessage.author', 'closedBy', 'messages.author']));
    }

    /**
     * @return Collection<int, Item>
     */
    private function openItems(): Collection
    {
        return Item::open()
            ->with(['latestMessage.author', 'closedBy'])
            ->latest('last_activity_at')
            ->latest('id')
            ->get();
    }
}
