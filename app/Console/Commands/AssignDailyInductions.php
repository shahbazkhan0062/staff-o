<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InductionAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AssignDailyInductions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-daily-inductions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily: give every active staff/contractor user any induction (authored by admin_id 292) for their type that they don\'t already have, and email them about it';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('=== Assign Daily Inductions Command Started ===');
        $this->info('Assigning missing inductions to staff and contractor users...');

        $totalAssigned = 0;
        $totalUsersAffected = 0;

        foreach (['staff', 'contractor'] as $type) {
            $users = User::where('user_type', $type)
                // ->where('is_active', 1)
                ->get(['id', 'name', 'email']);

            $this->info("Checking {$users->count()} {$type} user(s)...");

            foreach ($users as $user) {
                try {
                    $newlyAssigned = InductionAssignmentService::assignToUser($user->id, $type);

                    if (!empty($newlyAssigned)) {
                        $totalAssigned += count($newlyAssigned);
                        $totalUsersAffected++;

                        $this->info("  -> assigned " . count($newlyAssigned) . " {$type} induction(s) to user #{$user->id} ({$user->email})");

                        Log::info('Daily induction assignment made', [
                            'user_id' => $user->id,
                            'type' => $type,
                            'induction_ids' => collect($newlyAssigned)->pluck('id')->all(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    $this->error("  -> failed for user #{$user->id}: " . $e->getMessage());

                    Log::error('Daily induction assignment failed for user', [
                        'user_id' => $user->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        $this->info("Done. {$totalAssigned} new induction assignment(s) made across {$totalUsersAffected} user(s).");

        Log::info('=== Assign Daily Inductions Command Finished ===', [
            'total_assigned' => $totalAssigned,
            'total_users_affected' => $totalUsersAffected,
        ]);

        return Command::SUCCESS;
    }
}
