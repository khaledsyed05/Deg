<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('support_tickets')
            ->whereNull('ticket_number')
            ->orderBy('id')
            ->chunkById(500, function ($tickets) {
                foreach ($tickets as $ticket) {
                    $date = Carbon::parse($ticket->created_at)->format('Ymd');
                    DB::table('support_tickets')
                        ->where('id', $ticket->id)
                        ->update([
                            'ticket_number' => sprintf('TKT-%s-%04d', $date, $ticket->id),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No-op: not safe to wipe ticket numbers
    }
};
