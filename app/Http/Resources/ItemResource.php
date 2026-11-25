<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, kind: string, title: string, preview: string|null, last_response_at: string|null, closed_at: string|null, closed_by: string|null, messages?: mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'title' => $this->title,
            'preview' => $this->previewFor($request),
            'last_response_at' => $this->latestMessage?->created_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'closed_by' => $this->closedBy?->nameFor($request->user()),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }

    private function previewFor(Request $request): ?string
    {
        $message = $this->latestMessage;

        if (! $message instanceof Message) {
            return null;
        }

        $author = $message->author?->nameFor($request->user()) ?? 'Former member';

        if ($message->hasAttachment()) {
            return "{$author} shared a file: {$message->attachment_name}";
        }

        return "{$author}: “{$message->body}”";
    }
}
