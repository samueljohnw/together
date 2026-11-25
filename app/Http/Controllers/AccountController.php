<?php

namespace App\Http\Controllers;

use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $closedItems = Item::closed()
            ->with(['latestMessage.author', 'closedBy'])
            ->latest('closed_at')
            ->latest('id')
            ->get();

        return view('account', [
            'closedItems' => ItemResource::collection($closedItems)->resolve($request),
            'members' => User::query()->orderBy('name')->get(),
        ]);
    }
}
