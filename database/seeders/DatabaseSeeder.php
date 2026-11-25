<?php

namespace Database\Seeders;

use App\Enums\ItemKind;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Local demo data: two members (password "password") and the prototype's to-dos and threads.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $alex = User::factory()->create(['name' => 'Alex', 'email' => 'alex@example.com']);
        $sam = User::factory()->create(['name' => 'Sam', 'email' => 'sam@example.com']);

        $this->seedItem(ItemKind::Todo, 'Buy the bathroom paint', $sam, [
            [$sam, 'Do we want to keep the walls light, or try that soft green we saw?'],
            [$alex, 'I like the green! Let’s check it in the room before we decide.'],
            [$sam, 'I can grab a few samples after work 💚'],
        ], now()->subHours(3));

        $this->seedItem(ItemKind::Todo, 'Call the plumber', $alex, [
            [$alex, 'The sink is still leaking underneath. I think it might be the seal.'],
            [$sam, 'Could you call the plumber we used last year and see when they’re free?'],
        ], now()->subDay());

        $this->seedItem(ItemKind::Todo, 'Send the insurance form', $sam, [
            [$sam, 'The form is in the blue folder by the front door.'],
            [$alex, 'Got it. I’ll fill it out and send it Friday.'],
        ], now()->subDays(2));

        $this->seedItem(ItemKind::Thread, 'Weekend plans', $sam, [
            [$sam, 'Should we invite your sister over Saturday?'],
            [$alex, 'That sounds nice. I’ll check if she’s free.'],
            [$sam, 'We could make dinner here if she can come.'],
        ], now()->subDay());

        $this->seedItem(ItemKind::Thread, 'Date night ideas', $alex, [
            [$alex, 'I found a little Italian place nearby that looks lovely.'],
            [$sam, 'That looks great. Friday could work for me!'],
        ], now()->subDays(3));

        $this->seedItem(ItemKind::Todo, 'Replace the entryway light', $alex, [
            [$alex, 'The entryway light is flickering again. We should replace it this week.'],
            [$sam, 'It’s installed and working. The entry feels much brighter now.'],
        ], now()->subDays(4))->close($sam);
    }

    /**
     * @param  list<array{0: User, 1: string}>  $messages
     */
    private function seedItem(ItemKind $kind, string $title, User $creator, array $messages, Carbon $startedAt): Item
    {
        $item = new Item(['kind' => $kind, 'title' => $title, 'last_activity_at' => $startedAt]);
        $item->creator()->associate($creator);
        $item->save();

        foreach ($messages as $index => [$author, $body]) {
            $message = $item->messages()->make(['body' => $body]);
            $message->author()->associate($author);
            $message->created_at = $startedAt->copy()->addMinutes($index * 20);
            $message->save();
            $item->last_activity_at = $message->created_at;
        }

        $item->save();

        return $item;
    }
}
