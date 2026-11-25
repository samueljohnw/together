<?php

use App\Enums\ItemKind;
use App\Jobs\SendActivityPushNotifications;
use App\Models\Item;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

test('the open items list shows open to-dos and threads, most recently active first', function () {
    $older = Item::factory()->todo()->create(['last_activity_at' => now()->subDay()]);
    $newer = Item::factory()->thread()->create(['last_activity_at' => now()]);
    Item::factory()->closed()->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('items.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.0.kind', 'thread')
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('data.1.kind', 'todo');
});

test('the preview names the viewer as "You" and other members by name', function () {
    $viewer = User::factory()->create();
    $partner = User::factory()->create(['name' => 'Sam']);
    $mine = Item::factory()->create();
    $theirs = Item::factory()->create();
    Message::factory()->for($mine)->for($viewer, 'author')->create(['body' => 'On it']);
    Message::factory()->for($theirs)->for($partner, 'author')->create(['body' => 'Thanks']);

    $response = $this->actingAs($viewer)->getJson(route('items.index'))->assertOk();

    expect(collect($response->json('data'))->pluck('preview', 'id')->sortKeys()->all())->toBe([
        $mine->id => 'You: “On it”',
        $theirs->id => 'Sam: “Thanks”',
    ]);
});

test('an attachment preview names the shared file', function () {
    $item = Item::factory()->create();
    $partner = User::factory()->create(['name' => 'Sam']);
    Message::factory()->for($item)->for($partner, 'author')->withAttachment('attachments/quote.pdf', 'quote.pdf')->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('items.index'))
        ->assertJsonPath('data.0.preview', 'Sam shared a file: quote.pdf');
});

test('a member creates a to-do and the other members are notified', function (string $kind) {
    Bus::fake();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('items.store'), ['kind' => $kind, 'title' => 'Call the plumber'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Call the plumber')
        ->assertJsonPath('data.kind', $kind);

    $item = Item::sole();
    expect($item->kind)->toBe(ItemKind::from($kind))
        ->and($item->creator->is($user))->toBeTrue()
        ->and($item->isClosed())->toBeFalse();

    Bus::assertDispatchedAfterResponse(SendActivityPushNotifications::class, fn ($job) => $job->actor->is($user));
})->with(['to-do' => 'todo', 'thread' => 'thread']);

test('creating an item validates the kind and title', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('items.store'), ['kind' => 'reminder', 'title' => str_repeat('a', 121)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['kind', 'title']);

    expect(Item::count())->toBe(0);
});

test('an item shows its full conversation in order', function () {
    $item = Item::factory()->create();
    $first = Message::factory()->for($item)->create(['created_at' => now()->subMinutes(5)]);
    $second = Message::factory()->for($item)->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('items.show', $item))
        ->assertOk()
        ->assertJsonPath('data.messages.0.id', $first->id)
        ->assertJsonPath('data.messages.1.id', $second->id);
});

test('the workspace page embeds the open items', function () {
    Item::factory()->create(['title' => 'Buy the bathroom paint']);

    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Buy the bathroom paint');
});
