<?php

use App\Models\Item;
use App\Models\User;

test('a member closes an open item', function () {
    $user = User::factory()->create();
    $item = Item::factory()->create();

    $this->actingAs($user)
        ->postJson(route('closed-items.store', $item))
        ->assertOk()
        ->assertJsonPath('data.closed_by', 'You');

    $item->refresh();
    expect($item->isClosed())->toBeTrue()
        ->and($item->closedBy->is($user))->toBeTrue();
});

test('closing an already closed item keeps the original closer', function () {
    $closer = User::factory()->create();
    $item = Item::factory()->closed($closer)->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('closed-items.store', $item))
        ->assertOk();

    expect($item->fresh()->closedBy->is($closer))->toBeTrue();
});

test('a member re-opens a closed item and it moves to the top of the workspace', function () {
    $this->freezeSecond();
    $item = Item::factory()->closed()->create(['last_activity_at' => now()->subWeek()]);

    $this->actingAs(User::factory()->create())
        ->deleteJson(route('closed-items.destroy', $item))
        ->assertOk()
        ->assertJsonPath('data.closed_at', null);

    $item->refresh();
    expect($item->isClosed())->toBeFalse()
        ->and($item->closed_by_id)->toBeNull()
        ->and($item->last_activity_at->equalTo(now()))->toBeTrue();
});
