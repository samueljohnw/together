<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;

class ClosedItemController extends Controller
{
    /**
     * Close an open to-do or thread.
     */
    public function store(Request $request, Item $item): ItemResource
    {
        if (! $item->isClosed()) {
            $item->close($request->user());
        }

        return ItemResource::make($item->load(['latestMessage.author', 'closedBy']));
    }

    /**
     * Re-open a closed to-do or thread.
     */
    public function destroy(Item $item): ItemResource
    {
        if ($item->isClosed()) {
            $item->reopen();
        }

        return ItemResource::make($item->load(['latestMessage.author', 'closedBy']));
    }
}
