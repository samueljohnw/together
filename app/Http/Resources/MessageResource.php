<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, author: string, initials: string, is_mine: bool, body: string|null, created_at: string, attachment: array{name: string, size: int, type: string, url: string, media: 'image'|'video'|null, preview_url: string|null}|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author?->nameFor($request->user()) ?? 'Former member',
            'initials' => $this->author?->initials() ?? '?',
            'is_mine' => $this->author !== null && $this->author->is($request->user()),
            'body' => $this->body,
            'created_at' => $this->created_at->toIso8601String(),
            'attachment' => $this->hasAttachment() ? [
                'name' => $this->attachment_name,
                'size' => $this->attachment_size,
                'type' => $this->attachment_mime ?? 'File',
                'url' => route('messages.attachment', $this->resource),
                'media' => $this->mediaKind(),
                'preview_url' => $this->mediaKind() !== null ? route('messages.attachment.preview', $this->resource) : null,
            ] : null,
        ];
    }
}
