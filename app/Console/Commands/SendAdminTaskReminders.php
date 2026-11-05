<?php

namespace App\Console\Commands;

use App\Mail\AdminTaskReminderMail;
use App\Models\AdminTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * php artisan admin-tasks:send-reminders
 *
 * Once a day, emails every admin who still has pending tasks assigned to
 * them a single digest listing all of those tasks (one email per admin,
 * not one per task). Scheduled daily in App\Console\Kernel.
 */
class SendAdminTaskReminders extends Command
{
    protected $signature = 'admin-tasks:send-reminders';
    protected $description = 'Send each admin a daily reminder email of their pending tasks';

    public function handle(): int
    {
        $pendingTasks = AdminTask::with(['assignedBy:id,name,email', 'assignedTo:id,name,email'])
            ->pending()
            ->get()
            ->groupBy('assigned_to');

        if ($pendingTasks->isEmpty()) {
            $this->info('No pending admin tasks — nothing to remind.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($pendingTasks as $assigneeId => $tasks) {
            $assignee = $tasks->first()->assignedTo;

            if (!$assignee || !$assignee->email) {
                continue;
            }

            try {
                Mail::to($assignee->email)->send(new AdminTaskReminderMail($assignee->name, $tasks));

                AdminTask::whereIn('id', $tasks->pluck('id'))->update(['last_reminder_sent_at' => now()]);

                $this->info("  ✓ {$assignee->email} — {$tasks->count()} pending task(s)");
                $sent++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed for {$assignee->email}: {$e->getMessage()}");
                Log::error('AdminTaskReminderMail failed', [
                    'assigned_to' => $assigneeId,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Reminder emails sent: {$sent}.");

        return self::SUCCESS;
    }
}
