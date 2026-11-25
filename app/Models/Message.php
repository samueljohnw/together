<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['body', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * Raster image types browsers can show inline. SVG is deliberately excluded because it can carry scripts.
     *
     * @var list<string>
     */
    public const array PreviewableImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];

    /**
     * Video types browsers can play inline.
     *
     * @var list<string>
     */
    public const array PlayableVideoTypes = ['video/mp4', 'application/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachment_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null;
    }

    /**
     * Whether the attachment can be viewed on the site: "image", "video", or null for anything else.
     */
    public function mediaKind(): ?string
    {
        return $this->hasAttachment() ? self::mediaKindOf($this->attachment_mime) : null;
    }

    public static function mediaKindOf(?string $mimeType): ?string
    {
        return match (true) {
            in_array($mimeType, self::PreviewableImageTypes, true) => 'image',
            in_array($mimeType, self::PlayableVideoTypes, true) => 'video',
            default => null,
        };
    }
}
