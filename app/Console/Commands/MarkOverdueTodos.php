<?php

namespace App\Console\Commands;

use App\Enums\TodoStatus;
use App\Models\Todo;
use Carbon\Carbon;
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
        $currentWeekStart = now()->startOfWeek(Carbon::SUNDAY);

        $count = Todo::query()
            ->where('status', TodoStatus::Pending)
            ->where('week_start', '<', $currentWeekStart)
            ->update(['status' => TodoStatus::Overdue]);

        $this->info("Marked {$count} todos as overdue.");
    }
}
