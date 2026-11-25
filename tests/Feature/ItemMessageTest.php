<?php

use App\Jobs\SendActivityPushNotifications;
use App\Models\Item;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

test('a member replies to an item', function () {
    Bus::fake();
    $this->freezeSecond();
    $user = User::factory()->create();
    $item = Item::factory()->create(['last_activity_at' => now()->subDay()]);

    $this->actingAs($user)
        ->postJson(route('items.messages.store', $item), ['body' => 'I can grab samples after work'])
        ->assertCreated()
        ->assertJsonPath('data.0.body', 'I can grab samples after work')
        ->assertJsonPath('data.0.author', 'You')
        ->assertJsonPath('data.0.is_mine', true);

    $message = Message::sole();
    expect($message->item->is($item))->toBeTrue()
        ->and($message->author->is($user))->toBeTrue()
        ->and($item->fresh()->last_activity_at->equalTo(now()))->toBeTrue();

    Bus::assertDispatchedAfterResponse(SendActivityPushNotifications::class, fn ($job) => $job->actor->is($user));
});

test('a member attaches files and each becomes its own message', function () {
    Bus::fake();
    Storage::fake();
    $item = Item::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('items.messages.store', $item), [
            'attachments' => [
                UploadedFile::fake()->create('quote.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->image('swatch.png'),
            ],
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.attachment.name', 'quote.pdf')
        ->assertJsonPath('data.1.attachment.name', 'swatch.png');

    $messages = Message::orderBy('id')->get();
    expect($messages)->toHaveCount(2);
    $messages->each(fn (Message $message) => Storage::assertExists($message->attachment_path));
});

test('a message needs text or a file', function () {
    $item = Item::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('items.messages.store', $item), ['body' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body', 'attachments']);
});

test('attachments larger than 20 MB are rejected', function () {
    Storage::fake();
    $item = Item::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('items.messages.store', $item), [
            'attachments' => [UploadedFile::fake()->create('video.mov', 20481)],
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attachments.0' => 'Each file must be 20 MB or smaller.']);

    expect(Message::count())->toBe(0);
});

test('a closed item does not accept replies', function () {
    $item = Item::factory()->closed()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('items.messages.store', $item), ['body' => 'Hello?'])
        ->assertConflict();

    expect(Message::count())->toBe(0);
});

test('members download an attachment under its original name', function () {
    Storage::fake();
    Storage::put('attachments/abc123.pdf', 'pdf-contents');
    $message = Message::factory()->withAttachment('attachments/abc123.pdf', 'Insurance form.pdf')->create();

    $this->actingAs(User::factory()->create())
        ->get(route('messages.attachment', $message))
        ->assertOk()
        ->assertDownload('Insurance form.pdf');
});

test('a message without an attachment has nothing to download', function () {
    $message = Message::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('messages.attachment', $message))
        ->assertNotFound();
});

test('guests cannot download attachments', function () {
    $message = Message::factory()->withAttachment()->create();

    $this->get(route('messages.attachment', $message))->assertRedirect(route('login'));
});

test('an uploaded image gets an inline preview link and other files do not', function () {
    Bus::fake();
    Storage::fake();
    $item = Item::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('items.messages.store', $item), [
            'attachments' => [
                UploadedFile::fake()->image('swatch.png'),
                UploadedFile::fake()->create('quote.pdf', 120, 'application/pdf'),
            ],
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.0.attachment.preview_url', route('messages.attachment.preview', Message::where('attachment_name', 'swatch.png')->sole()))
        ->assertJsonPath('data.1.attachment.preview_url', null);
});

test('an image attachment is shown inline', function () {
    Storage::fake();
    $path = UploadedFile::fake()->image('swatch.png')->store('attachments');
    $message = Message::factory()->withAttachment($path, 'swatch.png')->create(['attachment_mime' => 'image/png']);

    $this->actingAs(User::factory()->create())
        ->get(route('messages.attachment.preview', $message))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=swatch.png');
});

test('a file that only claims to be an image is not shown inline', function () {
    Storage::fake();
    Storage::put('attachments/fake.png', '<script>alert(1)</script>');
    $message = Message::factory()->withAttachment('attachments/fake.png', 'fake.png')->create(['attachment_mime' => 'image/png']);

    $this->actingAs(User::factory()->create())
        ->get(route('messages.attachment.preview', $message))
        ->assertNotFound();
});

test('non-image attachments have no inline preview', function () {
    Storage::fake();
    Storage::put('attachments/quote.pdf', 'pdf-contents');
    $message = Message::factory()->withAttachment('attachments/quote.pdf', 'quote.pdf')->create();

    $this->actingAs(User::factory()->create())
        ->get(route('messages.attachment.preview', $message))
        ->assertNotFound();
});

test('an uploaded video is marked as playable media', function () {
    Bus::fake();
    Storage::fake();
    $item = Item::factory()->create();
    $mp4 = UploadedFile::fake()->createWithContent('clip.mp4', "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 64));

    $this->actingAs(User::factory()->create())
        ->post(route('items.messages.store', $item), ['attachments' => [$mp4]], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.0.attachment.media', 'video')
        ;
});

test('a video attachment streams inline and supports seeking', function () {
    Storage::fake();
    $contents = "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x01", 200);
    Storage::put('attachments/clip.mp4', $contents);
    $message = Message::factory()->withAttachment('attachments/clip.mp4', 'clip.mp4')->create(['attachment_mime' => 'video/mp4']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('messages.attachment.preview', $message), ['Range' => 'bytes=0-9'])
        ->assertStatus(206)
        ->assertHeader('Content-Type', 'video/mp4')
        ->assertHeader('Content-Range', 'bytes 0-9/'.strlen($contents));

    expect($response->streamedContent())->toBe(substr($contents, 0, 10));
});
