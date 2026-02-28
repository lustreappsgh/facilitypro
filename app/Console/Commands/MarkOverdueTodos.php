<?php

namespace App\Console\Commands;

use App\Enums\TodoStatus;
use App\Models\Todo;
use Illuminate\Console\Command;

class MarkOverdueTodos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'todos:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark pending todos from past weeks as overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // A week ends on Sunday. If today is past the end of the week, the todo is overdue.
        // week_start is Monday. End of week is Sunday.
        // If now() > week_start + 6 days (end of week), it is overdue?
        // Actually, if we are in the week *after*, it's overdue.
        // So if week_start is older than start of this week (last Monday or earlier).
        
        $currentWeekStart = now()->startOfWeek();

        $count = Todo::query()
            ->where('status', TodoStatus::Pending)
            ->where('week_start', '<', $currentWeekStart)
            ->update(['status' => TodoStatus::Overdue]);

        $this->info("Marked {$count} todos as overdue.");
    }
}
