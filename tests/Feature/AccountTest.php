<?php

use App\Models\Item;
use App\Models\User;

test('the account page lists closed items and household members', function () {
    $viewer = User::factory()->create(['name' => 'Alex']);
    User::factory()->create(['name' => 'Sam']);
    Item::factory()->closed($viewer)->create(['title' => 'Replace the entryway light']);
    Item::factory()->create(['title' => 'Still open']);

    $this->actingAs($viewer)
        ->get(route('account'))
        ->assertOk()
        ->assertViewHas('closedItems', fn (array $items) => count($items) === 1
            && $items[0]['title'] === 'Replace the entryway light'
            && $items[0]['closed_by'] === 'You')
        ->assertSeeInOrder(['Alex', '(you)', 'Sam']);
});
