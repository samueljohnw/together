<?php

namespace App\Models;

use App\Enums\ItemKind;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['kind', 'title', 'last_activity_at'])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ItemKind::class,
            'last_activity_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * @param  Builder<Item>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    /**
     * @param  Builder<Item>  $query
     */
    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->whereNotNull('closed_at');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function close(User $closedBy): void
    {
        $this->forceFill([
            'closed_at' => now(),
            'closed_by_id' => $closedBy->getKey(),
        ])->save();
    }

    public function reopen(): void
    {
        $this->forceFill([
            'closed_at' => null,
            'closed_by_id' => null,
            'last_activity_at' => now(),
        ])->save();
    }
}
