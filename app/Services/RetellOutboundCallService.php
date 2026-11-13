<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Places the one-off outbound welcome call to a client or resource partner
 * shortly after they register — a separate Retell agent from the in-app/
 * WhatsApp support chat agent (RetellChatService), on its own phone number.
 *
 * Mirrors RetellChatService's conventions (base URL, auth, logging) but
 * talks to Retell's phone-call endpoint instead of the chat endpoint.
 */
class RetellOutboundCallService
{
    protected string $baseUrl = 'https://api.retellai.com';
    protected string $apiKey;
    protected ?string $agentId;
    protected ?string $fromNumber;

    public function __construct()
    {
        $this->apiKey     = (string) config('services.retell.api_key');
        $this->agentId    = config('services.retell.outbound_agent_id') ?: null;
        $this->fromNumber = config('services.retell.outbound_from_number') ?: null;

        if ($this->apiKey === '') {
            throw new RuntimeException(
                'Retell is not configured: services.retell.api_key is missing. '
                . 'Set RETELL_API_KEY in .env, then run php artisan config:clear.'
            );
        }

        if (empty($this->fromNumber)) {
            throw new RuntimeException(
                'Outbound welcome calls are not configured: services.retell.outbound_from_number is missing. '
                . 'Set RETELL_OUTBOUND_FROM_NUMBER in .env to the Twilio/Retell number the welcome-call agent '
                . 'is attached to, then run php artisan config:clear.'
            );
        }
    }

    /**
     * Place the welcome call for a just-registered client or resource
     * partner. $toPhone must already be a real, dialable number — this
     * method does not look one up.
     *
     * account_type/state/email are read fresh off $user, not cached from
     * registration time, so a state filled in after registering (state is
     * not collected at sign-up — see TriggerWelcomeCallJob) is picked up
     * correctly.
     */
    public function placeWelcomeCall(User $user, string $toPhone): bool
    {
        $accountType = match ($user->user_type) {
            'customer'   => 'client',
            'contractor' => 'resource_partner',
            default      => $user->user_type,
        };

        $dynamicVariables = [
            'caller_name'  => (string) ($user->name ?: 'there'),
            'account_type' => $accountType,
            'state'        => (string) ($user->state ?: ''),
            'caller_email' => (string) ($user->email ?: ''),
        ];

        $payload = [
            'from_number' => $this->fromNumber,
            'to_number'   => $toPhone,
            'retell_llm_dynamic_variables' => $dynamicVariables,
        ];

        // Only needed if this Twilio/Retell number isn't already dedicated
        // to the welcome-call agent in Retell's dashboard.
        if (!empty($this->agentId)) {
            $payload['override_agent_id'] = $this->agentId;
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->timeout(20)
            ->post($this->baseUrl . '/v2/create-phone-call', $payload);

        if (!$response->successful()) {
            Log::error('[Retell] welcome call create-phone-call failed', [
                'user_id' => $user->id,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);

            return false;
        }

        Log::info('[Retell] welcome call placed', [
            'user_id'      => $user->id,
            'account_type' => $accountType,
            'state'        => $dynamicVariables['state'],
            'call_id'      => $response->json('call_id'),
        ]);

        return true;
    }
}
