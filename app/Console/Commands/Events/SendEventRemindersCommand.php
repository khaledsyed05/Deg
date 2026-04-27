<?php

namespace App\Console\Commands\Events;

use App\Models\Event;
use App\Notifications\Event\EventReminderNotification;
use Illuminate\Console\Command;

class SendEventRemindersCommand extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send 24h and 1h reminders for upcoming events to confirmed participants.';

    public function handle(): int
    {
        $this->dispatchWindow(now()->addHours(23), now()->addHours(25), '24h');
        $this->dispatchWindow(now()->addMinutes(55), now()->addMinutes(65), '1h');

        return self::SUCCESS;
    }

    private function dispatchWindow(\DateTimeInterface $from, \DateTimeInterface $to, string $window): void
    {
        Event::whereBetween('starts_at', [$from, $to])
            ->where('status', 'open')
            ->with(['confirmedRegistrations.user'])
            ->get()
            ->each(function (Event $event) use ($window) {
                foreach ($event->confirmedRegistrations as $reg) {
                    if ($reg->user) {
                        $reg->user->notify(new EventReminderNotification($event, $window));
                    }
                }
            });
    }
}
