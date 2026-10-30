<?php

namespace App\Services;

use App\Models\AppChat;
use App\Models\User;

/**
 * In-app chat -> the SAME Retell agent used for WhatsApp, for an
 * authenticated user messaging support. Mirrors
 * WhatsappWebhookController::resolveChat(), but keyed by user_id instead of
 * phone, since the sender is already a known, logged-in User.
 */
class AppChatService
{
    public function __construct(protected RetellChatService $retell)
    {
    }

    /**
     * Send one message through the agent on behalf of $user, opening or
     * reusing their session, and return the agent's reply ('' if it ran a
     * tool but said nothing).
     */
    public function reply(User $user, string $body): string
    {
        $chat = $this->resolveChat($user);

        $reply = $this->retell->sendMessage($chat->chat_id, $body);

        $chat->update([
            'last_message_at' => now(),
            'message_count'   => $chat->message_count + 1,
        ]);

        return $reply;
    }

    protected function resolveChat(User $user): AppChat
    {
        $timeout = (int) config('services.retell.chat_timeout_minutes', 180);

        $chat = AppChat::where('user_id', $user->id)->first();

        if ($chat && $chat->chat_id && $chat->status === 'active' && !$chat->isStale($timeout)) {
            return $chat;
        }

        if ($chat && $chat->chat_id) {
            $this->retell->endChat($chat->chat_id);
        }

        $chatId = $this->retell->createChat(
            $this->dynamicVariablesFor($user),
            ['channel' => 'app', 'user_id' => $user->id]
        );

        if ($chat) {
            $chat->update([
                'chat_id'         => $chatId,
                'status'          => 'active',
                'last_message_at' => now(),
                'message_count'   => 0,
            ]);

            return $chat->refresh();
        }

        return AppChat::create([
            'user_id'         => $user->id,
            'chat_id'         => $chatId,
            'status'          => 'active',
            'last_message_at' => now(),
            'message_count'   => 0,
        ]);
    }

    /**
     * Same mapping as WhatsappWebhookController::dynamicVariablesFor(), but
     * caller_phone is deliberately left blank. The prompt tells the agent
     * to always pass {{caller_phone}} as the "phone" argument on
     * my_shifts/pay_status/verify_identity_*, which gates those behind a
     * code emailed to that phone's matching WhatsappChat row. An in-app
     * message is already from an authenticated, logged-in user — at least
     * as strong an identity check — so an empty phone here makes those
     * tools skip that challenge for this channel, using the "nothing to
     * verify against" branch AgentLookupController::requireOtp() already
     * has (the same one a phone-less direct call hits today). No change
     * needed there.
     */
    protected function dynamicVariablesFor(User $user): array
    {
        $type = match ($user->user_type) {
            'customer'   => 'client',
            'contractor' => 'contractor',
            'staff'      => ((int) $user->user_id === 1) ? 'staffoo_staff' : 'contractor_staff',
            default      => (string) $user->user_type,
        };

        return [
            'caller_known'  => 'yes',
            'caller_phone'  => '',
            'caller_name'   => (string) $user->name,
            'caller_email'  => (string) $user->email,
            'account_type'  => $type,
            'account_state' => ((int) $user->is_active === 1) ? 'active' : 'inactive',
        ];
    }
}