<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemMessageRequest;
use App\Http\Resources\MessageResource;
use App\Jobs\SendActivityPushNotifications;
use App\Models\Item;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ItemMessageController extends Controller
{
    /**
     * Post a text message, or one message per attached file, to an open item.
     */
    public function store(StoreItemMessageRequest $request, Item $item): JsonResponse
    {
        abort_if($item->isClosed(), Response::HTTP_CONFLICT, 'This item is closed. Re-open it to reply.');

        $author = $request->user();

        $messages = DB::transaction(function () use ($request, $item, $author) {
            $messages = collect();

            if (filled($request->validated('body'))) {
                $messages->push($this->createMessage($item, $author, ['body' => $request->validated('body')]));
            }

            /** @var UploadedFile $file */
            foreach ($request->file('attachments', []) as $file) {
                $messages->push($this->createMessage($item, $author, [
                    'attachment_path' => $file->store('attachments'),
                    'attachment_name' => $file->getClientOriginalName(),
                    'attachment_mime' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'attachment_size' => $file->getSize(),
                ]));
            }

            $item->forceFill(['last_activity_at' => now()])->save();

            return $messages;
        });

        SendActivityPushNotifications::dispatchAfterResponse($author);

        return MessageResource::collection($messages)->response()->setStatusCode(201);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMessage(Item $item, User $author, array $attributes): Message
    {
        $message = $item->messages()->make($attributes);
        $message->author()->associate($author);
        $message->save();

        return $message;
    }
}
