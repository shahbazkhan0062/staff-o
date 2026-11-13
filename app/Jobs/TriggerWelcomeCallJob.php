<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RetellOutboundCallService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Places the outbound welcome call to a client or resource partner after
 * they register. Dispatched (with a short delay — see
 * welcome_call_delay_minutes) from AuthController::register().
 *
 * `state` is not collected on the registration form itself (it's only set
 * later, when the user completes their profile), so this does not wait or
 * retry for it — it calls regardless. The welcome-call agent's prompt asks
 * the caller for their state itself, right after the opening greeting, and
 * branches on the spoken answer rather than relying on {{state}} being
 * pre-filled.
 */
class TriggerWelcomeCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $userId
    ) {
    }

    public function handle(RetellOutboundCallService $calls): void
    {
        $user = User::with(['customer', 'contractor'])->find($this->userId);

        if (!$user) {
            Log::info('[WelcomeCall] user no longer exists, skipping', ['user_id' => $this->userId]);
            return;
        }

        // Only clients and resource partners get this call — never
        // STAFFOO's own guards, and never a second time.
        if (!in_array($user->user_type, ['customer', 'contractor'], true)) {
            return;
        }

        $phone = $user->user_type === 'customer'
            ? ($user->customer->phone ?? $user->phone)
            : ($user->contractor->phone ?? $user->phone);

        if (empty($phone)) {
            Log::info('[WelcomeCall] skipped — no phone number on file', ['user_id' => $user->id]);
            return;
        }

        try {
            $calls->placeWelcomeCall($user, $phone);
        } catch (\Throwable $e) {
            Log::error('[WelcomeCall] failed to place call', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}